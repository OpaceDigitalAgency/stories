<?php

// Include header
include '../includes/header.php';


// Page variables
$pageTitle = 'Author Delete';
$currentPage = 'author-delete';

require_once '../../simple_auth.php';

// Database configuration
$config = [
    'host' => 'localhost',
    'name' => 'stories_db',
    'user' => 'stories_user',
    'password' => '$tw1cac3*sOt',
    'charset' => 'utf8mb4',
    'port' => 3306
];

// Initialize SimpleAuth
SimpleAuth::initDB($config);

// Check if user is logged in
if (!$user = SimpleAuth::check()) {
    header("Location: ../login.php");
    exit;
}

// Get author details
$authorId = $_GET['id'] ?? 0;

try {
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

    // Get author details
    $stmt = $db->prepare("SELECT * FROM authors WHERE id = ?");
    $stmt->execute([$authorId]);
    $author = $stmt->fetch();

    if (!$author) {
        $_SESSION['error'] = "Author not found.";
        header("Location: authors.php");
        exit;
    }

    // Check if story_authors junction table exists
    $hasStoryAuthorsTable = false;
    try {
        $stmt = $db->query("SHOW TABLES LIKE 'story_authors'");
        $hasStoryAuthorsTable = $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        // Table might not exist, ignore
    }

    // Get story count
    $storyCount = 0;
    if ($hasStoryAuthorsTable) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM story_authors WHERE author_id = ?");
        $stmt->execute([$authorId]);
        $storyCount = $stmt->fetchColumn();
    } else {
        // Check if stories table has author_id column
        try {
            $stmt = $db->query("SHOW COLUMNS FROM stories LIKE 'author_id'");
            if ($stmt->rowCount() > 0) {
                $stmt = $db->prepare("SELECT COUNT(*) FROM stories WHERE author_id = ?");
                $stmt->execute([$authorId]);
                $storyCount = $stmt->fetchColumn();
            }
        } catch (PDOException $e) {
            // Table might not exist, ignore
        }
    }

    // Get other authors for reassignment
    $stmt = $db->prepare("SELECT id, name FROM authors WHERE id != ? ORDER BY name");
    $stmt->execute([$authorId]);
    $otherAuthors = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Author delete page error: " . $e->getMessage());
    $_SESSION['error'] = "Error loading author details. Please try again.";
    header("Location: authors.php");
    exit;
}

// Check for success/error messages
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Author - Admin</title>
    <link rel="stylesheet" href="../assets/css/modern-admin.css">
</head>
<body>
    <header class="admin-header">
        <div class="header-container">
            <div class="logo-container">
                <div class="logo">S</div>
                <div class="logo-text">Stories Admin</div>
            </div>
            <div class="user-info">
                <span class="user-name">Welcome, <?php echo htmlspecialchars($user['name']); ?></span>
                <form method="POST" action="../logout.php" style="display: inline;">
                    <button type="submit" class="btn btn-danger btn-sm">Logout</button>
                </form>
            </div>
        </div>
    </header>

    <div class="container">
        <nav class="nav-menu">
            <form method="GET" style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                <button type="submit" formaction="../dashboard.php" class="nav-link">Dashboard</button>
                <button type="submit" formaction="stories.php" class="nav-link">Stories</button>
                <button type="submit" formaction="blog-posts.php" class="nav-link">Blog Posts</button>
                <button type="submit" formaction="authors.php" class="nav-link active">Authors</button>
                <button type="submit" formaction="tags.php" class="nav-link">Tags</button>
                <button type="submit" formaction="games.php" class="nav-link">Games</button>
                <button type="submit" formaction="directory-items.php" class="nav-link">Directory</button>
                <button type="submit" formaction="ai-tools.php" class="nav-link">AI Tools</button>
                <button type="submit" formaction="media.php" class="nav-link">Media</button>
            </form>
        </nav>

        <div class="page-header d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="page-title">Delete Author</h1>
                <p class="page-description">Confirm author deletion</p>
            </div>
        </div>

        <?php if (isset($success)): ?>
            <div class="success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="content-section">
            <div class="section-body">
                <p>Are you sure you want to delete the author "<?php echo htmlspecialchars($author['name']); ?>"?</p>
                
                <?php if ($storyCount > 0): ?>
                    <div class="alert alert-warning">
                        <p>This author has <?php echo $storyCount; ?> associated stories. Please choose how to handle them:</p>
                        
                        <form action="delete-author.php" method="post" class="mt-3">
                            <input type="hidden" name="id" value="<?php echo $authorId; ?>">
                            
                            <div class="form-check mb-3">
                                <input type="radio" id="delete_all" name="action" value="delete_all" class="form-check-input">
                                <label for="delete_all" class="form-check-label">
                                    Delete all associated stories
                                </label>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input type="radio" id="reassign" name="action" value="reassign" class="form-check-input">
                                <label for="reassign" class="form-check-label">
                                    Reassign stories to another author:
                                </label>
                                <select name="new_author_id" class="form-control mt-2" id="new_author_select" disabled>
                                    <option value="">Select an author</option>
                                    <?php foreach ($otherAuthors as $otherAuthor): ?>
                                        <option value="<?php echo $otherAuthor['id']; ?>">
                                            <?php echo htmlspecialchars($otherAuthor['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input type="radio" id="cancel" name="action" value="cancel" class="form-check-input" checked>
                                <label for="cancel" class="form-check-label">
                                    Cancel deletion
                                </label>
                            </div>
                            
                            <button type="submit" class="btn btn-danger">Confirm</button>
                            <a href="authors.php" class="btn btn-secondary">Back</a>
                        </form>
                    </div>
                    
                    <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        document.querySelectorAll('input[name="action"]').forEach(radio => {
                            radio.addEventListener('change', function() {
                                document.getElementById('new_author_select').disabled = this.value !== 'reassign';
                            });
                        });
                    });
                    </script>
                <?php else: ?>
                    <form action="delete-author.php" method="post">
                        <input type="hidden" name="id" value="<?php echo $authorId; ?>">
                        <input type="hidden" name="action" value="delete_all">
                        <button type="submit" class="btn btn-danger">Delete Author</button>
                        <a href="authors.php" class="btn btn-secondary">Cancel</a>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

// Include footer
include '../includes/footer.php';
