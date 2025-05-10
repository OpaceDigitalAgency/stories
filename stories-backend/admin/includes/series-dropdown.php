<?php
/**
 * Series Dropdown Component
 * 
 * This component provides a dropdown of book series.
 */

/**
 * Get a list of common book series
 * 
 * @return array Array of series names
 */
function getCommonSeries() {
    return [
        'Harry Potter',
        'Percy Jackson',
        'The Chronicles of Narnia',
        'A Series of Unfortunate Events',
        'Diary of a Wimpy Kid',
        'The Hunger Games',
        'His Dark Materials',
        'The Lord of the Rings',
        'Winnie-the-Pooh',
        'The Magic School Bus',
        'Goosebumps',
        'The Baby-Sitters Club',
        'Artemis Fowl',
        'The Famous Five',
        'The Secret Seven',
        'Malory Towers',
        'Horrible Histories',
        'Captain Underpants',
        'The Magic Faraway Tree',
        'The Treehouse Series'
    ];
}

/**
 * Get a list of series from the database
 * 
 * @param PDO $db Database connection
 * @return array Array of series names
 */
function getSeriesFromDatabase($db) {
    $seriesList = [];
    
    try {
        // Query unique series from the books table
        $stmt = $db->query("SELECT DISTINCT series FROM books WHERE series IS NOT NULL AND series != '' ORDER BY series");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $seriesList[] = $row['series'];
        }
    } catch (PDOException $e) {
        // Silently fail and return empty array
        error_log("Error fetching series: " . $e->getMessage());
    }
    
    return $seriesList;
}

/**
 * Render a series dropdown
 * 
 * @param PDO $db Database connection
 * @param string $selectedSeries Currently selected series
 * @return string HTML for the series dropdown
 */
function renderSeriesDropdown($db, $selectedSeries = '') {
    // Get common series
    $commonSeries = getCommonSeries();
    
    // Get series from database
    $dbSeries = getSeriesFromDatabase($db);
    
    // Merge and remove duplicates
    $allSeries = array_unique(array_merge($commonSeries, $dbSeries));
    sort($allSeries);
    
    $html = '<select id="series" name="book_series" class="form-control">';
    $html .= '<option value="">Select Series</option>';
    
    foreach ($allSeries as $series) {
        $selected = ($selectedSeries == $series) ? 'selected' : '';
        $html .= '<option value="' . htmlspecialchars($series) . '" ' . $selected . '>' . htmlspecialchars($series) . '</option>';
    }
    
    $html .= '<option value="custom">Other (enter manually)</option>';
    $html .= '</select>';
    
    // Add custom series input field
    $customValue = (!empty($selectedSeries) && !in_array($selectedSeries, $allSeries)) ? htmlspecialchars($selectedSeries) : '';
    $customDisplay = (!empty($selectedSeries) && !in_array($selectedSeries, $allSeries)) ? '' : 'd-none';
    
    $html .= '<input type="text" id="custom_series" name="custom_series" class="form-control mt-1 ' . $customDisplay . '"
        placeholder="Enter series name" value="' . $customValue . '">';
    
    return $html;
}
?>
