<?php
/**
 * Direct Post Preview Handler
 *
 * This script renders a blog post preview directly in the admin interface
 * without using iframes or external URLs.
 */

// Include auth check
require_once '../includes/auth-check.php';

// Include database connection
require_once '../includes/db-connect.php';

// Set content type to HTML
header('Content-Type: text/html; charset=utf-8');

// Get the post ID from the query string
$postId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Initialize variables
$post = null;
$tags = [];
$error = null;

try {
    // Get post data
    if ($postId > 0) {
        $stmt = $db->prepare("
            SELECT p.*
            FROM blog_posts p
            WHERE p.id = ?
        ");
        $stmt->execute([$postId]);
        $post = $stmt->fetch();

        if (!$post) {
            $error = "Blog post not found.";
        } else {
            // Get tags
            $stmt = $db->prepare("
                SELECT t.name
                FROM tags t
                JOIN post_tags pt ON t.id = pt.tag_id
                WHERE pt.post_id = ?
                ORDER BY t.name
            ");
            $stmt->execute([$postId]);
            $tags = $stmt->fetchAll(PDO::FETCH_COLUMN);
        }
    } else {
        $error = "Invalid blog post ID.";
    }
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// Extract content sections
$excerpt = '';
$postContent = '';

if ($post) {
    // First check if we have an excerpt field in the database
    if (!empty($post['excerpt'])) {
        $excerpt = $post['excerpt'];
    }
    
    // Get the main content
    if (!empty($post['content'])) {
        $postContent = $post['content'];
    }
}

// Function to render the post content with proper styling
function renderPostContent($content) {
    // Check if the content is HTML
    if (strpos($content, '<') !== false && strpos($content, '>') !== false) {
        // It's HTML, process it to ensure images have absolute URLs
        $dom = new DOMDocument();

        // Use error suppression to avoid warnings about HTML5 tags
        @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $content);

        // Process all images
        $images = $dom->getElementsByTagName('img');
        foreach ($images as $img) {
            $src = $img->getAttribute('src');

            // If the src is not an absolute URL, make it absolute
            if ($src && strpos($src, 'http') !== 0) {
                $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'api.storiesfromtheweb.org';
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
                $newSrc = "$protocol://$host" . (strpos($src, '/') === 0 ? $src : "/$src");
                $img->setAttribute('src', $newSrc);
            }
        }

        // Extract the body content
        $body = $dom->getElementsByTagName('body')->item(0);
        if ($body) {
            // Convert back to HTML string
            $html = '';
            foreach ($body->childNodes as $child) {
                $html .= $dom->saveHTML($child);
            }
            return $html;
        }

        // Fallback if DOM processing fails
        return $content;
    } else {
        // It's plain text, convert newlines to <br> tags
        return nl2br(htmlspecialchars($content));
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($post['title'] ?? 'Blog Post Preview'); ?></title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .post-header {
            margin-bottom: 30px;
        }
        .post-title {
            font-size: 28px;
            margin-bottom: 10px;
        }
        .post-meta {
            color: #666;
            font-size: 14px;
            margin-bottom: 15px;
        }
        .post-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 20px;
        }
        .post-tag {
            background-color: #f0f0f0;
            border-radius: 4px;
            padding: 4px 8px;
            font-size: 12px;
        }
        .post-cover {
            width: 100%;
            max-height: 400px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .post-excerpt {
            font-style: italic;
            margin-bottom: 30px;
            padding: 15px;
            background-color: #f5f5f5;
            border-radius: 8px;
        }
        .post-content {
            line-height: 1.8;
        }
        .post-content img {
            max-width: 100%;
            height: auto;
            border-radius: 4px;
            margin: 10px 0;
        }
        .post-content h2 {
            margin-top: 30px;
            margin-bottom: 15px;
            font-size: 24px;
        }
        .post-content h3 {
            margin-top: 25px;
            margin-bottom: 10px;
            font-size: 20px;
        }
        .post-content p {
            margin-bottom: 15px;
        }
        .error-message {
            color: #721c24;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php if ($error): ?>
        <div class="error-message">
            <h2>Error</h2>
            <p><?php echo htmlspecialchars($error); ?></p>
        </div>
    <?php elseif ($post): ?>
        <div class="post-header">
            <h1 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h1>
            <div class="post-meta">
                <span>Posted on <?php echo date('F j, Y', strtotime($post['created_at'])); ?></span>
            </div>

            <?php if (!empty($tags)): ?>
                <div class="post-tags">
                    <?php foreach ($tags as $tag): ?>
                        <span class="post-tag"><?php echo htmlspecialchars($tag); ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($post['cover_url'])): ?>
                <img src="<?php echo htmlspecialchars($post['cover_url']); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>" class="post-cover">
            <?php endif; ?>
        </div>

        <?php if (!empty($excerpt)): ?>
            <div class="post-excerpt">
                <?php echo renderPostContent($excerpt); ?>
            </div>
        <?php endif; ?>

        <div class="post-content">
            <?php echo renderPostContent($postContent); ?>
        </div>
    <?php else: ?>
        <div class="error-message">
            <h2>No Blog Post Found</h2>
            <p>The requested blog post could not be found.</p>
        </div>
    <?php endif; ?>
</body>
</html>
