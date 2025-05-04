<?php
/**
 * AI Tools Admin Page
 *
 * This page displays a list of all AI tools and allows for searching, filtering, and bulk actions.
 */

// Include auth check
require_once '../includes/auth-check.php';

// Include database connection
require_once '../includes/db-connect.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    // Enable detailed error reporting
    error_log("Starting AI tools page load...");
    
    // Verify database connection
    if (!$db) {
        throw new Exception("Database connection is not available");
    }
    error_log("Database connection verified");

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

    // Get pagination parameters
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $perPage = isset($_GET['per_page']) ? intval($_GET['per_page']) : 25;
    $offset = ($page - 1) * $perPage;

    // Get total count for pagination
    $countQuery = "SELECT COUNT(*) FROM ai_tools";
    $totalItems = $db->query($countQuery)->fetchColumn();
    error_log("Total AI tools found: $totalItems");

    // Get AI tools with pagination
    $query = "
        SELECT a.id,
               a.title,
               a.description,
               a.tool_url,
               a.pricing_type,
               a.price_info,
               a.features,
               a.rating,
               a.featured,
               a.is_published,
               a.slug,
               a.published_at,
               a.created_at,
               a.updated_at,
               c.name as category_name
        FROM ai_tools a
        LEFT JOIN ai_tool_categories c ON a.category_id = c.id
        ORDER BY a.created_at DESC
        LIMIT $offset, $perPage
    ";
    error_log("Executing query: " . $query);

    $stmt = $db->prepare($query);
    $stmt->execute();
    $ai_tools = $stmt->fetchAll();
    error_log("Number of tools fetched: " . count($ai_tools));

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

// Set page variables for header
$pageTitle = 'AI Tools';
$currentPage = 'ai-tools';
$pageDescription = 'Manage all your AI tools from here.';
$pageActions = '
<div class="d-flex gap-2">
    <form method="GET" action="ai-tool-form.php">
        <button type="submit" class="btn btn-success">
            <i class="fas fa-plus" aria-hidden="true"></i> Add New AI Tool
        </button>
    </form>
    <button onclick="window.location.reload()" class="btn btn-secondary">
        <i class="fas fa-sync" aria-hidden="true"></i> Refresh
    </button>
    <button onclick="window.location.href=\'?debug=1\'" class="btn btn-info">
        <i class="fas fa-bug" aria-hidden="true"></i> Debug Mode
    </button>
</div>
';

// Add additional debug logging if debug mode is enabled
if (isset($_GET['debug']) && $_GET['debug'] === '1') {
    error_log("DEBUG MODE ENABLED");
    error_log("PHP Version: " . phpversion());
    error_log("MySQL Version: " . $db->getAttribute(PDO::ATTR_SERVER_VERSION));
    error_log("Current User: " . ($user['name'] ?? 'Unknown'));
    error_log("Session Status: " . session_status());
    error_log("Memory Usage: " . memory_get_usage(true) / 1024 / 1024 . " MB");
}

// Include header
require_once '../includes/header.php';

// Display any database connection errors
if (!$db) {
    echo '<div class="alert alert-danger" role="alert">';
    echo '<h4 class="alert-heading"><i class="fas fa-database"></i> Database Connection Error</h4>';
    echo '<p>Could not connect to the database. Please check your configuration.</p>';
    echo '</div>';
}

// Display any errors prominently
if (isset($error)) {
    echo '<div class="alert alert-danger" role="alert">';
    echo '<h4 class="alert-heading"><i class="fas fa-exclamation-triangle"></i> Error Loading AI Tools</h4>';
    echo '<p>' . htmlspecialchars($error) . '</p>';
    echo '<hr>';
    echo '<p class="mb-0">Please check the error logs for more details or contact support.</p>';
    echo '</div>';
}

// Display debug information for administrators
if ($user && $user['role'] === 'admin') {
    echo '<div class="card mb-3">';
    echo '<div class="card-header bg-info text-white">';
    echo '<i class="fas fa-bug"></i> Debug Information';
    echo '</div>';
    echo '<div class="card-body">';
    echo '<pre class="mb-0">';
    echo "Database Connection: " . ($db ? "Success" : "Failed") . "\n";
    echo "Total Tools Found: " . ($totalItems ?? 0) . "\n";
    echo "Tools Loaded: " . (isset($ai_tools) ? count($ai_tools) : 0) . "\n";
    echo "Current Page: " . $page . "\n";
    echo "Items Per Page: " . $perPage . "\n";
    echo '</pre>';
    echo '</div>';
    echo '</div>';
}

// Include search component
include_once '../includes/search-component.php';
if (function_exists('renderSearchComponent')) {
    renderSearchComponent('ai_tools', ['title', 'description', 'pricing_type', 'category_name']);
}

// Include bulk actions component
include_once '../includes/bulk-actions-component.php';
if (function_exists('renderBulkActionsComponent')) {
    renderBulkActionsComponent('ai_tools', ['delete', 'publish', 'unpublish', 'feature', 'unfeature']);
}

// Include status indicator component
include_once '../includes/status-indicator-component.php';

// Include table component
include_once '../includes/table-component.php';
if (function_exists('renderTable')) {
    // Define columns
    $columns = [
        'id' => 'ID',
        'title' => 'Title',
        'category_name' => 'Category',
        'pricing_type' => 'Pricing',
        'rating' => 'Rating',
        'featured' => 'Featured',
        'is_published' => 'Published'
    ];

    // Define custom formatters
    $customFormatters = [
        'category_name' => function($tool, $key) {
            return htmlspecialchars($tool[$key] ?? 'None');
        },
        'pricing_type' => function($tool, $key) {
            return ucfirst($tool[$key]);
        },
        'rating' => function($tool, $key) {
            return number_format($tool[$key], 1);
        },
        'featured' => function($tool, $key) {
            return $tool[$key] ? 'Yes' : 'No';
        },
        'is_published' => function($tool, $key) {
            return $tool[$key] ? 'Yes' : 'No';
        }
    ];

    // Render the table
    renderTable($ai_tools, $columns, [
        'content_type' => 'ai_tools',
        'name_field' => 'title',
        'empty_message' => 'No AI tools found. Add your first AI tool!',
        'custom_formatters' => $customFormatters,
        'view_url' => 'view-ai-tool.php?id={id}',
        'edit_url' => 'ai-tool-form.php?id={id}',
        'delete_url' => 'delete-ai-tool.php'
    ]);
}

// Include pagination component
include_once '../includes/pagination-component.php';
if (function_exists('renderPagination')) {
    renderPagination($totalItems, $perPage, $page);
}

// Include footer
include_once '../includes/footer.php';