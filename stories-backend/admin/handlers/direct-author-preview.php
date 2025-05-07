<?php
/**
 * Direct Author Preview Handler
 *
 * This script provides a direct HTML preview of an author.
 * It's used as a fallback when the AJAX preview fails.
 */

// Include auth check
require_once '../includes/auth-check.php';

// Include database connection
require_once '../includes/db-connect.php';

// Get author ID from query string
$authorId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Initialize variables
$author = null;
$stories = [];
$posts = [];
$error = null;

try {
    // Get author details
    $stmt = $db->prepare("
        SELECT a.*,
               COUNT(DISTINCT s.id) as story_count,
               COUNT(DISTINCT p.id) as post_count
        FROM authors a
        LEFT JOIN stories s ON a.id = s.author_id
        LEFT JOIN posts p ON a.id = p.author_id
        WHERE a.id = ?
        GROUP BY a.id
    ");
    $stmt->execute([$authorId]);
    $author = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($author) {
        // Get stories by this author
        $stmtStories = $db->prepare("
            SELECT id, title, slug, published_at
            FROM stories
            WHERE author_id = ?
            ORDER BY published_at DESC
            LIMIT 10
        ");
        $stmtStories->execute([$authorId]);
        $stories = $stmtStories->fetchAll(PDO::FETCH_ASSOC);

        // Get posts by this author
        $stmtPosts = $db->prepare("
            SELECT id, title, slug, published_at
            FROM posts
            WHERE author_id = ?
            ORDER BY published_at DESC
            LIMIT 10
        ");
        $stmtPosts->execute([$authorId]);
        $posts = $stmtPosts->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $error = "Author not found";
    }
} catch (Exception $e) {
    $error = "Error loading author: " . $e->getMessage();
}

// Set page title
$pageTitle = $author ? $author['name'] : 'Author Preview';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            padding: 20px;
        }
        .author-card {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .author-header {
            display: flex;
            padding: 20px;
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
        }
        .author-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 20px;
        }
        .author-info {
            flex: 1;
        }
        .author-name {
            margin: 0 0 10px;
            font-size: 24px;
            color: #212529;
        }
        .author-meta {
            color: #6c757d;
            font-size: 14px;
        }
        .author-bio {
            padding: 20px;
            border-bottom: 1px solid #e9ecef;
        }
        .author-content {
            padding: 20px;
            display: flex;
            flex-wrap: wrap;
        }
        .author-stories, .author-posts {
            flex: 1;
            min-width: 250px;
            padding: 0 10px;
        }
        .error-message {
            text-align: center;
            padding: 40px 20px;
            color: #721c24;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 8px;
        }
        @media (max-width: 768px) {
            .author-header {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }
            .author-avatar {
                margin-right: 0;
                margin-bottom: 15px;
            }
            .author-content {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($error): ?>
            <div class="error-message">
                <h2>Error</h2>
                <p><?php echo htmlspecialchars($error); ?></p>
            </div>
        <?php elseif ($author): ?>
            <div class="author-card">
                <div class="author-header">
                    <img src="<?php echo htmlspecialchars($author['avatar_url'] ?? $author['avatar'] ?? '../assets/images/default-avatar.svg'); ?>" alt="<?php echo htmlspecialchars($author['name']); ?>" class="author-avatar">
                    <div class="author-info">
                        <h1 class="author-name"><?php echo htmlspecialchars($author['name']); ?></h1>
                        <div class="author-meta">
                            <?php if (!empty($author['email'])): ?>
                                <div><strong>Email:</strong> <?php echo htmlspecialchars($author['email']); ?></div>
                            <?php endif; ?>
                            <?php if (!empty($author['author_type'])): ?>
                                <div><strong>Type:</strong> <?php echo htmlspecialchars($author['author_type']); ?></div>
                            <?php endif; ?>
                            <?php if (!empty($author['age'])): ?>
                                <div><strong>Age:</strong> <?php echo htmlspecialchars($author['age']); ?></div>
                            <?php endif; ?>
                            <?php if (!empty($author['location'])): ?>
                                <div><strong>Location:</strong> <?php echo htmlspecialchars($author['location']); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="author-bio">
                    <h3>Biography</h3>
                    <?php echo !empty($author['bio']) ? $author['bio'] : '<p>No biography available.</p>'; ?>
                </div>

                <div class="author-content">
                    <div class="author-stories">
                        <h3>Stories (<?php echo count($stories); ?>)</h3>
                        <?php if (!empty($stories)): ?>
                            <ul>
                                <?php foreach ($stories as $story): ?>
                                    <li><?php echo htmlspecialchars($story['title']); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p>No stories found.</p>
                        <?php endif; ?>
                    </div>

                    <div class="author-posts">
                        <h3>Blog Posts (<?php echo count($posts); ?>)</h3>
                        <?php if (!empty($posts)): ?>
                            <ul>
                                <?php foreach ($posts as $post): ?>
                                    <li><?php echo htmlspecialchars($post['title']); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p>No blog posts found.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="error-message">
                <h2>Author Not Found</h2>
                <p>The requested author could not be found.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
