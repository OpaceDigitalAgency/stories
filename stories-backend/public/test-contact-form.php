<?php
/**
 * Test Contact Form Submission
 * This script tests the contact form submission by inserting a test record
 */

// Set error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Output header
echo "<h1>Contact Form Test Script</h1>";
echo "<p>This script tests the database connection and inserts a test contact record.</p>";

// Connect to database
try {
    echo "<h2>Attempting Database Connection</h2>";

    // Try different database configurations
    $dbConfigs = [
        [
            'host' => 'localhost',
            'dbname' => 'stories_db',
            'user' => 'stories_user',
            'pass' => '$tw1cac3*sOt'
        ],
        [
            'host' => '127.0.0.1',
            'dbname' => 'stories_db',
            'user' => 'stories_user',
            'pass' => '$tw1cac3*sOt'
        ]
    ];

    $db = null;
    $connectionError = null;

    foreach ($dbConfigs as $config) {
        try {
            echo "Trying connection to {$config['host']}...<br>";
            $db = new PDO(
                "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4",
                $config['user'],
                $config['pass'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
            echo "<p style='color:green'>Connection successful to {$config['host']}!</p>";
            break;
        } catch (PDOException $e) {
            $connectionError = $e->getMessage();
            echo "<p style='color:orange'>Connection failed to {$config['host']}: {$connectionError}</p>";
        }
    }

    if (!$db) {
        throw new Exception("Could not connect to any database. Last error: " . $connectionError);
    }

    // Check if contacts table exists, create if not
    $stmt = $db->query("SHOW TABLES LIKE 'contacts'");
    if ($stmt->rowCount() === 0) {
        echo "Creating contacts table as it doesn't exist<br>";
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
    }

    // Insert test contact
    echo "Inserting test contact<br>";
    $name = "Test User";
    $email = "test@example.com";
    $subject = "Test Subject";
    $message = "This is a test message from the test-contact-form.php script.";
    $is_responded = 0;
    $admin_notes = "Test contact created by test-contact-form.php";

    $stmt = $db->prepare("INSERT INTO contacts (name, email, subject, message, is_responded, admin_notes, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())");
    $stmt->execute([$name, $email, $subject, $message, $is_responded, $admin_notes]);

    $newId = $db->lastInsertId();
    echo "New test contact inserted with ID: {$newId}<br>";

    // Get all contacts
    $stmt = $db->query("SELECT * FROM contacts ORDER BY created_at DESC");
    $contacts = $stmt->fetchAll();

    echo "<h2>All Contacts</h2>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Subject</th><th>Message</th><th>Responded</th><th>Created At</th></tr>";

    foreach ($contacts as $contact) {
        echo "<tr>";
        echo "<td>{$contact['id']}</td>";
        echo "<td>{$contact['name']}</td>";
        echo "<td>{$contact['email']}</td>";
        echo "<td>{$contact['subject']}</td>";
        echo "<td>{$contact['message']}</td>";
        echo "<td>{$contact['is_responded'] ? 'Yes' : 'No'}</td>";
        echo "<td>{$contact['created_at']}</td>";
        echo "</tr>";
    }

    echo "</table>";

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
