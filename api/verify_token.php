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

// Get token from header
$headers = getallheaders();
if (!isset($headers['Authorization'])) {
    error_log('No Authorization header present');
    http_response_code(401);
    ob_end_clean();
    $response = json_encode([
        'status' => 'error',
        'error' => 'No token provided'
    ]);
    error_log('Sending response: ' . $response);
    echo $response;
    exit;
}

$token = str_replace('Bearer ', '', $headers['Authorization']);
error_log('Processing token verification request for token: ' . substr($token, 0, 20) . '...');

try {
    // Verify token
    $payload = verifyToken($token);
    if (!$payload) {
        error_log('Token verification failed');
        http_response_code(401);
        echo json_encode([
            'status' => 'error',
            'error' => 'Invalid or expired token'
        ]);
        exit;
    }
    
    // Get user data
    $db = getDBConnection();
    $stmt = $db->prepare('SELECT id, username, email, last_login FROM users WHERE id = ?');
    $stmt->execute([$payload->user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        error_log('User not found: ' . $payload->user_id);
        http_response_code(401);
        echo json_encode([
            'status' => 'error',
            'error' => 'User not found'
        ]);
        exit;
    }
    
    // Token is valid
    error_log('Token verified successfully for user: ' . $payload->user_id);
    error_log('Token verification successful for user: ' . $payload->user_id);
    
    // Update last login time
    $stmt = $db->prepare('UPDATE users SET last_login = NOW() WHERE id = ?');
    $stmt->execute([$payload->user_id]);
    
    $response = [
        'status' => 'success',
        'user' => $user,
        'token_expires' => date('Y-m-d H:i:s', $payload->exp)
    ];
    
    error_log('Response data: ' . json_encode($response));
    echo json_encode($response);

    // Get user data
    $db = getDBConnection();
    $stmt = $db->prepare('SELECT id, username, email FROM users WHERE id = ?');
    $stmt->execute([$payload->user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception('User not found');
    }

    // Update last login
    $stmt = $db->prepare('UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?');
    $stmt->execute([$user['id']]);

    // Get last login time
    $stmt = $db->prepare('SELECT last_login FROM users WHERE id = ?');
    $stmt->execute([$user['id']]);
    $lastLogin = $stmt->fetchColumn();

    $response = [
        'status' => 'success',
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'last_login' => $lastLogin
        ],
        'token_expires' => date('Y-m-d H:i:s', $payload->exp)
    ];

    error_log('Token verification successful for user: ' . $user['id']);
    error_log('Response data: ' . json_encode($response));
    
    // Clear any previous output and send response
    ob_end_clean();
    $jsonResponse = json_encode($response);
    if ($jsonResponse === false) {
        throw new Exception('Failed to encode response: ' . json_last_error_msg());
    }
    echo $jsonResponse;
    exit;

} catch (Exception $e) {
    error_log('Token verification failed: ' . $e->getMessage());
    http_response_code(401);
    
    // Clear any previous output
    ob_end_clean();
    
    // Prepare error response
    $errorResponse = [
        'status' => 'error',
        'error' => $e->getMessage()
    ];
    
    // Ensure proper JSON encoding
    $jsonError = json_encode($errorResponse);
    if ($jsonError === false) {
        error_log('Failed to encode error response: ' . json_last_error_msg());
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'error' => 'Internal server error'
        ]);
    } else {
        error_log('Sending error response: ' . $jsonError);
        echo $jsonError;
    }
    exit;
}
