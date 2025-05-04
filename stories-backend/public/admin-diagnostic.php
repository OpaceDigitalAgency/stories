<?php
/**
 * Public Admin Diagnostic Tool
 * This script bypasses the authentication to diagnose admin issues.
 */

// Set error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Don't include any authentication checks to avoid redirect loops
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Diagnostic Tool</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; margin: 0; padding: 20px; color: #333; }
        h1, h2, h3 { color: #2c3e50; }
        .container { max-width: 1200px; margin: 0 auto; }
        .card { background: #fff; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-bottom: 20px; padding: 20px; }
        .section { margin-bottom: 30px; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        .btn { 
            display: inline-block; 
            padding: 8px 16px; 
            background: #3498db; 
            color: white; 
            border-radius: 4px; 
            text-decoration: none;
            border: none;
            cursor: pointer;
        }
        .btn-danger { background: #e74c3c; }
        .btn-success { background: #2ecc71; }
        table { width: 100%; border-collapse: collapse; }
        table, th, td { border: 1px solid #ddd; }
        th, td { padding: 10px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Admin Diagnostic Tool</h1>
        <p>This tool helps diagnose issues with the admin dashboard without authentication redirect loops.</p>

        <div class="card">
            <h2>Database Connection Test</h2>
            <?php
            try {
                // Database configuration
                $config = [
                    'host' => 'localhost',
                    'name' => 'stories_db',
                    'user' => 'stories_user',
                    'password' => '$tw1cac3*sOt',
                    'charset' => 'utf8mb4',
                    'port' => 3306
                ];

                // Connect to database
                $db = new PDO(
                    "mysql:host={$config['host']};dbname={$config['name']};charset={$config['charset']}",
                    $config['user'],
                    $config['password'],
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false
                    ]
                );
                echo '<p class="success">✓ Database connection successful</p>';
            } catch (PDOException $e) {
                echo '<p class="error">✗ Database connection failed: ' . htmlspecialchars($e->getMessage()) . '</p>';
                $db = null;
            }
            ?>
        </div>

        <?php if (isset($db) && $db): ?>
        <div class="card">
            <h2>Authors Table Test</h2>
            <?php
            try {
                // Check if authors table exists
                $stmt = $db->query("SHOW TABLES LIKE 'authors'");
                $tableExists = ($stmt->rowCount() > 0);
                
                if ($tableExists) {
                    echo '<p class="success">✓ Authors table exists</p>';
                    
                    // Count authors
                    $stmt = $db->query("SELECT COUNT(*) FROM authors");
                    $authorCount = $stmt->fetchColumn();
                    echo '<p>Total authors: ' . $authorCount . '</p>';
                    
                    // Show author list
                    if ($authorCount > 0) {
                        echo '<h3>Author List (first 10)</h3>';
                        echo '<table>';
                        echo '<tr><th>ID</th><th>Name</th><th>Actions</th></tr>';
                        
                        $stmt = $db->query("SELECT id, name FROM authors ORDER BY id DESC LIMIT 10");
                        while ($author = $stmt->fetch()) {
                            echo '<tr>';
                            echo '<td>' . htmlspecialchars($author['id']) . '</td>';
                            echo '<td>' . htmlspecialchars($author['name']) . '</td>';
                            echo '<td><a href="?action=test_author&id=' . $author['id'] . '" class="btn">Test Author</a></td>';
                            echo '</tr>';
                        }
                        echo '</table>';
                    }
                } else {
                    echo '<p class="error">✗ Authors table does not exist</p>';
                }
            } catch (PDOException $e) {
                echo '<p class="error">✗ Error checking authors table: ' . htmlspecialchars($e->getMessage()) . '</p>';
            }
            ?>
        </div>

        <?php 
        // Test specific author if requested
        if (isset($_GET['action']) && $_GET['action'] == 'test_author' && isset($_GET['id'])) {
            $authorId = (int)$_GET['id'];
            echo '<div class="card">';
            echo '<h2>Testing Author ID: ' . $authorId . '</h2>';
            
            try {
                // Get author details
                $stmt = $db->prepare("SELECT * FROM authors WHERE id = ?");
                $stmt->execute([$authorId]);
                $author = $stmt->fetch();
                
                if ($author) {
                    echo '<div class="section">';
                    echo '<h3>Author Details</h3>';
                    echo '<pre>' . print_r($author, true) . '</pre>';
                    echo '</div>';
                    
                    // Check if story_authors junction table exists
                    $hasStoryAuthorsTable = false;
                    try {
                        $stmt = $db->query("SHOW TABLES LIKE 'story_authors'");
                        $hasStoryAuthorsTable = $stmt->rowCount() > 0;
                        
                        if ($hasStoryAuthorsTable) {
                            echo '<div class="section">';
                            echo '<h3>Story Authors Junction Table</h3>';
                            echo '<p class="success">✓ story_authors table exists</p>';
                            
                            // Count associated stories
                            $stmt = $db->prepare("SELECT COUNT(*) FROM story_authors WHERE author_id = ?");
                            $stmt->execute([$authorId]);
                            $storyCount = $stmt->fetchColumn();
                            
                            echo '<p>Stories linked to this author: ' . $storyCount . '</p>';
                            echo '</div>';
                        } else {
                            echo '<div class="section">';
                            echo '<h3>Story Authors Junction Table</h3>';
                            echo '<p class="warning">⚠ story_authors table does not exist</p>';
                            echo '</div>';
                        }
                    } catch (PDOException $e) {
                        echo '<p class="error">✗ Error checking story_authors table: ' . htmlspecialchars($e->getMessage()) . '</p>';
                    }
                    
                    // Check if stories table has author_id column
                    try {
                        $stmt = $db->query("SHOW COLUMNS FROM stories LIKE 'author_id'");
                        $hasAuthorIdColumn = $stmt->rowCount() > 0;
                        
                        echo '<div class="section">';
                        echo '<h3>Stories Table</h3>';
                        
                        if ($hasAuthorIdColumn) {
                            echo '<p class="success">✓ stories table has author_id column</p>';
                            
                            // Count stories with direct author_id
                            $stmt = $db->prepare("SELECT COUNT(*) FROM stories WHERE author_id = ?");
                            $stmt->execute([$authorId]);
                            $directStoryCount = $stmt->fetchColumn();
                            
                            echo '<p>Stories with direct author_id: ' . $directStoryCount . '</p>';
                        } else {
                            echo '<p class="warning">⚠ stories table does not have author_id column</p>';
                        }
                        echo '</div>';
                    } catch (PDOException $e) {
                        echo '<p class="error">✗ Error checking stories table: ' . htmlspecialchars($e->getMessage()) . '</p>';
                    }
                    
                    echo '<div class="section">';
                    echo '<h3>Delete Process Simulation</h3>';
                    echo '<p>This section shows what would happen when deleting this author, without actually deleting anything.</p>';
                    
                    // Simulate delete_all action
                    echo '<h4>Simulate "Delete All" Action</h4>';
                    echo '<pre>';
                    echo "BEGIN TRANSACTION;\n";
                    
                    if ($hasStoryAuthorsTable) {
                        echo "-- Delete from story_authors junction table\n";
                        echo "DELETE FROM story_authors WHERE author_id = {$authorId};\n";
                    }
                    
                    if (isset($hasAuthorIdColumn) && $hasAuthorIdColumn) {
                        echo "-- Delete stories with author_id = {$authorId}\n";
                        echo "DELETE FROM stories WHERE author_id = {$authorId};\n";
                    }
                    
                    echo "-- Delete the author\n";
                    echo "DELETE FROM authors WHERE id = {$authorId};\n";
                    echo "COMMIT;\n";
                    echo '</pre>';
                    
                    echo '</div>';
                } else {
                    echo '<p class="error">✗ Author not found with ID ' . $authorId . '</p>';
                }
            } catch (PDOException $e) {
                echo '<p class="error">✗ Error retrieving author: ' . htmlspecialchars($e->getMessage()) . '</p>';
            }
            
            echo '</div>';
        }
        ?>

        <div class="card">
            <h2>Admin Structure</h2>
            <div class="section">
                <h3>Admin URL Structure</h3>
                <p>The correct URL structure for admin pages is:</p>
                <pre>https://api.storiesfromtheweb.org/admin/</pre>
                
                <p>The content pages are located at:</p>
                <pre>https://api.storiesfromtheweb.org/admin/content/</pre>
                
                <p>When accessing these directly, you need to be logged in or you'll get a redirect loop.</p>
            </div>
            
            <div class="section">
                <h3>Authentication Flow</h3>
                <ol>
                    <li>Visiting <code>/admin/</code> should redirect to login if not authenticated</li>
                    <li>After login, you should be redirected to dashboard</li>
                    <li>From dashboard, you can navigate to other admin pages</li>
                </ol>
                <p>The redirect loop occurs when:</p>
                <ol>
                    <li>You're not logged in and try to access a protected page directly</li>
                    <li>The page checks authentication and redirects to login</li>
                    <li>Something in the login process is failing and redirecting back</li>
                </ol>
            </div>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>