<?php
/**
 * Predictive Search Component
 *
 * A reusable predictive search component for content listing pages.
 * This component enhances the basic search component with real-time
 * search results as the user types.
 *
 * Usage:
 * include '../includes/predictive-search-component.php';
 * renderPredictiveSearchComponent('stories', ['title', 'content', 'author']);
 */

/**
 * Renders a predictive search component for the specified content type
 *
 * @param string $contentType The type of content to search (e.g., 'stories', 'authors')
 * @param array $searchFields The fields to search in (e.g., ['title', 'content'])
 * @param string $currentSearch The current search query (if any)
 * @return void
 */
function renderPredictiveSearchComponent($contentType, $searchFields = [], $currentSearch = '') {
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
    <div class="search-container">
        <form method="GET" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="search-form" data-content-type="<?php echo htmlspecialchars($contentType); ?>">
            <div class="d-flex gap-2">
                <div class="search-input-container" style="position: relative; flex-grow: 1;">
                    <span class="search-icon" aria-hidden="true">
                        <i class="fas fa-search"></i>
                    </span>
                    <input
                        type="text"
                        name="search"
                        class="search-input"
                        placeholder="Search <?php echo ucfirst($contentType); ?>..."
                        value="<?php echo htmlspecialchars($currentSearch); ?>"
                        aria-label="Search <?php echo ucfirst($contentType); ?>"
                        autocomplete="off"
                    >
                    <!-- Predictive search results will be inserted here by JavaScript -->
                    <div class="predictive-search-results"></div>
                </div>

                <select name="search_field" class="form-control" style="width: auto;">
                    <?php foreach ($searchFields as $field): ?>
                        <option value="<?php echo $field; ?>" <?php echo $currentField === $field ? 'selected' : ''; ?>>
                            <?php echo isset($fieldLabels[$field]) ? $fieldLabels[$field] : ucfirst($field); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search" aria-hidden="true"></i> Search
                </button>

                <?php if (!empty($currentSearch)): ?>
                    <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="btn btn-secondary">
                        <i class="fas fa-times" aria-hidden="true"></i> Clear
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
    <?php
}

/**
 * AJAX endpoint for predictive search
 * This function should be called from an AJAX handler file
 *
 * @param string $contentType The type of content to search
 * @param string $query The search query
 * @param string $searchField The field to search in
 * @param int $limit The maximum number of results to return
 * @return array The search results
 */
function getPredictiveSearchResults($contentType, $query, $searchField = 'all', $limit = 5) {
    global $db;
    
    if (!$db) {
        return ['error' => 'Database connection not available'];
    }
    
    if (empty($query)) {
        return ['results' => []];
    }
    
    $results = [];
    $params = [];
    
    try {
        switch ($contentType) {
            case 'stories':
                $whereClause = '';
                
                if ($searchField === 'all') {
                    $whereClause = "WHERE s.title LIKE ? OR s.content LIKE ? OR a.name LIKE ?";
                    $params = ["%$query%", "%$query%", "%$query%"];
                } else if ($searchField === 'title') {
                    $whereClause = "WHERE s.title LIKE ?";
                    $params = ["%$query%"];
                } else if ($searchField === 'content') {
                    $whereClause = "WHERE s.content LIKE ?";
                    $params = ["%$query%"];
                } else if ($searchField === 'author') {
                    $whereClause = "WHERE a.name LIKE ?";
                    $params = ["%$query%"];
                } else if ($searchField === 'tags') {
                    $whereClause = "WHERE t.name LIKE ?";
                    $params = ["%$query%"];
                }
                
                $sql = "
                    SELECT DISTINCT s.id, s.title, s.created_at, 
                           (SELECT GROUP_CONCAT(a.name SEPARATOR ', ') 
                            FROM story_authors sa 
                            JOIN authors a ON sa.author_id = a.id 
                            WHERE sa.story_id = s.id) as author_name
                    FROM stories s
                    LEFT JOIN story_authors sa ON s.id = sa.story_id
                    LEFT JOIN authors a ON sa.author_id = a.id
                    LEFT JOIN story_tags st ON s.id = st.story_id
                    LEFT JOIN tags t ON st.tag_id = t.id
                    $whereClause
                    GROUP BY s.id
                    ORDER BY s.created_at DESC
                    LIMIT ?
                ";
                $params[] = $limit;
                
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                
                while ($row = $stmt->fetch()) {
                    $results[] = [
                        'id' => $row['id'],
                        'title' => $row['title'],
                        'author' => $row['author_name'],
                        'created_at' => $row['created_at'],
                        'type' => 'story'
                    ];
                }
                break;
                
            case 'authors':
                $whereClause = '';
                
                if ($searchField === 'all') {
                    $whereClause = "WHERE name LIKE ? OR email LIKE ? OR bio LIKE ?";
                    $params = ["%$query%", "%$query%", "%$query%"];
                } else if ($searchField === 'name') {
                    $whereClause = "WHERE name LIKE ?";
                    $params = ["%$query%"];
                } else if ($searchField === 'email') {
                    $whereClause = "WHERE email LIKE ?";
                    $params = ["%$query%"];
                } else if ($searchField === 'bio') {
                    $whereClause = "WHERE bio LIKE ?";
                    $params = ["%$query%"];
                }
                
                $sql = "
                    SELECT id, name, email, created_at
                    FROM authors
                    $whereClause
                    ORDER BY name ASC
                    LIMIT ?
                ";
                $params[] = $limit;
                
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                
                while ($row = $stmt->fetch()) {
                    $results[] = [
                        'id' => $row['id'],
                        'name' => $row['name'],
                        'email' => $row['email'],
                        'created_at' => $row['created_at'],
                        'type' => 'author'
                    ];
                }
                break;
                
            case 'blog_posts':
            case 'posts':
                $whereClause = '';
                
                if ($searchField === 'all') {
                    $whereClause = "WHERE title LIKE ? OR content LIKE ?";
                    $params = ["%$query%", "%$query%"];
                } else if ($searchField === 'title') {
                    $whereClause = "WHERE title LIKE ?";
                    $params = ["%$query%"];
                } else if ($searchField === 'content') {
                    $whereClause = "WHERE content LIKE ?";
                    $params = ["%$query%"];
                }
                
                $sql = "
                    SELECT id, title, created_at, author_name
                    FROM blog_posts
                    $whereClause
                    ORDER BY created_at DESC
                    LIMIT ?
                ";
                $params[] = $limit;
                
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                
                while ($row = $stmt->fetch()) {
                    $results[] = [
                        'id' => $row['id'],
                        'title' => $row['title'],
                        'author' => $row['author_name'],
                        'created_at' => $row['created_at'],
                        'type' => 'post'
                    ];
                }
                break;
                
            case 'media':
                $whereClause = '';
                
                if ($searchField === 'all') {
                    $whereClause = "WHERE filename LIKE ? OR alt_text LIKE ? OR file_type LIKE ?";
                    $params = ["%$query%", "%$query%", "%$query%"];
                } else if ($searchField === 'filename') {
                    $whereClause = "WHERE filename LIKE ?";
                    $params = ["%$query%"];
                } else if ($searchField === 'alt_text') {
                    $whereClause = "WHERE alt_text LIKE ?";
                    $params = ["%$query%"];
                } else if ($searchField === 'file_type') {
                    $whereClause = "WHERE file_type LIKE ?";
                    $params = ["%$query%"];
                }
                
                $sql = "
                    SELECT id, filename, alt_text, created_at
                    FROM media
                    $whereClause
                    ORDER BY created_at DESC
                    LIMIT ?
                ";
                $params[] = $limit;
                
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                
                while ($row = $stmt->fetch()) {
                    $results[] = [
                        'id' => $row['id'],
                        'filename' => $row['filename'],
                        'alt_text' => $row['alt_text'],
                        'created_at' => $row['created_at'],
                        'type' => 'media'
                    ];
                }
                break;
                
            // Add more content types as needed
            
            default:
                // Generic search for any table
                $tableName = str_replace('-', '_', $contentType);
                
                // Check if the table exists
                $stmt = $db->query("SHOW TABLES LIKE '$tableName'");
                if ($stmt->rowCount() === 0) {
                    return ['error' => "Table '$tableName' does not exist"];
                }
                
                // Get the table columns
                $columns = [];
                $stmt = $db->query("DESCRIBE $tableName");
                while ($row = $stmt->fetch()) {
                    $columns[] = $row['Field'];
                }
                
                // Determine which columns to search
                $searchColumns = [];
                $titleColumn = in_array('title', $columns) ? 'title' : (in_array('name', $columns) ? 'name' : null);
                $descColumn = in_array('description', $columns) ? 'description' : (in_array('content', $columns) ? 'content' : null);
                
                if ($titleColumn) $searchColumns[] = $titleColumn;
                if ($descColumn) $searchColumns[] = $descColumn;
                
                // If no searchable columns found, return empty results
                if (empty($searchColumns)) {
                    return ['error' => "No searchable columns found in table '$tableName'"];
                }
                
                // Build the WHERE clause
                $whereClause = "WHERE ";
                $whereParts = [];
                
                foreach ($searchColumns as $column) {
                    $whereParts[] = "$column LIKE ?";
                    $params[] = "%$query%";
                }
                
                $whereClause .= implode(" OR ", $whereParts);
                
                // Build the query
                $sql = "
                    SELECT id, " . implode(", ", $searchColumns) . ", created_at
                    FROM $tableName
                    $whereClause
                    ORDER BY created_at DESC
                    LIMIT ?
                ";
                $params[] = $limit;
                
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                
                while ($row = $stmt->fetch()) {
                    $result = [
                        'id' => $row['id'],
                        'type' => $contentType
                    ];
                    
                    if (isset($row[$titleColumn])) {
                        $result['title'] = $row[$titleColumn];
                    }
                    
                    if (isset($row[$descColumn])) {
                        $result['description'] = $row[$descColumn];
                    }
                    
                    if (isset($row['created_at'])) {
                        $result['created_at'] = $row['created_at'];
                    }
                    
                    $results[] = $result;
                }
        }
        
        return ['results' => $results];
    } catch (PDOException $e) {
        return ['error' => $e->getMessage()];
    }
}
