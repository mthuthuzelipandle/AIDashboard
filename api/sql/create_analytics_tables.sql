USE AIDash_DBS;

-- Customers table
CREATE TABLE IF NOT EXISTS customers (
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

-- Spending habits table
CREATE TABLE IF NOT EXISTS spending_habits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT,
    category ENUM('Food', 'Shopping', 'Entertainment', 'Transportation', 'Healthcare', 'Education', 'Utilities', 'Other'),
    amount DECIMAL(10,2),
    transaction_date DATE,
    frequency ENUM('Daily', 'Weekly', 'Monthly', 'Quarterly', 'Yearly'),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id)
);

-- Insert sample customer data
INSERT INTO customers (name, surname, age, gender, country, province, city, profession, annual_income) VALUES
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
('Nomvula', 'Zulu', 38, 'Female', 'South Africa', 'KwaZulu-Natal', 'Pietermaritzburg', 'Lawyer', 95000.00),
('Sipho', 'Mthembu', 34, 'Male', 'South Africa', 'KwaZulu-Natal', 'Durban', 'Teacher', 48000.00),

-- Eastern Cape
('David', 'Brown', 39, 'Male', 'South Africa', 'Eastern Cape', 'Port Elizabeth', 'Business Analyst', 70000.00),
('Linda', 'Naidoo', 32, 'Female', 'South Africa', 'Eastern Cape', 'East London', 'Software Engineer', 72000.00),

-- Free State
('Pieter', 'Venter', 41, 'Male', 'South Africa', 'Free State', 'Bloemfontein', 'Doctor', 115000.00),
('Maria', 'du Plessis', 36, 'Female', 'South Africa', 'Free State', 'Bloemfontein', 'Teacher', 47000.00),

-- Additional demographics
('Zandile', 'Dlamini', 24, 'Female', 'South Africa', 'Gauteng', 'Johannesburg', 'Data Scientist', 60000.00),
('Hendrik', 'Botha', 52, 'Male', 'South Africa', 'Western Cape', 'Cape Town', 'Business Analyst', 98000.00),
('Fatima', 'Patel', 29, 'Female', 'South Africa', 'KwaZulu-Natal', 'Durban', 'Marketing Manager', 63000.00),
('William', 'Smith', 45, 'Male', 'South Africa', 'Gauteng', 'Pretoria', 'Lawyer', 105000.00),
('Busisiwe', 'Mahlangu', 31, 'Female', 'South Africa', 'Mpumalanga', 'Nelspruit', 'Teacher', 46000.00);

-- Insert sample spending habits data for the last 6 months
INSERT INTO spending_habits (customer_id, category, amount, transaction_date, frequency) VALUES
-- Recent month (March 2025)
(1, 'Food', 2500.00, '2025-03-01', 'Monthly'),
(1, 'Shopping', 3000.00, '2025-03-05', 'Monthly'),
(1, 'Entertainment', 1500.00, '2025-03-10', 'Monthly'),
(2, 'Food', 2000.00, '2025-03-02', 'Monthly'),
(2, 'Healthcare', 4000.00, '2025-03-07', 'Quarterly'),
(3, 'Education', 5000.00, '2025-03-03', 'Monthly'),
(3, 'Transportation', 1800.00, '2025-03-08', 'Monthly'),

-- February 2025
(4, 'Utilities', 1500.00, '2025-02-04', 'Monthly'),
(4, 'Shopping', 2800.00, '2025-02-09', 'Monthly'),
(5, 'Food', 2200.00, '2025-02-05', 'Monthly'),
(5, 'Entertainment', 1200.00, '2025-02-10', 'Monthly'),
(6, 'Transportation', 1600.00, '2025-02-15', 'Monthly'),

-- January 2025
(7, 'Food', 2300.00, '2025-01-05', 'Monthly'),
(8, 'Shopping', 3500.00, '2025-01-10', 'Monthly'),
(9, 'Entertainment', 1800.00, '2025-01-15', 'Monthly'),
(10, 'Healthcare', 5000.00, '2025-01-20', 'Quarterly'),

-- December 2024
(11, 'Food', 3000.00, '2024-12-05', 'Monthly'),
(12, 'Shopping', 4500.00, '2024-12-10', 'Monthly'),
(13, 'Entertainment', 2000.00, '2024-12-15', 'Monthly'),
(14, 'Education', 6000.00, '2024-12-20', 'Monthly'),

-- November 2024
(15, 'Food', 2400.00, '2024-11-05', 'Monthly'),
(16, 'Transportation', 1700.00, '2024-11-10', 'Monthly'),
(17, 'Utilities', 1600.00, '2024-11-15', 'Monthly'),
(18, 'Healthcare', 4500.00, '2024-11-20', 'Quarterly'),

-- October 2024
(19, 'Food', 2200.00, '2024-10-05', 'Monthly'),
(20, 'Shopping', 3200.00, '2024-10-10', 'Monthly'),
(1, 'Entertainment', 1400.00, '2024-10-15', 'Monthly'),
(2, 'Education', 5500.00, '2024-10-20', 'Monthly');

-- Add more transactions for spending categories analysis
INSERT INTO spending_habits (customer_id, category, amount, transaction_date, frequency) VALUES
(3, 'Food', 2800.00, '2025-03-15', 'Monthly'),
(4, 'Shopping', 3800.00, '2025-03-15', 'Monthly'),
(5, 'Entertainment', 1600.00, '2025-03-15', 'Monthly'),
(6, 'Transportation', 2000.00, '2025-03-15', 'Monthly'),
(7, 'Healthcare', 4800.00, '2025-03-15', 'Quarterly'),
(8, 'Education', 5800.00, '2025-03-15', 'Monthly'),
(9, 'Utilities', 1900.00, '2025-03-15', 'Monthly'),
(10, 'Other', 1200.00, '2025-03-15', 'Monthly');
