<?php
/**
 * Author Delete Diagnostic Script
 * This script helps diagnose issues with author deletion by displaying detailed information in the browser.
 */

// Start output buffering to prevent headers already sent issues
ob_start();

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set page variables for header
$pageTitle = 'Author Delete Diagnostics';
$currentPage = 'authors';
$pageDescription = 'Diagnostic information for author deletion';

// Include auth check
require_once '../includes/auth-check.php';

// Include database connection
require_once '../includes/db-connect.php';

// Include header
require_once '../includes/header.php';

// Helper function to print variables with formatting
function debug_var($label, $var) {
    echo '<div style="margin-bottom: 10px;">';
    echo '<strong>' . htmlspecialchars($label) . ':</strong> ';
    if (is_null($var)) {
        echo '<span style="color: red;">NULL</span>';
    } elseif (is_array($var)) {
        echo '<pre>' . htmlspecialchars(print_r($var, true)) . '</pre>';
    } elseif (is_object($var)) {
        echo '<pre>' . htmlspecialchars(print_r($var, true)) . '</pre>';
    } elseif (is_bool($var)) {
        echo $var ? '<span style="color: green;">TRUE</span>' : '<span style="color: red;">FALSE</span>';
    } else {
        echo '<span style="color: blue;">' . htmlspecialchars((string)$var) . '</span>';
    }
    echo '</div>';
}

// Get author ID from URL
$authorId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Database status
$dbConnected = isset($db) && $db instanceof PDO;

?>

<div class="content-wrapper">
    <div class="container-fluid">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h3>Author Delete Diagnostics</h3>
            </div>
            <div class="card-body">
                <h4>Request Information</h4>
                <div class="debug-section" style="margin-bottom: 20px; padding: 10px; background: #f8f9fa; border-radius: 5px;">
                    <?php
                    debug_var('$_GET', $_GET);
                    debug_var('$_POST', $_POST);
                    debug_var('Author ID (parsed)', $authorId);
                    debug_var('HTTP Request Method', $_SERVER['REQUEST_METHOD']);
                    ?>
                </div>

                <h4>Database Connection</h4>
                <div class="debug-section" style="margin-bottom: 20px; padding: 10px; background: #f8f9fa; border-radius: 5px;">
                    <?php
                    debug_var('Database Connected', $dbConnected);
                    if (!$dbConnected) {
                        echo '<div class="alert alert-danger">Database connection failed.</div>';
                    }
                    ?>
                </div>

                <?php if ($dbConnected && $authorId > 0): ?>
                    <h4>Author Information</h4>
                    <div class="debug-section" style="margin-bottom: 20px; padding: 10px; background: #f8f9fa; border-radius: 5px;">
                        <?php
                        try {
                            // Prepare statement
                            $stmt = $db->prepare("SELECT * FROM authors WHERE id = ?");
                            debug_var('SQL Query', "SELECT * FROM authors WHERE id = {$authorId}");
                            
                            // Execute with parameter
                            $executed = $stmt->execute([$authorId]);
                            debug_var('Query Executed Successfully', $executed);
                            
                            // Fetch result
                            $author = $stmt->fetch(PDO::FETCH_ASSOC);
                            debug_var('Author Data', $author);
                            
                            // Check if author was found
                            if (!$author) {
                                echo '<div class="alert alert-warning">No author found with ID ' . $authorId . '</div>';
                            } else {
                                echo '<div class="alert alert-success">Author found: ' . htmlspecialchars($author['name']) . '</div>';
                            }
                            
                            // Check for story associations
                            // First check story_authors junction table
                            $hasStoriesJunction = false;
                            $junctionTableExists = false;
                            try {
                                $tableCheck = $db->query("SHOW TABLES LIKE 'story_authors'");
                                $junctionTableExists = ($tableCheck->rowCount() > 0);
                                debug_var('story_authors Table Exists', $junctionTableExists);
                                
                                if ($junctionTableExists) {
                                    $storyCount = $db->prepare("SELECT COUNT(*) FROM story_authors WHERE author_id = ?");
                                    $storyCount->execute([$authorId]);
                                    $count = $storyCount->fetchColumn();
                                    debug_var('Associated Stories Count (junction table)', $count);
                                    $hasStoriesJunction = ($count > 0);
                                }
                            } catch (PDOException $e) {
                                debug_var('Error checking story_authors table', $e->getMessage());
                            }
                            
                            // Then check direct author_id column in stories table
                            $hasStoriesDirect = false;
                            $authorColumnExists = false;
                            try {
                                $columnCheck = $db->query("SHOW COLUMNS FROM stories LIKE 'author_id'");
                                $authorColumnExists = ($columnCheck->rowCount() > 0);
                                debug_var('author_id Column Exists in stories table', $authorColumnExists);
                                
                                if ($authorColumnExists) {
                                    $storyCount = $db->prepare("SELECT COUNT(*) FROM stories WHERE author_id = ?");
                                    $storyCount->execute([$authorId]);
                                    $count = $storyCount->fetchColumn();
                                    debug_var('Associated Stories Count (direct column)', $count);
                                    $hasStoriesDirect = ($count > 0);
                                }
                            } catch (PDOException $e) {
                                debug_var('Error checking stories table', $e->getMessage());
                            }
                            
                        } catch (PDOException $e) {
                            debug_var('Database Error', $e->getMessage());
                            echo '<div class="alert alert-danger">Database Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
                        }
                        ?>
                    </div>
                    
                    <h4>Test Deletion Form</h4>
                    <div class="debug-section" style="margin-bottom: 20px; padding: 10px; background: #f8f9fa; border-radius: 5px;">
                        <?php if (isset($author) && $author): ?>
                            <form action="delete-author.php" method="post" class="mt-3">
                                <div class="alert alert-warning">
                                    <strong>This is a test form to attempt deletion. Use at your own risk!</strong>
                                </div>
                                <input type="hidden" name="id" value="<?php echo $authorId; ?>">
                                
                                <div class="form-check mb-3">
                                    <input type="radio" id="delete_all" name="action" value="delete_all" class="form-check-input">
                                    <label for="delete_all" class="form-check-label">
                                        Delete all associated stories
                                    </label>
                                </div>
                                
                                <div class="form-check mb-3">
                                    <input type="radio" id="cancel" name="action" value="cancel" class="form-check-input" checked>
                                    <label for="cancel" class="form-check-label">
                                        Cancel deletion
                                    </label>
                                </div>
                                
                                <button type="submit" class="btn btn-danger">Test Delete Process</button>
                                <a href="authors.php" class="btn btn-secondary">Back to Authors</a>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-danger">Cannot test deletion - author not found</div>
                            <a href="authors.php" class="btn btn-secondary">Back to Authors</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <h4>Link Analysis</h4>
                <div class="debug-section" style="margin-bottom: 20px; padding: 10px; background: #f8f9fa; border-radius: 5px;">
                    <?php
                    // Analyze how the deletion link should work
                    echo '<p>Link from authors list should be: <code>author-delete.php?id=' . $authorId . '</code></p>';
                    echo '<p>Link points to: <code>' . htmlspecialchars(basename($_SERVER['REQUEST_URI'])) . '</code></p>';
                    
                    // Check if the JavaScript confirmation is working
                    echo '<div class="mt-3">';
                    echo '<strong>Test Link with JavaScript Confirmation:</strong><br>';
                    echo '<a href="author-delete.php?id=' . $authorId . '" class="btn btn-sm btn-danger mt-2" onclick="return confirm(\'This is a test confirmation dialog. Click Cancel.\');">';
                    echo '<i class="fas fa-trash"></i> Test Delete Link</a>';
                    echo '</div>';
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
require_once '../includes/footer.php';

// End output buffering and flush content
if (ob_get_length()) ob_end_flush();
?>