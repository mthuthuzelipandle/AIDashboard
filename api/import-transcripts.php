<?php
require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
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
    
    // Create transcripts table if it doesn't exist
    $createTableSQL = "CREATE TABLE IF NOT EXISTS transcripts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        Discussion_Content_English TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    $db->exec($createTableSQL);
    
    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['content']) || empty($data['content'])) {
        throw new Exception('No content provided');
    }
    
    // Insert the content
    $stmt = $db->prepare('INSERT INTO transcripts (Discussion_Content_English) VALUES (?)');
    $stmt->execute([$data['content']]);
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Content imported successfully'
    ]);

} catch (Exception $e) {
    error_log('Error in import transcripts: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to import content']);
}
?>
