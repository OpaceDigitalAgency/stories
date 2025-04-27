<?php
// Database configuration
$config = [
    'host' => 'localhost',
    'name' => 'stories_db',
    'user' => 'stories_user',
    'password' => '$tw1cac3*sOt',
    'charset' => 'utf8mb4',
    'port' => 3306
];

try {
    // Connect to database
    $db = new PDO(
        "mysql:host={$config['host']};dbname={$config['name']};charset={$config['charset']}",
        $config['user'],
        $config['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );

    echo "Connected to database successfully.\n";
    
    // Check if avatar_url column exists in authors table
    $stmt = $db->query("SHOW COLUMNS FROM authors LIKE 'avatar_url'");
    $columnExists = $stmt->rowCount() > 0;
    
    if (!$columnExists) {
        // Add avatar_url column to authors table
        $db->exec("ALTER TABLE authors ADD COLUMN avatar_url VARCHAR(255) DEFAULT NULL");
        echo "Added avatar_url column to authors table.\n";
    } else {
        echo "avatar_url column already exists in authors table.\n";
    }
    
    // Show all columns in authors table
    $stmt = $db->query("DESCRIBE authors");
    $columns = $stmt->fetchAll();
    
    echo "\nColumns in authors table:\n";
    foreach ($columns as $column) {
        echo "- " . $column['Field'] . " (" . $column['Type'] . ")\n";
    }
    
    // Show all authors with their avatar_url
    $stmt = $db->query("SELECT id, name, avatar_url FROM authors");
    $authors = $stmt->fetchAll();
    
    echo "\nAuthors in database:\n";
    foreach ($authors as $author) {
        echo "- ID: " . $author['id'] . ", Name: " . $author['name'] . ", Avatar URL: " . ($author['avatar_url'] ?? 'NULL') . "\n";
    }
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}