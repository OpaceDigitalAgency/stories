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
$categories = [];
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
            slug VARCHAR(255) NOT NULL UNIQUE,
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
            slug VARCHAR(255) NOT NULL UNIQUE,
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

    // Get all categories
    $categories = $db->query("SELECT * FROM ai_tool_categories ORDER BY name")->fetchAll();

    // Handle AI tool creation/update
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $id = $_POST['id'] ?? null;
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $category_id = $_POST['category_id'] ?? null;
        $tool_url = trim($_POST['tool_url'] ?? '');
        $pricing_type = $_POST['pricing_type'] ?? 'free';
        $price_info = trim($_POST['price_info'] ?? '');
        $features = trim($_POST['features'] ?? '');
        $rating = $_POST['rating'] ?? 0;
        $featured = isset($_POST['featured']) ? 1 : 0;
        $is_published = isset($_POST['is_published']) ? 1 : 0;
        $slug = trim($_POST['slug'] ?? '');
        $published_at = $_POST['published_at'] ?? null;

        // Validate required fields
        if (empty($title)) {
            throw new Exception("Title is required");
        }

        // Generate slug if not provided
        if (empty($slug)) {
            $slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $title));
            $slug = trim($slug, '-');
        }

        // Format published_at
        if (!empty($published_at)) {
            $date = new DateTime($published_at);
            $published_at = $date->format('Y-m-d H:i:s');
        } else {
            $published_at = null;
        }

        if ($id) {
            // Update existing AI tool
            $stmt = $db->prepare("UPDATE ai_tools SET 
                title = ?, 
                description = ?, 
                category_id = ?, 
                tool_url = ?, 
                pricing_type = ?, 
                price_info = ?, 
                features = ?, 
                rating = ?, 
                featured = ?, 
                is_published = ?, 
                slug = ?, 
                published_at = ?, 
                updated_at = NOW() 
                WHERE id = ?");
            $stmt->execute([
                $title, 
                $description, 
                $category_id, 
                $tool_url, 
                $pricing_type, 
                $price_info, 
                $features, 
                $rating, 
                $featured, 
                $is_published, 
                $slug, 
                $published_at, 
                $id
            ]);
            $success = "AI tool updated successfully";
        } else {
            // Create new AI tool
            $stmt = $db->prepare("INSERT INTO ai_tools (
                title, 
                description, 
                category_id, 
                tool_url, 
                pricing_type, 
                price_info, 
                features, 
                rating, 
                featured, 
                is_published, 
                slug, 
                published_at, 
                created_at, 
                updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
            $stmt->execute([
                $title, 
                $description, 
                $category_id, 
                $tool_url, 
                $pricing_type, 
                $price_info, 
                $features, 
                $rating, 
                $featured, 
                $is_published, 
                $slug, 
                $published_at
            ]);
            $success = "AI tool created successfully";
        }
    }

    // Handle AI tool deletion
    if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
        $id = $_GET['delete'];
        $stmt = $db->prepare("DELETE FROM ai_tools WHERE id = ?");
        $stmt->execute([$id]);
        $success = "AI tool deleted successfully";
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
    <link rel="stylesheet" href="../assets/css/main.css">
</head>
<body>
    <div class="container">
        <div class="user-info">
            Welcome, <?php echo htmlspecialchars($user['name']); ?> |
            <form method="POST" action="../logout.php" style="display: inline;">
                <button type="submit" class="form-submit" style="background: #dc3545;">Logout</button>
            </form>
        </div>

        <nav class="nav-menu">
            <form method="GET" style="display: inline;">
                <button type="submit" formaction="../dashboard.php" class="nav-link">Dashboard</button>
                <button type="submit" formaction="stories.php" class="nav-link">Stories</button>
                <button type="submit" formaction="blog-posts.php" class="nav-link">Blog Posts</button>
                <button type="submit" formaction="authors.php" class="nav-link">Authors</button>
                <button type="submit" formaction="tags.php" class="nav-link">Tags</button>
                <button type="submit" formaction="games.php" class="nav-link">Games</button>
                <button type="submit" formaction="directory-items.php" class="nav-link">Directory</button>
                <button type="submit" formaction="ai-tools.php" class="nav-link">AI Tools</button>
                <button type="submit" formaction="media.php" class="nav-link">Media</button>
            </form>
        </nav>

        <div class="content-header">
            <h1>AI Tools</h1>
            <form method="GET" action="../dashboard.php" style="display: inline;">
                <button type="submit" class="form-submit" style="background: #6c757d;">Back to Dashboard</button>
            </form>
        </div>

        <?php if ($success): ?>
            <div class="success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="content-section">
            <h2>Add New AI Tool</h2>
            <form method="POST" class="ai-tool-form">
                <input type="hidden" name="id" value="">
                <div class="form-group">
                    <label class="form-label" for="title">Title <span class="required">*</span></label>
                    <input type="text" id="title" name="title" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="slug">Slug</label>
                    <input type="text" id="slug" name="slug" class="form-input">
                    <small>URL-friendly version of the title. Will be auto-generated if left empty.</small>
                </div>
                <div class="form-group">
                    <label class="form-label" for="category_id">Category</label>
                    <select id="category_id" name="category_id" class="form-input">
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="description">Description</label>
                    <textarea id="description" name="description" class="form-input" rows="5"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label" for="tool_url">Tool URL</label>
                    <input type="url" id="tool_url" name="tool_url" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label" for="pricing_type">Pricing Type</label>
                    <select id="pricing_type" name="pricing_type" class="form-input">
                        <option value="free">Free</option>
                        <option value="freemium">Freemium</option>
                        <option value="paid">Paid</option>
                        <option value="subscription">Subscription</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="price_info">Price Information</label>
                    <input type="text" id="price_info" name="price_info" class="form-input">
                    <small>E.g., "Free trial, $9.99/month" or "Starting at $19.99"</small>
                </div>
                <div class="form-group">
                    <label class="form-label" for="features">Features</label>
                    <textarea id="features" name="features" class="form-input" rows="5"></textarea>
                    <small>List key features, one per line</small>
                </div>
                <div class="form-group">
                    <label class="form-label" for="rating">Rating (0-5)</label>
                    <input type="number" id="rating" name="rating" class="form-input" min="0" max="5" step="0.1" value="0">
                </div>
                <div class="form-group checkbox-field">
                    <label class="checkbox-label">
                        <input type="checkbox" name="featured" value="1">
                        Featured
                    </label>
                </div>
                <div class="form-group checkbox-field">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_published" value="1">
                        Published
                    </label>
                </div>
                <div class="form-group">
                    <label class="form-label" for="published_at">Published at</label>
                    <input type="datetime-local" id="published_at" name="published_at" class="form-input" value="<?php echo date('Y-m-d\TH:i'); ?>">
                    <small>Format: YYYY-MM-DD HH:MM (pre-filled with current date/time)</small>
                </div>
                <div class="form-group">
                    <button type="submit" class="form-submit">Add AI Tool</button>
                </div>
            </form>
        </div>

        <div class="content-section">
            <h2>AI Tools List</h2>
            <?php if (empty($ai_tools)): ?>
                <p class="no-items">No AI tools found.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
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
                            <?php foreach ($ai_tools as $tool): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($tool['title']); ?></td>
                                    <td><?php echo htmlspecialchars($tool['category_name'] ?? 'None'); ?></td>
                                    <td><?php echo ucfirst($tool['pricing_type']); ?></td>
                                    <td><?php echo number_format($tool['rating'], 1); ?></td>
                                    <td><?php echo $tool['featured'] ? 'Yes' : 'No'; ?></td>
                                    <td><?php echo $tool['is_published'] ? 'Yes' : 'No'; ?></td>
                                    <td class="actions">
                                        <button class="action-btn edit" data-id="<?php echo $tool['id']; ?>" 
                                                data-title="<?php echo htmlspecialchars($tool['title']); ?>"
                                                data-slug="<?php echo htmlspecialchars($tool['slug']); ?>"
                                                data-category="<?php echo $tool['category_id']; ?>"
                                                data-description="<?php echo htmlspecialchars($tool['description'] ?? ''); ?>"
                                                data-url="<?php echo htmlspecialchars($tool['tool_url'] ?? ''); ?>"
                                                data-pricing="<?php echo $tool['pricing_type']; ?>"
                                                data-price-info="<?php echo htmlspecialchars($tool['price_info'] ?? ''); ?>"
                                                data-features="<?php echo htmlspecialchars($tool['features'] ?? ''); ?>"
                                                data-rating="<?php echo $tool['rating']; ?>"
                                                data-featured="<?php echo $tool['featured']; ?>"
                                                data-published="<?php echo $tool['is_published']; ?>"
                                                data-published-at="<?php echo $tool['published_at'] ? date('Y-m-d\TH:i', strtotime($tool['published_at'])) : ''; ?>">
                                            Edit
                                        </button>
                                        <a href="?delete=<?php echo $tool['id']; ?>" class="action-btn delete" onclick="return confirm('Are you sure you want to delete this AI tool?')">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <style>
        .nav-link {
            background: none;
            border: none;
            padding: 8px 15px;
            color: #333;
            text-decoration: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        .nav-link:hover {
            background: #f5f5f5;
        }
        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .content-header h1 {
            margin: 0;
        }
        .content-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .content-section h2 {
            margin-top: 0;
            color: #333;
            font-size: 1.5rem;
            margin-bottom: 20px;
        }
        .ai-tool-form {
            max-width: 600px;
        }
        .checkbox-field {
            margin-bottom: 15px;
        }
        .checkbox-field .checkbox-label {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .table-responsive {
            overflow-x: auto;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        .data-table th, .data-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .data-table th {
            background-color: #f9f9f9;
            font-weight: bold;
        }
        .actions {
            white-space: nowrap;
        }
        .action-btn {
            display: inline-block;
            padding: 5px 10px;
            margin-right: 5px;
            background: #4a6cf7;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
        }
        .action-btn.delete {
            background: #dc3545;
        }
        .action-btn:hover {
            opacity: 0.9;
        }
        .no-items {
            text-align: center;
            padding: 20px;
            color: #666;
        }
        .required {
            color: #dc3545;
            margin-left: 3px;
        }
    </style>
    <script>
        // Auto-generate slug from title
        document.addEventListener('DOMContentLoaded', function() {
            const titleInput = document.getElementById('title');
            const slugInput = document.getElementById('slug');
            
            if (titleInput && slugInput) {
                titleInput.addEventListener('input', function() {
                    // Only auto-generate if slug is empty or hasn't been manually edited
                    if (!slugInput.value || slugInput._autoGenerated) {
                        const slug = titleInput.value
                            .toLowerCase()
                            .replace(/[^\w\s-]/g, '') // Remove special characters
                            .replace(/\s+/g, '-')     // Replace spaces with hyphens
                            .replace(/-+/g, '-');     // Replace multiple hyphens with single hyphen
                        
                        slugInput.value = slug;
                        slugInput._autoGenerated = true;
                    }
                });
                
                // Mark when user manually edits the slug
                slugInput.addEventListener('input', function() {
                    slugInput._autoGenerated = false;
                });
            }
            
            // Handle edit buttons
            const editButtons = document.querySelectorAll('.action-btn.edit');
            const form = document.querySelector('.ai-tool-form');
            const formTitle = document.querySelector('.ai-tool-form h2');
            const submitButton = form.querySelector('button[type="submit"]');
            
            editButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const title = this.getAttribute('data-title');
                    const slug = this.getAttribute('data-slug');
                    const category = this.getAttribute('data-category');
                    const description = this.getAttribute('data-description');
                    const url = this.getAttribute('data-url');
                    const pricing = this.getAttribute('data-pricing');
                    const priceInfo = this.getAttribute('data-price-info');
                    const features = this.getAttribute('data-features');
                    const rating = this.getAttribute('data-rating');
                    const featured = this.getAttribute('data-featured') === '1';
                    const published = this.getAttribute('data-published') === '1';
                    const publishedAt = this.getAttribute('data-published-at');
                    
                    // Fill form with AI tool data
                    form.querySelector('input[name="id"]').value = id;
                    form.querySelector('input[name="title"]').value = title;
                    form.querySelector('input[name="slug"]').value = slug;
                    form.querySelector('select[name="category_id"]').value = category;
                    form.querySelector('textarea[name="description"]').value = description;
                    form.querySelector('input[name="tool_url"]').value = url;
                    form.querySelector('select[name="pricing_type"]').value = pricing;
                    form.querySelector('input[name="price_info"]').value = priceInfo;
                    form.querySelector('textarea[name="features"]').value = features;
                    form.querySelector('input[name="rating"]').value = rating;
                    form.querySelector('input[name="featured"]').checked = featured;
                    form.querySelector('input[name="is_published"]').checked = published;
                    form.querySelector('input[name="published_at"]').value = publishedAt || '';
                    
                    // Update form title and submit button
                    form.querySelector('h2').textContent = 'Edit AI Tool';
                    submitButton.textContent = 'Update AI Tool';
                    
                    // Scroll to form
                    form.scrollIntoView({ behavior: 'smooth' });
                });
            });
        });
    </script>
</body>
</html>