<?php
/**
 * Find Admin User Credentials
 *
 * This script helps find admin users in the database.
 */

// Check if running in web or CLI mode
$isWeb = php_sapi_name() !== 'cli';

// Function to output text based on environment
function output($text, $isHtml = false) {
    global $isWeb;
    if ($isWeb) {
        echo $isHtml ? $text : nl2br(htmlspecialchars($text)) . "<br>";
    } else {
        echo $text . ($isHtml ? '' : "\n");
    }
}

// Include the database configuration
require_once __DIR__ . '/api/v1/config/config.php';

// Set content type for web
if ($isWeb) {
    header('Content-Type: text/html; charset=utf-8');
    output('<!DOCTYPE html>
<html>
<head>
    <title>Admin User Finder</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        h1 { color: #333; }
        .container { max-width: 800px; margin: 0 auto; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .admin { background: #f5f5f5; padding: 10px; margin-bottom: 10px; border-left: 4px solid #0066cc; }
        pre { background: #f5f5f5; padding: 10px; overflow: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Admin User Finder</h1>
', true);
}

output("Admin User Finder");
output("================");
output("");

try {
    // Connect to the database
    $dsn = "mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset={$config['db']['charset']}";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    $db = new PDO($dsn, $config['db']['user'], $config['db']['password'], $options);
    
    output("Database connection successful.");
    output("");
    
    // Find admin users
    $query = "SELECT id, name, email, role, active FROM users WHERE role = 'admin'";
    $stmt = $db->query($query);
    $admins = $stmt->fetchAll();
    
    if (count($admins) > 0) {
        output("Found " . count($admins) . " admin users:");
        output("");
        
        foreach ($admins as $admin) {
            if ($isWeb) output('<div class="admin">', true);
            output("ID: " . $admin['id']);
            output("Name: " . $admin['name']);
            output("Email: " . $admin['email']);
            output("Role: " . $admin['role']);
            output("Active: " . ($admin['active'] ? 'Yes' : 'No'));
            if ($isWeb) output('</div>', true);
            output("");
        }
        
        // Try to reset the password for the first active admin
        $activeAdmins = array_filter($admins, function($admin) {
            return $admin['active'] == 1;
        });
        
        if (count($activeAdmins) > 0) {
            $firstAdmin = reset($activeAdmins);
            $adminId = $firstAdmin['id'];
            $adminEmail = $firstAdmin['email'];
            
            // For web server, automatically reset the password
            $resetPassword = true;
            
            if ($resetPassword) {
                $newPassword = 'Admin123!';
                $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
                
                $updateQuery = "UPDATE users SET password = ? WHERE id = ?";
                $updateStmt = $db->prepare($updateQuery);
                $updateStmt->execute([$hashedPassword, $adminId]);
                
                if ($isWeb) output('<div class="success">', true);
                output("Password reset successful!");
                output("New password: {$newPassword}");
                output("Please change this password after logging in.");
                if ($isWeb) output('</div>', true);
            } else {
                output("Password reset cancelled.");
            }
        }
    } else {
        if ($isWeb) output('<div class="error">', true);
        output("No admin users found in the database.");
        if ($isWeb) output('</div>', true);
        
        // Create a new admin user
        // For web server, automatically create a new admin user
        $createAdmin = true;
        
        if ($createAdmin) {
            $name = "Administrator";
            $email = "admin@storiesfromtheweb.org";
            $password = "Admin123!";
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            
            $insertQuery = "INSERT INTO users (name, email, password, role, active) VALUES (?, ?, ?, 'admin', 1)";
            $insertStmt = $db->prepare($insertQuery);
            $insertStmt->execute([$name, $email, $hashedPassword]);
            
            $newAdminId = $db->lastInsertId();
            
            if ($isWeb) output('<div class="success">', true);
            output("New admin user created successfully!");
            output("ID: {$newAdminId}");
            output("Name: {$name}");
            output("Email: {$email}");
            output("Password: {$password}");
            output("Please change this password after logging in.");
            if ($isWeb) output('</div>', true);
        } else {
            output("Admin user creation cancelled.");
        }
    }
} catch (PDOException $e) {
    if ($isWeb) output('<div class="error">', true);
    output("Database error: " . $e->getMessage());
    if ($isWeb) output('</div>', true);
}

output("");
output("Script completed.");

// Close HTML if in web mode
if ($isWeb) {
    output('
    </div>
</body>
</html>', true);
}