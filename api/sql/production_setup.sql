USE AIDash_DBS;

-- Create tables
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(100),
    role ENUM('admin', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default admin user (password: admin123)
INSERT INTO users (email, password, name, role) VALUES
('admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin User', 'admin');

-- Create tables
CREATE TABLE IF NOT EXISTS analytics_customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50),
    surname VARCHAR(50),
    age INT,
    gender ENUM('Male', 'Female', 'Other'),
    country VARCHAR(50),
    province VARCHAR(50),
    city VARCHAR(50),
    profession VARCHAR(100),
    annual_income DECIMAL(12,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS analytics_spending_habits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT,
    category ENUM('Food', 'Shopping', 'Entertainment', 'Transportation', 'Healthcare', 'Education', 'Utilities', 'Other'),
    amount DECIMAL(10,2),
    transaction_date DATE,
    frequency ENUM('Daily', 'Weekly', 'Monthly', 'Quarterly', 'Yearly'),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES analytics_customers(id)
);

-- Insert sample data
INSERT INTO analytics_customers (name, surname, age, gender, country, province, city, profession, annual_income) VALUES
-- Gauteng
('John', 'Doe', 35, 'Male', 'South Africa', 'Gauteng', 'Johannesburg', 'Software Engineer', 85000.00),
('Sarah', 'Williams', 31, 'Female', 'South Africa', 'Gauteng', 'Pretoria', 'Teacher', 45000.00),
('Michael', 'van der Merwe', 45, 'Male', 'South Africa', 'Gauteng', 'Johannesburg', 'Business Analyst', 92000.00),
('Thabo', 'Molefe', 29, 'Male', 'South Africa', 'Gauteng', 'Pretoria', 'Software Engineer', 78000.00),
('Lerato', 'Ndlovu', 33, 'Female', 'South Africa', 'Gauteng', 'Johannesburg', 'Marketing Manager', 68000.00),

-- Western Cape
('Jane', 'Smith', 28, 'Female', 'South Africa', 'Western Cape', 'Cape Town', 'Marketing Manager', 65000.00),
('James', 'Wilson', 37, 'Male', 'South Africa', 'Western Cape', 'Cape Town', 'Doctor', 130000.00),
('Emma', 'Brown', 26, 'Female', 'South Africa', 'Western Cape', 'Stellenbosch', 'Data Scientist', 82000.00),

-- KwaZulu-Natal
('Mike', 'Johnson', 42, 'Male', 'South Africa', 'KwaZulu-Natal', 'Durban', 'Doctor', 120000.00),
('Nomvula', 'Zulu', 38, 'Female', 'South Africa', 'KwaZulu-Natal', 'Pietermaritzburg', 'Lawyer', 95000.00);

-- Insert sample spending data
INSERT INTO analytics_spending_habits (customer_id, category, amount, transaction_date, frequency) VALUES
(1, 'Food', 2500.00, '2025-03-01', 'Monthly'),
(1, 'Shopping', 3000.00, '2025-03-05', 'Monthly'),
(2, 'Entertainment', 1500.00, '2025-03-10', 'Monthly'),
(3, 'Healthcare', 4000.00, '2025-03-07', 'Quarterly'),
(4, 'Education', 5000.00, '2025-03-03', 'Monthly'),
(5, 'Transportation', 1800.00, '2025-03-08', 'Monthly'),
(6, 'Food', 2200.00, '2025-03-15', 'Monthly'),
(7, 'Shopping', 3800.00, '2025-03-15', 'Monthly'),
(8, 'Entertainment', 1600.00, '2025-03-15', 'Monthly'),
(9, 'Healthcare', 4800.00, '2025-03-15', 'Quarterly'),
(10, 'Education', 5800.00, '2025-03-15', 'Monthly');
