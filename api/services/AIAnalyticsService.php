<?php
require_once __DIR__ . '/../config.php';

class AIAnalyticsService {
    private $db;
    private $userId;
    private $userRole;
    private $themes;
    private $currentTheme = 'default';
    
    public function __construct($userId) {
        $this->db = getDBConnection();
        $this->userId = $userId;
        $this->loadUserRole();
        $this->themes = require __DIR__ . '/../config/chart_themes.php';
        $this->startOrUpdateSession();
    }
    
    private function loadUserRole() {
        $stmt = $this->db->prepare('
            SELECT r.name as role_name 
            FROM users u 
            JOIN user_roles r ON u.role_id = r.id 
            WHERE u.id = ?
        ');
        $stmt->execute([$this->userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$result) {
            throw new Exception('User role not found');
        }
        $this->userRole = $result['role_name'];
    }

    private function startOrUpdateSession() {
        // End any existing active sessions
        $stmt = $this->db->prepare('
            UPDATE user_sessions 
            SET session_end = CURRENT_TIMESTAMP, is_active = FALSE 
            WHERE user_id = ? AND is_active = TRUE
        ');
        $stmt->execute([$this->userId]);

        // Start a new session
        $stmt = $this->db->prepare('
            INSERT INTO user_sessions (user_id) 
            VALUES (?)
        ');
        $stmt->execute([$this->userId]);
    }

    public function logChatInteraction($message, $response, $hasVisualization = false) {
        $stmt = $this->db->prepare('
            INSERT INTO chat_history (user_id, message, response, thread_id, sentiment_score, sentiment_label, category)
            VALUES (?, ?, ?, ?)
        ');
        $stmt->execute([
            $this->userId,
            $message,
            $response,
            $hasVisualization
        ]);
    }
    
    private function hasPermission($permission) {
        $stmt = $this->db->prepare('
            SELECT COUNT(*) as has_permission
            FROM role_permissions rp
            JOIN permissions p ON rp.permission_id = p.id
            JOIN user_roles r ON rp.role_id = r.id
            JOIN users u ON u.role_id = r.id
            WHERE u.id = ? AND p.name = ?
        ');
        $stmt->execute([$this->userId, $permission]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['has_permission'] > 0;
    }
    
    public function generateQuery($prompt) {
        // Log the analytics query
        $stmt = $this->db->prepare('
            INSERT INTO ai_analytics_queries (user_id, query, status)
            VALUES (?, ?, "processing")
        ');
        $stmt->execute([$this->userId, $prompt]);
        $queryId = $this->db->lastInsertId();
        
        try {
            // Use OpenAI to generate SQL based on the prompt
            $response = $this->queryOpenAI($prompt);
            $sql = $this->extractSQLFromResponse($response);
            
            // Validate and secure the generated SQL
            $sql = $this->validateAndSecureSQL($sql);
            
            // Update the query record with the generated SQL
            $stmt = $this->db->prepare('
                UPDATE ai_analytics_queries 
                SET generated_sql = ?, status = "completed"
                WHERE id = ?
            ');
            $stmt->execute([$sql, $queryId]);
            
            return $sql;
        } catch (Exception $e) {
            // Log the error
            $stmt = $this->db->prepare('
                UPDATE ai_analytics_queries 
                SET status = "error", error_message = ?
                WHERE id = ?
            ');
            $stmt->execute([$e->getMessage(), $queryId]);
            throw $e;
        }
    }
    
    private function queryOpenAI($prompt) {
        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . OPENAI_API_KEY
        ]);
        
        $messages = [
            [
                'role' => 'system',
                'content' => 'You are a SQL expert. Generate secure SQL queries based on natural language prompts. Only use tables: users, analysis_data, saved_visualizations. Always include proper security checks.'
            ],
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ];
        
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'model' => 'gpt-4-turbo-preview',
            'messages' => $messages,
            'temperature' => 0.3,
            'max_tokens' => 500
        ]));
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
    
    private function extractSQLFromResponse($response) {
        // Extract SQL from OpenAI response
        if (isset($response['choices'][0]['message']['content'])) {
            $content = $response['choices'][0]['message']['content'];
            if (preg_match('/```sql\n(.*?)\n```/s', $content, $matches)) {
                return trim($matches[1]);
            }
        }
        throw new Exception('Failed to generate SQL query');
    }
    
    private function validateAndSecureSQL($sql) {
        // Basic SQL injection prevention
        if (preg_match('/(DELETE|DROP|TRUNCATE|ALTER|UPDATE|INSERT)/i', $sql)) {
            throw new Exception('Invalid query type detected');
        }
        
        // Ensure user data access restrictions
        if ($this->userRole !== 'admin') {
            // Add user_id restriction for non-admin users
            if (stripos($sql, 'WHERE') !== false) {
                $sql = preg_replace('/WHERE/i', 'WHERE user_id = ' . $this->userId . ' AND ', $sql, 1);
            } else {
                $sql .= ' WHERE user_id = ' . $this->userId;
            }
        }
        
        return $sql;
    }
    
    public function executeQuery($sql) {
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception('Query execution failed: ' . $e->getMessage());
        }
    }
    
    public function setTheme($theme) {
        if (isset($this->themes[$theme])) {
            $this->currentTheme = $theme;
        }
    }

    public function generateVisualization($data, $type = 'bar', $theme = null) {
        if ($theme && isset($this->themes[$theme])) {
            $this->currentTheme = $theme;
        }
        // Convert SQL results to visualization config
        $config = [
            'type' => $type,
            'data' => $this->formatDataForChart($data, $type),
            'options' => $this->getChartOptions($type)
        ];
        
        // Save visualization
        $stmt = $this->db->prepare('
            INSERT INTO saved_visualizations (user_id, name, type, config)
            VALUES (?, ?, ?, ?)
        ');
        $stmt->execute([
            $this->userId,
            'Generated Chart',
            $type,
            json_encode($config)
        ]);
        
        return $config;
    }
    
    private function getThemeColors() {
        return $this->themes[$this->currentTheme];
    }

    private function formatDataForChart($data, $type) {
        switch ($type) {
            case 'bar':
            case 'line':
            case 'area':
                return [
                    'labels' => array_column($data, array_keys($data[0])[0]),
                    'datasets' => [[
                        'data' => array_column($data, array_keys($data[0])[1]),
                        'backgroundColor' => $this->getThemeColors()['colors'][0],
                        'borderColor' => $this->getThemeColors()['borderColors'][0],
                        'borderWidth' => 1
                    ]]
                ];
            case 'pie':
            case 'doughnut':
                return [
                    'labels' => array_column($data, array_keys($data[0])[0]),
                    'datasets' => [[
                        'data' => array_column($data, array_keys($data[0])[1]),
                        'backgroundColor' => array_slice($this->getThemeColors()['colors'], 0, count($data)),
                        'borderColor' => array_slice($this->getThemeColors()['borderColors'], 0, count($data))
                    ]]
                ];
            case 'radar':
                return [
                    'labels' => array_column($data, array_keys($data[0])[0]),
                    'datasets' => [[
                        'data' => array_column($data, array_keys($data[0])[1]),
                        'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
                        'borderColor' => 'rgba(54, 162, 235, 1)',
                        'pointBackgroundColor' => 'rgba(54, 162, 235, 1)',
                        'pointBorderColor' => '#fff',
                        'pointHoverBackgroundColor' => '#fff',
                        'pointHoverBorderColor' => 'rgba(54, 162, 235, 1)'
                    ]]
                ];
            case 'bubble':
                return [
                    'datasets' => [[
                        'data' => array_map(function($row) {
                            return [
                                'x' => $row[array_keys($row)[0]],
                                'y' => $row[array_keys($row)[1]],
                                'r' => isset($row[array_keys($row)[2]]) ? $row[array_keys($row)[2]] : 10
                            ];
                        }, $data),
                        'backgroundColor' => 'rgba(54, 162, 235, 0.5)'
                    ]]
                ];
            case 'scatter':
                return [
                    'datasets' => [[
                        'data' => array_map(function($row) {
                            return [
                                'x' => $row[array_keys($row)[0]],
                                'y' => $row[array_keys($row)[1]]
                            ];
                        }, $data),
                        'backgroundColor' => 'rgba(54, 162, 235, 0.5)',
                        'pointRadius' => 6
                    ]]
                ];
            case 'heatmap':
                $labels = array_unique(array_column($data, array_keys($data[0])[0]));
                $datasets = [];
                foreach ($data as $row) {
                    $x = array_keys($row)[0];
                    $y = array_keys($row)[1];
                    $value = $row[array_keys($row)[2]];
                    if (!isset($datasets[$y])) {
                        $datasets[$y] = [
                            'label' => $y,
                            'data' => array_fill(0, count($labels), null)
                        ];
                    }
                    $datasets[$y]['data'][array_search($x, $labels)] = $value;
                }
                return [
                    'labels' => $labels,
                    'datasets' => array_values($datasets)
                ];
            case 'gauge':
                $value = $data[0][array_keys($data[0])[0]];
                $min = 0;
                $max = max(100, ceil($value / 100) * 100);
                return [
                    'datasets' => [[
                        'data' => [$value],
                        'backgroundColor' => [
                            'rgba(54, 162, 235, 0.8)',
                            'rgba(200, 200, 200, 0.2)'
                        ],
                        'borderWidth' => 0
                    ]],
                    'options' => [
                        'rotation' => -Math.PI,
                        'circumference' => Math.PI,
                        'cutout' => '75%'
                    ]
                ];
            default:
                throw new Exception('Unsupported chart type');
        }
    }
    
    private function getChartOptions($type) {
        $baseOptions = [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'position' => 'top'
                ],
                'title' => [
                    'display' => true,
                    'text' => 'Generated Chart'
                ],
                'tooltip' => [
                    'enabled' => true,
                    'mode' => 'index',
                    'intersect' => false
                ]
            ],
            'animation' => [
                'duration' => 1000,
                'easing' => 'easeInOutQuart'
            ]
        ];
        
        switch ($type) {
            case 'bar':
                return array_merge($baseOptions, [
                    'scales' => [
                        'y' => ['beginAtZero' => true]
                    ]
                ]);
            case 'line':
                return array_merge($baseOptions, [
                    'scales' => [
                        'y' => ['beginAtZero' => true]
                    ],
                    'elements' => [
                        'line' => ['tension' => 0.4]
                    ]
                ]);
            case 'radar':
                return array_merge($baseOptions, [
                    'scales' => [
                        'r' => [
                            'beginAtZero' => true,
                            'ticks' => ['stepSize' => 20]
                        ]
                    ]
                ]);
            case 'bubble':
            case 'scatter':
                return array_merge($baseOptions, [
                    'scales' => [
                        'x' => ['type' => 'linear', 'position' => 'bottom'],
                        'y' => ['type' => 'linear']
                    ]
                ]);
            case 'heatmap':
                return array_merge($baseOptions, [
                    'scales' => [
                        'x' => ['type' => 'category'],
                        'y' => ['type' => 'category']
                    ],
                    'plugins' => [
                        'legend' => ['display' => false],
                        'tooltip' => [
                            'callbacks' => [
                                'label' => 'function(context) { return `Value: ${context.parsed.v}`; }'
                            ]
                        ]
                    ]
                ]);
            case 'gauge':
                return array_merge($baseOptions, [
                    'plugins' => [
                        'legend' => ['display' => false],
                        'tooltip' => ['enabled' => false]
                    ]
                ]);
            default:
                return $baseOptions;
        }
    }
}
?>
