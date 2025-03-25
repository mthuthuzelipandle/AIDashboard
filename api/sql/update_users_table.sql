-- First drop tables with foreign key constraints
SET FOREIGN_KEY_CHECKS = 0;

-- Drop dependent tables first
DROP TABLE IF EXISTS chat_history;
DROP TABLE IF EXISTS chat_threads;
DROP TABLE IF EXISTS user_sessions;
DROP TABLE IF EXISTS ai_analytics_queries;
DROP TABLE IF EXISTS analytics_data;

-- Drop and recreate users table
DROP TABLE IF EXISTS users;

-- Create users table with proper fields
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE
);

-- Recreate chat_threads table
CREATE TABLE chat_threads (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    thread_id VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Recreate chat_history table
CREATE TABLE chat_history (
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
);

-- Recreate ai_analytics_queries table
CREATE TABLE ai_analytics_queries (
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
);

-- Recreate analytics_data table
CREATE TABLE analytics_data (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    data_type VARCHAR(50) NOT NULL,
    data_value TEXT NOT NULL,
    sentiment_score FLOAT,
    sentiment_label VARCHAR(20),
    category VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;
