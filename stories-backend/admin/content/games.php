<?php
/**
 * Games Admin Page
 *
 * This page displays a list of all games and allows for searching, filtering, and bulk actions.
 */

// Include auth check
require_once '../includes/auth-check.php';

// Include database connection
require_once '../includes/db-connect.php';

try {
    // Check if games table exists
    $stmt = $db->query("SHOW TABLES LIKE 'games'");
    if ($stmt->rowCount() === 0) {
        // Create games table if it doesn't exist
        $db->exec("CREATE TABLE IF NOT EXISTS games (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            slug VARCHAR(255) NOT NULL,
            featured TINYINT(1) DEFAULT 0,
            is_published TINYINT(1) DEFAULT 0,
            published_at DATETIME,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL
        )");
    }

    // Get pagination parameters
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $perPage = isset($_GET['per_page']) ? intval($_GET['per_page']) : 25;
    $offset = ($page - 1) * $perPage;

    // Get total count for pagination
    $countQuery = "SELECT COUNT(*) FROM games";
    $totalItems = $db->query($countQuery)->fetchColumn();
    // Get games with pagination
    $query = "
        SELECT id,
               title,
               description,
               slug,
               featured,
               is_published,
               published_at,
               created_at,
               updated_at
        FROM games
        ORDER BY created_at DESC
        LIMIT $offset, $perPage
    ";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $games = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Games page error: " . $e->getMessage());
    $error = "Error loading games data. Please try again.";
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
$pageTitle = 'Games';
$currentPage = 'games';
$pageDescription = 'Manage all your games from here.';

// Add extra head content for premium features
$extraHeadContent = '
<!-- Add Premium Admin CSS -->
<link rel="stylesheet" href="../assets/css/premium-admin.css">
<!-- Add Live Search JS -->
<script src="../assets/js/live-search.js"></script>
<!-- Add Inline Editing JS -->
<script src="../assets/js/inline-editing.js"></script>
';

$pageActions = '
<div class="premium-flex premium-gap-2">
    <a href="game-form.php" class="premium-btn premium-btn-success">
        <i class="fas fa-plus" aria-hidden="true"></i> Add New Game
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
    echo '<h4 class="alert-heading"><i class="fas fa-exclamation-triangle"></i> Error Loading Games</h4>';
    echo '<p>' . htmlspecialchars($error) . '</p>';
    echo '<hr>';
    echo '<p class="mb-0">Please check the error logs for more details or contact support.</p>';
    echo '</div>';
}

// Include search component
include_once '../includes/search-component.php';
if (function_exists('renderSearchComponent')) {
    renderSearchComponent('games', ['title', 'slug', 'description']);
}

// Include bulk actions component
include_once '../includes/bulk-actions-component.php';
if (function_exists('renderEnhancedBulkActionsComponent')) {
    renderEnhancedBulkActionsComponent('games', [
        'delete' => 'Delete Selected',
        'publish' => 'Publish Selected',
        'unpublish' => 'Unpublish Selected',
        'feature' => 'Feature Selected',
        'unfeature' => 'Unfeature Selected'
    ]);
} else if (function_exists('renderBulkActionsComponent')) {
    renderBulkActionsComponent('games', ['delete', 'publish', 'unpublish', 'feature', 'unfeature']);
}

// Include status indicator component
include_once '../includes/status-indicator-component.php';

// Include enhanced table component
include_once '../includes/enhanced-table-component.php';
if (function_exists('renderEnhancedTable')) {
    // Prepare data for the enhanced table
    $tableData = [];
    foreach ($games as $game) {
        // Format the status
        $status = isset($game['is_published']) && $game['is_published'] ? 'Published' : 'Draft';
        $featured = isset($game['featured']) && $game['featured'] ? 'Yes' : 'No';

        // Format the created date
        $createdDate = date('M j, Y', strtotime($game['created_at']));

        // Add the item to the table data
        $tableData[] = [
            'id' => $game['id'],
            'title' => $game['title'],
            'slug' => $game['slug'] ?? '',
            'featured' => $featured,
            'status' => $status,
            'created' => $createdDate
        ];
    }

    // Define columns for the table
    $columns = [
        'title' => 'Title',
        'slug' => 'Slug',
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
        'game', // This must match a key in the $tableMap array in update-field.php
        'games-table',
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