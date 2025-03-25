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
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Debug log for request
error_log('Request method: ' . $_SERVER['REQUEST_METHOD']);
error_log('Raw input: ' . file_get_contents('php://input'));

// Get raw input and parse JSON
$rawInput = file_get_contents('php://input');
$contentType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';

// Debug log
error_log('Content-Type header: ' . $contentType);
error_log('Raw input length: ' . strlen($rawInput));

// Parse JSON data
$data = json_decode($rawInput, true);

// Check for JSON parsing errors
if (json_last_error() !== JSON_ERROR_NONE) {
    error_log('JSON parse error: ' . json_last_error_msg());
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'error' => 'Invalid JSON data: ' . json_last_error_msg()
    ]);
    exit;
}

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204); // No content for preflight
    ob_end_clean();
    exit(0);
}

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'error' => 'Method not allowed'
    ]);
    exit;
}

// Get and validate POST data
$data = json_decode(file_get_contents('php://input'), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    error_log('Invalid JSON data received: ' . json_last_error_msg());
    http_response_code(400);
    ob_end_clean();
    $response = json_encode([
        'status' => 'error',
        'error' => 'Invalid request data: ' . json_last_error_msg()
    ]);
    error_log('Sending response: ' . $response);
    echo $response;
    exit;
}

if (!isset($data['email']) || !isset($data['password'])) {
    error_log('Missing email or password');
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'error' => 'Email and password are required'
    ]);
    exit;
}

$email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    error_log('Invalid email format');
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'error' => 'Invalid email format'
    ]);
    exit;
}

$password = $data['password'];

try {
    // Connect to database
    $db = getDBConnection();
    
    // Check if user exists and verify password
    $stmt = $db->prepare('SELECT id, email, password_hash FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user || !password_verify($password, $user['password_hash'])) {
        error_log('Authentication failed for email: ' . $email);
        http_response_code(401);
        echo json_encode([
        'status' => 'error',
        'error' => 'Invalid credentials'
    ]);
        exit;
    }
    
    // Generate JWT token
    $token = generateToken($user['id']);
    
    // Update last login timestamp
    $stmt = $db->prepare('UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?');
    $stmt->execute([$user['id']]);
    
    // Get token expiration time
    $tokenData = jwt_decode($token, JWT_SECRET);
    
    // Prepare success response
    $response = [
        'status' => 'success',
        'token' => $token,
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'last_login' => date('Y-m-d H:i:s')
        ],
        'token_expires' => date('Y-m-d H:i:s', $tokenData->exp)
    ];
    
    error_log('Login successful for user: ' . $user['id'] . ' (token expires: ' . $response['token_expires'] . ')');
    
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
        error_log('Sending success response: ' . $jsonResponse);
        echo $jsonResponse;
    }
    
} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    http_response_code(500);
    ob_end_clean();
    $response = json_encode([
        'status' => 'error',
        'error' => 'Database error'
    ]);
    error_log('Sending error response: ' . $response);
    echo $response;
} catch (Exception $e) {
    error_log('Server error: ' . $e->getMessage());
    http_response_code(500);
    ob_end_clean();
    $response = json_encode([
        'status' => 'error',
        'error' => 'Server error'
    ]);
    error_log('Sending error response: ' . $response);
    echo $response;
}
?>
