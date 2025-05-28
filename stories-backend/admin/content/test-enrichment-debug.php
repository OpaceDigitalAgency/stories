<?php
/**
 * Test script for debugging data enrichment save issues
 * This script simulates the enrichment process and shows detailed debugging information
 */

// Include database connection
require_once '../includes/db-connect.php';

// Include the enrichment functions - check if file exists first
$enrichmentFile = 'book-import-validate/ajax/data-enrichment-ajax.php';
if (file_exists($enrichmentFile)) {
    require_once $enrichmentFile;
} else {
    echo '<div class="alert alert-danger">Enrichment file not found: ' . $enrichmentFile . '</div>';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Enrichment Debug Test</title>
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
        <h1>🔧 Data Enrichment Debug Test</h1>
        <p class="text-muted">This script tests the data enrichment save functionality and shows detailed debugging information.</p>

        <?php
        // Test 1: Check database connection
        echo '<div class="card mb-4">';
        echo '<div class="card-header"><h3>Test 1: Database Connection</h3></div>';
        echo '<div class="card-body">';

        try {
            $stmt = $db->query("SELECT COUNT(*) as count FROM books LIMIT 1");
            $result = $stmt->fetch();
            echo '<div class="alert alert-success">✅ Database connection successful</div>';
            echo '<div class="debug-output">Connected to database successfully. Books table accessible.</div>';
        } catch (Exception $e) {
            echo '<div class="alert alert-danger">❌ Database connection failed</div>';
            echo '<div class="debug-output error">Error: ' . $e->getMessage() . '</div>';
        }

        echo '</div></div>';

        // Test 2: Check books table structure
        echo '<div class="card mb-4">';
        echo '<div class="card-header"><h3>Test 2: Books Table Structure</h3></div>';
        echo '<div class="card-body">';

        try {
            $stmt = $db->query("SHOW COLUMNS FROM books");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo '<div class="alert alert-success">✅ Books table structure retrieved</div>';
            echo '<div class="debug-output">';
            echo "Books table columns:\n";
            foreach ($columns as $column) {
                echo "- {$column['Field']} ({$column['Type']})\n";
            }
            echo '</div>';

            // Check for specific fields that enrichment system uses
            $requiredFields = ['directory_item_id', 'title', 'author', 'publisher', 'isbn', 'isbn13',
                              'publication_date', 'page_count', 'language', 'format', 'cover_url',
                              'alternative_isbns', 'awards', 'characters', 'settings'];

            $existingFields = array_column($columns, 'Field');
            $missingFields = array_diff($requiredFields, $existingFields);

            if (empty($missingFields)) {
                echo '<div class="alert alert-success">✅ All required enrichment fields exist</div>';
            } else {
                echo '<div class="alert alert-warning">⚠️ Some enrichment fields are missing</div>';
                echo '<div class="debug-output error">Missing fields: ' . implode(', ', $missingFields) . '</div>';
            }

        } catch (Exception $e) {
            echo '<div class="alert alert-danger">❌ Failed to check table structure</div>';
            echo '<div class="debug-output error">Error: ' . $e->getMessage() . '</div>';
        }

        echo '</div></div>';

        // Test 3: Get a sample book for testing
        echo '<div class="card mb-4">';
        echo '<div class="card-header"><h3>Test 3: Sample Book Data</h3></div>';
        echo '<div class="card-body">';

        try {
            $stmt = $db->query("SELECT * FROM books LIMIT 1");
            $sampleBook = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($sampleBook) {
                echo '<div class="alert alert-success">✅ Sample book found</div>';
                echo '<div class="debug-output">';
                echo "Sample book data:\n";
                echo "ID: {$sampleBook['directory_item_id']}\n";
                echo "Title: " . ($sampleBook['title'] ?: 'NULL') . "\n";
                echo "Author: " . ($sampleBook['author'] ?: 'NULL') . "\n";
                echo "ISBN: " . ($sampleBook['isbn'] ?: 'NULL') . "\n";
                echo "ISBN13: " . ($sampleBook['isbn13'] ?: 'NULL') . "\n";
                echo '</div>';

                // Store sample book ID for testing
                $testBookId = $sampleBook['directory_item_id'];

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

        // Test 4: Test columnExists function
        echo '<div class="card mb-4">';
        echo '<div class="card-header"><h3>Test 4: Column Existence Check</h3></div>';
        echo '<div class="card-body">';

        $testColumns = ['author', 'publisher', 'isbn', 'isbn13', 'alternative_isbns', 'nonexistent_field'];

        echo '<div class="debug-output">';
        echo "Testing columnExists function:\n";
        foreach ($testColumns as $column) {
            $exists = columnExists('books', $column);
            $status = $exists ? '✅ EXISTS' : '❌ MISSING';
            echo "- {$column}: {$status}\n";
        }
        echo '</div>';

        echo '</div></div>';

        // Test 5: Check enrichment file and functions
        echo '<div class="card mb-4">';
        echo '<div class="card-header"><h3>Test 5: Check Enrichment Functions</h3></div>';
        echo '<div class="card-body">';

        echo '<div class="debug-output">';
        echo "Checking enrichment file and functions:\n";
        echo "File exists: " . (file_exists($enrichmentFile) ? 'YES' : 'NO') . "\n";
        echo "handleApplyEnrichment function exists: " . (function_exists('handleApplyEnrichment') ? 'YES' : 'NO') . "\n";
        echo "columnExists function exists: " . (function_exists('columnExists') ? 'YES' : 'NO') . "\n";
        echo '</div>';

        if (function_exists('handleApplyEnrichment') && $testBookId) {
            // Create test enrichment data
            $testFields = [
                'author' => [
                    'value' => 'Test Author Updated',
                    'source' => 'test',
                    'confidence' => 100
                ],
                'publisher' => [
                    'value' => 'Test Publisher Updated',
                    'source' => 'test',
                    'confidence' => 100
                ]
            ];

            echo '<div class="debug-output">';
            echo "Test enrichment data:\n";
            echo json_encode($testFields, JSON_PRETTY_PRINT) . "\n";
            echo '</div>';

            // Simulate the POST data
            $_POST['action'] = 'apply_enrichment';
            $_POST['book_id'] = $testBookId;
            $_POST['fields'] = json_encode($testFields);

            echo '<div class="alert alert-info">🔧 Simulating enrichment save...</div>';

            try {
                // Capture output
                ob_start();
                handleApplyEnrichment();
                $output = ob_get_clean();

                echo '<div class="debug-output">';
                echo "Enrichment function output:\n";
                echo $output;
                echo '</div>';

                // Try to decode the JSON response
                $response = json_decode($output, true);
                if ($response) {
                    if ($response['success']) {
                        echo '<div class="alert alert-success">✅ Enrichment save test successful!</div>';
                    } else {
                        echo '<div class="alert alert-danger">❌ Enrichment save test failed</div>';
                        echo '<div class="debug-output error">Error message: ' . ($response['message'] ?? 'Unknown error') . '</div>';
                    }
                } else {
                    echo '<div class="alert alert-warning">⚠️ Could not parse response as JSON</div>';
                }
            } catch (Exception $e) {
                echo '<div class="alert alert-danger">❌ Exception during enrichment test</div>';
                echo '<div class="debug-output error">Exception: ' . $e->getMessage() . '</div>';
            }
        } else {
            echo '<div class="alert alert-warning">⚠️ Cannot run enrichment test - missing functions or book ID</div>';
        }

        echo '</div></div>';
        ?>

        <div class="card">
            <div class="card-header">
                <h3>🎯 Next Steps</h3>
            </div>
            <div class="card-body">
                <p>If you see any errors above, they indicate the specific issue preventing data enrichment saves.</p>
                <ul>
                    <li><strong>Database connection issues:</strong> Check your db-connect.php file</li>
                    <li><strong>Missing table columns:</strong> Run database migration scripts</li>
                    <li><strong>Column existence check failures:</strong> Verify table structure matches documentation</li>
                    <li><strong>Enrichment save failures:</strong> Check the detailed error messages above</li>
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
