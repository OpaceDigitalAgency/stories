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
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Initialize variables to avoid undefined variable errors
$ai_tools = [];
$totalItems = 0;

try {
    // Check if database connection is valid
    if (!$db) {
        throw new Exception("Database connection is not available");
    }

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
            image VARCHAR(255),
            rating DECIMAL(3,1) DEFAULT 0,
            featured TINYINT(1) DEFAULT 0,
            is_published TINYINT(1) DEFAULT 0,
            slug VARCHAR(255) NOT NULL,
            published_at DATETIME,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL
        )");

        // Log table creation
        error_log("Created ai_tools table");
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
    $stmt = $db->prepare($query);
    $stmt->execute();
    $ai_tools = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("AI tools page PDO error: " . $e->getMessage());
    $error = "Database error loading AI tools data: " . $e->getMessage();

    // Check if the error is related to a missing table
    if (strpos($e->getMessage(), "doesn't exist") !== false) {
        $error .= ". The required table may be missing. Please check the database structure.";
    }
} catch (Exception $e) {
    error_log("AI tools page general error: " . $e->getMessage());
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

// Add extra head content for premium features
$extraHeadContent = '
<!-- Add Premium Admin CSS -->
<link rel="stylesheet" href="../assets/css/premium-admin.css">
<!-- Add Live Search JS -->
<script src="../assets/js/live-search.js"></script>
<!-- Add Inline Editing JS -->
<script src="../assets/js/inline-editing.js"></script>
<!-- Add Direct Data Loader JS -->
<script src="../assets/js/admin-direct-data.js"></script>
';

$pageActions = '
<div class="premium-flex premium-gap-2">
    <a href="ai-tool-form.php" class="premium-btn premium-btn-success">
        <i class="fas fa-plus" aria-hidden="true"></i> Add New AI Tool
    </a>
    <button onclick="window.location.reload()" class="premium-btn premium-btn-secondary">
        <i class="fas fa-sync" aria-hidden="true"></i> Refresh
    </button>
</div>
';

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

// Include search component
include_once '../includes/search-component.php';
if (function_exists('renderSearchComponent')) {
    renderSearchComponent('ai_tools', ['title', 'description', 'pricing_type', 'category_name']);
}

// Include bulk actions component
include_once '../includes/bulk-actions-component.php';
if (function_exists('renderEnhancedBulkActionsComponent')) {
    renderEnhancedBulkActionsComponent('ai_tools', [
        'delete' => 'Delete Selected',
        'publish' => 'Publish Selected',
        'unpublish' => 'Unpublish Selected',
        'feature' => 'Feature Selected',
        'unfeature' => 'Unfeature Selected'
    ]);
} else if (function_exists('renderBulkActionsComponent')) {
    renderBulkActionsComponent('ai_tools', ['delete', 'publish', 'unpublish', 'feature', 'unfeature']);
}

// Include status indicator component
include_once '../includes/status-indicator-component.php';

// Include enhanced table component
include_once '../includes/enhanced-table-component.php';
if (function_exists('renderEnhancedTable')) {
    // Prepare data for the enhanced table
    $tableData = [];
    foreach ($ai_tools as $tool) {
        // Format the status
        $status = isset($tool['is_published']) && $tool['is_published'] ? 'Published' : 'Draft';
        $featured = isset($tool['featured']) && $tool['featured'] ? 'Yes' : 'No';

        // Format the rating
        $rating = number_format($tool['rating'] ?? 0, 1);

        // Add the item to the table data
        $tableData[] = [
            'id' => $tool['id'],
            'title' => $tool['title'],
            'category' => $tool['category_name'] ?? 'None',
            'pricing' => ucfirst($tool['pricing_type'] ?? 'Free'),
            'rating' => $rating,
            'featured' => $featured,
            'status' => $status
        ];
    }

    // Define columns for the table
    $columns = [
        'title' => 'Title',
        'category' => 'Category',
        'pricing' => 'Pricing',
        'rating' => 'Rating',
        'featured' => 'Featured',
        'status' => 'Status'
    ];

    // Define which fields are editable inline
    $editableFields = ['title'];

    // Render the enhanced table
    renderEnhancedTable(
        $tableData,
        $columns,
        'ai_tool', // This must match a key in the $tableMap array in update-field.php
        'ai-tools-table',
        [
            'showCheckboxes' => true,
            'showActions' => true,
            'actions' => ['view', 'edit', 'delete'],
            'editableFields' => $editableFields,
            'bulkActions' => ['delete', 'publish', 'unpublish', 'feature', 'unfeature'],
            'itemsPerPage' => $perPage,
            'currentPage' => $page
        ]
    );
}

// Include pagination component if needed
include_once '../includes/pagination-component.php';
if (function_exists('renderPagination') && $totalItems > $perPage) {
    renderPagination($totalItems, $perPage, $page);
}

// Include footer
include_once '../includes/footer.php';