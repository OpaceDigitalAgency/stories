<?php
/**
 * Publisher Dropdown Component
 *
 * This component provides a dropdown of common book publishers.
 */

/**
 * Get a list of common book publishers
 *
 * @return array Array of publisher names
 */
function getCommonPublishers() {
    return [
        'Penguin Random House',
        'HarperCollins Children\'s Books',
        'Simon & Schuster',
        'Hachette Book Group',
        'Macmillan Publishers',
        'Scholastic',
        'Oxford University Press',
        'Cambridge University Press',
        'Bloomsbury Publishing',
        'Orion Children\'s Books',
        'Usborne Publishing',
        'Walker Books',
        'Nosy Crow',
        'Andersen Press',
        'Puffin Books',
        'Egmont Books',
        'Little Tiger Press',
        'Chicken House',
        'Barrington Stoke',
        'Frances Lincoln Children\'s Books'
    ];
}

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
 * Render a publisher dropdown
 *
 * @param PDO $db Database connection
 * @param string $selectedPublisher Currently selected publisher
 * @return string HTML for the publisher dropdown
 */
function renderPublisherDropdown($db, $selectedPublisher = '') {
    // Get common publishers
    $commonPublishers = getCommonPublishers();

    // Get publishers from database
    $dbPublishers = getPublishersFromDatabase($db);

    // Merge and remove duplicates
    $allPublishers = array_unique(array_merge($commonPublishers, $dbPublishers));
    sort($allPublishers);

    $html = '<select id="publisher" name="book_publisher" class="form-control">';
    $html .= '<option value="">Select Publisher</option>';

    foreach ($allPublishers as $publisher) {
        $selected = ($selectedPublisher == $publisher) ? 'selected' : '';
        $html .= '<option value="' . htmlspecialchars($publisher) . '" ' . $selected . '>' . htmlspecialchars($publisher) . '</option>';
    }

    $html .= '<option value="custom">Other (enter manually)</option>';
    $html .= '</select>';

    // Add custom publisher input field
    $customValue = (!empty($selectedPublisher) && !in_array($selectedPublisher, $allPublishers)) ? htmlspecialchars($selectedPublisher) : '';
    $customDisplay = (!empty($selectedPublisher) && !in_array($selectedPublisher, $allPublishers)) ? '' : 'd-none';

    $html .= '<input type="text" id="custom_publisher" name="custom_publisher" class="form-control mt-1 ' . $customDisplay . '"
        placeholder="Enter publisher name" value="' . $customValue . '">';

    return $html;
}
?>
