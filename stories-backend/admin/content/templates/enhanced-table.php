<?php
// Define table columns
$columns = [
    'title' => 'Title',
    'author' => 'Author',
    'isbn' => 'ISBN',
    'status' => 'Status',
    'reviews' => 'Reviews',
    'rating' => 'Rating',
    'missing_data' => 'Missing Data',
    'actions' => 'Actions'
];

// Define editable fields
$editableFields = ['title', 'author', 'series', 'publisher'];

// Render enhanced table
renderEnhancedTable(
    $tableData,
    $columns,
    'book',
    'books-table',
    [
        'showCheckboxes' => true,
        'showActions' => true,
        'actions' => ['view', 'edit', 'validate', 'scrape'],
        'editableFields' => $editableFields,
        'bulkActions' => ['delete', 'validate', 'scrape'],
        'itemsPerPage' => $perPage,
        'currentPage' => $page,
        'totalItems' => $totalBooks,
        'htmlFields' => ['rating', 'status', 'missing_data', 'actions'],
        'showPagination' => true,
        'showItemsPerPage' => true,
        'validPerPageValues' => [10, 25, 50, 100, $totalBooks],
        'perPageLabel' => 'Show',
        'showAllLabel' => 'Show All'
    ]
);
?>