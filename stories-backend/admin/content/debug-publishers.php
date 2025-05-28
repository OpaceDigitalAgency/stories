<?php
/**
 * Debug Publishers - Quick check of what's in the database
 */

// Include auth check
require_once '../includes/auth-check.php';

// Include database connection
require_once '../includes/db-connect.php';

// Set content type for web display
header('Content-Type: text/html; charset=utf-8');

echo '<h1>Publisher Debug Information</h1>';

try {
    // 1. Check what Harper Collins variations exist in books.publisher field
    echo '<h2>1. Harper Collins in books.publisher field:</h2>';
    $stmt = $db->query("
        SELECT DISTINCT publisher, COUNT(*) as book_count
        FROM books 
        WHERE publisher LIKE '%harper%' OR publisher LIKE '%collins%'
        GROUP BY publisher
        ORDER BY publisher
    ");
    $bookPublishers = $stmt->fetchAll();
    
    if (empty($bookPublishers)) {
        echo '<p>No Harper Collins variations found in books.publisher field</p>';
    } else {
        echo '<ul>';
        foreach ($bookPublishers as $pub) {
            echo '<li>' . htmlspecialchars($pub['publisher']) . ' (' . $pub['book_count'] . ' books)</li>';
        }
        echo '</ul>';
    }

    // 2. Check what Harper Collins variations exist in authors table
    echo '<h2>2. Harper Collins in authors table:</h2>';
    $stmt = $db->query("
        SELECT id, name, 
               (SELECT COUNT(*) FROM books WHERE publisher_id = authors.id) as books_by_id,
               (SELECT COUNT(*) FROM books WHERE publisher = authors.name) as books_by_name
        FROM authors 
        WHERE name LIKE '%harper%' OR name LIKE '%collins%'
        ORDER BY name
    ");
    $authorPublishers = $stmt->fetchAll();
    
    if (empty($authorPublishers)) {
        echo '<p>No Harper Collins variations found in authors table</p>';
    } else {
        echo '<ul>';
        foreach ($authorPublishers as $pub) {
            echo '<li>ID: ' . $pub['id'] . ' - ' . htmlspecialchars($pub['name']) . 
                 ' (Books by ID: ' . $pub['books_by_id'] . ', Books by name: ' . $pub['books_by_name'] . ')</li>';
        }
        echo '</ul>';
    }

    // 3. Check what Bloomsbury variations exist
    echo '<h2>3. Bloomsbury variations:</h2>';
    echo '<h3>In books.publisher:</h3>';
    $stmt = $db->query("
        SELECT DISTINCT publisher, COUNT(*) as book_count
        FROM books 
        WHERE publisher LIKE '%bloomsbury%'
        GROUP BY publisher
        ORDER BY publisher
    ");
    $bloomsburyBooks = $stmt->fetchAll();
    
    if (empty($bloomsburyBooks)) {
        echo '<p>No Bloomsbury variations in books.publisher</p>';
    } else {
        echo '<ul>';
        foreach ($bloomsburyBooks as $pub) {
            echo '<li>' . htmlspecialchars($pub['publisher']) . ' (' . $pub['book_count'] . ' books)</li>';
        }
        echo '</ul>';
    }

    echo '<h3>In authors table:</h3>';
    $stmt = $db->query("
        SELECT id, name, 
               (SELECT COUNT(*) FROM books WHERE publisher_id = authors.id) as books_by_id,
               (SELECT COUNT(*) FROM books WHERE publisher = authors.name) as books_by_name
        FROM authors 
        WHERE name LIKE '%bloomsbury%'
        ORDER BY name
    ");
    $bloomsburyAuthors = $stmt->fetchAll();
    
    if (empty($bloomsburyAuthors)) {
        echo '<p>No Bloomsbury variations in authors table</p>';
    } else {
        echo '<ul>';
        foreach ($bloomsburyAuthors as $pub) {
            echo '<li>ID: ' . $pub['id'] . ' - ' . htmlspecialchars($pub['name']) . 
                 ' (Books by ID: ' . $pub['books_by_id'] . ', Books by name: ' . $pub['books_by_name'] . ')</li>';
        }
        echo '</ul>';
    }

    // 4. Check what Simon & Schuster variations exist
    echo '<h2>4. Simon & Schuster variations:</h2>';
    echo '<h3>In books.publisher:</h3>';
    $stmt = $db->query("
        SELECT DISTINCT publisher, COUNT(*) as book_count
        FROM books 
        WHERE publisher LIKE '%simon%' OR publisher LIKE '%schuster%'
        GROUP BY publisher
        ORDER BY publisher
    ");
    $simonBooks = $stmt->fetchAll();
    
    if (empty($simonBooks)) {
        echo '<p>No Simon & Schuster variations in books.publisher</p>';
    } else {
        echo '<ul>';
        foreach ($simonBooks as $pub) {
            echo '<li>' . htmlspecialchars($pub['publisher']) . ' (' . $pub['book_count'] . ' books)</li>';
        }
        echo '</ul>';
    }

    echo '<h3>In authors table:</h3>';
    $stmt = $db->query("
        SELECT id, name, 
               (SELECT COUNT(*) FROM books WHERE publisher_id = authors.id) as books_by_id,
               (SELECT COUNT(*) FROM books WHERE publisher = authors.name) as books_by_name
        FROM authors 
        WHERE name LIKE '%simon%' OR name LIKE '%schuster%'
        ORDER BY name
    ");
    $simonAuthors = $stmt->fetchAll();
    
    if (empty($simonAuthors)) {
        echo '<p>No Simon & Schuster variations in authors table</p>';
    } else {
        echo '<ul>';
        foreach ($simonAuthors as $pub) {
            echo '<li>ID: ' . $pub['id'] . ' - ' . htmlspecialchars($pub['name']) . 
                 ' (Books by ID: ' . $pub['books_by_id'] . ', Books by name: ' . $pub['books_by_name'] . ')</li>';
        }
        echo '</ul>';
    }

    // 5. Show total publisher counts
    echo '<h2>5. Summary:</h2>';
    $stmt = $db->query("SELECT COUNT(DISTINCT publisher) as unique_publishers FROM books WHERE publisher IS NOT NULL AND publisher != ''");
    $uniquePublishers = $stmt->fetchColumn();
    
    $stmt = $db->query("SELECT COUNT(*) as total_authors FROM authors");
    $totalAuthors = $stmt->fetchColumn();
    
    $stmt = $db->query("SELECT COUNT(DISTINCT publisher_id) as publishers_with_relationships FROM books WHERE publisher_id IS NOT NULL");
    $publishersWithRelationships = $stmt->fetchColumn();
    
    echo '<ul>';
    echo '<li>Unique publishers in books.publisher field: ' . $uniquePublishers . '</li>';
    echo '<li>Total authors in authors table: ' . $totalAuthors . '</li>';
    echo '<li>Publishers with relationships (publisher_id set): ' . $publishersWithRelationships . '</li>';
    echo '</ul>';

} catch (Exception $e) {
    echo '<div style="color: red;">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
}
?>
