USE AIDash_DBS;

-- User sessions table for tracking active sessions
CREATE TABLE IF NOT EXISTS user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(500) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Analytics overview table for dashboard metrics
CREATE TABLE IF NOT EXISTS analytics_overview (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total_chats INT DEFAULT 0,
    total_analysis INT DEFAULT 0,
    avg_sentiment DECIMAL(5,2),
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    metrics JSON,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create indexes for better performance
ALTER TABLE user_sessions ADD INDEX idx_user_sessions_token (token);
ALTER TABLE user_sessions ADD INDEX idx_user_sessions_user (user_id);
ALTER TABLE analytics_overview ADD INDEX idx_analytics_overview_user (user_id);
