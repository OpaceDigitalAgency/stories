<?php

// Include header
include '../includes/header.php';


// Page variables
$pageTitle = 'View Tag';
$currentPage = 'view-tag';

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
    $_SESSION['error'] = "Invalid tag ID.";
    header("Location: tags.php");
    exit;
}

$tagId = (int)$_GET['id'];

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

    // Get tag details
    $stmt = $db->prepare("SELECT * FROM tags WHERE id = ?");
    $stmt->execute([$tagId]);
    $tag = $stmt->fetch();

    if (!$tag) {
        $_SESSION['error'] = "Tag not found.";
        header("Location: tags.php");
        exit;
    }

    // Get stories with this tag
    $stmt = $db->prepare("
        SELECT s.id, s.title, s.slug 
        FROM stories s 
        JOIN story_tags st ON s.id = st.story_id 
        WHERE st.tag_id = ? 
        ORDER BY s.title ASC
    ");
    $stmt->execute([$tagId]);
    $stories = $stmt->fetchAll();

    // Get blog posts with this tag
    $stmt = $db->prepare("
        SELECT bp.id, bp.title 
        FROM blog_posts bp 
        JOIN post_tags pt ON bp.id = pt.post_id 
        WHERE pt.tag_id = ? 
        ORDER BY bp.title ASC
    ");
    $stmt->execute([$tagId]);
    $posts = $stmt->fetchAll();

    // If blog_posts table doesn't exist, try the blog table
    if (empty($posts)) {
        $stmt = $db->query("SHOW TABLES LIKE 'blog'");
        if ($stmt->rowCount() > 0) {
            $stmt = $db->prepare("
                SELECT b.id, b.title 
                FROM blog b 
                JOIN post_tags pt ON b.id = pt.post_id 
                WHERE pt.tag_id = ? 
                ORDER BY b.title ASC
            ");
            $stmt->execute([$tagId]);
            $posts = $stmt->fetchAll();
        }
    }

} catch (PDOException $e) {
    error_log("View tag error: " . $e->getMessage());
    $_SESSION['error'] = "Error loading tag details. Please try again.";
    header("Location: tags.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Tag - Admin</title>
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
                <button type="submit" formaction="authors.php" class="nav-link">Authors</button>
                <button type="submit" formaction="tags.php" class="nav-link active">Tags</button>
                <button type="submit" formaction="games.php" class="nav-link">Games</button>
                <button type="submit" formaction="directory-items.php" class="nav-link">Directory</button>
                <button type="submit" formaction="ai-tools.php" class="nav-link">AI Tools</button>
                <button type="submit" formaction="media.php" class="nav-link">Media</button>
            </form>
        </nav>

        <div class="page-header d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="page-title">View Tag</h1>
                <p class="page-description">
                    <a href="tags.php" class="text-primary">← Back to Tags</a>
                </p>
            </div>
            <div class="d-flex gap-2">
                <form method="GET" action="tag-form.php">
                    <input type="hidden" name="id" value="<?php echo $tag['id']; ?>">
                    <button type="submit" class="btn btn-primary">
                        <span class="icon-edit"></span> Edit
                    </button>
                </form>
                <form method="POST" action="delete-tag.php" onsubmit="return confirm('Are you sure you want to delete this tag?');">
                    <input type="hidden" name="id" value="<?php echo $tag['id']; ?>">
                    <button type="submit" class="btn btn-danger">
                        <span class="icon-delete"></span> Delete
                    </button>
                </form>
            </div>
        </div>

        <div class="content-section mb-4">
            <div class="section-header">
                <h2 class="section-title"><?php echo htmlspecialchars($tag['name']); ?></h2>
            </div>
            <div class="section-body">
                <div class="mb-4">
                    <div class="mb-3">
                        <strong>Slug:</strong> 
                        <?php echo htmlspecialchars($tag['slug']); ?>
                    </div>
                    
                    <?php if (!empty($tag['description'])): ?>
                    <div class="mb-3">
                        <strong>Description:</strong><br>
                        <div class="p-3 bg-light border rounded">
                            <?php echo nl2br(htmlspecialchars($tag['description'])); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($tag['created_at'])): ?>
                    <div class="mb-3">
                        <strong>Created:</strong> 
                        <?php echo date('M j, Y', strtotime($tag['created_at'])); ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($tag['updated_at'])): ?>
                    <div class="mb-3">
                        <strong>Updated:</strong> 
                        <?php echo date('M j, Y', strtotime($tag['updated_at'])); ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if (!empty($stories)): ?>
        <div class="content-section mb-4">
            <div class="section-header">
                <h3 class="section-title">Stories with this tag (<?php echo count($stories); ?>)</h3>
            </div>
            <div class="section-body">
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Slug</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stories as $story): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($story['title']); ?></td>
                                    <td><?php echo htmlspecialchars($story['slug']); ?></td>
                                    <td>
                                        <div class="table-actions">
                                            <form method="GET" action="view-story.php" style="display: inline;">
                                                <input type="hidden" name="id" value="<?php echo $story['id']; ?>">
                                                <button type="submit" class="btn btn-info btn-sm">
                                                    <span class="icon-view"></span> View
                                                </button>
                                            </form>
                                            <form method="GET" action="story-form.php" style="display: inline;">
                                                <input type="hidden" name="id" value="<?php echo $story['id']; ?>">
                                                <button type="submit" class="btn btn-primary btn-sm">
                                                    <span class="icon-edit"></span> Edit
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($posts)): ?>
        <div class="content-section mb-4">
            <div class="section-header">
                <h3 class="section-title">Blog Posts with this tag (<?php echo count($posts); ?>)</h3>
            </div>
            <div class="section-body">
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($posts as $post): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($post['title']); ?></td>
                                    <td>
                                        <div class="table-actions">
                                            <form method="GET" action="view-post.php" style="display: inline;">
                                                <input type="hidden" name="id" value="<?php echo $post['id']; ?>">
                                                <button type="submit" class="btn btn-info btn-sm">
                                                    <span class="icon-view"></span> View
                                                </button>
                                            </form>
                                            <form method="GET" action="post-form.php" style="display: inline;">
                                                <input type="hidden" name="id" value="<?php echo $post['id']; ?>">
                                                <button type="submit" class="btn btn-primary btn-sm">
                                                    <span class="icon-edit"></span> Edit
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="d-flex justify-content-between mt-4">
            <a href="tags.php" class="btn btn-secondary">
                Back to Tags
            </a>
            <form method="GET" action="tag-form.php">
                <input type="hidden" name="id" value="<?php echo $tag['id']; ?>">
                <button type="submit" class="btn btn-primary">
                    <span class="icon-edit"></span> Edit Tag
                </button>
            </form>
        </div>
    </div>
    
    <style>
        .bg-light {
            background-color: var(--gray-50);
        }
        
        .border {
            border: 1px solid var(--border-color);
        }
        
        .rounded {
            border-radius: var(--radius-md);
        }
        
        .p-3 {
            padding: 1rem;
        }
        
        .gap-2 {
            gap: 0.5rem;
        }
    </style>

// Include footer
include '../includes/footer.php';
