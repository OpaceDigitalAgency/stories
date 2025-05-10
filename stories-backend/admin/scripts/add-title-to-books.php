<?php
/**
 * Add Title Column to Books Table
 *
 * This script adds a title column to the books table and populates it with
 * the title from the corresponding directory item.
 */

// Include database connection
require_once '../includes/db-connect.php';

// Set execution time limit to 5 minutes
set_time_limit(300);

// Start output buffering
ob_start();

// Function to log messages
function logMessage($message) {
    echo $message . "<br>";
    flush();
    ob_flush();
}

try {
    // Check if title column exists
    $stmt = $db->query("SHOW COLUMNS FROM books LIKE 'title'");
    $titleColumnExists = $stmt->rowCount() > 0;

    if (!$titleColumnExists) {
        // Start transaction for ALTER TABLE
        $db->beginTransaction();

        try {
            // Add title column to books table
            $db->exec("ALTER TABLE books ADD COLUMN title VARCHAR(255) AFTER directory_item_id");
            // Commit the ALTER TABLE transaction
            $db->commit();
            logMessage("Added title column to books table");
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            logMessage("Error adding title column: " . $e->getMessage());
            throw $e; // Re-throw to be caught by outer try-catch
        }

        // Start a new transaction for the UPDATE
        $db->beginTransaction();

        try {
            // Update title column with values from directory_items
            $updateStmt = $db->prepare("
                UPDATE books b
                JOIN directory_items di ON b.directory_item_id = di.id
                SET b.title = di.title
                WHERE b.directory_item_id IS NOT NULL
            ");
            $updateStmt->execute();
            $updatedCount = $updateStmt->rowCount();

            // Commit the UPDATE transaction
            $db->commit();
            logMessage("Updated $updatedCount book titles from directory items");
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            logMessage("Error updating book titles: " . $e->getMessage());
            throw $e; // Re-throw to be caught by outer try-catch
        }
    } else {
        logMessage("Title column already exists in books table");
    }

    logMessage("Script completed successfully.");

} catch (Exception $e) {
    logMessage("Error: " . $e->getMessage());
}

// End output buffering
ob_end_flush();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Title Column to Books Table</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 20px;
        }
        h1 {
            color: #333;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }
        .alert-info {
            color: #31708f;
            background-color: #d9edf7;
            border-color: #bce8f1;
        }
        .alert-success {
            color: #3c763d;
            background-color: #dff0d8;
            border-color: #d6e9c6;
        }
        .alert-warning {
            color: #8a6d3b;
            background-color: #fcf8e3;
            border-color: #faebcc;
        }
        .alert-danger {
            color: #a94442;
            background-color: #f2dede;
            border-color: #ebccd1;
        }
        pre {
            background-color: #f5f5f5;
            padding: 10px;
            border-radius: 4px;
            overflow: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Add Title Column to Books Table</h1>
        <div class="alert alert-info">
            <p>This script adds a title column to the books table and populates it with the title from the corresponding directory item.</p>
        </div>
        <div class="results">
            <h2>Results:</h2>
            <pre><?php echo ob_get_clean(); ?></pre>
        </div>
        <p><a href="../content/directory-items.php">Return to Directory Items</a></p>
    </div>
</body>
</html>
