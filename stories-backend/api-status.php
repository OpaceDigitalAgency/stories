<?php
// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://storiesfromtheweb.netlify.app');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

// Check database connection
$dbStatus = 'unknown';
$dbMessage = '';

try {
    // Database configuration
    $config = [
        'host' => 'localhost',
        'name' => 'stories_db',
        'user' => 'stories_user',
        'password' => '$tw1cac3*sOt',
        'charset' => 'utf8mb4',
        'port' => 3306
    ];
    
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
    
    // Test query
    $stmt = $db->query("SELECT 1");
    $result = $stmt->fetch();
    
    if ($result) {
        $dbStatus = 'connected';
        $dbMessage = 'Database connection successful';
    } else {
        $dbStatus = 'error';
        $dbMessage = 'Database query failed';
    }
} catch (PDOException $e) {
    $dbStatus = 'error';
    $dbMessage = 'Database connection failed: ' . $e->getMessage();
}

// Check API endpoints
$endpoints = [
    'stories' => false,
    'authors' => false,
    'tags' => false,
    'games' => false,
    'directory-items' => false,
    'ai-tools' => false
];

foreach ($endpoints as $endpoint => $status) {
    try {
        // Check if the endpoint file exists
        $file = __DIR__ . "/api/v1/Endpoints/" . ucfirst(str_replace('-', '', $endpoint)) . "Controller.php";
        $endpoints[$endpoint] = file_exists($file);
    } catch (Exception $e) {
        $endpoints[$endpoint] = false;
    }
}

// Return status
echo json_encode([
    'status' => 'online',
    'timestamp' => date('Y-m-d H:i:s'),
    'database' => [
        'status' => $dbStatus,
        'message' => $dbMessage
    ],
    'endpoints' => $endpoints,
    'version' => '1.0.0'
]);