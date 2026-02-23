<?php
// Rule: Mandatory Debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Rule: AJAX Consistency
header('Content-Type: application/json; charset=UTF-8');
date_default_timezone_set('America/Mexico_City');

$response = [
    'status' => 'error',
    'message' => 'Unknown error occurred.',
    'data' => null
];

if (!isset($_POST['trama'])) {
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
    if (isset($requestData->accion) && $requestData->accion == 0) {
        $result = processPurchase($requestData);
        if ($result['success']) {
            $response['status'] = 'success';
            $response['message'] = $result['message'];
        }
        else {
            $response['message'] = $result['message'];
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
// Helper Functions (Rule: Single Responsibility)
// ------------------------------------------------------------------

/**
 * Creates purchase records based on the JSON payload.
 */
function processPurchase($data)
{
    include("../conexion.php");

    $date = date('Y-m-d H:i:s');

    // Rule: Data Type Validation
    if (!isset($data->productos) || !is_array($data->productos) || !isset($data->usuario_id)) {
        throw new Exception("Malformed product list or missing user ID in payload.");
    }

    $products = $data->productos;
    $userId = intval($data->usuario_id);

    $sql = "INSERT INTO venta (fecha, usuario_id, producto_id) VALUES (?, ?, ?)";
    $stmt = $con->prepare($sql);

    if (!$stmt) {
        throw new Exception("Failed to prepare sales query.");
    }

    $allSuccessful = true;
    foreach ($products as $productId) {
        $cleanProductId = intval($productId);
        $stmt->bind_param("sii", $date, $userId, $cleanProductId);
        if (!$stmt->execute()) {
            $allSuccessful = false;
            break;
        }
    }
    $stmt->close();

    if ($allSuccessful) {
        return ['success' => true, 'message' => 'Purchase registered successfully.'];
    }
    else {
        return ['success' => false, 'message' => 'Error registering one or more items.'];
    }
}
?>