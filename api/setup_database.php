<?php
require_once 'config.php';

try {
    $db = getDBConnection();
    $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, 0);
    
    // Read the SQL file
    $sql = file_get_contents(__DIR__ . '/sql/create_analytics_tables.sql');
    
    // Split into individual statements
    $statements = array_filter(
        explode(';', $sql),
        function($stmt) {
            return trim($stmt) !== '';
        }
    );
    
    // Execute each statement
    foreach ($statements as $statement) {
        $db->exec($statement);
    }
    
    echo "Database setup completed successfully!";
} catch (PDOException $e) {
    echo "Error setting up database: " . $e->getMessage();
}
?>
