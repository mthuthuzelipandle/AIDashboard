<?php
require_once 'config.php';
require_once 'services/ExportService.php';

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

try {
    // Get request data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['type']) || !isset($data['format'])) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Missing required parameters'
        ]);
        exit;
    }
    
    $exportService = new ExportService($payload->user_id);
    $result = $exportService->exportAnalytics(
        $data['type'],
        $data['format'],
        $data['dateRange'] ?? null,
        $data['chartOptions'] ?? null
    );
    
    // Create a temporary file to store the export
    $tempDir = '/Applications/MAMP/tmp/exports';
    if (!file_exists($tempDir)) {
        mkdir($tempDir, 0755, true);
    }
    
    $filename = 'analytics_export_' . time() . '_' . uniqid() . '.' . $data['format'];
    $tempFile = $tempDir . '/' . $filename;
    file_put_contents($tempFile, $result);
    
    // Return success with file URL
    echo json_encode([
        'status' => 'success',
        'message' => 'Export generated successfully',
        'fileUrl' => '/ai_analytics_dashboard/tmp/exports/' . $filename
    ]);

} catch (Exception $e) {
    error_log('Error in export-analytics.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to generate export'
    ]);
}
