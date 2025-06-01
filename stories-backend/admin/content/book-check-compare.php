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
 * Convert ISBN-13 to ISBN-10
 */
function convertISBN13ToISBN10($isbn13) {
    if (strlen($isbn13) !== 13 || substr($isbn13, 0, 3) !== '978') {
        return null;
    }

    $isbn10Base = substr($isbn13, 3, 9);
    $sum = 0;
    for ($i = 0; $i < 9; $i++) {
        $sum += (int)$isbn10Base[$i] * (10 - $i);
    }
    $checkDigit = (11 - ($sum % 11)) % 11;
    $checkDigit = $checkDigit === 10 ? 'X' : (string)$checkDigit;

    return $isbn10Base . $checkDigit;
}

/**
 * Test individual APIs with detailed diagnostics and field mapping
 */
function testIndividualAPIs($isbn) {
    $results = [];

    // Convert to ISBN-10 for Amazon (as modal uses)
    $isbn10 = convertISBN13ToISBN10($isbn);

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

    // Test Amazon Scraping with ISBN-10 (same as modal)
    try {
        if (function_exists('scrapeAmazonBuyingOptions')) {
            // Try both ISBN-13 and ISBN-10
            $amazonData = null;
            if ($isbn10) {
                $amazonData = scrapeAmazonBuyingOptions($isbn10);
            }
            if (!$amazonData || empty($amazonData)) {
                $amazonData = scrapeAmazonBuyingOptions($isbn);
            }

            $results['amazon'] = [
                'status' => $amazonData && !empty($amazonData) ? 'success' : 'no_data',
                'data' => $amazonData,
                'isbn_used' => $amazonData && !empty($amazonData) ? ($isbn10 ? $isbn10 : $isbn) : null
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

            // Step 2: Test the main enrichment function (fallback to original approach)
            $enrichedData = getEnrichedBookData($title, $author, $isbn);

            $endTime = microtime(true);
            $executionTime = round($endTime - $startTime, 2);

            $results['success'] = true;
            $results['enriched_data'] = $enrichedData;
            $results['execution_time'] = $executionTime;
            $results['confidence_score'] = $enrichedData['confidence_score'] ?? 'N/A';

            // Add note about AJAX testing
            $results['note'] = 'Using getEnrichedBookData() function - AJAX testing temporarily disabled to prevent page errors';

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

                    <!-- Comprehensive Field Mapping Table -->
                    <div class="table-responsive mb-4">
                        <h6>📊 Complete Field Mapping: Books Table vs API Sources</h6>
                        <table class="table table-bordered table-hover">
                            <thead class="thead-dark">
                                <tr>
                                    <th>Books Table Field</th>
                                    <th>📚 Google Books</th>
                                    <th>📖 OpenLibrary</th>
                                    <th>🛒 Amazon</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Get API data
                                $googleData = $results['api_tests']['google_books']['data']['items'][0]['volumeInfo'] ?? null;
                                $olData = $results['api_tests']['open_library']['data']['docs'][0] ?? null;
                                $amazonData = $results['api_tests']['amazon']['data'] ?? null;

                                // Define field mappings
                                $fieldMappings = [
                                    'title' => [
                                        'google' => $googleData['title'] ?? 'N/A',
                                        'ol' => $olData['title'] ?? 'N/A',
                                        'amazon' => $amazonData['metadata']['title'] ?? 'N/A'
                                    ],
                                    'author' => [
                                        'google' => isset($googleData['authors']) ? implode(', ', $googleData['authors']) : 'N/A',
                                        'ol' => isset($olData['author_name']) ? implode(', ', $olData['author_name']) : 'N/A',
                                        'amazon' => $amazonData['metadata']['author'] ?? 'N/A'
                                    ],
                                    'isbn' => [
                                        'google' => 'From query',
                                        'ol' => 'From query',
                                        'amazon' => 'From query'
                                    ],
                                    'isbn_13' => [
                                        'google' => 'From query',
                                        'ol' => 'From query',
                                        'amazon' => 'From query'
                                    ],
                                    'publisher' => [
                                        'google' => $googleData['publisher'] ?? 'N/A',
                                        'ol' => isset($olData['publisher']) ? implode(', ', array_slice($olData['publisher'], 0, 1)) : 'N/A',
                                        'amazon' => $amazonData['metadata']['publisher'] ?? 'N/A'
                                    ],
                                    'publication_date' => [
                                        'google' => $googleData['publishedDate'] ?? 'N/A',
                                        'ol' => $olData['first_publish_year'] ?? 'N/A',
                                        'amazon' => $amazonData['metadata']['publication_date'] ?? 'N/A'
                                    ],
                                    'page_count' => [
                                        'google' => $googleData['pageCount'] ?? 'N/A',
                                        'ol' => $olData['number_of_pages_median'] ?? 'N/A',
                                        'amazon' => $amazonData['metadata']['pages'] ?? 'N/A'
                                    ],
                                    'language' => [
                                        'google' => $googleData['language'] ?? 'N/A',
                                        'ol' => isset($olData['language']) ? implode(', ', array_slice($olData['language'], 0, 1)) : 'N/A',
                                        'amazon' => $amazonData['metadata']['language'] ?? 'N/A'
                                    ],
                                    'age_range' => [
                                        'google' => isset($googleData['categories']) ? 'From categories: ' . implode(', ', $googleData['categories']) : 'N/A',
                                        'ol' => 'No direct age data',
                                        'amazon' => $amazonData['metadata']['reading_age'] ?? $amazonData['metadata']['age_range'] ?? 'N/A'
                                    ],
                                    'reading_level' => [
                                        'google' => 'Derived from categories',
                                        'ol' => 'No direct reading level',
                                        'amazon' => $amazonData['metadata']['reading_level'] ?? 'N/A'
                                    ],
                                    'tags' => [
                                        'google' => isset($googleData['categories']) ? implode(', ', $googleData['categories']) : 'N/A',
                                        'ol' => isset($olData['subject']) ? implode(', ', array_slice($olData['subject'], 0, 5)) . '...' : 'N/A',
                                        'amazon' => $amazonData['metadata']['genres'] ?? 'N/A'
                                    ],
                                    'cover_url' => [
                                        'google' => $googleData['imageLinks']['thumbnail'] ?? 'N/A',
                                        'ol' => 'Available via cover API',
                                        'amazon' => $amazonData['metadata']['image_url'] ?? 'N/A'
                                    ],
                                    'purchase_links' => [
                                        'google' => $googleData['previewLink'] ?? 'N/A',
                                        'ol' => 'No purchase links',
                                        'amazon' => isset($amazonData['buying_options']) ? 'Available' : 'N/A'
                                    ],
                                    'format' => [
                                        'google' => 'No format data',
                                        'ol' => 'No format data',
                                        'amazon' => $amazonData['metadata']['format'] ?? 'N/A'
                                    ],
                                    'price_range' => [
                                        'google' => 'No price data',
                                        'ol' => 'No price data',
                                        'amazon' => isset($amazonData['buying_options']) ? 'Available' : 'N/A'
                                    ]
                                ];

                                foreach ($fieldMappings as $field => $sources) {
                                    $hasData = false;
                                    foreach ($sources as $value) {
                                        if ($value !== 'N/A' && $value !== 'No direct age data' && $value !== 'No direct reading level' && $value !== 'No format data' && $value !== 'No price data' && $value !== 'No purchase links') {
                                            $hasData = true;
                                            break;
                                        }
                                    }
                                    $statusClass = $hasData ? 'table-success' : 'table-warning';
                                    $statusIcon = $hasData ? '✅' : '⚠️';
                                    ?>
                                    <tr class="<?php echo $statusClass; ?>">
                                        <td><strong><?php echo htmlspecialchars($field); ?></strong></td>
                                        <td><?php echo htmlspecialchars($sources['google']); ?></td>
                                        <td><?php echo htmlspecialchars($sources['ol']); ?></td>
                                        <td><?php echo htmlspecialchars($sources['amazon']); ?></td>
                                        <td><?php echo $statusIcon; ?></td>
                                    </tr>
                                    <?php
                                }
                                ?>
                            </tbody>
                        </table>

                        <!-- Amazon ISBN Test Info -->
                        <?php if (isset($results['api_tests']['amazon']['isbn_used'])): ?>
                        <div class="alert alert-info">
                            <strong>Amazon ISBN Used:</strong> <?php echo htmlspecialchars($results['api_tests']['amazon']['isbn_used']); ?>
                            (Modal uses ISBN-10 for Amazon scraping)
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Note about testing approach -->
                    <?php if (isset($results['note'])): ?>
                    <div class="alert alert-warning">
                        <strong>Note:</strong> <?php echo htmlspecialchars($results['note']); ?>
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
