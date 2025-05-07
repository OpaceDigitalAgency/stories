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

// Add custom CSS for form styling
$extraHeadContent = '
<style>
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
</style>
';

// Include header
require_once '../includes/header.php';
?>

<div class="content-section mb-4">
    <div class="section-header">
        <h2 class="section-title"><?php echo $pageTitle; ?></h2>
    </div>
    <div class="section-body">
        <form method="POST" action="save-story.php" class="content-form">
            <input type="hidden" name="id" value="<?php echo $story['id'] ?? ''; ?>">

            <!-- Basic Information -->
            <div class="form-section-title">Basic Information</div>

            <div class="form-group">
                <label class="form-label" for="title">Title <span class="required">*</span></label>
                <input type="text" id="title" name="title" class="form-control" required
                       value="<?php echo htmlspecialchars($story['title'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="slug">Slug <span class="required">*</span></label>
                <input type="text" id="slug" name="slug" class="form-control" required
                       value="<?php echo htmlspecialchars($story['slug'] ?? ''); ?>">
                <small class="form-text text-muted">URL-friendly version of the title (auto-generated if left empty)</small>
            </div>

            <div class="form-group">
                <label class="form-label" for="author_id">Author <span class="required">*</span></label>
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

            <!-- Content -->
            <div class="form-section-title">Content</div>

            <div class="form-group">
                <label class="form-label" for="content">Story Content <span class="required">*</span></label>
                <textarea id="content" name="content" class="form-control" rows="10" required><?php echo htmlspecialchars($story['content'] ?? ''); ?></textarea>
            </div>

            <!-- Image Upload -->
            <div class="form-section-title">Image</div>

            <?php
            // Render image upload component
            renderImageUploadComponent(
                'cover_url',
                $story['cover_url'] ?? '',
                'Cover URL',
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

            <!-- Additional Fields -->
            <div class="form-section-title">Additional Information</div>

            <?php
            foreach ($additionalFields as $field):
                // Skip fields we handle specially
                if (in_array($field, ['id', 'title', 'content', 'author_id', 'created_at', 'updated_at', 'slug'])) {
                    continue;
                }

                $columnData = $columnInfo[$field];
                $isRequired = strpos($columnData['Type'], 'NOT NULL') !== false;
                $isIntField = strpos($columnData['Type'], 'int') === 0;
                $isDecimalField = strpos($columnData['Type'], 'decimal') === 0;
                $label = ucwords(str_replace('_', ' ', $field));

                // Special handling for certain fields
                if ($field === 'source_type'):
            ?>
                    <div class="form-group">
                        <label class="form-label" for="source_type">Source Type</label>
                        <select id="source_type" name="source_type" class="form-control">
                            <option value="child" <?php echo (($story['source_type'] ?? '') === 'child') ? 'selected' : ''; ?>>Child's Story</option>
                            <option value="parent" <?php echo (($story['source_type'] ?? '') === 'parent') ? 'selected' : ''; ?>>Parent's Story</option>
                            <option value="classic" <?php echo (($story['source_type'] ?? '') === 'classic') ? 'selected' : ''; ?>>Classic Story</option>
                        </select>
                    </div>
                <?php elseif ($field === 'allow_reviews'): ?>
                    <div class="form-group">
                        <div class="form-check">
                            <input type="checkbox" id="allow_reviews" name="allow_reviews" class="form-check-input"
                                   <?php echo (!empty($story['allow_reviews'])) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="allow_reviews">Allow Reviews</label>
                        </div>
                    </div>
                <?php elseif ($field === 'average_rating'): ?>
                    <div class="form-group">
                        <label class="form-label" for="average_rating">Average Rating</label>
                        <input type="number" id="average_rating" name="average_rating" class="form-control"
                               min="0" max="5" step="0.1"
                               value="<?php echo htmlspecialchars($story['average_rating'] ?? '0'); ?>">
                    </div>
                <?php elseif ($field === 'estimated_reading_time'): ?>
                    <?php
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
                        <small class="form-text text-muted">Automatically calculated based on content length (minimum 1 minute)</small>
                    </div>
                <?php elseif ($field === 'age_group'): ?>
                    <?php
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
                <?php elseif ($isIntField || $isDecimalField): ?>
                    <div class="form-group">
                        <label class="form-label" for="<?php echo $field; ?>"><?php echo $label; ?></label>
                        <input type="number" id="<?php echo $field; ?>" name="<?php echo $field; ?>" class="form-control"
                               value="<?php echo htmlspecialchars($story[$field] ?? ''); ?>"
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

            <!-- Tags section moved to the bottom -->
            <div class="form-group">
                <label class="form-label">Tags</label>
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

            <div class="form-group">
                <button type="submit" class="btn btn-primary">Save Story</button>
                <a href="stories.php" class="btn btn-secondary">Cancel</a>
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
            authorSelect.addEventListener('change', updateSourceTypeFromAuthor);
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

<?php
// Include footer
include_once '../includes/footer.php';
?>
