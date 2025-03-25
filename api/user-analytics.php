<?php
require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Verify JWT token
$headers = getallheaders();
if (!isset($headers['Authorization'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No token provided']);
    exit;
}

$token = str_replace('Bearer ', '', $headers['Authorization']);
$payload = verifyToken($token);

if (!$payload) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid token']);
    exit;
}

try {
    $db = getDBConnection();
    
    // Age Distribution
    $ageQuery = "SELECT 
        CASE 
            WHEN age < 25 THEN '18-24'
            WHEN age BETWEEN 25 AND 34 THEN '25-34'
            WHEN age BETWEEN 35 AND 44 THEN '35-44'
            WHEN age BETWEEN 45 AND 54 THEN '45-54'
            ELSE '55+'
        END as age_group,
        COUNT(*) as count
        FROM customers
        GROUP BY 
        CASE 
            WHEN age < 25 THEN '18-24'
            WHEN age BETWEEN 25 AND 34 THEN '25-34'
            WHEN age BETWEEN 35 AND 44 THEN '35-44'
            WHEN age BETWEEN 45 AND 54 THEN '45-54'
            ELSE '55+'
        END
        ORDER BY age_group";
    
    // Gender Distribution
    $genderQuery = "SELECT gender, COUNT(*) as count FROM customers GROUP BY gender";
    
    // Income Analysis
    $incomeQuery = "SELECT 
        CASE 
            WHEN annual_income < 30000 THEN 'Under R30K'
            WHEN annual_income BETWEEN 30000 AND 50000 THEN 'R30K-R50K'
            WHEN annual_income BETWEEN 50001 AND 80000 THEN 'R50K-R80K'
            WHEN annual_income BETWEEN 80001 AND 100000 THEN 'R80K-R100K'
            ELSE 'Over R100K'
        END as income_range,
        COUNT(*) as count
        FROM customers
        GROUP BY 
        CASE 
            WHEN annual_income < 30000 THEN 'Under R30K'
            WHEN annual_income BETWEEN 30000 AND 50000 THEN 'R30K-R50K'
            WHEN annual_income BETWEEN 50001 AND 80000 THEN 'R50K-R80K'
            WHEN annual_income BETWEEN 80001 AND 100000 THEN 'R80K-R100K'
            ELSE 'Over R100K'
        END
        ORDER BY income_range";
    
    // Location Analysis
    $locationQuery = "SELECT province, COUNT(*) as count FROM customers GROUP BY province ORDER BY count DESC";
    
    // Profession Analysis
    $professionQuery = "SELECT profession, COUNT(*) as count FROM customers GROUP BY profession ORDER BY count DESC";
    
    // Spending Category Analysis
    $spendingQuery = "SELECT 
        category,
        COUNT(*) as transaction_count,
        SUM(amount) as total_amount,
        AVG(amount) as avg_amount
        FROM spending_habits
        GROUP BY category
        ORDER BY total_amount DESC";
    
    // Monthly Spending Pattern
    $monthlySpendingQuery = "SELECT 
        DATE_FORMAT(transaction_date, '%Y-%m') as month,
        SUM(amount) as total_amount
        FROM spending_habits
        GROUP BY DATE_FORMAT(transaction_date, '%Y-%m')
        ORDER BY month";
    
    // Income vs Spending Correlation
    $correlationQuery = "SELECT 
        c.annual_income,
        SUM(sh.amount) as total_spending
        FROM customers c
        LEFT JOIN spending_habits sh ON c.id = sh.customer_id
        GROUP BY c.id, c.annual_income
        ORDER BY c.annual_income";

    $results = [
        'ageDistribution' => $db->query($ageQuery)->fetchAll(PDO::FETCH_ASSOC),
        'genderDistribution' => $db->query($genderQuery)->fetchAll(PDO::FETCH_ASSOC),
        'incomeAnalysis' => $db->query($incomeQuery)->fetchAll(PDO::FETCH_ASSOC),
        'locationAnalysis' => $db->query($locationQuery)->fetchAll(PDO::FETCH_ASSOC),
        'professionAnalysis' => $db->query($professionQuery)->fetchAll(PDO::FETCH_ASSOC),
        'spendingCategories' => $db->query($spendingQuery)->fetchAll(PDO::FETCH_ASSOC),
        'monthlySpending' => $db->query($monthlySpendingQuery)->fetchAll(PDO::FETCH_ASSOC),
        'incomeVsSpending' => $db->query($correlationQuery)->fetchAll(PDO::FETCH_ASSOC)
    ];
    
    echo json_encode([
        'status' => 'success',
        'data' => $results
    ]);

} catch (Exception $e) {
    error_log('Error in user analytics: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch analytics data']);
}
?>
