-- Add sample users with diverse demographics
INSERT INTO users (username, email, password, role, profile) VALUES
('john_doe', 'john@example.com', '$2y$10$lQd5BwgaKkNUcRGZG/wTSeyS/EIks6D2eTlz9GMwFY/BYOFe89kc2', 'user', 
    '{"gender": "Male", "location": "New York", "maritalStatus": "Single", "age": 28, "race": "Caucasian", "occupation": "Software Engineer"}'),
('jane_smith', 'jane@example.com', '$2y$10$lQd5BwgaKkNUcRGZG/wTSeyS/EIks6D2eTlz9GMwFY/BYOFe89kc2', 'user',
    '{"gender": "Female", "location": "San Francisco", "maritalStatus": "Married", "age": 34, "race": "Asian", "occupation": "Data Scientist"}'),
('mike_johnson', 'mike@example.com', '$2y$10$lQd5BwgaKkNUcRGZG/wTSeyS/EIks6D2eTlz9GMwFY/BYOFe89kc2', 'user',
    '{"gender": "Male", "location": "Chicago", "maritalStatus": "Divorced", "age": 45, "race": "African American", "occupation": "Manager"}'),
('sarah_williams', 'sarah@example.com', '$2y$10$lQd5BwgaKkNUcRGZG/wTSeyS/EIks6D2eTlz9GMwFY/BYOFe89kc2', 'user',
    '{"gender": "Female", "location": "Boston", "maritalStatus": "Single", "age": 31, "race": "Hispanic", "occupation": "Marketing"}'),
('alex_chen', 'alex@example.com', '$2y$10$lQd5BwgaKkNUcRGZG/wTSeyS/EIks6D2eTlz9GMwFY/BYOFe89kc2', 'user',
    '{"gender": "Non-Binary", "location": "Seattle", "maritalStatus": "Single", "age": 29, "race": "Asian", "occupation": "Designer"}');

-- Add sample analysis data with different sentiments and scores
INSERT INTO analysis_data (user_id, data_type, sentiment, score, content, created_at) VALUES
(1, 'customer_feedback', 'positive', 0.92, 'Excellent service, very satisfied with the product!', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(2, 'customer_feedback', 'negative', 0.15, 'Product quality needs improvement', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(3, 'customer_feedback', 'neutral', 0.50, 'Average experience, nothing special', DATE_SUB(NOW(), INTERVAL 3 DAY)),
(4, 'social_media', 'positive', 0.88, 'Love the new features!', DATE_SUB(NOW(), INTERVAL 4 DAY)),
(5, 'social_media', 'positive', 0.95, 'Best customer support ever!', DATE_SUB(NOW(), INTERVAL 5 DAY)),
(1, 'product_review', 'negative', 0.20, 'The interface is confusing', DATE_SUB(NOW(), INTERVAL 6 DAY)),
(2, 'product_review', 'positive', 0.85, 'Very intuitive and user-friendly', DATE_SUB(NOW(), INTERVAL 7 DAY));

-- Add sample chat history
INSERT INTO chat_history (user_id, message, response, created_at) VALUES
(1, 'Can you analyze the customer satisfaction trend?', 'Based on the recent data, customer satisfaction shows an upward trend with 65% positive feedback.', DATE_SUB(NOW(), INTERVAL 1 HOUR)),
(2, 'What are the main customer complaints?', 'The primary concerns are related to interface complexity and product quality, accounting for 80% of negative feedback.', DATE_SUB(NOW(), INTERVAL 2 HOUR)),
(3, 'Show me the demographics of satisfied customers', 'Analysis shows that customers aged 25-34 from urban areas report the highest satisfaction rates.', DATE_SUB(NOW(), INTERVAL 3 HOUR)),
(4, 'Generate a report on user engagement', 'User engagement has increased by 25% in the last month, with peak activity during weekday afternoons.', DATE_SUB(NOW(), INTERVAL 4 HOUR));
