<?php
/**
 * Fix Author Images
 * 
 * This script checks for authors with NULL avatar_url values and attempts to fix them
 * by looking for images in the media library that might match the author.
 */

// Include auth check
require_once '../includes/auth-check.php';

// Include header
require_once '../includes/header.php';

// Initialize variables
$messages = [];
$errors = [];
$fixed = 0;

// Function to find potential images for an author
function findPotentialAuthorImages($db, $authorName) {
    $searchTerms = explode(' ', $authorName);
    $potentialImages = [];
    
    // Search for images with the author's name in the filename
    foreach ($searchTerms as $term) {
        if (strlen($term) < 3) continue; // Skip short terms
        
        $stmt = $db->prepare("SELECT * FROM media WHERE filename LIKE ? OR alt_text LIKE ? ORDER BY created_at DESC LIMIT 5");
        $searchTerm = '%' . $term . '%';
        $stmt->execute([$searchTerm, $searchTerm]);
        
        while ($row = $stmt->fetch()) {
            $potentialImages[$row['id']] = $row;
        }
    }
    
    return $potentialImages;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Start transaction
        $db->beginTransaction();
        
        if (isset($_POST['fix_all'])) {
            // Get all authors with NULL avatar_url
            $stmt = $db->query("SELECT * FROM authors WHERE avatar_url IS NULL OR avatar_url = ''");
            $authors = $stmt->fetchAll();
            
            foreach ($authors as $author) {
                // Find potential images
                $potentialImages = findPotentialAuthorImages($db, $author['name']);
                
                if (!empty($potentialImages)) {
                    // Use the first image found
                    $image = reset($potentialImages);
                    $imageUrl = $image['url'];
                    
                    // Update the author
                    $updateStmt = $db->prepare("UPDATE authors SET avatar_url = ? WHERE id = ?");
                    $updateStmt->execute([$imageUrl, $author['id']]);
                    
                    $messages[] = "Fixed author: {$author['name']} - Set avatar_url to: {$imageUrl}";
                    $fixed++;
                } else {
                    $errors[] = "No matching images found for author: {$author['name']}";
                }
            }
        } elseif (isset($_POST['manual_fix']) && isset($_POST['author_id']) && isset($_POST['image_url'])) {
            // Manual fix for a specific author
            $authorId = $_POST['author_id'];
            $imageUrl = $_POST['image_url'];
            
            // Get author name for the message
            $stmt = $db->prepare("SELECT name FROM authors WHERE id = ?");
            $stmt->execute([$authorId]);
            $authorName = $stmt->fetchColumn();
            
            // Update the author
            $updateStmt = $db->prepare("UPDATE authors SET avatar_url = ? WHERE id = ?");
            $updateStmt->execute([$imageUrl, $authorId]);
            
            $messages[] = "Manually fixed author: {$authorName} - Set avatar_url to: {$imageUrl}";
            $fixed++;
        }
        
        // Commit transaction
        $db->commit();
        
        $messages[] = "Fixed {$fixed} authors.";
        
    } catch (Exception $e) {
        // Rollback transaction on error
        if (isset($db)) {
            $db->rollBack();
        }
        
        $errors[] = "Error: " . $e->getMessage();
    }
}

// Get all authors with NULL avatar_url
$stmt = $db->query("SELECT * FROM authors WHERE avatar_url IS NULL OR avatar_url = '' ORDER BY name");
$authorsWithNullImages = $stmt->fetchAll();

// Count total authors
$stmt = $db->query("SELECT COUNT(*) FROM authors");
$totalAuthors = $stmt->fetchColumn();

// Count authors with NULL avatar_url
$stmt = $db->query("SELECT COUNT(*) FROM authors WHERE avatar_url IS NULL OR avatar_url = ''");
$authorsWithNullImagesCount = $stmt->fetchColumn();

?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="content-header">
                <h1>Fix Author Images</h1>
                <p class="text-muted">Fix authors with missing avatar images</p>
            </div>
            
            <?php if (!empty($messages)): ?>
                <div class="alert alert-success">
                    <ul class="mb-0">
                        <?php foreach ($messages as $message): ?>
                            <li><?php echo htmlspecialchars($message); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h2 class="card-title">Author Image Status</h2>
                </div>
                <div class="card-body">
                    <p>Total authors: <?php echo $totalAuthors; ?></p>
                    <p>Authors with missing images: <?php echo $authorsWithNullImagesCount; ?></p>
                    
                    <?php if ($authorsWithNullImagesCount > 0): ?>
                        <form method="POST" action="">
                            <button type="submit" name="fix_all" class="btn btn-primary">
                                <i class="fas fa-magic"></i> Auto-Fix All Missing Images
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> All authors have images!
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if (!empty($authorsWithNullImages)): ?>
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Authors with Missing Images</h2>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($authorsWithNullImages as $author): ?>
                                        <tr>
                                            <td><?php echo $author['id']; ?></td>
                                            <td><?php echo htmlspecialchars($author['name']); ?></td>
                                            <td>
                                                <form method="POST" action="" class="d-flex">
                                                    <input type="hidden" name="author_id" value="<?php echo $author['id']; ?>">
                                                    <input type="text" name="image_url" class="form-control form-control-sm me-2" placeholder="Enter image URL">
                                                    <button type="submit" name="manual_fix" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-save"></i> Save
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Include footer
require_once '../includes/footer.php';
?>
