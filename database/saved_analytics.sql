USE AIDash_DBS;

-- Create saved analytics table
CREATE TABLE IF NOT EXISTS saved_analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    analytic_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    UNIQUE KEY unique_saved_analytic (user_id, analytic_id)
);
