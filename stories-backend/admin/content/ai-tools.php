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

// Initialize variables
$ai_tools = [];
$error = null;
$success = null;

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

    // Check if ai_tools table exists
    $stmt = $db->query("SHOW TABLES LIKE 'ai_tools'");
    if ($stmt->rowCount() === 0) {
        // Create ai_tools table if it doesn't exist
        $db->exec("CREATE TABLE IF NOT EXISTS ai_tools (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            category_id INT,
            tool_url VARCHAR(255),
            pricing_type ENUM('free', 'freemium', 'paid', 'subscription') DEFAULT 'free',
            price_info VARCHAR(255),
            features TEXT,
            rating DECIMAL(3,1) DEFAULT 0,
            featured TINYINT(1) DEFAULT 0,
            is_published TINYINT(1) DEFAULT 0,
            slug VARCHAR(255) NOT NULL,
            published_at DATETIME,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL
        )");
    }

    // Check if ai_tool_categories table exists
    $stmt = $db->query("SHOW TABLES LIKE 'ai_tool_categories'");
    if ($stmt->rowCount() === 0) {
        // Create ai_tool_categories table if it doesn't exist
        $db->exec("CREATE TABLE IF NOT EXISTS ai_tool_categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL,
            description TEXT,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL
        )");
        
        // Add some default categories
        $db->exec("INSERT INTO ai_tool_categories (name, slug, description, created_at, updated_at) VALUES 
            ('Text Generation', 'text-generation', 'AI tools for generating text content', NOW(), NOW()),
            ('Image Generation', 'image-generation', 'AI tools for generating images', NOW(), NOW()),
            ('Content Summarization', 'content-summarization', 'AI tools for summarizing content', NOW(), NOW()),
            ('Translation', 'translation', 'AI tools for translating content', NOW(), NOW()),
            ('Chatbots', 'chatbots', 'AI chatbot tools', NOW(), NOW())
        ");
    }

    // Get all AI tools with category names
    $ai_tools = $db->query("
        SELECT a.*, c.name as category_name 
        FROM ai_tools a 
        LEFT JOIN ai_tool_categories c ON a.category_id = c.id 
        ORDER BY a.created_at DESC
    ")->fetchAll();

} catch (PDOException $e) {
    error_log("AI tools page error: " . $e->getMessage());
    $error = "Error loading AI tools data. Please try again.";
} catch (Exception $e) {
    $error = $e->getMessage();
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
    <title>AI Tools - Admin</title>
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
                <button type="submit" formaction="directory-items.php" class="nav-link">Directory</button>
                <button type="submit" formaction="ai-tools.php" class="nav-link active">AI Tools</button>
                <button type="submit" formaction="media.php" class="nav-link">Media</button>
            </form>
        </nav>

        <div class="page-header d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="page-title">AI Tools</h1>
                <p class="page-description">Manage all your AI tools from here.</p>
            </div>
            <form method="GET" action="ai-tool-form.php">
                <button type="submit" class="btn btn-success">
                    <span class="icon-edit"></span> Add New AI Tool
                </button>
            </form>
        </div>

        <?php if (isset($success)): ?>
            <div class="success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Pricing</th>
                        <th>Rating</th>
                        <th>Featured</th>
                        <th>Published</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($ai_tools)): ?>
                        <tr>
                            <td colspan="8" class="text-center">No AI tools found. Add your first AI tool!</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($ai_tools as $tool): ?>
                            <tr>
                                <td><?php echo $tool['id']; ?></td>
                                <td><?php echo htmlspecialchars($tool['title']); ?></td>
                                <td><?php echo htmlspecialchars($tool['category_name'] ?? 'None'); ?></td>
                                <td><?php echo ucfirst($tool['pricing_type']); ?></td>
                                <td><?php echo number_format($tool['rating'], 1); ?></td>
                                <td><?php echo $tool['featured'] ? 'Yes' : 'No'; ?></td>
                                <td><?php echo $tool['is_published'] ? 'Yes' : 'No'; ?></td>
                                <td>
                                    <div class="table-actions">
                                        <form method="GET" action="view-ai-tool.php" style="display: inline;">
                                            <input type="hidden" name="id" value="<?php echo $tool['id']; ?>">
                                            <button type="submit" class="btn btn-info btn-sm">
                                                <span class="icon-view"></span> View
                                            </button>
                                        </form>
                                        <form method="GET" action="ai-tool-form.php" style="display: inline;">
                                            <input type="hidden" name="id" value="<?php echo $tool['id']; ?>">
                                            <button type="submit" class="btn btn-primary btn-sm">
                                                <span class="icon-edit"></span> Edit
                                            </button>
                                        </form>
                                        <form method="POST" action="delete-ai-tool.php" style="display: inline;">
                                            <input type="hidden" name="id" value="<?php echo $tool['id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm" 
                                                    onclick="return confirm('Are you sure you want to delete this AI tool?')">
                                                <span class="icon-delete"></span> Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>