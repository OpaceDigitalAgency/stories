<?php
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

// Initialize variables
$games = [];
$error = null;
$success = null;

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Games - Admin</title>
    <link rel="stylesheet" href="../assets/css/main.css">
</head>
<body>
    <div class="container">
        <div class="user-info">
            Welcome, <?php echo htmlspecialchars($user['name']); ?> |
            <form method="POST" action="../logout.php" style="display: inline;">
                <button type="submit" class="form-submit" style="background: #dc3545;">Logout</button>
            </form>
        </div>

        <nav class="nav-menu">
            <form method="GET" style="display: inline;">
                <button type="submit" formaction="../dashboard.php" class="nav-link">Dashboard</button>
                <button type="submit" formaction="stories.php" class="nav-link">Stories</button>
                <button type="submit" formaction="blog-posts.php" class="nav-link">Blog Posts</button>
                <button type="submit" formaction="authors.php" class="nav-link">Authors</button>
                <button type="submit" formaction="tags.php" class="nav-link">Tags</button>
                <button type="submit" formaction="games.php" class="nav-link">Games</button>
                <button type="submit" formaction="directory-items.php" class="nav-link">Directory</button>
                <button type="submit" formaction="ai-tools.php" class="nav-link">AI Tools</button>
                <button type="submit" formaction="media.php" class="nav-link">Media</button>
            </form>
        </nav>

        <div class="content-header">
            <h1>Games</h1>
            <form method="GET" action="game-form.php" style="display: inline;">
                <button type="submit" class="form-submit">Add New Game</button>
            </form>
        </div>

        <?php if (isset($success)): ?>
            <div class="success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Slug</th>
                        <th>Featured</th>
                        <th>Published</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($games)): ?>
                        <tr>
                            <td colspan="7" class="text-center">No games found. Add your first game!</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($games as $game): ?>
                            <tr>
                                <td><?php echo $game['id']; ?></td>
                                <td><?php echo htmlspecialchars($game['title']); ?></td>
                                <td><?php echo htmlspecialchars($game['slug']); ?></td>
                                <td><?php echo $game['featured'] ? 'Yes' : 'No'; ?></td>
                                <td><?php echo $game['is_published'] ? 'Yes' : 'No'; ?></td>
                                <td><?php echo date('M j, Y', strtotime($game['created_at'])); ?></td>
                                <td>
                                    <form method="GET" action="game-form.php" style="display: inline;">
                                        <input type="hidden" name="id" value="<?php echo $game['id']; ?>">
                                        <button type="submit" class="form-submit">Edit</button>
                                    </form>
                                    <form method="POST" action="delete-game.php" style="display: inline;">
                                        <input type="hidden" name="id" value="<?php echo $game['id']; ?>">
                                        <button type="submit" class="form-submit" style="background: #dc3545;"
                                                onclick="return confirm('Are you sure you want to delete this game?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <style>
        .nav-link {
            background: none;
            border: none;
            padding: 8px 15px;
            color: #333;
            text-decoration: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        .nav-link:hover {
            background: #f5f5f5;
        }
        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .content-header h1 {
            margin: 0;
        }
        .text-center {
            text-align: center;
            padding: 20px;
        }
        .table-container {
            overflow-x: auto;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        .table th, .table td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        .table th {
            background-color: #f5f5f5;
        }
        .table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
    </style>
</body>
</html>