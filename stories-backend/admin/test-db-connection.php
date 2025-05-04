<?php
/**
 * Test Database Connection for Admin
 * This script tests the database connection for the admin panel
 */

// Set error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Output header
echo "<h1>Admin Database Connection Test</h1>";
echo "<p>This script tests the database connection for the admin panel.</p>";

// Include the database connection
echo "<h2>Testing Database Connection</h2>";
echo "<p>Including db-connect.php...</p>";

include_once 'includes/db-connect.php';

// Check if connection was successful
if (isset($db) && $db) {
    echo "<p style='color:green'>Database connection successful!</p>";
    
    // Check if contacts table exists
    echo "<h2>Checking Contacts Table</h2>";
    try {
        $stmt = $db->query("SHOW TABLES LIKE 'contacts'");
        $tableExists = $stmt->rowCount() > 0;
        
        if ($tableExists) {
            echo "<p style='color:green'>Contacts table exists.</p>";
            
            // Count contacts
            $stmt = $db->query("SELECT COUNT(*) FROM contacts");
            $contactsCount = $stmt->fetchColumn();
            
            echo "<p>Total contacts in database: <strong>{$contactsCount}</strong></p>";
            
            // Show latest contacts
            if ($contactsCount > 0) {
                $stmt = $db->query("SELECT * FROM contacts ORDER BY created_at DESC LIMIT 5");
                $latestContacts = $stmt->fetchAll();
                
                echo "<h3>Latest Contacts</h3>";
                echo "<table border='1' cellpadding='5'>";
                echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Subject</th><th>Message</th><th>Responded</th><th>Created At</th></tr>";
                
                foreach ($latestContacts as $contact) {
                    echo "<tr>";
                    echo "<td>{$contact['id']}</td>";
                    echo "<td>{$contact['name']}</td>";
                    echo "<td>{$contact['email']}</td>";
                    echo "<td>{$contact['subject']}</td>";
                    echo "<td>" . substr($contact['message'], 0, 50) . (strlen($contact['message']) > 50 ? '...' : '') . "</td>";
                    echo "<td>{$contact['is_responded']}</td>";
                    echo "<td>{$contact['created_at']}</td>";
                    echo "</tr>";
                }
                
                echo "</table>";
            } else {
                echo "<p>No contacts found in the database.</p>";
            }
        } else {
            echo "<p style='color:orange'>Contacts table does not exist. Creating it now...</p>";
            
            // Create contacts table
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
        }
    } catch (PDOException $e) {
        echo "<p style='color:red'>Error checking contacts table: " . $e->getMessage() . "</p>";
    }
    
    // Output JSON response for API testing
    $response = [
        'success' => true,
        'message' => 'Database connection successful',
        'table_exists' => $tableExists ?? false,
        'contacts_count' => $contactsCount ?? 0,
        'latest_contacts' => $latestContacts ?? []
    ];
    
    echo "<h2>JSON Response</h2>";
    echo "<pre>" . json_encode($response, JSON_PRETTY_PRINT) . "</pre>";
    
} else {
    echo "<p style='color:red'>Database connection failed!</p>";
    echo "<p>Error: " . ($connectionError ?? 'Unknown error') . "</p>";
    
    // Output JSON response for API testing
    $response = [
        'success' => false,
        'message' => 'Database connection failed',
        'error' => $connectionError ?? 'Unknown error'
    ];
    
    echo "<h2>JSON Response</h2>";
    echo "<pre>" . json_encode($response, JSON_PRETTY_PRINT) . "</pre>";
}
