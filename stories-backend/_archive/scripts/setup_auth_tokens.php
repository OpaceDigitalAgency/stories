<?php
// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$config = [
    'db' => [
        'host'     => 'localhost',
        'name'     => 'stories_db',
        'user'     => 'stories_user',
        'password' => '$tw1cac3*sOt',
        'charset'  => 'utf8mb4',
        'port'     => 3306
    ]
];

try {
    // Connect to database
    $dsn = "mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset={$config['db']['charset']};port={$config['db']['port']}";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    $db = new PDO($dsn, $config['db']['user'], $config['db']['password'], $options);
    
    // Create auth_tokens table
    $query = "CREATE TABLE IF NOT EXISTS auth_tokens (
        user_id INT NOT NULL,
        token VARCHAR(255) NOT NULL,
        expires_at DATETIME NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (user_id),
        UNIQUE KEY (token)
    )";
    
    $db->exec($query);
    
    echo json_encode([
        'success' => true,
        'message' => 'Auth tokens table created successfully'
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}