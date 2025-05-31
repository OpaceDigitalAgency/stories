<?php
// Test the render_table AJAX endpoint
require_once '../../admin/includes/auth-check.php';
require_once '../../admin/includes/db-connect.php';
require_once '../includes/enhanced-table-component.php';

// Test data
$testBooks = [
    [
        'title' => 'Test Book 1',
        'author' => 'Test Author, illustrated by John Smith',
        'isbn' => '1234567890',
        'isbn13' => '1234567890123',
        'age_range' => '4 to 6 years',
        'year' => '2023',
        'publisher' => 'Test Publisher',
        'language' => 'English',
        'page_count' => '32',
        'reading_level' => 'Beginner'
    ]
];

// Include the helper functions
function cleanAuthorName($author) {
    if (empty($author)) return '';
    
    // Remove "illustrated by" variations
    $author = preg_replace('/,\s*illustrated\s+by\s+[^,]+/i', '', $author);
    $author = preg_replace('/,\s*illus\.\s+[^,]+/i', '', $author);
    $author = preg_replace('/,\s*illustrations\s+by\s+[^,]+/i', '', $author);
    
    return trim($author);
}

function formatAgeRange($ageRange) {
    if (empty($ageRange)) return '';
    
    // Convert "4 to 5 years" to "4-5 years"
    $ageRange = preg_replace('/(\d+)\s+to\s+(\d+)\s+years?/i', '$1-$2 years', $ageRange);
    
    // Convert "4-5 year olds" to "4-5 years"
    $ageRange = preg_replace('/(\d+-\d+)\s+year\s+olds?/i', '$1 years', $ageRange);
    
    // Convert "4+ years" to "4-99 years"
    $ageRange = preg_replace('/(\d+)\+\s+years?/i', '$1-99 years', $ageRange);
    
    return trim($ageRange);
}

function formatPublicationDate($date) {
    if (empty($date)) return '';
    
    $timestamp = strtotime($date);
    if ($timestamp === false) return '';
    
    return date('d/m/Y', $timestamp);
}

echo "<h1>Testing Enhanced Table Rendering</h1>";

try {
    // Format books for enhanced table
    $tableData = [];
    foreach ($testBooks as $index => $book) {
        $tableData[] = [
            'id' => $index,
            'title' => $book['title'] ?? '',
            'author' => cleanAuthorName($book['author'] ?? ''),
            'isbn10' => $book['isbn'] ?? '',
            'isbn13' => $book['isbn13'] ?? '',
            'age' => formatAgeRange($book['age_range'] ?? ''),
            'date' => $book['formatted_date'] ?? ($book['year'] ? "01/01/{$book['year']}" : ''),
            'publisher' => $book['publisher'] ?? '',
            'language' => $book['language'] ?? '',
            'page_count' => $book['page_count'] ?? '',
            'reading_level' => $book['reading_level'] ?? '',
            'status' => '<span class="text-info">Ready</span>'
        ];
    }
    
    // Define columns for enhanced table
    $columns = [
        'title' => 'Title',
        'author' => 'Author',
        'isbn10' => 'ISBN-10',
        'isbn13' => 'ISBN-13',
        'age' => 'Age Range',
        'date' => 'Publication Date',
        'publisher' => 'Publisher',
        'language' => 'Language',
        'page_count' => 'Pages',
        'reading_level' => 'Reading Level',
        'status' => 'Status'
    ];
    
    echo "<h2>Table Data:</h2>";
    echo "<pre>" . print_r($tableData, true) . "</pre>";
    
    echo "<h2>Rendered Table:</h2>";
    
    // Render enhanced table
    renderEnhancedTable(
        $tableData,
        $columns,
        'book',
        'discovery-books-table',
        [
            'showCheckboxes' => true,
            'showActions' => false,
            'bulkActions' => ['import', 'export', 'delete'],
            'htmlFields' => ['status'],
            'showPagination' => true,
            'itemsPerPage' => 25,
            'thumbnailField' => false // No thumbnails for book discovery
        ]
    );
    
} catch (Exception $e) {
    echo "<h2>Error:</h2>";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>