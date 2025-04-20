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
    header("Location: stories.php");
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
    $slug = trim($_POST['slug'] ?? '');
    $featured = isset($_POST['featured']) ? $_POST['featured'] : 0;
    $published_at = $_POST['published_at'] ?? '';
    $tags = $_POST['tags'] ?? [];

    // Validate required fields
    if (empty($title) || empty($author_id) || empty($content)) {
        throw new Exception("Please fill in all required fields");
    }

    // Get author name for fallback
    $stmt = $db->prepare("SELECT name FROM authors WHERE id = ?");
    $stmt->execute([$author_id]);
    $author = $stmt->fetch();
    
    if (!$author) {
        throw new Exception("Selected author does not exist");
    }

    // Get all columns from stories table
    $columns = [];
    $columnInfo = [];
    $stmt = $db->query("DESCRIBE stories");
    while ($row = $stmt->fetch()) {
        $columns[] = $row['Field'];
        $columnInfo[$row['Field']] = $row;
    }

    // Check if author_id column exists in stories table
    $hasAuthorIdColumn = in_array('author_id', $columns);
    
    // Check if author column exists in stories table
    $hasAuthorColumn = in_array('author', $columns);
    
    // Check if slug column exists in stories table
    $hasSlugColumn = in_array('slug', $columns);
    
    // Check if featured column exists in stories table
    $hasFeaturedColumn = in_array('featured', $columns);
    
    // Check if published_at column exists in stories table
    $hasPublishedAtColumn = in_array('published_at', $columns);

    // Generate slug from title if not provided
    if ($hasSlugColumn && empty($slug)) {
        $slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $title));
        $slug = trim($slug, '-');
    }

    // Prepare data for insert/update
    $data = [
        'title' => $title,
        'content' => $content
    ];

    // Add author data
    if ($hasAuthorIdColumn) {
        $data['author_id'] = $author_id;
    }
    
    // Include author name for backward compatibility if the column exists
    if ($hasAuthorColumn) {
        $data['author'] = $author['name'];
    }
    
    // Add slug if the column exists
    if ($hasSlugColumn) {
        $data['slug'] = $slug;
    }
    
    // Add featured if the column exists
    if ($hasFeaturedColumn) {
        // Check if featured is an integer field
        $featuredType = isset($columnInfo['featured']) ? $columnInfo['featured']['Type'] : '';
        $isIntField = strpos($featuredType, 'int') !== false || strpos($featuredType, 'tinyint') !== false;
        
        if ($isIntField) {
            // Ensure featured is an integer
            $data['featured'] = empty($featured) ? 0 : 1;
        } else {
            $data['featured'] = $featured;
        }
    }
    
    // Add published_at if the column exists
    if ($hasPublishedAtColumn) {
        if (!empty($published_at)) {
            // Convert HTML datetime-local format to MySQL datetime format
            $date = new DateTime($published_at);
            $data['published_at'] = $date->format('Y-m-d H:i:s');
        } else {
            // Use current datetime if empty
            $data['published_at'] = date('Y-m-d H:i:s');
        }
    }

    // Add any additional fields from the form
    foreach ($_POST as $key => $value) {
        if (!in_array($key, ['id', 'title', 'author_id', 'content', 'slug', 'featured', 'published_at', 'tags']) && in_array($key, $columns)) {
            // Handle datetime fields
            if (isset($columnInfo[$key]) && strpos($columnInfo[$key]['Type'], 'datetime') !== false) {
                if (!empty($value)) {
                    // Convert HTML datetime-local format to MySQL datetime format
                    $date = new DateTime($value);
                    $data[$key] = $date->format('Y-m-d H:i:s');
                } else if ($columnInfo[$key]['Null'] === 'NO' && $columnInfo[$key]['Default'] === null) {
                    // If field is required and no value provided, use current datetime
                    $data[$key] = date('Y-m-d H:i:s');
                }
            } else {
                $data[$key] = trim($value);
            }
        }
    }

    if ($id) {
        // Verify story exists
        $stmt = $db->prepare("SELECT id FROM stories WHERE id = ?");
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            throw new Exception("Story not found");
        }

        // Update existing story
        $setClause = [];
        $updateData = [];
        
        foreach ($data as $key => $value) {
            $setClause[] = "$key = ?";
            $updateData[] = $value;
        }
        
        // Add updated_at
        $setClause[] = "updated_at = NOW()";
        
        // Add ID at the end
        $updateData[] = $id;
        
        $sql = "UPDATE stories SET " . implode(', ', $setClause) . " WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute($updateData);
        
        // Delete existing tags
        $stmt = $db->prepare("DELETE FROM story_tags WHERE story_id = ?");
        $stmt->execute([$id]);

        $message = "Story updated successfully";
    } else {
        // Create new story
        $columns = array_keys($data);
        $placeholders = array_fill(0, count($columns), '?');
        
        // Add created_at and updated_at
        $columns[] = 'created_at';
        $columns[] = 'updated_at';
        $placeholders[] = 'NOW()';
        $placeholders[] = 'NOW()';
        
        $sql = "INSERT INTO stories (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $stmt = $db->prepare($sql);
        $stmt->execute(array_values($data));
        $id = $db->lastInsertId();

        $message = "Story created successfully";
    }

    // Add tags if the story_tags table exists
    if (!empty($tags)) {
        try {
            $values = array_fill(0, count($tags), "($id, ?)");
            $sql = "INSERT INTO story_tags (story_id, tag_id) VALUES " . implode(', ', $values);
            $stmt = $db->prepare($sql);
            
            $i = 1;
            foreach ($tags as $tag_id) {
                $stmt->bindValue($i++, $tag_id);
            }
            $stmt->execute();
        } catch (PDOException $e) {
            // Ignore tag errors, just log them
            error_log("Error adding tags: " . $e->getMessage());
        }
    }

    // Commit transaction
    $db->commit();

    // Store success message and redirect
    session_start();
    $_SESSION['success'] = $message;
    
    header("Location: stories.php");
    exit;

} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($db)) {
        $db->rollBack();
    }

    error_log("Save story error: " . $e->getMessage());
    
    // Store error in session and redirect back to form
    session_start();
    $_SESSION['error'] = $e->getMessage();
    
    $redirect = $id ? "story-form.php?id=$id" : "story-form.php";
    header("Location: $redirect");
    exit;
}