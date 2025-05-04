<?php

// Include header
include '../includes/header.php';


// Page variables
$pageTitle = 'View Post';
$currentPage = 'view-post';

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
    $_SESSION['error'] = "Invalid blog post ID.";
    header("Location: blog-posts.php");
    exit;
}

$postId = (int)$_GET['id'];

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

    // Check if blog_posts or blog table exists
    $blogTableName = 'blog_posts';
    $stmt = $db->query("SHOW TABLES LIKE 'blog_posts'");
    if ($stmt->rowCount() === 0) {
        // Check if blog table exists instead
        $stmt = $db->query("SHOW TABLES LIKE 'blog'");
        if ($stmt->rowCount() > 0) {
            $blogTableName = 'blog';
        } else {
            $_SESSION['error'] = "Blog posts table not found.";
            header("Location: blog-posts.php");
            exit;
        }
    }

    // Check if post_tags table exists
    $postTagsTableName = 'post_tags';
    $stmt = $db->query("SHOW TABLES LIKE 'post_tags'");
    if ($stmt->rowCount() === 0) {
        // Check if blog_tags table exists instead
        $stmt = $db->query("SHOW TABLES LIKE 'blog_tags'");
        if ($stmt->rowCount() > 0) {
            $postTagsTableName = 'blog_tags';
        }
    }

    // Get all columns from the blog table
    $columns = [];
    $stmt = $db->query("DESCRIBE $blogTableName");
    while ($row = $stmt->fetch()) {
        $columns[] = $row['Field'];
    }

    // Check if author_id column exists
    $hasAuthorIdColumn = in_array('author_id', $columns);
    $authorJoinCondition = $hasAuthorIdColumn ? "bp.author_id = a.id" : "1=0"; // No join if no author_id

    // Get blog post details
    $query = "SELECT bp.*, a.name as author_name";
    
    // Add tags subquery if the post_tags table exists
    $stmt = $db->query("SHOW TABLES LIKE '$postTagsTableName'");
    if ($stmt->rowCount() > 0) {
        $query .= ", (SELECT GROUP_CONCAT(t.name ORDER BY t.name ASC SEPARATOR ', ') 
                   FROM $postTagsTableName pt 
                   JOIN tags t ON pt.tag_id = t.id 
                   WHERE pt.post_id = bp.id) as tags";
    } else {
        $query .= ", '' as tags";
    }
    
    $query .= " FROM $blogTableName bp 
               LEFT JOIN authors a ON $authorJoinCondition
               WHERE bp.id = ?";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$postId]);
    $post = $stmt->fetch();

    if (!$post) {
        $_SESSION['error'] = "Blog post not found.";
        header("Location: blog-posts.php");
        exit;
    }

} catch (PDOException $e) {
    error_log("View blog post error: " . $e->getMessage());
    $_SESSION['error'] = "Error loading blog post. Please try again.";
    header("Location: blog-posts.php");
    exit;
}
?>

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
                <button type="submit" formaction="blog-posts.php" class="nav-link active">Blog Posts</button>
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
                <h1 class="page-title">View Blog Post</h1>
                <p class="page-description">
                    <a href="blog-posts.php" class="text-primary">← Back to Blog Posts</a>
                </p>
            </div>
            <div class="d-flex gap-2">
                <form method="GET" action="post-form.php">
                    <input type="hidden" name="id" value="<?php echo $post['id']; ?>">
                    <button type="submit" class="btn btn-primary">
                        <span class="icon-edit"></span> Edit
                    </button>
                </form>
                <form method="POST" action="delete-post.php" onsubmit="return confirm('Are you sure you want to delete this post?');">
                    <input type="hidden" name="id" value="<?php echo $post['id']; ?>">
                    <button type="submit" class="btn btn-danger">
                        <span class="icon-delete"></span> Delete
                    </button>
                </form>
            </div>
        </div>

        <div class="content-section mb-4">
            <div class="section-header">
                <h2 class="section-title"><?php echo htmlspecialchars($post['title']); ?></h2>
            </div>
            <div class="section-body">
                <div class="mb-4">
                    <div class="d-flex gap-3 mb-3">
                        <?php if (isset($post['author_name']) && !empty($post['author_name'])): ?>
                        <div>
                            <strong>Author:</strong> 
                            <?php echo htmlspecialchars($post['author_name']); ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (isset($post['status'])): ?>
                        <div>
                            <strong>Status:</strong> 
                            <?php echo ucfirst(htmlspecialchars($post['status'])); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!empty($post['tags'])): ?>
                    <div class="mb-3">
                        <strong>Tags:</strong> 
                        <?php echo htmlspecialchars($post['tags']); ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($post['created_at'])): ?>
                    <div class="mb-3">
                        <strong>Created:</strong> 
                        <?php echo date('M j, Y', strtotime($post['created_at'])); ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($post['updated_at'])): ?>
                    <div class="mb-3">
                        <strong>Updated:</strong> 
                        <?php echo date('M j, Y', strtotime($post['updated_at'])); ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($post['published_at']) && !empty($post['published_at'])): ?>
                    <div class="mb-3">
                        <strong>Published Date:</strong> 
                        <?php echo date('M j, Y', strtotime($post['published_at'])); ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php 
                    // Check if any additional fields exist and display them
                    $skipFields = ['id', 'title', 'content', 'excerpt', 'author_id', 'author_name', 'tags', 
                                  'status', 'created_at', 'updated_at', 'published_at'];
                    foreach ($post as $key => $value) {
                        if (!in_array($key, $skipFields) && !is_null($value) && $value !== '') {
                            echo '<div class="mb-2"><strong>' . htmlspecialchars(ucfirst(str_replace('_', ' ', $key))) . ':</strong> ' . 
                                 htmlspecialchars($value) . '</div>';
                        }
                    }
                    ?>
                </div>
                
                <?php if (!empty($post['excerpt'])): ?>
                <div class="content-preview mb-4">
                    <h3 class="mb-3">Excerpt</h3>
                    <div class="content-body p-4 bg-light border rounded">
                        <?php 
                        // Check if excerpt might be HTML
                        if (strpos($post['excerpt'], '<') !== false && strpos($post['excerpt'], '>') !== false) {
                            // It might be HTML, so display it as is
                            echo $post['excerpt']; 
                        } else {
                            // It's plain text, so preserve line breaks
                            echo nl2br(htmlspecialchars($post['excerpt']));
                        }
                        ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($post['content'])): ?>
                <div class="content-preview">
                    <h3 class="mb-3">Content</h3>
                    <div class="content-body p-4 bg-light border rounded">
                        <?php 
                        // Check if content might be HTML
                        if (strpos($post['content'], '<') !== false && strpos($post['content'], '>') !== false) {
                            // It might be HTML, so display it as is
                            echo $post['content']; 
                        } else {
                            // It's plain text, so preserve line breaks
                            echo nl2br(htmlspecialchars($post['content']));
                        }
                        ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="d-flex justify-content-between mt-4">
            <a href="blog-posts.php" class="btn btn-secondary">
                Back to Blog Posts
            </a>
            <form method="GET" action="post-form.php">
                <input type="hidden" name="id" value="<?php echo $post['id']; ?>">
                <button type="submit" class="btn btn-primary">
                    <span class="icon-edit"></span> Edit Post
                </button>
            </form>
        </div>
    </div>
    
    <style>
        .content-body {
            max-height: 400px;
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
        
        .gap-2 {
            gap: 0.5rem;
        }
        
        .gap-3 {
            gap: 1rem;
        }
    </style>

// Include footer
include '../includes/footer.php';
