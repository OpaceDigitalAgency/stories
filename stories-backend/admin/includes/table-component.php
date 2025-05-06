<?php
/**
 * Table Component
 *
 * A reusable table component for content listing pages.
 *
 * Usage:
 * include '../includes/table-component.php';
 * renderEnhancedTable($items, $columns, $options);
 */

/**
 * Renders an enhanced table for the specified content
 *
 * @param array $items The items to display in the table
 * @param array $columns The columns to display (format: ['key' => 'Label'])
 * @param array $options Additional options for the table
 * @return void
 */
function renderEnhancedTable($items, $columns, $options = []) {
    // Default options
    $defaults = [
        'id' => 'data-table',
        'class' => 'table',
        'empty_message' => 'No items found.',
        'checkbox_column' => true,
        'thumbnail_column' => true, // New option for thumbnail column
        'actions_column' => true,
        'actions' => [
            'view' => true,
            'edit' => true,
            'delete' => true
        ],
        'content_type' => 'item', // Used for aria labels
        'id_field' => 'id',
        'name_field' => 'name', // Used for aria labels
        'view_url' => 'view-{content_type}.php?id={id}',
        'edit_url' => '{content_type}-form.php?id={id}',
        'delete_url' => 'delete-{content_type}.php',
        'delete_type' => 'simple', // Default to simple delete, override for special cases
        'custom_formatters' => [], // Custom formatters for specific columns
        'row_classes' => [], // Custom classes for specific rows
        'thumbnail_field' => null, // Field containing the thumbnail URL
    ];

    // Merge options with defaults
    $options = array_merge($defaults, $options);

    // Replace placeholders in URLs
    // Properly handle singular forms
    $singularForms = [
        'stories' => 'story',
        'categories' => 'category',
        'properties' => 'property',
        'directories' => 'directory',
        // Add more special cases as needed
    ];

    if (isset($singularForms[$options['content_type']])) {
        $content_type_singular = $singularForms[$options['content_type']];
    } else {
        // Default behavior for regular plurals
        $content_type_singular = rtrim($options['content_type'], 's');
    }

    $options['view_url'] = str_replace('{content_type}', $content_type_singular, $options['view_url']);
    $options['edit_url'] = str_replace('{content_type}', $content_type_singular, $options['edit_url']);
    $options['delete_url'] = str_replace('{content_type}', $content_type_singular, $options['delete_url']);

    // Render the table
    ?>
    <div class="table-container">
        <table id="<?php echo htmlspecialchars($options['id']); ?>" class="<?php echo htmlspecialchars($options['class']); ?>">
            <thead>
                <tr>
                    <?php if ($options['checkbox_column']): ?>
                        <th class="checkbox-column">
                            <input type="checkbox" id="select-all" aria-label="Select all <?php echo htmlspecialchars($options['content_type']); ?>">
                        </th>
                    <?php endif; ?>

                    <?php if ($options['thumbnail_column']): ?>
                        <th class="thumbnail-column">Image</th>
                    <?php endif; ?>

                    <?php foreach ($columns as $key => $label): ?>
                        <th><?php echo htmlspecialchars($label); ?></th>
                    <?php endforeach; ?>

                    <?php if ($options['actions_column']): ?>
                        <th>Actions</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="<?php echo count($columns) + ($options['checkbox_column'] ? 1 : 0) + ($options['actions_column'] ? 1 : 0); ?>" class="text-center">
                            <?php echo htmlspecialchars($options['empty_message']); ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($items as $item): ?>
                        <?php
                        // Determine row classes
                        $rowClass = '';
                        foreach ($options['row_classes'] as $class => $condition) {
                            if (is_callable($condition) && $condition($item)) {
                                $rowClass .= ' ' . $class;
                            }
                        }
                        ?>
                        <tr class="<?php echo trim($rowClass); ?>">
                            <?php if ($options['checkbox_column']): ?>
                                <td class="checkbox-column">
                                    <input
                                        type="checkbox"
                                        class="bulk-checkbox"
                                        name="selected_ids[]"
                                        value="<?php echo $item[$options['id_field']]; ?>"
                                        aria-label="Select <?php echo htmlspecialchars($content_type_singular); ?>: <?php echo htmlspecialchars($item[$options['name_field']] ?? ''); ?>"
                                    >
                                </td>
                            <?php endif; ?>

                            <?php if ($options['thumbnail_column']): ?>
                                <td class="thumbnail-column">
                                    <?php
                                    // Get thumbnail URL based on content type
                                    $thumbnailUrl = getThumbnailUrl($item, $options['content_type'], $options['thumbnail_field']);
                                    if ($thumbnailUrl): ?>
                                        <img
                                            src="<?php echo htmlspecialchars($thumbnailUrl); ?>"
                                            alt="<?php echo htmlspecialchars($item[$options['name_field']] ?? 'Thumbnail'); ?>"
                                            class="admin-thumbnail"
                                            loading="lazy"
                                        >
                                    <?php else: ?>
                                        <div class="no-thumbnail">
                                            <i class="fas fa-image" aria-hidden="true"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>

                            <?php foreach ($columns as $key => $label): ?>
                                <td>
                                    <?php
                                    // Check if there's a custom formatter for this column
                                    if (isset($options['custom_formatters'][$key]) && is_callable($options['custom_formatters'][$key])) {
                                        echo $options['custom_formatters'][$key]($item, $key);
                                    } else {
                                        // Default formatting
                                        echo isset($item[$key]) ? htmlspecialchars($item[$key]) : '';
                                    }
                                    ?>
                                </td>
                            <?php endforeach; ?>

                            <?php if ($options['actions_column']): ?>
                                <td>
                                    <div class="table-actions">
                                        <?php if ($options['actions']['view']): ?>
                                            <a
                                                href="<?php echo str_replace('{id}', $item[$options['id_field']], $options['view_url']); ?>"
                                                class="btn btn-info btn-sm"
                                                aria-label="View <?php echo htmlspecialchars($content_type_singular); ?>: <?php echo htmlspecialchars($item[$options['name_field']] ?? ''); ?>"
                                            >
                                                <i class="fas fa-eye" aria-hidden="true"></i> View
                                            </a>
                                        <?php endif; ?>

                                        <?php if ($options['actions']['edit']): ?>
                                            <a
                                                href="<?php echo str_replace('{id}', $item[$options['id_field']], $options['edit_url']); ?>"
                                                class="btn btn-primary btn-sm"
                                                aria-label="Edit <?php echo htmlspecialchars($content_type_singular); ?>: <?php echo htmlspecialchars($item[$options['name_field']] ?? ''); ?>"
                                            >
                                                <i class="fas fa-edit" aria-hidden="true"></i> Edit
                                            </a>
                                        <?php endif; ?>

                                        <?php if ($options['actions']['delete']): ?>
                                            <?php if ($options['delete_type'] === 'confirm'): ?>
                                                <a
                                                    href="<?php echo $options['delete_url'] . '?id=' . $item[$options['id_field']]; ?>"
                                                    class="btn btn-danger btn-sm"
                                                    aria-label="Delete <?php echo htmlspecialchars($content_type_singular); ?>: <?php echo htmlspecialchars($item[$options['name_field']] ?? ''); ?>"
                                                >
                                                    <i class="fas fa-trash-alt" aria-hidden="true"></i> Delete
                                                </a>
                                            <?php else: ?>
                                                <form method="POST" action="<?php echo $options['delete_url']; ?>" style="display: inline;">
                                                    <input type="hidden" name="id" value="<?php echo $item[$options['id_field']]; ?>">
                                                    <button
                                                        type="submit"
                                                        class="btn btn-danger btn-sm"
                                                        aria-label="Delete <?php echo htmlspecialchars($content_type_singular); ?>: <?php echo htmlspecialchars($item[$options['name_field']] ?? ''); ?>"
                                                    >
                                                        <i class="fas fa-trash-alt" aria-hidden="true"></i> Delete
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        <?php endif; ?>

                                        <?php if (isset($options['custom_actions']) && is_callable($options['custom_actions'])): ?>
                                            <?php echo $options['custom_actions']($item); ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}

/**
 * Gets the thumbnail URL for an item based on content type
 *
 * @param array $item The item data
 * @param string $contentType The content type (stories, posts, games, etc.)
 * @param string|null $thumbnailField Optional specific field containing the thumbnail URL
 * @return string|null The thumbnail URL or null if not found
 */
function getThumbnailUrl($item, $contentType, $thumbnailField = null) {
    // If a specific thumbnail field is provided and exists in the item, use it
    if ($thumbnailField && isset($item[$thumbnailField]) && !empty($item[$thumbnailField])) {
        return $item[$thumbnailField];
    }

    // First check for optimized thumbnail versions
    if (isset($item['thumbnail_url']) && !empty($item['thumbnail_url'])) {
        return $item['thumbnail_url'];
    }

    if (isset($item['small_url']) && !empty($item['small_url'])) {
        return $item['small_url'];
    }

    // Check for common image fields based on content type
    $imageUrl = null;

    switch ($contentType) {
        case 'stories':
            // Check for cover_url field
            if (isset($item['cover_url']) && !empty($item['cover_url'])) {
                $imageUrl = $item['cover_url'];
            }
            break;

        case 'posts':
        case 'blog_posts':
            // Check for cover_url field
            if (isset($item['cover_url']) && !empty($item['cover_url'])) {
                $imageUrl = $item['cover_url'];
            }
            break;

        case 'games':
            // Check for cover_url field
            if (isset($item['cover_url']) && !empty($item['cover_url'])) {
                $imageUrl = $item['cover_url'];
            }
            break;

        case 'authors':
            // Check for avatar field
            if (isset($item['avatar']) && !empty($item['avatar'])) {
                $imageUrl = $item['avatar'];
            }
            break;

        case 'directory_items':
            // Check for image_url field
            if (isset($item['image_url']) && !empty($item['image_url'])) {
                $imageUrl = $item['image_url'];
            }
            break;

        case 'ai_tools':
            // Check for image_url field
            if (isset($item['image_url']) && !empty($item['image_url'])) {
                $imageUrl = $item['image_url'];
            }
            break;
    }

    // If we found an image URL, try to get its thumbnail version
    if ($imageUrl) {
        // Try to find a thumbnail version in the media table
        $thumbnailUrl = getOptimizedImageUrl($imageUrl);
        if ($thumbnailUrl) {
            return $thumbnailUrl;
        }

        // If no thumbnail found, return the original URL
        return $imageUrl;
    }

    // Check for any field that might contain an image URL
    $possibleImageFields = ['thumbnail', 'thumbnail_url', 'image', 'image_url', 'photo', 'photo_url', 'picture', 'picture_url'];

    foreach ($possibleImageFields as $field) {
        if (isset($item[$field]) && !empty($item[$field])) {
            return $item[$field];
        }
    }

    // No thumbnail found
    return null;
}

/**
 * Get optimized image URL for a specific size
 *
 * @param string $originalUrl Original image URL
 * @param string $size Size identifier (thumbnail, small)
 * @return string|null URL for the requested size or null if not available
 */
function getOptimizedImageUrl($originalUrl, $size = 'thumbnail') {
    // Check if this is already a thumbnail URL
    if (strpos($originalUrl, '/thumbnail/') !== false ||
        strpos($originalUrl, '_thumbnail.') !== false ||
        strpos($originalUrl, '-thumbnail.') !== false) {
        return $originalUrl;
    }

    // Check if it's an optimized image URL
    if (strpos($originalUrl, '/uploads/optimized/') !== false) {
        // Replace the size suffix with thumbnail
        $thumbnailUrl = preg_replace('/-(?:medium|large|small)\.(webp|jpg|png|jpeg)$/', '-thumbnail.$1', $originalUrl);

        // If no size suffix was found, insert thumbnail before the extension
        if (strpos($thumbnailUrl, '-thumbnail.') === false) {
            $thumbnailUrl = preg_replace('/\.(webp|jpg|png|jpeg)$/', '-thumbnail.$1', $originalUrl);
        }

        return $thumbnailUrl;
    }

    // For regular uploads, try to find the optimized version
    if (strpos($originalUrl, '/uploads/') !== false) {
        $pathInfo = pathinfo($originalUrl);
        $filename = $pathInfo['filename'];

        // Create the optimized thumbnail URL
        $thumbnailUrl = '/uploads/optimized/' . $filename . '-thumbnail.webp';

        return $thumbnailUrl;
    }

    // For other images, try to find the thumbnail in the database
    try {
        // Get database connection
        global $db;
        if (!$db) {
            return null;
        }

        // Extract filename from URL
        $filename = basename($originalUrl);

        // Query the media table for thumbnail versions
        $stmt = $db->prepare("
            SELECT thumbnail_url, small_url
            FROM media
            WHERE file_path = ? OR filename = ?
            LIMIT 1
        ");
        $stmt->execute([$originalUrl, $filename]);
        $media = $stmt->fetch();

        if ($media) {
            // Return the requested size or fall back to the next available size
            if ($size === 'thumbnail' && !empty($media['thumbnail_url'])) {
                return $media['thumbnail_url'];
            } else if (!empty($media['small_url'])) {
                return $media['small_url'];
            }
        }
    } catch (Exception $e) {
        error_log("Error getting optimized image URL: " . $e->getMessage());
    }

    // If we couldn't find a thumbnail, return the original URL
    return $originalUrl;
}