<?php
/**
 * Main Configuration File
 * Edit these settings when deploying to a new domain
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'AIDash_DBS');
define('DB_USER', 'root');
define('DB_PASS', 'root');

// OpenAI configuration
define('OPENAI_API_KEY', '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');
define('OPENAI_ASSISTANT_ID', 'asst_lbtHKiKNyl1SsIWCzmzvrRTr');

// OpenAI configuration function
function loadOpenAIConfig() {
    return [
        'api_key' => OPENAI_API_KEY,
        'assistant_id' => OPENAI_ASSISTANT_ID
    ];
}

// JWT configuration
define('JWT_SECRET', 'dK8Lp#mN9$qR2@vX5*zA7&jW4'); // Change this in production

// Development error handling
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '/var/log/php_error.log');

// Database connection with charset and options
function getDBConnection() {
    try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        $conn = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $conn;
    } catch(PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        throw $e; // Let the caller handle the error
    }
}

// JWT functions
function generateToken($userId) {
    $issuedAt = time();
    $expiresAt = $issuedAt + (60 * 60 * 24); // 24 hours expiration
    
    $payload = [
        'iat' => $issuedAt,           // Issued at time
        'nbf' => $issuedAt,           // Not valid before
        'exp' => $expiresAt,          // Expiration time
        'user_id' => $userId
    ];
    
    error_log('Generating token for user: ' . $userId);
    error_log('Token details:');
    error_log('- Issued at: ' . date('Y-m-d H:i:s', $issuedAt));
    error_log('- Expires at: ' . date('Y-m-d H:i:s', $expiresAt));
    
    $token = jwt_encode($payload, JWT_SECRET);
    error_log('Token generated successfully');
    
    return $token;
}

function verifyToken($token) {
    try {
        error_log('Verifying token: ' . substr($token, 0, 20) . '...');
        $payload = jwt_decode($token, JWT_SECRET);
        
        // Validate payload structure
        if (!isset($payload->exp) || !isset($payload->user_id) || !isset($payload->iat)) {
            error_log('Token missing required fields');
            return false;
        }
        
        // Check token expiration
        $currentTime = time();
        if ($payload->exp < $currentTime) {
            error_log('Token expired. Expiry: ' . date('Y-m-d H:i:s', $payload->exp) . 
                      ', Current: ' . date('Y-m-d H:i:s', $currentTime));
            return false;
        }
        
        // Check if token is not used before its issuance (if nbf is set)
        if (isset($payload->nbf) && $payload->nbf > $currentTime) {
            error_log('Token not yet valid');
            return false;
        }
        
        error_log('Token verified successfully for user: ' . $payload->user_id . 
                  ' (issued: ' . date('Y-m-d H:i:s', $payload->iat) . ')');
        return $payload;
    } catch(Exception $e) {
        error_log('Token verification failed: ' . $e->getMessage());
        return false;
    }
}

// JWT implementation
function jwt_encode($payload, $secret) {
    try {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        if (!$header) {
            throw new Exception('Failed to encode header');
        }
        
        $encodedPayload = json_encode($payload);
        if (!$encodedPayload) {
            throw new Exception('Failed to encode payload');
        }
        
        // Base64Url encode header and payload
        $base64UrlHeader = rtrim(strtr(base64_encode($header), '+/', '-_'), '=');
        $base64UrlPayload = rtrim(strtr(base64_encode($encodedPayload), '+/', '-_'), '=');
        
        // Create signature
        $signatureInput = $base64UrlHeader . "." . $base64UrlPayload;
        $signature = hash_hmac('sha256', $signatureInput, $secret, true);
        $base64UrlSignature = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');
        
        $token = $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
        error_log('Generated JWT token with payload: ' . substr($encodedPayload, 0, 100));
        return $token;
    } catch (Exception $e) {
        error_log('JWT encode error: ' . $e->getMessage());
        throw $e;
    }
}

function jwt_decode($token, $secret) {
    try {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new Exception('Invalid token format');
        }
        
        // Add padding to base64 strings
        $header = base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[0]) . str_repeat('=', 4 - (strlen($parts[0]) % 4)));
        $payload = base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1]) . str_repeat('=', 4 - (strlen($parts[1]) % 4)));
        $signature = base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[2]) . str_repeat('=', 4 - (strlen($parts[2]) % 4)));
        
        // Verify signature
        $valid = hash_hmac('sha256', 
            $parts[0] . "." . $parts[1], 
            $secret, 
            true
        );
        
        if (!hash_equals($valid, $signature)) {
            throw new Exception('Invalid signature');
        }
        
        $decodedPayload = json_decode($payload);
        if ($decodedPayload === null) {
            throw new Exception('Invalid payload JSON');
        }
        
        return $decodedPayload;
    } catch (Exception $e) {
        error_log('JWT decode error: ' . $e->getMessage());
        return false;
    }
}
?>
