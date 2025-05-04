<?php

// Include header
include '../includes/header.php';


// Page variables
$pageTitle = 'View Game';
$currentPage = 'view-game';

require_once '../../simple_auth.php';

// Database configuration
$config = [
    'host' => 'localhost',
    'name' => 'stories_db',
    'user' => 'stories_user',
    'password' => '$tw1cac3*sOt',
    'charset' => 'utf8mb4',
    'port' => 3306
];

// Initialize SimpleAuth
SimpleAuth::initDB($config);

// Check if user is logged in
if (!$user = SimpleAuth::check()) {
    header("Location: ../login.php");
    exit;
}

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid game ID.";
    header("Location: games.php");
    exit;
}

$gameId = (int)$_GET['id'];

try {
    // Connect to database
    $db = new PDO(
        "mysql:host={$config['host']};dbname={$config['name']};charset={$config['charset']}",
        $config['user'],
        $config['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );

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

<body>
    <header class="admin-header">
        <div class="header-container">
            <div class="logo-container">
                <div class="logo">S</div>
                <div class="logo-text">Stories Admin</div>
            </div>
            <div class="user-info">
                <span class="user-name">Welcome, <?php echo htmlspecialchars($user['name']); ?></span>
                <form method="POST" action="../logout.php" style="display: inline;">
                    <button type="submit" class="btn btn-danger btn-sm">Logout</button>
                </form>
            </div>
        </div>
    </header>

    <div class="container">
        <nav class="nav-menu">
            <form method="GET" style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                <button type="submit" formaction="../dashboard.php" class="nav-link">Dashboard</button>
                <button type="submit" formaction="stories.php" class="nav-link">Stories</button>
                <button type="submit" formaction="blog-posts.php" class="nav-link">Blog Posts</button>
                <button type="submit" formaction="authors.php" class="nav-link">Authors</button>
                <button type="submit" formaction="tags.php" class="nav-link">Tags</button>
                <button type="submit" formaction="games.php" class="nav-link active">Games</button>
                <button type="submit" formaction="directory-items.php" class="nav-link">Directory</button>
                <button type="submit" formaction="ai-tools.php" class="nav-link">AI Tools</button>
                <button type="submit" formaction="media.php" class="nav-link">Media</button>
            </form>
        </nav>

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

// Include footer
include '../includes/footer.php';
