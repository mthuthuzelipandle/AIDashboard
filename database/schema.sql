-- Create database
CREATE DATABASE IF NOT EXISTS AIDash_DBS;
USE AIDash_DBS;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    profile JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Analysis data table
CREATE TABLE IF NOT EXISTS analysis_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    data_type VARCHAR(50),
    sentiment ENUM('positive', 'neutral', 'negative'),
    score DECIMAL(5,2),
    content TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Chat history table
CREATE TABLE IF NOT EXISTS chat_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    message TEXT,
    response TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Insert sample admin user (password: admin123)
INSERT INTO users (username, email, password, role, profile) VALUES (
    'admin',
    'admin@example.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'admin',
    '{
        "gender": "Not specified",
        "location": "San Francisco",
        "maritalStatus": "Not specified",
        "age": 30,
        "race": "Not specified",
        "occupation": "Administrator"
    }'
);
