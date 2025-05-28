<?php
/**
 * Test script to verify publisher relationship updates are working
 */

// Set content type to HTML
header('Content-Type: text/html; charset=utf-8');

// Include database connection
require_once '../includes/db-connect.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Publisher Relationship Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .debug-output {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            padding: 1rem;
            margin: 1rem 0;
            font-family: 'Courier New', monospace;
            white-space: pre-wrap;
            max-height: 400px;
            overflow-y: auto;
        }
        .success { color: #198754; }
        .error { color: #dc3545; }
        .info { color: #0d6efd; }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h1>🔧 Publisher Relationship Test</h1>
        <p class="text-muted">Testing if publisher relationships are updating correctly.</p>

        <?php
        // Test book ID (Demon Dentist)
        $testBookId = 2105;

        echo '<div class="card mb-4">';
        echo '<div class="card-header"><h3>Current State for Book ID: ' . $testBookId . '</h3></div>';
        echo '<div class="card-body">';

        try {
            // Get current book data
            $stmt = $db->prepare("
                SELECT b.*, di.title, a.name as publisher_name, a.id as publisher_author_id
                FROM books b
                JOIN directory_items di ON b.directory_item_id = di.id
                LEFT JOIN authors a ON b.publisher_id = a.id
                WHERE b.directory_item_id = ?
            ");
            $stmt->execute([$testBookId]);
            $book = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($book) {
                echo '<div class="debug-output">';
                echo "Book Title: {$book['title']}\n";
                echo "Publisher Field: " . ($book['publisher'] ?: 'NULL') . "\n";
                echo "Publisher ID: " . ($book['publisher_id'] ?: 'NULL') . "\n";
                echo "Publisher Name (from authors table): " . ($book['publisher_name'] ?: 'NULL') . "\n";
                echo "Publisher Author ID: " . ($book['publisher_author_id'] ?: 'NULL') . "\n";
                echo '</div>';

                // Check for publisher duplicates - first check which column exists
                echo '<h4>Publisher Duplicates Check</h4>';

                // Check if 'type' or 'author_type' column exists
                $hasTypeColumn = false;
                $hasAuthorTypeColumn = false;

                try {
                    $stmt = $db->query("SHOW COLUMNS FROM authors LIKE 'type'");
                    $hasTypeColumn = $stmt->fetch() !== false;
                } catch (Exception $e) {}

                try {
                    $stmt = $db->query("SHOW COLUMNS FROM authors LIKE 'author_type'");
                    $hasAuthorTypeColumn = $stmt->fetch() !== false;
                } catch (Exception $e) {}

                echo '<div class="debug-output">';
                echo "Authors table columns:\n";
                echo "- 'type' column exists: " . ($hasTypeColumn ? 'YES' : 'NO') . "\n";
                echo "- 'author_type' column exists: " . ($hasAuthorTypeColumn ? 'YES' : 'NO') . "\n";
                echo '</div>';

                // Query based on available columns
                if ($hasTypeColumn) {
                    $stmt = $db->prepare("SELECT id, name FROM authors WHERE type = 'publisher' AND name LIKE '%Harper%' ORDER BY name");
                } elseif ($hasAuthorTypeColumn) {
                    $stmt = $db->prepare("SELECT id, name FROM authors WHERE author_type = 'publisher' AND name LIKE '%Harper%' ORDER BY name");
                } else {
                    // No type column - just get all authors with Harper in name
                    $stmt = $db->prepare("SELECT id, name FROM authors WHERE name LIKE '%Harper%' ORDER BY name");
                }
                $stmt->execute();
                $publishers = $stmt->fetchAll(PDO::FETCH_ASSOC);

                echo '<div class="debug-output">';
                echo "Publishers containing 'Harper':\n";
                foreach ($publishers as $pub) {
                    echo "ID: {$pub['id']} - Name: {$pub['name']}\n";
                }
                echo '</div>';

            } else {
                echo '<div class="alert alert-warning">Book not found</div>';
            }

        } catch (Exception $e) {
            echo '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
        }

        echo '</div></div>';

        // Test the publisher processing function
        echo '<div class="card mb-4">';
        echo '<div class="card-header"><h3>Test Publisher Processing</h3></div>';
        echo '<div class="card-body">';

        // Include the enrichment functions
        require_once 'book-import-validate/ajax/data-enrichment-ajax.php';

        if (function_exists('processPublisherRelationship')) {
            echo '<div class="alert alert-info">Testing publisher relationship processing...</div>';

            // Test with "Harper Collins" to see if it matches existing
            $testPublisher = "Harper Collins";
            echo '<div class="debug-output">';
            echo "Testing publisher: $testPublisher\n\n";

            // Capture any error logs
            ob_start();
            $publisherId = processPublisherRelationship($testBookId, $testPublisher);
            $output = ob_get_clean();

            echo "Function output: $output\n";
            echo "Returned Publisher ID: " . ($publisherId ?: 'NULL') . "\n";
            echo '</div>';

            // Check the result
            if ($publisherId) {
                $stmt = $db->prepare("SELECT name FROM authors WHERE id = ?");
                $stmt->execute([$publisherId]);
                $publisherName = $stmt->fetchColumn();

                echo '<div class="alert alert-success">✅ Publisher relationship processed successfully</div>';
                echo '<div class="debug-output">Publisher ID: ' . $publisherId . '\nPublisher Name: ' . $publisherName . '</div>';

                // Check if book was updated
                $stmt = $db->prepare("SELECT publisher_id FROM books WHERE directory_item_id = ?");
                $stmt->execute([$testBookId]);
                $currentPublisherId = $stmt->fetchColumn();

                echo '<div class="debug-output">Book publisher_id after processing: ' . ($currentPublisherId ?: 'NULL') . '</div>';

            } else {
                echo '<div class="alert alert-danger">❌ Publisher relationship processing failed</div>';
            }

        } else {
            echo '<div class="alert alert-warning">processPublisherRelationship function not found</div>';
        }

        echo '</div></div>';
        ?>

        <div class="card">
            <div class="card-header">
                <h3>🎯 Next Steps</h3>
            </div>
            <div class="card-body">
                <p>This test shows the current state of publisher relationships and tests the processing function.</p>
                <p>If the publisher_id is not updating correctly, we need to debug the enrichment integration.</p>

                <p class="mt-3">
                    <a href="book-validation.php" class="btn btn-primary">← Back to Book Validation</a>
                    <a href="directory-item-form.php?id=<?php echo $testBookId; ?>" class="btn btn-secondary">View Book Form</a>
                    <button onclick="location.reload()" class="btn btn-info">🔄 Refresh Test</button>
                </p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
