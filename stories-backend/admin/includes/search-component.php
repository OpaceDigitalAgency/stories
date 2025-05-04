<?php
/**
 * Search Component
 *
 * A reusable search component for content listing pages.
 *
 * Usage:
 * include '../includes/search-component.php';
 * renderSearchComponent('stories', ['title', 'content', 'author']);
 */

/**
 * Renders a search component for the specified content type
 *
 * @param string $contentType The type of content to search (e.g., 'stories', 'authors')
 * @param array $searchFields The fields to search in (e.g., ['title', 'content'])
 * @param string $currentSearch The current search query (if any)
 * @return void
 */
function renderSearchComponent($contentType, $searchFields = [], $currentSearch = '') {
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
        'filename' => 'Filename'
    ];

    // Render the search form
    ?>
    <div class="search-container">
        <form method="GET" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="search-form">
            <div class="d-flex gap-2">
                <div class="search-input-container" style="position: relative; flex-grow: 1;">
                    <span class="search-icon" aria-hidden="true">🔍</span>
                    <input
                        type="text"
                        name="search"
                        class="search-input"
                        placeholder="Search <?php echo ucfirst($contentType); ?>..."
                        value="<?php echo htmlspecialchars($currentSearch); ?>"
                        aria-label="Search <?php echo ucfirst($contentType); ?>"
                    >
                </div>

                <select name="search_field" class="form-control" style="width: auto;">
                    <?php foreach ($searchFields as $field): ?>
                        <option value="<?php echo $field; ?>" <?php echo $currentField === $field ? 'selected' : ''; ?>>
                            <?php echo isset($fieldLabels[$field]) ? $fieldLabels[$field] : ucfirst($field); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <button type="submit" class="btn btn-primary">
                    Search
                </button>

                <?php if (!empty($currentSearch)): ?>
                    <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="btn btn-secondary">
                        Clear
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
    <?php
}


