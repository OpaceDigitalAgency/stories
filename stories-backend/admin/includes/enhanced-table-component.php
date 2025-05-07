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
            return '../assets/images/default-avatar.svg';
        }
        // For other content types, use the default cover
        return '../assets/images/default-cover.svg';
    }

    // Log the file path for debugging
    error_log("Thumbnail lookup for: " . $filePath . " (Item type: " . $itemType . ")");

    // First, try to look up the thumbnail in the database based on the item type and ID
    if (isset($GLOBALS['db']) && $GLOBALS['db']) {
        $db = $GLOBALS['db'];
        $itemId = null;

        // Try to get the item ID from the URL or path
        if (preg_match('/\/(\d+)\//', $filePath, $matches)) {
            $itemId = $matches[1];
        } else if (is_numeric($filePath)) {
            $itemId = $filePath;
        }

        if ($itemId) {
            try {
                $tableName = '';
                $idField = 'id';
                $thumbnailField = 'thumbnail_url';

                // Determine the table and fields based on item type
                switch ($itemType) {
                    case 'media':
                        $tableName = 'media';
                        break;
                    case 'author':
                        $tableName = 'authors';
                        $thumbnailField = 'avatar_url';
                        break;
                    case 'directory_item':
                        $tableName = 'directory_items';
                        $thumbnailField = 'image_url';
                        break;
                    case 'game':
                        $tableName = 'games';
                        $thumbnailField = 'cover_image';
                        break;
                    case 'ai_tool':
                        $tableName = 'ai_tools';
                        $thumbnailField = 'image_url';
                        break;
                    case 'story':
                        $tableName = 'stories';
                        $thumbnailField = 'cover_image';
                        break;
                    case 'post':
                        $tableName = 'posts';
                        $thumbnailField = 'featured_image';
                        break;
                }

                if (!empty($tableName)) {
                    // Query for the thumbnail URL
                    $stmt = $db->prepare("SELECT {$thumbnailField} FROM {$tableName} WHERE {$idField} = ?");
                    $stmt->execute([$itemId]);
                    $result = $stmt->fetch();

                    if ($result && !empty($result[$thumbnailField])) {
                        $dbThumbnail = $result[$thumbnailField];
                        error_log("Found item in database with thumbnail: " . $dbThumbnail);

                        // If this is a media item, check if it has a specific thumbnail URL
                        if ($itemType === 'media') {
                            $mediaStmt = $db->prepare("SELECT thumbnail_url FROM media WHERE id = ?");
                            $mediaStmt->execute([$itemId]);
                            $mediaResult = $mediaStmt->fetch();

                            if ($mediaResult && !empty($mediaResult['thumbnail_url'])) {
                                error_log("Found media thumbnail in database: " . $mediaResult['thumbnail_url']);
                                return $mediaResult['thumbnail_url'];
                            }
                        }

                        // Use the thumbnail from the database
                        return $dbThumbnail;
                    }
                }
            } catch (Exception $e) {
                error_log("Error looking up thumbnail in database: " . $e->getMessage());
            }
        }
    }

    // Check if there's a thumbnail version available in the filesystem
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
        $thumbnailPathAbs = $_SERVER['DOCUMENT_ROOT'] . $thumbnailPath;

        error_log("Looking for thumbnail at: " . $thumbnailPathAbs);

        // Check if the thumbnail exists
        if (file_exists($thumbnailPathAbs)) {
            error_log("Found thumbnail: " . $thumbnailPath);
            return $thumbnailPath;
        } else {
            // Try with jpg extension as fallback
            $thumbnailPathJpg = $pathInfo['dirname'] . '/optimized/' . $filename . '-thumbnail.jpg';
            $thumbnailPathJpgAbs = $_SERVER['DOCUMENT_ROOT'] . $thumbnailPathJpg;

            error_log("Looking for JPG thumbnail at: " . $thumbnailPathJpgAbs);

            if (file_exists($thumbnailPathJpgAbs)) {
                error_log("Found JPG thumbnail: " . $thumbnailPathJpg);
                return $thumbnailPathJpg;
            }

            // Try with png extension as another fallback
            $thumbnailPathPng = $pathInfo['dirname'] . '/optimized/' . $filename . '-thumbnail.png';
            $thumbnailPathPngAbs = $_SERVER['DOCUMENT_ROOT'] . $thumbnailPathPng;

            error_log("Looking for PNG thumbnail at: " . $thumbnailPathPngAbs);

            if (file_exists($thumbnailPathPngAbs)) {
                error_log("Found PNG thumbnail: " . $thumbnailPathPng);
                return $thumbnailPathPng;
            }
        }
    }

    // For media library items, check if we have a thumbnail URL in the database
    if ($itemType === 'media') {
        // Try to extract ID from URL if it's a full URL
        $id = null;
        if (preg_match('/\/(\d+)\//', $filePath, $matches)) {
            $id = $matches[1];
        } else if (is_numeric($filePath)) {
            $id = $filePath;
        }

        if ($id) {
            try {
                // Connect to database if not already connected
                if (!isset($GLOBALS['db']) || !$GLOBALS['db']) {
                    $db = new PDO(
                        'mysql:host=localhost;dbname=stories_db;charset=utf8mb4',
                        'stories_user',
                        '$tw1cac3*sOt',
                        [
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        ]
                    );
                } else {
                    $db = $GLOBALS['db'];
                }

                // Query for thumbnail URL
                $stmt = $db->prepare("SELECT thumbnail_url FROM media WHERE id = ?");
                $stmt->execute([$id]);
                $result = $stmt->fetch();

                if ($result && !empty($result['thumbnail_url'])) {
                    error_log("Found thumbnail in database: " . $result['thumbnail_url']);
                    return $result['thumbnail_url'];
                }
            } catch (Exception $e) {
                error_log("Error looking up thumbnail in database: " . $e->getMessage());
            }
        }
    }

    // If it's already an absolute URL
    if (strpos($filePath, 'http') === 0) {
        return $filePath;
    }

    // If it's a relative URL starting with /
    if (strpos($filePath, '/') === 0) {
        // Check if we're in a development environment
        if (isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] === 'localhost') {
            return $filePath;
        }
        return 'https://' . $_SERVER['HTTP_HOST'] . $filePath;
    }

    // If it's a relative path without leading slash
    if (strpos($filePath, '../') === 0 || strpos($filePath, './') === 0) {
        return $filePath;
    }

    // If it's just a filename, assume it's in the uploads directory
    if (strpos($filePath, '/') === false) {
        return '../uploads/' . $filePath;
    }

    return $filePath;
}

/**
 * Renders an enhanced table for the specified items
 *
 * @param array $items The items to display in the table
 * @param array $columns The columns to display (format: ['field' => 'Label', ...])
 * @param string $itemType The type of items (e.g., 'stories', 'authors')
 * @param string $tableId The ID for the table element
 * @param array $options Additional options for the table
 * @return void
 */
function renderEnhancedTable($items, $columns, $itemType, $tableId, $options = []) {
    // Default options
    $defaultOptions = [
        'showCheckboxes' => true,
        'showActions' => true,
        'actions' => ['view', 'edit', 'delete'],
        'thumbnailField' => 'image', // Set to false to disable thumbnail column
        'thumbnailAltField' => 'title',
        'editableFields' => [],
        'htmlFields' => [], // Fields that should render HTML instead of escaping it
        'bulkActions' => ['delete'],
        'itemsPerPage' => 10,
        'currentPage' => 1
    ];

    // Merge options
    $options = array_merge($defaultOptions, $options);

    // Check if we have items to display
    if (empty($items)) {
        echo '<div class="alert alert-info">No items found.</div>';
        return;
    }

    // Calculate pagination
    $totalItems = count($items);
    $totalPages = ceil($totalItems / $options['itemsPerPage']);
    $startIndex = ($options['currentPage'] - 1) * $options['itemsPerPage'];
    $endIndex = min($startIndex + $options['itemsPerPage'], $totalItems);
    $paginatedItems = array_slice($items, $startIndex, $options['itemsPerPage']);

    // Render the table
    ?>
    <style>
        .thumbnail-column {
            width: 80px;
            text-align: center;
        }
        .thumbnail-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        .loading-indicator {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        /* Preview Modal Styles */
        .preview-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 10000;
        }

        .preview-modal-content {
            background-color: #fff;
            border-radius: 5px;
            width: 90%;
            max-width: 900px;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }

        .preview-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            border-bottom: 1px solid #ddd;
        }

        .preview-modal-header h2 {
            margin: 0;
            font-size: 1.5rem;
        }

        .preview-modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #666;
        }

        .preview-modal-body {
            padding: 20px;
            overflow-y: auto;
            flex-grow: 1;
        }

        .preview-loading {
            text-align: center;
            padding: 20px;
            font-style: italic;
            color: #666;
        }

        #contact-preview .card {
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        #contact-preview .card-header {
            background-color: #f8f9fa;
            padding: 15px;
            border-bottom: 1px solid #ddd;
        }

        #contact-preview .card-body {
            padding: 20px;
        }

        #contact-preview .card-footer {
            background-color: #f8f9fa;
            padding: 15px;
            border-top: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
        }

        #contact-preview .message-content {
            white-space: pre-wrap;
            margin-top: 15px;
            line-height: 1.5;
        }
    </style>
    <div class="premium-table-container">
        <?php if ($options['showCheckboxes'] && !empty($options['bulkActions'])): ?>
            <div class="premium-bulk-actions" style="padding: 1rem; border-bottom: 1px solid var(--premium-gray-200); display: flex; align-items: center; gap: 0.75rem;">
                <div class="premium-checkbox-container">
                    <input type="checkbox" id="select-all" class="premium-checkbox">
                    <label for="select-all" class="premium-checkbox-label"></label>
                </div>

                <select class="form-control" id="bulk-action-select" style="width: auto;">
                    <option value="">Bulk Actions</option>
                    <?php foreach ($options['bulkActions'] as $action): ?>
                        <option value="<?php echo htmlspecialchars($action); ?>"><?php echo ucfirst($action); ?></option>
                    <?php endforeach; ?>
                </select>

                <button type="button" class="premium-btn premium-btn-secondary" id="apply-bulk-action">Apply</button>

                <span class="premium-selected-count" style="margin-left: auto; font-size: 0.875rem; color: var(--premium-gray-600);">0 items selected</span>
            </div>
        <?php endif; ?>

        <table class="premium-table" id="<?php echo htmlspecialchars($tableId); ?>" data-item-type="<?php echo htmlspecialchars($itemType); ?>">
            <thead>
                <tr>
                    <?php if ($options['showCheckboxes']): ?>
                        <th class="checkbox-column">
                            <div class="premium-checkbox-container">
                                <input type="checkbox" class="premium-checkbox select-all-checkbox">
                            </div>
                        </th>
                    <?php endif; ?>

                    <?php if (isset($options['thumbnailField']) && $options['thumbnailField'] !== false): ?>
                        <th class="thumbnail-column">Image</th>
                    <?php endif; ?>

                    <?php foreach ($columns as $field => $label): ?>
                        <th data-field="<?php echo htmlspecialchars($field); ?>"><?php echo htmlspecialchars($label); ?></th>
                    <?php endforeach; ?>

                    <?php if ($options['showActions']): ?>
                        <th class="actions-column">Actions</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($paginatedItems)): ?>
                    <tr>
                        <td colspan="<?php echo count($columns) + ($options['showCheckboxes'] ? 1 : 0) + ($options['showActions'] ? 1 : 0) + (isset($options['thumbnailField']) ? 1 : 0); ?>" class="premium-text-center" style="padding: 2rem;">
                            No items found.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($paginatedItems as $item): ?>
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
                                            $clickUrl = 'view-' . $itemType . '.php?id=' . $item['id'];
                                        } else if ($clickAction === 'edit') {
                                            $clickUrl = $itemType . '-form.php?id=' . $item['id'];
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
                                                // Get the file path based on item type
                                                // Special handling for stories, posts, games, directory items, AI tools, authors, and contacts to use the lightbox
                                                // Skip preview for tags and subscribers
                                                if ($itemType === 'tag' || $itemType === 'subscriber') {
                                                    // No preview button for tags and subscribers
                                                } else if ($itemType === 'story') {
                                                    echo '<button type="button" class="premium-btn premium-btn-info premium-btn-sm story-preview-btn" data-story-id="' . htmlspecialchars($item['id']) . '" title="Preview">';
                                                    echo '<i class="fas fa-eye"></i>';
                                                    echo '</button>';
                                                } else if ($itemType === 'post') {
                                                    echo '<button type="button" class="premium-btn premium-btn-info premium-btn-sm post-preview-btn" data-post-id="' . htmlspecialchars($item['id']) . '" title="Preview">';
                                                    echo '<i class="fas fa-eye"></i>';
                                                    echo '</button>';
                                                } else if ($itemType === 'game') {
                                                    echo '<button type="button" class="premium-btn premium-btn-info premium-btn-sm game-preview-btn" data-game-id="' . htmlspecialchars($item['id']) . '" title="Preview">';
                                                    echo '<i class="fas fa-eye"></i>';
                                                    echo '</button>';
                                                } else if ($itemType === 'directory_item') {
                                                    echo '<button type="button" class="premium-btn premium-btn-info premium-btn-sm directory-item-preview-btn" data-directory-item-id="' . htmlspecialchars($item['id']) . '" title="Preview">';
                                                    echo '<i class="fas fa-eye"></i>';
                                                    echo '</button>';
                                                } else if ($itemType === 'ai_tool') {
                                                    echo '<button type="button" class="premium-btn premium-btn-info premium-btn-sm ai-tool-preview-btn" data-ai-tool-id="' . htmlspecialchars($item['id']) . '" title="Preview">';
                                                    echo '<i class="fas fa-eye"></i>';
                                                    echo '</button>';
                                                } else if ($itemType === 'author') {
                                                    echo '<button type="button" class="premium-btn premium-btn-info premium-btn-sm author-preview-btn" data-author-id="' . htmlspecialchars($item['id']) . '" title="Preview">';
                                                    echo '<i class="fas fa-eye"></i>';
                                                    echo '</button>';
                                                } else if ($itemType === 'contact') {
                                                    echo '<button type="button" class="premium-btn premium-btn-info premium-btn-sm contact-preview-btn" data-contact-id="' . htmlspecialchars($item['id']) . '" title="Preview">';
                                                    echo '<i class="fas fa-eye"></i>';
                                                    echo '</button>';
                                                } else {
                                                    $actionFile = match($itemType) {
                                                        'ai_tool' => 'ai-tool-form.php',
                                                        'directory_item' => 'directory-item-form.php',
                                                        'game' => 'game-form.php',
                                                        'media' => 'media.php',
                                                        'contact' => 'contacts.php',
                                                        'subscriber' => 'subscribers.php',
                                                        'tag' => 'tag-form.php',
                                                        'author' => 'author-form.php',
                                                        default => "{$itemType}s.php"
                                                    };
                                                    echo '<a href="' . $actionFile . '?id=' . htmlspecialchars($item['id']) . '" class="premium-btn premium-btn-info premium-btn-sm">';
                                                    echo '<i class="fas fa-eye"></i>';
                                                    echo '</a>';
                                                }
                                                ?>
                                            <?php endif; ?>

                                            <?php if (in_array('edit', $options['actions'])): ?>
                                                <?php
                                                // Get the file path based on item type
                                                $actionFile = match($itemType) {
                                                    'ai_tool' => 'ai-tool-form.php',
                                                    'directory_item' => 'directory-item-form.php',
                                                    'game' => 'game-form.php',
                                                    'story' => 'story-form.php',
                                                    'media' => 'media.php',
                                                    'contact' => 'contacts.php',
                                                    'subscriber' => 'subscribers.php',
                                                    'post' => 'post-form.php',
                                                    'tag' => 'tag-form.php',
                                                    'author' => 'author-form.php',
                                                    default => "{$itemType}s.php"
                                                };
                                                echo '<a href="' . $actionFile . '?id=' . htmlspecialchars($item['id']) . '" class="premium-btn premium-btn-primary premium-btn-sm">';
                                                echo '<i class="fas fa-edit"></i>';
                                                echo '</a>';
                                                ?>
                                            <?php endif; ?>

                                            <?php if (in_array('delete', $options['actions'])): ?>
                                                <button type="button" class="premium-btn premium-btn-danger premium-btn-sm delete-item-btn" data-id="<?php echo htmlspecialchars($item['id']); ?>" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if ($totalPages > 1): ?>
            <div class="premium-pagination" style="padding: 1rem; border-top: 1px solid var(--premium-gray-200);">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>" class="premium-pagination-item <?php echo $i === $options['currentPage'] ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize select all functionality
            const selectAllCheckboxes = document.querySelectorAll('.select-all-checkbox');
            const itemCheckboxes = document.querySelectorAll('.item-checkbox');
            const selectedCountElement = document.querySelector('.premium-selected-count');

            selectAllCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const isChecked = this.checked;

                    itemCheckboxes.forEach(itemCheckbox => {
                        itemCheckbox.checked = isChecked;
                    });

                    updateSelectedCount();
                });
            });

            itemCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    updateSelectedCount();

                    // Update select all checkbox
                    const allChecked = Array.from(itemCheckboxes).every(cb => cb.checked);
                    selectAllCheckboxes.forEach(selectAll => {
                        selectAll.checked = allChecked;
                    });
                });
            });

            // Initialize bulk actions
            const applyBulkActionButton = document.getElementById('apply-bulk-action');
            const bulkActionSelect = document.getElementById('bulk-action-select');

            if (applyBulkActionButton && bulkActionSelect) {
                applyBulkActionButton.addEventListener('click', function() {
                    const selectedAction = bulkActionSelect.value;

                    if (!selectedAction) {
                        alert('Please select an action');
                        return;
                    }

                    const selectedItems = Array.from(itemCheckboxes)
                        .filter(checkbox => checkbox.checked)
                        .map(checkbox => checkbox.value);

                    if (selectedItems.length === 0) {
                        alert('Please select at least one item');
                        return;
                    }

                    // Confirm the action
                    if (confirm(`Are you sure you want to ${selectedAction} the selected items?`)) {
                        // Get the item type from the table
                        const table = document.querySelector('table[data-item-type]');
                        const itemType = table ? table.getAttribute('data-item-type') : '';

                        if (!itemType) {
                            console.error('Could not determine item type for bulk action');
                            alert('Error: Could not determine item type for bulk action');
                            return;
                        }

                        console.log(`Performing ${selectedAction} on ${itemType} items:`, selectedItems);

                        // Show loading indicator
                        const loadingIndicator = document.createElement('div');
                        loadingIndicator.className = 'loading-indicator';
                        loadingIndicator.innerHTML = '<div class="spinner-border spinner-border-sm text-light" role="status"><span class="visually-hidden">Loading...</span></div>';
                        document.body.appendChild(loadingIndicator);

                        // Send the bulk action request
                        fetch('../handlers/bulk-action.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: `action=${selectedAction}&item_type=${itemType}&selected_ids=${selectedItems.join(',')}`
                        })
                        .then(response => response.json())
                        .then(data => {
                            // Remove loading indicator
                            loadingIndicator.remove();

                            if (data.success) {
                                // Show success message
                                alert(data.message || 'Bulk action completed successfully');

                                // Reload the page to update the table
                                window.location.reload();
                            } else {
                                // Show error message
                                alert(data.message || 'Failed to perform bulk action');
                            }
                        })
                        .catch(error => {
                            // Remove loading indicator
                            loadingIndicator.remove();

                            console.error('Error:', error);
                            alert('An error occurred while performing the bulk action');
                        });
                    }
                });
            }

            // Initialize delete buttons
            const deleteButtons = document.querySelectorAll('.delete-item-btn');

            deleteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const itemId = this.getAttribute('data-id');

                    if (confirm('Are you sure you want to delete this item?')) {
                        // Get the item type from the table
                        const table = button.closest('table');
                        const itemType = table ? table.getAttribute('data-item-type') : '';

                        if (!itemType) {
                            console.error('Could not determine item type for deletion');
                            alert('Error: Could not determine item type for deletion');
                            return;
                        }

                        // Determine the delete handler URL based on item type
                        const deleteHandlerUrl = `../handlers/delete-${itemType}.php`;

                        console.log(`Deleting ${itemType} with ID ${itemId} using ${deleteHandlerUrl}`);

                        // Show loading indicator
                        const loadingIndicator = document.createElement('div');
                        loadingIndicator.className = 'loading-indicator';
                        loadingIndicator.innerHTML = '<div class="spinner-border spinner-border-sm text-light" role="status"><span class="visually-hidden">Loading...</span></div>';
                        document.body.appendChild(loadingIndicator);

                        // Send the delete request
                        fetch(deleteHandlerUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: `id=${itemId}`
                        })
                        .then(response => response.json())
                        .then(data => {
                            // Remove loading indicator
                            loadingIndicator.remove();

                            if (data.success) {
                                // Show success message
                                alert(data.message || 'Item deleted successfully');

                                // Remove the row from the table
                                const row = button.closest('tr');
                                if (row) {
                                    row.remove();
                                }

                                // Reload the page to update counts and pagination
                                window.location.reload();
                            } else {
                                // Show error message
                                alert(data.message || 'Failed to delete item');
                            }
                        })
                        .catch(error => {
                            // Remove loading indicator
                            loadingIndicator.remove();

                            console.error('Error:', error);
                            alert('An error occurred while deleting the item');
                        });
                    }
                });
            });

            // Helper function to update the selected count
            function updateSelectedCount() {
                if (selectedCountElement) {
                    const selectedCount = Array.from(itemCheckboxes).filter(cb => cb.checked).length;
                    selectedCountElement.textContent = `${selectedCount} item${selectedCount !== 1 ? 's' : ''} selected`;
                }
            }

            // Initialize author preview buttons
            const authorPreviewButtons = document.querySelectorAll('.author-preview-btn');
            authorPreviewButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const authorId = this.getAttribute('data-author-id');

                    // Create modal container
                    const modal = document.createElement('div');
                    modal.className = 'preview-modal';
                    modal.innerHTML = `
                        <div class="preview-modal-content">
                            <div class="preview-modal-header">
                                <h2>Author Preview</h2>
                                <button class="preview-modal-close">&times;</button>
                            </div>
                            <div class="preview-modal-body">
                                <div class="preview-loading">Loading author details...</div>
                                <iframe id="author-preview-frame" style="display:none; width:100%; height:600px; border:none;"></iframe>
                            </div>
                        </div>
                    `;
                    document.body.appendChild(modal);

                    // Add event listener to close button
                    modal.querySelector('.preview-modal-close').addEventListener('click', function() {
                        modal.remove();
                    });

                    // Load author details
                    fetch(`../handlers/get-author.php?id=${authorId}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                const iframe = modal.querySelector('#author-preview-frame');
                                const loading = modal.querySelector('.preview-loading');

                                // Create HTML content for the iframe
                                const html = `
                                    <!DOCTYPE html>
                                    <html>
                                    <head>
                                        <meta charset="UTF-8">
                                        <meta name="viewport" content="width=device-width, initial-scale=1.0">
                                        <title>${data.author.name}</title>
                                        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
                                        <style>
                                            body { padding: 20px; font-family: Arial, sans-serif; }
                                            .author-header { display: flex; align-items: center; margin-bottom: 20px; }
                                            .author-avatar { width: 100px; height: 100px; border-radius: 50%; object-fit: cover; margin-right: 20px; }
                                            .author-name { margin: 0; }
                                            .author-meta { color: #666; margin-top: 5px; }
                                            .author-bio { line-height: 1.6; }
                                            .author-stories { margin-top: 30px; }
                                        </style>
                                    </head>
                                    <body>
                                        <div class="container">
                                            <div class="author-header">
                                                <img src="${data.author.avatar_url || '../assets/images/default-avatar.svg'}" alt="${data.author.name}" class="author-avatar">
                                                <div>
                                                    <h1 class="author-name">${data.author.name}</h1>
                                                    <div class="author-meta">
                                                        ${data.author.author_type ? `<div>Type: ${data.author.author_type}</div>` : ''}
                                                        ${data.author.age ? `<div>Age: ${data.author.age}</div>` : ''}
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="author-bio">
                                                ${data.author.bio || '<p>No biography available.</p>'}
                                            </div>

                                            <div class="author-stories">
                                                <h3>Stories by this author</h3>
                                                ${data.stories && data.stories.length > 0 ?
                                                    `<ul>${data.stories.map(story => `<li>${story.title}</li>`).join('')}</ul>` :
                                                    '<p>No stories found.</p>'}
                                            </div>
                                        </div>
                                    </body>
                                    </html>
                                `;

                                // Set iframe content
                                iframe.onload = function() {
                                    loading.style.display = 'none';
                                    iframe.style.display = 'block';
                                };

                                iframe.srcdoc = html;
                            } else {
                                modal.querySelector('.preview-loading').innerHTML = 'Error loading author details.';
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            modal.querySelector('.preview-loading').innerHTML = 'Error loading author details.';
                        });
                });
            });

            // Initialize contact preview buttons
            const contactPreviewButtons = document.querySelectorAll('.contact-preview-btn');
            contactPreviewButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const contactId = this.getAttribute('data-contact-id');

                    // Create modal container
                    const modal = document.createElement('div');
                    modal.className = 'preview-modal';
                    modal.innerHTML = `
                        <div class="preview-modal-content">
                            <div class="preview-modal-header">
                                <h2>Contact Preview</h2>
                                <button class="preview-modal-close">&times;</button>
                            </div>
                            <div class="preview-modal-body">
                                <div class="preview-loading">Loading contact details...</div>
                                <div id="contact-preview" style="display:none;"></div>
                            </div>
                        </div>
                    `;
                    document.body.appendChild(modal);

                    // Add event listener to close button
                    modal.querySelector('.preview-modal-close').addEventListener('click', function() {
                        modal.remove();
                    });

                    // Load contact details
                    fetch(`../handlers/get-contact.php?id=${contactId}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                const previewDiv = modal.querySelector('#contact-preview');
                                const loading = modal.querySelector('.preview-loading');

                                // Create HTML content
                                previewDiv.innerHTML = `
                                    <div class="card">
                                        <div class="card-header">
                                            <h3>${data.contact.name}</h3>
                                            <div>${data.contact.email}</div>
                                        </div>
                                        <div class="card-body">
                                            <h4>${data.contact.subject}</h4>
                                            <div class="message-content">${data.contact.message}</div>
                                        </div>
                                        <div class="card-footer">
                                            <div class="status">
                                                Status: <span class="badge ${data.contact.is_responded ? 'bg-success' : 'bg-warning'}">
                                                    ${data.contact.is_responded ? 'Responded' : 'Not Responded'}
                                                </span>
                                            </div>
                                            <div class="date">
                                                Received: ${new Date(data.contact.created_at).toLocaleString()}
                                            </div>
                                        </div>
                                    </div>
                                `;

                                // Show the preview
                                loading.style.display = 'none';
                                previewDiv.style.display = 'block';
                            } else {
                                modal.querySelector('.preview-loading').innerHTML = 'Error loading contact details.';
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            modal.querySelector('.preview-loading').innerHTML = 'Error loading contact details.';
                        });
                });
            });
        });
    </script>
    <?php
}

/**
 * Converts an array of items to the format expected by the enhanced table component
 *
 * @param array $items The items to convert
 * @param array $mapping The mapping of database fields to table fields
 * @return array The converted items
 */
function convertItemsForEnhancedTable($items, $mapping) {
    $convertedItems = [];

    foreach ($items as $item) {
        $convertedItem = [];

        foreach ($mapping as $dbField => $tableField) {
            if (isset($item[$dbField])) {
                $convertedItem[$tableField] = $item[$dbField];
            }
        }

        $convertedItems[] = $convertedItem;
    }

    return $convertedItems;
}
?>
