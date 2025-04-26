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

    // Check if stories table exists
    $stmt = $db->query("SHOW TABLES LIKE 'stories'");
    if ($stmt->rowCount() === 0) {
        // Create stories table if it doesn't exist
        $db->exec("CREATE TABLE IF NOT EXISTS stories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            content TEXT NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL
        )");
    }

    // Check if story_tags table exists
    $stmt = $db->query("SHOW TABLES LIKE 'story_tags'");
    if ($stmt->rowCount() === 0) {
        // Create story_tags table if it doesn't exist
        $db->exec("CREATE TABLE IF NOT EXISTS story_tags (
            story_id INT NOT NULL,
            tag_id INT NOT NULL,
            PRIMARY KEY (story_id, tag_id)
        )");
    }

    // Get all columns from stories table
    $columns = [];
    $stmt = $db->query("DESCRIBE stories");
    while ($row = $stmt->fetch()) {
        $columns[] = $row['Field'];
    }

    // Determine the join condition based on available columns
    $joinCondition = "1=0"; // Default to no join if neither column exists
    if (in_array('author_id', $columns)) {
        $joinCondition = "s.author_id = a.id";
    } elseif (in_array('author', $columns)) {
        $joinCondition = "s.author = a.name";
    }

    // Get all stories with all available fields
    try {
        // Get a list of all story IDs first
        $query = "SELECT DISTINCT id FROM stories ORDER BY created_at DESC";
        $storyIds = $db->query($query)->fetchAll(PDO::FETCH_COLUMN);
        
        error_log("Story IDs: " . implode(', ', $storyIds));
        
        // Initialize stories array
        $stories = [];
        
        // Fetch each story by ID to ensure we only get one copy
        foreach ($storyIds as $id) {
            $query = "SELECT * FROM stories WHERE id = ? LIMIT 1";
            $stmt = $db->prepare($query);
            $stmt->execute([$id]);
            $story = $stmt->fetch();
            
            if ($story) {
                $stories[] = $story;
            }
        }
        
        error_log("Number of stories fetched: " . count($stories));
        
        // Then for each story, try to get the author information from story_authors table
        foreach ($stories as &$story) {
            try {
                // Get author from story_authors table
                $stmt = $db->prepare("
                    SELECT a.id, a.name
                    FROM story_authors sa
                    JOIN authors a ON sa.author_id = a.id
                    WHERE sa.story_id = ?
                ");
                $stmt->execute([$story['id']]);
                $author = $stmt->fetch();
                
                if ($author) {
                    $story['author_id'] = $author['id'];
                    $story['author_name'] = $author['name'];
                } else {
                    $story['author_name'] = 'Unknown';
                }
            } catch (Exception $e) {
                error_log("Error fetching author for story ID " . $story['id'] . ": " . $e->getMessage());
                $story['author_name'] = 'Unknown';
            }
            
            // Get tags for the story
            try {
                $stmt = $db->prepare("
                    SELECT GROUP_CONCAT(t.name ORDER BY t.name ASC SEPARATOR ', ') as tags
                    FROM story_tags st
                    JOIN tags t ON st.tag_id = t.id
                    WHERE st.story_id = ?
                ");
                $stmt->execute([$story['id']]);
                $tags = $stmt->fetch();
                
                if ($tags && isset($tags['tags'])) {
                    $story['tags'] = $tags['tags'];
                } else {
                    $story['tags'] = '';
                }
            } catch (Exception $e) {
                error_log("Error fetching tags for story ID " . $story['id'] . ": " . $e->getMessage());
                $story['tags'] = '';
            }
            
            // Debug log for author information
            error_log("Story ID: " . $story['id'] . ", Author ID: " . ($story['author_id'] ?? 'null') . ", Author Name: " . ($story['author_name'] ?? 'null'));
        }
    } catch (Exception $e) {
        error_log("Error fetching stories: " . $e->getMessage());
        $stories = [];
    }

} catch (PDOException $e) {
    error_log("Stories page error: " . $e->getMessage());
    $error = "Error loading stories. Please try again.";
    $stories = [];
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
    <title>Stories - Admin</title>
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
            <h1>Stories</h1>
            <form method="GET" action="story-form.php" style="display: inline;">
                <button type="submit" class="form-submit">Add New Story</button>
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
                        <th>Author</th>
                        <th>Tags</th>
                        <th>Content Preview</th>
                        <th>Created</th>
                        <th>Updated</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($stories)): ?>
                        <tr>
                            <td colspan="8" class="text-center">No stories found. Add your first story!</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($stories as $story): ?>
                            <tr>
                                <td><?php echo $story['id']; ?></td>
                                <td><?php echo htmlspecialchars($story['title']); ?></td>
                                <td><?php echo htmlspecialchars($story['author_name'] ?? $story['author'] ?? 'Unknown'); ?></td>
                                <td><?php echo htmlspecialchars($story['tags'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars(substr($story['content'], 0, 100) . '...'); ?></td>
                                <td><?php echo date('M j, Y', strtotime($story['created_at'])); ?></td>
                                <td><?php echo date('M j, Y', strtotime($story['updated_at'])); ?></td>
                                <td>
                                    <form method="GET" action="story-form.php" style="display: inline;">
                                        <input type="hidden" name="id" value="<?php echo $story['id']; ?>">
                                        <button type="submit" class="form-submit">Edit</button>
                                    </form>
                                    <form method="POST" action="delete-story.php" style="display: inline;">
                                        <input type="hidden" name="id" value="<?php echo $story['id']; ?>">
                                        <button type="submit" class="form-submit" style="background: #dc3545;"
                                                onclick="return confirm('Are you sure you want to delete this story?')">Delete</button>
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