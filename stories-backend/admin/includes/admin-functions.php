<?php
/**
 * Admin Functions
 * 
 * This file contains common functions used across the admin interface.
 */

/**
 * Render a table with the given data and columns
 * 
 * @param array $data The data to display in the table
 * @param array $columns The columns to display (key => label)
 * @param array $options Additional options for the table
 */
function renderTable($data, $columns, $options = []) {
    // Default options
    $defaults = [
        'content_type' => 'items',
        'name_field' => 'name',
        'id_field' => 'id',
        'empty_message' => 'No items found.',
        'actions' => [
            'view' => true,
            'edit' => true,
            'delete' => true
        ],
        'custom_formatters' => [],
        'custom_actions' => null,
        'bulk_actions' => true
    ];
    
    // Merge options with defaults
    $options = array_merge($defaults, $options);
    
    // Start table
    echo '<div class="table-responsive">';
    echo '<table class="table">';
    
    // Table header
    echo '<thead>';
    echo '<tr>';
    
    // Bulk checkbox column if bulk actions are enabled
    if ($options['bulk_actions']) {
        echo '<th class="bulk-checkbox-column">';
        echo '<input type="checkbox" id="select-all" class="form-check-input">';
        echo '</th>';
    }
    
    // Column headers
    foreach ($columns as $key => $label) {
        echo '<th>' . htmlspecialchars($label) . '</th>';
    }
    
    // Actions column
    echo '<th class="actions-column">Actions</th>';
    
    echo '</tr>';
    echo '</thead>';
    
    // Table body
    echo '<tbody>';
    
    // Check if data is empty
    if (empty($data)) {
        echo '<tr><td colspan="' . (count($columns) + 1 + ($options['bulk_actions'] ? 1 : 0)) . '" class="text-center">' . $options['empty_message'] . '</td></tr>';
    } else {
        // Loop through data
        foreach ($data as $item) {
            echo '<tr>';
            
            // Bulk checkbox
            if ($options['bulk_actions']) {
                echo '<td class="bulk-checkbox-column">';
                echo '<input type="checkbox" class="form-check-input bulk-checkbox" name="selected_ids[]" value="' . $item[$options['id_field']] . '">';
                echo '</td>';
            }
            
            // Columns
            foreach ($columns as $key => $label) {
                echo '<td>';
                
                // Check if there's a custom formatter for this column
                if (isset($options['custom_formatters'][$key]) && is_callable($options['custom_formatters'][$key])) {
                    echo $options['custom_formatters'][$key]($item, $key);
                } else {
                    // Default formatting
                    echo isset($item[$key]) ? htmlspecialchars($item[$key]) : '';
                }
                
                echo '</td>';
            }
            
            // Actions
            echo '<td class="actions-column">';
            
            // Check if there's a custom actions renderer
            if (isset($options['custom_actions']) && is_callable($options['custom_actions'])) {
                echo $options['custom_actions']($item);
            } else {
                // Default actions
                if ($options['actions']['view']) {
                    echo '<a href="view-' . $options['content_type'] . '.php?id=' . $item[$options['id_field']] . '" class="btn btn-sm btn-info"><i class="fas fa-eye"></i> View</a> ';
                }
                
                if ($options['actions']['edit']) {
                    echo '<a href="edit-' . $options['content_type'] . '.php?id=' . $item[$options['id_field']] . '" class="btn btn-sm btn-primary"><i class="fas fa-edit"></i> Edit</a> ';
                }
                
                if ($options['actions']['delete']) {
                    echo '<a href="delete-' . $options['content_type'] . '.php?id=' . $item[$options['id_field']] . '" class="btn btn-sm btn-danger" onclick="return confirm(\'Are you sure you want to delete this ' . rtrim($options['content_type'], 's') . '?\');"><i class="fas fa-trash"></i> Delete</a>';
                }
            }
            
            echo '</td>';
            
            echo '</tr>';
        }
    }
    
    echo '</tbody>';
    echo '</table>';
    echo '</div>';
}

/**
 * Render bulk actions component
 * 
 * @param string $contentType The type of content (e.g., 'stories', 'authors')
 * @param array $actions The available actions
 */
function renderBulkActionsComponent($contentType, $actions = ['delete']) {
    echo '<div class="bulk-actions-container">';
    echo '<form id="bulk-actions-form" method="post" action="bulk-' . $contentType . '.php">';
    echo '<div class="bulk-actions">';
    echo '<select id="bulk-action" name="action" class="form-select">';
    echo '<option value="">Bulk Actions</option>';
    
    // Add actions
    foreach ($actions as $action) {
        $label = ucfirst(str_replace('_', ' ', $action));
        echo '<option value="' . $action . '">' . $label . '</option>';
    }
    
    echo '</select>';
    echo '<button type="submit" id="apply-bulk-action" class="btn btn-primary">Apply</button>';
    echo '</div>';
    echo '</form>';
    echo '</div>';
}

/**
 * Format a date for display
 * 
 * @param string $date The date to format
 * @param string $format The format to use (default: 'M d, Y')
 * @return string The formatted date
 */
function formatDate($date, $format = 'M d, Y') {
    if (empty($date)) {
        return '';
    }
    
    return date($format, strtotime($date));
}

/**
 * Truncate text to a specified length
 * 
 * @param string $text The text to truncate
 * @param int $length The maximum length
 * @param string $append The string to append if truncated
 * @return string The truncated text
 */
function truncateText($text, $length = 100, $append = '...') {
    if (strlen($text) <= $length) {
        return $text;
    }
    
    $text = substr($text, 0, $length);
    $text = substr($text, 0, strrpos($text, ' '));
    
    return $text . $append;
}

/**
 * Generate a slug from a string
 * 
 * @param string $string The string to convert to a slug
 * @return string The slug
 */
function generateSlug($string) {
    // Convert to lowercase
    $string = strtolower($string);
    
    // Replace non-alphanumeric characters with hyphens
    $string = preg_replace('/[^a-z0-9]+/', '-', $string);
    
    // Remove leading and trailing hyphens
    $string = trim($string, '-');
    
    return $string;
}

/**
 * Check if a slug is unique
 * 
 * @param string $slug The slug to check
 * @param string $table The table to check against
 * @param string $idField The ID field name
 * @param int $excludeId The ID to exclude from the check
 * @return bool True if the slug is unique, false otherwise
 */
function isSlugUnique($slug, $table, $idField = 'id', $excludeId = null) {
    global $db;
    
    if (!$db) {
        return true; // Can't check, assume it's unique
    }
    
    $query = "SELECT COUNT(*) FROM $table WHERE slug = ?";
    $params = [$slug];
    
    if ($excludeId) {
        $query .= " AND $idField != ?";
        $params[] = $excludeId;
    }
    
    try {
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $count = $stmt->fetchColumn();
        
        return $count === 0;
    } catch (PDOException $e) {
        error_log("Error checking slug uniqueness: " . $e->getMessage());
        return false;
    }
}

/**
 * Create a unique slug
 * 
 * @param string $string The string to convert to a slug
 * @param string $table The table to check against
 * @param string $idField The ID field name
 * @param int $excludeId The ID to exclude from the check
 * @return string The unique slug
 */
function createUniqueSlug($string, $table, $idField = 'id', $excludeId = null) {
    $slug = generateSlug($string);
    $originalSlug = $slug;
    $counter = 1;
    
    while (!isSlugUnique($slug, $table, $idField, $excludeId)) {
        $slug = $originalSlug . '-' . $counter;
        $counter++;
    }
    
    return $slug;
}
