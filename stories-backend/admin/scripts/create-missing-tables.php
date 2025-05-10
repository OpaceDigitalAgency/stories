<?php
/**
 * Create Missing Tables Script
 * 
 * This script checks for missing tables and creates them if they don't exist.
 */

// Include database connection
require_once '../includes/db-connect.php';

// Set execution time limit to 5 minutes
set_time_limit(300);

// Start output buffering
ob_start();

// Function to output messages
function output($message, $isHtml = false) {
    if ($isHtml) {
        echo $message . "<br>\n";
    } else {
        echo $message . "\n";
    }
    ob_flush();
    flush();
}

// Check if running in web or CLI
$isWeb = php_sapi_name() !== 'cli';

// Header for web output
if ($isWeb) {
    echo '<!DOCTYPE html>
<html>
<head>
    <title>Create Missing Tables</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 20px;
        }
        h1, h2 {
            color: #333;
        }
        .success {
            color: green;
        }
        .error {
            color: red;
        }
        .warning {
            color: orange;
        }
        .info {
            color: blue;
        }
        pre {
            background-color: #f5f5f5;
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <h1>Create Missing Tables</h1>
';
}

output("Starting table check...", $isWeb);
output("", $isWeb);

// Begin transaction
try {
    $db->beginTransaction();
    output("Transaction started", $isWeb);
} catch (PDOException $e) {
    output("Error starting transaction: " . $e->getMessage(), $isWeb);
    exit;
}

// 1. Check for directory_item_tags table
output("=== Checking for directory_item_tags table ===", $isWeb);
try {
    $stmt = $db->query("SHOW TABLES LIKE 'directory_item_tags'");
    if ($stmt->rowCount() == 0) {
        // Create directory_item_tags table
        $db->exec("CREATE TABLE directory_item_tags (
            item_id INT NOT NULL,
            tag_id INT NOT NULL,
            PRIMARY KEY (item_id, tag_id)
        )");
        output("Created directory_item_tags table", $isWeb);
        
        // Check if item_tags table exists and has data
        $stmt = $db->query("SHOW TABLES LIKE 'item_tags'");
        if ($stmt->rowCount() > 0) {
            // Check if item_tags has data
            $stmt = $db->query("SELECT COUNT(*) FROM item_tags WHERE item_type = 'directory_item'");
            $count = $stmt->fetchColumn();
            
            if ($count > 0) {
                // Migrate data from item_tags to directory_item_tags
                $db->exec("INSERT INTO directory_item_tags (item_id, tag_id)
                          SELECT item_id, tag_id FROM item_tags WHERE item_type = 'directory_item'");
                output("Migrated $count records from item_tags to directory_item_tags", $isWeb);
            } else {
                output("No directory item tags found in item_tags table", $isWeb);
            }
        }
    } else {
        output("directory_item_tags table already exists", $isWeb);
    }
} catch (PDOException $e) {
    output("Error checking/creating directory_item_tags table: " . $e->getMessage(), $isWeb);
    $db->rollBack();
    exit;
}

// 2. Check for publishers table
output("", $isWeb);
output("=== Checking for publishers table ===", $isWeb);
try {
    $stmt = $db->query("SHOW TABLES LIKE 'publishers'");
    if ($stmt->rowCount() == 0) {
        // Create publishers table
        $db->exec("CREATE TABLE publishers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL,
            website VARCHAR(255),
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY (slug)
        )");
        output("Created publishers table", $isWeb);
        
        // Migrate publisher data from books table
        $stmt = $db->query("SELECT DISTINCT publisher FROM books WHERE publisher IS NOT NULL AND publisher != ''");
        $publishers = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (count($publishers) > 0) {
            output("Found " . count($publishers) . " unique publishers to migrate", $isWeb);
            
            // Insert each publisher into the publishers table
            $insertStmt = $db->prepare("INSERT IGNORE INTO publishers (name, slug) VALUES (?, ?)");
            $count = 0;
            
            foreach ($publishers as $publisher) {
                // Generate slug
                $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $publisher));
                $slug = trim($slug, '-');
                
                $insertStmt->execute([$publisher, $slug]);
                if ($insertStmt->rowCount() > 0) {
                    $count++;
                }
            }
            
            output("Migrated $count publishers to the publishers table", $isWeb);
        } else {
            output("No publishers found to migrate", $isWeb);
        }
    } else {
        output("publishers table already exists", $isWeb);
    }
} catch (PDOException $e) {
    output("Error checking/creating publishers table: " . $e->getMessage(), $isWeb);
    $db->rollBack();
    exit;
}

// 3. Check for publisher_id column in books table
output("", $isWeb);
output("=== Checking for publisher_id column in books table ===", $isWeb);
try {
    $stmt = $db->query("SHOW COLUMNS FROM books LIKE 'publisher_id'");
    if ($stmt->rowCount() == 0) {
        // Add publisher_id column
        $db->exec("ALTER TABLE books ADD COLUMN publisher_id INT");
        output("Added publisher_id column to books table", $isWeb);
        
        // Update publisher_id based on publisher name
        $updateStmt = $db->prepare("
            UPDATE books b
            JOIN publishers p ON b.publisher = p.name
            SET b.publisher_id = p.id
            WHERE b.publisher IS NOT NULL AND b.publisher != ''
        ");
        $updateStmt->execute();
        output("Updated publisher_id for " . $updateStmt->rowCount() . " books", $isWeb);
    } else {
        output("publisher_id column already exists in books table", $isWeb);
    }
} catch (PDOException $e) {
    output("Error checking/adding publisher_id column: " . $e->getMessage(), $isWeb);
    $db->rollBack();
    exit;
}

// Commit transaction
try {
    $db->commit();
    output("", $isWeb);
    output("Transaction committed successfully", $isWeb);
} catch (PDOException $e) {
    output("Error committing transaction: " . $e->getMessage(), $isWeb);
    $db->rollBack();
    exit;
}

output("", $isWeb);
output("Table check completed successfully!", $isWeb);

// Footer for web output
if ($isWeb) {
    echo '
</body>
</html>';
}
?>
