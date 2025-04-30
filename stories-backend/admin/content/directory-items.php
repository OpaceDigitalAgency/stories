<?php
/**
 * Directory Items Admin Page
 *
 * This page displays a list of all directory items and allows for searching, filtering, and bulk actions.
 */

// Include auth check
include_once '../includes/auth-check.php';

// Include database connection
include_once '../includes/db-connect.php';

// Initialize variables
$directory_items = [];
$error = null;
$success = null;

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

    // Get all directory items with category names
    $directory_items = $db->query("
        SELECT d.*, c.name as category_name
        FROM directory_items d
        LEFT JOIN directory_categories c ON d.category_id = c.id
        ORDER BY d.created_at DESC
    ")->fetchAll();

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
<form method="GET" action="directory-item-form.php">
    <button type="submit" class="btn btn-success">
        <i class="fas fa-plus" aria-hidden="true"></i> Add New Directory Item
    </button>
</form>
';

// Include header
include_once '../includes/header.php';

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

// Include footer
include_once '../includes/footer.php';