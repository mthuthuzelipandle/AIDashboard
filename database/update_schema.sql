-- Add has_visualization column to chat_history
ALTER TABLE chat_history 
ADD COLUMN has_visualization BOOLEAN DEFAULT FALSE;

-- Create table for saved queries
CREATE TABLE IF NOT EXISTS saved_queries (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    query TEXT NOT NULL,
    visualization_type VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Create table for user permissions
CREATE TABLE IF NOT EXISTS user_permissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    permission VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Insert default permissions
INSERT INTO user_permissions (user_id, permission)
SELECT id, 'view_own_data'
FROM users;

-- Add indexes for performance
CREATE INDEX idx_chat_history_user ON chat_history(user_id);
CREATE INDEX idx_saved_queries_user ON saved_queries(user_id);
CREATE INDEX idx_user_permissions_user ON user_permissions(user_id);
