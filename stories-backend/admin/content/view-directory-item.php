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
    $_SESSION['error'] = "Invalid directory item ID.";
    header("Location: directory-items.php");
    exit;
}

$itemId = (int)$_GET['id'];

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

    // Get directory item details
    $stmt = $db->prepare("
        SELECT d.*, c.name as category_name 
        FROM directory_items d 
        LEFT JOIN directory_categories c ON d.category_id = c.id 
        WHERE d.id = ?
    ");
    $stmt->execute([$itemId]);
    $item = $stmt->fetch();

    if (!$item) {
        $_SESSION['error'] = "Directory item not found.";
        header("Location: directory-items.php");
        exit;
    }

} catch (PDOException $e) {
    error_log("View directory item error: " . $e->getMessage());
    $_SESSION['error'] = "Error loading directory item. Please try again.";
    header("Location: directory-items.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Directory Item - Admin</title>
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
                <button type="submit" formaction="tags.php" class="nav-link">Tags</button>
                <button type="submit" formaction="games.php" class="nav-link">Games</button>
                <button type="submit" formaction="directory-items.php" class="nav-link active">Directory</button>
                <button type="submit" formaction="ai-tools.php" class="nav-link">AI Tools</button>
                <button type="submit" formaction="media.php" class="nav-link">Media</button>
            </form>
        </nav>

        <div class="page-header d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="page-title">View Directory Item</h1>
                <p class="page-description">
                    <a href="directory-items.php" class="text-primary">← Back to Directory Items</a>
                </p>
            </div>
            <div class="d-flex gap-2">
                <form method="GET" action="directory-item-form.php">
                    <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                    <button type="submit" class="btn btn-primary">
                        <span class="icon-edit"></span> Edit
                    </button>
                </form>
                <form method="POST" action="delete-directory-item.php" onsubmit="return confirm('Are you sure you want to delete this directory item?');">
                    <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                    <button type="submit" class="btn btn-danger">
                        <span class="icon-delete"></span> Delete
                    </button>
                </form>
            </div>
        </div>

        <div class="content-section mb-4">
            <div class="section-header">
                <h2 class="section-title"><?php echo htmlspecialchars($item['title']); ?></h2>
            </div>
            <div class="section-body">
                <div class="mb-4">
                    <div class="d-flex gap-3 mb-3">
                        <div>
                            <strong>Category:</strong> 
                            <?php echo htmlspecialchars($item['category_name'] ?? 'None'); ?>
                        </div>
                        <div>
                            <strong>Featured:</strong> 
                            <?php echo $item['featured'] ? 'Yes' : 'No'; ?>
                        </div>
                        <div>
                            <strong>Published:</strong> 
                            <?php echo $item['is_published'] ? 'Yes' : 'No'; ?>
                        </div>
                    </div>
                    
                    <?php if (!empty($item['website_url'])): ?>
                    <div class="mb-3">
                        <strong>Website:</strong> 
                        <a href="<?php echo htmlspecialchars($item['website_url']); ?>" target="_blank">
                            <?php echo htmlspecialchars($item['website_url']); ?>
                        </a>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($item['contact_email'])): ?>
                    <div class="mb-3">
                        <strong>Contact Email:</strong> 
                        <a href="mailto:<?php echo htmlspecialchars($item['contact_email']); ?>">
                            <?php echo htmlspecialchars($item['contact_email']); ?>
                        </a>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($item['contact_phone'])): ?>
                    <div class="mb-3">
                        <strong>Contact Phone:</strong> 
                        <?php echo htmlspecialchars($item['contact_phone']); ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($item['address'])): ?>
                    <div class="mb-3">
                        <strong>Address:</strong> 
                        <?php echo nl2br(htmlspecialchars($item['address'])); ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($item['created_at'])): ?>
                    <div class="mb-3">
                        <strong>Created:</strong> 
                        <?php echo date('M j, Y', strtotime($item['created_at'])); ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($item['updated_at'])): ?>
                    <div class="mb-3">
                        <strong>Updated:</strong> 
                        <?php echo date('M j, Y', strtotime($item['updated_at'])); ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($item['published_at']) && !empty($item['published_at'])): ?>
                    <div class="mb-3">
                        <strong>Published Date:</strong> 
                        <?php echo date('M j, Y', strtotime($item['published_at'])); ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php 
                    // Check if any additional fields exist and display them
                    $skipFields = ['id', 'title', 'description', 'category_id', 'category_name', 'website_url', 
                                  'contact_email', 'contact_phone', 'address', 'featured', 'is_published', 
                                  'slug', 'published_at', 'created_at', 'updated_at'];
                    foreach ($item as $key => $value) {
                        if (!in_array($key, $skipFields) && !is_null($value) && $value !== '') {
                            echo '<div class="mb-2"><strong>' . htmlspecialchars(ucfirst(str_replace('_', ' ', $key))) . ':</strong> ' . 
                                 htmlspecialchars($value) . '</div>';
                        }
                    }
                    ?>
                </div>
                
                <?php if (!empty($item['description'])): ?>
                <div class="content-preview">
                    <h3 class="mb-3">Description</h3>
                    <div class="content-body p-4 bg-light border rounded">
                        <?php 
                        // Check if description might be HTML
                        if (strpos($item['description'], '<') !== false && strpos($item['description'], '>') !== false) {
                            // It might be HTML, so display it as is
                            echo $item['description']; 
                        } else {
                            // It's plain text, so preserve line breaks
                            echo nl2br(htmlspecialchars($item['description']));
                        }
                        ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="d-flex justify-content-between mt-4">
            <a href="directory-items.php" class="btn btn-secondary">
                Back to Directory Items
            </a>
            <form method="GET" action="directory-item-form.php">
                <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                <button type="submit" class="btn btn-primary">
                    <span class="icon-edit"></span> Edit Directory Item
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
</body>
</html>