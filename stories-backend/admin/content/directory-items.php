<?php
/**
 * Directory Items Admin Page
 *
 * This page displays a list of all directory items and allows for searching, filtering, and bulk actions.
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
    error_log("Starting directory items page load...");
    
    // Verify database connection
    if (!$db) {
        throw new Exception("Database connection is not available");
    }
    error_log("Database connection verified");

    // Check if directory_items table exists
    $stmt = $db->query("SHOW TABLES LIKE 'directory_items'");
    if ($stmt->rowCount() === 0) {
        // Create directory_items table if it doesn't exist
        $db->exec("CREATE TABLE IF NOT EXISTS directory_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            category_id INT,
            website_url VARCHAR(255),
            contact_email VARCHAR(255),
            contact_phone VARCHAR(50),
            address TEXT,
            featured TINYINT(1) DEFAULT 0,
            is_published TINYINT(1) DEFAULT 0,
            slug VARCHAR(255) NOT NULL,
            published_at DATETIME,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL
        )");
    }

    // Check if directory_categories table exists
    $stmt = $db->query("SHOW TABLES LIKE 'directory_categories'");
    if ($stmt->rowCount() === 0) {
        // Create directory_categories table if it doesn't exist
        $db->exec("CREATE TABLE IF NOT EXISTS directory_categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL,
            description TEXT,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL
        )");

        // Add some default categories
        $db->exec("INSERT INTO directory_categories (name, slug, description, created_at, updated_at) VALUES
            ('General', 'general', 'General directory listings', NOW(), NOW()),
            ('Business', 'business', 'Business directory listings', NOW(), NOW()),
            ('Education', 'education', 'Education directory listings', NOW(), NOW())
        ");
    }

    // Get pagination parameters
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $perPage = isset($_GET['per_page']) ? intval($_GET['per_page']) : 25;
    $offset = ($page - 1) * $perPage;

    // Get total count for pagination
    $countQuery = "SELECT COUNT(*) FROM directory_items";
    $totalItems = $db->query($countQuery)->fetchColumn();
    error_log("Total directory items found: $totalItems");

    // Get directory items with pagination
    $query = "
        SELECT d.id,
               d.title,
               d.description,
               d.website_url,
               d.contact_email,
               d.contact_phone,
               d.address,
               d.featured,
               d.is_published,
               d.slug,
               d.published_at,
               d.created_at,
               d.updated_at,
               c.name as category_name
        FROM directory_items d
        LEFT JOIN directory_categories c ON d.category_id = c.id
        ORDER BY d.created_at DESC
        LIMIT $offset, $perPage
    ";
    error_log("Executing query: " . $query);

    $stmt = $db->prepare($query);
    $stmt->execute();
    $directory_items = $stmt->fetchAll();
    error_log("Number of items fetched: " . count($directory_items));

} catch (PDOException $e) {
    error_log("Directory items page error: " . $e->getMessage());
    $error = "Error loading directory data. Please try again.";
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
$pageTitle = 'Directory Items';
$currentPage = 'directory';
$pageDescription = 'Manage all your directory items from here.';
$pageActions = '
<div class="d-flex gap-2">
    <form method="GET" action="directory-item-form.php">
        <button type="submit" class="btn btn-success">
            <i class="fas fa-plus" aria-hidden="true"></i> Add New Directory Item
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
    echo '<h4 class="alert-heading"><i class="fas fa-exclamation-triangle"></i> Error Loading Directory Items</h4>';
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
    echo "Total Items Found: " . ($totalItems ?? 0) . "\n";
    echo "Items Loaded: " . (isset($directory_items) ? count($directory_items) : 0) . "\n";
    echo "Current Page: " . $page . "\n";
    echo "Items Per Page: " . $perPage . "\n";
    echo '</pre>';
    echo '</div>';
    echo '</div>';
}

// Include search component
include_once '../includes/search-component.php';
if (function_exists('renderSearchComponent')) {
    renderSearchComponent('directory_items', ['title', 'description', 'website_url', 'contact_email']);
}

// Include bulk actions component
include_once '../includes/bulk-actions-component.php';
if (function_exists('renderBulkActionsComponent')) {
    renderBulkActionsComponent('directory_items', ['delete', 'publish', 'unpublish', 'feature', 'unfeature']);
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
        'website_url' => 'Website',
        'featured' => 'Featured',
        'is_published' => 'Published',
        'created_at' => 'Created'
    ];

    // Define custom formatters
    $customFormatters = [
        'category_name' => function($item, $key) {
            return htmlspecialchars($item[$key] ?? 'None');
        },
        'website_url' => function($item, $key) {
            if (!empty($item[$key])) {
                return '<a href="' . htmlspecialchars($item[$key]) . '" target="_blank">Visit</a>';
            } else {
                return '-';
            }
        },
        'featured' => function($item, $key) {
            return $item[$key] ? 'Yes' : 'No';
        },
        'is_published' => function($item, $key) {
            return $item[$key] ? 'Yes' : 'No';
        },
        'created_at' => function($item, $key) {
            return date('M j, Y', strtotime($item[$key]));
        }
    ];

    // Render the table
    renderTable($directory_items, $columns, [
        'content_type' => 'directory_items',
        'name_field' => 'title',
        'empty_message' => 'No directory items found. Add your first directory item!',
        'custom_formatters' => $customFormatters,
        'view_url' => 'view-directory-item.php?id={id}',
        'edit_url' => 'directory-item-form.php?id={id}',
        'delete_url' => 'delete-directory-item.php'
    ]);
}

// Include pagination component
include_once '../includes/pagination-component.php';
if (function_exists('renderPagination')) {
    renderPagination($totalItems, $perPage, $page);
}

// Include footer
include_once '../includes/footer.php';