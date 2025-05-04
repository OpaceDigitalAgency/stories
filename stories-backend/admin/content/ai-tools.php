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

// Initialize variables
$ai_tools = [];
$error = null;
$success = null;

try {

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

// Set page variables for header
$pageTitle = 'AI Tools';
$currentPage = 'ai-tools';
$pageDescription = 'Manage all your AI tools from here.';
$pageActions = '
<form method="GET" action="ai-tool-form.php">
    <button type="submit" class="btn btn-success">
        <i class="fas fa-plus" aria-hidden="true"></i> Add New AI Tool
    </button>
</form>
';

// Include header
require_once '../includes/header.php';

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

// Include footer
include_once '../includes/footer.php';