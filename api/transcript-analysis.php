<?php
require_once 'config.php';
require_once 'services/AIAnalyticsService.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
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
    
    // Check if the discussion content table exists
    $tables = $db->query("SHOW TABLES LIKE 'discussion_content'");
    if ($tables->rowCount() === 0) {
        $tables = $db->query("SHOW TABLES LIKE 'discussioncontent'");
    }
    
    if ($tables->rowCount() === 0) {
        throw new Exception('Discussion content table not found');
    }
    
    // Get the actual table name
    $tableName = $tables->fetch(PDO::FETCH_COLUMN);


    
    // Get topics and sentiments from Discussion_Content_English
    $innerQuery = "SELECT 
                CASE 
                    WHEN LOWER(Discussion_Content_English) LIKE '%education%' THEN 'Education'
                    WHEN LOWER(Discussion_Content_English) LIKE '%health%' THEN 'Healthcare'
                    WHEN LOWER(Discussion_Content_English) LIKE '%economy%' THEN 'Economy'
                    WHEN LOWER(Discussion_Content_English) LIKE '%infrastructure%' THEN 'Infrastructure'
                    WHEN LOWER(Discussion_Content_English) LIKE '%security%' THEN 'Security'
                    ELSE 'Other'
                END AS topic,
                IF(Discussion_Content_English REGEXP 'good|great|excellent|positive|success|improve', 1, 0) as positive,
                IF(Discussion_Content_English REGEXP 'bad|poor|negative|failure|worsen|problem', 1, 0) as negative,
                IF(Discussion_Content_English NOT REGEXP '(good|great|excellent|positive|success|improve|bad|poor|negative|failure|worsen|problem)', 1, 0) as neutral,
                SUBSTRING(Discussion_Content_English, 1, 200) as content
            FROM " . $tableName;
            
    $query = "SELECT 
            topic,
            COUNT(*) as topic_count,
            SUM(positive) as positive_count,
            SUM(negative) as negative_count,
            SUM(neutral) as neutral_count,
            GROUP_CONCAT(content SEPARATOR '|||') as sample_discussions
        FROM (" . $innerQuery . ") analysis 
        GROUP BY topic 
        ORDER BY topic_count DESC";


    $stmt = $db->query($query);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Process results for visualization
    $topics = [];
    $topicCounts = [];
    $sentiments = [];
    $discussions = [];

    foreach ($results as $row) {
        $topics[] = $row['topic'];
        $topicCounts[] = $row['topic_count'];
        $sentiments[] = [
            'positive' => (int)$row['positive_count'],
            'negative' => (int)$row['negative_count'],
            'neutral' => (int)$row['neutral_count']
        ];
        $discussions[$row['topic']] = array_slice(explode('|||', $row['sample_discussions']), 0, 3);
    }

    $response = [
        'status' => 'success',
        'data' => [
            'topics' => $topics,
            'topicCounts' => $topicCounts,
            'sentiments' => $sentiments,
            'discussions' => $discussions,
            'chartData' => [
                'labels' => $topics,
                'datasets' => [
                    [
                        'label' => 'Topic Distribution',
                        'data' => $topicCounts,
                        'backgroundColor' => [
                            '#FF6384',
                            '#36A2EB',
                            '#FFCE56',
                            '#4BC0C0',
                            '#9966FF',
                            '#FF9F40'
                        ]
                    ]
                ]
            ],
            'sentimentChartData' => [
                'labels' => $topics,
                'datasets' => [
                    [
                        'label' => 'Positive',
                        'data' => array_column($sentiments, 'positive'),
                        'backgroundColor' => '#36A2EB'
                    ],
                    [
                        'label' => 'Negative',
                        'data' => array_column($sentiments, 'negative'),
                        'backgroundColor' => '#FF6384'
                    ],
                    [
                        'label' => 'Neutral',
                        'data' => array_column($sentiments, 'neutral'),
                        'backgroundColor' => '#FFCE56'
                    ]
                ]
            ]
        ]
    ];

    echo json_encode($response);

} catch (Exception $e) {
    error_log('Error in transcript analysis: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to analyze transcripts']);
}
?>
