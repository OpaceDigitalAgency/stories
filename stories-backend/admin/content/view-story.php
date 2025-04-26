<?php
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

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid story ID.";
    header("Location: stories.php");
    exit;
}

$storyId = (int)$_GET['id'];

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

    // Get story details
    $stmt = $db->prepare("SELECT * FROM stories WHERE id = ?");
    $stmt->execute([$storyId]);
    $story = $stmt->fetch();

    if (!$story) {
        $_SESSION['error'] = "Story not found.";
        header("Location: stories.php");
        exit;
    }

    // Get author information
    try {
        $stmt = $db->prepare("
            SELECT a.id, a.name
            FROM story_authors sa
            JOIN authors a ON sa.author_id = a.id
            WHERE sa.story_id = ?
        ");
        $stmt->execute([$storyId]);
        $author = $stmt->fetch();
        
        if ($author) {
            $story['author_id'] = $author['id'];
            $story['author_name'] = $author['name'];
        } else {
            $story['author_name'] = $story['author'] ?? 'Unknown';
        }
    } catch (Exception $e) {
        error_log("Error fetching author for story ID " . $storyId . ": " . $e->getMessage());
        $story['author_name'] = $story['author'] ?? 'Unknown';
    }
    
    // Get tags for the story
    try {
        $stmt = $db->prepare("
            SELECT GROUP_CONCAT(t.name ORDER BY t.name ASC SEPARATOR ', ') as tags
            FROM story_tags st
            JOIN tags t ON st.tag_id = t.id
            WHERE st.story_id = ?
        ");
        $stmt->execute([$storyId]);
        $tags = $stmt->fetch();
        
        if ($tags && isset($tags['tags'])) {
            $story['tags'] = $tags['tags'];
        } else {
            $story['tags'] = '';
        }
    } catch (Exception $e) {
        error_log("Error fetching tags for story ID " . $storyId . ": " . $e->getMessage());
        $story['tags'] = '';
    }

} catch (PDOException $e) {
    error_log("View story error: " . $e->getMessage());
    $_SESSION['error'] = "Error loading story. Please try again.";
    header("Location: stories.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Story - Admin</title>
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
                <button type="submit" formaction="stories.php" class="nav-link active">Stories</button>
                <button type="submit" formaction="blog-posts.php" class="nav-link">Blog Posts</button>
                <button type="submit" formaction="authors.php" class="nav-link">Authors</button>
                <button type="submit" formaction="tags.php" class="nav-link">Tags</button>
                <button type="submit" formaction="games.php" class="nav-link">Games</button>
                <button type="submit" formaction="directory-items.php" class="nav-link">Directory</button>
                <button type="submit" formaction="ai-tools.php" class="nav-link">AI Tools</button>
                <button type="submit" formaction="media.php" class="nav-link">Media</button>
            </form>
        </nav>

        <div class="page-header d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="page-title">View Story</h1>
                <p class="page-description">
                    <a href="stories.php" class="text-primary">← Back to Stories</a>
                </p>
            </div>
            <div class="d-flex gap-2">
                <form method="GET" action="story-form.php">
                    <input type="hidden" name="id" value="<?php echo $story['id']; ?>">
                    <button type="submit" class="btn btn-primary">
                        <span class="icon-edit"></span> Edit
                    </button>
                </form>
                <form method="POST" action="delete-story.php" onsubmit="return confirm('Are you sure you want to delete this story?');">
                    <input type="hidden" name="id" value="<?php echo $story['id']; ?>">
                    <button type="submit" class="btn btn-danger">
                        <span class="icon-delete"></span> Delete
                    </button>
                </form>
            </div>
        </div>

        <div class="content-section mb-4">
            <div class="section-header">
                <h2 class="section-title"><?php echo htmlspecialchars($story['title']); ?></h2>
            </div>
            <div class="section-body">
                <div class="mb-4">
                    <div class="d-flex gap-3 mb-3">
                        <div>
                            <strong>Author:</strong> 
                            <?php echo htmlspecialchars($story['author_name']); ?>
                        </div>
                        <div>
                            <strong>Created:</strong> 
                            <?php echo date('M j, Y', strtotime($story['created_at'])); ?>
                        </div>
                        <div>
                            <strong>Updated:</strong> 
                            <?php echo date('M j, Y', strtotime($story['updated_at'])); ?>
                        </div>
                    </div>
                    
                    <?php if (!empty($story['tags'])): ?>
                    <div class="mb-3">
                        <strong>Tags:</strong> 
                        <?php echo htmlspecialchars($story['tags']); ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php 
                    // Check if any additional fields exist and display them
                    $skipFields = ['id', 'title', 'content', 'created_at', 'updated_at', 'author_id', 'author_name', 'tags'];
                    foreach ($story as $key => $value) {
                        if (!in_array($key, $skipFields) && !is_null($value) && $value !== '') {
                            echo '<div class="mb-2"><strong>' . htmlspecialchars(ucfirst(str_replace('_', ' ', $key))) . ':</strong> ' . 
                                 htmlspecialchars($value) . '</div>';
                        }
                    }
                    ?>
                </div>
                
                <div class="content-preview">
                    <h3 class="mb-3">Content</h3>
                    <div class="content-body p-4 bg-light border rounded">
                        <?php 
                        // Check if content might be HTML
                        if (strpos($story['content'], '<') !== false && strpos($story['content'], '>') !== false) {
                            // It might be HTML, so display it as is
                            echo $story['content']; 
                        } else {
                            // It's plain text, so preserve line breaks
                            echo nl2br(htmlspecialchars($story['content']));
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="d-flex justify-content-between mt-4">
            <a href="stories.php" class="btn btn-secondary">
                Back to Stories
            </a>
            <form method="GET" action="story-form.php">
                <input type="hidden" name="id" value="<?php echo $story['id']; ?>">
                <button type="submit" class="btn btn-primary">
                    <span class="icon-edit"></span> Edit Story
                </button>
            </form>
        </div>
    </div>
    
    <style>
        .content-body {
            max-height: 600px;
            overflow-y: auto;
            white-space: pre-wrap;
        }
        
        .bg-light {
            background-color: var(--gray-50);
        }
        
        .border {
            border: 1px solid var(--border-color);
        }
        
        .rounded {
            border-radius: var(--radius-md);
        }
        
        .p-4 {
            padding: 1.5rem;
        }
    </style>
</body>
</html>