<?php
/**
 * Enhanced Direct Import Tool
 * 
 * A comprehensive tool to import content with proper handling of
 * media files, authors, and tags. This improved version:
 * 
 * 1. Only deletes data related to the specific content being imported
 * 2. Supports multiple content types (stories, retail publisher stories, games, etc.)
 * 3. Uses the admin header/footer template for consistent UX
 * 4. Provides better error handling and reporting
 */

// Include auth check
require_once '../admin/includes/auth-check.php';

// Include database connection
require_once '../admin/includes/db-connect.php';

// Basic error handling and setup
ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit(0);
ini_set('output_buffering', 'off');
ini_set('implicit_flush', true);
ob_implicit_flush(true);

// Function to flush output buffer to ensure real-time progress display
function flushOutput() {
    if (ob_get_level() > 0) {
        ob_flush();
        flush();
    }
}

// Function to clean data for specific content type
function cleanContentData($db, $contentType, $sourceType = null) {
    try {
        // Begin transaction
        $db->beginTransaction();
        
        // Default to 'child' if no source type provided
        $sourceType = $sourceType ?: 'child';
        
        // Initialize counters for reporting
        $deletedAssociations = 0;
        $deletedItems = 0;
        $deletedMedia = 0;
        
        if ($contentType === 'books') {
            // For books, we need to clean directory_items with type 'book'
            echo "<h2>Cleaning Existing Data</h2>";
            flushOutput();
            
            // Get directory items IDs of book type
            $idStmt = $db->prepare("SELECT id FROM directory_items WHERE type = 'book'");
            $idStmt->execute();
            $itemIds = $idStmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (!empty($itemIds)) {
                $itemIdList = implode(',', $itemIds);
                
                // Delete book entries
                $stmt = $db->prepare("DELETE FROM books WHERE directory_item_id IN ($itemIdList)");
                $stmt->execute();
                $deletedItems = $stmt->rowCount();
                echo "<p class='info'>Deleted $deletedItems book entries</p>";
                flushOutput();
                
                // Delete book_authors associations
                $stmt = $db->prepare("DELETE FROM book_authors WHERE directory_item_id IN ($itemIdList)");
                $stmt->execute();
                $bookAuthorCount = $stmt->rowCount();
                $deletedAssociations += $bookAuthorCount;
                echo "<p class='info'>Deleted $bookAuthorCount book author relationships</p>";
                flushOutput();
                
                // Delete directory items
                $stmt = $db->prepare("DELETE FROM directory_items WHERE id IN ($itemIdList)");
                $stmt->execute();
                $dirItemCount = $stmt->rowCount();
                echo "<p class='info'>Deleted $dirItemCount existing book directory items</p>";
                flushOutput();
            } else {
                echo "<p class='info'>No existing books found to clean</p>";
                flushOutput();
            }
        } 
        
        // Commit transaction
        $db->commit();
        
        echo "<div class='alert alert-success'>";
        echo "<h3>Database cleaned successfully:</h3>";
        echo "<p>Removed $deletedItems items, $deletedAssociations associations, and $deletedMedia media files</p>";
        echo "</div>";
        flushOutput();
        
        return true;
    } catch (Exception $e) {
        // Rollback on error
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        
        echo "<div class='alert alert-danger'>";
        echo "<h3>Error cleaning database:</h3>";
        echo "<p>" . $e->getMessage() . "</p>";
        echo "</div>";
        flushOutput();
        
        return false;
    }
}

/**
 * Convert various date formats to MySQL YYYY-MM-DD format
 *
 * @param string|null $dateStr The date string to convert
 * @return string|null MySQL formatted date or null if conversion fails
 */
function convertToMySQLDate($dateStr) {
    if (empty($dateStr)) {
        return null;
    }
    
    // Store original for debugging
    $originalDate = $dateStr;
    
    // Clean up the date string
    $dateStr = trim($dateStr);
    
    // Case 1: Just a year (e.g., "1975", "1937")
    if (preg_match('/^\d{4}$/', $dateStr)) {
        return $dateStr . '-01-01'; // Add month and day
    }
    
    // Case 2: Year-month (e.g., "2003-05")
    if (preg_match('/^(\d{4})-(\d{1,2})$/', $dateStr, $matches)) {
        return $matches[1] . '-' . str_pad($matches[2], 2, '0', STR_PAD_LEFT) . '-01'; // Add day
    }
    
    // Case 3: Month Year (e.g., "May 2003", "February 2012", "September 2013")
    if (preg_match('/^([a-zA-Z]+)\s+(\d{4})$/i', $dateStr, $matches)) {
        $month = $matches[1];
        $year = $matches[2];
        
        // Map of month names to numbers
        $months = array(
            'january' => '01', 'february' => '02', 'march' => '03',
            'april' => '04', 'may' => '05', 'june' => '06',
            'july' => '07', 'august' => '08', 'september' => '09',
            'october' => '10', 'november' => '11', 'december' => '12'
        );
        
        $monthLower = strtolower($month);
        if (isset($months[$monthLower])) {
            return $year . '-' . $months[$monthLower] . '-01';
        }
        
        // If month name not found in map, try strtotime as fallback
        try {
            $timestamp = strtotime("$month 1, $year");
            if ($timestamp !== false) {
                return date('Y-m-d', $timestamp);
            }
        } catch (Exception $e) {
            // Ignore and use default
        }
        
        // If all else fails, default to January of that year
        return $year . '-01-01';
    }
    
    // Case 4: Already in YYYY-MM-DD format
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr)) {
        // Validate the date
        try {
            $date = new DateTime($dateStr);
            return $date->format('Y-m-d');
        } catch (Exception $e) {
            // Invalid date, try to extract just the year
            if (preg_match('/(\d{4})/', $dateStr, $matches)) {
                return $matches[1] . '-01-01';
            }
            return null;
        }
    }
    
    // Case 5: Try to extract month and year in various formats
    if (preg_match('/([a-zA-Z]+)[^\d]*(\d{4})/i', $dateStr, $matches)) {
        $month = $matches[1];
        $year = $matches[2];
        
        // Map of month names to numbers
        $months = array(
            'january' => '01', 'february' => '02', 'march' => '03',
            'april' => '04', 'may' => '05', 'june' => '06',
            'july' => '07', 'august' => '08', 'september' => '09',
            'october' => '10', 'november' => '11', 'december' => '12',
            // Add abbreviated months
            'jan' => '01', 'feb' => '02', 'mar' => '03',
            'apr' => '04', 'jun' => '06', 'jul' => '07',
            'aug' => '08', 'sep' => '09', 'sept' => '09',
            'oct' => '10', 'nov' => '11', 'dec' => '12'
        );
        
        $monthLower = strtolower($month);
        if (isset($months[$monthLower])) {
            echo "<p class='info'>Converting date: Found month '$month' ($monthLower) = {$months[$monthLower]}, year = $year</p>";
            flushOutput();
            return $year . '-' . $months[$monthLower] . '-01';
        }
    }
    
    // Case 6: Just try to find a year as last resort
    if (preg_match('/(\d{4})/', $dateStr, $matches)) {
        $year = $matches[1];
        echo "<p class='info'>Converting date: Extracted year $year from '$dateStr'</p>";
        flushOutput();
        return $year . '-01-01';
    }
    
    // Case 7: Other formats that PHP's strtotime can handle
    try {
        $timestamp = strtotime($dateStr);
        if ($timestamp !== false) {
            $result = date('Y-m-d', $timestamp);
            echo "<p class='info'>Converting date: Successfully parsed '$dateStr' to '$result' using strtotime</p>";
            flushOutput();
            return $result;
        }
    } catch (Exception $e) {
        echo "<p class='warning'>Converting date: Failed to parse '$dateStr' using strtotime: " . $e->getMessage() . "</p>";
        flushOutput();
    }
    
    // If we get here, all conversion attempts failed
    echo "<p class='error'>Converting date: Unable to parse date string '$dateStr' into MySQL format</p>";
    flushOutput();
    
    // If all else fails, extract the year if possible
    if (preg_match('/(\d{4})/', $dateStr, $matches)) {
        return $matches[1] . '-01-01';
    }
    
    // If all else fails, return null
    return null;
}

// Set page variables for header
$pageTitle = 'Import Tool';
$currentPage = 'import';
$pageDescription = '';

// Include header
require_once '../admin/includes/header.php';
?>

<div class="container">
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header">
                    <h2 class="mb-0">Content Import Tool</h2>
                </div>
                <div class="card-body">
                    <?php if (isset($_POST['import']) && !empty($_POST['content_type']) && !empty($_POST['source_path'])): ?>
                        <?php
                        $contentType = $_POST['content_type'];
                        $sourcePath = $_POST['source_path'];
                        $sourceType = $_POST['source_type'] ?? 'child';
                        $cleanFirst = isset($_POST['clean_first']) && $_POST['clean_first'] === '1';
                        
                        // Start output section for import results
                        echo '<div class="import-results">';
                        echo '<h2>Starting Import: ' . htmlspecialchars($contentType) . ' (' . htmlspecialchars($sourceType) . ')</h2>';
                        flushOutput();
                        
                        // Clean existing data if requested
                        if ($cleanFirst) {
                            cleanContentData($db, $contentType, $sourceType);
                        }
                        
                        // Process the import based on content type
                        echo '<h2>Importing ' . htmlspecialchars($contentType) . ' (' . htmlspecialchars($sourceType) . ')</h2>';
                        echo '<p class="text-muted">Import source: ' . htmlspecialchars($sourcePath) . '</p>';
                        flushOutput();
                        
                        // Statistics counters
                        $created = 0;
                        $updated = 0;
                        $skipped = 0;
                        $errors = 0;
                        
                        if ($contentType === 'books') {
                            // Get list of potential book directories
                            if (!is_dir($sourcePath)) {
                                echo '<div class="alert alert-danger">Source path is not a valid directory: ' . htmlspecialchars($sourcePath) . '</div>';
                                flushOutput();
                            } else {
                                $bookDirs = glob("$sourcePath/*", GLOB_ONLYDIR);
                                
                                echo '<p>Found ' . count($bookDirs) . ' potential book directories</p>';
                                flushOutput();
                                
                                // Process each book directory
                                foreach ($bookDirs as $bookDir) {
                                    $result = processBook($db, $bookDir);
                                    
                                    if ($result) {
                                        if ($result['success']) {
                                            if ($result['action'] === 'created') {
                                                $created++;
                                            } else {
                                                $updated++;
                                            }
                                        } else {
                                            $errors++;
                                        }
                                    } else {
                                        $skipped++;
                                    }
                                }
                            }
                        }
                        
                        // Display summary
                        echo '<h2>Import Complete!</h2>';
                        echo '<div class="alert alert-info">';
                        echo '<h4>Summary:</h4>';
                        echo '<ul>';
                        echo '<li>Created: ' . $created . ' ' . htmlspecialchars($contentType) . '</li>';
                        echo '<li>Updated: ' . $updated . ' ' . htmlspecialchars($contentType) . '</li>';
                        echo '<li>Skipped: ' . $skipped . ' ' . htmlspecialchars($contentType) . '</li>';
                        echo '<li>Errors: ' . $errors . ' ' . htmlspecialchars($contentType) . '</li>';
                        echo '</ul>';
                        echo '</div>';
                        flushOutput();
                        
                        echo '</div>'; // End import-results div
                        
                        // Add button to return to form
                        echo '<p class="mt-4"><a href="direct_import.php" class="btn btn-primary">Return to Import Form</a></p>';
                        flushOutput();
                        ?>
                    <?php else: ?>
                        <form method="post" class="needs-validation" novalidate>
                            <div class="mb-3">
                                <label for="content_type" class="form-label">Content Type</label>
                                <select name="content_type" id="content_type" class="form-control" required>
                                    <option value="">Select Content Type</option>
                                    <option value="books">Books</option>
                                </select>
                                <div class="invalid-feedback">Please select a content type.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="source_type" class="form-label">Source Type</label>
                                <select name="source_type" id="source_type" class="form-control" required>
                                    <option value="retail">Retail Publisher</option>
                                </select>
                                <div class="invalid-feedback">Please select a source type.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="source_path" class="form-label">Source Path</label>
                                <input type="text" name="source_path" id="source_path" class="form-control" required
                                       value="/home/stories/api.storiesfromtheweb.org/public/../_wp migration/wp-md/custom/book">
                                <div class="form-text">Directory containing content markdown files</div>
                                <div class="invalid-feedback">Please enter a valid source path.</div>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" name="clean_first" id="clean_first" class="form-check-input" value="1" checked>
                                <label class="form-check-label" for="clean_first">Clean existing data before import</label>
                            </div>
                            
                            <button type="submit" name="import" class="btn btn-primary">Import Content</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .import-results {
        padding: 15px;
        background-color: #f8f9fa;
        border-radius: 4px;
        max-height: 600px;
        overflow-y: auto;
    }
    
    .import-results p {
        margin-bottom: 0.5rem;
        padding: 0.25rem 0;
    }
    
    .import-results .success {
        color: #0c5460;
        background-color: #d1ecf1;
        padding: 0.5rem;
        border-radius: 3px;
        border-left: 3px solid #0c5460;
    }
    
    .import-results .info {
        color: #383d41;
        background-color: #e2e3e5;
        padding: 0.5rem;
        border-radius: 3px;
        border-left: 3px solid #383d41;
    }
    
    .import-results .warning {
        color: #856404;
        background-color: #fff3cd;
        padding: 0.5rem;
        border-radius: 3px;
        border-left: 3px solid #856404;
    }
    
    .import-results .error {
        color: #721c24;
        background-color: #f8d7da;
        padding: 0.5rem;
        border-radius: 3px;
        border-left: 3px solid #721c24;
    }
    
    .import-results h2, .import-results h3 {
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px solid #dee2e6;
    }
</style>

<script>
    // Form validation script
    (function() {
        'use strict';
        
        // Add event listener for form submission
        document.querySelector('.needs-validation').addEventListener('submit', function(event) {
            if (!this.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            this.classList.add('was-validated');
        }, false);
    })();
</script>

<?php require_once '../admin/includes/footer.php'; ?>