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
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Initialize variables to avoid undefined variable errors
$directory_items = [];
$totalItems = 0;

// Function to render star ratings
function renderStarRating($rating, $maxRating = 5, $size = 'md') {
    // Normalize rating to a scale of 0-5
    $normalizedRating = $rating * $maxRating;

    // Calculate full and half stars
    $fullStars = floor($normalizedRating);
    $halfStar = $normalizedRating - $fullStars >= 0.5;
    $emptyStars = $maxRating - $fullStars - ($halfStar ? 1 : 0);

    // Size classes
    $sizeClasses = [
        'sm' => 'width: 16px; height: 16px;',
        'md' => 'width: 20px; height: 20px;',
        'lg' => 'width: 24px; height: 24px;'
    ];

    $starStyle = $sizeClasses[$size] ?? $sizeClasses['md'];

    $html = '<div class="star-rating" style="display: inline-flex; align-items: center;">';

    // Full stars
    for ($i = 0; $i < $fullStars; $i++) {
        $html .= '<svg style="' . $starStyle . ' color: #FFD166; margin-right: 2px;" viewBox="0 0 24 24" fill="currentColor">
            <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"></path>
        </svg>';
    }

    // Half star
    if ($halfStar) {
        $html .= '<svg style="' . $starStyle . ' color: #FFD166; margin-right: 2px;" viewBox="0 0 24 24" fill="currentColor">
            <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z" fill-opacity="0.5"></path>
            <path d="M12 17.27V2L9.19 8.63 2 9.24l5.46 4.73L5.82 21 12 17.27z"></path>
        </svg>';
    }

    // Empty stars
    for ($i = 0; $i < $emptyStars; $i++) {
        $html .= '<svg style="' . $starStyle . ' color: #e0e0e0; margin-right: 2px;" viewBox="0 0 24 24" fill="currentColor">
            <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"></path>
        </svg>';
    }

    $html .= '</div>';

    return $html;
}

try {
    // Check if database connection is valid
    if (!$db) {
        throw new Exception("Database connection is not available");
    }

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
            cover_url VARCHAR(255),
            featured TINYINT(1) DEFAULT 0,
            is_published TINYINT(1) DEFAULT 0,
            slug VARCHAR(255) NOT NULL,
            published_at DATETIME,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL
        )");

        // Log table creation
        error_log("Created directory_items table");
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
    $perPage = isset($_GET['per_page']) ? intval($_GET['per_page']) : 10;

    // Ensure per_page is a valid value
    $validPerPageValues = [10, 25, 50, 100];
    if (!in_array($perPage, $validPerPageValues)) {
        $perPage = 10; // Default to 10 if invalid
    }

    $offset = ($page - 1) * $perPage;

    // Log pagination parameters for debugging
    error_log("Directory Items - Page: $page, Per Page: $perPage, Offset: $offset");

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
               d.cover_url,
               d.featured,
               d.is_published,
               d.slug,
               d.published_at,
               d.created_at,
               d.updated_at,
               d.type,
               d.average_rating,
               d.review_count,
               c.name as category_name
        FROM directory_items d
        LEFT JOIN directory_categories c ON d.category_id = c.id
        ORDER BY d.created_at DESC
        LIMIT $offset, $perPage
    ";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $directory_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Directory items page PDO error: " . $e->getMessage());
    $error = "Database error loading directory data: " . $e->getMessage();

    // Check if the error is related to a missing table
    if (strpos($e->getMessage(), "doesn't exist") !== false) {
        $error .= ". The required table may be missing. Please check the database structure.";
    }
} catch (Exception $e) {
    error_log("Directory items page general error: " . $e->getMessage());
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
$currentPage = 'directory';  // This matches the condition in header.php for loading directory-item-preview.js
$pageDescription = 'Manage all your directory items from here.';

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
<!-- Add Directory Item Preview CSS and JS -->
<link rel="stylesheet" href="../assets/css/story-preview.css">
<script src="../assets/js/directory-item-preview.js"></script>
';

$pageActions = '
<div class="premium-flex premium-gap-2">
    <a href="directory-item-form.php" class="premium-btn premium-btn-success">
        <i class="fas fa-plus" aria-hidden="true"></i> Add New Directory Item
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
if (function_exists('renderEnhancedBulkActionsComponent')) {
    renderEnhancedBulkActionsComponent('directory_items', [
        'delete' => 'Delete Selected',
        'publish' => 'Publish Selected',
        'unpublish' => 'Unpublish Selected',
        'feature' => 'Feature Selected',
        'unfeature' => 'Unfeature Selected'
    ]);
} else if (function_exists('renderBulkActionsComponent')) {
    renderBulkActionsComponent('directory_items', ['delete', 'publish', 'unpublish', 'feature', 'unfeature']);
}

// Include status indicator component
include_once '../includes/status-indicator-component.php';

// Include enhanced table component
require_once __DIR__ . '/../includes/enhanced-table-component.php';
if (function_exists('renderEnhancedTable')) {
    // Prepare data for the enhanced table
    $tableData = [];
    foreach ($directory_items as $item) {
        // Format the status
        $status = isset($item['is_published']) && $item['is_published'] ? 'Published' : 'Draft';
        $featured = isset($item['featured']) && $item['featured'] ? 'Yes' : 'No';

        // Format the created date
        $createdDate = date('M j, Y', strtotime($item['created_at']));

        // Format the website URL
        $websiteUrl = !empty($item['website_url']) ?
            '<a href="' . htmlspecialchars($item['website_url']) . '" target="_blank">Visit</a>' : '-';

        // Format the rating display for books
        $ratingDisplay = '';
        if ($item['type'] === 'book' && !empty($item['average_rating']) && !empty($item['review_count'])) {
            $ratingDisplay = '<div style="display: flex; align-items: center;">';
            $ratingDisplay .= renderStarRating($item['average_rating'], 5, 'sm');
            $ratingDisplay .= '<span style="margin-left: 5px; font-size: 12px; color: #666;">(' .
                              $item['review_count'] . ' ' . ($item['review_count'] == 1 ? 'review' : 'reviews') . ')</span>';
            $ratingDisplay .= '</div>';
        } else {
            $ratingDisplay = '-';
        }

        // Add the item to the table data
        $tableData[] = [
            'id' => $item['id'],
            'image' => !empty($item['cover_url']) ? $item['cover_url'] : '../assets/images/default-cover.svg',
            'title' => $item['title'],
            'slug' => $item['slug'] ?? '',
            'category' => $item['category_name'] ?? 'None',
            'type' => $item['type'] ?? 'general',
            'rating' => $ratingDisplay,
            'website' => $websiteUrl,
            'featured' => $featured,
            'status' => $status,
            'created' => $createdDate
        ];

        // Debug what URL we're passing to the table
        error_log("Directory Item {$item['id']} - Cover URL: " . ($item['cover_url'] ?? 'EMPTY'));
        error_log("Directory Item {$item['id']} - Image field in tableData: " . ($tableData[count($tableData)-1]['image'] ?? 'EMPTY'));
    }

    // Define columns for the table
    $columns = [
        'title' => 'Title',
        'slug' => 'Slug',
        'category' => 'Category',
        'type' => 'Type',
        'rating' => 'Rating',
        'website' => 'Website',
        'featured' => 'Featured',
        'status' => 'Status',
        'created' => 'Created'
    ];

    // Define which fields are editable inline
    $editableFields = ['title', 'slug'];

    // Render the enhanced table
    renderEnhancedTable(
        $tableData,
        $columns,
        'directory_item', // This must match a key in the $tableMap array in update-field.php
        'directory-items-table',
        [
            'showCheckboxes' => true,
            'showActions' => true,
            'actions' => ['view', 'edit', 'delete'],
            'editableFields' => $editableFields,
            'bulkActions' => ['delete', 'publish', 'unpublish', 'feature', 'unfeature'],
            'itemsPerPage' => $perPage,
            'currentPage' => $page,
            'totalItems' => $totalItems, // Pass the total items count from SQL query
            'htmlFields' => ['website', 'rating'], // Fields that should render HTML instead of escaping it
            'thumbnailField' => 'image', // Changed from 'cover_url' to 'image' to match the field name in tableData
            'thumbnailAltField' => 'title' // Use the title as alt text
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