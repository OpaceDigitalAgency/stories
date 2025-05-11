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

// Check if auth_tokens table exists
$stmt = $db->query("SHOW TABLES LIKE 'auth_tokens'");
if ($stmt->rowCount() === 0) {
    echo "<p>Creating auth_tokens table...</p>";
    
    // Create auth_tokens table
    $db->exec("CREATE TABLE auth_tokens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        token VARCHAR(255) NOT NULL,
        expires_at DATETIME NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY (user_id),
        UNIQUE KEY (token)
    )");
    
    echo "<p>auth_tokens table created successfully!</p>";
} else {
    echo "<p>auth_tokens table already exists.</p>";
    
    // Clear expired tokens
    $db->exec("DELETE FROM auth_tokens WHERE expires_at < NOW()");
    echo "<p>Cleared expired tokens.</p>";
}

// Check if users table exists
$stmt = $db->query("SHOW TABLES LIKE 'users'");
if ($stmt->rowCount() === 0) {
    echo "<p>Creating users table...</p>";
    
    // Create users table
    $db->exec("CREATE TABLE users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        password VARCHAR(255) NOT NULL,
        role VARCHAR(50) NOT NULL DEFAULT 'editor',
        active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY (email)
    )");
    
    // Create default admin user
    $name = 'Admin';
    $email = 'admin@storiesfromtheweb.org';
    $password = password_hash('admin123', PASSWORD_DEFAULT);
    $role = 'admin';
    
    $stmt = $db->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name, $email, $password, $role]);
    
    echo "<p>users table created successfully with default admin user!</p>";
    echo "<p>Default admin credentials:</p>";
    echo "<ul>";
    echo "<li>Email: admin@storiesfromtheweb.org</li>";
    echo "<li>Password: admin123</li>";
    echo "</ul>";
} else {
    echo "<p>users table already exists.</p>";
    
    // List all users
    $stmt = $db->query("SELECT id, name, email, role, active FROM users");
    $users = $stmt->fetchAll();
    
    echo "<p>Users in the database:</p>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Active</th></tr>";
    
    foreach ($users as $user) {
        echo "<tr>";
        echo "<td>{$user['id']}</td>";
        echo "<td>{$user['name']}</td>";
        echo "<td>{$user['email']}</td>";
        echo "<td>{$user['role']}</td>";
        echo "<td>{$user['active']}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
}

// Add a form to create a new admin user
echo "<h2>Create New Admin User</h2>";
echo "<form method='POST'>";
echo "<label>Name: <input type='text' name='name' required></label><br>";
echo "<label>Email: <input type='email' name='email' required></label><br>";
echo "<label>Password: <input type='password' name='password' required></label><br>";
echo "<label>Role: <select name='role'>";
echo "<option value='admin'>Admin</option>";
echo "<option value='editor'>Editor</option>";
echo "</select></label><br>";
echo "<button type='submit'>Create User</button>";
echo "</form>";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];
    
    try {
        $stmt = $db->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $email, $password, $role]);
        
        echo "<p>User created successfully!</p>";
    } catch (PDOException $e) {
        echo "<p>Error creating user: " . $e->getMessage() . "</p>";
    }
}
?>
