<?php
/**
 * Direct Story Preview Handler
 *
 * This script renders a story preview directly in the admin interface
 * without using iframes or external URLs.
 */

// Include auth check
require_once '../includes/auth-check.php';

// Include database connection
require_once '../includes/db-connect.php';

// Set content type to HTML
header('Content-Type: text/html; charset=utf-8');

// Get the story ID from the query string
$storyId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Initialize variables
$story = null;
$author = null;
$tags = [];
$error = null;

try {
    // Get story data
    if ($storyId > 0) {
        $stmt = $db->prepare("
            SELECT s.*, a.name as author_name, a.age as author_age, a.location as author_location
            FROM stories s
            LEFT JOIN story_authors sa ON s.id = sa.story_id
            LEFT JOIN authors a ON sa.author_id = a.id
            WHERE s.id = ?
        ");
        $stmt->execute([$storyId]);
        $story = $stmt->fetch();

        if (!$story) {
            $error = "Story not found.";
        } else {
            // Get tags
            $stmt = $db->prepare("
                SELECT t.name
                FROM tags t
                JOIN story_tags st ON t.id = st.tag_id
                WHERE st.story_id = ?
                ORDER BY t.name
            ");
            $stmt->execute([$storyId]);
            $tags = $stmt->fetchAll(PDO::FETCH_COLUMN);
        }
    } else {
        $error = "Invalid story ID.";
    }
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// Extract content sections
$summary = '';
$storyContent = '';
$authorInfo = [
    'name' => $story['author_name'] ?? '',
    'age' => $story['author_age'] ?? '',
    'location' => $story['author_location'] ?? ''
];

if ($story && !empty($story['content'])) {
    // Extract summary
    if (preg_match('/## Summary\s*\n(.*?)(?:\n##|\n\*\*|\Z)/s', $story['content'], $summaryMatch)) {
        $summary = trim($summaryMatch[1]);
    }

    // Extract story content
    if (preg_match('/## Story\s*\n(.*?)(?:\n##|\Z)/s', $story['content'], $storyMatch)) {
        $storyContent = trim($storyMatch[1]);
    }
}

// Function to render the story content with proper styling
function renderStoryContent($content) {
    // Check if the content is HTML
    if (strpos($content, '<') !== false && strpos($content, '>') !== false) {
        // It's HTML, return as is
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
    <title><?php echo htmlspecialchars($story['title'] ?? 'Story Preview'); ?></title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .story-header {
            margin-bottom: 30px;
        }
        .story-title {
            font-size: 28px;
            margin-bottom: 10px;
        }
        .story-meta {
            color: #666;
            font-size: 14px;
            margin-bottom: 15px;
        }
        .story-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 20px;
        }
        .story-tag {
            background-color: #f0f0f0;
            border-radius: 4px;
            padding: 4px 8px;
            font-size: 12px;
        }
        .story-cover {
            width: 100%;
            max-height: 400px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .story-author {
            background-color: #f9f9f9;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .story-author-name {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .story-author-details {
            font-size: 14px;
            color: #666;
        }
        .story-summary {
            font-style: italic;
            margin-bottom: 30px;
            padding: 15px;
            background-color: #f5f5f5;
            border-radius: 8px;
        }
        .story-content {
            line-height: 1.8;
        }
        .story-content img {
            max-width: 100%;
            height: auto;
            border-radius: 4px;
            margin: 10px 0;
        }
        .story-content h2 {
            margin-top: 30px;
            margin-bottom: 15px;
            font-size: 24px;
        }
        .story-content h3 {
            margin-top: 25px;
            margin-bottom: 10px;
            font-size: 20px;
        }
        .story-content p {
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
    <?php elseif ($story): ?>
        <div class="story-header">
            <h1 class="story-title"><?php echo htmlspecialchars($story['title']); ?></h1>
            <div class="story-meta">
                <?php if (!empty($story['estimated_reading_time'])): ?>
                    <span><?php echo $story['estimated_reading_time']; ?> min read</span> • 
                <?php endif; ?>
                <span>Posted on <?php echo date('F j, Y', strtotime($story['created_at'])); ?></span>
            </div>
            
            <?php if (!empty($tags)): ?>
                <div class="story-tags">
                    <?php foreach ($tags as $tag): ?>
                        <span class="story-tag"><?php echo htmlspecialchars($tag); ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($story['cover_url'])): ?>
                <img src="<?php echo htmlspecialchars($story['cover_url']); ?>" alt="<?php echo htmlspecialchars($story['title']); ?>" class="story-cover">
            <?php endif; ?>
        </div>
        
        <?php if (!empty($authorInfo['name'])): ?>
            <div class="story-author">
                <div class="story-author-name">By <?php echo htmlspecialchars($authorInfo['name']); ?></div>
                <?php if (!empty($authorInfo['age']) || !empty($authorInfo['location'])): ?>
                    <div class="story-author-details">
                        <?php if (!empty($authorInfo['age'])): ?>
                            <span>Age: <?php echo htmlspecialchars($authorInfo['age']); ?></span>
                        <?php endif; ?>
                        
                        <?php if (!empty($authorInfo['age']) && !empty($authorInfo['location'])): ?>
                            <span> • </span>
                        <?php endif; ?>
                        
                        <?php if (!empty($authorInfo['location'])): ?>
                            <span>From: <?php echo htmlspecialchars($authorInfo['location']); ?></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($summary)): ?>
            <div class="story-summary">
                <?php echo renderStoryContent($summary); ?>
            </div>
        <?php endif; ?>
        
        <div class="story-content">
            <?php echo renderStoryContent($storyContent); ?>
        </div>
    <?php else: ?>
        <div class="error-message">
            <h2>No Story Found</h2>
            <p>The requested story could not be found.</p>
        </div>
    <?php endif; ?>
</body>
</html>
