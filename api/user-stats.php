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
    
    // Get gender distribution
    $stmt = $db->query('SELECT profile->>"$.gender" as gender, COUNT(*) as count FROM users GROUP BY profile->>"$.gender"');
    $genderData = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $genderData[$row['gender']] = (int)$row['count'];
    }
    
    // Get age distribution
    $stmt = $db->query('
        SELECT 
            CASE 
                WHEN profile->>"$.age" < 25 THEN "18-24"
                WHEN profile->>"$.age" BETWEEN 25 AND 34 THEN "25-34"
                WHEN profile->>"$.age" BETWEEN 35 AND 44 THEN "35-44"
                WHEN profile->>"$.age" BETWEEN 45 AND 54 THEN "45-54"
                ELSE "55+"
            END as age_group,
            COUNT(*) as count
        FROM users
        GROUP BY age_group
        ORDER BY age_group
    ');
    $ageData = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $ageData[$row['age_group']] = (int)$row['count'];
    }
    
    // Get location distribution
    $stmt = $db->query('SELECT profile->>"$.location" as location, COUNT(*) as count FROM users GROUP BY profile->>"$.location"');
    $locationData = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $locationData[$row['location']] = (int)$row['count'];
    }
    
    echo json_encode([
        'gender' => $genderData,
        'age' => $ageData,
        'location' => $locationData
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
?>
