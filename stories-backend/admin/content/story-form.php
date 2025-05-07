<?php
/**
 * Story Form Page
 *
 * This page displays a form for adding or editing a story.
 */

// Include auth check
require_once '../includes/auth-check.php';

// Include database connection
require_once '../includes/db-connect.php';

// Include image upload component
require_once '../includes/image-upload-component.php';

// Include AI image generator component
require_once '../includes/ai-image-generator-component.php';

try {
    // Get story if editing
    $story = null;
    if (isset($_GET['id'])) {
        try {
            // First try to get just the story without the join to ensure we can at least load the basic data
            $stmt = $db->prepare("SELECT * FROM stories WHERE id = ?");
            $stmt->execute([$_GET['id']]);
            $story = $stmt->fetch();

            if (!$story) {
                header("Location: stories.php");
                exit;
            }

            // Now try to get author information from story_authors table
            try {
                $stmt = $db->prepare("
                    SELECT a.id as author_id, a.name as author_name
                    FROM story_authors sa
                    JOIN authors a ON sa.author_id = a.id
                    WHERE sa.story_id = ?
                ");
                $stmt->execute([$story['id']]);
                $author = $stmt->fetch();

                if ($author) {
                    $story['author_name'] = $author['author_name'];
                    $story['author_id'] = $author['author_id'];
                    error_log("Found author for story: " . $author['author_name'] . " (ID: " . $author['author_id'] . ")");
                }
            } catch (Exception $e) {
                error_log("Error fetching author: " . $e->getMessage());
                // Continue even if author fetch fails
            }

            // Debug log for story and author information
            error_log("Story ID: " . $story['id']);
            error_log("Story author_id: " . ($story['author_id'] ?? 'null'));
            error_log("Story author_name: " . ($story['author_name'] ?? 'null'));
        } catch (Exception $e) {
            error_log("Error loading story: " . $e->getMessage());
            header("Location: stories.php");
            exit;
        }
    }

    // Get authors for dropdown
    $authors = $db->query("SELECT id, name, author_type FROM authors ORDER BY name")->fetchAll();

    // Get tags for dropdown
    $tags = $db->query("SELECT id, name FROM tags ORDER BY name")->fetchAll();

    // Get story tags if editing
    $storyTags = [];
    if ($story) {
        $stmt = $db->prepare("SELECT tag_id FROM story_tags WHERE story_id = ?");
        $stmt->execute([$story['id']]);
        $storyTags = array_column($stmt->fetchAll(), 'tag_id');
    }

    // Get table column information for dynamic form fields
    $stmt = $db->prepare("DESCRIBE stories");
    $stmt->execute();
    $columns = $stmt->fetchAll();

    // Organize column info for easier access
    $columnInfo = [];
    $additionalFields = [];

    foreach ($columns as $column) {
        $columnInfo[$column['Field']] = $column;

        // Skip standard fields that are handled explicitly
        if (!in_array($column['Field'], ['id', 'title', 'content', 'author_id', 'created_at', 'updated_at', 'cover_url'])) {
            $additionalFields[] = $column['Field'];
        }
    }

} catch (PDOException $e) {
    error_log("Story form error: " . $e->getMessage());
    $error = "Error loading form data. Please try again.";
}

// Page variables
$pageTitle = isset($_GET['id']) ? 'Edit Story' : 'Add Story';
$currentPage = 'stories';

// Add custom CSS and JS for form styling and rich text editor
$extraHeadContent = '
<!-- Include CKEditor and custom upload adapter -->
<script src="../assets/js/ckeditor.js"></script>
<script src="../assets/js/ckeditor-upload-adapter.js"></script>
<script src="../assets/js/simple-source-editing.js"></script>

<!-- Fallback to load CKEditor from CDN if local file fails -->
<script>
    // Check if CKEditor is loaded after a short delay
    setTimeout(function() {
        if (typeof ClassicEditor === "undefined") {
            console.log("Loading CKEditor from CDN as fallback...");
            var script = document.createElement("script");
            script.src = "https://cdn.ckeditor.com/ckeditor5/36.0.1/classic/ckeditor.js";
            script.onload = function() {
                console.log("CKEditor loaded from CDN successfully");
                // Trigger the initialization
                var event = new Event("DOMContentLoaded");
                document.dispatchEvent(event);
            };
            document.head.appendChild(script);
        }
    }, 500);
</script>

<!-- Loading overlay styles -->
<style>
    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 9999;
        display: none;
        justify-content: center;
        align-items: center;
        flex-direction: column;
        color: white;
    }

    .loading-overlay.active {
        display: flex;
    }

    .loading-spinner {
        width: 50px;
        height: 50px;
        border: 5px solid rgba(255, 255, 255, 0.3);
        border-radius: 50%;
        border-top-color: #fff;
        animation: spin 1s ease-in-out infinite;
        margin-bottom: 15px;
    }

    .loading-message {
        font-size: 18px;
        text-align: center;
        max-width: 80%;
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }
</style>

<style>
    /* Base form styles */
    .checkbox-group {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 10px;
        margin-top: 10px;
    }

    .checkbox-label {
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .form-section-title {
        margin-top: 20px;
        margin-bottom: 10px;
        font-size: 1.25rem;
        color: var(--gray-800);
        border-bottom: 1px solid var(--border-color);
        padding-bottom: 5px;
    }

    .checkbox-section {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 15px;
        margin-bottom: 20px;
        background-color: var(--gray-50);
        padding: 15px;
        border-radius: var(--radius-md);
        border: 1px solid var(--border-color);
    }

    .checkbox-group-item {
        margin-bottom: 0;
    }

    .content-form {
        background: white;
        padding: 20px;
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-sm);
    }

    /* WordPress-like layout */
    .wp-layout {
        display: grid;
        grid-template-columns: 1fr;
        gap: 20px;
    }

    @media (min-width: 992px) {
        .wp-layout {
            grid-template-columns: 2fr 1fr;
        }
    }

    .wp-layout-top {
        grid-column: 1 / -1;
        margin-bottom: 20px;
    }

    .wp-layout-main {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .wp-layout-sidebar {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .wp-card {
        background: white;
        border-radius: var(--radius-md);
        box-shadow: var(--shadow-sm);
        border: 1px solid var(--border-color);
        overflow: hidden;
    }

    .wp-card-header {
        padding: 12px 15px;
        border-bottom: 1px solid var(--border-color);
        background-color: var(--gray-50);
        font-weight: 600;
        color: var(--gray-800);
    }

    .wp-card-body {
        padding: 15px;
    }

    /* Sticky save bar */
    .sticky-save-bar {
        position: sticky;
        bottom: 0;
        left: 0;
        right: 0;
        background: white;
        padding: 15px 20px;
        box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
        z-index: 100;
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 20px;
        border-top: 1px solid var(--border-color);
    }

    .sticky-save-bar .btn-group {
        display: flex;
        gap: 10px;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .sticky-save-bar {
            flex-direction: column;
            gap: 10px;
        }

        .sticky-save-bar .btn-group {
            width: 100%;
        }

        .sticky-save-bar .btn {
            flex: 1;
        }
    }
</style>
';

// Include header
require_once '../includes/header.php';
?>

<div class="content-section mb-4">
    <div class="section-body">
        <form method="POST" action="save-story.php" class="content-form">
            <input type="hidden" name="id" value="<?php echo $story['id'] ?? ''; ?>">

            <!-- WordPress-like Layout -->
            <div class="wp-layout">
                <!-- Top Section (Title & Slug) -->
                <div class="wp-layout-top wp-card">
                    <div class="wp-card-header">
                        Basic Information
                    </div>
                    <div class="wp-card-body">
                        <div class="form-group">
                            <div class="row align-items-center">
                                <div class="col-md-2">
                                    <label class="form-label mb-md-0" for="title">Title <span class="required">*</span></label>
                                </div>
                                <div class="col-md-10">
                                    <input type="text" id="title" name="title" class="form-control" required
                                        value="<?php echo htmlspecialchars($story['title'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>

                        <div class="form-group mb-0">
                            <div class="row align-items-center">
                                <div class="col-md-2">
                                    <label class="form-label mb-md-0" for="slug">Slug <span class="required">*</span></label>
                                </div>
                                <div class="col-md-10">
                                    <input type="text" id="slug" name="slug" class="form-control" required
                                        value="<?php echo htmlspecialchars($story['slug'] ?? ''); ?>">
                                    <small class="form-text text-muted">URL-friendly version of the title (auto-generated if left empty)</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Content Column -->
                <div class="wp-layout-main">
                    <!-- Image Upload -->
                    <div class="wp-card">
                        <div class="wp-card-header">
                            Cover Image
                        </div>
                        <div class="wp-card-body">
                            <?php
                            // Render image upload component
                            renderImageUploadComponent(
                                'cover_url',
                                $story['cover_url'] ?? '',
                                'Cover Image',
                                'story',
                                $story['id'] ?? null
                            );

                            // Render AI image generator
                            if (function_exists('renderAiImageGenerator')) {
                                renderAiImageGenerator(
                                    'story',
                                    [
                                        'title' => $story['title'] ?? '',
                                        'excerpt' => $story['excerpt'] ?? '',
                                        'content' => $story['content'] ?? ''
                                    ],
                                    'cover_url',
                                    'cover_url_preview'
                                );
                            }
                            ?>
                        </div>
                    </div>

                    <!-- Story Content -->
                    <div class="wp-card">
                        <div class="wp-card-header">
                            Story Content
                        </div>
                        <div class="wp-card-body">
                            <?php
                            // Extract summary/excerpt and story content from markdown
                            $content = $story['content'] ?? '';
                            $summary = '';
                            $storyText = '';

                            // Extract author info
                            $authorName = '';
                            $authorAge = '';
                            $authorLocation = '';

                            if (!empty($content)) {
                                // Extract author name
                                if (preg_match('/\*\*Name:\*\*\s*(.*?)(?:\n|$)/i', $content, $nameMatch)) {
                                    $authorName = trim($nameMatch[1]);
                                }

                                // Extract author age
                                if (preg_match('/\*\*Age:\*\*\s*(.*?)(?:\n|$)/i', $content, $ageMatch)) {
                                    $authorAge = trim($ageMatch[1]);
                                }

                                // Extract author location
                                if (preg_match('/\*\*Location:\*\*\s*(.*?)(?:\n|$)/i', $content, $locationMatch)) {
                                    $authorLocation = trim($locationMatch[1]);
                                }

                                // Extract summary
                                if (preg_match('/## Summary\s*\n(.*?)(?:\n##|\n\*\*|\Z)/s', $content, $summaryMatch)) {
                                    $summary = trim($summaryMatch[1]);
                                }

                                // Extract story content - use a more robust pattern
                                if (preg_match('/## Story\s*\n(.*?)(?:\n##|\Z)/s', $content, $storyMatch)) {
                                    $storyText = trim($storyMatch[1]);
                                } else {
                                    // If we can't find the story section with the pattern, use everything after the summary
                                    // This is a fallback for stories that might not have the exact format
                                    if (preg_match('/## Summary.*?\n\n(.*)/s', $content, $fallbackMatch)) {
                                        $storyText = trim($fallbackMatch[1]);
                                    } else {
                                        // Last resort: use the entire content but strip markdown headers
                                        $storyText = preg_replace('/^##.*?\n/m', '', $content);
                                        $storyText = preg_replace('/\*\*.*?\*\*/m', '', $storyText);
                                    }
                                }

                                // Check if the content contains HTML tags
                                if (strpos($storyText, '<') !== false && strpos($storyText, '>') !== false) {
                                    // It's HTML content, we'll store it as is for direct output
                                    // Fix image URLs if they're relative or missing domain
                                    $storyHtml = preg_replace_callback(
                                        '/<img[^>]*src=["\']([^"\']+)["\'][^>]*>/i',
                                        function($matches) {
                                            $src = $matches[1];
                                            // If it's not an absolute URL, make it absolute
                                            if (strpos($src, 'http') !== 0) {
                                                $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'api.storiesfromtheweb.org';
                                                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
                                                $src = "$protocol://$host" . (strpos($src, '/') === 0 ? $src : "/$src");
                                            }
                                            return str_replace($matches[1], $src, $matches[0]);
                                        },
                                        $storyText
                                    );
                                } else {
                                    // It's plain text, we'll escape it when displaying
                                    $storyHtml = htmlspecialchars($storyText);
                                }

                                // Debug log to check what's happening with the content
                                error_log("Story content extracted. Length: " . strlen($storyText));
                                error_log("HTML content prepared. Length: " . strlen($storyHtml ?? ''));
                            }
                            ?>

                            <!-- Summary/Excerpt Field -->
                            <div class="form-group">
                                <label for="summary">Summary/Excerpt</label>
                                <textarea id="summary" name="summary" class="form-control" rows="3"><?php echo htmlspecialchars($summary); ?></textarea>
                            </div>

                            <!-- Story Content Field with WYSIWYG -->
                            <div class="form-group mb-0">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label for="story_content">Story</label>
                                    <button type="button" id="toggle-html-view" class="btn btn-sm btn-outline-secondary">
                                        <i class="fas fa-code"></i> Toggle HTML
                                    </button>
                                </div>
                                <textarea id="story_content" name="story_content" class="form-control rich-text-editor" rows="15"><?php echo isset($storyHtml) ? $storyHtml : (isset($storyText) ? htmlspecialchars($storyText) : ''); ?></textarea>
                                <textarea id="html_content" name="html_content" class="form-control" rows="15" style="display: none; font-family: monospace;"><?php echo isset($storyHtml) ? htmlspecialchars($storyHtml) : (isset($storyText) ? htmlspecialchars($storyText) : ''); ?></textarea>
                                <input type="hidden" id="content" name="content" value="">
                            </div>
                        </div>
                    </div>

                    <!-- Tags -->
                    <div class="wp-card">
                        <div class="wp-card-header">
                            Tags
                        </div>
                        <div class="wp-card-body">
                            <div class="checkbox-group">
                                <?php foreach ($tags as $tag): ?>
                                    <label class="checkbox-label">
                                        <input type="checkbox" name="tags[]" value="<?php echo $tag['id']; ?>"
                                            <?php echo in_array($tag['id'], $storyTags) ? 'checked' : ''; ?>>
                                        <?php echo htmlspecialchars($tag['name']); ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar Column -->
                <div class="wp-layout-sidebar">
                    <!-- Author Information -->
                    <div class="wp-card">
                        <div class="wp-card-header">
                            Author
                        </div>
                        <div class="wp-card-body">
                            <div class="form-group">
                                <select id="author_id" name="author_id" class="form-control" required>
                                    <option value="">Select Author</option>
                                    <?php foreach ($authors as $author): ?>
                                        <option value="<?php echo $author['id']; ?>"
                                                data-author-type="<?php echo htmlspecialchars($author['author_type']); ?>"
                                                <?php echo (isset($story['author_id']) && $story['author_id'] == $author['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($author['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <?php
                            // Get author details if we have an author ID
                            $authorDetails = null;
                            if (isset($story['author_id'])) {
                                $stmt = $db->prepare("SELECT name, age, location FROM authors WHERE id = ?");
                                $stmt->execute([$story['author_id']]);
                                $authorDetails = $stmt->fetch();
                            }
                            ?>

                            <!-- Author Age and Location Display -->
                            <div id="author-details" class="mt-3" style="<?php echo $authorDetails ? '' : 'display: none;'; ?>">
                                <div class="author-info-box">
                                    <div class="author-info-item">
                                        <span class="author-info-label">Age:</span>
                                        <span id="author-age" class="author-info-value"><?php echo $authorDetails ? htmlspecialchars($authorDetails['age']) : ''; ?></span>
                                    </div>
                                    <div class="author-info-item">
                                        <span class="author-info-label">Location:</span>
                                        <span id="author-location" class="author-info-value"><?php echo $authorDetails ? htmlspecialchars($authorDetails['location']) : ''; ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <style>
                        .author-info-box {
                            background-color: var(--gray-100);
                            border-radius: var(--radius-sm);
                            padding: 10px;
                            margin-top: 10px;
                        }
                        .author-info-item {
                            margin-bottom: 5px;
                        }
                        .author-info-label {
                            font-weight: 600;
                            margin-right: 5px;
                        }
                    </style>

                    <!-- Story Settings -->
                    <div class="wp-card">
                        <div class="wp-card-header">
                            Story Settings
                        </div>
                        <div class="wp-card-body">
                            <?php
                            // Source Type
                            if (in_array('source_type', $additionalFields)):
                            ?>
                            <div class="form-group">
                                <label class="form-label" for="source_type">Source Type</label>
                                <select id="source_type" name="source_type" class="form-control">
                                    <option value="child" <?php echo (($story['source_type'] ?? '') === 'child') ? 'selected' : ''; ?>>Child's Story</option>
                                    <option value="parent" <?php echo (($story['source_type'] ?? '') === 'parent') ? 'selected' : ''; ?>>Parent's Story</option>
                                    <option value="classic" <?php echo (($story['source_type'] ?? '') === 'classic') ? 'selected' : ''; ?>>Classic Story</option>
                                </select>
                            </div>
                            <?php endif; ?>

                            <?php
                            // Age Group
                            if (in_array('age_group', $additionalFields)):
                                // Get author's age if available
                                $authorAge = null;
                                if (isset($story['author_id'])) {
                                    $stmt = $db->prepare("SELECT age FROM authors WHERE id = ?");
                                    $stmt->execute([$story['author_id']]);
                                    $authorAge = $stmt->fetchColumn();
                                }

                                // Determine age group based on author's age
                                $ageGroup = '7-12'; // default
                                if ($authorAge !== null) {
                                    if ($authorAge <= 5) $ageGroup = '0-3';
                                    else if ($authorAge <= 8) $ageGroup = '4-6';
                                    else if ($authorAge <= 12) $ageGroup = '7-12';
                                    else $ageGroup = '13+';
                                }
                            ?>
                            <div class="form-group">
                                <label class="form-label" for="age_group">Age Group</label>
                                <select id="age_group" name="age_group" class="form-control" required>
                                    <option value="0-3" <?php echo ($ageGroup === '0-3') ? 'selected' : ''; ?>>0-3 years</option>
                                    <option value="4-6" <?php echo ($ageGroup === '4-6') ? 'selected' : ''; ?>>4-6 years</option>
                                    <option value="7-12" <?php echo ($ageGroup === '7-12') ? 'selected' : ''; ?>>7-12 years</option>
                                    <option value="13+" <?php echo ($ageGroup === '13+') ? 'selected' : ''; ?>>13+ years</option>
                                </select>
                                <small class="form-text text-muted">Auto-set based on author's age (<?php echo $authorAge ?? 'unknown'; ?> years old)</small>
                            </div>
                            <?php endif; ?>

                            <?php
                            // Reading Time
                            if (in_array('estimated_reading_time', $additionalFields)):
                                // Calculate reading time based on content
                                $wordCount = str_word_count(strip_tags($story['content'] ?? ''));
                                $readingTime = max(1, ceil($wordCount / 200)); // At least 1 minute
                            ?>
                            <div class="form-group">
                                <label class="form-label">Reading Time</label>
                                <div class="form-control-static">
                                    <?php echo $readingTime; ?> minute<?php echo $readingTime !== 1 ? 's' : ''; ?>
                                    <input type="hidden" name="estimated_reading_time" value="<?php echo $readingTime; ?>">
                                </div>
                                <small class="form-text text-muted">Automatically calculated based on content length</small>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Review Settings -->
                    <div class="wp-card">
                        <div class="wp-card-header">
                            Review Settings
                        </div>
                        <div class="wp-card-body">
                            <?php if (in_array('allow_reviews', $additionalFields)): ?>
                            <div class="form-group">
                                <div class="form-check">
                                    <input type="checkbox" id="allow_reviews" name="allow_reviews" class="form-check-input" value="1"
                                        <?php echo (isset($story['allow_reviews']) && $story['allow_reviews'] == 1) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="allow_reviews">Allow Reviews</label>
                                    <input type="hidden" name="allow_reviews_submitted" value="1">
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php if (in_array('average_rating', $additionalFields)): ?>
                            <div class="form-group">
                                <label class="form-label" for="average_rating">Average Rating</label>
                                <input type="number" id="average_rating" name="average_rating" class="form-control"
                                    min="0" max="5" step="0.1"
                                    value="<?php echo htmlspecialchars($story['average_rating'] ?? '0'); ?>">
                            </div>
                            <?php endif; ?>

                            <?php if (in_array('review_count', $additionalFields)): ?>
                            <div class="form-group mb-0">
                                <label class="form-label" for="review_count">Review Count</label>
                                <input type="number" id="review_count" name="review_count" class="form-control"
                                    min="0" step="1"
                                    value="<?php echo htmlspecialchars($story['review_count'] ?? '0'); ?>">
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Additional Fields -->
                    <div class="wp-card">
                        <div class="wp-card-header">
                            Additional Information
                        </div>
                        <div class="wp-card-body">
                            <?php
                            foreach ($additionalFields as $field):
                                // Skip fields we handle specially or already displayed
                                if (in_array($field, [
                                    'id', 'title', 'content', 'author_id', 'created_at', 'updated_at', 'slug',
                                    'source_type', 'allow_reviews', 'average_rating', 'estimated_reading_time',
                                    'age_group', 'review_count'
                                ])) {
                                    continue;
                                }

                                $columnData = $columnInfo[$field];
                                $isRequired = strpos($columnData['Type'], 'NOT NULL') !== false;
                                $isIntField = strpos($columnData['Type'], 'int') === 0;
                                $isDecimalField = strpos($columnData['Type'], 'decimal') === 0;
                                $label = ucwords(str_replace('_', ' ', $field));

                                // Check if this is a boolean field (tinyint(1))
                                $isBooleanField = $isIntField && strpos($columnData['Type'], 'tinyint(1)') !== false;

                                if ($isBooleanField):
                            ?>
                                <div class="form-group">
                                    <div class="form-check">
                                        <input type="checkbox" id="<?php echo $field; ?>" name="<?php echo $field; ?>" class="form-check-input"
                                            value="1" <?php echo (isset($story[$field]) && $story[$field] == 1) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="<?php echo $field; ?>"><?php echo $label; ?></label>
                                        <input type="hidden" name="<?php echo $field; ?>_submitted" value="1">
                                    </div>
                                </div>
                            <?php elseif ($isIntField || $isDecimalField): ?>
                                <div class="form-group">
                                    <label class="form-label" for="<?php echo $field; ?>"><?php echo $label; ?></label>
                                    <input type="number" id="<?php echo $field; ?>" name="<?php echo $field; ?>" class="form-control"
                                        value="<?php echo htmlspecialchars($story[$field] ?? '0'); ?>"
                                        <?php echo $isDecimalField ? 'step="0.01"' : ''; ?>
                                        <?php echo $isRequired ? 'required' : ''; ?>>
                                </div>
                            <?php else: ?>
                                <div class="form-group">
                                    <label class="form-label" for="<?php echo $field; ?>"><?php echo $label; ?></label>
                                    <input type="text" id="<?php echo $field; ?>" name="<?php echo $field; ?>" class="form-control"
                                        value="<?php echo htmlspecialchars($story[$field] ?? ''); ?>"
                                        <?php echo $isRequired ? 'required' : ''; ?>>
                                </div>
                            <?php endif; endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sticky Save Bar -->
            <div class="sticky-save-bar">
                <div class="story-status">
                    <?php if (isset($story['id'])): ?>
                    <span class="text-muted">Last updated: <?php echo date('M j, Y g:i a', strtotime($story['updated_at'] ?? 'now')); ?></span>
                    <?php else: ?>
                    <span class="text-muted">Creating new story</span>
                    <?php endif; ?>
                </div>
                <div class="btn-group">
                    <a href="stories.php" class="btn btn-secondary">Cancel</a>
                    <button type="button" id="preview-story" class="btn btn-info">Preview</button>
                    <button type="submit" class="btn btn-primary">Save Story</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    // Function to update source type based on author selection
    function updateSourceTypeFromAuthor() {
        const authorSelect = document.getElementById('author_id');
        const sourceTypeSelect = document.getElementById('source_type');

        if (!authorSelect || !sourceTypeSelect) {
            console.error('Required elements not found');
            return;
        }

        if (authorSelect.selectedIndex > 0) {
            const selectedOption = authorSelect.options[authorSelect.selectedIndex];
            const authorType = selectedOption.getAttribute('data-author-type');

            // Map author type to source type
            let sourceType;
            switch (authorType) {
                case 'child':
                    sourceType = 'child';
                    break;
                case 'parent':
                    sourceType = 'parent';
                    break;
                case 'retail':
                case 'educator':
                default:
                    sourceType = 'classic';
                    break;
            }

            // Set the source type and disable the dropdown
            sourceTypeSelect.value = sourceType;
            sourceTypeSelect.disabled = true;

            // Update the allow reviews visibility
            handleSourceTypeChange();
        } else {
            // Enable the dropdown if no author is selected
            sourceTypeSelect.disabled = false;
        }
    }

    // Function to handle source_type changes
    function handleSourceTypeChange() {
        const sourceTypeSelect = document.getElementById('source_type');
        const allowReviewsCheckbox = document.getElementById('allow_reviews');

        if (!sourceTypeSelect || !allowReviewsCheckbox) {
            console.error('Required elements not found');
            return;
        }

        const sourceType = sourceTypeSelect.value;
        const allowReviewsLabel = allowReviewsCheckbox.closest('.form-group');

        console.log('Source type changed to:', sourceType);

        // Find all review/rating related fields
        const reviewFields = [
            document.getElementById('allow_reviews'),
            document.getElementById('average_rating'),
            document.getElementById('review_count')
        ];

        // Find the containers for these fields
        const reviewFieldContainers = reviewFields
            .filter(field => field !== null)
            .map(field => field.closest('.form-group'));

        if (sourceType === 'child') {
            // Children's stories never get reviews - disable all review fields
            reviewFields.forEach(field => {
                if (field) {
                    if (field.type === 'checkbox') {
                        field.checked = false;
                    } else if (field.type === 'number') {
                        field.value = '0';
                    }
                    field.disabled = true;
                }
            });

            // Make all review field containers appear disabled
            reviewFieldContainers.forEach(container => {
                if (container) {
                    container.style.opacity = '0.5';
                    container.title = 'Children\'s stories never get reviews';
                }
            });

            // Also disable the average_rating slider if it exists
            const ratingSlider = document.getElementById('average_rating_slider');
            if (ratingSlider) {
                ratingSlider.disabled = true;
            }
        } else if (sourceType === 'classic') {
            // Classic works always get reviews
            if (allowReviewsCheckbox) {
                allowReviewsCheckbox.checked = true;
                allowReviewsCheckbox.disabled = true;
            }
            if (allowReviewsLabel) {
                allowReviewsLabel.style.opacity = '0.5';
                allowReviewsLabel.title = 'Classic works always get reviews';
            }

            // Enable other review fields
            reviewFields.slice(1).forEach(field => {
                if (field) {
                    field.disabled = false;
                }
            });

            // Make other review field containers appear enabled
            reviewFieldContainers.slice(1).forEach(container => {
                if (container) {
                    container.style.opacity = '1';
                    container.title = '';
                }
            });

            // Enable the average_rating slider if it exists
            const ratingSlider = document.getElementById('average_rating_slider');
            if (ratingSlider) {
                ratingSlider.disabled = false;
            }
        } else {
            // Parent stories can choose
            reviewFields.forEach(field => {
                if (field) {
                    field.disabled = false;
                }
            });

            // Make all review field containers appear enabled
            reviewFieldContainers.forEach(container => {
                if (container) {
                    container.style.opacity = '1';
                    container.title = '';
                }
            });

            // Enable the average_rating slider if it exists
            const ratingSlider = document.getElementById('average_rating_slider');
            if (ratingSlider) {
                ratingSlider.disabled = false;
            }
        }
    }

    // Function to update author details when author selection changes
    function updateAuthorDetails() {
        const authorSelect = document.getElementById('author_id');
        const authorDetailsDiv = document.getElementById('author-details');
        const authorAgeSpan = document.getElementById('author-age');
        const authorLocationSpan = document.getElementById('author-location');

        if (!authorSelect || !authorDetailsDiv || !authorAgeSpan || !authorLocationSpan) {
            console.error('Required author detail elements not found');
            return;
        }

        if (authorSelect.selectedIndex > 0) {
            // Get the selected author ID
            const authorId = authorSelect.value;

            // Fetch author details via AJAX
            fetch(`../handlers/get-author-details.php?id=${authorId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update the author details
                        authorAgeSpan.textContent = data.age || 'Not specified';
                        authorLocationSpan.textContent = data.location || 'Not specified';

                        // Show the author details div
                        authorDetailsDiv.style.display = 'block';
                    } else {
                        console.error('Error fetching author details:', data.message);
                        authorDetailsDiv.style.display = 'none';
                    }
                })
                .catch(error => {
                    console.error('Error fetching author details:', error);
                    authorDetailsDiv.style.display = 'none';
                });
        } else {
            // Hide the author details div if no author is selected
            authorDetailsDiv.style.display = 'none';
        }
    }

    // Run when DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {
        const sourceTypeSelect = document.getElementById('source_type');
        const authorSelect = document.getElementById('author_id');

        if (sourceTypeSelect) {
            // Set initial state
            handleSourceTypeChange();

            // Add event listener for changes
            sourceTypeSelect.addEventListener('change', handleSourceTypeChange);
        }

        if (authorSelect) {
            // Set initial state
            updateSourceTypeFromAuthor();

            // Add event listener for changes
            authorSelect.addEventListener('change', function() {
                updateSourceTypeFromAuthor();
                updateAuthorDetails();
            });
        }
    });
</script>

<script>
    // Auto-generate slug from title
    document.addEventListener('DOMContentLoaded', function() {
        const titleInput = document.getElementById('title');
        const slugInput = document.getElementById('slug');

        if (titleInput && slugInput) {
            titleInput.addEventListener('input', function() {
                // Only auto-generate if slug is empty or hasn't been manually edited
                if (!slugInput.value || slugInput._autoGenerated) {
                    const slug = titleInput.value
                        .toLowerCase()
                        .replace(/[^\w\s-]/g, '') // Remove special characters
                        .replace(/\s+/g, '-')     // Replace spaces with hyphens
                        .replace(/-+/g, '-');     // Replace multiple hyphens with single hyphen

                    slugInput.value = slug;
                    slugInput._autoGenerated = true;
                }
            });

            // Mark when user manually edits the slug
            slugInput.addEventListener('input', function() {
                slugInput._autoGenerated = false;
            });
        }
    });
</script>

<!-- Include image upload script -->
<script src="../assets/js/image-upload.js"></script>

<!-- Initialize CKEditor for rich text editing -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM loaded, checking for CKEditor...');

        // Check if CKEditor script is loaded
        if (typeof ClassicEditor === 'undefined') {
            console.error('CKEditor is not loaded. Adding fallback editor...');

            // Create a fallback basic editor
            const storyContentTextarea = document.getElementById('story_content');
            const htmlContentTextarea = document.getElementById('html_content');
            const toggleHtmlButton = document.getElementById('toggle-html-view');

            if (storyContentTextarea) {
                // Make the textarea visible and styled
                storyContentTextarea.style.display = 'block';
                storyContentTextarea.style.minHeight = '300px';
                storyContentTextarea.style.width = '100%';
                storyContentTextarea.style.padding = '10px';
                storyContentTextarea.style.fontFamily = 'inherit';
                storyContentTextarea.style.fontSize = 'inherit';
                storyContentTextarea.style.lineHeight = '1.5';

                // Set up the HTML toggle button for the fallback
                if (toggleHtmlButton && htmlContentTextarea) {
                    let isHtmlMode = false;
                    toggleHtmlButton.addEventListener('click', () => {
                        if (!isHtmlMode) {
                            // Switch to HTML mode
                            htmlContentTextarea.value = storyContentTextarea.value;
                            htmlContentTextarea.style.display = 'block';
                            storyContentTextarea.style.display = 'none';
                        } else {
                            // Switch back to normal mode
                            storyContentTextarea.value = htmlContentTextarea.value;
                            htmlContentTextarea.style.display = 'none';
                            storyContentTextarea.style.display = 'block';
                        }
                        isHtmlMode = !isHtmlMode;
                    });
                }
            }
        } else {
            console.log('CKEditor found, initializing...');

            // Initialize CKEditor for the story content
            ClassicEditor
                .create(document.querySelector('#story_content'), {
                    toolbar: [
                        'heading', '|',
                        'bold', 'italic', 'link', '|',
                        'bulletedList', 'numberedList', '|',
                        'insertTable', '|',
                        'imageUpload', 'mediaEmbed', '|',
                        'sourceEditing', '|',
                        'undo', 'redo'
                    ],
                    heading: {
                        options: [
                            { model: 'paragraph', title: 'Paragraph', class: 'ck-heading_paragraph' },
                            { model: 'heading2', view: 'h2', title: 'Heading 2', class: 'ck-heading_heading2' },
                            { model: 'heading3', view: 'h3', title: 'Heading 3', class: 'ck-heading_heading3' }
                        ]
                    },
                    image: {
                        toolbar: [
                            'imageStyle:inline',
                            'imageStyle:block',
                            'imageStyle:side',
                            '|',
                            'toggleImageCaption',
                            'imageTextAlternative'
                        ]
                    },
                    // Register the custom upload adapter plugin
                    extraPlugins: [MediaLibraryUploadAdapterPlugin]
                })
                .then(editor => {
                    console.log('CKEditor initialized successfully');

                    // Store editor instance
                    window.storyEditor = editor;

                    // Set up the HTML toggle button
                    const toggleHtmlButton = document.getElementById('toggle-html-view');
                    const htmlContentTextarea = document.getElementById('html_content');
                    let isHtmlMode = false;

                    if (toggleHtmlButton && htmlContentTextarea) {
                        toggleHtmlButton.addEventListener('click', () => {
                            if (!isHtmlMode) {
                                // Switch to HTML mode
                                htmlContentTextarea.value = editor.getData();
                                htmlContentTextarea.style.display = 'block';

                                // Get the CKEditor root element and hide it
                                const editorRoot = editor.ui.getEditableElement().parentElement;
                                if (editorRoot) {
                                    editorRoot.style.display = 'none';
                                }
                            } else {
                                // Switch back to WYSIWYG mode
                                editor.setData(htmlContentTextarea.value);
                                htmlContentTextarea.style.display = 'none';

                                // Show the CKEditor root element again
                                const editorRoot = editor.ui.getEditableElement().parentElement;
                                if (editorRoot) {
                                    editorRoot.style.display = '';
                                }
                            }
                            isHtmlMode = !isHtmlMode;
                        });
                    }
                })
                .catch(error => {
                    console.error('Error initializing CKEditor:', error);

                    // Fallback to basic textarea if CKEditor fails to initialize
                    const storyContentTextarea = document.getElementById('story_content');
                    if (storyContentTextarea) {
                        storyContentTextarea.style.display = 'block';
                        storyContentTextarea.style.minHeight = '300px';
                        storyContentTextarea.style.width = '100%';
                    }
                });
        }

        // Create loading overlay
        function createLoadingOverlay() {
            // Check if overlay already exists
            if (!document.getElementById('loading-overlay')) {
                const overlay = document.createElement('div');
                overlay.id = 'loading-overlay';
                overlay.className = 'loading-overlay';

                const spinner = document.createElement('div');
                spinner.className = 'loading-spinner';
                overlay.appendChild(spinner);

                const message = document.createElement('div');
                message.className = 'loading-message';
                message.textContent = 'Saving story...';
                overlay.appendChild(message);

                document.body.appendChild(overlay);
            }

            return document.getElementById('loading-overlay');
        }

        // Show loading overlay
        function showLoadingOverlay(message = 'Saving story...') {
            const overlay = createLoadingOverlay();
            const messageEl = overlay.querySelector('.loading-message');
            if (messageEl) {
                messageEl.textContent = message;
            }
            overlay.classList.add('active');
        }

        // Hide loading overlay
        function hideLoadingOverlay() {
            const overlay = document.getElementById('loading-overlay');
            if (overlay) {
                overlay.classList.remove('active');
            }
        }

        // Handle form submission to format content properly
        document.querySelector('form.content-form').addEventListener('submit', function(e) {
            // Show loading overlay
            showLoadingOverlay('Saving story and processing images...');

            // Get the summary
            const summary = document.querySelector('#summary').value;

            // Get the story content - check if we're in HTML mode or WYSIWYG mode
            let storyContent = '';
            const htmlContentTextarea = document.getElementById('html_content');
            const storyContentTextarea = document.getElementById('story_content');

            if (htmlContentTextarea && htmlContentTextarea.style.display !== 'none') {
                // We're in HTML mode, get content from the HTML textarea
                storyContent = htmlContentTextarea.value;
            } else if (window.storyEditor) {
                // We're in WYSIWYG mode, get content from CKEditor
                storyContent = window.storyEditor.getData();
            } else if (storyContentTextarea && storyContentTextarea.style.display !== 'none') {
                // We're using the fallback textarea
                storyContent = storyContentTextarea.value;
            }

            // Process images in the content to ensure they have proper URLs
            storyContent = processContentImages(storyContent);

            // Combine the summary and story content into the final content format
            let finalContent = '';

            if (summary) {
                finalContent += '## Summary\n\n' + summary + '\n\n';
            }

            finalContent += '## Story\n\n' + storyContent;

            // Set the hidden content field with the final formatted content
            document.getElementById('content').value = finalContent;
        });

        // Function to process images in content to ensure they have absolute URLs
        function processContentImages(content) {
            // If the content is empty, return as is
            if (!content) return content;

            // Check if the content contains HTML
            if (content.indexOf('<img') === -1) return content;

            // Create a temporary div to parse the HTML
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = content;

            // Find all images in the content
            const images = tempDiv.querySelectorAll('img');

            // Process each image
            images.forEach(img => {
                let src = img.getAttribute('src');

                // If the src is not an absolute URL, make it absolute
                if (src && src.indexOf('http') !== 0) {
                    const host = window.location.host || 'api.storiesfromtheweb.org';
                    const protocol = window.location.protocol || 'https:';
                    src = `${protocol}//${host}${src.startsWith('/') ? '' : '/'}${src}`;
                    img.setAttribute('src', src);
                }
            });

            // Return the processed content
            return tempDiv.innerHTML;
        }

        // Handle form submission to format content properly
        document.querySelector('form.content-form').addEventListener('submit', function(e) {
            // Show loading overlay
            showLoadingOverlay('Saving story and processing images...');

            // Get the summary
            const summary = document.querySelector('#summary').value;

            // Get the story content - check if we're in HTML mode or WYSIWYG mode
            let storyContent = '';
            const htmlContentTextarea = document.getElementById('html_content');
            const storyContentTextarea = document.getElementById('story_content');

            if (htmlContentTextarea && htmlContentTextarea.style.display !== 'none') {
                // We're in HTML mode, get content from the HTML textarea
                storyContent = htmlContentTextarea.value;
            } else if (window.storyEditor) {
                // We're in WYSIWYG mode, get content from CKEditor
                storyContent = window.storyEditor.getData();
            } else if (storyContentTextarea && storyContentTextarea.style.display !== 'none') {
                // We're using the fallback textarea
                storyContent = storyContentTextarea.value;
            }

            // Process images in the content
            storyContent = processImagesInContent(storyContent);

            // Get author info if available
            const authorSelect = document.querySelector('#author_id');
            let authorName = '';
            let authorAge = '';
            let authorLocation = '';

            // Try to get author info from the form or from extracted data
            if (authorSelect && authorSelect.selectedIndex > 0) {
                const selectedOption = authorSelect.options[authorSelect.selectedIndex];
                authorName = selectedOption.text || '<?php echo addslashes($authorName); ?>';

                // Try to get author age and location from the displayed info
                const authorAgeSpan = document.getElementById('author-age');
                const authorLocationSpan = document.getElementById('author-location');

                if (authorAgeSpan) {
                    authorAge = authorAgeSpan.textContent;
                }

                if (authorLocationSpan) {
                    authorLocation = authorLocationSpan.textContent;
                }
            }

            // Format the content in markdown format
            let formattedContent = '';

            // Add author section if we have author info
            if (authorName) {
                formattedContent += '## Author\n\n';
                formattedContent += '**Name:** ' + authorName + '\n';

                if (authorAge) {
                    formattedContent += '**Age:** ' + authorAge + '\n';
                }

                if (authorLocation) {
                    formattedContent += '**Location:** ' + authorLocation + '\n';
                }

                formattedContent += '\n';
            }

            // Add summary section
            if (summary) {
                formattedContent += '## Summary\n\n' + summary + '\n\n';
            }

            // Add story section with the HTML content directly
            formattedContent += '## Story\n\n' + storyContent;

            // Set the hidden content field value
            document.querySelector('#content').value = formattedContent;

            // Add a small delay to ensure the form is submitted with the updated content
            setTimeout(() => {
                // If the form hasn't been submitted yet (due to validation errors), hide the overlay
                if (!this.classList.contains('submitted')) {
                    hideLoadingOverlay();
                }
            }, 500);

            // Mark the form as submitted
            this.classList.add('submitted');
        });

        // Handle preview button click
        const previewButton = document.getElementById('preview-story');
        if (previewButton) {
            previewButton.addEventListener('click', function() {
                // Get the story content - check if we're in HTML mode or WYSIWYG mode
                let storyContent = '';
                const htmlContentTextarea = document.getElementById('html_content');
                const storyContentTextarea = document.getElementById('story_content');

                if (htmlContentTextarea && htmlContentTextarea.style.display !== 'none') {
                    // We're in HTML mode, get content from the HTML textarea
                    storyContent = htmlContentTextarea.value;
                } else if (window.storyEditor) {
                    // We're in WYSIWYG mode, get content from CKEditor
                    storyContent = window.storyEditor.getData();
                } else if (storyContentTextarea && storyContentTextarea.style.display !== 'none') {
                    // We're using the fallback textarea
                    storyContent = storyContentTextarea.value;
                }

                // Process images in the content to ensure they have proper URLs
                storyContent = processImagesInContent(storyContent);

                // Get the title
                const title = document.getElementById('title').value || 'Preview';

                // Get the summary
                const summary = document.getElementById('summary').value || '';

                // Get the cover image
                const coverUrlInput = document.querySelector('.image-url-input');
                let coverUrl = '';

                if (coverUrlInput) {
                    coverUrl = coverUrlInput.value || '';

                    // Make sure the cover URL is absolute
                    if (coverUrl && coverUrl.indexOf('http') !== 0) {
                        const host = window.location.host || 'api.storiesfromtheweb.org';
                        const protocol = window.location.protocol || 'https:';
                        coverUrl = `${protocol}//${host}${coverUrl.startsWith('/') ? '' : '/'}${coverUrl}`;
                    }
                }

                // Create a form to post the data
                const form = document.createElement('form');
                form.method = 'post';
                form.action = 'preview-story.php';
                form.target = '_blank';

                // Add the title
                const titleInput = document.createElement('input');
                titleInput.type = 'hidden';
                titleInput.name = 'title';
                titleInput.value = title;
                form.appendChild(titleInput);

                // Add the summary
                const summaryInput = document.createElement('input');
                summaryInput.type = 'hidden';
                summaryInput.name = 'summary';
                summaryInput.value = summary;
                form.appendChild(summaryInput);

                // Add the cover image
                const coverInput = document.createElement('input');
                coverInput.type = 'hidden';
                coverInput.name = 'cover_url';
                coverInput.value = coverUrl;
                form.appendChild(coverInput);

                // Add the content
                const contentInput = document.createElement('input');
                contentInput.type = 'hidden';
                contentInput.name = 'content';
                contentInput.value = storyContent;
                form.appendChild(contentInput);

                // Submit the form
                document.body.appendChild(form);
                form.submit();
                document.body.removeChild(form);
            });
        }

        // Function to process images in HTML content
        function processImagesInContent(content) {
            // If content is empty or not a string, return as is
            if (!content || typeof content !== 'string') {
                console.warn('Invalid content passed to processImagesInContent:', content);
                return content || '';
            }

            // Create a temporary div to parse the HTML
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = content;

            // Process all images to ensure they have absolute URLs
            let imgElements = tempDiv.querySelectorAll('img');
            imgElements.forEach(img => {
                let src = img.getAttribute('src');
                if (src && src.indexOf('http') !== 0) {
                    // Convert to absolute URL if it's not already
                    const host = window.location.host || 'api.storiesfromtheweb.org';
                    const protocol = window.location.protocol || 'https:';
                    src = `${protocol}//${host}${src.startsWith('/') ? '' : '/'}${src}`;
                    img.setAttribute('src', src);
                }
            });

            // Find all figures with empty images
            const emptyFigures = Array.from(tempDiv.querySelectorAll('figure.image')).filter(figure => {
                const img = figure.querySelector('img');
                return !img || !img.src || img.src === 'about:blank' || img.src === 'null' || img.src === 'undefined' || img.src.trim() === '';
            });

            // Remove empty figures
            emptyFigures.forEach(figure => figure.remove());

            // Re-select all images after removing empty figures
            imgElements = tempDiv.querySelectorAll('img');

            // Process each image
            imgElements.forEach(img => {
                // Check if the image has a valid src attribute
                if (!img.src || img.src === 'about:blank' || img.src === 'null' || img.src === 'undefined' || img.src.trim() === '') {
                    // If the image has no valid src, remove it or its parent figure
                    const figure = img.closest('figure');
                    if (figure) {
                        figure.remove();
                    } else {
                        img.remove();
                    }
                    return;
                }

                // Make sure it's an absolute URL
                if (img.src.indexOf('http') !== 0) {
                    const host = window.location.host || 'api.storiesfromtheweb.org';
                    const protocol = window.location.protocol || 'https:';
                    img.src = `${protocol}//${host}${img.src.startsWith('/') ? '' : '/'}${img.src}`;
                }

                // Make sure it has an alt attribute
                if (!img.alt) {
                    img.alt = 'Story image';
                }
            });

            // Return the processed HTML
            return tempDiv.innerHTML;
        }

        // Set up the preview button
        const previewButton = document.getElementById('preview-story');
        if (previewButton) {
            previewButton.addEventListener('click', function() {
                // Create a preview modal if it doesn't exist
                let previewModal = document.getElementById('story-preview-modal');
                if (!previewModal) {
                    previewModal = document.createElement('div');
                    previewModal.id = 'story-preview-modal';
                    previewModal.className = 'modal fade';
                    previewModal.setAttribute('tabindex', '-1');
                    previewModal.setAttribute('role', 'dialog');
                    previewModal.setAttribute('aria-labelledby', 'previewModalLabel');
                    previewModal.setAttribute('aria-hidden', 'true');

                    previewModal.innerHTML = `
                        <div class="modal-dialog modal-xl" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="previewModalLabel">Story Preview</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <iframe id="preview-iframe" style="width: 100%; height: 600px; border: none;"></iframe>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    `;

                    document.body.appendChild(previewModal);
                }

                // Get the content for preview
                let storyContent = '';
                const htmlContentTextarea = document.getElementById('html_content');
                const title = document.getElementById('title').value || 'Story Preview';
                const summary = document.getElementById('summary').value || '';

                if (htmlContentTextarea && htmlContentTextarea.style.display !== 'none') {
                    // We're in HTML mode, get content from the HTML textarea
                    storyContent = htmlContentTextarea.value;
                } else if (window.storyEditor) {
                    // We're in WYSIWYG mode, get content from CKEditor
                    storyContent = window.storyEditor.getData();
                }

                // Process images in the content
                storyContent = processImagesInContent(storyContent);

                // Get author info if available
                const authorSelect = document.querySelector('#author_id');
                let authorName = '';
                let authorAge = '';
                let authorLocation = '';

                if (authorSelect && authorSelect.selectedIndex > 0) {
                    const selectedOption = authorSelect.options[authorSelect.selectedIndex];
                    authorName = selectedOption.text || '';

                    // Try to get author age and location from the displayed info
                    const authorAgeSpan = document.getElementById('author-age');
                    const authorLocationSpan = document.getElementById('author-location');

                    if (authorAgeSpan) {
                        authorAge = authorAgeSpan.textContent;
                    }

                    if (authorLocationSpan) {
                        authorLocation = authorLocationSpan.textContent;
                    }
                }

                // Create a preview HTML document
                const previewHtml = `
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <title>${title}</title>
                        <meta charset="UTF-8">
                        <meta name="viewport" content="width=device-width, initial-scale=1.0">
                        <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
                        <style>
                            body {
                                font-family: Arial, sans-serif;
                                line-height: 1.6;
                                color: #333;
                                max-width: 800px;
                                margin: 0 auto;
                                padding: 20px;
                            }
                            h1 {
                                font-size: 2.2em;
                                margin-bottom: 0.5em;
                            }
                            .author-info {
                                margin-bottom: 1.5em;
                                padding: 10px;
                                background-color: #f8f9fa;
                                border-radius: 5px;
                            }
                            .author-info p {
                                margin: 0.3em 0;
                            }
                            .summary {
                                font-style: italic;
                                margin-bottom: 2em;
                                color: #555;
                                padding: 10px;
                                background-color: #f8f9fa;
                                border-left: 3px solid #007bff;
                            }
                            .story-content {
                                margin-top: 2em;
                            }
                            .story-content img {
                                max-width: 100%;
                                height: auto;
                                margin: 1em 0;
                            }
                            .story-content figure {
                                margin: 1.5em 0;
                            }
                            .story-content figcaption {
                                font-size: 0.9em;
                                color: #666;
                                text-align: center;
                            }
                        </style>
                    </head>
                    <body>
                        <h1>${title}</h1>

                        ${authorName ? `
                        <div class="author-info">
                            <p><strong>Author:</strong> ${authorName}</p>
                            ${authorAge && authorAge !== 'Not specified' ? `<p><strong>Age:</strong> ${authorAge}</p>` : ''}
                            ${authorLocation && authorLocation !== 'Not specified' ? `<p><strong>Location:</strong> ${authorLocation}</p>` : ''}
                        </div>
                        ` : ''}

                        ${summary ? `<div class="summary">${summary}</div>` : ''}

                        <div class="story-content">
                            ${storyContent}
                        </div>
                    </body>
                    </html>
                `;

                // Get the iframe and set its content
                const previewIframe = document.getElementById('preview-iframe');
                if (previewIframe) {
                    // Show the modal
                    $(previewModal).modal('show');

                    // Set the iframe content after the modal is shown
                    setTimeout(() => {
                        const iframeDoc = previewIframe.contentDocument || previewIframe.contentWindow.document;
                        iframeDoc.open();
                        iframeDoc.write(previewHtml);
                        iframeDoc.close();
                    }, 300);
                }
            });
        }
    });
</script>

<?php
// Include footer
include_once '../includes/footer.php';
?>
