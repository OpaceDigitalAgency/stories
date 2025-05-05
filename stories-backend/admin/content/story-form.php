<?php
/**
 * Story Form Page
 *
 * This page displays a form for adding or editing a story.
 */

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include simple_auth.php directly
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
$user = SimpleAuth::check();
if (!$user) {
    // Redirect to login
    header("Location: ../login.php");
    exit;
}

// Include database connection
require_once '../includes/db-connect.php';

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
        if (!in_array($column['Field'], ['id', 'title', 'content', 'author_id', 'created_at', 'updated_at'])) {
            $additionalFields[] = $column['Field'];
        }
    }

} catch (PDOException $e) {
    error_log("Story form error: " . $e->getMessage());
    $error = "Error loading form data. Please try again.";
}
// Set page variables for header
$pageTitle = ($story ? 'Edit' : 'Add') . ' Story';
$currentPage = 'stories';
$pageDescription = '<a href="stories.php" class="text-primary">← Back to Stories</a>';

// Add custom CSS for form styling
$extraHeadContent = '
<style>
    .checkbox-group {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 10px;
    }

    .checkbox-label {
        display: flex;
        align-items: center;
        gap: 5px;
        padding: 5px 10px;
        background-color: var(--gray-100);
        border-radius: var(--radius-sm);
        font-size: 0.9rem;
    }

    .checkbox-section {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        margin-bottom: 20px;
    }

    .checkbox-group-item {
        display: flex;
        flex-direction: row-reverse;
        align-items: center;
        gap: 8px;
        margin-bottom: 0;
    }

    .form-section-title {
        font-size: 1.2rem;
        margin-top: 20px;
        margin-bottom: 15px;
        padding-bottom: 5px;
        border-bottom: 1px solid var(--gray-200);
    }

    .content-form {
        max-width: 800px;
    }
</style>
';

// Include header
require_once '../includes/header.php';

// Include image upload component
require_once '../includes/image-upload-component.php';

// Display error message if any
if (isset($error)): ?>
    <div class="error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

        <div class="content-section mb-4">
            <div class="section-body">
                <form method="POST" action="save-story.php" class="content-form">
                    <?php if ($story): ?>
                        <input type="hidden" name="id" value="<?php echo $story['id']; ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label class="form-label" for="title">Title</label>
                        <input type="text" id="title" name="title" class="form-control" required
                               value="<?php echo htmlspecialchars($story['title'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="slug">Slug</label>
                        <input type="text" id="slug" name="slug" class="form-control"
                               value="<?php echo htmlspecialchars($story['slug'] ?? ''); ?>">
                        <small class="form-text text-muted">Leave empty to auto-generate from title</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="author_id">Author</label>
                        <select id="author_id" name="author_id" class="form-control" required onchange="updateAgeGroup(this)">
                            <option value="">Select Author</option>
                            <?php foreach ($authors as $author): ?>
                                <option value="<?php echo $author['id']; ?>"
                                        data-author-type="<?php echo htmlspecialchars($author['author_type'] ?? 'retail'); ?>"
                                        data-author-age="<?php echo htmlspecialchars($author['age'] ?? ''); ?>"
                                        <?php echo isset($story['author_id']) && $story['author_id'] == $author['id'] ? 'selected' : ''; ?>>
                                    <?php
                                        echo htmlspecialchars($author['name']);
                                        if ($author['age']) echo " (Age: {$author['age']})";
                                        echo " - " . ucfirst($author['author_type'] ?? 'retail');
                                    ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($story['author_id']) && isset($story['author_name'])): ?>
                            <small class="form-text text-muted">Current author: <?php echo htmlspecialchars($story['author_name']); ?></small>
                        <?php endif; ?>
                    </div>

                    <script>
                    function updateAgeGroup(authorSelect) {
                        const option = authorSelect.options[authorSelect.selectedIndex];
                        const age = option.getAttribute('data-author-age');
                        const ageGroupSelect = document.getElementById('age_group');

                        if (age) {
                            const ageNum = parseInt(age);
                            if (ageNum <= 5) ageGroupSelect.value = '0-3';
                            else if (ageNum <= 8) ageGroupSelect.value = '4-6';
                            else if (ageNum <= 12) ageGroupSelect.value = '7-12';
                            else ageGroupSelect.value = '13+';
                        }
                    }
                    </script>

                    <div class="form-group">
                        <label class="form-label" for="excerpt">Excerpt</label>
                        <textarea id="excerpt" name="excerpt" class="form-control" rows="3"><?php
                            echo htmlspecialchars($story['excerpt'] ?? '');
                        ?></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="content">Content</label>
                        <textarea id="content" name="content" class="form-control" rows="10" required><?php
                            echo htmlspecialchars($story['content'] ?? '');
                        ?></textarea>
                    </div>

                    <?php
                    // Handle cover image fields - use cover_image if it exists, otherwise use cover_url
                    $coverImageField = in_array('cover_image', $additionalFields) ? 'cover_image' :
                                      (in_array('cover_url', $additionalFields) ? 'cover_url' : '');

                    if ($coverImageField):
                        // Render the image upload component for cover image
                        renderImageUploadComponent(
                            $coverImageField,
                            $story[$coverImageField] ?? '',
                            'Cover Image',
                            'story',
                            $story['id'] ?? null
                        );
                    endif;
                    ?>

                    <div class="form-group">
                        <label class="form-label" for="published_at">Publish Date</label>
                        <input type="datetime-local" id="published_at" name="published_at" class="form-control"
                               value="<?php echo isset($story['published_at']) ? date('Y-m-d\TH:i', strtotime($story['published_at'])) : ''; ?>">
                    </div>

                    <!-- Hidden source_type field for JavaScript functionality -->
                    <input type="hidden" id="source_type" name="source_type" value="<?php
                        // Determine source type based on author type
                        $sourceType = 'classic'; // Default
                        if (isset($story['author_id'])) {
                            $stmt = $db->prepare("SELECT author_type FROM authors WHERE id = ?");
                            $stmt->execute([$story['author_id']]);
                            $authorType = $stmt->fetchColumn();

                            if ($authorType === 'child') {
                                $sourceType = 'child';
                            } else if ($authorType === 'parent') {
                                $sourceType = 'parent';
                            }
                        }
                        echo htmlspecialchars($story['source_type'] ?? $sourceType);
                    ?>"><?php

                    // Hidden allow_reviews field for JavaScript functionality
                    $allowReviews = 1; // Default to true
                    if (isset($story['author_id'])) {
                        $stmt = $db->prepare("SELECT author_type FROM authors WHERE id = ?");
                        $stmt->execute([$story['author_id']]);
                        $authorType = $stmt->fetchColumn();

                        if ($authorType === 'child') {
                            $allowReviews = 0; // Child authors never get reviews
                        }
                    }
                    ?>
                    <input type="hidden" id="allow_reviews" name="allow_reviews" value="<?php echo $allowReviews; ?>">

                    <!-- Group all checkboxes together -->
                    <h3 class="form-section-title">Options</h3>
                    <div class="checkbox-section">
                        <div class="form-group checkbox-group-item">
                            <label class="form-check-label" for="is_published">Published</label>
                            <input type="checkbox" id="is_published" name="is_published" value="1"
                                   <?php echo (isset($story['is_published']) && $story['is_published']) ? "checked" : ""; ?>
                                   class="form-check-input">
                        </div>

                        <div class="form-group checkbox-group-item">
                            <label class="form-check-label" for="featured">Featured</label>
                            <input type="checkbox" id="featured" name="featured" value="1"
                                   <?php echo (isset($story['featured']) && $story['featured']) ? "checked" : ""; ?>
                                   class="form-check-input">
                        </div>

                        <div class="form-group checkbox-group-item">
                            <label class="form-check-label" for="is_sponsored">Sponsored</label>
                            <input type="checkbox" id="is_sponsored" name="is_sponsored" value="1"
                                   <?php echo (isset($story['is_sponsored']) && $story['is_sponsored']) ? "checked" : ""; ?>
                                   class="form-check-input">
                        </div>

                    <?php
                    // Collect boolean fields and non-boolean fields separately
                    $booleanFields = [];
                    $nonBooleanFields = [];

                    foreach ($additionalFields as $field) {
                        // Skip fields that are already handled above or will be handled below
                        if (in_array($field, ['featured', 'is_sponsored', 'is_published', 'published', 'published_at', 'cover_image', 'cover_url', 'slug', 'excerpt'])) continue;

                        $isRequired = isset($columnInfo[$field]) && $columnInfo[$field]['Null'] === 'NO' && $columnInfo[$field]['Default'] === null;
                        $isDateTime = isset($columnInfo[$field]) && strpos($columnInfo[$field]['Type'], 'datetime') !== false;
                        $isIntField = isset($columnInfo[$field]) && (strpos($columnInfo[$field]['Type'], 'int') !== false || strpos($columnInfo[$field]['Type'], 'tinyint') !== false);
                        $isDecimalField = isset($columnInfo[$field]) && (strpos($columnInfo[$field]['Type'], 'decimal') !== false || strpos($columnInfo[$field]['Type'], 'float') !== false || strpos($columnInfo[$field]['Type'], 'double') !== false);
                        $isEnumField = isset($columnInfo[$field]) && strpos($columnInfo[$field]['Type'], 'enum') !== false;
                        $isBooleanField = isset($columnInfo[$field]) && (
                            (strpos($columnInfo[$field]['Type'], 'tinyint(1)') !== false) ||
                            (strpos($field, 'is_') === 0) ||
                            (strpos($field, 'has_') === 0) ||
                            (strpos($field, 'needs_') === 0)
                        );

                        if ($isBooleanField) {
                            $booleanFields[] = $field;
                        } else {
                            $nonBooleanFields[] = [
                                'field' => $field,
                                'isRequired' => $isRequired,
                                'isDateTime' => $isDateTime,
                                'isIntField' => $isIntField,
                                'isDecimalField' => $isDecimalField,
                                'isEnumField' => $isEnumField
                            ];
                        }
                    }

                    // Add all boolean fields to the checkbox section
                    foreach ($booleanFields as $field):
                        // Format field label
                        $label = ucwords(str_replace('_', ' ', $field));
                    ?>
                        <div class="form-group checkbox-group-item">
                            <label class="form-check-label" for="<?php echo $field; ?>"><?php echo $label; ?></label>
                            <input type="checkbox" id="<?php echo $field; ?>" name="<?php echo $field; ?>" value="1"
                                   <?php echo (isset($story[$field]) && $story[$field]) ? "checked" : ""; ?>
                                   class="form-check-input">
                        </div>
                    <?php endforeach; ?>
                    </div>

                    <!-- Display non-boolean fields -->
                    <?php foreach ($nonBooleanFields as $fieldData):
                        $field = $fieldData['field'];
                        $isRequired = $fieldData['isRequired'];
                        $isDateTime = $fieldData['isDateTime'];
                        $isIntField = $fieldData['isIntField'];
                        $isDecimalField = $fieldData['isDecimalField'];
                        $isEnumField = $fieldData['isEnumField'];

                        // Format field label
                        $label = ucwords(str_replace('_', ' ', $field));

                        if ($isDateTime):
                    ?>
                        <div class="form-group">
                            <label class="form-label" for="<?php echo $field; ?>"><?php echo $label; ?></label>
                            <input type="datetime-local" id="<?php echo $field; ?>" name="<?php echo $field; ?>" class="form-control"
                                   value="<?php echo isset($story[$field]) ? date('Y-m-d\TH:i', strtotime($story[$field])) : ''; ?>"
                                   <?php echo $isRequired ? 'required' : ''; ?>>
                        </div>
                    <?php elseif ($isEnumField):
                        // Extract enum values
                        preg_match("/enum\(([^)]+)\)/", $columnInfo[$field]['Type'], $matches);
                        $enumValues = $matches[1] ? str_getcsv($matches[1], ',', "'") : [];
                    ?>
                        <div class="form-group">
                            <label class="form-label" for="<?php echo $field; ?>"><?php echo $label; ?></label>
                            <select id="<?php echo $field; ?>" name="<?php echo $field; ?>" class="form-control"
                                    <?php echo $isRequired ? 'required' : ''; ?>>
                                <option value="">Select <?php echo $label; ?></option>
                                <?php foreach ($enumValues as $value): ?>
                                    <option value="<?php echo $value; ?>"
                                            <?php echo ($story[$field] ?? '') == $value ? 'selected' : ''; ?>>
                                        <?php echo ucfirst($value); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php elseif (($field === 'average_rating' || $field === 'review_count')): ?>
                        <?php
                        // Check if author is a child
                        $isChildAuthor = false;
                        if (isset($story['author_id'])) {
                            $stmt = $db->prepare("SELECT author_type FROM authors WHERE id = ?");
                            $stmt->execute([$story['author_id']]);
                            $authorType = $stmt->fetchColumn();
                            $isChildAuthor = ($authorType === 'child');
                        }

                        // Only show rating fields for non-child authors
                        if (!$isChildAuthor):
                        ?>
                            <?php if ($field === 'average_rating'): ?>
                                <div class="form-group">
                                    <label class="form-label" for="<?php echo $field; ?>"><?php echo $label; ?></label>
                                    <div class="d-flex align-items-center">
                                        <input type="range" id="<?php echo $field; ?>_slider" class="form-control w-75"
                                               min="0" max="5" step="0.1"
                                               value="<?php echo htmlspecialchars($story[$field] ?? '0'); ?>"
                                               oninput="document.getElementById('<?php echo $field; ?>').value = this.value; document.getElementById('<?php echo $field; ?>_display').textContent = this.value;">
                                        <input type="number" id="<?php echo $field; ?>" name="<?php echo $field; ?>" class="form-control w-25 ml-2"
                                               min="0" max="5" step="0.1"
                                               value="<?php echo htmlspecialchars($story[$field] ?? '0'); ?>"
                                               oninput="document.getElementById('<?php echo $field; ?>_slider').value = this.value; document.getElementById('<?php echo $field; ?>_display').textContent = this.value;"
                                               <?php echo $isRequired ? 'required' : ''; ?>>
                                    </div>
                                    <div class="text-center mt-2">
                                        <span id="<?php echo $field; ?>_display" class="text-lg font-bold"><?php echo htmlspecialchars($story[$field] ?? '0'); ?></span> / 5
                                    </div>
                                </div>
                            <?php elseif ($field === 'review_count'): ?>
                                <div class="form-group">
                                    <label class="form-label" for="<?php echo $field; ?>"><?php echo $label; ?></label>
                                    <input type="number" id="<?php echo $field; ?>" name="<?php echo $field; ?>" class="form-control"
                                           min="0" step="1"
                                           value="<?php echo htmlspecialchars($story[$field] ?? '0'); ?>"
                                           <?php echo $isRequired ? 'required' : ''; ?>>
                                    <small class="form-text text-muted">Number of reviews for this story</small>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <!-- Hidden inputs for child authors -->
                            <input type="hidden" name="<?php echo $field; ?>" value="0">
                        <?php endif; ?>
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
    </div>

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

        .w-75 {
            width: 75%;
        }

        .w-25 {
            width: 25%;
        }

        .ml-2 {
            margin-left: 0.5rem;
        }

        .d-flex {
            display: flex;
        }

        .align-items-center {
            align-items: center;
        }

        .text-center {
            text-align: center;
        }

        .mt-2 {
            margin-top: 0.5rem;
        }

        .text-lg {
            font-size: 1.125rem;
        }

        .font-bold {
            font-weight: 700;
        }
    </style>

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
