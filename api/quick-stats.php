<?php
require_once 'config.php';
require_once 'services/AIAnalyticsService.php';

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
    
    // Get active users (users who logged in within the last 24 hours)
    $stmt = $db->prepare('
        SELECT COUNT(DISTINCT user_id) as active_users 
        FROM user_sessions 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ');
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $activeUsers = $result ? $result['active_users'] : 0;

    // Calculate growth rate (new users in last 7 days compared to previous 7 days)
    $stmt = $db->prepare('
        SELECT 
            (SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)) as current_week,
            (SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 14 DAY) 
             AND created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)) as previous_week
    ');
    $stmt->execute();
    $growth = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Handle case where there are no users
    if (!$growth) {
        $growth = ['current_week' => 0, 'previous_week' => 0];
    }
    $growthRate = $growth['previous_week'] > 0 
        ? (($growth['current_week'] - $growth['previous_week']) / $growth['previous_week']) * 100 
        : 0;

    // Get average sentiment score from recent AI analytics
    $stmt = $db->prepare('
        SELECT COALESCE(AVG(sentiment_score), 0) as avg_sentiment 
        FROM analytics_data 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        AND sentiment_score IS NOT NULL
    ');
    $stmt->execute();
    $sentimentScore = $stmt->fetch(PDO::FETCH_ASSOC)['avg_sentiment'] ?? 0;

    // Calculate average session duration in minutes
    $stmt = $db->prepare('
        SELECT COALESCE(AVG(TIMESTAMPDIFF(MINUTE, created_at, last_activity)), 0) as avg_duration 
        FROM user_sessions 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ');
    $stmt->execute();
    $avgSession = round($stmt->fetch(PDO::FETCH_ASSOC)['avg_duration'] ?? 0);

    echo json_encode([
        'activeUsers' => $activeUsers,
        'growthRate' => round($growthRate, 1),
        'sentimentScore' => $sentimentScore,
        'avgSession' => $avgSession
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
    error_log($e->getMessage());
}
