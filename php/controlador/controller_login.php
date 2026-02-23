<?php
// Rule: Mandatory Debugging Blocks
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Rule: AJAX Consistency
header('Content-Type: application/json; charset=UTF-8');
date_default_timezone_set('America/Mexico_City');

// Fallback JSON structure
$response = [
    'status' => 'error',
    'message' => 'Unknown error occurred.',
    'data' => null
];

// Payload Data Validation
if (!isset($_POST['trama']) || empty($_POST['trama'])) {
    $response['message'] = 'Missing data payload (trama).';
    echo json_encode($response);
    exit;
}

$requestData = json_decode($_POST['trama']);

if ($requestData === null) {
    $response['message'] = 'Invalid JSON format received.';
    echo json_encode($response);
    exit;
}

try {
    if (!isset($requestData->accion)) {
        throw new Exception("No action specified.");
    }

    if ($requestData->accion == 0) {
        $result = processLogin($requestData);
        if ($result !== false) {
            $response['status'] = 'success';
            $response['message'] = 'Login successful.';
            $response['data'] = $result;
        }
        else {
            $response['message'] = 'Invalid credentials.';
        }
    }
    else if ($requestData->accion == 1) {
        $result = registerUser($requestData);
        if ($result) {
            $response['status'] = 'success';
            $response['message'] = 'User successfully registered.';
        }
        else {
            $response['message'] = 'Registration failed. User might already exist.';
        }
    }
    else {
        $response['message'] = 'Invalid action specified.';
    }
}
catch (Exception $e) {
    // Rule: No Silent Errors
    $response['message'] = 'Server error: ' . $e->getMessage();
}

echo json_encode($response);
exit;


// ------------------------------------------------------------------
// Helper Functions (Rule: Single Responsibility & SQL Integrity)
// ------------------------------------------------------------------

/**
 * Validates user credentials securely against the database.
 */
function processLogin($data)
{
    include("conexion.php");

    // Rule: SQL Integrity via Prepared Statements
    $sql = "SELECT matricula, nombre, correo, contrasenia FROM usuario WHERE nombre = ?";
    $stmt = $con->prepare($sql);

    if (!$stmt) {
        throw new Exception("Failed to prepare statement.");
    }

    $stmt->bind_param("s", $data->nombre);
    $stmt->execute();
    $result = $stmt->get_result();

    // Rule: Early Returns (Cleaner Scope)
    if ($result->num_rows === 0) {
        $stmt->close();
        return false;
    }

    $user = $result->fetch_assoc();
    $stmt->close();

    if (password_verify($data->contraseña, $user['contrasenia'])) {
        return [$user['matricula'], $user['nombre'], $user['correo']];
    }

    return false;
}

/**
 * Registers a new user securely, verifying duplicates and using prepared queries.
 */
function registerUser($data)
{
    include("conexion.php");
    $registrationDate = date("Y-m-d H:i:s");

    // 1. Verify if the username already exists (Prepared Statement)
    $checkSql = "SELECT id FROM usuario WHERE nombre = ?";
    $checkStmt = $con->prepare($checkSql);
    $checkStmt->bind_param("s", $data->nombre);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    $userExists = $checkResult->num_rows > 0;
    $checkStmt->close();

    // Rule: Early Returns
    if ($userExists) {
        return false;
    }

    // 2. Insert new user securely (Prepared Statement)
    $hashedPassword = password_hash($data->contraseña, PASSWORD_BCRYPT);
    $insertSql = "INSERT INTO usuario (nombre, correo, contrasenia, registro) VALUES (?, ?, ?, ?)";

    // Note: It seems the original code passed matricula inside contrasenia's place or viceversa, 
    // adapting to match the DB columns: nombre, correo, contrasenia, registro.
    // Ensure data object contains correct fields. If 'matricula' is a DB field, insert it properly.
    // The original code passed: '$valores->nombre', '$valores->correo', '$valores->matricula' instead of password! 
    // This looks like a bug in original code, fixing it to map standard structure based on your original insert.
    // Assuming original intent based on values passed (nombre, correo, contrasenia, registro). The original code had 5 values:
    // INSERT INTO usuario(nombre,correo,contrasenia,registro) VALUES ('$valores->nombre','$valores->correo','$valores->matricula','$hashed_contraseña','$registro')
    // Wait, original has 4 columns: nombre,correo,contrasenia,registro but passed 5 values!
    // Let's look at the database schema. Based on plf.sql, `usuario` has: (id, nombre, contrasenia, matricula, correo, tipo, registro, estatus).
    // Let's bind them correctly.

    $insertSql = "INSERT INTO usuario (nombre, correo, matricula, contrasenia, registro) VALUES (?, ?, ?, ?, ?)";
    $insertStmt = $con->prepare($insertSql);

    if (!$insertStmt) {
        throw new Exception("Failed to prepare insertion statement.");
    }

    $insertStmt->bind_param("sssss", $data->nombre, $data->correo, $data->matricula, $hashedPassword, $registrationDate);
    $success = $insertStmt->execute();
    $insertStmt->close();

    return $success;
}
?>