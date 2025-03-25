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
header('Access-Control-Allow-Methods: GET, OPTIONS');
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
    echo json_encode(['error' => 'No token provided']);
    exit;
}

$token = str_replace('Bearer ', '', $headers['Authorization']);
$payload = verifyToken($token);

if (!$payload) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid token']);
    exit;
}

try {
    $db = getDBConnection();
    
    // Return empty data if tables don't exist yet
    $tables = [
        'analytics_overview' => false,
        'user_analytics' => false,
        'sentiment_analysis' => false
    ];
    
    // Check which tables exist
    $stmt = $db->query("SHOW TABLES");
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        if (isset($tables[$row[0]])) {
            $tables[$row[0]] = true;
        }
    }
    
    // Return empty data if no tables exist
    if (!array_filter($tables)) {
        $response = [
            'status' => 'success',
            'data' => [],
            'message' => 'No analytics data available yet'
        ];
        error_log('No analytics tables found, returning empty data');
        ob_end_clean();
        echo json_encode($response);
        exit;
    }
    
    // Build query based on existing tables
    $query = [];
    $params = [];
    
    if ($tables['analytics_overview']) {
        $query[] = "(SELECT 
            'overview' as type,
            'Analytics Overview' as description,
            created_at,
            id,
            user_id
        FROM analytics_overview
        WHERE user_id = ?)";
        $params[] = $payload->user_id;
    }
    
    if ($tables['user_analytics']) {
        $query[] = "(SELECT 
            'user' as type,
            'User Demographics Analysis' as description,
            created_at,
            id,
            user_id
        FROM user_analytics
        WHERE user_id = ?)";
        $params[] = $payload->user_id;
    }
    // Combine queries with UNION ALL if we have any
    if (empty($query)) {
        $response = [
            'status' => 'success',
            'data' => [],
            'message' => 'No analytics data available for this user'
        ];
        error_log('No analytics data found for user ' . $payload->user_id);
        ob_end_clean();
        echo json_encode($response);
        exit;
    }
    
    $finalQuery = implode("
UNION ALL
", $query) . "
ORDER BY created_at DESC LIMIT 10";
    error_log('Executing query: ' . $finalQuery);
    
    $stmt = $db->prepare($finalQuery);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the response
    $analytics = array_map(function($item) {
        return [
            'id' => $item['id'],
            'type' => $item['type'],
            'description' => htmlspecialchars($item['description']),
            'created_at' => $item['created_at']
        ];
    }, $results);
    
    $response = [
        'status' => 'success',
        'data' => $analytics
    ];
    
    ob_end_clean();
    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
    error_log($e->getMessage());
}
