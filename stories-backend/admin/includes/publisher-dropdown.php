<?php
/**
 * Publisher Dropdown Component
 *
 * This component provides a dropdown of common book publishers.
 */

// REMOVED: Hard-coded publisher list - now using purely dynamic data from database

/**
 * Get a list of publishers from the database
 *
 * @param PDO $db Database connection
 * @return array Array of publisher names
 */
function getPublishersFromDatabase($db) {
    $publishers = [];

    try {
        // Query unique publishers from the books table
        $stmt = $db->query("SELECT DISTINCT publisher FROM books WHERE publisher IS NOT NULL AND publisher != '' ORDER BY publisher");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $publishers[] = $row['publisher'];
        }
    } catch (PDOException $e) {
        // Silently fail and return empty array
        error_log("Error fetching publishers: " . $e->getMessage());
    }

    return $publishers;
}

/**
 * Render a publisher dropdown - PURELY DYNAMIC from database
 *
 * @param PDO $db Database connection
 * @param string $selectedPublisher Currently selected publisher
 * @return string HTML for the publisher dropdown
 */
function renderPublisherDropdown($db, $selectedPublisher = '') {
    // Get publishers ONLY from database - no hard-coded values
    $dbPublishers = getPublishersFromDatabase($db);

    // Sort alphabetically
    sort($dbPublishers);

    $html = '<select id="publisher" name="book_publisher" class="form-control">';
    $html .= '<option value="">Select Publisher</option>';

    foreach ($dbPublishers as $publisher) {
        $selected = ($selectedPublisher == $publisher) ? 'selected' : '';
        $html .= '<option value="' . htmlspecialchars($publisher) . '" ' . $selected . '>' . htmlspecialchars($publisher) . '</option>';
    }

    $html .= '<option value="custom">Other (enter manually)</option>';
    $html .= '</select>';

    // Add custom publisher input field
    $customValue = (!empty($selectedPublisher) && !in_array($selectedPublisher, $dbPublishers)) ? htmlspecialchars($selectedPublisher) : '';
    $customDisplay = (!empty($selectedPublisher) && !in_array($selectedPublisher, $dbPublishers)) ? '' : 'd-none';

    $html .= '<input type="text" id="custom_publisher" name="custom_publisher" class="form-control mt-1 ' . $customDisplay . '"
        placeholder="Enter publisher name" value="' . $customValue . '">';

    return $html;
}
?>
