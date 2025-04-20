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

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: blog-posts.php");
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

    // Start transaction
    $db->beginTransaction();

    // Get form data
    $id = $_POST['id'] ?? null;
    $title = trim($_POST['title'] ?? '');
    $author_id = $_POST['author_id'] ?? '';
    $content = trim($_POST['content'] ?? '');
    $excerpt = trim($_POST['excerpt'] ?? '');
    $status = $_POST['status'] ?? 'draft';
    $tags = $_POST['tags'] ?? [];

    // Validate required fields
    if (empty($title) || empty($author_id) || empty($content)) {
        throw new Exception("Please fill in all required fields");
    }

    // Verify author exists
    $stmt = $db->prepare("SELECT id, name FROM authors WHERE id = ?");
    $stmt->execute([$author_id]);
    $author = $stmt->fetch();
    if (!$author) {
        throw new Exception("Selected author does not exist");
    }

    // Verify tags exist
    if (!empty($tags)) {
        $placeholders = str_repeat('?,', count($tags) - 1) . '?';
        $stmt = $db->prepare("SELECT COUNT(*) FROM tags WHERE id IN ($placeholders)");
        $stmt->execute($tags);
        if ($stmt->fetchColumn() != count($tags)) {
            throw new Exception("One or more selected tags do not exist");
        }
    }

    // Validate status
    if (!in_array($status, ['draft', 'published'])) {
        throw new Exception("Invalid status value");
    }

    // Check if blog_posts table exists
    $blogTableName = 'blog_posts';
    $stmt = $db->query("SHOW TABLES LIKE 'blog_posts'");
    if ($stmt->rowCount() === 0) {
        // Check if blog table exists instead
        $stmt = $db->query("SHOW TABLES LIKE 'blog'");
        if ($stmt->rowCount() > 0) {
            $blogTableName = 'blog';
        } else {
            // Create blog_posts table if neither exists
            $db->exec("CREATE TABLE IF NOT EXISTS blog_posts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                author_id INT NOT NULL,
                content TEXT NOT NULL,
                excerpt TEXT,
                status ENUM('draft', 'published') NOT NULL DEFAULT 'draft',
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL
            )");
        }
    }

    // Check if post_tags table exists
    $postTagsTableName = 'post_tags';
    $stmt = $db->query("SHOW TABLES LIKE 'post_tags'");
    if ($stmt->rowCount() === 0) {
        // Check if blog_tags table exists instead
        $stmt = $db->query("SHOW TABLES LIKE 'blog_tags'");
        if ($stmt->rowCount() > 0) {
            $postTagsTableName = 'blog_tags';
        } else {
            // Create post_tags table if neither exists
            $db->exec("CREATE TABLE IF NOT EXISTS post_tags (
                post_id INT NOT NULL,
                tag_id INT NOT NULL,
                PRIMARY KEY (post_id, tag_id)
            )");
        }
    }

    // Get all columns from the blog table
    $columns = [];
    $stmt = $db->query("DESCRIBE $blogTableName");
    while ($row = $stmt->fetch()) {
        $columns[] = $row['Field'];
    }

    // Check if author_id column exists
    $hasAuthorIdColumn = in_array('author_id', $columns);
    
    // Check if excerpt column exists
    $hasExcerptColumn = in_array('excerpt', $columns);
    
    // Check if status column exists
    $hasStatusColumn = in_array('status', $columns);

    if ($id) {
        // Verify post exists
        $stmt = $db->prepare("SELECT id FROM $blogTableName WHERE id = ?");
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            throw new Exception("Blog post not found");
        }

        // Update existing post
        $setClause = ["title = ?"];
        $params = [$title];
        
        if ($hasAuthorIdColumn) {
            $setClause[] = "author_id = ?";
            $params[] = $author_id;
        }
        
        $setClause[] = "content = ?";
        $params[] = $content;
        
        if ($hasExcerptColumn) {
            $setClause[] = "excerpt = ?";
            $params[] = $excerpt;
        }
        
        if ($hasStatusColumn) {
            $setClause[] = "status = ?";
            $params[] = $status;
        }
        
        $setClause[] = "updated_at = NOW()";
        $params[] = $id; // Add ID for WHERE clause
        
        $sql = "UPDATE $blogTableName SET " . implode(', ', $setClause) . " WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        // Delete existing tags
        $stmt = $db->prepare("DELETE FROM $postTagsTableName WHERE post_id = ?");
        $stmt->execute([$id]);

        $message = "Blog post updated successfully";
    } else {
        // Create new post
        $columns = ["title"];
        $placeholders = ["?"];
        $params = [$title];
        
        if ($hasAuthorIdColumn) {
            $columns[] = "author_id";
            $placeholders[] = "?";
            $params[] = $author_id;
        }
        
        $columns[] = "content";
        $placeholders[] = "?";
        $params[] = $content;
        
        if ($hasExcerptColumn) {
            $columns[] = "excerpt";
            $placeholders[] = "?";
            $params[] = $excerpt;
        }
        
        if ($hasStatusColumn) {
            $columns[] = "status";
            $placeholders[] = "?";
            $params[] = $status;
        }
        
        $columns[] = "created_at";
        $columns[] = "updated_at";
        $placeholders[] = "NOW()";
        $placeholders[] = "NOW()";
        
        $sql = "INSERT INTO $blogTableName (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $id = $db->lastInsertId();

        $message = "Blog post created successfully";
    }

    // Add tags
    if (!empty($tags)) {
        $values = array_fill(0, count($tags), "($id, ?)");
        $sql = "INSERT INTO $postTagsTableName (post_id, tag_id) VALUES " . implode(', ', $values);
        $stmt = $db->prepare($sql);
        
        $i = 1;
        foreach ($tags as $tag_id) {
            $stmt->bindValue($i++, $tag_id);
        }
        $stmt->execute();
    }

    // Commit transaction
    $db->commit();

    // Store success message and redirect
    session_start();
    $_SESSION['success'] = $message;
    
    header("Location: blog-posts.php");
    exit;

} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($db)) {
        $db->rollBack();
    }

    error_log("Save blog post error: " . $e->getMessage());
    
    // Store error in session and redirect back to form
    session_start();
    $_SESSION['error'] = $e->getMessage();
    
    $redirect = $id ? "post-form.php?id=$id" : "post-form.php";
    header("Location: $redirect");
    exit;
}