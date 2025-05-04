<?php
/**
 * Database connection and contacts table check
 * This script checks the database connection and the contacts table
 */

// Allow cross-origin requests
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');

// Connect to database
try {
    $db = new PDO(
        'mysql:host=localhost;dbname=stories_db;charset=utf8mb4',
        'stories_user',
        '$tw1cac3*sOt',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    // Check if contacts table exists
    $stmt = $db->query("SHOW TABLES LIKE 'contacts'");
    $tableExists = $stmt->rowCount() > 0;

    if (!$tableExists) {
        // Create the contacts table
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
        
        echo json_encode([
            'success' => true,
            'message' => 'Contacts table created successfully',
            'table_exists' => true,
            'contacts_count' => 0
        ]);
    } else {
        // Count contacts
        $stmt = $db->query("SELECT COUNT(*) FROM contacts");
        $contactsCount = $stmt->fetchColumn();

        // Get the latest 5 contacts
        $stmt = $db->query("SELECT * FROM contacts ORDER BY created_at DESC LIMIT 5");
        $latestContacts = $stmt->fetchAll();

        echo json_encode([
            'success' => true,
            'message' => 'Database connection successful',
            'table_exists' => true,
            'contacts_count' => $contactsCount,
            'latest_contacts' => $latestContacts
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
