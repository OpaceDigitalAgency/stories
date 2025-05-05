<?php
/**
 * Emergency Login
 * 
 * This tool provides emergency login functionality to bypass normal authentication.
 * USE WITH CAUTION - This should only be used in emergency situations.
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include common functions
require_once __DIR__ . '/../includes/common.php';

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
    $dbConnected = true;
} catch (PDOException $e) {
    $dbConnected = false;
    $dbError = $e->getMessage();
}

// Initialize variables
$message = '';
$messageType = '';
$users = [];

// Get users from database
if ($dbConnected) {
    try {
        $stmt = $db->query("SHOW TABLES LIKE 'users'");
        if ($stmt->rowCount() > 0) {
            $stmt = $db->query("SELECT id, name, email, role FROM users");
            $users = $stmt->fetchAll();
        }
    } catch (PDOException $e) {
        // Ignore errors
    }
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $userId = $_POST['user_id'] ?? '';
    
    if (!empty($userId)) {
        // Get user from database
        try {
            $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['authenticated'] = true;
                
                // Generate token
                $token = bin2hex(random_bytes(32));
                $_SESSION['token'] = $token;
                
                // Set cookie
                setcookie('auth_token', $token, time() + 86400, '/');
                
                $message = "Emergency login successful. You are now logged in as {$user['name']}.";
                $messageType = 'success';
            } else {
                $message = "User not found.";
                $messageType = 'danger';
            }
        } catch (PDOException $e) {
            $message = "Database error: " . $e->getMessage();
            $messageType = 'danger';
        }
    } else {
        $message = "Please select a user.";
        $messageType = 'warning';
    }
}

// Handle create admin form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_admin') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (!empty($name) && !empty($email) && !empty($password)) {
        // Check if users table exists
        try {
            $stmt = $db->query("SHOW TABLES LIKE 'users'");
            if ($stmt->rowCount() === 0) {
                // Create users table
                $db->exec("CREATE TABLE IF NOT EXISTS users (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    email VARCHAR(255) NOT NULL UNIQUE,
                    password VARCHAR(255) NOT NULL,
                    role VARCHAR(50) NOT NULL DEFAULT 'user',
                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )");
            }
            
            // Check if email already exists
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->rowCount() > 0) {
                $message = "Email already exists.";
                $messageType = 'danger';
            } else {
                // Hash password
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert user
                $stmt = $db->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'admin')");
                $stmt->execute([$name, $email, $hashedPassword]);
                
                $message = "Admin user created successfully.";
                $messageType = 'success';
                
                // Refresh users list
                $stmt = $db->query("SELECT id, name, email, role FROM users");
                $users = $stmt->fetchAll();
            }
        } catch (PDOException $e) {
            $message = "Database error: " . $e->getMessage();
            $messageType = 'danger';
        }
    } else {
        $message = "Please fill in all fields.";
        $messageType = 'warning';
    }
}

// HTML header
echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Emergency Login</title>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css'>
    <link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css'>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }
        h2 {
            color: #555;
            margin-top: 30px;
        }
        pre {
            background-color: #f8f8f8;
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto;
        }
        .success {
            color: #4CAF50;
        }
        .error {
            color: #F44336;
        }
        .warning {
            color: #FF9800;
        }
    </style>
</head>
<body>
    <div class='container'>
        <h1>Emergency Login</h1>
        <p class='lead'>This tool provides emergency login functionality to bypass normal authentication.</p>
        <div class='alert alert-warning'>
            <strong>Warning:</strong> This tool should only be used in emergency situations when normal login is not working.
        </div>";

// Display message
if (!empty($message)) {
    echo "<div class='alert alert-$messageType'>$message</div>";
}

// Display login form
echo "<div class='card mb-4'>";
echo "<div class='card-header bg-danger text-white'>";
echo "<h2 class='m-0'>Emergency Login</h2>";
echo "</div>";
echo "<div class='card-body'>";

if ($dbConnected) {
    if (!empty($users)) {
        echo "<form method='post' action=''>";
        echo "<div class='mb-3'>";
        echo "<label for='user_id' class='form-label'>Select User</label>";
        echo "<select class='form-select' id='user_id' name='user_id' required>";
        echo "<option value=''>-- Select User --</option>";
        
        foreach ($users as $user) {
            echo "<option value='" . $user['id'] . "'>" . htmlspecialchars($user['name']) . " (" . htmlspecialchars($user['email']) . ") - " . htmlspecialchars($user['role']) . "</option>";
        }
        
        echo "</select>";
        echo "</div>";
        
        echo "<input type='hidden' name='action' value='login'>";
        echo "<button type='submit' class='btn btn-danger'>Emergency Login</button>";
        echo "</form>";
    } else {
        echo "<div class='alert alert-warning'>";
        echo "No users found in the database. Please create an admin user.";
        echo "</div>";
    }
} else {
    echo "<div class='alert alert-danger'>";
    echo "Database connection failed. Please check your database configuration.";
    
    if (isset($dbError)) {
        echo "<p><strong>Error:</strong> " . htmlspecialchars($dbError) . "</p>";
    }
    
    echo "</div>";
}

echo "</div>";
echo "</div>";

// Display create admin form
echo "<div class='card mb-4'>";
echo "<div class='card-header bg-primary text-white'>";
echo "<h2 class='m-0'>Create Admin User</h2>";
echo "</div>";
echo "<div class='card-body'>";

if ($dbConnected) {
    echo "<form method='post' action=''>";
    echo "<div class='mb-3'>";
    echo "<label for='name' class='form-label'>Name</label>";
    echo "<input type='text' class='form-control' id='name' name='name' required>";
    echo "</div>";
    
    echo "<div class='mb-3'>";
    echo "<label for='email' class='form-label'>Email</label>";
    echo "<input type='email' class='form-control' id='email' name='email' required>";
    echo "</div>";
    
    echo "<div class='mb-3'>";
    echo "<label for='password' class='form-label'>Password</label>";
    echo "<input type='password' class='form-control' id='password' name='password' required>";
    echo "</div>";
    
    echo "<input type='hidden' name='action' value='create_admin'>";
    echo "<button type='submit' class='btn btn-primary'>Create Admin User</button>";
    echo "</form>";
} else {
    echo "<div class='alert alert-danger'>";
    echo "Database connection failed. Please check your database configuration.";
    echo "</div>";
}

echo "</div>";
echo "</div>";

// Display session information
echo "<div class='card mb-4'>";
echo "<div class='card-header bg-info text-white'>";
echo "<h2 class='m-0'>Current Session Information</h2>";
echo "</div>";
echo "<div class='card-body'>";

echo "<pre>" . htmlspecialchars(print_r($_SESSION, true)) . "</pre>";

echo "</div>";
echo "</div>";

// Display actions
echo "<div class='card mb-4'>";
echo "<div class='card-header bg-info text-white'>";
echo "<h2 class='m-0'>Actions</h2>";
echo "</div>";
echo "<div class='card-body'>";

echo "<a href='../auth/check_auth_status.php' class='btn btn-info me-2'>";
echo "<i class='fas fa-user-check'></i> Check Auth Status";
echo "</a>";

echo "<a href='../auth/clear_session.php' class='btn btn-warning me-2'>";
echo "<i class='fas fa-trash-alt'></i> Clear Session";
echo "</a>";

echo "<a href='../../admin/login.php' class='btn btn-primary me-2'>";
echo "<i class='fas fa-sign-in-alt'></i> Normal Login";
echo "</a>";

echo "</div>";
echo "</div>";

// HTML footer
echo "
        <div class='mt-4'>
            <a href='/diagnostic-dashboard.php' class='btn btn-primary'>
                <i class='fas fa-arrow-left'></i> Back to Diagnostic Dashboard
            </a>
        </div>
    </div>
    
    <script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js'></script>
</body>
</html>";
