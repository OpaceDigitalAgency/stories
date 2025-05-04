<?php
/**
 * Bulk Actions Component
 *
 * A reusable bulk actions component for content listing pages.
 *
 * Usage:
 * include '../includes/bulk-actions-component.php';
 * renderBulkActionsComponent('stories', ['delete', 'publish', 'unpublish']);
 */

/**
 * Renders a bulk actions component for the specified content type
 *
 * @param string $contentType The type of content to act on (e.g., 'stories', 'authors')
 * @param array $actions The available actions (e.g., ['delete', 'publish'])
 * @return void
 */
function renderEnhancedBulkActionsComponent($contentType, $actions = []) {
    // Action labels mapping
    $actionLabels = [
        'delete' => 'Delete Selected',
        'publish' => 'Publish Selected',
        'unpublish' => 'Unpublish Selected',
        'feature' => 'Feature Selected',
        'unfeature' => 'Unfeature Selected',
        'tag' => 'Add Tag to Selected',
        'untag' => 'Remove Tag from Selected',
        'export' => 'Export Selected'
    ];

    // Determine the form action URL
    $formAction = "bulk-{$contentType}.php";

    // Render the bulk actions form
    ?>
    <div class="bulk-actions">
        <form method="POST" action="<?php echo htmlspecialchars($formAction); ?>" id="bulk-actions-form">
            <div class="d-flex gap-2 align-items-center">
                <label for="bulk-action" class="form-label mb-0">Bulk Actions:</label>
                <select name="action" id="bulk-action" class="form-control" style="width: auto;">
                    <option value="">-- Select Action --</option>
                    <?php foreach ($actions as $action): ?>
                        <option value="<?php echo $action; ?>">
                            <?php echo isset($actionLabels[$action]) ? $actionLabels[$action] : ucfirst($action); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <button type="submit" class="btn btn-primary" id="apply-bulk-action" disabled>
                    <i class="fas fa-check" aria-hidden="true"></i> Apply
                </button>

                <span class="selected-count" id="selected-count">0 items selected</span>
            </div>

            <div id="bulk-action-options" style="display: none; margin-top: 1rem;">
                <!-- This div will be populated with action-specific options via JavaScript -->
            </div>

            <!-- Hidden container for checkboxes that will be populated by JavaScript -->
            <div id="selected-items-container" style="display: none;"></div>
        </form>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Get elements
        const bulkActionForm = document.getElementById('bulk-actions-form');
        const bulkActionSelect = document.getElementById('bulk-action');
        const applyButton = document.getElementById('apply-bulk-action');
        const selectedCount = document.getElementById('selected-count');
        const bulkActionOptions = document.getElementById('bulk-action-options');
        const selectedItemsContainer = document.getElementById('selected-items-container');
        const checkboxes = document.querySelectorAll('.bulk-checkbox');

        // Update selected count and button state
        function updateSelectedCount() {
            const checkedBoxes = document.querySelectorAll('.bulk-checkbox:checked');
            const checkedCount = checkedBoxes.length;
            selectedCount.textContent = checkedCount + ' items selected';
            applyButton.disabled = checkedCount === 0 || bulkActionSelect.value === '';

            // Update hidden inputs for selected IDs
            selectedItemsContainer.innerHTML = '';
            checkedBoxes.forEach(function(checkbox) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'selected_ids[]';
                input.value = checkbox.value;
                selectedItemsContainer.appendChild(input);
            });
        }

        // Handle bulk action change
        bulkActionSelect.addEventListener('change', function() {
            updateSelectedCount();

            // Show action-specific options if needed
            bulkActionOptions.innerHTML = '';
            bulkActionOptions.style.display = 'none';

            if (this.value === 'tag') {
                // Fetch tags via AJAX and populate the dropdown
                fetch('get-tags.php')
                    .then(response => response.json())
                    .then(tags => {
                        let options = '<option value="">-- Select Tag --</option>';
                        tags.forEach(tag => {
                            options += `<option value="${tag.id}">${tag.name}</option>`;
                        });

                        bulkActionOptions.innerHTML = `
                            <div class="form-group">
                                <label for="tag-id" class="form-label">Select Tag:</label>
                                <select name="tag_id" id="tag-id" class="form-control" required>
                                    ${options}
                                </select>
                            </div>
                        `;
                    })
                    .catch(error => {
                        // Fallback to static options if AJAX fails
                        bulkActionOptions.innerHTML = `
                            <div class="form-group">
                                <label for="tag-id" class="form-label">Select Tag:</label>
                                <select name="tag_id" id="tag-id" class="form-control" required>
                                    <option value="">-- Select Tag --</option>
                                    <option value="1">Tag 1</option>
                                    <option value="2">Tag 2</option>
                                </select>
                            </div>
                        `;
                    });

                bulkActionOptions.style.display = 'block';
            }
        });

        // Handle form submission
        bulkActionForm.addEventListener('submit', function(e) {
            const checkedBoxes = document.querySelectorAll('.bulk-checkbox:checked');
            if (checkedBoxes.length === 0 || bulkActionSelect.value === '') {
                e.preventDefault();
                alert('Please select items and an action to perform.');
                return false;
            }

            // Confirm destructive actions
            if (['delete', 'unpublish', 'unfeature'].includes(bulkActionSelect.value)) {
                if (!confirm('Are you sure you want to ' + bulkActionSelect.value + ' the selected items?')) {
                    e.preventDefault();
                    return false;
                }
            }

            return true;
        });

        // Handle checkbox changes
        checkboxes.forEach(function(checkbox) {
            checkbox.addEventListener('change', updateSelectedCount);
        });

        // Handle "Select All" checkbox
        const selectAllCheckbox = document.getElementById('select-all');
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function() {
                checkboxes.forEach(function(checkbox) {
                    checkbox.checked = selectAllCheckbox.checked;
                });
                updateSelectedCount();
            });
        }

        // Initialize
        updateSelectedCount();
    });
    </script>
    <?php
}


