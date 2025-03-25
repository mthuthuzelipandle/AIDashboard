-- Add threads table
CREATE TABLE IF NOT EXISTS chat_threads (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    thread_id VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Add thread_id to chat_history
ALTER TABLE chat_history
ADD COLUMN thread_id VARCHAR(255) DEFAULT NULL;
