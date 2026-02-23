<?php
// Active error reporting during development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Standardize header output to JSON
header('Content-Type: application/json; charset=UTF-8');
date_default_timezone_set('America/Mexico_City');

// Base response structure for AJAX consistency
$response = [
    'status' => 'error',
    'message' => 'Unknown error occurred.',
    'data' => null
];

// Payload validation to prevent silent failures
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
        $response['status'] = 'success';
        $response['message'] = 'Action successfully processed.';
        // Descriptive function name revealing intent
        $response['data'] = ['resultado' => processInitialAction($requestData)];
    }
    else {
        $response['message'] = 'Invalid action specified.';
    }
}
catch (Exception $e) {
    // Ensuring no silent errors
    $response['message'] = 'Server error: ' . $e->getMessage();
}

// Consistent JSON Output
echo json_encode($response);
exit;

// ----------------------------------------------------
// Helper Functions 
// ----------------------------------------------------

/**
 * Processes the initial action triggered by the ESP32.
 * @param object $data The decoded JSON payload sent by the client.
 * @return string
 */
function processInitialAction($data)
{
    // Specific logic for action 0
    return "Action 0 processed";
}

?>