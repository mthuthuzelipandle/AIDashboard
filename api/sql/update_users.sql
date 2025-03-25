-- Update existing users to use email as login
UPDATE users SET email = 'admin@example.com' WHERE username = 'admin';
UPDATE users SET email = 'user@example.com' WHERE username = 'user';

-- Make email field required and unique
ALTER TABLE users MODIFY email VARCHAR(255) NOT NULL UNIQUE;
