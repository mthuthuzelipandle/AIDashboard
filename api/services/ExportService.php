<?php
require_once __DIR__ . '/../config.php';

class ExportService {
    private $db;
    private $userId;
    private $supportedFormats = ['csv', 'json', 'pdf', 'xlsx', 'png', 'svg'];
    
    public function __construct($userId) {
        $this->db = getDBConnection();
        $this->userId = $userId;
    }
    
    public function exportAnalytics($type, $format, $dateRange = null) {
        if (!in_array($format, $this->supportedFormats)) {
            throw new Exception('Unsupported export format');
        }
        
        $data = $this->fetchAnalyticsData($type, $dateRange);
        
        // Log export activity
        $this->logExport($type, $format);
        
        switch ($format) {
            case 'csv':
                return $this->generateCSV($data);
            case 'json':
                return $this->generateJSON($data);
            case 'pdf':
                return $this->generatePDF($data, $type);
            case 'xlsx':
                return $this->generateXLSX($data);
            case 'png':
            case 'svg':
                return $this->generateChart($data, $type, $format);
        }
    }
    
    private function fetchAnalyticsData($type, $dateRange) {
        $params = [$this->userId];
        $dateFilter = '';
        
        if ($dateRange) {
            $dateFilter = 'AND created_at BETWEEN ? AND ?';
            array_push($params, $dateRange['start'], $dateRange['end']);
        }
        
        $query = '';
        switch ($type) {
            case 'overview':
                $query = "
                    SELECT 
                        date(created_at) as date,
                        active_users,
                        page_views,
                        avg_session_duration,
                        bounce_rate
                    FROM analytics_overview
                    WHERE user_id = ? $dateFilter
                    ORDER BY created_at DESC
                ";
                break;
                
            case 'user-stats':
                $query = "
                    SELECT 
                        date(created_at) as date,
                        total_users,
                        new_users,
                        returning_users,
                        demographics
                    FROM user_analytics
                    WHERE user_id = ? $dateFilter
                    ORDER BY created_at DESC
                ";
                break;
                
            case 'ai-insights':
                $query = "
                    SELECT 
                        date(created_at) as date,
                        query,
                        generated_sql,
                        visualization_type,
                        sentiment_score
                    FROM ai_analytics_queries
                    WHERE user_id = ? 
                    AND status = 'completed'
                    $dateFilter
                    ORDER BY created_at DESC
                ";
                break;
                
            default:
                throw new Exception('Invalid analytics type');
        }
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function generateCSV($data) {
        if (empty($data)) {
            throw new Exception('No data to export');
        }
        
        $output = fopen('php://temp', 'r+');
        
        // Add headers
        fputcsv($output, array_keys($data[0]));
        
        // Add data rows
        foreach ($data as $row) {
            fputcsv($output, array_map(function($value) {
                return is_array($value) ? json_encode($value) : $value;
            }, $row));
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }
    
    private function generateJSON($data) {
        return json_encode($data, JSON_PRETTY_PRINT);
    }
    
    private function generatePDF($data, $type) {
        require_once __DIR__ . '/../../vendor/autoload.php';
        
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Set document information
        $pdf->SetCreator('AI Analytics Dashboard');
        $pdf->SetAuthor('System');
        $pdf->SetTitle('Analytics Export');
        
        // Add a page
        $pdf->AddPage();
        
        // Set font
        $pdf->SetFont('helvetica', '', 10);
        
        // Create table content
        $html = '<table border="1" cellpadding="4">';
        
        // Add headers
        $html .= '<tr>';
        foreach (array_keys($data[0]) as $header) {
            $html .= '<th>' . htmlspecialchars($header) . '</th>';
        }
        $html .= '</tr>';
        
        // Add data rows
        foreach ($data as $row) {
            $html .= '<tr>';
            foreach ($row as $value) {
                $html .= '<td>' . htmlspecialchars(
                    is_array($value) ? json_encode($value) : $value
                ) . '</td>';
            }
            $html .= '</tr>';
        }
        
        $html .= '</table>';
        
        // Add charts if available
        if ($type !== 'all') {
            $chartData = $this->prepareChartData($data, $type);
            if ($chartData) {
                $pdf->AddPage();
                $pdf->writeHTML('<h3>Data Visualization</h3>', true, false, true, false, '');
                
                // Generate chart as PNG and embed in PDF
                $chart = $this->generateChart($data, $type, 'png');
                $tempFile = tempnam(sys_get_temp_dir(), 'chart');
                file_put_contents($tempFile, $chart);
                
                $pdf->Image($tempFile, 15, $pdf->GetY(), 180);
                unlink($tempFile);
            }
        }
        
        // Print table
        $pdf->writeHTML($html, true, false, true, false, '');
        
        return $pdf->Output('', 'S');
    }
    
    private function generateXLSX($data) {
        require_once __DIR__ . '/../../vendor/autoload.php';
        
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Add headers
        $column = 1;
        foreach (array_keys($data[0]) as $header) {
            $sheet->setCellValueByColumnAndRow($column++, 1, $header);
        }
        
        // Add data rows
        $row = 2;
        foreach ($data as $rowData) {
            $column = 1;
            foreach ($rowData as $value) {
                $sheet->setCellValueByColumnAndRow(
                    $column++, 
                    $row, 
                    is_array($value) ? json_encode($value) : $value
                );
            }
            $row++;
        }
        
        // Create Excel file
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        ob_start();
        $writer->save('php://output');
        return ob_get_clean();
    }
    
    private function generateChart($data, $type, $format) {
        require_once __DIR__ . '/../../vendor/autoload.php';
        
        // Prepare chart data
        $chartData = $this->prepareChartData($data, $type);
        if (!$chartData) {
            throw new Exception('No data available for visualization');
        }
        
        // Create chart using PHP GD
        $width = 800;
        $height = 400;
        $image = imagecreatetruecolor($width, $height);
        
        // Set background
        $white = imagecolorallocate($image, 255, 255, 255);
        imagefill($image, 0, 0, $white);
        
        // Set colors
        $colors = [
            imagecolorallocate($image, 54, 162, 235),   // Blue
            imagecolorallocate($image, 255, 99, 132),   // Red
            imagecolorallocate($image, 75, 192, 192),   // Teal
            imagecolorallocate($image, 255, 206, 86),   // Yellow
            imagecolorallocate($image, 153, 102, 255),  // Purple
        ];
        
        // Draw chart based on type
        switch ($type) {
            case 'overview':
                $this->drawLineChart($image, $chartData, $colors);
                break;
            case 'user-stats':
                $this->drawBarChart($image, $chartData, $colors);
                break;
            case 'ai-insights':
                $this->drawPieChart($image, $chartData, $colors);
                break;
            default:
                throw new Exception('Unsupported chart type');
        }
        
        // Output image
        ob_start();
        if ($format === 'png') {
            imagepng($image);
        } else { // svg
            // Convert to SVG using imagick
            $imagick = new Imagick();
            $imagick->readImageBlob(ob_get_clean());
            $imagick->setImageFormat('svg');
            echo $imagick->getImageBlob();
            $imagick->clear();
            $imagick->destroy();
        }
        
        imagedestroy($image);
        return ob_get_clean();
    }
    
    private function prepareChartData($data, $type) {
        $chartData = ['labels' => [], 'datasets' => []];
        
        switch ($type) {
            case 'overview':
                foreach ($data as $row) {
                    $chartData['labels'][] = $row['date'];
                    $chartData['datasets']['page_views'][] = $row['page_views'];
                    $chartData['datasets']['active_users'][] = $row['active_users'];
                }
                break;
                
            case 'user-stats':
                foreach ($data as $row) {
                    $chartData['labels'][] = $row['date'];
                    $chartData['datasets']['new_users'][] = $row['new_users'];
                    $chartData['datasets']['returning_users'][] = $row['returning_users'];
                }
                break;
                
            case 'ai-insights':
                $sentiments = ['positive' => 0, 'neutral' => 0, 'negative' => 0];
                foreach ($data as $row) {
                    if ($row['sentiment_score'] > 0.5) $sentiments['positive']++;
                    elseif ($row['sentiment_score'] < -0.5) $sentiments['negative']++;
                    else $sentiments['neutral']++;
                }
                $chartData['labels'] = array_keys($sentiments);
                $chartData['datasets'] = array_values($sentiments);
                break;
        }
        
        return $chartData;
    }
    
    private function drawLineChart($image, $chartData, $colors) {
        $padding = 40;
        $width = imagesx($image) - (2 * $padding);
        $height = imagesy($image) - (2 * $padding);
        
        // Draw axes
        $black = imagecolorallocate($image, 0, 0, 0);
        imageline($image, $padding, $height + $padding, $width + $padding, $height + $padding, $black); // X-axis
        imageline($image, $padding, $padding, $padding, $height + $padding, $black); // Y-axis
        
        // Plot data
        $datasetIndex = 0;
        foreach ($chartData['datasets'] as $label => $dataset) {
            $points = [];
            $count = count($dataset);
            $max = max($dataset);
            
            for ($i = 0; $i < $count; $i++) {
                $x = $padding + ($i * ($width / ($count - 1)));
                $y = $padding + $height - ($dataset[$i] * ($height / $max));
                $points[] = ['x' => $x, 'y' => $y];
                
                if ($i > 0) {
                    imageline($image, 
                        $points[$i-1]['x'], $points[$i-1]['y'],
                        $points[$i]['x'], $points[$i]['y'],
                        $colors[$datasetIndex % count($colors)]
                    );
                }
            }
            $datasetIndex++;
        }
    }
    
    private function drawBarChart($image, $chartData, $colors) {
        $padding = 40;
        $width = imagesx($image) - (2 * $padding);
        $height = imagesy($image) - (2 * $padding);
        
        // Draw axes
        $black = imagecolorallocate($image, 0, 0, 0);
        imageline($image, $padding, $height + $padding, $width + $padding, $height + $padding, $black);
        imageline($image, $padding, $padding, $padding, $height + $padding, $black);
        
        // Plot bars
        $datasetIndex = 0;
        foreach ($chartData['datasets'] as $label => $dataset) {
            $barWidth = ($width / (count($dataset) * 2));
            $max = max($dataset);
            
            for ($i = 0; $i < count($dataset); $i++) {
                $x = $padding + ($i * ($width / count($dataset))) + $barWidth/2;
                $barHeight = ($dataset[$i] * ($height / $max));
                $y = $height + $padding - $barHeight;
                
                imagefilledrectangle($image,
                    $x, $y,
                    $x + $barWidth, $height + $padding,
                    $colors[$datasetIndex % count($colors)]
                );
            }
            $datasetIndex++;
        }
    }
    
    private function drawPieChart($image, $chartData, $colors) {
        $centerX = imagesx($image) / 2;
        $centerY = imagesy($image) / 2;
        $radius = min($centerX, $centerY) - 60;
        
        $total = array_sum($chartData['datasets']);
        $start = 0;
        
        for ($i = 0; $i < count($chartData['datasets']); $i++) {
            $value = $chartData['datasets'][$i];
            $slice = ($value / $total) * 360;
            
            imagefilledarc(
                $image,
                $centerX, $centerY,
                $radius * 2, $radius * 2,
                $start, $start + $slice,
                $colors[$i % count($colors)],
                IMG_ARC_PIE
            );
            
            $start += $slice;
        }
    }
    
    private function logExport($type, $format) {
        $stmt = $this->db->prepare('
            INSERT INTO export_logs (user_id, export_type, format, created_at)
            VALUES (?, ?, ?, NOW())
        ');
        $stmt->execute([$this->userId, $type, $format]);
    }
}
