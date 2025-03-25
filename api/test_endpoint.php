<?php
require_once 'config.php';

// Configure error logging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '/Applications/MAMP/logs/php_error.log');

// Ensure no whitespace or output before headers
ob_start();

// Set response headers
header_remove();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, Accept');
header('Access-Control-Expose-Headers: *');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204); // No content for preflight
    ob_end_clean();
    exit(0);
}

// Test database connection
try {
    $db = getDBConnection();
    $dbStatus = 'connected';
} catch (PDOException $e) {
    error_log('Database connection error: ' . $e->getMessage());
    $dbStatus = 'error';
}

// Test OpenAI configuration
$openaiStatus = (!empty(OPENAI_API_KEY) && !empty(OPENAI_ASSISTANT_ID)) ? 'configured' : 'not configured';

// Prepare response
$response = [
    'status' => 'success',
    'message' => 'API endpoint is accessible',
    'timestamp' => date('Y-m-d H:i:s'),
    'database' => $dbStatus,
    'openai' => $openaiStatus
];

// Clear any previous output and send response
ob_end_clean();
$jsonResponse = json_encode($response);
if ($jsonResponse === false) {
    error_log('Failed to encode response: ' . json_last_error_msg());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'error' => 'Internal server error'
    ]);
} else {
    error_log('Test endpoint response: ' . $jsonResponse);
    echo $jsonResponse;
}
