<?php
/**
 * Live Search Component
 *
 * A modern search component that filters table results directly as the user types.
 * This component enhances the user experience by providing instant feedback
 * without requiring page reloads.
 *
 * Usage:
 * include '../includes/live-search-component.php';
 * renderLiveSearchComponent('stories', ['title', 'content', 'author'], 'stories-table');
 */

/**
 * Renders a live search component for the specified content type
 *
 * @param string $contentType The type of content to search (e.g., 'stories', 'authors')
 * @param array $searchFields The fields to search in (e.g., ['title', 'content'])
 * @param string $tableId The ID of the table to filter
 * @param string $currentSearch The current search query (if any)
 * @return void
 */
function renderLiveSearchComponent($contentType, $searchFields = [], $tableId = '', $currentSearch = '') {
    // Get the current search query from GET parameters if not provided
    if (empty($currentSearch) && isset($_GET['search'])) {
        $currentSearch = $_GET['search'];
    }

    // Get the current search field from GET parameters
    $currentField = isset($_GET['search_field']) ? $_GET['search_field'] : 'all';

    // Add "All Fields" option
    array_unshift($searchFields, 'all');

    // Field labels mapping
    $fieldLabels = [
        'all' => 'All Fields',
        'title' => 'Title',
        'name' => 'Name',
        'content' => 'Content',
        'author' => 'Author',
        'tags' => 'Tags',
        'description' => 'Description',
        'slug' => 'Slug',
        'filename' => 'Filename',
        'alt_text' => 'Alt Text',
        'file_type' => 'File Type',
        'email' => 'Email',
        'bio' => 'Bio',
        'website_url' => 'Website URL',
        'contact_email' => 'Contact Email',
        'pricing_type' => 'Pricing Type',
        'category_name' => 'Category'
    ];

    // Render the search form
    ?>
    <div class="premium-search-container">
        <form method="GET" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="premium-search-form" data-content-type="<?php echo htmlspecialchars($contentType); ?>">
            <div class="premium-search-input-container">
                <span class="premium-search-icon" aria-hidden="true">
                    <i class="fas fa-search"></i>
                </span>
                <input
                    type="text"
                    name="search"
                    class="premium-search-input"
                    placeholder="Search <?php echo ucfirst($contentType); ?>..."
                    value="<?php echo htmlspecialchars($currentSearch); ?>"
                    aria-label="Search <?php echo ucfirst($contentType); ?>"
                    autocomplete="off"
                    data-table-id="<?php echo htmlspecialchars($tableId); ?>"
                >
            </div>

            <select name="search_field" class="form-control" style="width: auto;">
                <?php foreach ($searchFields as $field): ?>
                    <option value="<?php echo $field; ?>" <?php echo $currentField === $field ? 'selected' : ''; ?>>
                        <?php echo isset($fieldLabels[$field]) ? $fieldLabels[$field] : ucfirst($field); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="button" class="premium-btn premium-btn-primary" id="clear-search-btn" style="<?php echo empty($currentSearch) ? 'display: none;' : ''; ?>">
                <i class="fas fa-times" aria-hidden="true"></i> Clear
            </button>
        </form>
        
        <div class="premium-row-count" style="margin-top: 0.5rem; font-size: 0.875rem; color: var(--premium-gray-600);"></div>
    </div>

    <script>
        // Initialize the row count
        document.addEventListener('DOMContentLoaded', function() {
            const table = document.getElementById('<?php echo htmlspecialchars($tableId); ?>');
            if (table) {
                const rows = table.querySelectorAll('tbody tr');
                const rowCountElement = document.querySelector('.premium-row-count');
                if (rowCountElement) {
                    rowCountElement.textContent = `Showing ${rows.length} of ${rows.length} items`;
                }
            }
            
            // Add event listener for the clear button
            const clearButton = document.getElementById('clear-search-btn');
            const searchInput = document.querySelector('.premium-search-input');
            
            if (clearButton && searchInput) {
                clearButton.addEventListener('click', function() {
                    searchInput.value = '';
                    
                    // Trigger the input event to clear the filter
                    const event = new Event('input', { bubbles: true });
                    searchInput.dispatchEvent(event);
                    
                    // Hide the clear button
                    clearButton.style.display = 'none';
                });
                
                // Show/hide the clear button based on input
                searchInput.addEventListener('input', function() {
                    clearButton.style.display = this.value.trim() ? '' : 'none';
                });
            }
        });
    </script>
    <?php
}
