<?php
// Start session
session_start();

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
try {
    $dsn = "mysql:host={$config['host']};dbname={$config['name']};charset={$config['charset']}";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    $db = new PDO($dsn, $config['user'], $config['password'], $options);
    echo "<p>Database connection successful!</p>";
} catch (PDOException $e) {
    echo "<p>Database connection failed: " . $e->getMessage() . "</p>";
    exit;
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = $_POST['email'];
    
    // Get user from database
    $stmt = $db->prepare("SELECT id, name, email, role FROM users WHERE email = ? AND active = 1 LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user) {
        // Set session variables directly
        $_SESSION['auth_user'] = $user;
        $_SESSION['auth_time'] = time();
        
        echo "<p>Login successful! You are now logged in as {$user['name']} ({$user['email']}).</p>";
        echo "<p><a href='dashboard.php'>Go to Dashboard</a></p>";
    } else {
        echo "<p>User not found or inactive.</p>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Emergency Login</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        form { margin: 20px 0; padding: 20px; border: 1px solid #ccc; max-width: 400px; }
        label { display: block; margin-bottom: 5px; }
        input[type="email"] { width: 100%; padding: 8px; margin-bottom: 10px; }
        button { padding: 8px 16px; background: #4361ee; color: white; border: none; cursor: pointer; }
    </style>
</head>
<body>
    <h1>Emergency Login</h1>
    <p>This page allows you to bypass the normal login process in case of issues.</p>
    
    <form method="POST">
        <label for="email">Enter your admin email:</label>
        <input type="email" id="email" name="email" required>
        <button type="submit">Login</button>
    </form>
    
    <h2>Session Information</h2>
    <pre><?php print_r($_SESSION); ?></pre>
    
    <h2>Cookie Information</h2>
    <pre><?php print_r($_COOKIE); ?></pre>
</body>
</html>
