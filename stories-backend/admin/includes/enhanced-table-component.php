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
        'thumbnailField' => 'image',
        'thumbnailAltField' => 'title',
        'editableFields' => [],
        'bulkActions' => ['delete'],
        'itemsPerPage' => 10,
        'currentPage' => 1
    ];
    
    // Merge options
    $options = array_merge($defaultOptions, $options);
    
    // Calculate pagination
    $totalItems = count($items);
    $totalPages = ceil($totalItems / $options['itemsPerPage']);
    $startIndex = ($options['currentPage'] - 1) * $options['itemsPerPage'];
    $endIndex = min($startIndex + $options['itemsPerPage'], $totalItems);
    $paginatedItems = array_slice($items, $startIndex, $options['itemsPerPage']);
    
    // Render the table
    ?>
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
                    
                    <?php if (isset($options['thumbnailField'])): ?>
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
                            
                            <?php if (isset($options['thumbnailField']) && isset($item[$options['thumbnailField']])): ?>
                                <td class="thumbnail-column">
                                    <?php
                                    $thumbnailUrl = $item[$options['thumbnailField']];
                                    $thumbnailAlt = isset($item[$options['thumbnailAltField']]) ? $item[$options['thumbnailAltField']] : '';
                                    
                                    // Check if the thumbnail URL is for a full-size image or a thumbnail
                                    if (strpos($thumbnailUrl, '-thumbnail') === false && strpos($thumbnailUrl, '/uploads/') !== false) {
                                        // Convert to thumbnail URL
                                        $pathInfo = pathinfo($thumbnailUrl);
                                        $thumbnailUrl = $pathInfo['dirname'] . '/optimized/' . $pathInfo['filename'] . '-thumbnail.' . $pathInfo['extension'];
                                    }
                                    ?>
                                    <img src="<?php echo htmlspecialchars($thumbnailUrl); ?>" alt="<?php echo htmlspecialchars($thumbnailAlt); ?>" class="thumbnail-image">
                                </td>
                            <?php endif; ?>
                            
                            <?php foreach ($columns as $field => $label): ?>
                                <td>
                                    <?php if (in_array($field, $options['editableFields'])): ?>
                                        <div class="premium-editable" data-field-name="<?php echo htmlspecialchars($field); ?>" data-field-type="text">
                                            <?php echo htmlspecialchars($item[$field] ?? ''); ?>
                                        </div>
                                    <?php else: ?>
                                        <?php echo htmlspecialchars($item[$field] ?? ''); ?>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                            
                            <?php if ($options['showActions']): ?>
                                <td class="actions-column">
                                    <div class="premium-table-actions">
                                        <?php if (in_array('view', $options['actions'])): ?>
                                            <a href="view-<?php echo $itemType; ?>.php?id=<?php echo htmlspecialchars($item['id']); ?>" class="premium-btn premium-btn-info premium-btn-sm" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if (in_array('edit', $options['actions'])): ?>
                                            <a href="<?php echo $itemType; ?>-form.php?id=<?php echo htmlspecialchars($item['id']); ?>" class="premium-btn premium-btn-primary premium-btn-sm" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if (in_array('delete', $options['actions'])): ?>
                                            <button type="button" class="premium-btn premium-btn-danger premium-btn-sm delete-item-btn" data-id="<?php echo htmlspecialchars($item['id']); ?>" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
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
                        // Perform the action
                        console.log(`Performing ${selectedAction} on items:`, selectedItems);
                        
                        // In a real implementation, this would make an AJAX request to the server
                        // For now, we'll just reload the page
                        window.location.reload();
                    }
                });
            }
            
            // Initialize delete buttons
            const deleteButtons = document.querySelectorAll('.delete-item-btn');
            
            deleteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const itemId = this.getAttribute('data-id');
                    
                    if (confirm('Are you sure you want to delete this item?')) {
                        // Perform the delete action
                        console.log(`Deleting item ${itemId}`);
                        
                        // In a real implementation, this would make an AJAX request to the server
                        // For now, we'll just reload the page
                        window.location.reload();
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
