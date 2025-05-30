<?php
/**
 * Book Discovery Process
 * 
 * Handles the discovery and import of books from URLs
 */

// Include auth check
require_once '../includes/auth-check.php';

// Include database connection
require_once '../includes/db-connect.php';

// Include existing import functions
require_once 'book-import-process.php';

// Include enrichment functions
require_once 'book-import-validate/functions/data-enrichment-functions.php';

// Include discovery engine
require_once 'book-discovery/BookDiscoveryEngine.php';

// Set up error handling
ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit(300); // 5 minutes
ini_set('output_buffering', 'off');
ini_set('implicit_flush', true);
ob_implicit_flush(true);

// Function to flush output for discovery process
function flushDiscoveryOutput() {
    if (ob_get_level() > 0) {
        ob_flush();
    }
    flush();
}

// Set page variables
$pageTitle = 'Book Discovery Process';
$currentPage = 'book-import-tool';

// Include header
include_once '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h1><?php echo $pageTitle; ?></h1>
            
            <div class="card">
                <div class="card-body">
                    <?php
                    try {
                        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['discovery_url'])) {
                            $url = filter_var($_POST['discovery_url'], FILTER_VALIDATE_URL);
                            $ageFilter = $_POST['age_filter'] ?? '';
                            $autoEnrich = isset($_POST['auto_enrich']) ? true : false;
                            $importToDb = isset($_POST['import_to_db']) ? true : false;
                            
                            if (!$url) {
                                throw new Exception("Invalid URL provided");
                            }
                            
                            echo "<h3>Discovering books from: " . htmlspecialchars($url) . "</h3>";
                            flushDiscoveryOutput();
                            
                            // Initialize discovery engine
                            $discoveryEngine = new BookDiscoveryEngine($db);
                            
                            // Discover books
                            echo "<p>Starting discovery process...</p>";
                            flushDiscoveryOutput();
                            
                            $books = $discoveryEngine->discoverFromURL($url);
                            
                            echo "<p class='text-success'>Found " . count($books) . " books</p>";
                            flushDiscoveryOutput();
                            
                            // Filter by age if specified
                            if ($ageFilter) {
                                $originalCount = count($books);
                                $books = array_filter($books, function($book) use ($ageFilter) {
                                    $bookAge = strtolower($book['age_range'] ?? '');
                                    $filterAge = strtolower($ageFilter);
                                    return strpos($bookAge, $filterAge) !== false || 
                                           strpos($bookAge, str_replace('-', ' to ', $filterAge)) !== false;
                                });
                                
                                if ($originalCount > count($books)) {
                                    echo "<p>Filtered to " . count($books) . " books matching age range: {$ageFilter}</p>";
                                    flushDiscoveryOutput();
                                }
                            }
                            
                            if (empty($books)) {
                                echo "<p class='text-warning'>No books found matching your criteria.</p>";
                            } else {
                                // Display discovered books
                                echo "<h4>Discovered Books:</h4>";
                                echo "<div class='table-responsive'>";
                                echo "<table class='table table-striped'>";
                                echo "<thead><tr>";
                                echo "<th>Title</th>";
                                echo "<th>Author</th>";
                                echo "<th>Age Range</th>";
                                echo "<th>Year</th>";
                                echo "<th>ISBN</th>";
                                echo "<th>Tags</th>";
                                echo "<th>Status</th>";
                                echo "</tr></thead>";
                                echo "<tbody>";
                                
                                $importCount = 0;
                                $skipCount = 0;
                                $errorCount = 0;
                                
                                foreach ($books as $index => $book) {
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($book['title'] ?? 'Unknown') . "</td>";
                                    echo "<td>" . htmlspecialchars($book['author'] ?? 'Unknown') . "</td>";
                                    echo "<td>" . htmlspecialchars($book['age_range'] ?? '-') . "</td>";
                                    echo "<td>" . htmlspecialchars($book['year'] ?? '-') . "</td>";
                                    echo "<td>" . htmlspecialchars($book['isbn'] ?? $book['isbn13'] ?? '-') . "</td>";
                                    echo "<td>";
                                    if (!empty($book['tags'])) {
                                        $tags = is_array($book['tags']) ? $book['tags'] : [$book['tags']];
                                        echo htmlspecialchars(implode(', ', $tags));
                                    } else {
                                        echo '-';
                                    }
                                    echo "</td>";
                                    echo "<td>";
                                    
                                    // Check if book exists
                                    $exists = $discoveryEngine->bookExists(
                                        $book['isbn'] ?? $book['isbn13'] ?? '', 
                                        $book['title'],
                                        $book['author'] ?? ''
                                    );
                                    
                                    if ($exists) {
                                        echo "<span class='badge badge-warning'>Already Exists</span>";
                                        $skipCount++;
                                    } elseif ($importToDb) {
                                        // Enrich if requested
                                        if ($autoEnrich && (empty($book['isbn']) || empty($book['isbn13']))) {
                                            $enrichedData = getEnrichedBookData(
                                                $book['title'],
                                                $book['author'] ?? '',
                                                $book['isbn'] ?? $book['isbn13'] ?? ''
                                            );
                                            
                                            // Merge enriched data
                                            if (!empty($enrichedData['fields'])) {
                                                foreach ($enrichedData['fields'] as $field => $data) {
                                                    if (!empty($data['new_data']['value']) && empty($book[$field])) {
                                                        $book[$field] = $data['new_data']['value'];
                                                    }
                                                }
                                            }
                                        }
                                        
                                        // Import the book
                                        try {
                                            $result = importBook($db, $book);
                                            if ($result['success']) {
                                                echo "<span class='badge badge-success'>Imported</span>";
                                                $importCount++;
                                            } else {
                                                echo "<span class='badge badge-danger'>Failed</span>";
                                                $errorCount++;
                                            }
                                        } catch (Exception $e) {
                                            echo "<span class='badge badge-danger'>Error</span>";
                                            $errorCount++;
                                            error_log("Import error: " . $e->getMessage());
                                        }
                                    } else {
                                        echo "<span class='badge badge-info'>Ready to Import</span>";
                                    }
                                    
                                    echo "</td>";
                                    echo "</tr>";
                                    
                                    // Flush output periodically
                                    if ($index % 5 == 0) {
                                        flushDiscoveryOutput();
                                    }
                                }
                                
                                echo "</tbody>";
                                echo "</table>";
                                echo "</div>";
                                
                                // Summary
                                echo "<div class='alert alert-info mt-4'>";
                                echo "<h5>Summary:</h5>";
                                echo "<ul>";
                                echo "<li>Total books discovered: " . count($books) . "</li>";
                                if ($importToDb) {
                                    echo "<li>Successfully imported: {$importCount}</li>";
                                    echo "<li>Skipped (already exist): {$skipCount}</li>";
                                    echo "<li>Errors: {$errorCount}</li>";
                                }
                                echo "</ul>";
                                echo "</div>";
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
                                echo "<form method='post' action='book-discovery-process.php' style='display:inline;'>";
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
                            echo "<p class='text-danger'>Invalid request. Please use the discovery form.</p>";
                        }
                        
                    } catch (Exception $e) {
                        echo "<div class='alert alert-danger'>";
                        echo "<strong>Error:</strong> " . htmlspecialchars($e->getMessage());
                        echo "</div>";
                        error_log("Book discovery error: " . $e->getMessage());
                    }
                    ?>
                    
                    <div class="mt-4">
                        <a href="book-import-tool.php?tab=discovery" class="btn btn-primary">
                            <i class="fas fa-arrow-left"></i> Back to Discovery
                        </a>
                    </div>
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
});
</script>

<?php
// Include footer
include_once '../includes/footer.php';
?>