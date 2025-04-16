<?php
// Direct login script
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "<h1>Direct Login</h1>";

try {
    // Connect directly with PDO
    $host = 'localhost';
    $dbname = 'stories_db';
    $username = 'stories_user';
    $password = '$tw1cac3*sOt';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get admin user
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute(['admin@example.com']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "<p>✅ Found admin user: " . $user['email'] . "</p>";
        
        // Store user in session
        $_SESSION['user'] = [
            'id' => $user['id'],
            'name' => $user['name'] ?? 'Admin',
            'email' => $user['email'],
            'role' => $user['role']
        ];
        
        // Generate a simple token
        $jwtSecret = '$tw1cac3*sOt'; // Same as in config
        $payload = [
            'user_id' => $user['id'],
            'role' => $user['role'],
            'iat' => time(),
            'exp' => time() + 86400
        ];
        
        // Create JWT token (simplified)
        $header = base64_encode(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
        $payloadEncoded = base64_encode(json_encode($payload));
        $signature = hash_hmac('sha256', "$header.$payloadEncoded", $jwtSecret, true);
        $signatureEncoded = base64_encode($signature);
        $token = "$header.$payloadEncoded.$signatureEncoded";
        
        // Set cookie
        setcookie('auth_token', $token, time() + 86400, '/', '', false, true);
        
        echo "<p>✅ User stored in session</p>";
        echo "<p>✅ Auth token set in cookie</p>";
        echo "<p>Session data:</p>";
        echo "<pre>" . print_r($_SESSION, true) . "</pre>";
        
        echo "<p><a href='admin/index.php' class='btn btn-success'>Go to Admin Dashboard</a></p>";
    } else {
        echo "<p>❌ Admin user not found</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}