<?php
// Database connection
$db = new PDO(
    'mysql:host=localhost;dbname=stories_db;charset=utf8mb4',
    'stories_user',
    '$tw1cac3*sOt',
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]
);

// Get all tables
$tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

// Drop all foreign key constraints
foreach ($tables as $table) {
    $sql = "SELECT CONSTRAINT_NAME 
            FROM information_schema.TABLE_CONSTRAINTS 
            WHERE TABLE_SCHEMA = 'stories_db' 
            AND TABLE_NAME = '$table' 
            AND CONSTRAINT_TYPE = 'FOREIGN KEY'";
            
    $constraints = $db->query($sql)->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($constraints as $constraint) {
        $db->exec("ALTER TABLE $table DROP FOREIGN KEY $constraint");
        echo "Dropped constraint $constraint from $table<br>";
    }
}

// Drop all tables
foreach ($tables as $table) {
    $db->exec("DROP TABLE IF EXISTS $table");
    echo "Dropped table $table<br>";
}

echo "<br>Database cleaned. <a href='setup.php'>Run setup</a>";