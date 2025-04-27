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
    
    // Create users table if it doesn't exist
    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role VARCHAR(50) NOT NULL DEFAULT 'user',
        active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    // Check if admin user exists
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute(['admin@storiesfromtheweb.org']);
    
    if (!$stmt->fetch()) {
        // Create admin user
        $stmt = $db->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            'Admin',
            'admin@storiesfromtheweb.org',
            password_hash('admin123', PASSWORD_DEFAULT),
            'admin'
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Admin user created successfully'
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'message' => 'Admin user already exists'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}