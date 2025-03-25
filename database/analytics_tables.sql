USE AIDash_DBS;

-- Drop existing tables if they exist
DROP TABLE IF EXISTS sentiment_analysis;
DROP TABLE IF EXISTS user_analytics;
DROP TABLE IF EXISTS analytics_overview;

-- Analytics Overview table
CREATE TABLE analytics_overview (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    type VARCHAR(20) DEFAULT 'overview',
    description VARCHAR(255) DEFAULT 'Analytics Overview',
    sentiment_score DECIMAL(5,2),
    active_users INT DEFAULT 0,
    growth_rate DECIMAL(5,2) DEFAULT 0,
    avg_session INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- User Analytics table
CREATE TABLE user_analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    type VARCHAR(20) DEFAULT 'user',
    description VARCHAR(255) DEFAULT 'User Demographics Analysis',
    age_group VARCHAR(20),
    gender VARCHAR(20),
    location VARCHAR(100),
    device_type VARCHAR(50),
    browser VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Sentiment Analysis table
CREATE TABLE sentiment_analysis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    type VARCHAR(20) DEFAULT 'sentiment',
    description VARCHAR(255) DEFAULT 'AI Sentiment Analysis',
    content TEXT,
    sentiment_score DECIMAL(5,2),
    confidence DECIMAL(5,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Insert sample data for testing
INSERT INTO analytics_overview (
    user_id,
    sentiment_score,
    active_users,
    growth_rate,
    avg_session
)
SELECT 
    id,
    0.75,  -- Positive sentiment
    42,    -- Active users
    15.5,  -- Growth rate
    25     -- Average session length in minutes
FROM users
WHERE username = 'admin';

INSERT INTO user_analytics (
    user_id,
    age_group,
    gender,
    location,
    device_type,
    browser
)
SELECT 
    id,
    '25-34',
    'Not specified',
    'San Francisco',
    'Desktop',
    'Chrome'
FROM users
WHERE username = 'admin';

INSERT INTO sentiment_analysis (
    user_id,
    content,
    sentiment_score,
    confidence
)
SELECT 
    id,
    'Sample analysis of user interaction patterns',
    0.75,
    0.85
FROM users
WHERE username = 'admin';
