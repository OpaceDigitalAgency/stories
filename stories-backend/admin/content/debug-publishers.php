<?php
/**
 * Debug Publishers - Check what publishers exist in database
 */

// Include database connection
require_once '../includes/db-connect.php';

// Set JSON header
header('Content-Type: application/json');

try {
    // Get publishers from books table
    $stmt = $db->query("
        SELECT DISTINCT publisher, COUNT(*) as book_count
        FROM books 
        WHERE publisher IS NOT NULL AND publisher != ''
        GROUP BY publisher
        ORDER BY publisher
    ");
    $bookPublishers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get publishers from authors table (those with publisher_id relationships)
    $stmt = $db->query("
        SELECT DISTINCT a.id, a.name,
               (SELECT COUNT(*) FROM books WHERE publisher_id = a.id) as book_count
        FROM authors a
        WHERE a.id IN (SELECT DISTINCT publisher_id FROM books WHERE publisher_id IS NOT NULL)
        AND a.name IS NOT NULL AND a.name != ''
        ORDER BY a.name
    ");
    $authorPublishers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Filter for specific publishers we're looking for
    $harperPublishers = array_filter($bookPublishers, function($pub) {
        return stripos($pub['publisher'], 'harper') !== false || stripos($pub['publisher'], 'collins') !== false;
    });

    $bloomsburyPublishers = array_filter($bookPublishers, function($pub) {
        return stripos($pub['publisher'], 'bloomsbury') !== false;
    });

    $simonPublishers = array_filter($bookPublishers, function($pub) {
        return stripos($pub['publisher'], 'simon') !== false || stripos($pub['publisher'], 'schuster') !== false;
    });

    // Also check authors table
    $harperAuthors = array_filter($authorPublishers, function($pub) {
        return stripos($pub['name'], 'harper') !== false || stripos($pub['name'], 'collins') !== false;
    });

    $bloomsburyAuthors = array_filter($authorPublishers, function($pub) {
        return stripos($pub['name'], 'bloomsbury') !== false;
    });

    $simonAuthors = array_filter($authorPublishers, function($pub) {
        return stripos($pub['name'], 'simon') !== false || stripos($pub['name'], 'schuster') !== false;
    });

    echo json_encode([
        'success' => true,
        'summary' => [
            'total_book_publishers' => count($bookPublishers),
            'total_author_publishers' => count($authorPublishers),
            'harper_in_books' => count($harperPublishers),
            'bloomsbury_in_books' => count($bloomsburyPublishers),
            'simon_in_books' => count($simonPublishers),
            'harper_in_authors' => count($harperAuthors),
            'bloomsbury_in_authors' => count($bloomsburyAuthors),
            'simon_in_authors' => count($simonAuthors)
        ],
        'harper_collins_books' => array_values($harperPublishers),
        'bloomsbury_books' => array_values($bloomsburyPublishers),
        'simon_schuster_books' => array_values($simonPublishers),
        'harper_collins_authors' => array_values($harperAuthors),
        'bloomsbury_authors' => array_values($bloomsburyAuthors),
        'simon_schuster_authors' => array_values($simonAuthors),
        'all_book_publishers' => array_slice($bookPublishers, 0, 50), // First 50 for debugging
        'all_author_publishers' => array_slice($authorPublishers, 0, 50) // First 50 for debugging
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
