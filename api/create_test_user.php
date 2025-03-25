<?php
require_once 'config.php';

try {
    $db = getDBConnection();
    
    // Check if test user exists
    $email = 'test@example.com';
    $stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        // Create test user
        $password = 'test123';
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $db->prepare('INSERT INTO users (email, password_hash, username) VALUES (?, ?, ?)');
        $stmt->execute([$email, $passwordHash, 'Test User']);
        
        echo "Test user created successfully\n";
        echo "Email: test@example.com\n";
        echo "Password: test123\n";
    } else {
        echo "Test user already exists\n";
        echo "Email: test@example.com\n";
        echo "Password: test123\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
