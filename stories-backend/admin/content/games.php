<?php
/**
 * Games Admin Page
 *
 * This page displays a list of all games and allows for searching, filtering, and bulk actions.
 */

// Include auth check
include_once '../includes/auth-check.php';

// Include database connection
include_once '../includes/db-connect.php';

// Initialize variables
$games = [];
$error = null;
$success = null;

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

    // Get all games
    $games = $db->query("SELECT * FROM games ORDER BY created_at DESC")->fetchAll();

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
$pageActions = '
<form method="GET" action="game-form.php">
    <button type="submit" class="btn btn-success">
        <i class="fas fa-plus" aria-hidden="true"></i> Add New Game
    </button>
</form>
';

// Include header
include_once '../includes/header.php';

// Include search component
include_once '../includes/search-component.php';
if (function_exists('renderSearchComponent')) {
    renderSearchComponent('games', ['title', 'slug', 'description']);
}

// Include bulk actions component
include_once '../includes/bulk-actions-component.php';
if (function_exists('renderBulkActionsComponent')) {
    renderBulkActionsComponent('games', ['delete', 'publish', 'unpublish', 'feature', 'unfeature']);
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
        'slug' => 'Slug',
        'featured' => 'Featured',
        'is_published' => 'Published',
        'created_at' => 'Created'
    ];

    // Define custom formatters
    $customFormatters = [
        'featured' => function($game, $key) {
            return $game[$key] ? 'Yes' : 'No';
        },
        'is_published' => function($game, $key) {
            return $game[$key] ? 'Yes' : 'No';
        },
        'created_at' => function($game, $key) {
            return date('M j, Y', strtotime($game[$key]));
        }
    ];

    // Render the table
    renderTable($games, $columns, [
        'content_type' => 'games',
        'name_field' => 'title',
        'empty_message' => 'No games found. Add your first game!',
        'custom_formatters' => $customFormatters,
        'view_url' => 'view-game.php?id={id}',
        'edit_url' => 'game-form.php?id={id}',
        'delete_url' => 'delete-game.php'
    ]);
}

// Include footer
include_once '../includes/footer.php';