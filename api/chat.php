<?php
require_once 'config.php';

// Configure error logging
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', '/Applications/MAMP/logs/php_error.log');

// Set response headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Debug: Log environment variables (never log actual API keys)
error_log('Chat API request received at: ' . date('Y-m-d H:i:s'));
error_log('API Key set: ' . (!empty(OPENAI_API_KEY) ? 'Yes' : 'No'));
error_log('Assistant ID set: ' . (!empty(OPENAI_ASSISTANT_ID) ? 'Yes' : 'No'));

// Verify JWT token
$headers = getallheaders();
if (!isset($headers['Authorization'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No token provided']);
    exit;
}

$token = str_replace('Bearer ', '', $headers['Authorization']);
error_log('Verifying token...');
$payload = verifyToken($token);

if (!$payload) {
    error_log('Token verification failed');
    http_response_code(401);
    echo json_encode(['error' => 'Invalid token']);
    exit;
}

// Get POST data
$rawInput = file_get_contents('php://input');
error_log('Raw request data: ' . $rawInput);

$data = json_decode($rawInput, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    error_log('JSON decode error: ' . json_last_error_msg());
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON data']);
    exit;
}

if (!isset($data['message'])) {
    error_log('No message in request data');
    http_response_code(400);
    echo json_encode(['error' => 'Message is required']);
    exit;
}

try {
    // Verify required configuration
    if (!defined('OPENAI_API_KEY') || empty(OPENAI_API_KEY)) {
        throw new Exception('OpenAI API key not configured');
    }

    if (!defined('OPENAI_ASSISTANT_ID') || empty(OPENAI_ASSISTANT_ID)) {
        throw new Exception('OpenAI Assistant ID not configured');
    }

    // Connect to database
    error_log('Connecting to database...');
    $db = getDBConnection();

    // Get or create thread for user
    error_log('Looking up thread for user ID: ' . $payload->user_id);
    $stmt = $db->prepare('SELECT thread_id FROM chat_threads WHERE user_id = ? ORDER BY created_at DESC LIMIT 1');
    $stmt->execute([$payload->user_id]);
    $thread = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$thread) {
        error_log('Creating new thread...');
        // Create new thread
        // Debug log for API key
        error_log('Using OpenAI API Key: ' . substr(OPENAI_API_KEY, 0, 5) . '...');

        $ch = curl_init('https://api.openai.com/v1/threads');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . OPENAI_API_KEY,
                'OpenAI-Beta: assistants=v2'
            ]
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            error_log('cURL Error: ' . $curlError);
            throw new Exception('Failed to connect to OpenAI API');
        }

        if ($httpCode !== 200) {
            error_log('Failed to create thread. HTTP Code: ' . $httpCode);
            error_log('Response: ' . $response);
            throw new Exception('Failed to create chat thread');
        }

        $threadData = json_decode($response, true);
        $threadId = $threadData['id'];
        error_log('Created new thread: ' . $threadId);

        // Save thread ID
        $stmt = $db->prepare('INSERT INTO chat_threads (user_id, thread_id) VALUES (?, ?)');
        $stmt->execute([$payload->user_id, $threadId]);
    } else {
        $threadId = $thread['thread_id'];
        error_log('Using existing thread: ' . $threadId);
    }

    // Add message to thread
    error_log('Adding message to thread...');
    $ch = curl_init("https://api.openai.com/v1/threads/{$threadId}/messages");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode(['role' => 'user', 'content' => $data['message']]),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . OPENAI_API_KEY,
            'OpenAI-Beta: assistants=v2'
        ]
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($httpCode !== 200) {
        error_log('Failed to add message. HTTP Code: ' . $httpCode);
        error_log('Response: ' . $response);
        if ($curlError) {
            error_log('Curl error: ' . $curlError);
        }
        throw new Exception('Failed to send message');
    }

    // Create run with the assistant
    error_log('Creating run with assistant...');
    $ch = curl_init("https://api.openai.com/v1/threads/{$threadId}/runs");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode(['assistant_id' => OPENAI_ASSISTANT_ID]),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . OPENAI_API_KEY,
            'OpenAI-Beta: assistants=v2'
        ]
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($httpCode !== 200) {
        error_log('Failed to create run. HTTP Code: ' . $httpCode);
        error_log('Response: ' . $response);
        if ($curlError) {
            error_log('Curl error: ' . $curlError);
        }
        throw new Exception('Failed to process message');
    }

    $runData = json_decode($response, true);
    $runId = $runData['id'];
    error_log('Run created: ' . $runId);

    // Wait for run to complete
    $maxAttempts = 10;
    $attempts = 0;
    $completed = false;

    while (!$completed && $attempts < $maxAttempts) {
        $attempts++;
        error_log("Checking run status (attempt {$attempts})...");

        // Wait before checking status
        sleep(1);

        $ch = curl_init("https://api.openai.com/v1/threads/{$threadId}/runs/{$runId}");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . OPENAI_API_KEY,
                'OpenAI-Beta: assistants=v2'
            ]
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            error_log('Failed to check run status. HTTP Code: ' . $httpCode);
            continue;
        }

        $status = json_decode($response, true);
        if ($status['status'] === 'completed') {
            $completed = true;
            break;
        }

        if (in_array($status['status'], ['failed', 'cancelled', 'expired'])) {
            throw new Exception('Run failed with status: ' . $status['status']);
        }
    }

    if (!$completed) {
        throw new Exception('Run timed out');
    }

    // Get messages (assistant's response)
    error_log('Getting messages...');
    $ch = curl_init("https://api.openai.com/v1/threads/{$threadId}/messages");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . OPENAI_API_KEY,
            'OpenAI-Beta: assistants=v2'
        ]
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        error_log('Failed to get messages. HTTP Code: ' . $httpCode);
        throw new Exception('Failed to get response');
    }

    $messages = json_decode($response, true);
    if (empty($messages['data'])) {
        throw new Exception('No response received');
    }

    $assistantMessage = $messages['data'][0]['content'][0]['text']['value'];
    error_log('Assistant response received');

    // Store conversation in database
    $stmt = $db->prepare('INSERT INTO chat_history (user_id, message, response, thread_id, sentiment_score, sentiment_label, category) VALUES (?, ?, ?, ?, NULL, NULL, NULL)');
    $stmt->execute([
        $payload->user_id,
        $data['message'],
        $assistantMessage,
        $threadId
    ]);

    echo json_encode([
        'response' => $assistantMessage,
        'thread_id' => $threadId
    ]);

} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error occurred']);
} catch (Exception $e) {
    error_log('Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
