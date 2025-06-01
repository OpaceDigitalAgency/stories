<?php
/**
 * Book Check & Compare - Diagnostic Tool
 *
 * This page provides comprehensive diagnostics for book enrichment data from multiple sources.
 * It uses the EXACT same functions as the modal to ensure accurate testing.
 */

// Set page title and current page
$pageTitle = 'Book Check & Compare';
$currentPage = 'book-check-compare';
$pageDescription = 'Comprehensive diagnostic tool for book enrichment data';

// Include the header
require_once '../includes/auth-check.php';
require_once '../includes/header.php';

// Include ALL the same functions the modal uses
require_once 'book-import-validate/functions/data-enrichment-functions.php';
require_once 'book-import-validate/functions/google-books-validation-functions.php';
require_once 'book-import-validate/functions/open-library-validation-functions.php';

/**
 * Test individual APIs with detailed diagnostics
 */
function testIndividualAPIs($isbn) {
    $results = [];

    // Test Google Books API
    try {
        $googleUrl = "https://www.googleapis.com/books/v1/volumes?q=isbn:$isbn";
        $ch = curl_init($googleUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $results['google_books'] = [
            'status' => $httpCode === 200 ? 'success' : 'failed',
            'http_code' => $httpCode,
            'data' => $response ? json_decode($response, true) : null
        ];
    } catch (Exception $e) {
        $results['google_books'] = ['status' => 'error', 'message' => $e->getMessage()];
    }

    // Test OpenLibrary API
    try {
        $olUrl = "https://openlibrary.org/search.json?q=isbn:$isbn&fields=*,availability&limit=1";
        $ch = curl_init($olUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $results['open_library'] = [
            'status' => $httpCode === 200 ? 'success' : 'failed',
            'http_code' => $httpCode,
            'data' => $response ? json_decode($response, true) : null
        ];
    } catch (Exception $e) {
        $results['open_library'] = ['status' => 'error', 'message' => $e->getMessage()];
    }

    // Test Amazon Scraping
    try {
        if (function_exists('scrapeAmazonBuyingOptions')) {
            $amazonData = scrapeAmazonBuyingOptions($isbn);
            $results['amazon'] = [
                'status' => $amazonData && !empty($amazonData) ? 'success' : 'no_data',
                'data' => $amazonData
            ];
        } else {
            $results['amazon'] = ['status' => 'function_missing', 'message' => 'scrapeAmazonBuyingOptions function not available'];
        }
    } catch (Exception $e) {
        $results['amazon'] = ['status' => 'error', 'message' => $e->getMessage()];
    }

    return $results;
}

// Handle form submission
$results = [];
$isbn = '';
$title = '';
$author = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['isbn'])) {
    $isbn = trim($_POST['isbn']);
    $title = trim($_POST['title'] ?? '');
    $author = trim($_POST['author'] ?? '');

    if (empty($isbn)) {
        $results['error'] = 'Please enter an ISBN';
    } else {
        $startTime = microtime(true);
        try {
            // Step 1: Test individual APIs
            $results['api_tests'] = testIndividualAPIs($isbn);

            // Step 2: Call EXACT same AJAX endpoints as modal
            $results['ajax_calls'] = [];

            // Simulate get_enrichment_data AJAX call
            $_POST_backup = $_POST;
            $_POST = [
                'action' => 'get_enrichment_data',
                'title' => $title,
                'author' => $author,
                'current_isbn' => $isbn,
                'book_id' => '1' // Dummy book ID for testing
            ];

            ob_start();
            include 'book-import-validate/ajax/data-enrichment-ajax.php';
            $enrichmentResponse = ob_get_clean();
            $results['ajax_calls']['enrichment'] = json_decode($enrichmentResponse, true);

            // Simulate get_amazon_data AJAX call
            $_POST = [
                'action' => 'get_amazon_data',
                'isbn' => $isbn,
                'book_id' => '1' // Dummy book ID for testing
            ];

            ob_start();
            include 'book-import-validate/ajax/data-enrichment-ajax.php';
            $amazonResponse = ob_get_clean();
            $results['ajax_calls']['amazon'] = json_decode($amazonResponse, true);

            // Restore original $_POST
            $_POST = $_POST_backup;

            $endTime = microtime(true);
            $executionTime = round($endTime - $startTime, 2);

            $results['success'] = true;
            $results['execution_time'] = $executionTime;

            // Use the AJAX response data instead of getEnrichedBookData
            if ($results['ajax_calls']['enrichment']['success']) {
                $results['enriched_data'] = $results['ajax_calls']['enrichment']['data'];
                $results['confidence_score'] = $results['ajax_calls']['enrichment']['data']['confidence_score'] ?? 'N/A';
            } else {
                $results['error'] = 'AJAX enrichment failed: ' . ($results['ajax_calls']['enrichment']['message'] ?? 'Unknown error');
            }

        } catch (Exception $e) {
            $results['error'] = 'Enrichment failed: ' . $e->getMessage();
        }
    }
} else {
    // Set defaults for GET requests or initial load
    $isbn = $_GET['isbn'] ?? '9780007115617'; // Default to correct Chronicles of Narnia ISBN
    $title = $_GET['title'] ?? 'The Lion, the Witch and the Wardrobe';
    $author = $_GET['author'] ?? 'C.S. Lewis';
}

// Add page actions
$pageActions = '
<div class="btn-group">
    <a href="book-import-tool.php" class="btn btn-primary">
        <i class="fas fa-arrow-left"></i> Back to Import Tool
    </a>
    <a href="debug-logs.php" class="btn btn-info">
        <i class="fas fa-file-alt"></i> View Debug Logs
    </a>
</div>';

?>

<div class="row">
    <div class="col-md-12">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Test Book Enrichment</h5>
                <?php echo $pageActions; ?>
            </div>
            <div class="card-body">
                <p>Use this form to test book enrichment data from multiple sources with any ISBN. This will help diagnose issues with the data enrichment process.</p>

                <form method="post" action="" class="mb-4">
                    <div class="form-row">
                        <div class="form-group col-md-3">
                            <label for="isbn">ISBN</label>
                            <input type="text" class="form-control" id="isbn" name="isbn" value="<?php echo htmlspecialchars($isbn); ?>" placeholder="Enter ISBN-13 or ISBN-10" required>
                            <small class="form-text text-muted">Example: 9780007115617 or 000711561X</small>
                        </div>

                        <div class="form-group col-md-3">
                            <label for="title">Expected Title</label>
                            <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($title); ?>" placeholder="Expected book title">
                            <small class="form-text text-muted">For comparison purposes</small>
                        </div>

                        <div class="form-group col-md-3">
                            <label for="author">Expected Author</label>
                            <input type="text" class="form-control" id="author" name="author" value="<?php echo htmlspecialchars($author); ?>" placeholder="Expected author">
                            <small class="form-text text-muted">For comparison purposes</small>
                        </div>

                        <div class="form-group col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Test Enrichment
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Quick Test Buttons -->
                <div class="mb-4">
                    <h6>Quick Tests:</h6>
                    <div class="btn-group btn-group-sm" role="group">
                        <a href="?isbn=9780007115617&title=The Lion, the Witch and the Wardrobe&author=C.S. Lewis"
                           class="btn btn-outline-success">Chronicles of Narnia (Correct)</a>
                        <a href="?isbn=9780007416851&title=The Lion, the Witch and the Wardrobe&author=C.S. Lewis"
                           class="btn btn-outline-danger">Chronicles of Narnia (Wrong ISBN)</a>
                        <a href="?isbn=9780380977789&title=Coraline&author=Neil Gaiman"
                           class="btn btn-outline-info">Coraline</a>
                        <a href="?isbn=9781408855652&title=Harry Potter and the Philosopher's Stone&author=J.K. Rowling"
                           class="btn btn-outline-info">Harry Potter</a>
                    </div>
                </div>

                <?php if (isset($results['error'])): ?>
                    <div class="alert alert-danger">
                        <strong>Error:</strong> <?php echo htmlspecialchars($results['error']); ?>
                    </div>
                <?php elseif (isset($results['success'])): ?>
                    <div class="alert alert-success">
                        <strong>Success!</strong> Enrichment completed in <?php echo $results['execution_time']; ?> seconds.
                        Confidence Score: <?php echo $results['confidence_score']; ?>
                    </div>

                    <!-- API Status Overview -->
                    <div class="table-responsive mb-4">
                        <h6>🔍 API Status Overview</h6>
                        <table class="table table-sm table-bordered">
                            <thead class="thead-light">
                                <tr>
                                    <th>API Source</th>
                                    <th>Status</th>
                                    <th>HTTP Code</th>
                                    <th>Data Found</th>
                                    <th>Key Fields</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($results['api_tests'] as $source => $test): ?>
                                <tr>
                                    <td><strong><?php echo ucwords(str_replace('_', ' ', $source)); ?></strong></td>
                                    <td>
                                        <?php if ($test['status'] === 'success'): ?>
                                            <span class="badge badge-success">✓ Success</span>
                                        <?php elseif ($test['status'] === 'no_data'): ?>
                                            <span class="badge badge-warning">⚠ No Data</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">✗ Failed</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $test['http_code'] ?? 'N/A'; ?></td>
                                    <td>
                                        <?php
                                        if ($source === 'google_books' && isset($test['data']['items'][0])) {
                                            echo 'Title, Author, Categories';
                                        } elseif ($source === 'open_library' && isset($test['data']['docs'][0])) {
                                            echo 'Title, Author, Subjects';
                                        } elseif ($source === 'amazon' && isset($test['data']['metadata'])) {
                                            echo 'Price, Format, Age Range';
                                        } else {
                                            echo 'None';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        if ($source === 'google_books' && isset($test['data']['items'][0]['volumeInfo'])) {
                                            $book = $test['data']['items'][0]['volumeInfo'];
                                            echo htmlspecialchars(($book['title'] ?? 'N/A') . ' by ' . implode(', ', $book['authors'] ?? []));
                                        } elseif ($source === 'open_library' && isset($test['data']['docs'][0])) {
                                            $book = $test['data']['docs'][0];
                                            echo htmlspecialchars(($book['title'] ?? 'N/A') . ' by ' . implode(', ', $book['author_name'] ?? []));
                                        } elseif ($source === 'amazon' && isset($test['data']['metadata']['reading_age'])) {
                                            echo 'Age: ' . htmlspecialchars($test['data']['metadata']['reading_age']);
                                        } else {
                                            echo isset($test['message']) ? htmlspecialchars($test['message']) : 'No data';
                                        }
                                        ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Expected vs Actual Analysis -->
                    <div class="table-responsive mb-4">
                        <h6>🎯 Expected vs Actual Data</h6>
                        <table class="table table-bordered table-striped">
                            <thead class="thead-dark">
                                <tr>
                                    <th>Field</th>
                                    <th>Expected</th>
                                    <th>Actual</th>
                                    <th>Status</th>
                                    <th>Source</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $enrichedData = $results['enriched_data'];

                                // Check title
                                $titleField = $enrichedData['fields']['title'] ?? null;
                                $actualTitle = $titleField['value'] ?? 'N/A';
                                $titleMatch = stripos($actualTitle, $title) !== false || stripos($title, $actualTitle) !== false;
                                $titleStatus = $titleMatch ? '<span class="badge badge-success">✅ Match</span>' : '<span class="badge badge-danger">❌ Different</span>';
                                ?>
                                <tr>
                                    <td><strong>Title</strong></td>
                                    <td><?php echo htmlspecialchars($title); ?></td>
                                    <td><?php echo htmlspecialchars($actualTitle); ?></td>
                                    <td><?php echo $titleStatus; ?></td>
                                    <td><?php echo htmlspecialchars($titleField['source'] ?? 'unknown'); ?></td>
                                </tr>

                                <?php
                                // Check author
                                $authorField = $enrichedData['fields']['author'] ?? null;
                                $actualAuthor = $authorField['value'] ?? 'N/A';
                                $authorMatch = stripos($actualAuthor, $author) !== false || stripos($author, $actualAuthor) !== false;
                                $authorStatus = $authorMatch ? '<span class="badge badge-success">✅ Match</span>' : '<span class="badge badge-danger">❌ Different</span>';
                                ?>
                                <tr>
                                    <td><strong>Author</strong></td>
                                    <td><?php echo htmlspecialchars($author); ?></td>
                                    <td><?php echo htmlspecialchars($actualAuthor); ?></td>
                                    <td><?php echo $authorStatus; ?></td>
                                    <td><?php echo htmlspecialchars($authorField['source'] ?? 'unknown'); ?></td>
                                </tr>

                                <?php
                                // Check age range
                                $ageRangeField = $enrichedData['fields']['age_range'] ?? null;
                                $actualAgeRange = $ageRangeField['value'] ?? 'null';
                                $ageStatus = ($actualAgeRange === 'null') ? '<span class="badge badge-warning">⚠ Null</span>' :
                                           (($actualAgeRange === '18+ years') ? '<span class="badge badge-danger">❌ Adult</span>' : '<span class="badge badge-success">✅ Has Value</span>');
                                ?>
                                <tr>
                                    <td><strong>Age Range</strong></td>
                                    <td>Children's (8-9 years)</td>
                                    <td><?php echo htmlspecialchars($actualAgeRange); ?></td>
                                    <td><?php echo $ageStatus; ?></td>
                                    <td><?php echo htmlspecialchars($ageRangeField['source'] ?? 'unknown'); ?></td>
                                </tr>

                                <?php
                                // Check reading level
                                $readingField = $enrichedData['fields']['reading_level'] ?? null;
                                $actualReading = $readingField['value'] ?? 'N/A';
                                $readingStatus = '<span class="badge badge-info">📝 Info</span>';
                                ?>
                                <tr>
                                    <td><strong>Reading Level</strong></td>
                                    <td>Age-appropriate</td>
                                    <td><?php echo htmlspecialchars($actualReading); ?></td>
                                    <td><?php echo $readingStatus; ?></td>
                                    <td><?php echo htmlspecialchars($readingField['source'] ?? 'unknown'); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- All Enriched Fields -->
                    <div class="table-responsive mb-4">
                        <h6>All Enriched Fields</h6>
                        <table class="table table-sm table-bordered">
                            <thead class="thead-light">
                                <tr>
                                    <th>Field</th>
                                    <th>Value</th>
                                    <th>Source</th>
                                    <th>Confidence</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($enrichedData['fields'] as $fieldName => $fieldData): ?>
                                    <?php if (is_array($fieldData)): ?>
                                        <?php
                                        $value = $fieldData['value'] ?? 'N/A';
                                        $source = $fieldData['source'] ?? 'unknown';
                                        $confidence = $fieldData['confidence'] ?? 'N/A';

                                        // Highlight important fields
                                        $rowClass = '';
                                        if ($fieldName === 'age_range' && $value === '18+ years') {
                                            $rowClass = ' class="table-danger"';
                                        } elseif (in_array($fieldName, ['title', 'author', 'age_range', 'reading_level'])) {
                                            $rowClass = ' class="table-warning"';
                                        }
                                        ?>
                                        <tr<?php echo $rowClass; ?>>
                                            <td><strong><?php echo htmlspecialchars($fieldName); ?></strong></td>
                                            <td><?php echo htmlspecialchars($value); ?></td>
                                            <td><?php echo htmlspecialchars($source); ?></td>
                                            <td><?php echo htmlspecialchars($confidence); ?></td>
                                        </tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Individual API Tests -->
                    <div class="row">
                        <!-- Google Books API Test -->
                        <div class="col-md-4">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="mb-0">📚 Google Books API</h6>
                                </div>
                                <div class="card-body">
                                    <?php
                                    $googleUrl = "https://www.googleapis.com/books/v1/volumes?q=isbn:$isbn";
                                    $ch = curl_init($googleUrl);
                                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                                    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
                                    $response = curl_exec($ch);
                                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                                    curl_close($ch);

                                    if ($response && $httpCode === 200) {
                                        $data = json_decode($response, true);
                                        if (!empty($data['items'][0])) {
                                            $book = $data['items'][0]['volumeInfo'];
                                            echo '<p><span class="badge badge-success">✓ API Success</span></p>';
                                            echo '<table class="table table-sm">';
                                            echo '<tr><td><strong>Title:</strong></td><td>' . htmlspecialchars($book['title'] ?? 'N/A') . '</td></tr>';
                                            echo '<tr><td><strong>Authors:</strong></td><td>' . htmlspecialchars(implode(', ', $book['authors'] ?? [])) . '</td></tr>';
                                            echo '<tr><td><strong>Categories:</strong></td><td>' . htmlspecialchars(implode(', ', $book['categories'] ?? [])) . '</td></tr>';
                                            echo '<tr><td><strong>Publisher:</strong></td><td>' . htmlspecialchars($book['publisher'] ?? 'N/A') . '</td></tr>';
                                            echo '</table>';
                                        } else {
                                            echo '<p><span class="badge badge-warning">⚠ No Results</span></p>';
                                        }
                                    } else {
                                        echo '<p><span class="badge badge-danger">✗ API Failed (HTTP ' . $httpCode . ')</span></p>';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>

                        <!-- OpenLibrary API Test -->
                        <div class="col-md-4">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="mb-0">📖 OpenLibrary API</h6>
                                </div>
                                <div class="card-body">
                                    <?php
                                    $olUrl = "https://openlibrary.org/search.json?q=isbn:$isbn&fields=*,availability&limit=1";
                                    $ch = curl_init($olUrl);
                                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                                    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
                                    $response = curl_exec($ch);
                                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                                    curl_close($ch);

                                    if ($response && $httpCode === 200) {
                                        $data = json_decode($response, true);
                                        if (!empty($data['docs'][0])) {
                                            $book = $data['docs'][0];
                                            echo '<p><span class="badge badge-success">✓ API Success</span></p>';
                                            echo '<table class="table table-sm">';
                                            echo '<tr><td><strong>Title:</strong></td><td>' . htmlspecialchars($book['title'] ?? 'N/A') . '</td></tr>';
                                            echo '<tr><td><strong>Authors:</strong></td><td>' . htmlspecialchars(implode(', ', $book['author_name'] ?? [])) . '</td></tr>';
                                            echo '<tr><td><strong>Subjects:</strong></td><td>' . htmlspecialchars(implode(', ', array_slice($book['subject'] ?? [], 0, 3))) . '...</td></tr>';
                                            echo '<tr><td><strong>First Published:</strong></td><td>' . htmlspecialchars($book['first_publish_year'] ?? 'N/A') . '</td></tr>';
                                            echo '</table>';
                                        } else {
                                            echo '<p><span class="badge badge-warning">⚠ No Results</span></p>';
                                        }
                                    } else {
                                        echo '<p><span class="badge badge-danger">✗ API Failed (HTTP ' . $httpCode . ')</span></p>';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>

                        <!-- Amazon Test -->
                        <div class="col-md-4">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="mb-0">🛒 Amazon Scraping</h6>
                                </div>
                                <div class="card-body">
                                    <?php
                                    if (function_exists('scrapeAmazonBuyingOptions')) {
                                        $amazonData = scrapeAmazonBuyingOptions($isbn);
                                        if ($amazonData && !empty($amazonData)) {
                                            echo '<p><span class="badge badge-success">✓ Scraping Success</span></p>';
                                            if (isset($amazonData['metadata']) && !empty($amazonData['metadata'])) {
                                                echo '<table class="table table-sm">';
                                                foreach (array_slice($amazonData['metadata'], 0, 4) as $key => $value) {
                                                    if (is_array($value)) $value = implode(', ', $value);
                                                    echo '<tr><td><strong>' . htmlspecialchars(ucfirst(str_replace('_', ' ', $key))) . ':</strong></td><td>' . htmlspecialchars($value) . '</td></tr>';
                                                }
                                                echo '</table>';
                                            } else {
                                                echo '<p><span class="badge badge-warning">⚠ No Metadata</span></p>';
                                            }
                                        } else {
                                            echo '<p><span class="badge badge-warning">⚠ No Data Found</span></p>';
                                        }
                                    } else {
                                        echo '<p><span class="badge badge-danger">✗ Function Not Available</span></p>';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- AJAX Call Results -->
                    <?php if (isset($results['ajax_calls'])): ?>
                    <div class="mb-4">
                        <h6>🔧 AJAX Call Results (Same as Modal)</h6>

                        <!-- Enrichment AJAX -->
                        <div class="card mb-3">
                            <div class="card-header">
                                <strong>get_enrichment_data AJAX Response</strong>
                            </div>
                            <div class="card-body">
                                <?php if ($results['ajax_calls']['enrichment']['success']): ?>
                                    <span class="badge badge-success">✓ Success</span>
                                    <p><strong>Fields Found:</strong> <?php echo count($results['ajax_calls']['enrichment']['data']['fields']); ?></p>
                                    <p><strong>Confidence:</strong> <?php echo $results['ajax_calls']['enrichment']['data']['confidence_score']; ?></p>

                                    <!-- Show key fields -->
                                    <?php
                                    $keyFields = ['title', 'author', 'age_range', 'reading_level'];
                                    foreach ($keyFields as $field) {
                                        if (isset($results['ajax_calls']['enrichment']['data']['fields'][$field])) {
                                            $fieldData = $results['ajax_calls']['enrichment']['data']['fields'][$field];
                                            echo "<p><strong>$field:</strong> " . htmlspecialchars($fieldData['value'] ?? 'N/A') . " (source: " . htmlspecialchars($fieldData['source'] ?? 'unknown') . ")</p>";
                                        }
                                    }
                                    ?>
                                <?php else: ?>
                                    <span class="badge badge-danger">✗ Failed</span>
                                    <p><strong>Error:</strong> <?php echo htmlspecialchars($results['ajax_calls']['enrichment']['message'] ?? 'Unknown error'); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Amazon AJAX -->
                        <div class="card mb-3">
                            <div class="card-header">
                                <strong>get_amazon_data AJAX Response</strong>
                            </div>
                            <div class="card-body">
                                <?php if (isset($results['ajax_calls']['amazon']['success']) && $results['ajax_calls']['amazon']['success']): ?>
                                    <span class="badge badge-success">✓ Success</span>
                                    <p><strong>Amazon Data Found:</strong></p>
                                    <pre><?php echo htmlspecialchars(json_encode($results['ajax_calls']['amazon']['data'] ?? [], JSON_PRETTY_PRINT)); ?></pre>
                                <?php else: ?>
                                    <span class="badge badge-warning">⚠ No Data</span>
                                    <p><strong>Message:</strong> <?php echo htmlspecialchars($results['ajax_calls']['amazon']['message'] ?? 'No Amazon data found'); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="alert alert-info mt-3">
                        <strong>Debug Information:</strong> Check the <a href="debug-logs.php" class="alert-link">debug logs</a> for more details.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
// Include the footer
require_once '../includes/footer.php';
?>
