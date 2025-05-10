<?php
/**
 * Fix Book Directory Links
 * 
 * This script fixes the relationship between books and directory items by:
 * 1. Finding books that don't have a directory_item_id
 * 2. Looking for matching directory items by title
 * 3. Updating the books with the correct directory_item_id
 * 4. Ensuring directory items have the correct type set to 'book'
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
    // Start transaction
    $db->beginTransaction();

    // Get all books without directory_item_id
    $booksStmt = $db->query("SELECT id, title FROM books WHERE directory_item_id IS NULL OR directory_item_id = 0");
    $books = $booksStmt->fetchAll();

    logMessage("Found " . count($books) . " books without directory item links.");

    $matchedCount = 0;
    $notFoundCount = 0;

    foreach ($books as $book) {
        // Look for a directory item with the same title
        $stmt = $db->prepare("SELECT id, type FROM directory_items WHERE title = ?");
        $stmt->execute([$book['title']]);
        $directoryItem = $stmt->fetch();

        if ($directoryItem) {
            // Update the book with the directory_item_id
            $updateStmt = $db->prepare("UPDATE books SET directory_item_id = ? WHERE id = ?");
            $updateStmt->execute([$directoryItem['id'], $book['id']]);

            // If the directory item type is not 'book', update it
            if ($directoryItem['type'] !== 'book') {
                $updateTypeStmt = $db->prepare("UPDATE directory_items SET type = 'book' WHERE id = ?");
                $updateTypeStmt->execute([$directoryItem['id']]);
                logMessage("Updated directory item #{$directoryItem['id']} type to 'book'");
            }

            logMessage("Linked book #{$book['id']} '{$book['title']}' to directory item #{$directoryItem['id']}");
            $matchedCount++;
        } else {
            // No matching directory item found
            logMessage("No matching directory item found for book #{$book['id']} '{$book['title']}'");
            $notFoundCount++;
        }
    }

    // Get all directory items of type 'book' without a corresponding book entry
    $dirItemsStmt = $db->query("
        SELECT di.id, di.title 
        FROM directory_items di
        LEFT JOIN books b ON di.id = b.directory_item_id
        WHERE di.type = 'book' AND b.id IS NULL
    ");
    $orphanedDirItems = $dirItemsStmt->fetchAll();

    logMessage("Found " . count($orphanedDirItems) . " directory items of type 'book' without book entries.");

    $createdBookCount = 0;

    foreach ($orphanedDirItems as $dirItem) {
        // Look for a book with the same title
        $stmt = $db->prepare("SELECT id FROM books WHERE title = ?");
        $stmt->execute([$dirItem['title']]);
        $existingBook = $stmt->fetch();

        if ($existingBook) {
            // Update the existing book with the directory_item_id
            $updateStmt = $db->prepare("UPDATE books SET directory_item_id = ? WHERE id = ?");
            $updateStmt->execute([$dirItem['id'], $existingBook['id']]);
            logMessage("Linked existing book #{$existingBook['id']} to directory item #{$dirItem['id']} '{$dirItem['title']}'");
        } else {
            // Create a new book entry for this directory item
            $insertStmt = $db->prepare("
                INSERT INTO books (directory_item_id, title, cover_image_url)
                SELECT id, title, cover_url FROM directory_items WHERE id = ?
            ");
            $insertStmt->execute([$dirItem['id']]);
            logMessage("Created new book entry for directory item #{$dirItem['id']} '{$dirItem['title']}'");
            $createdBookCount++;
        }
    }

    // Commit transaction
    $db->commit();

    logMessage("=== Summary ===");
    logMessage("Total books without directory item links: " . count($books));
    logMessage("Books linked to directory items: $matchedCount");
    logMessage("Books without matching directory items: $notFoundCount");
    logMessage("Directory items of type 'book' without book entries: " . count($orphanedDirItems));
    logMessage("New book entries created: $createdBookCount");
    logMessage("Script completed successfully.");

} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($db)) {
        $db->rollBack();
    }

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
    <title>Fix Book Directory Links</title>
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
        <h1>Fix Book Directory Links</h1>
        <div class="alert alert-info">
            <p>This script fixes the relationship between books and directory items.</p>
        </div>
        <div class="results">
            <h2>Results:</h2>
            <pre><?php echo ob_get_clean(); ?></pre>
        </div>
        <p><a href="../content/directory-items.php">Return to Directory Items</a></p>
    </div>
</body>
</html>
