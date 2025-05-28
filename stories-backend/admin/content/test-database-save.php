<?php
/**
 * Test script to verify database saves are working
 * This script tests the actual database update functionality
 */

// Include database connection
require_once '../includes/db-connect.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Save Test</title>
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
        <h1>🔧 Database Save Test</h1>
        <p class="text-muted">This script tests if database updates are actually working.</p>

        <?php
        // Test 1: Get a sample book
        echo '<div class="card mb-4">';
        echo '<div class="card-header"><h3>Test 1: Get Sample Book</h3></div>';
        echo '<div class="card-body">';

        try {
            $stmt = $db->query("SELECT * FROM books LIMIT 1");
            $sampleBook = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($sampleBook) {
                echo '<div class="alert alert-success">✅ Sample book found</div>';
                echo '<div class="debug-output">';
                echo "Book ID: {$sampleBook['directory_item_id']}\n";
                echo "Current Author: " . ($sampleBook['author'] ?: 'NULL') . "\n";
                echo "Current Publisher: " . ($sampleBook['publisher'] ?: 'NULL') . "\n";
                echo "Current ISBN: " . ($sampleBook['isbn'] ?: 'NULL') . "\n";
                echo "Current ISBN13: " . ($sampleBook['isbn13'] ?: 'NULL') . "\n";
                echo '</div>';

                $testBookId = $sampleBook['directory_item_id'];
                $originalAuthor = $sampleBook['author'];
                $originalPublisher = $sampleBook['publisher'];

            } else {
                echo '<div class="alert alert-warning">⚠️ No books found in database</div>';
                $testBookId = null;
            }

        } catch (Exception $e) {
            echo '<div class="alert alert-danger">❌ Failed to get sample book</div>';
            echo '<div class="debug-output error">Error: ' . $e->getMessage() . '</div>';
            $testBookId = null;
        }

        echo '</div></div>';

        // Test 2: Test simple update
        if ($testBookId) {
            echo '<div class="card mb-4">';
            echo '<div class="card-header"><h3>Test 2: Test Simple Update</h3></div>';
            echo '<div class="card-body">';

            $testValue = "TEST_AUTHOR_" . date('His');

            try {
                // Update the author field
                $stmt = $db->prepare("UPDATE books SET author = ? WHERE directory_item_id = ?");
                $result = $stmt->execute([$testValue, $testBookId]);
                $affectedRows = $stmt->rowCount();

                echo '<div class="debug-output">';
                echo "Update SQL executed: " . ($result ? 'SUCCESS' : 'FAILED') . "\n";
                echo "Affected rows: $affectedRows\n";
                echo "Test value used: $testValue\n";
                echo '</div>';

                if ($result && $affectedRows > 0) {
                    echo '<div class="alert alert-success">✅ Update executed successfully</div>';

                    // Verify the update
                    $stmt = $db->prepare("SELECT author FROM books WHERE directory_item_id = ?");
                    $stmt->execute([$testBookId]);
                    $newAuthor = $stmt->fetchColumn();

                    echo '<div class="debug-output">';
                    echo "Verification check:\n";
                    echo "Expected: $testValue\n";
                    echo "Actual: $newAuthor\n";
                    echo "Match: " . ($newAuthor === $testValue ? 'YES' : 'NO') . "\n";
                    echo '</div>';

                    if ($newAuthor === $testValue) {
                        echo '<div class="alert alert-success">✅ Database update verified - data was actually saved!</div>';

                        // Restore original value
                        $stmt = $db->prepare("UPDATE books SET author = ? WHERE directory_item_id = ?");
                        $stmt->execute([$originalAuthor, $testBookId]);
                        echo '<div class="alert alert-info">ℹ️ Original author value restored</div>';

                    } else {
                        echo '<div class="alert alert-danger">❌ Database update failed - data was not saved!</div>';
                    }

                } else {
                    echo '<div class="alert alert-danger">❌ Update failed or no rows affected</div>';
                }

            } catch (Exception $e) {
                echo '<div class="alert alert-danger">❌ Update failed with exception</div>';
                echo '<div class="debug-output error">Error: ' . $e->getMessage() . '</div>';
            }

            echo '</div></div>';
        }

        // Test 3: Test column existence function
        echo '<div class="card mb-4">';
        echo '<div class="card-header"><h3>Test 3: Test Column Existence Function</h3></div>';
        echo '<div class="card-body">';

        // Define the FIXED columnExists function for testing
        function columnExists($table, $column) {
            global $db;
            try {
                // MySQL doesn't support parameter binding for SHOW COLUMNS LIKE
                // So we need to escape the values manually and use direct query
                $table = $db->quote($table);
                $column = $db->quote($column);

                $sql = "SHOW COLUMNS FROM $table LIKE $column";
                $stmt = $db->query($sql);
                $result = $stmt->fetch();
                echo "<div class='debug-output'>DEBUG columnExists (FIXED VERSION):\n";
                echo "SQL: $sql\n";
                echo "Result: " . json_encode($result) . "\n";
                echo "Return value: " . ($result !== false ? 'TRUE' : 'FALSE') . "\n</div>";
                return $result !== false;
            } catch (Exception $e) {
                echo "<div class='debug-output error'>Error in columnExists: " . $e->getMessage() . "</div>";
                error_log("Error checking column existence: " . $e->getMessage());
                return false;
            }
        }

        // Also test direct SHOW COLUMNS query
        echo '<div class="debug-output">';
        echo "Direct SHOW COLUMNS test:\n";
        try {
            $stmt = $db->query("SHOW COLUMNS FROM books");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "All columns in books table:\n";
            foreach ($columns as $col) {
                echo "- {$col['Field']} ({$col['Type']})\n";
            }
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }
        echo '</div>';

        $testColumns = ['author', 'publisher', 'isbn', 'isbn13', 'title', 'nonexistent_field'];

        echo '<div class="debug-output">';
        echo "Testing columnExists function:\n";
        foreach ($testColumns as $column) {
            $exists = columnExists('books', $column);
            $status = $exists ? '✅ EXISTS' : '❌ MISSING';
            echo "- books.$column: $status\n";
        }
        echo '</div>';

        echo '</div></div>';

        // Test 4: Test the exact enrichment update process
        if ($testBookId) {
            echo '<div class="card mb-4">';
            echo '<div class="card-header"><h3>Test 4: Test Enrichment-Style Update</h3></div>';
            echo '<div class="card-body">';

            // Simulate the exact process from handleApplyEnrichment
            $testFields = [
                'author' => ['value' => 'TEST_ENRICHMENT_AUTHOR_' . date('His')],
                'publisher' => ['value' => 'TEST_ENRICHMENT_PUBLISHER_' . date('His')]
            ];

            echo '<div class="debug-output">';
            echo "Simulating enrichment update process:\n";
            echo "Test fields: " . json_encode($testFields) . "\n\n";
            echo '</div>';

            try {
                // Build update query exactly like in handleApplyEnrichment
                $updateFields = [];
                $params = [];

                foreach ($testFields as $fieldName => $fieldData) {
                    $value = $fieldData['value'];

                    echo '<div class="debug-output">';
                    echo "Processing field: $fieldName\n";
                    echo "Value: $value\n";

                    $columnExistsResult = columnExists('books', $fieldName);
                    echo "Column exists: " . ($columnExistsResult ? 'YES' : 'NO') . "\n";

                    if (!empty($value) && $columnExistsResult) {
                        $updateFields[] = "$fieldName = ?";
                        $params[] = $value;
                        echo "Added to update: YES\n";
                    } else {
                        echo "Added to update: NO\n";
                    }
                    echo "\n";
                    echo '</div>';
                }

                if (!empty($updateFields)) {
                    // Add validation status update
                    $updateFields[] = "validation_status = 'partial'";
                    $updateFields[] = "last_validated = NOW()";

                    // Add book ID parameter
                    $params[] = $testBookId;

                    // Execute update
                    $sql = "UPDATE books SET " . implode(', ', $updateFields) . " WHERE directory_item_id = ?";

                    echo '<div class="debug-output">';
                    echo "Final SQL: $sql\n";
                    echo "Final params: " . json_encode($params) . "\n";
                    echo '</div>';

                    $stmt = $db->prepare($sql);
                    $result = $stmt->execute($params);
                    $affectedRows = $stmt->rowCount();

                    echo '<div class="debug-output">';
                    echo "SQL execution result: " . ($result ? 'SUCCESS' : 'FAILED') . "\n";
                    echo "Affected rows: $affectedRows\n";
                    echo '</div>';

                    if ($result && $affectedRows > 0) {
                        echo '<div class="alert alert-success">✅ Enrichment-style update successful!</div>';

                        // Verify the changes
                        $stmt = $db->prepare("SELECT author, publisher FROM books WHERE directory_item_id = ?");
                        $stmt->execute([$testBookId]);
                        $updated = $stmt->fetch(PDO::FETCH_ASSOC);

                        echo '<div class="debug-output">';
                        echo "Verification:\n";
                        echo "Author - Expected: {$testFields['author']['value']}, Actual: {$updated['author']}\n";
                        echo "Publisher - Expected: {$testFields['publisher']['value']}, Actual: {$updated['publisher']}\n";
                        echo '</div>';

                        // Restore original values
                        $stmt = $db->prepare("UPDATE books SET author = ?, publisher = ? WHERE directory_item_id = ?");
                        $stmt->execute([$originalAuthor, $originalPublisher, $testBookId]);
                        echo '<div class="alert alert-info">ℹ️ Original values restored</div>';

                    } else {
                        echo '<div class="alert alert-danger">❌ Enrichment-style update failed</div>';
                    }

                } else {
                    echo '<div class="alert alert-warning">⚠️ No fields to update</div>';
                }

            } catch (Exception $e) {
                echo '<div class="alert alert-danger">❌ Enrichment test failed</div>';
                echo '<div class="debug-output error">Error: ' . $e->getMessage() . '</div>';
            }

            echo '</div></div>';
        }
        ?>

        <div class="card">
            <div class="card-header">
                <h3>🎯 Results Summary</h3>
            </div>
            <div class="card-body">
                <p>This test verifies whether database updates are actually working. If you see success messages above, the database save functionality is working correctly.</p>
                <p>If the enrichment modal shows success but data isn't being saved, the issue might be:</p>
                <ul>
                    <li><strong>JavaScript errors</strong> - Check browser console for errors</li>
                    <li><strong>AJAX request issues</strong> - Check network tab in browser dev tools</li>
                    <li><strong>Field validation</strong> - Some fields might be filtered out during processing</li>
                    <li><strong>Cache issues</strong> - Browser or server-side caching might show old data</li>
                </ul>

                <p class="mt-3">
                    <a href="book-validation.php" class="btn btn-primary">← Back to Book Validation</a>
                    <button onclick="location.reload()" class="btn btn-secondary">🔄 Refresh Test</button>
                </p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
