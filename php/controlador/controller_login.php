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
    // Validated against plf.sql, 'matricula' does not exist. We fetch 'id' and 'rol' instead.
    $sql = "SELECT id, nombre, correo, contrasenia, rol FROM usuario WHERE nombre = ?";
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
        // Return structured data for the frontend mapping. [id, nombre, correo, rol]
        // Note: Javascript legacy code expects the role at index 4 if sent as an array. 
        // For backwards compatibility with login.js mapping: `res.resultado[4]`, we can return specific indexes, 
        // but since we updated it to JSON, returning a standard mapped array works better:
        return [$user['id'], $user['nombre'], $user['correo'], 'dummy_index', $user['rol']];
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

    // The real database schema for `usuario` based on plf.sql is: id, nombre, correo, contrasenia, registro, rol.
    // Defaulting role to 'user' for public registration.
    $rol = 'user';
    $insertSql = "INSERT INTO usuario (nombre, correo, contrasenia, registro, rol) VALUES (?, ?, ?, ?, ?)";
    $insertStmt = $con->prepare($insertSql);

    if (!$insertStmt) {
        throw new Exception("Failed to prepare insertion statement: " . $con->error);
    }

    $insertStmt->bind_param("sssss", $data->nombre, $data->correo, $hashedPassword, $registrationDate, $rol);
    $success = $insertStmt->execute();
    $insertStmt->close();

    return $success;
}
?>