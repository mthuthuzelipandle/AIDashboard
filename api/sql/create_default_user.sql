-- Check if admin user exists
INSERT INTO users (username, password_hash, role_id)
SELECT 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1
WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'admin');

-- Check if default user exists
INSERT INTO users (username, password_hash, role_id)
SELECT 'user', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2
WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'user');
