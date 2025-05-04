<?php

// Page variables
$pageTitle = 'View Game';
$currentPage = 'view-game';

// Include auth check
require_once '../includes/auth-check.php';

// Include database connection
require_once '../includes/db-connect.php';

// Include header
require_once '../includes/header.php';

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid game ID.";
    header("Location: games.php");
    exit;
}

$gameId = (int)$_GET['id'];

try {
    // Ensure we have a database connection
    if (!isset($db) || !$db) {
        // Try to connect to the database directly
        try {
            $db = new PDO(
                'mysql:host=localhost;dbname=stories_db;charset=utf8mb4',
                'stories_user',
                '$tw1cac3*sOt',
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
        } catch (PDOException $e) {
            $errorMessage = "Database connection error: " . $e->getMessage();
            error_log("Database connection error in view-game.php: " . $e->getMessage());
        }
    }

    // Get game details
    $stmt = $db->prepare("SELECT * FROM games WHERE id = ?");
    $stmt->execute([$gameId]);
    $game = $stmt->fetch();

    if (!$game) {
        $_SESSION['error'] = "Game not found.";
        header("Location: games.php");
        exit;
    }

    // Check if game_stories table exists
    $hasGameStories = false;
    $gameStories = [];
    try {
        $stmt = $db->query("SHOW TABLES LIKE 'game_stories'");
        if ($stmt->rowCount() > 0) {
            $hasGameStories = true;
            
            // Get stories related to this game
            $stmt = $db->prepare("
                SELECT s.id, s.title, s.slug 
                FROM stories s 
                JOIN game_stories gs ON s.id = gs.story_id 
                WHERE gs.game_id = ? 
                ORDER BY s.title ASC
            ");
            $stmt->execute([$gameId]);
            $gameStories = $stmt->fetchAll();
        }
    } catch (PDOException $e) {
        // Table might not exist, ignore
    }

} catch (PDOException $e) {
    error_log("View game error: " . $e->getMessage());
    $_SESSION['error'] = "Error loading game details. Please try again.";
    header("Location: games.php");
    exit;
}
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <div class="page-header d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="page-title">View Game</h1>
                <p class="page-description">
                    <a href="games.php" class="text-primary">← Back to Games</a>
                </p>
            </div>
            <div class="d-flex gap-2">
                <form method="GET" action="game-form.php">
                    <input type="hidden" name="id" value="<?php echo $game['id']; ?>">
                    <button type="submit" class="btn btn-primary">
                        <span class="icon-edit"></span> Edit
                    </button>
                </form>
                <form method="POST" action="delete-game.php" onsubmit="return confirm('Are you sure you want to delete this game?');">
                    <input type="hidden" name="id" value="<?php echo $game['id']; ?>">
                    <button type="submit" class="btn btn-danger">
                        <span class="icon-delete"></span> Delete
                    </button>
                </form>
            </div>
        </div>

        <div class="content-section mb-4">
            <div class="section-header">
                <h2 class="section-title"><?php echo htmlspecialchars($game['title']); ?></h2>
            </div>
            <div class="section-body">
                <div class="mb-4">
                    <div class="mb-3">
                        <strong>Slug:</strong> 
                        <?php echo htmlspecialchars($game['slug']); ?>
                    </div>
                    
                    <div class="mb-3">
                        <strong>Featured:</strong> 
                        <?php echo $game['featured'] ? 'Yes' : 'No'; ?>
                    </div>
                    
                    <div class="mb-3">
                        <strong>Published:</strong> 
                        <?php echo $game['is_published'] ? 'Yes' : 'No'; ?>
                    </div>
                    
                    <?php if (isset($game['published_at']) && !empty($game['published_at'])): ?>
                    <div class="mb-3">
                        <strong>Published Date:</strong> 
                        <?php echo date('M j, Y', strtotime($game['published_at'])); ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($game['created_at'])): ?>
                    <div class="mb-3">
                        <strong>Created:</strong> 
                        <?php echo date('M j, Y', strtotime($game['created_at'])); ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($game['updated_at'])): ?>
                    <div class="mb-3">
                        <strong>Updated:</strong> 
                        <?php echo date('M j, Y', strtotime($game['updated_at'])); ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($game['description'])): ?>
                    <div class="mb-3">
                        <strong>Description:</strong><br>
                        <div class="p-3 bg-light border rounded">
                            <?php echo nl2br(htmlspecialchars($game['description'])); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php 
                    // Check if any additional fields exist and display them
                    $skipFields = ['id', 'title', 'slug', 'description', 'featured', 'is_published', 
                                  'published_at', 'created_at', 'updated_at'];
                    foreach ($game as $key => $value) {
                        if (!in_array($key, $skipFields) && !is_null($value) && $value !== '') {
                            echo '<div class="mb-2"><strong>' . htmlspecialchars(ucfirst(str_replace('_', ' ', $key))) . ':</strong> ' . 
                                 htmlspecialchars($value) . '</div>';
                        }
                    }
                    ?>
                </div>
            </div>
        </div>

        <?php if ($hasGameStories && !empty($gameStories)): ?>
        <div class="content-section mb-4">
            <div class="section-header">
                <h3 class="section-title">Stories related to this game (<?php echo count($gameStories); ?>)</h3>
            </div>
            <div class="section-body">
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Slug</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($gameStories as $story): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($story['title']); ?></td>
                                    <td><?php echo htmlspecialchars($story['slug']); ?></td>
                                    <td>
                                        <div class="table-actions">
                                            <form method="GET" action="view-story.php" style="display: inline;">
                                                <input type="hidden" name="id" value="<?php echo $story['id']; ?>">
                                                <button type="submit" class="btn btn-info btn-sm">
                                                    <span class="icon-view"></span> View
                                                </button>
                                            </form>
                                            <form method="GET" action="story-form.php" style="display: inline;">
                                                <input type="hidden" name="id" value="<?php echo $story['id']; ?>">
                                                <button type="submit" class="btn btn-primary btn-sm">
                                                    <span class="icon-edit"></span> Edit
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="d-flex justify-content-between mt-4">
            <a href="games.php" class="btn btn-secondary">
                Back to Games
            </a>
            <form method="GET" action="game-form.php">
                <input type="hidden" name="id" value="<?php echo $game['id']; ?>">
                <button type="submit" class="btn btn-primary">
                    <span class="icon-edit"></span> Edit Game
                </button>
            </form>
        </div>
    </div>
</div>

<style>
        .bg-light {
            background-color: var(--gray-50);
        }
        
        .border {
            border: 1px solid var(--border-color);
        }
        
        .rounded {
            border-radius: var(--radius-md);
        }
        
        .p-3 {
            padding: 1rem;
        }
        
        .gap-2 {
            gap: 0.5rem;
        }
    </style>

<?php require_once '../includes/footer.php'; ?>
