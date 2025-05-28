<?php
/**
 * Comprehensive Duplicate Cleanup Tool
 * 3-Stage Process: Analyze → Reassign → Update
 * Covers all dropdown fields from directory-item-form.php
 */

// Include auth check
require_once '../includes/auth-check.php';

// Include database connection
require_once '../includes/db-connect.php';

// Set page variables for header
$pageTitle = 'Comprehensive Duplicate Cleanup';
$currentPage = 'comprehensive-cleanup';
$pageDescription = 'Analyze and clean up duplicate entries across all dropdown fields';

// Include header
require_once '../includes/header.php';

// Set execution time limit for large operations
set_time_limit(300);

// Get current stage from URL parameter
$stage = $_GET['stage'] ?? 'analyze';
$validStages = ['analyze', 'reassign', 'update'];
if (!in_array($stage, $validStages)) {
    $stage = 'analyze';
}

// Helper function to check if column exists
function columnExists($table, $column) {
    global $db;
    try {
        $stmt = $db->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
        return $stmt->fetch() !== false;
    } catch (Exception $e) {
        return false;
    }
}

// Helper function to get table info
function getTableInfo($table) {
    global $db;
    try {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        return $stmt->fetch() !== false;
    } catch (Exception $e) {
        return false;
    }
}

?>

<style>
    .debug-output {
        background-color: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 0.375rem;
        padding: 1rem;
        margin: 1rem 0;
        font-family: 'Courier New', monospace;
        white-space: pre-wrap;
    }
    .success { color: #198754; }
    .error { color: #dc3545; }
    .info { color: #0dcaf0; }
    .duplicate-group {
        background: #fff3cd;
        border: 1px solid #ffeaa7;
        border-radius: 0.375rem;
        padding: 1rem;
        margin: 0.5rem 0;
    }
    .stage-nav .btn {
        margin-right: 0.5rem;
    }
</style>
    <div class="container-fluid">
        <!-- Stage Navigation -->
        <div class="mb-4">
            <h1 class="mb-3">🔧 Comprehensive Duplicate Cleanup Tool</h1>
            <p class="text-muted">Analyzes all dropdown fields from directory-item-form.php for duplicates</p>

            <div class="stage-nav mb-3">
                <a href="?stage=analyze" class="btn <?php echo $stage === 'analyze' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                    📊 Stage 1: Analyze Duplicates
                </a>
                <a href="?stage=reassign" class="btn <?php echo $stage === 'reassign' ? 'btn-warning' : 'btn-outline-warning'; ?>">
                    🔄 Stage 2: Reassign References
                </a>
                <a href="?stage=update" class="btn <?php echo $stage === 'update' ? 'btn-success' : 'btn-outline-success'; ?>">
                    ✅ Stage 3: Update Database
                </a>
            </div>

            <div class="alert alert-warning">
                <strong>⚠️ Warning:</strong> This tool will modify your database. Make sure you have a backup before proceeding.
            </div>
        </div>

        <?php if ($stage === 'analyze'): ?>
            <div class="row">
                <div class="col-12">
                    <h2>📊 Stage 1: Analyze All Duplicates</h2>
                    <p>Scanning all lookup tables and text fields for potential duplicates...</p>
                </div>
            </div>

            <?php
            // 1. AUTHORS (Publishers and Book Authors)
            echo '<div class="card mb-4">';
            echo '<div class="card-header"><h3>👥 Authors & Publishers Analysis</h3></div>';
            echo '<div class="card-body">';

            try {
                // Check which column exists for type
                $hasTypeColumn = columnExists('authors', 'type');
                $hasAuthorTypeColumn = columnExists('authors', 'author_type');

                echo '<div class="debug-output">';
                echo "Authors table structure:\n";
                echo "- 'type' column exists: " . ($hasTypeColumn ? 'YES' : 'NO') . "\n";
                echo "- 'author_type' column exists: " . ($hasAuthorTypeColumn ? 'YES' : 'NO') . "\n";
                echo '</div>';

                // Find exact duplicates - fix GROUP BY issue
                $sql = "
                    SELECT
                        MIN(name) as name,
                        COUNT(*) as count,
                        GROUP_CONCAT(id) as ids
                    FROM authors
                    GROUP BY LOWER(TRIM(name))
                    HAVING count > 1
                    ORDER BY count DESC, MIN(name)
                ";
                $stmt = $db->query($sql);
                $duplicateAuthors = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (empty($duplicateAuthors)) {
                    echo '<div class="alert alert-success">✅ No exact duplicate authors found</div>';
                } else {
                    echo '<div class="alert alert-warning">⚠️ Found ' . count($duplicateAuthors) . ' groups of duplicate authors:</div>';

                    foreach ($duplicateAuthors as $group) {
                        echo '<div class="duplicate-group">';
                        echo '<strong>' . htmlspecialchars($group['name']) . '</strong> (' . $group['count'] . ' duplicates)<br>';
                        echo 'IDs: ' . $group['ids'] . '<br>';

                        // Show usage
                        $ids = explode(',', $group['ids']);
                        foreach ($ids as $id) {
                            $id = trim($id);

                            // Check books.publisher_id
                            if (columnExists('books', 'publisher_id')) {
                                $stmt = $db->prepare("SELECT COUNT(*) FROM books WHERE publisher_id = ?");
                                $stmt->execute([$id]);
                                $publisherBooks = $stmt->fetchColumn();
                                if ($publisherBooks > 0) {
                                    echo "ID $id: $publisherBooks books (as publisher)<br>";
                                }
                            }

                            // Check story_authors
                            if (getTableInfo('story_authors')) {
                                $stmt = $db->prepare("SELECT COUNT(*) FROM story_authors WHERE author_id = ?");
                                $stmt->execute([$id]);
                                $storyCount = $stmt->fetchColumn();
                                if ($storyCount > 0) {
                                    echo "ID $id: $storyCount stories (as author)<br>";
                                }
                            }
                        }
                        echo '</div>';
                    }
                }

            } catch (Exception $e) {
                echo '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }

            echo '</div></div>';

            // 2. PUBLISHER RELATIONSHIPS TEST & CLEANUP
            echo '<div class="card mb-4">';
            echo '<div class="card-header"><h3>🔗 Publisher Relationships Analysis & Cleanup</h3></div>';
            echo '<div class="card-body">';

            try {
                // Test a specific book (Demon Dentist)
                $testBookId = 2105;
                $stmt = $db->prepare("
                    SELECT b.*, di.title as item_title
                    FROM books b
                    JOIN directory_items di ON b.directory_item_id = di.id
                    WHERE b.directory_item_id = ?
                ");
                $stmt->execute([$testBookId]);
                $testBook = $stmt->fetch();

                if ($testBook) {
                    echo '<h5>📋 Test Case: ' . htmlspecialchars($testBook['item_title']) . ' (ID: ' . $testBookId . ')</h5>';
                    echo '<div class="debug-output">';
                    echo "Publisher Field: " . ($testBook['publisher'] ?: 'NULL') . "\n";
                    echo "Publisher ID: " . ($testBook['publisher_id'] ?: 'NULL') . "\n";
                    echo '</div>';

                    // Check if publisher exists in authors table
                    if ($testBook['publisher']) {
                        $publisherName = trim($testBook['publisher']);

                        // Check for exact match
                        if ($hasAuthorTypeColumn) {
                            $stmt = $db->prepare("SELECT id, name, author_type FROM authors WHERE name = ?");
                        } else {
                            $stmt = $db->prepare("SELECT id, name FROM authors WHERE name = ?");
                        }
                        $stmt->execute([$publisherName]);
                        $exactMatch = $stmt->fetch();

                        if ($exactMatch) {
                            echo '<div class="alert alert-success">';
                            echo "✅ Exact publisher match found: {$exactMatch['name']} (ID: {$exactMatch['id']})";
                            if (isset($exactMatch['author_type'])) {
                                echo " - Type: {$exactMatch['author_type']}";
                            }
                            echo '</div>';

                            // Check if relationship needs updating
                            if ($testBook['publisher_id'] != $exactMatch['id']) {
                                echo '<div class="alert alert-warning">';
                                echo "⚠️ Publisher relationship needs updating: Current ID ({$testBook['publisher_id']}) → Should be ({$exactMatch['id']})";
                                echo '<br><button onclick="updatePublisherRelationship(' . $testBookId . ', ' . $exactMatch['id'] . ')" class="btn btn-sm btn-primary mt-2">Fix Relationship</button>';
                                echo '</div>';
                            } else {
                                echo '<div class="alert alert-success">✅ Publisher relationship is correct</div>';
                            }
                        } else {
                            // Check for similar matches
                            $stmt = $db->prepare("SELECT id, name FROM authors WHERE name LIKE ?");
                            $stmt->execute(['%' . $publisherName . '%']);
                            $similarMatches = $stmt->fetchAll();

                            if (!empty($similarMatches)) {
                                echo '<div class="alert alert-info">';
                                echo "🔍 Similar publisher matches found:";
                                echo '<ul>';
                                foreach ($similarMatches as $match) {
                                    echo '<li>' . htmlspecialchars($match['name']) . ' (ID: ' . $match['id'] . ')';
                                    echo ' <button onclick="updatePublisherRelationship(' . $testBookId . ', ' . $match['id'] . ')" class="btn btn-xs btn-outline-primary">Use This</button></li>';
                                }
                                echo '</ul>';
                                echo '</div>';
                            } else {
                                echo '<div class="alert alert-warning">';
                                echo "⚠️ No publisher match found for: " . htmlspecialchars($publisherName);
                                echo '<br><button onclick="createPublisher(\'' . htmlspecialchars($publisherName) . '\', ' . $testBookId . ')" class="btn btn-sm btn-success mt-2">Create Publisher</button>';
                                echo '</div>';
                            }
                        }
                    }
                } else {
                    echo '<div class="alert alert-warning">Test book (ID: ' . $testBookId . ') not found</div>';
                }

                // Show books with missing publisher relationships
                echo '<h5>📚 Books with Missing Publisher Relationships:</h5>';
                $stmt = $db->query("
                    SELECT b.directory_item_id, di.title, b.publisher
                    FROM books b
                    JOIN directory_items di ON b.directory_item_id = di.id
                    WHERE b.publisher IS NOT NULL
                    AND b.publisher != ''
                    AND b.publisher_id IS NULL
                    LIMIT 10
                ");
                $missingRelationships = $stmt->fetchAll();

                if (empty($missingRelationships)) {
                    echo '<div class="alert alert-success">✅ All books with publishers have relationships set</div>';
                } else {
                    echo '<div class="alert alert-warning">⚠️ Found ' . count($missingRelationships) . ' books with missing publisher relationships (showing first 10):</div>';
                    echo '<div class="table-responsive">';
                    echo '<table class="table table-sm">';
                    echo '<thead><tr><th>Book</th><th>Publisher</th><th>Action</th></tr></thead>';
                    echo '<tbody>';
                    foreach ($missingRelationships as $book) {
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($book['title']) . '</td>';
                        echo '<td>' . htmlspecialchars($book['publisher']) . '</td>';
                        echo '<td><button onclick="fixPublisherRelationship(' . $book['directory_item_id'] . ')" class="btn btn-xs btn-primary">Fix</button></td>';
                        echo '</tr>';
                    }
                    echo '</tbody></table>';
                    echo '</div>';
                }

            } catch (Exception $e) {
                echo '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }

            echo '</div></div>';

            // 3. TAGS (Genres)
            echo '<div class="card mb-4">';
            echo '<div class="card-header"><h3>🏷️ Tags (Genres) Analysis</h3></div>';
            echo '<div class="card-body">';

            try {
                if (getTableInfo('tags')) {
                    $sql = "
                        SELECT
                            MIN(name) as name,
                            COUNT(*) as count,
                            GROUP_CONCAT(id) as ids
                        FROM tags
                        GROUP BY LOWER(TRIM(name))
                        HAVING count > 1
                        ORDER BY count DESC, MIN(name)
                    ";
                    $stmt = $db->query($sql);
                    $duplicateTags = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    if (empty($duplicateTags)) {
                        echo '<div class="alert alert-success">✅ No exact duplicate tags found</div>';
                    } else {
                        echo '<div class="alert alert-warning">⚠️ Found ' . count($duplicateTags) . ' groups of duplicate tags:</div>';

                        foreach ($duplicateTags as $group) {
                            echo '<div class="duplicate-group">';
                            echo '<strong>' . htmlspecialchars($group['name']) . '</strong> (' . $group['count'] . ' duplicates)<br>';
                            echo 'IDs: ' . $group['ids'] . '<br>';

                            // Show usage
                            $ids = explode(',', $group['ids']);
                            foreach ($ids as $id) {
                                $id = trim($id);

                                // Check directory_item_tags
                                if (getTableInfo('directory_item_tags')) {
                                    $stmt = $db->prepare("SELECT COUNT(*) FROM directory_item_tags WHERE tag_id = ?");
                                    $stmt->execute([$id]);
                                    $itemCount = $stmt->fetchColumn();
                                    if ($itemCount > 0) {
                                        echo "ID $id: $itemCount directory items<br>";
                                    }
                                }

                                // Check story_tags
                                if (getTableInfo('story_tags')) {
                                    $stmt = $db->prepare("SELECT COUNT(*) FROM story_tags WHERE tag_id = ?");
                                    $stmt->execute([$id]);
                                    $storyCount = $stmt->fetchColumn();
                                    if ($storyCount > 0) {
                                        echo "ID $id: $storyCount stories<br>";
                                    }
                                }
                            }
                            echo '</div>';
                        }
                    }
                } else {
                    echo '<div class="alert alert-info">Tags table not found</div>';
                }

            } catch (Exception $e) {
                echo '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }

            echo '</div></div>';

            // 3. PRICE RANGES
            echo '<div class="card mb-4">';
            echo '<div class="card-header"><h3>💰 Price Ranges Analysis</h3></div>';
            echo '<div class="card-body">';

            try {
                if (getTableInfo('price_ranges')) {
                    $sql = "
                        SELECT
                            MIN(range_name) as range_name,
                            COUNT(*) as count,
                            GROUP_CONCAT(id) as ids
                        FROM price_ranges
                        GROUP BY LOWER(TRIM(range_name))
                        HAVING count > 1
                        ORDER BY count DESC, MIN(range_name)
                    ";
                    $stmt = $db->query($sql);
                    $duplicatePriceRanges = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    if (empty($duplicatePriceRanges)) {
                        echo '<div class="alert alert-success">✅ No duplicate price ranges found</div>';
                    } else {
                        echo '<div class="alert alert-warning">⚠️ Found ' . count($duplicatePriceRanges) . ' groups of duplicate price ranges:</div>';

                        foreach ($duplicatePriceRanges as $group) {
                            echo '<div class="duplicate-group">';
                            echo '<strong>' . htmlspecialchars($group['range_name']) . '</strong> (' . $group['count'] . ' duplicates)<br>';
                            echo 'IDs: ' . $group['ids'] . '<br>';
                            echo '</div>';
                        }
                    }
                } else {
                    echo '<div class="alert alert-info">Price ranges table not found</div>';
                }

            } catch (Exception $e) {
                echo '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }

            echo '</div></div>';

            // 4. AGE RANGES
            echo '<div class="card mb-4">';
            echo '<div class="card-header"><h3>👶 Age Ranges Analysis</h3></div>';
            echo '<div class="card-body">';

            try {
                // Check both age_ranges table and books.age_range field
                $hasAgeRangesTable = getTableInfo('age_ranges');
                $hasBooksAgeRange = columnExists('books', 'age_range');

                echo '<div class="debug-output">';
                echo "Age range sources:\n";
                echo "- age_ranges table exists: " . ($hasAgeRangesTable ? 'YES' : 'NO') . "\n";
                echo "- books.age_range column exists: " . ($hasBooksAgeRange ? 'YES' : 'NO') . "\n";
                echo '</div>';

                if ($hasAgeRangesTable) {
                    $sql = "
                        SELECT
                            MIN(range_name) as range_name,
                            COUNT(*) as count,
                            GROUP_CONCAT(id) as ids
                        FROM age_ranges
                        GROUP BY LOWER(TRIM(range_name))
                        HAVING count > 1
                        ORDER BY count DESC, MIN(range_name)
                    ";
                    $stmt = $db->query($sql);
                    $duplicateAgeRanges = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    if (empty($duplicateAgeRanges)) {
                        echo '<div class="alert alert-success">✅ No duplicate age ranges in table</div>';
                    } else {
                        echo '<div class="alert alert-warning">⚠️ Found ' . count($duplicateAgeRanges) . ' groups of duplicate age ranges:</div>';

                        foreach ($duplicateAgeRanges as $group) {
                            echo '<div class="duplicate-group">';
                            echo '<strong>' . htmlspecialchars($group['range_name']) . '</strong> (' . $group['count'] . ' duplicates)<br>';
                            echo 'IDs: ' . $group['ids'] . '<br>';
                            echo '</div>';
                        }
                    }
                }

                if ($hasBooksAgeRange) {
                    // Analyze text values in books.age_range
                    $sql = "
                        SELECT
                            MIN(age_range) as age_range,
                            COUNT(*) as count
                        FROM books
                        WHERE age_range IS NOT NULL AND age_range != ''
                        GROUP BY LOWER(TRIM(age_range))
                        ORDER BY count DESC, MIN(age_range)
                    ";
                    $stmt = $db->query($sql);
                    $ageRangeValues = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    echo '<h5>Age Range Values in Books:</h5>';
                    if (empty($ageRangeValues)) {
                        echo '<div class="alert alert-info">No age range values found in books</div>';
                    } else {
                        echo '<div class="table-responsive">';
                        echo '<table class="table table-sm">';
                        echo '<thead><tr><th>Age Range</th><th>Book Count</th></tr></thead>';
                        echo '<tbody>';
                        foreach ($ageRangeValues as $value) {
                            echo '<tr>';
                            echo '<td>' . htmlspecialchars($value['age_range']) . '</td>';
                            echo '<td><span class="badge bg-info">' . $value['count'] . '</span></td>';
                            echo '</tr>';
                        }
                        echo '</tbody></table>';
                        echo '</div>';
                    }
                }

            } catch (Exception $e) {
                echo '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }

            echo '</div></div>';

            // 5. READING LEVELS
            echo '<div class="card mb-4">';
            echo '<div class="card-header"><h3>📚 Reading Levels Analysis</h3></div>';
            echo '<div class="card-body">';

            try {
                $hasReadingLevel = columnExists('books', 'reading_level');

                echo '<div class="debug-output">';
                echo "Reading level sources:\n";
                echo "- books.reading_level column exists: " . ($hasReadingLevel ? 'YES' : 'NO') . "\n";
                echo '</div>';

                if ($hasReadingLevel) {
                    $sql = "
                        SELECT
                            MIN(reading_level) as reading_level,
                            COUNT(*) as count
                        FROM books
                        WHERE reading_level IS NOT NULL AND reading_level != ''
                        GROUP BY LOWER(TRIM(reading_level))
                        ORDER BY count DESC, MIN(reading_level)
                    ";
                    $stmt = $db->query($sql);
                    $readingLevelValues = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    if (empty($readingLevelValues)) {
                        echo '<div class="alert alert-info">No reading level values found in books</div>';
                    } else {
                        echo '<div class="table-responsive">';
                        echo '<table class="table table-sm">';
                        echo '<thead><tr><th>Reading Level</th><th>Book Count</th></tr></thead>';
                        echo '<tbody>';
                        foreach ($readingLevelValues as $value) {
                            echo '<tr>';
                            echo '<td>' . htmlspecialchars($value['reading_level']) . '</td>';
                            echo '<td><span class="badge bg-info">' . $value['count'] . '</span></td>';
                            echo '</tr>';
                        }
                        echo '</tbody></table>';
                        echo '</div>';
                    }
                }

            } catch (Exception $e) {
                echo '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }

            echo '</div></div>';

            // 6. DIRECTORY CATEGORIES
            echo '<div class="card mb-4">';
            echo '<div class="card-header"><h3>📁 Directory Categories Analysis</h3></div>';
            echo '<div class="card-body">';

            try {
                if (getTableInfo('directory_categories')) {
                    $sql = "
                        SELECT
                            MIN(name) as name,
                            COUNT(*) as count,
                            GROUP_CONCAT(id) as ids
                        FROM directory_categories
                        GROUP BY LOWER(TRIM(name))
                        HAVING count > 1
                        ORDER BY count DESC, MIN(name)
                    ";
                    $stmt = $db->query($sql);
                    $duplicateCategories = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    if (empty($duplicateCategories)) {
                        echo '<div class="alert alert-success">✅ No duplicate directory categories found</div>';
                    } else {
                        echo '<div class="alert alert-warning">⚠️ Found ' . count($duplicateCategories) . ' groups of duplicate categories:</div>';

                        foreach ($duplicateCategories as $group) {
                            echo '<div class="duplicate-group">';
                            echo '<strong>' . htmlspecialchars($group['name']) . '</strong> (' . $group['count'] . ' duplicates)<br>';
                            echo 'IDs: ' . $group['ids'] . '<br>';

                            // Show usage
                            $ids = explode(',', $group['ids']);
                            foreach ($ids as $id) {
                                $id = trim($id);
                                $stmt = $db->prepare("SELECT COUNT(*) FROM directory_items WHERE category_id = ?");
                                $stmt->execute([$id]);
                                $itemCount = $stmt->fetchColumn();
                                if ($itemCount > 0) {
                                    echo "ID $id: $itemCount directory items<br>";
                                }
                            }
                            echo '</div>';
                        }
                    }
                } else {
                    echo '<div class="alert alert-info">Directory categories table not found</div>';
                }

            } catch (Exception $e) {
                echo '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }

            echo '</div></div>';

            // 7. SERIES (Text field analysis)
            echo '<div class="card mb-4">';
            echo '<div class="card-header"><h3>📖 Series Analysis</h3></div>';
            echo '<div class="card-body">';

            try {
                $hasSeries = columnExists('books', 'series');

                echo '<div class="debug-output">';
                echo "Series sources:\n";
                echo "- books.series column exists: " . ($hasSeries ? 'YES' : 'NO') . "\n";
                echo '</div>';

                if ($hasSeries) {
                    $sql = "
                        SELECT
                            MIN(series) as series,
                            COUNT(*) as count
                        FROM books
                        WHERE series IS NOT NULL AND series != ''
                        GROUP BY LOWER(TRIM(series))
                        HAVING count > 1
                        ORDER BY count DESC, MIN(series)
                    ";
                    $stmt = $db->query($sql);
                    $duplicateSeries = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    if (empty($duplicateSeries)) {
                        echo '<div class="alert alert-success">✅ No duplicate series found</div>';
                    } else {
                        echo '<div class="alert alert-warning">⚠️ Found ' . count($duplicateSeries) . ' potential series duplicates:</div>';

                        foreach ($duplicateSeries as $series) {
                            echo '<div class="duplicate-group">';
                            echo '<strong>' . htmlspecialchars($series['series']) . '</strong> (' . $series['count'] . ' books)<br>';
                            echo '</div>';
                        }
                    }

                    // Show all unique series for reference
                    $sql = "
                        SELECT
                            series,
                            COUNT(*) as count
                        FROM books
                        WHERE series IS NOT NULL AND series != ''
                        GROUP BY series
                        ORDER BY count DESC, series
                        LIMIT 20
                    ";
                    $stmt = $db->query($sql);
                    $allSeries = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    if (!empty($allSeries)) {
                        echo '<h5>Top 20 Series (for reference):</h5>';
                        echo '<div class="table-responsive">';
                        echo '<table class="table table-sm">';
                        echo '<thead><tr><th>Series Name</th><th>Book Count</th></tr></thead>';
                        echo '<tbody>';
                        foreach ($allSeries as $series) {
                            echo '<tr>';
                            echo '<td>' . htmlspecialchars($series['series']) . '</td>';
                            echo '<td><span class="badge bg-info">' . $series['count'] . '</span></td>';
                            echo '</tr>';
                        }
                        echo '</tbody></table>';
                        echo '</div>';
                    }
                }

            } catch (Exception $e) {
                echo '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }

            echo '</div></div>';

        elseif ($stage === 'reassign'):
        ?>
            <div class="row">
                <div class="col-12">
                    <h2>🔄 Stage 2: Reassign References</h2>
                    <p>This stage will show you what changes will be made and allow you to confirm them.</p>
                    <div class="alert alert-info">Stage 2 implementation coming next...</div>
                </div>
            </div>

        <?php elseif ($stage === 'update'): ?>
            <div class="row">
                <div class="col-12">
                    <h2>✅ Stage 3: Update Database</h2>
                    <p>This stage will execute the confirmed changes.</p>
                    <div class="alert alert-info">Stage 3 implementation coming next...</div>
                </div>
            </div>

        <?php endif; ?>
    </div>

    <script>
    function updatePublisherRelationship(bookId, publisherId) {
        if (!confirm('Update publisher relationship for book ID ' + bookId + ' to publisher ID ' + publisherId + '?')) {
            return;
        }

        const button = event.target;
        const originalText = button.textContent;
        button.textContent = 'Updating...';
        button.disabled = true;

        fetch('book-import-validate/ajax/data-enrichment-ajax.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=update_publisher_relationship&book_id=' + bookId + '&publisher_id=' + publisherId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✅ Publisher relationship updated successfully!');
                location.reload();
            } else {
                alert('❌ Error: ' + (data.message || 'Unknown error'));
                button.textContent = originalText;
                button.disabled = false;
            }
        })
        .catch(error => {
            alert('❌ Network error: ' + error.message);
            button.textContent = originalText;
            button.disabled = false;
        });
    }

    function createPublisher(publisherName, bookId) {
        if (!confirm('Create new publisher "' + publisherName + '" and link to book ID ' + bookId + '?')) {
            return;
        }

        const button = event.target;
        const originalText = button.textContent;
        button.textContent = 'Creating...';
        button.disabled = true;

        fetch('book-import-validate/ajax/data-enrichment-ajax.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=create_publisher&publisher_name=' + encodeURIComponent(publisherName) + '&book_id=' + bookId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✅ Publisher created and linked successfully!\nPublisher ID: ' + data.publisher_id);
                location.reload();
            } else {
                alert('❌ Error: ' + (data.message || 'Unknown error'));
                button.textContent = originalText;
                button.disabled = false;
            }
        })
        .catch(error => {
            alert('❌ Network error: ' + error.message);
            button.textContent = originalText;
            button.disabled = false;
        });
    }

    function fixPublisherRelationship(bookId) {
        if (!confirm('Automatically fix publisher relationship for book ID ' + bookId + '?')) {
            return;
        }

        const button = event.target;
        const originalText = button.textContent;
        button.textContent = 'Fixing...';
        button.disabled = true;

        fetch('book-import-validate/ajax/data-enrichment-ajax.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=fix_publisher_relationship&book_id=' + bookId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✅ Publisher relationship fixed!\n' + (data.message || ''));
                location.reload();
            } else {
                alert('❌ Error: ' + (data.message || 'Unknown error'));
                button.textContent = originalText;
                button.disabled = false;
            }
        })
        .catch(error => {
            alert('❌ Network error: ' + error.message);
            button.textContent = originalText;
            button.disabled = false;
        });
    }
    </script>

<?php require_once '../includes/footer.php'; ?>
