<?php
/**
 * Author Delete Test - Public Version
 * This script bypasses the admin authentication to test author deletion directly.
 */

// Set error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set up page variables
$pageTitle = 'Author Delete Test';
$action = isset($_GET['action']) ? $_GET['action'] : '';
$authorId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$messages = [];
$error = null;
$success = null;
$showConfirm = false;
$author = null;

// Connect to database
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
    $messages[] = "Database connection successful";
} catch (PDOException $e) {
    $error = "Database connection failed: " . $e->getMessage();
    $db = null;
}

// Check if authors table exists
$authorsTableExists = false;
if ($db) {
    try {
        $stmt = $db->query("SHOW TABLES LIKE 'authors'");
        $authorsTableExists = ($stmt->rowCount() > 0);
        if ($authorsTableExists) {
            $messages[] = "Authors table exists";
        } else {
            $error = "Authors table does not exist";
        }
    } catch (PDOException $e) {
        $error = "Error checking authors table: " . $e->getMessage();
    }
}

// Get author info if ID is provided
if ($db && $authorsTableExists && $authorId > 0) {
    try {
        // First, check the structure of the authors table
        $stmt = $db->query("DESCRIBE authors");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $messages[] = "Authors table columns: " . implode(", ", $columns);
        
        // Get author details
        $stmt = $db->prepare("SELECT * FROM authors WHERE id = ?");
        $stmt->execute([$authorId]);
        $author = $stmt->fetch();
        
        if ($author) {
            $messages[] = "Author found: ID #{$author['id']} - {$author['name']}";
            
            // Check for stories associated with this author
            $storyCount = 0;
            
            // Check if story_authors junction table exists
            try {
                $stmt = $db->query("SHOW TABLES LIKE 'story_authors'");
                $junctionTableExists = ($stmt->rowCount() > 0);
                
                if ($junctionTableExists) {
                    $messages[] = "story_authors junction table exists";
                    
                    $stmt = $db->prepare("SELECT COUNT(*) FROM story_authors WHERE author_id = ?");
                    $stmt->execute([$authorId]);
                    $junctionCount = $stmt->fetchColumn();
                    $messages[] = "Stories in junction table: {$junctionCount}";
                    $storyCount += $junctionCount;
                }
            } catch (PDOException $e) {
                $messages[] = "Error checking story_authors table: " . $e->getMessage();
            }
            
            // Check for direct author_id in stories table
            try {
                $stmt = $db->query("SHOW COLUMNS FROM stories LIKE 'author_id'");
                $hasAuthorIdColumn = ($stmt->rowCount() > 0);
                
                if ($hasAuthorIdColumn) {
                    $messages[] = "stories table has author_id column";
                    
                    $stmt = $db->prepare("SELECT COUNT(*) FROM stories WHERE author_id = ?");
                    $stmt->execute([$authorId]);
                    $directCount = $stmt->fetchColumn();
                    $messages[] = "Stories with direct author_id: {$directCount}";
                    $storyCount += $directCount;
                }
            } catch (PDOException $e) {
                $messages[] = "Error checking stories table: " . $e->getMessage();
            }
            
            $messages[] = "Total associated stories: {$storyCount}";
            
            // Confirm deletion
            if ($action === 'confirm_delete') {
                $showConfirm = true;
            }
            
            // Process deletion
            if ($action === 'delete' && isset($_POST['confirm']) && $_POST['confirm'] === 'yes') {
                try {
                    // Start transaction
                    $db->beginTransaction();
                    $deleteMessages = [];
                    
                    // Delete from story_authors junction table if it exists
                    if (isset($junctionTableExists) && $junctionTableExists) {
                        $stmt = $db->prepare("DELETE FROM story_authors WHERE author_id = ?");
                        $stmt->execute([$authorId]);
                        $count = $stmt->rowCount();
                        $deleteMessages[] = "Deleted {$count} records from story_authors";
                    }
                    
                    // Delete from stories table if it has author_id
                    if (isset($hasAuthorIdColumn) && $hasAuthorIdColumn) {
                        $stmt = $db->prepare("DELETE FROM stories WHERE author_id = ?");
                        $stmt->execute([$authorId]);
                        $count = $stmt->rowCount();
                        $deleteMessages[] = "Deleted {$count} stories with author_id = {$authorId}";
                    }
                    
                    // Delete the author
                    $stmt = $db->prepare("DELETE FROM authors WHERE id = ?");
                    $stmt->execute([$authorId]);
                    $count = $stmt->rowCount();
                    $deleteMessages[] = "Deleted author with ID {$authorId}";
                    
                    // Commit transaction
                    $db->commit();
                    
                    $success = "Author deleted successfully. " . implode(", ", $deleteMessages);
                    $author = null; // Clear author since it's deleted
                } catch (PDOException $e) {
                    // Rollback on error
                    $db->rollBack();
                    $error = "Error deleting author: " . $e->getMessage();
                }
            }
        } else {
            $error = "Author not found with ID {$authorId}";
        }
    } catch (PDOException $e) {
        $error = "Error retrieving author: " . $e->getMessage();
    }
}

// Process list request
if ($db && $authorsTableExists && $action === 'list') {
    try {
        $stmt = $db->query("SELECT id, name FROM authors ORDER BY id DESC LIMIT 20");
        $authors = $stmt->fetchAll();
    } catch (PDOException $e) {
        $error = "Error listing authors: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; margin: 0; padding: 20px; color: #333; }
        h1, h2, h3 { color: #2c3e50; }
        .container { max-width: 1200px; margin: 0 auto; }
        .card { background: #fff; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-bottom: 20px; padding: 20px; }
        .message-box { margin-bottom: 20px; }
        .success { color: #2ecc71; background: #e8f8f5; padding: 10px; border-radius: 4px; }
        .error { color: #e74c3c; background: #fdedec; padding: 10px; border-radius: 4px; }
        .info { color: #3498db; background: #ebf5fb; padding: 10px; border-radius: 4px; }
        .btn { display: inline-block; padding: 8px 16px; background: #3498db; color: white; border-radius: 4px; text-decoration: none; border: none; cursor: pointer; }
        .btn-danger { background: #e74c3c; }
        .btn-success { background: #2ecc71; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
        form { margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        table, th, td { border: 1px solid #ddd; }
        th, td { padding: 10px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <div class="container">
        <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
        <p>This is a direct testing tool for author deletion that bypasses the admin system.</p>
        
        <!-- Messages -->
        <div class="message-box">
            <?php if ($success): ?>
                <div class="success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php foreach ($messages as $msg): ?>
                <div class="info"><?php echo htmlspecialchars($msg); ?></div>
            <?php endforeach; ?>
        </div>
        
        <!-- Authors List -->
        <?php if (isset($authors) && is_array($authors)): ?>
            <div class="card">
                <h2>Authors List</h2>
                <table>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Actions</th>
                    </tr>
                    <?php foreach ($authors as $a): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($a['id']); ?></td>
                            <td><?php echo htmlspecialchars($a['name']); ?></td>
                            <td>
                                <a href="?id=<?php echo $a['id']; ?>" class="btn">View</a>
                                <a href="?action=confirm_delete&id=<?php echo $a['id']; ?>" class="btn btn-danger">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        <?php endif; ?>
        
        <!-- View Author -->
        <?php if ($author): ?>
            <div class="card">
                <h2>Author Details</h2>
                <pre><?php print_r($author); ?></pre>
                
                <?php if ($showConfirm): ?>
                    <div class="card" style="background-color: #fdedec;">
                        <h3>Confirm Deletion</h3>
                        <p>Are you sure you want to delete the author "<?php echo htmlspecialchars($author['name']); ?>" (ID: <?php echo $author['id']; ?>)?</p>
                        <p>This action cannot be undone.</p>
                        
                        <form method="post" action="?action=delete&id=<?php echo $author['id']; ?>">
                            <button type="submit" name="confirm" value="yes" class="btn btn-danger">Yes, Delete Author</button>
                            <a href="?id=<?php echo $author['id']; ?>" class="btn">Cancel</a>
                        </form>
                    </div>
                <?php else: ?>
                    <p>
                        <a href="?action=confirm_delete&id=<?php echo $author['id']; ?>" class="btn btn-danger">Delete This Author</a>
                        <a href="?action=list" class="btn">Back to List</a>
                    </p>
                <?php endif; ?>
            </div>
        <?php elseif (!isset($authors)): ?>
            <div class="card">
                <h2>Author Testing</h2>
                <?php if ($db && $authorsTableExists): ?>
                    <p>Enter an author ID to test or view the author list:</p>
                    <form method="get">
                        <input type="number" name="id" placeholder="Author ID" required>
                        <button type="submit" class="btn">View Author</button>
                        <a href="?action=list" class="btn">View All Authors</a>
                    </form>
                <?php else: ?>
                    <p>Please fix database connection or table issues before testing.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <!-- Navigation -->
        <div class="card">
            <h2>Admin Navigation</h2>
            <p>Below are the correct paths to access admin:</p>
            <ul>
                <li><strong>Admin Login:</strong> <a href="../admin/login.php" target="_blank">../admin/login.php</a></li>
                <li><strong>Authors List in Admin:</strong> <a href="../admin/content/authors.php" target="_blank">../admin/content/authors.php</a> (requires login)</li>
                <li><strong>Admin Diagnostic Tool:</strong> <a href="admin-diagnostic.php">admin-diagnostic.php</a></li>
            </ul>
        </div>
    </div>
</body>
</html>