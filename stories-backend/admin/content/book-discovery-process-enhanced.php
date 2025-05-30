<?php
/**
 * Enhanced Book Discovery Process Page
 *
 * Features:
 * - Real-time progress updates with WebSocket-like streaming
 * - Enhanced table component with bulk actions and pagination
 * - Automatic API enrichment (Google Books, Open Library)
 * - Modern UX with progress indicators
 */

// Disable output buffering for real-time updates
if (ob_get_level()) {
    ob_end_clean();
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include auth check
require_once '../includes/auth-check.php';

// Include database connection
require_once '../includes/db-connect.php';

// Include components
require_once '../includes/enhanced-table-component.php';
require_once '../includes/bulk-actions-component.php';
require_once '../includes/pagination-component.php';

// Include discovery engine and enrichment functions
require_once 'book-discovery/BookDiscoveryEngine.php';
require_once 'book-import-validate/functions/data-enrichment-functions.php';

// Set page variables
$pageTitle = 'Book Discovery Process';
$currentPage = 'book-import-tool';

// Include header
include_once '../includes/header.php';

// Flush output function for real-time updates
function flushDiscoveryOutput() {
    // Force output to browser immediately
    while (ob_get_level()) {
        ob_end_flush();
    }
    flush();
    
    // Add padding to force browser to display content
    echo str_repeat(' ', 1024);
    flush();
}

// Progress update function
function sendProgressUpdate($step, $current, $total, $message = '') {
    $progress = $total > 0 ? round(($current / $total) * 100) : 0;
    echo "<script>";
    echo "if (typeof updateProgress === 'function') {";
    echo "updateProgress('{$step}', {$current}, {$total}, {$progress}, '" . addslashes($message) . "');";
    echo "} else {";
    echo "console.log('Progress: {$step} - {$current}/{$total} - {$message}');";
    echo "}";
    echo "</script>";
    flushDiscoveryOutput();
}

// Book enrichment function with progress updates
function enrichBookWithAPIs($book, $index, $total) {
    sendProgressUpdate('enrichment', $index, $total, "Enriching: {$book['title']}");
    
    // Get enriched data from APIs
    $enrichedData = getEnrichedBookData(
        $book['title'],
        $book['author'] ?? '',
        $book['isbn'] ?? $book['isbn13'] ?? ''
    );
    
    // Merge enriched data with original book data
    if (!empty($enrichedData['fields'])) {
        foreach ($enrichedData['fields'] as $field => $data) {
            if (!empty($data['new_data']['value']) && empty($book[$field])) {
                $book[$field] = $data['new_data']['value'];
            }
        }
    }
    
    return $book;
}

// Import book function for discovered books
function importBook($db, $book) {
    try {
        // Check if book already exists
        $checkStmt = $db->prepare("
            SELECT id FROM directory_items
            WHERE title = ? AND type = 'book'
        ");
        $checkStmt->execute([$book['title']]);
        
        if ($checkStmt->fetch()) {
            return ['success' => false, 'message' => 'Book already exists'];
        }
        
        // Generate slug
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $book['title'])));
        
        // Insert directory item
        $dirStmt = $db->prepare("
            INSERT INTO directory_items (title, slug, type, description, created_at, updated_at)
            VALUES (?, ?, 'book', ?, NOW(), NOW())
        ");
        
        $description = $book['description'] ?? '';
        $dirStmt->execute([$book['title'], $slug, $description]);
        $directoryItemId = $db->lastInsertId();
        
        // Insert book details
        $bookStmt = $db->prepare("
            INSERT INTO books (
                directory_item_id, isbn, isbn13, author, publisher,
                page_count, series, price_range, age_range, tags
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $tags = is_array($book['tags']) ? implode(',', $book['tags']) : ($book['tags'] ?? '');
        
        $bookStmt->execute([
            $directoryItemId,
            $book['isbn'] ?? '',
            $book['isbn13'] ?? '',
            $book['author'] ?? '',
            $book['publisher'] ?? '',
            $book['page_count'] ?? null,
            $book['series'] ?? '',
            $book['price_range'] ?? '',
            $book['age_range'] ?? '',
            $tags
        ]);
        
        return ['success' => true, 'id' => $directoryItemId];
        
    } catch (Exception $e) {
        error_log("Import error: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}
?>

<!-- Load JavaScript first -->
<script>
function showDiscoveryProgress() {
    document.getElementById('discoveryProgress').style.display = 'flex';
}

function hideDiscoveryProgress() {
    document.getElementById('discoveryProgress').style.display = 'none';
}

function updateProgress(step, current, total, percentage, message) {
    const progressBar = document.getElementById('discoveryProgressBar');
    const progressMessage = document.getElementById('discoveryProgressMessage');
    const steps = document.querySelectorAll('.step');
    
    // Update progress bar
    if (progressBar) {
        progressBar.style.width = percentage + '%';
        progressBar.setAttribute('aria-valuenow', percentage);
        progressBar.textContent = percentage + '%';
    }
    
    // Update message
    if (progressMessage) {
        progressMessage.textContent = message;
    }
    
    // Update step status
    steps.forEach(stepEl => {
        const stepName = stepEl.getAttribute('data-step');
        const statusEl = stepEl.querySelector('.step-status');
        
        if (stepName === step) {
            stepEl.classList.add('active');
            stepEl.classList.remove('completed');
            if (statusEl) statusEl.textContent = `${current}/${total}`;
        } else if (stepName === 'discovery' && step === 'enrichment') {
            stepEl.classList.remove('active');
            stepEl.classList.add('completed');
            if (statusEl) statusEl.textContent = 'completed';
        } else if (stepName === 'enrichment' && step === 'complete') {
            stepEl.classList.remove('active');
            stepEl.classList.add('completed');
            if (statusEl) statusEl.textContent = 'completed';
        } else if (stepName === 'complete' && step === 'complete') {
            stepEl.classList.add('active', 'completed');
            if (statusEl) statusEl.textContent = 'completed';
        }
    });
}
</script>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h1><?php echo $pageTitle; ?></h1>
            
            <!-- Progress Overlay -->
            <div id="discoveryProgress" class="discovery-progress-overlay" style="display: none;">
                <div class="discovery-progress-container">
                    <div class="discovery-progress-spinner"></div>
                    <h3 id="discoveryProgressTitle">Discovering Books...</h3>
                    <div class="progress mb-3">
                        <div id="discoveryProgressBar" class="progress-bar progress-bar-striped progress-bar-animated" 
                             role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                            0%
                        </div>
                    </div>
                    <p id="discoveryProgressMessage">Initializing discovery process...</p>
                    <div id="discoveryProgressSteps" class="progress-steps">
                        <div class="step" data-step="discovery">
                            <span class="step-number">1</span>
                            <span class="step-label">Discovering Books</span>
                            <span class="step-status">pending</span>
                        </div>
                        <div class="step" data-step="enrichment">
                            <span class="step-number">2</span>
                            <span class="step-label">API Enrichment</span>
                            <span class="step-status">pending</span>
                        </div>
                        <div class="step" data-step="complete">
                            <span class="step-number">3</span>
                            <span class="step-label">Complete</span>
                            <span class="step-status">pending</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <?php
                    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['discovery_url'])) {
                        $url = filter_var($_POST['discovery_url'], FILTER_VALIDATE_URL);
                        $ageFilter = $_POST['age_filter'] ?? '';
                        $autoEnrich = isset($_POST['auto_enrich']) ? true : false;
                        $importToDb = isset($_POST['import_to_db']) ? true : false;
                        
                        if (!$url) {
                            echo "<div class='alert alert-danger'>Invalid URL provided</div>";
                            echo "<a href='book-import-tool.php?tab=discovery' class='btn btn-primary'>Back to Discovery</a>";
                            exit;
                        }
                        
                        echo "<script>showDiscoveryProgress();</script>";
                        flushDiscoveryOutput();
                        
                        try {
                            // Initialize discovery engine
                            $discoveryEngine = new BookDiscoveryEngine($db);
                            
                            // Step 1: Discover books
                            sendProgressUpdate('discovery', 0, 100, "Starting discovery from: " . htmlspecialchars($url));
                            
                            $books = $discoveryEngine->discoverFromURL($url);
                            
                            sendProgressUpdate('discovery', 100, 100, "Found " . count($books) . " books");
                            
                            // Filter by age if specified
                            if ($ageFilter && !empty($books)) {
                                $originalCount = count($books);
                                $books = array_filter($books, function($book) use ($ageFilter) {
                                    $bookAge = strtolower($book['age_range'] ?? '');
                                    $filterAge = strtolower($ageFilter);
                                    return strpos($bookAge, $filterAge) !== false || 
                                           strpos($bookAge, str_replace('-', ' to ', $filterAge)) !== false;
                                });
                                
                                if ($originalCount > count($books)) {
                                    sendProgressUpdate('discovery', 100, 100, "Filtered to " . count($books) . " books matching age range: {$ageFilter}");
                                }
                            }
                            
                            if (empty($books)) {
                                echo "<script>hideDiscoveryProgress();</script>";
                                echo "<div class='alert alert-warning'>No books found matching your criteria.</div>";
                                echo "<a href='book-import-tool.php?tab=discovery' class='btn btn-primary'>Back to Discovery</a>";
                                exit;
                            }
                            
                            // Step 2: Enrich with APIs if requested
                            if ($autoEnrich) {
                                sendProgressUpdate('enrichment', 0, count($books), "Starting API enrichment...");
                                
                                foreach ($books as $index => &$book) {
                                    $book = enrichBookWithAPIs($book, $index + 1, count($books));
                                }
                                unset($book); // Break reference
                            }
                            
                            // Step 3: Import to database if requested
                            $importCount = 0;
                            $skipCount = 0;
                            $errorCount = 0;
                            
                            if ($importToDb) {
                                require_once 'book-import-validate/functions/validation-functions.php';
                                
                                foreach ($books as $index => $book) {
                                    try {
                                        $result = importBook($db, $book);
                                        if ($result['success']) {
                                            $importCount++;
                                        } else {
                                            $errorCount++;
                                        }
                                    } catch (Exception $e) {
                                        $errorCount++;
                                        error_log("Import error: " . $e->getMessage());
                                    }
                                }
                            }
                            
                            sendProgressUpdate('complete', 100, 100, "Discovery process complete!");
                            
                            echo "<script>hideDiscoveryProgress();</script>";
                            flushDiscoveryOutput();
                            
                            // Display results using enhanced table component
                            echo "<div class='discovery-results'>";
                            echo "<h3>Discovery Results</h3>";
                            
                            // Summary
                            echo "<div class='alert alert-info'>";
                            echo "<h5>Summary:</h5>";
                            echo "<ul>";
                            echo "<li>Total books discovered: " . count($books) . "</li>";
                            if ($autoEnrich) {
                                echo "<li>Books enriched with API data: " . count($books) . "</li>";
                            }
                            if ($importToDb) {
                                echo "<li>Successfully imported: {$importCount}</li>";
                                echo "<li>Skipped (already exist): {$skipCount}</li>";
                                echo "<li>Errors: {$errorCount}</li>";
                            }
                            echo "</ul>";
                            echo "</div>";
                            
                            // Prepare data for enhanced table
                            $tableData = [];
                            foreach ($books as $index => $book) {
                                $tableData[] = [
                                    'id' => $index + 1,
                                    'title' => $book['title'] ?? '',
                                    'author' => $book['author'] ?? '',
                                    'age_range' => $book['age_range'] ?? '',
                                    'year' => $book['year'] ?? '',
                                    'isbn' => $book['isbn'] ?? '',
                                    'isbn13' => $book['isbn13'] ?? '',
                                    'publisher' => $book['publisher'] ?? '',
                                    'tags' => is_array($book['tags']) ? implode(', ', $book['tags']) : ($book['tags'] ?? ''),
                                    'source' => $book['source'] ?? 'booktrust',
                                    'detail_url' => $book['detail_url'] ?? '',
                                    'cover_url' => $book['cover_url'] ?? '',
                                    'enriched' => $autoEnrich ? 'Yes' : 'No'
                                ];
                            }
                            
                            // Define table columns
                            $columns = [
                                'title' => ['label' => 'Title', 'sortable' => true, 'searchable' => true],
                                'author' => ['label' => 'Author', 'sortable' => true, 'searchable' => true],
                                'age_range' => ['label' => 'Age Range', 'sortable' => true],
                                'year' => ['label' => 'Year', 'sortable' => true],
                                'isbn' => ['label' => 'ISBN', 'sortable' => false],
                                'isbn13' => ['label' => 'ISBN-13', 'sortable' => false],
                                'publisher' => ['label' => 'Publisher', 'sortable' => true, 'searchable' => true],
                                'tags' => ['label' => 'Tags/Genres', 'sortable' => false],
                                'source' => ['label' => 'Source', 'sortable' => true],
                                'enriched' => ['label' => 'API Enriched', 'sortable' => true]
                            ];
                            
                            // Render enhanced table
                            renderEnhancedTable($tableData, $columns, 'discovered-books', 'discovered-books-table', [
                                'show_bulk_actions' => true,
                                'bulk_actions' => [
                                    'import' => 'Import Selected',
                                    'export_csv' => 'Export as CSV',
                                    'export_json' => 'Export as JSON'
                                ],
                                'items_per_page' => 25,
                                'show_search' => true,
                                'show_filters' => true
                            ]);
                            
                            echo "</div>";
                            
                        } catch (Exception $e) {
                            echo "<script>hideDiscoveryProgress();</script>";
                            echo "<div class='alert alert-danger'>";
                            echo "<strong>Error:</strong> " . htmlspecialchars($e->getMessage());
                            echo "</div>";
                            echo "<a href='book-import-tool.php?tab=discovery' class='btn btn-primary'>Back to Discovery</a>";
                            error_log("Book discovery error: " . $e->getMessage());
                        }
                        
                    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['discover_sources'])) {
                        // Automated source discovery
                        echo "<h3>Discovering New Book Sources</h3>";
                        flushDiscoveryOutput();
                        
                        $discoveryEngine = new BookDiscoveryEngine($db);
                        $sources = $discoveryEngine->discoverNewSources();
                        
                        echo "<p>Found " . count($sources) . " potential book sources:</p>";
                        
                        echo "<div class='table-responsive'>";
                        echo "<table class='table table-striped'>";
                        echo "<thead><tr>";
                        echo "<th>Source</th>";
                        echo "<th>URL</th>";
                        echo "<th>Confidence</th>";
                        echo "<th>Action</th>";
                        echo "</tr></thead>";
                        echo "<tbody>";
                        
                        foreach ($sources as $source) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($source['title']) . "</td>";
                            echo "<td><a href='" . htmlspecialchars($source['url']) . "' target='_blank'>" . 
                                 htmlspecialchars($source['url']) . "</a></td>";
                            echo "<td>" . round($source['confidence'] * 100) . "%</td>";
                            echo "<td>";
                            echo "<form method='post' action='book-discovery-process-enhanced.php' style='display:inline;'>";
                            echo "<input type='hidden' name='discovery_url' value='" . htmlspecialchars($source['url']) . "'>";
                            echo "<button type='submit' class='btn btn-sm btn-primary'>Discover Books</button>";
                            echo "</form>";
                            echo "</td>";
                            echo "</tr>";
                        }
                        
                        echo "</tbody>";
                        echo "</table>";
                        echo "</div>";
                        
                    } else {
                        // Not a POST request, redirect to discovery tab
                        echo "<div class='alert alert-info'>";
                        echo "<i class='fas fa-info-circle'></i> This page processes book discovery requests. ";
                        echo "Please use the Discovery tab in the Book Import Tool to start discovering books.";
                        echo "</div>";
                        echo "<a href='book-import-tool.php?tab=discovery' class='btn btn-primary'>";
                        echo "<i class='fas fa-arrow-left'></i> Go to Discovery Tab";
                        echo "</a>";
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Discovery Progress Overlay */
.discovery-progress-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.8);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
}

.discovery-progress-container {
    background-color: white;
    padding: 2rem;
    border-radius: 10px;
    text-align: center;
    max-width: 500px;
    width: 90%;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
}

.discovery-progress-spinner {
    border: 5px solid var(--gray-200);
    border-top: 5px solid var(--primary);
    border-radius: 50%;
    width: 50px;
    height: 50px;
    animation: spin 1s linear infinite;
    margin: 0 auto 1rem;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.progress-steps {
    margin-top: 1.5rem;
    text-align: left;
}

.step {
    display: flex;
    align-items: center;
    margin-bottom: 0.5rem;
    padding: 0.5rem;
    border-radius: 5px;
    transition: all 0.3s ease;
}

.step.active {
    background-color: var(--primary-light);
    color: var(--primary);
}

.step.completed {
    background-color: var(--success-light);
    color: var(--success);
}

.step-number {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background-color: var(--gray-300);
    color: white;
    font-size: 0.8rem;
    font-weight: bold;
    margin-right: 0.75rem;
}

.step.active .step-number {
    background-color: var(--primary);
}

.step.completed .step-number {
    background-color: var(--success);
}

.step-label {
    flex: 1;
    font-weight: 500;
}

.step-status {
    font-size: 0.8rem;
    text-transform: uppercase;
    font-weight: bold;
}

.discovery-results {
    margin-top: 2rem;
}

/* Enhanced table styling for discovery results */
.discovered-books-table {
    margin-top: 1rem;
}

.discovered-books-table .table th {
    background-color: var(--primary);
    color: white;
    border: none;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.85rem;
    letter-spacing: 0.5px;
}

.discovered-books-table .table td {
    vertical-align: middle;
    border-color: var(--gray-200);
}

.discovered-books-table .table tbody tr:hover {
    background-color: var(--primary-light);
}
</style>

<script>
// Auto-hide progress overlay after completion
setTimeout(() => {
    const progressOverlay = document.getElementById('discoveryProgress');
    if (progressOverlay && progressOverlay.style.display === 'flex') {
        const completeStep = document.querySelector('.step[data-step="complete"]');
        if (completeStep && completeStep.classList.contains('completed')) {
            setTimeout(() => {
                hideDiscoveryProgress();
            }, 2000);
        }
    }
}, 1000);
</script>

<?php include_once '../includes/footer.php'; ?>