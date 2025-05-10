<?php
/**
 * Update All Authors
 * 
 * This script updates all authors in the database to have a default avatar URL
 * if they don't already have one.
 */

// Include auth check
require_once '../includes/auth-check.php';

// Include database connection
require_once '../includes/db-connect.php';

// Set page variables for header
$pageTitle = 'Update All Authors';
$currentPage = 'authors';
$pageDescription = 'Update all authors to have a default avatar URL';

// Include header
require_once '../includes/header.php';

// Function to flush output buffer to ensure real-time progress display
function flushOutput() {
    if (ob_get_level() > 0) {
        ob_flush();
        flush();
    }
}

// Default avatar URL
$defaultAvatarUrl = '/uploads/default-avatar.svg';

// Check if the default avatar file exists, if not, create it
$defaultAvatarPath = __DIR__ . '/../../uploads/default-avatar.svg';
$defaultAvatarCreated = false;

if (!file_exists($defaultAvatarPath)) {
    // Create the uploads directory if it doesn't exist
    if (!is_dir(__DIR__ . '/../../uploads')) {
        mkdir(__DIR__ . '/../../uploads', 0755, true);
    }
    
    // Copy the default avatar from the admin assets if it exists
    $sourceAvatarPath = __DIR__ . '/../assets/images/default-avatar.svg';
    if (file_exists($sourceAvatarPath)) {
        copy($sourceAvatarPath, $defaultAvatarPath);
        $defaultAvatarCreated = true;
    } else {
        // Create a simple SVG avatar
        $svgContent = '<?xml version="1.0" encoding="UTF-8"?><svg xmlns="http://www.w3.org/2000/svg" width="200" height="200" viewBox="0 0 200 200"><circle cx="100" cy="100" r="100" fill="#e0e0e0"/><circle cx="100" cy="80" r="40" fill="#a0a0a0"/><path d="M100 130 C60 130 40 170 40 200 L160 200 C160 170 140 130 100 130 Z" fill="#a0a0a0"/></svg>';
        file_put_contents($defaultAvatarPath, $svgContent);
        $defaultAvatarCreated = true;
    }
}

// Process form submission
$updated = 0;
$errors = [];
$totalAuthors = 0;
$authorsWithoutAvatar = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    try {
        // Begin transaction
        $db->beginTransaction();
        
        // Get all authors without an avatar URL
        $stmt = $db->query("SELECT id, name FROM authors WHERE avatar_url IS NULL OR avatar_url = ''");
        $authors = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $authorsWithoutAvatar = count($authors);
        
        // Update each author
        foreach ($authors as $author) {
            try {
                $updateStmt = $db->prepare("UPDATE authors SET avatar_url = ? WHERE id = ?");
                $updateStmt->execute([$defaultAvatarUrl, $author['id']]);
                $updated++;
                
                echo "<p class='text-success'>Updated author: {$author['name']} (ID: {$author['id']})</p>";
                flushOutput();
            } catch (Exception $e) {
                $errors[] = "Error updating author {$author['id']}: " . $e->getMessage();
                echo "<p class='text-danger'>Error updating author {$author['name']} (ID: {$author['id']}): " . $e->getMessage() . "</p>";
                flushOutput();
            }
        }
        
        // Commit transaction
        $db->commit();
        
    } catch (Exception $e) {
        // Rollback transaction on error
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        $errors[] = "Error: " . $e->getMessage();
    }
}

// Get total number of authors
$totalStmt = $db->query("SELECT COUNT(*) as total FROM authors");
$totalResult = $totalStmt->fetch(PDO::FETCH_ASSOC);
$totalAuthors = $totalResult['total'];

// Get number of authors without avatar
$withoutAvatarStmt = $db->query("SELECT COUNT(*) as total FROM authors WHERE avatar_url IS NULL OR avatar_url = ''");
$withoutAvatarResult = $withoutAvatarStmt->fetch(PDO::FETCH_ASSOC);
$authorsWithoutAvatar = $withoutAvatarResult['total'];

?>

<div class="container">
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header">
                    <h2>Update All Authors</h2>
                </div>
                <div class="card-body">
                    <?php if ($defaultAvatarCreated): ?>
                    <div class="alert alert-success">
                        <strong>Success:</strong> Default avatar created at <?php echo $defaultAvatarPath; ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="alert alert-info">
                        <strong>Info:</strong> Found <?php echo $totalAuthors; ?> total authors in the database.
                        <?php echo $authorsWithoutAvatar; ?> authors do not have an avatar URL.
                    </div>
                    
                    <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <strong>Errors:</strong>
                        <ul>
                            <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($updated > 0): ?>
                    <div class="alert alert-success">
                        <strong>Success:</strong> Updated <?php echo $updated; ?> authors with default avatar URL.
                    </div>
                    <?php endif; ?>
                    
                    <form method="post" action="">
                        <input type="hidden" name="action" value="update">
                        <button type="submit" class="btn btn-primary">Update All Authors Without Avatar</button>
                        <a href="authors.php" class="btn btn-secondary">Back to Authors</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
require_once '../includes/footer.php';
?>
