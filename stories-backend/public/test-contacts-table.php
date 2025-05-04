<?php
/**
 * Test script to check the contacts table
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Testing Contacts Table</h1>";

// Database configurations to try
$dbConfigs = [
    [
        'host' => 'localhost',
        'dbname' => 'stories_db',
        'user' => 'stories_user',
        'password' => '$tw1cac3*sOt',
        'charset' => 'utf8mb4'
    ],
    [
        'host' => '127.0.0.1',
        'dbname' => 'stories_db',
        'user' => 'stories_user',
        'password' => '$tw1cac3*sOt',
        'charset' => 'utf8mb4'
    ]
];

$db = null;
$connectionError = null;

echo "<h2>Testing Database Connection</h2>";

foreach ($dbConfigs as $config) {
    try {
        echo "<p>Trying connection to {$config['host']}...</p>";
        $db = new PDO(
            "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}",
            $config['user'],
            $config['password'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        echo "<p style='color:green'>Connection successful to {$config['host']}</p>";
        break;
    } catch (PDOException $e) {
        $connectionError = $e->getMessage();
        echo "<p style='color:red'>Connection failed to {$config['host']}: {$connectionError}</p>";
    }
}

if (!$db) {
    echo "<p style='color:red'>All database connection attempts failed. Last error: " . ($connectionError ?? 'Unknown error') . "</p>";
    exit;
}

echo "<h2>Checking Contacts Table</h2>";

try {
    $stmt = $db->query("SHOW TABLES LIKE 'contacts'");
    if ($stmt->rowCount() === 0) {
        echo "<p style='color:orange'>Contacts table doesn't exist. Creating it now...</p>";
        $db->exec("CREATE TABLE contacts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            subject VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            is_responded TINYINT(1) DEFAULT 0,
            admin_notes TEXT,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        echo "<p style='color:green'>Contacts table created successfully.</p>";
    } else {
        echo "<p style='color:green'>Contacts table exists.</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color:red'>Error checking/creating contacts table: " . $e->getMessage() . "</p>";
    exit;
}

echo "<h2>Checking Contacts Data</h2>";

try {
    $stmt = $db->query("SELECT COUNT(*) FROM contacts");
    $count = $stmt->fetchColumn();
    echo "<p>Total contacts: {$count}</p>";

    if ($count === 0) {
        echo "<p style='color:orange'>No contacts found. Adding a test contact...</p>";
        $stmt = $db->prepare("INSERT INTO contacts (name, email, subject, message) VALUES (?, ?, ?, ?)");
        $stmt->execute(['Test User', 'test@example.com', 'Test Subject', 'This is a test message.']);
        echo "<p style='color:green'>Test contact added successfully.</p>";
    }

    echo "<h3>Contact Records</h3>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Subject</th><th>Message</th><th>Responded</th><th>Created At</th></tr>";

    $stmt = $db->query("SELECT * FROM contacts ORDER BY created_at DESC");
    $contacts = $stmt->fetchAll();

    if (count($contacts) > 0) {
        foreach ($contacts as $contact) {
            echo "<tr>";
            echo "<td>{$contact['id']}</td>";
            echo "<td>" . htmlspecialchars($contact['name']) . "</td>";
            echo "<td>" . htmlspecialchars($contact['email']) . "</td>";
            echo "<td>" . htmlspecialchars($contact['subject']) . "</td>";
            echo "<td>" . htmlspecialchars(substr($contact['message'], 0, 50)) . (strlen($contact['message']) > 50 ? '...' : '') . "</td>";
            echo "<td>" . ($contact['is_responded'] ? 'Yes' : 'No') . "</td>";
            echo "<td>" . $contact['created_at'] . "</td>";
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan='7'>No contacts found.</td></tr>";
    }

    echo "</table>";
} catch (PDOException $e) {
    echo "<p style='color:red'>Error querying contacts: " . $e->getMessage() . "</p>";
}
