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

try {
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
    $stmt = $db->prepare($query);
    $stmt->execute();
    $directory_items = $stmt->fetchAll();
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
    echo '<h4 class="alert-heading"><i class="fas fa-exclamation-triangle"></i> Error Loading Directory Items</h4>';
    echo '<p>' . htmlspecialchars($error) . '</p>';
    echo '<hr>';
    echo '<p class="mb-0">Please check the error logs for more details or contact support.</p>';
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
if (function_exists('renderEnhancedTable')) {
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

    // Add debug output before rendering
    error_log("Rendering table with " . count($directory_items) . " items");
    error_log("Items data: " . json_encode(array_slice($directory_items, 0, 2)));
    error_log("Columns: " . json_encode($columns));
    error_log("Custom formatters: " . json_encode(array_keys($customFormatters)));

    // Render the table
    renderEnhancedTable($directory_items, $columns, [
        'content_type' => 'directory_items',
        'name_field' => 'title',
        'empty_message' => 'No directory items found. Add your first directory item!',
        'custom_formatters' => $customFormatters,
        'view_url' => 'view-directory-item.php?id={id}',
        'edit_url' => 'directory-item-form.php?id={id}',
        'delete_url' => 'delete-directory-item.php'
    ]);
}

// Include pagination component if needed
include_once '../includes/pagination-component.php';
if (function_exists('renderPagination') && $totalItems > $perPage) {
    renderPagination($totalItems, $perPage, $page);
}

// Include footer
include_once '../includes/footer.php';