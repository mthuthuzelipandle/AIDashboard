<?php
require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Verify JWT token
$headers = getallheaders();
if (!isset($headers['Authorization'])) {
    error_log('No Authorization header found in analytics.php');
    http_response_code(401);
    echo json_encode(['error' => 'No token provided']);
    exit;
}

$authHeader = $headers['Authorization'];
error_log('Authorization header found: ' . $authHeader);

if (strpos($authHeader, 'Bearer ') !== 0) {
    error_log('Invalid Authorization header format');
    http_response_code(401);
    echo json_encode(['error' => 'Invalid token format']);
    exit;
}

$token = substr($authHeader, 7); // Remove 'Bearer ' prefix
if (empty($token)) {
    error_log('Empty token provided');
    http_response_code(401);
    echo json_encode(['error' => 'Empty token']);
    exit;
}

error_log('Token extracted: ' . substr($token, 0, 10) . '...');
$payload = verifyToken($token);

if (!$payload) {
    error_log('Token verification failed in analytics.php');
    http_response_code(401);
    echo json_encode(['error' => 'Invalid token']);
    exit;
}

error_log('Token verified successfully in analytics.php. User ID: ' . $payload->user_id);

try {
    error_log('Attempting to fetch analytics data');
    $db = getDBConnection();
    error_log('Database connection successful');
    
    // Get gender distribution
    $genderQuery = "SELECT gender, COUNT(*) as count FROM analytics_customers GROUP BY gender";
    $genderStmt = $db->query($genderQuery);
    $genderDistribution = [];
    while ($row = $genderStmt->fetch(PDO::FETCH_ASSOC)) {
        $genderDistribution[] = [
            'gender' => ucfirst($row['gender']),
            'count' => (int)$row['count']
        ];
    }

    // Get age distribution
    $ageQuery = "SELECT 
        CASE 
            WHEN age < 25 THEN '18-24'
            WHEN age BETWEEN 25 AND 34 THEN '25-34'
            WHEN age BETWEEN 35 AND 44 THEN '35-44'
            WHEN age BETWEEN 45 AND 54 THEN '45-54'
            ELSE '55+'
        END as age_group,
        COUNT(*) as count 
    FROM analytics_customers 
    GROUP BY age_group 
    ORDER BY FIELD(age_group, '18-24', '25-34', '35-44', '45-54', '55+')";
    $ageStmt = $db->query($ageQuery);
    $ageDistribution = [];
    while ($row = $ageStmt->fetch(PDO::FETCH_ASSOC)) {
        $ageDistribution[] = [
            'age_group' => $row['age_group'],
            'count' => (int)$row['count']
        ];
    }

    // Get location distribution
    $locationQuery = "SELECT CONCAT(province, ', ', country) as location, COUNT(*) as count FROM analytics_customers GROUP BY location";
    $locationStmt = $db->query($locationQuery);
    $locationDistribution = [];
    while ($row = $locationStmt->fetch(PDO::FETCH_ASSOC)) {
        $locationDistribution[] = [
            'location' => $row['location'],
            'count' => (int)$row['count']
        ];
    }

    // Get trend data
    $trendQuery = "
        SELECT transaction_date as date, COUNT(*) as count
        FROM ai_analytics_spending_habits
        WHERE transaction_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY transaction_date
        ORDER BY date ASC
    ";
    $trendStmt = $db->query($trendQuery);
    $trendData = [];
    while ($row = $trendStmt->fetch(PDO::FETCH_ASSOC)) {
        $trendData[] = [
            'date' => $row['date'],
            'count' => (int)$row['count']
        ];
    }

    // Return all data
    echo json_encode([
        'genderDistribution' => $genderDistribution,
        'ageDistribution' => $ageDistribution,
        'locationDistribution' => $locationDistribution,
        'trendData' => $trendData
    ]);
    
} catch (Exception $e) {
    error_log('Error in analytics.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>
