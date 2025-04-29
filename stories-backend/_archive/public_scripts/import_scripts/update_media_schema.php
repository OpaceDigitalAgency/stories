<?php
/**
 * Update Media Schema
 * 
 * This script updates the media table schema to support multiple image sizes
 */

// Basic error handling
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Database connection
try {
    $db = new PDO(
        'mysql:host=localhost;dbname=stories_db;charset=utf8mb4',
        'stories_user',
        '$tw1cac3*sOt',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
    
    echo "<p style='color:green'>Database connection successful</p>";
} catch (PDOException $e) {
    echo "<p style='color:red'>Database connection failed: " . $e->getMessage() . "</p>";
    exit;
}

// Check if columns already exist
$columnsExist = false;
try {
    $stmt = $db->query("SHOW COLUMNS FROM media LIKE 'thumbnail_url'");
    $columnsExist = $stmt->rowCount() > 0;
    
    if ($columnsExist) {
        echo "<p style='color:blue'>Media table already has the required columns</p>";
    } else {
        echo "<p style='color:blue'>Media table needs to be updated</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color:red'>Error checking columns: " . $e->getMessage() . "</p>";
    exit;
}

// If columns don't exist, apply the schema update
if (!$columnsExist) {
    try {
        // Read the SQL file
        $sql = file_get_contents(__DIR__ . '/../includes/update_media_schema.sql');
        
        // Split into individual statements
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        // Execute each statement
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                $db->exec($statement);
                echo "<p style='color:green'>Executed: " . htmlspecialchars($statement) . "</p>";
            }
        }
        
        echo "<p style='color:green'>Schema update completed successfully</p>";
    } catch (PDOException $e) {
        echo "<p style='color:red'>Error updating schema: " . $e->getMessage() . "</p>";
        exit;
    }
}

// Check if we need to update existing records
echo "<p style='color:blue'>Checking for existing media records that need URL updates...</p>";

try {
    $stmt = $db->query("SELECT COUNT(*) FROM media WHERE file_path IS NOT NULL AND thumbnail_url IS NULL");
    $count = $stmt->fetchColumn();
    
    echo "<p style='color:blue'>Found $count media records that need URL updates</p>";
    
    if ($count > 0) {
        echo "<p style='color:blue'>To update these records, please run the optimize_image.php script</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color:red'>Error checking records: " . $e->getMessage() . "</p>";
}

echo "<p style='color:green'>Schema update process completed</p>";
echo "<p><a href='optimize_image.php'>Run the image optimization script</a></p>";