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
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, Accept');
header('Access-Control-Expose-Headers: *');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204); // No content for preflight
    ob_end_clean();
    exit(0);
}

// Verify JWT token
$headers = getallheaders();
if (!isset($headers['Authorization'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'No token provided']);
    exit;
}

$token = str_replace('Bearer ', '', $headers['Authorization']);
$payload = verifyToken($token);

if (!$payload) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Invalid token']);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

// Get request body
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['id']) || !is_numeric($input['id'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid analytic ID']);
    exit;
}

try {
    $db = getDBConnection();
    
    // Check if the analytic exists and belongs to the user
    $stmt = $db->prepare('
        SELECT id FROM analytics_overview 
        WHERE id = ? AND user_id = ?
        UNION ALL
        SELECT id FROM user_analytics
        WHERE id = ? AND user_id = ?
        UNION ALL
        SELECT id FROM sentiment_analysis
        WHERE id = ? AND user_id = ?
    ');
    
    $stmt->execute([
        $input['id'], $payload->user_id,
        $input['id'], $payload->user_id,
        $input['id'], $payload->user_id
    ]);
    
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode([
            'status' => 'error',
            'message' => 'Analytic not found or does not belong to you'
        ]);
        exit;
    }
    
    // Save to user's saved analytics
    $stmt = $db->prepare('
        INSERT INTO saved_analytics (user_id, analytic_id, created_at)
        VALUES (?, ?, NOW())
        ON DUPLICATE KEY UPDATE created_at = NOW()
    ');
    
    $stmt->execute([$payload->user_id, $input['id']]);
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Analytic saved successfully'
    ]);

} catch (Exception $e) {
    error_log('Error saving analytic: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Internal server error'
    ]);
}
