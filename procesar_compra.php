<?php
// Rule: Mandatory Debugging Blocks
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'conexion.php';
// Rule: Session Validation for sensitive scripts
session_start();

// Rule: AJAX Consistency
header('Content-Type: application/json; charset=UTF-8');

$response = [
    'status' => 'error',
    'message' => 'Unknown error occurred.',
    'data' => null
];

// Verify Authentication
if (!isset($_SESSION['usuario'])) {
    $response['message'] = 'User is not authenticated. Please log in.';
    echo json_encode($response);
    exit();
}

// Rule: Data Type Validation
if (empty($_POST['producto_id']) || !is_array($_POST['producto_id'])) {
    $response['message'] = 'No products selected or invalid data format.';
    echo json_encode($response);
    exit();
}

try {
    // Get User ID (Prepared Statement)
    $usuario_nombre = $_SESSION['usuario'];
    $stmt = $con->prepare("SELECT id FROM usuario WHERE nombre = ?");

    if (!$stmt) {
        throw new Exception("Failed to prepare user query.");
    }

    $stmt->bind_param("s", $usuario_nombre);
    $stmt->execute();
    $stmt->bind_result($usuario_id);

    // Check if user exists in DB
    if (!$stmt->fetch()) {
        $stmt->close();
        throw new Exception("Authenticated user not found in the database. Constraint check failed.");
    }
    $stmt->close();

    // Insert each product as a separate sale (Prepared Statement)
    $stmt = $con->prepare("INSERT INTO venta (producto_id, usuario_id) VALUES (?, ?)");
    if (!$stmt) {
        throw new Exception("Failed to prepare sales query.");
    }

    $inserted_count = 0;
    foreach ($_POST['producto_id'] as $producto_id) {
        $producto_id_clean = intval($producto_id);
        if ($producto_id_clean > 0) {
            $stmt->bind_param("ii", $producto_id_clean, $usuario_id);
            $stmt->execute();
            $inserted_count++;
        }
    }
    $stmt->close();

    // Success response instead of HTML Redirect
    if ($inserted_count > 0) {
        $response['status'] = 'success';
        $response['message'] = 'Purchase successfully registered.';
        $response['data'] = ['items_bought' => $inserted_count];
    }
    else {
        $response['message'] = 'No valid products were processed.';
    }

}
catch (Exception $e) {
    // Rule: No Silent Errors
    $response['message'] = 'Transaction error: ' . $e->getMessage();
}

echo json_encode($response);
exit();
?>