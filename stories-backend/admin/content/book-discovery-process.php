<?php
/**
 * Book Discovery Process Page
 * Handles book discovery from URLs and imports selected books
 */

// Include auth check
require_once '../includes/auth-check.php';

// Include database connection
require_once '../includes/db-connect.php';

// Only include what we absolutely need to avoid function conflicts
// We'll define the functions we need inline to avoid conflicts

/**
 * Check if a book already exists in the database
 */
function bookExists($db, $title, $author) {
    $stmt = $db->prepare("
        SELECT COUNT(*)
        FROM directory_items di
        JOIN books b ON di.id = b.directory_item_id
        WHERE di.title = ? AND b.author = ?
    ");
    $stmt->execute([$title, $author]);
    return $stmt->fetchColumn() > 0;
}

/**
 * Import a book into the database
 */
function importBook($db, $bookData) {
    try {
        $db->beginTransaction();
        
        // Insert into directory_items
        $stmt = $db->prepare("
            INSERT INTO directory_items (title, slug, type, status, created_at, updated_at)
            VALUES (?, ?, 'book', 'published', NOW(), NOW())
        ");
        
        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $bookData['title']));
        $stmt->execute([$bookData['title'], $slug]);
        $directoryItemId = $db->lastInsertId();
        
        // Insert into books
        $stmt = $db->prepare("
            INSERT INTO books (directory_item_id, author, description, age_range, created_at, updated_at)
            VALUES (?, ?, ?, ?, NOW(), NOW())
        ");
        
        $stmt->execute([
            $directoryItemId,
            $bookData['author'] ?? 'Unknown',
            $bookData['description'] ?? '',
            $bookData['age_range'] ?? ''
        ]);
        
        $db->commit();
        return $directoryItemId;
        
    } catch (Exception $e) {
        $db->rollback();
        error_log("Failed to import book: " . $e->getMessage());
        return false;
    }
}

// Include enrichment functions
require_once 'book-import-validate/functions/data-enrichment-functions.php';

// Include discovery engine
require_once 'book-discovery/BookDiscoveryEngine.php';

// Start output buffering for progress updates
ob_start();

// Function to flush output for discovery process
if (!function_exists('flushDiscoveryOutput')) {
    function flushDiscoveryOutput() {
        if (ob_get_level() > 0) {
            ob_flush();
        }
        flush();
    }
}

// Set page variables
$pageTitle = 'Book Discovery Process';
$currentPage = 'book-discovery-process';

// Include header
include_once '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h2>Book Discovery Process</h2>
                </div>
                <div class="card-body">
                    <?php
                    // Handle form submission
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        $action = $_POST['action'] ?? '';
                        
                        try {
                            if ($action === 'import_selected') {
                                // Handle book import
                                $selectedBooks = $_POST['selected_books'] ?? [];
                                $bookData = json_decode($_POST['books_data'] ?? '[]', true);
                                
                                if (!empty($selectedBooks) && !empty($bookData)) {
                                    $importCount = 0;
                                    $failCount = 0;
                                    
                                    echo "<h3>Import Progress</h3>";
                                    echo "<div class='progress mb-3'><div class='progress-bar' role='progressbar' style='width: 0%' id='importProgress'></div></div>";
                                    echo "<div id='importLog'>";
                                    
                                    foreach ($selectedBooks as $index) {
                                        if (isset($bookData[$index])) {
                                            $book = $bookData[$index];
                                            
                                            // Check if book already exists
                                            if (!bookExists($db, $book['title'], $book['author'])) {
                                                // Import the book
                                                $bookId = importBook($db, $book);
                                                
                                                if ($bookId) {
                                                    $importCount++;
                                                    echo "<p class='text-success'>✓ Imported: " . htmlspecialchars($book['title']) . "</p>";
                                                } else {
                                                    $failCount++;
                                                    echo "<p class='text-danger'>✗ Failed to import: " . htmlspecialchars($book['title']) . "</p>";
                                                }
                                            } else {
                                                echo "<p class='text-warning'>⚠ Skipped (already exists): " . htmlspecialchars($book['title']) . "</p>";
                                            }
                                            
                                            // Update progress bar
                                            $progress = (($index + 1) / count($selectedBooks)) * 100;
                                            echo "<script>document.getElementById('importProgress').style.width = '$progress%';</script>";
                                            flushDiscoveryOutput();
                                        }
                                    }
                                    
                                    echo "</div>";
                                    echo "<div class='alert alert-info mt-3'>";
                                    echo "<strong>Import Complete!</strong><br>";
                                    echo "Successfully imported: $importCount books<br>";
                                    echo "Failed to import: $failCount books";
                                    echo "</div>";
                                    
                                    echo "<a href='book-import-tool.php?tab=discovery' class='btn btn-primary'>Back to Discovery</a>";
                                } else {
                                    echo "<div class='alert alert-danger'>No books selected for import.</div>";
                                    echo "<a href='book-import-tool.php?tab=discovery' class='btn btn-primary'>Back to Discovery</a>";
                                }
                                
                            } elseif ($action === 'discover_from_url') {
                                // Handle URL discovery
                                $url = $_POST['url'] ?? '';
                                $limit = intval($_POST['limit'] ?? 25);
                                
                                if (!empty($url)) {
                                    // Initialize discovery engine
                                    $engine = new BookDiscoveryEngine($db);
                                    
                                    echo "<h2>Book Discovery Preview</h2>";
                                    echo "<div class='alert alert-info'>";
                                    echo "<i class='fas fa-info-circle'></i> <strong>Preview Mode:</strong> The books below have been discovered but NOT yet imported. ";
                                    echo "Review the information and select which books you want to add to your library.";
                                    echo "</div>";
                                    echo "<p>Discovering books from: " . htmlspecialchars($url) . "</p>";
                                    flushDiscoveryOutput();
                                    
                                    $books = $engine->discoverFromUrl($url, $limit);
                                    
                                    if (!empty($books)) {
                                        echo "<p>Found " . count($books) . " books from the website:</p>";
                                        
                                        echo "<form method='post' action='' onsubmit='return confirmImport();'>";
                                        echo "<input type='hidden' name='action' value='import_selected'>";
                                        
                                        echo "<table class='table table-striped'>";
                                        echo "<thead>";
                                        echo "<tr>";
                                        echo "<th><input type='checkbox' id='selectAll'> Select</th>";
                                        echo "<th>Title</th>";
                                        echo "<th>Author</th>";
                                        echo "<th>Description</th>";
                                        echo "<th>Age Range</th>";
                                        echo "<th>Database Status</th>";
                                        echo "</tr>";
                                        echo "</thead>";
                                        echo "<tbody>";
                                        
                                        $booksJson = htmlspecialchars(json_encode($books), ENT_QUOTES, 'UTF-8');
                                        foreach ($books as $index => $book) {
                                            $exists = bookExists($db, $book['title'], $book['author']);
                                            
                                            echo "<tr>";
                                            echo "<td>";
                                            if (!$exists) {
                                                echo "<input type='checkbox' name='selected_books[]' value='$index'>";
                                            } else {
                                                echo "<input type='checkbox' disabled>";
                                            }
                                            echo "</td>";
                                            echo "<td>" . htmlspecialchars($book['title']) . "</td>";
                                            echo "<td>" . htmlspecialchars($book['author'] ?? 'Unknown') . "</td>";
                                            echo "<td>" . htmlspecialchars(substr($book['description'] ?? 'No description', 0, 150)) . (strlen($book['description'] ?? '') > 150 ? '...' : '') . "</td>";
                                            echo "<td>" . htmlspecialchars($book['age_range'] ?? 'Not specified') . "</td>";
                                            echo "<td>";
                                            if ($exists) {
                                                echo "<span class='badge badge-warning'><i class='fas fa-exclamation-triangle'></i> Already in database</span>";
                                            } else {
                                                echo "<span class='badge badge-success'><i class='fas fa-plus-circle'></i> Ready to import</span>";
                                            }
                                            echo "</td>";
                                            echo "</tr>";
                                        }
                                        
                                        echo "</tbody>";
                                        echo "</table>";
                                        
                                        echo "<input type='hidden' name='books_data' value='$booksJson'>";
                                        
                                        echo "<div class='mt-3'>";
                                        echo "<div class='alert alert-warning'>";
                                        echo "<i class='fas fa-exclamation-triangle'></i> <strong>Confirm Import:</strong> ";
                                        echo "Clicking the button below will add the selected books to your database. This action cannot be undone.";
                                        echo "</div>";
                                        echo "<button type='submit' name='action' value='import_selected' class='btn btn-primary btn-lg'>";
                                        echo "<i class='fas fa-download'></i> Import Selected Books to Database";
                                        echo "</button>";
                                        echo "<a href='book-import-tool.php?tab=discovery' class='btn btn-secondary btn-lg ml-2'>";
                                        echo "<i class='fas fa-arrow-left'></i> Back to Discovery";
                                        echo "</a>";
                                        echo "</div>";
                                        echo "</form>";
                                    } else {
                                        echo "<div class='alert alert-warning'>No books found at the specified URL.</div>";
                                        echo "<a href='book-import-tool.php?tab=discovery' class='btn btn-primary'>Back to Discovery</a>";
                                    }
                                } else {
                                    echo "<div class='alert alert-danger'>Please provide a valid URL.</div>";
                                    echo "<a href='book-import-tool.php?tab=discovery' class='btn btn-primary'>Back to Discovery</a>";
                                }
                                
                            } else {
                                echo "<div class='alert alert-danger'>Invalid request. Please use the discovery form.</div>";
                                echo "<a href='book-import-tool.php?tab=discovery' class='btn btn-primary'>Back to Discovery</a>";
                            }
                            
                        } catch (Exception $e) {
                            echo "<div class='alert alert-danger'>";
                            echo "<strong>Error:</strong> " . htmlspecialchars($e->getMessage());
                            echo "</div>";
                            echo "<a href='book-import-tool.php?tab=discovery' class='btn btn-primary'>Back to Discovery</a>";
                            error_log("Book discovery error: " . $e->getMessage());
                        }
                    } else {
                        // Not a POST request, show error
                        echo "<div class='alert alert-danger'>Invalid request. Please use the discovery form.</div>";
                        echo "<a href='book-import-tool.php?tab=discovery' class='btn btn-primary'>Back to Discovery</a>";
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function confirmImport() {
    const checkboxes = document.querySelectorAll('input[name="selected_books[]"]:checked');
    if (checkboxes.length === 0) {
        alert('Please select at least one book to import.');
        return false;
    }
    
    const newBooks = Array.from(checkboxes).filter(cb => {
        const row = cb.closest('tr');
        return row.querySelector('.badge-success') !== null;
    }).length;
    
    if (newBooks === 0) {
        alert('All selected books already exist in the database. Please select new books to import.');
        return false;
    }
    
    return confirm(`Are you sure you want to import ${newBooks} new book(s) to your database?`);
}

// Auto-uncheck books that already exist
document.addEventListener('DOMContentLoaded', function() {
    const rows = document.querySelectorAll('table tbody tr');
    rows.forEach(row => {
        if (row.querySelector('.badge-warning')) {
            const checkbox = row.querySelector('input[type="checkbox"]');
            if (checkbox) {
                checkbox.checked = false;
                checkbox.disabled = true;
                row.style.opacity = '0.6';
            }
        }
    });
    
    // Select all functionality
    const selectAllCheckbox = document.getElementById('selectAll');
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('input[name="selected_books[]"]:not(:disabled)');
            checkboxes.forEach(cb => cb.checked = this.checked);
        });
    }
});
</script>

<?php
// Include footer
include_once '../includes/footer.php';
?>