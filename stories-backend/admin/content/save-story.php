<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// This is a processing script, no UI needed

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
    $featured = isset($_POST['featured']) ? 1 : 0;
    $is_published = isset($_POST['is_published']) ? 1 : 0;
    $is_sponsored = isset($_POST['is_sponsored']) ? 1 : 0;
    $published_at = $_POST['published_at'] ?? '';
    $review_count = isset($_POST['review_count']) ? (int)$_POST['review_count'] : 0;
    $average_rating = isset($_POST['average_rating']) ? (float)$_POST['average_rating'] : 0;
    $tags = $_POST['tags'] ?? [];

    // Always set reading time to 1 minute
    $_POST['estimated_reading_time'] = '1 minute';

    // Validate age group
    $age_group = $_POST['age_group'] ?? '7-12';
    if (!in_array($age_group, ['0-3', '4-6', '7-12', '13+'])) {
        $age_group = '7-12'; // Default to 7-12 if invalid
    }
    $_POST['age_group'] = $age_group;

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

    // Check for required fields
    $requiredFields = ['title', 'author_id', 'content'];
    foreach ($columnInfo as $field => $info) {
        if ($info['Null'] === 'NO' && $info['Default'] === null && !in_array($field, ['id', 'created_at', 'updated_at'])) {
            $requiredFields[] = $field;
        }
    }

    // Validate all required fields
    foreach ($requiredFields as $field) {
        if (!isset($_POST[$field]) || $_POST[$field] === '') {
            throw new Exception("Please fill in all required fields. Missing: " . ucfirst(str_replace('_', ' ', $field)));
        }
    }

    // Generate slug from title if not provided
    if (in_array('slug', $columns) && empty($slug)) {
        $slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $title));
        $slug = trim($slug, '-');
    }

    // Prepare data for insert/update
    $data = [
        'title' => $title,
        'content' => $content
    ];

    // Verify author exists
    $stmt = $db->prepare("SELECT id, name FROM authors WHERE id = ?");
    $stmt->execute([$author_id]);
    $authorData = $stmt->fetch();

    if (!$authorData) {
        throw new Exception("Selected author does not exist or could not be verified");
    }

    error_log("Verified author: ID=" . $authorData['id'] . ", Name=" . $authorData['name']);

    // Include author name for backward compatibility
    if (in_array('author', $columns)) {
        $data['author'] = $author['name'];
    }

    // Add slug if the column exists
    if (in_array('slug', $columns)) {
        $data['slug'] = $slug;
    }

    // Add featured and is_published if the columns exist
    if (in_array('featured', $columns)) {
        $data['featured'] = $featured;
    }

    if (in_array('is_published', $columns)) {
        $data['is_published'] = $is_published;
    }

    // Add is_sponsored if the column exists
    if (in_array('is_sponsored', $columns)) {
        $data['is_sponsored'] = $is_sponsored;
    }

    // Add published_at if the column exists
    if (in_array('published_at', $columns)) {
        if (!empty($published_at)) {
            // Convert HTML datetime-local format to MySQL datetime format
            $date = new DateTime($published_at);
            $data['published_at'] = $date->format('Y-m-d H:i:s');
        } else {
            // Use current datetime if empty
            $data['published_at'] = date('Y-m-d H:i:s');
        }
    }

    // Add review_count if the column exists
    if (in_array('review_count', $columns)) {
        $data['review_count'] = $review_count;
    }

    // Add average_rating if the column exists
    if (in_array('average_rating', $columns)) {
        $data['average_rating'] = $average_rating;
    }

    // Process all boolean fields
    $booleanFields = ['featured', 'is_published', 'is_sponsored', 'is_self_published', 'is_ai_enhanced', 'needs_moderation', 'allow_reviews'];
    foreach ($booleanFields as $field) {
        if (in_array($field, $columns)) {
            // Check if the field was submitted (even if unchecked)
            if (isset($_POST[$field . '_submitted'])) {
                $data[$field] = isset($_POST[$field]) ? 1 : 0;
            } else if (isset($_POST[$field])) {
                $data[$field] = 1;
            }
        }
    }

    // Enforce business rules for source_type and allow_reviews
    if (in_array('source_type', $columns) && in_array('allow_reviews', $columns)) {
        $source_type = $_POST['source_type'] ?? 'child';

        // Validate source_type
        if (!in_array($source_type, ['child', 'parent', 'classic'])) {
            $source_type = 'child';
        }

        $data['source_type'] = $source_type;

        // Apply business rules
        if ($source_type === 'child') {
            // Children's stories NEVER get reviews
            $data['allow_reviews'] = 0;
            error_log("ENFORCING RULE: Child story - allow_reviews set to 0");
        } else if ($source_type === 'classic') {
            // Classic works ALWAYS get reviews
            $data['allow_reviews'] = 1;
            error_log("ENFORCING RULE: Classic work - allow_reviews set to 1");
        }

        error_log("Final values - source_type: {$data['source_type']}, allow_reviews: {$data['allow_reviews']}");
    }

    // Add cover_url field if it exists in the table
    if (in_array('cover_url', $columns) && isset($_POST['cover_url'])) {
        $data['cover_url'] = $_POST['cover_url'];
    }

    // Add any additional fields from the form
    foreach ($_POST as $key => $value) {
        if (!in_array($key, ['id', 'title', 'author_id', 'content', 'slug', 'featured', 'is_published', 'is_sponsored', 'is_self_published', 'is_ai_enhanced', 'needs_moderation', 'published_at', 'review_count', 'average_rating', 'tags', 'cover_url']) && in_array($key, $columns)) {
            // Handle integer fields
            if (isset($columnInfo[$key]) && (strpos($columnInfo[$key]['Type'], 'int') !== false || strpos($columnInfo[$key]['Type'], 'tinyint') !== false)) {
                $data[$key] = (int)$value;
            }
            // Handle decimal fields
            else if (isset($columnInfo[$key]) && (strpos($columnInfo[$key]['Type'], 'decimal') !== false || strpos($columnInfo[$key]['Type'], 'float') !== false || strpos($columnInfo[$key]['Type'], 'double') !== false)) {
                $data[$key] = (float)$value;
            }
            // Handle datetime fields
            else if (isset($columnInfo[$key]) && strpos($columnInfo[$key]['Type'], 'datetime') !== false) {
                if (!empty($value)) {
                    $date = new DateTime($value);
                    $data[$key] = $date->format('Y-m-d H:i:s');
                } else {
                    $data[$key] = date('Y-m-d H:i:s');
                }
            }
            // Handle other fields
            else {
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
        $stmt = $db->query("SHOW TABLES LIKE 'story_tags'");
        if ($stmt->rowCount() > 0) {
            $stmt = $db->prepare("DELETE FROM story_tags WHERE story_id = ?");
            $stmt->execute([$id]);
        }

        // Delete existing author relationships for THIS STORY ONLY
        $stmt = $db->query("SHOW TABLES LIKE 'story_authors'");
        if ($stmt->rowCount() > 0) {
            // First delete only this story's author relationships
            $stmt = $db->prepare("DELETE FROM story_authors WHERE story_id = ?");
            $stmt->execute([$id]);

            // Then add the new author relationship for this story
            $stmt = $db->prepare("INSERT INTO story_authors (story_id, author_id) VALUES (?, ?)");
            $stmt->execute([$id, $author_id]);
            error_log("Updated story_authors relationship: story_id=$id, author_id=$author_id");

            // Debug log all story_authors relationships
            $allRelationships = $db->query("SELECT sa.story_id, s.title, sa.author_id, a.name
                                           FROM story_authors sa
                                           JOIN stories s ON sa.story_id = s.id
                                           JOIN authors a ON sa.author_id = a.id")->fetchAll();

            foreach ($allRelationships as $rel) {
                error_log("Story-Author relationship: Story ID: " . $rel['story_id'] .
                         ", Title: " . $rel['title'] .
                         ", Author ID: " . $rel['author_id'] .
                         ", Name: " . $rel['name']);
            }
        }

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

        // Add author relationship for new story
        $stmt = $db->query("SHOW TABLES LIKE 'story_authors'");
        if ($stmt->rowCount() > 0) {
            $stmt = $db->prepare("INSERT INTO story_authors (story_id, author_id) VALUES (?, ?)");
            $stmt->execute([$id, $author_id]);
            error_log("Created story_authors relationship: story_id=$id, author_id=$author_id");
        }

        $message = "Story created successfully";
    }

    // Add tags if the story_tags table exists and tags were provided
    if (!empty($tags)) {
        $stmt = $db->query("SHOW TABLES LIKE 'story_tags'");
        if ($stmt->rowCount() > 0) {
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
    }

    // Commit transaction
    $db->commit();

    // Store success message and redirect
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
    $_SESSION['error'] = $e->getMessage();

    $redirect = $id ? "story-form.php?id=$id" : "story-form.php";
    header("Location: $redirect");
    exit;
}

// No footer needed for processing script
