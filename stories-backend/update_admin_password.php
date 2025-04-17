<?php
/**
 * Update Admin Password Script
 * 
 * This script generates a proper hash for the password "Pa55word!" and updates the admin user in the database.
 */

// Set error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$config = [
    'host'     => 'localhost',      // Database host
    'name'     => 'stories_db',     // Database name
    'user'     => 'stories_user',   // Database username
    'password' => '$tw1cac3*sOt',   // Database password
    'charset'  => 'utf8mb4',        // Character set
    'port'     => 3306              // Database port
];

// HTML header
echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Admin Password</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        h1 { color: #333; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
        .container { border: 1px solid #ddd; padding: 20px; border-radius: 5px; margin-top: 20px; }
        code { background: #f5f5f5; padding: 2px 5px; border-radius: 3px; }
    </style>
</head>
<body>
    <h1>Update Admin Password</h1>
    <div class="container">';

try {
    // Generate password hash for "Pa55word!"
    $password = 'Pa55word!';
    $hash = password_hash($password, PASSWORD_DEFAULT);
    
    echo "<p class='info'>Generated hash for password <code>$password</code>:</p>";
    echo "<pre>$hash</pre>";
    
    // Connect to database
    echo "<p class='info'>Connecting to database...</p>";
    $dsn = "mysql:host={$config['host']};dbname={$config['name']};charset={$config['charset']};port={$config['port']}";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    $pdo = new PDO($dsn, $config['user'], $config['password'], $options);
    echo "<p class='success'>Connected to database successfully.</p>";
    
    // Delete existing admin user
    echo "<p class='info'>Deleting existing admin user...</p>";
    $stmt = $pdo->prepare("DELETE FROM users WHERE email = ?");
    $stmt->execute(['admin@example.com']);
    $deletedCount = $stmt->rowCount();
    
    if ($deletedCount > 0) {
        echo "<p class='success'>Deleted $deletedCount existing admin user(s).</p>";
    } else {
        echo "<p class='info'>No existing admin user found.</p>";
    }
    
    // Insert new admin user with correct hash
    echo "<p class='info'>Creating new admin user with correct password hash...</p>";
    $stmt = $pdo->prepare("
        INSERT INTO users (name, email, password, role, active, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, NOW(), NOW())
    ");
    $stmt->execute([
        'Site Admin',
        'admin@example.com',
        $hash,
        'admin',
        1
    ]);
    
    echo "<p class='success'>Admin user created successfully.</p>";
    
    // Verify the user
    $stmt = $pdo->prepare("SELECT id, name, email, role, active FROM users WHERE email = ?");
    $stmt->execute(['admin@example.com']);
    $user = $stmt->fetch();
    
    echo "<p class='success'>Admin user details:</p>";
    echo "<pre>";
    print_r($user);
    echo "</pre>";
    
    echo "<p class='info'>You can now log in with:</p>";
    echo "<p><strong>Email:</strong> admin@example.com</p>";
    echo "<p><strong>Password:</strong> Pa55word!</p>";
    
    // Security reminder
    echo "<p class='error'><strong>IMPORTANT:</strong> For security, delete this file after use!</p>";
    
    // Link to login page
    echo "<p><a href='admin/login.php'>Go to Login Page</a></p>";
    
} catch (PDOException $e) {
    echo "<p class='error'>Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Please check your database configuration in this file.</p>";
} catch (Exception $e) {
    echo "<p class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// HTML footer
echo '</div>
</body>
</html>';