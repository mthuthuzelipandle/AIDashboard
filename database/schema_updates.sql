-- Add user roles table
CREATE TABLE IF NOT EXISTS user_roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Add default roles
INSERT INTO user_roles (name) VALUES 
('admin'),
('analyst'),
('user');

-- Add role column to users table
ALTER TABLE users 
ADD COLUMN role_id INT,
ADD FOREIGN KEY (role_id) REFERENCES user_roles(id);

-- Add permissions table
CREATE TABLE IF NOT EXISTS permissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Add role permissions table
CREATE TABLE IF NOT EXISTS role_permissions (
    role_id INT,
    permission_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES user_roles(id),
    FOREIGN KEY (permission_id) REFERENCES permissions(id)
);

-- Add default permissions
INSERT INTO permissions (name, description) VALUES 
('view_all_analytics', 'Can view all analytics data'),
('view_own_analytics', 'Can view own analytics data'),
('generate_infographics', 'Can generate custom infographics'),
('manage_users', 'Can manage user accounts');

-- Add saved visualizations table
CREATE TABLE IF NOT EXISTS saved_visualizations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    type VARCHAR(50) NOT NULL,
    config JSON NOT NULL,
    is_public BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Add AI chat analytics table
CREATE TABLE IF NOT EXISTS ai_analytics_queries (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    query TEXT NOT NULL,
    generated_sql TEXT,
    visualization_id INT,
    status VARCHAR(20) NOT NULL,
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (visualization_id) REFERENCES saved_visualizations(id)
);

-- Add user data access log
CREATE TABLE IF NOT EXISTS data_access_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    action VARCHAR(50) NOT NULL,
    resource_type VARCHAR(50) NOT NULL,
    resource_id VARCHAR(100),
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Update existing tables with audit fields
ALTER TABLE analysis_data 
ADD COLUMN created_by INT,
ADD FOREIGN KEY (created_by) REFERENCES users(id);

-- Add indexes for performance
CREATE INDEX idx_user_role ON users(role_id);
CREATE INDEX idx_visualization_user ON saved_visualizations(user_id);
CREATE INDEX idx_analytics_user ON ai_analytics_queries(user_id);
CREATE INDEX idx_access_log_user ON data_access_log(user_id);
