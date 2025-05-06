<?php
/**
 * Enhanced Table Component
 *
 * A modern, interactive table component with clickable images, inline editing,
 * and other premium features.
 *
 * Usage:
 * include '../includes/enhanced-table-component.php';
 * renderEnhancedTable($items, $columns, 'stories', 'stories-table');
 */

/**
 * Helper function to get display URL for images
 *
 * @param string $filePath The file path or URL
 * @return string The properly formatted URL
 */
function getTableDisplayUrl($filePath, $itemType = 'general') {
    // If it's null or empty, return default image
    if (empty($filePath)) {
        // Check if this is an author avatar
        if ($itemType === 'author') {
            return 'https://api.storiesfromtheweb.org/admin/assets/images/default-avatar.svg';
        }
        // For other content types, use the default cover
        return 'https://api.storiesfromtheweb.org/admin/assets/images/default-cover.svg';
    }

    // Check if there's a thumbnail version available
    if (strpos($filePath, '/uploads/') !== false && strpos($filePath, '-thumbnail') === false) {
        // Try to use the thumbnail version if it exists
        $pathInfo = pathinfo($filePath);

        // Use the correct path format without any unique ID prefix
        // First, remove any unique ID prefix if it exists (like '6819c7559130f-')
        $filename = $pathInfo['filename'];
        if (preg_match('/^[a-f0-9]+-(.+)$/', $filename, $matches)) {
            $filename = $matches[1];
        }

        // Use .webp extension for thumbnails as that's what the system is using
        $thumbnailPath = $pathInfo['dirname'] . '/optimized/' . $filename . '-thumbnail.webp';

        // Check if the thumbnail exists
        if (file_exists($_SERVER['DOCUMENT_ROOT'] . $thumbnailPath)) {
            return $thumbnailPath;
        } else {
            // Try with jpg extension as fallback
            $thumbnailPathJpg = $pathInfo['dirname'] . '/optimized/' . $filename . '-thumbnail.jpg';
            if (file_exists($_SERVER['DOCUMENT_ROOT'] . $thumbnailPathJpg)) {
                return $thumbnailPathJpg;
            }
        }
    }

    // If it's already an absolute URL
    if (strpos($filePath, 'http') === 0) {
        return $filePath;
    }

    // If it's a relative URL starting with /
    if (strpos($filePath, '/') === 0) {
        return 'https://' . $_SERVER['HTTP_HOST'] . $filePath;
    }

    return $filePath;
}

/**
 * Render an enhanced table with modern features
 *
 * @param array $items The data items to display
 * @param array $columns The columns to show
 * @param string $itemType The type of items (e.g., 'story', 'author')
 * @param string $tableId A unique ID for the table
 * @param array $options Additional options for the table
 */
function renderEnhancedTable($items, $columns, $itemType, $tableId, $options = []) {
    // Default options
    $defaultOptions = [
        'showCheckboxes' => false,
        'showActions' => true,
        'actions' => ['view', 'edit', 'delete'],
        'thumbnailField' => false,
        'thumbnailAltField' => 'title',
        'thumbnailClickable' => true,
        'thumbnailClickAction' => 'view',
        'editableFields' => [],
        'htmlFields' => [],
        'bulkActions' => [],
        'customActionRenderer' => null,
        'itemsPerPage' => 25,
        'currentPage' => 1
    ];

    // Merge options with defaults
    $options = array_merge($defaultOptions, $options);

    // Start output buffering to capture any errors
    ob_start();
    ?>

    <div class="premium-table-container">
        <?php if (!empty($options['bulkActions'])): ?>
            <div class="premium-bulk-actions">
                <div class="premium-bulk-actions-select">
                    <select class="premium-select bulk-action-select">
                        <option value="">Bulk Actions</option>
                        <?php foreach ($options['bulkActions'] as $action => $label): ?>
                            <?php
                            if (is_numeric($action)) {
                                $action = $label;
                            }
                            ?>
                            <option value="<?php echo htmlspecialchars($action); ?>">
                                <?php echo htmlspecialchars(ucfirst($label)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button class="premium-btn premium-btn-secondary apply-bulk-action">Apply</button>
                </div>
                <div class="premium-selected-count" style="display: none;">
                    <span class="count">0</span> items selected
                </div>
            </div>
        <?php endif; ?>

        <div class="premium-table-wrapper">
            <table class="premium-table <?php echo htmlspecialchars($tableId); ?>" id="<?php echo htmlspecialchars($tableId); ?>">
                <thead>
                    <tr>
                        <?php if ($options['showCheckboxes']): ?>
                            <th class="checkbox-column">
                                <div class="premium-checkbox-container">
                                    <input type="checkbox" class="premium-checkbox select-all">
                                </div>
                            </th>
                        <?php endif; ?>

                        <?php if ($options['thumbnailField']): ?>
                            <th class="thumbnail-column"></th>
                        <?php endif; ?>

                        <?php foreach ($columns as $field => $label): ?>
                            <th><?php echo htmlspecialchars($label); ?></th>
                        <?php endforeach; ?>

                        <?php if ($options['showActions']): ?>
                            <th class="actions-column">Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr data-id="<?php echo htmlspecialchars($item['id']); ?>">
                            <?php if ($options['showCheckboxes']): ?>
                                <td class="checkbox-column">
                                    <div class="premium-checkbox-container">
                                        <input type="checkbox" class="premium-checkbox item-checkbox" value="<?php echo htmlspecialchars($item['id']); ?>">
                                    </div>
                                </td>
                            <?php endif; ?>

                            <?php if (isset($options['thumbnailField']) && $options['thumbnailField'] !== false): ?>
                                <td class="thumbnail-column">
                                    <?php
                                    $thumbnailUrl = isset($item[$options['thumbnailField']]) ? $item[$options['thumbnailField']] : '';
                                    $thumbnailAlt = isset($item[$options['thumbnailAltField']]) ? $item[$options['thumbnailAltField']] : '';

                                    // Get the proper display URL (will return default image if empty)
                                    $thumbnailUrl = getTableDisplayUrl($thumbnailUrl, $itemType);

                                    // Check if thumbnails should be clickable
                                    $isClickable = isset($options['thumbnailClickable']) && $options['thumbnailClickable'];
                                    $clickAction = isset($options['thumbnailClickAction']) ? $options['thumbnailClickAction'] : 'view';
                                    $clickUrl = '';

                                    if ($isClickable) {
                                        if ($clickAction === 'view') {
                                            $clickUrl = $itemType === 'story' ? 'view-story.php?id=' . $item['id'] : $itemType . '.php?id=' . $item['id'];
                                        } else if ($clickAction === 'edit') {
                                            $clickUrl = $itemType === 'story' ? 'story-form.php?id=' . $item['id'] : $itemType . '.php?id=' . $item['id'];
                                        }
                                    }

                                    if ($isClickable && !empty($clickUrl)) {
                                        echo '<a href="' . htmlspecialchars($clickUrl) . '" class="thumbnail-link">';
                                    }
                                    ?>
                                    <img src="<?php echo htmlspecialchars($thumbnailUrl); ?>" alt="<?php echo htmlspecialchars($thumbnailAlt); ?>" class="thumbnail-image">
                                    <?php
                                    if ($isClickable && !empty($clickUrl)) {
                                        echo '</a>';
                                    }
                                    ?>
                                </td>
                            <?php endif; ?>

                            <?php foreach ($columns as $field => $label): ?>
                                <td>
                                    <?php if (in_array($field, $options['editableFields'])): ?>
                                        <div class="premium-editable" data-field-name="<?php echo htmlspecialchars($field); ?>" data-field-type="text">
                                            <?php echo htmlspecialchars($item[$field] ?? ''); ?>
                                        </div>
                                    <?php else: ?>
                                        <?php if (in_array($field, $options['htmlFields'])): ?>
                                            <?php echo $item[$field] ?? ''; ?>
                                        <?php else: ?>
                                            <?php echo htmlspecialchars($item[$field] ?? ''); ?>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>

                            <?php if ($options['showActions']): ?>
                                <td class="actions-column">
                                    <?php if (isset($options['customActionRenderer']) && is_callable($options['customActionRenderer'])): ?>
                                        <?php echo $options['customActionRenderer']($item); ?>
                                    <?php else: ?>
                                        <div class="premium-table-actions">
                                            <?php if (in_array('view', $options['actions'])): ?>
                                                <?php
                                                // Only use view-story.php for stories, keep original paths for others
                                                $viewUrl = $itemType === 'story' ? 'view-story.php?id=' . $item['id'] : $itemType . '.php?id=' . $item['id'];
                                                echo '<a href="' . htmlspecialchars($viewUrl) . '" class="premium-btn premium-btn-info premium-btn-sm">';
                                                echo '<i class="fas fa-eye"></i>';
                                                echo '</a>';
                                                ?>
                                            <?php endif; ?>

                                            <?php if (in_array('edit', $options['actions'])): ?>
                                                <?php
                                                // Only use story-form.php for stories, keep original paths for others
                                                $editUrl = $itemType === 'story' ? 'story-form.php?id=' . $item['id'] : $itemType . '.php?id=' . $item['id'];
                                                echo '<a href="' . htmlspecialchars($editUrl) . '" class="premium-btn premium-btn-primary premium-btn-sm">';
                                                echo '<i class="fas fa-edit"></i>';
                                                echo '</a>';
                                                ?>
                                            <?php endif; ?>

                                            <?php if (in_array('delete', $options['actions'])): ?>
                                                <form method="POST" action="delete-<?php echo $itemType; ?>.php" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this item?');">
                                                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($item['id']); ?>">
                                                    <button type="submit" class="premium-btn premium-btn-danger premium-btn-sm">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php
    // Output any errors
    if ($error = ob_get_clean()) {
        error_log("Enhanced table error: " . $error);
        echo '<div class="alert alert-danger">Error rendering table. Please check the error logs.</div>';
    }
}
?>
