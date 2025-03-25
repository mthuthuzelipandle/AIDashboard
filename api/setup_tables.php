<?php
require_once 'config.php';

try {
    $db = getDBConnection();
    
    // Create users table if it doesn't exist
    $db->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT PRIMARY KEY AUTO_INCREMENT,
            username VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Create chat_threads table if it doesn't exist
    $db->exec("
        CREATE TABLE IF NOT EXISTS chat_threads (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            thread_id VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    
    // Create chat_history table if it doesn't exist
    $db->exec("
        CREATE TABLE IF NOT EXISTS chat_history (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            message TEXT NOT NULL,
            response TEXT NOT NULL,
            thread_id VARCHAR(255),
            sentiment_score FLOAT,
            sentiment_label VARCHAR(20),
            category VARCHAR(50),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    
    // Create ai_analytics_queries table if it doesn't exist
    $db->exec("
        CREATE TABLE IF NOT EXISTS ai_analytics_queries (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            query_type VARCHAR(50) NOT NULL,
            query_text TEXT NOT NULL,
            result TEXT,
            sentiment_score FLOAT,
            sentiment_label VARCHAR(20),
            category VARCHAR(50),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    
    // Create analytics_data table if it doesn't exist
    $db->exec("
        CREATE TABLE IF NOT EXISTS analytics_data (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            data_type VARCHAR(50) NOT NULL,
            data_value TEXT NOT NULL,
            sentiment_score FLOAT,
            sentiment_label VARCHAR(20),
            category VARCHAR(50),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    
    echo "Database tables updated successfully\n";
} catch (PDOException $e) {
    die("Database setup failed: " . $e->getMessage() . "\n");
}
