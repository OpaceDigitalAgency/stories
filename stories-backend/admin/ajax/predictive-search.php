<?php
/**
 * Predictive Search AJAX Handler
 *
 * This file handles AJAX requests for the predictive search component.
 * It returns search results in JSON format.
 */

// Include database connection
require_once '../includes/db-connect.php';

// Include the predictive search component
require_once '../includes/predictive-search-component.php';

// Set the content type to JSON
header('Content-Type: application/json');

// Check if the request is valid
if (!isset($_GET['query']) || !isset($_GET['content_type'])) {
    echo json_encode(['error' => 'Missing required parameters']);
    exit;
}

// Get the parameters
$query = $_GET['query'];
$contentType = $_GET['content_type'];
$searchField = isset($_GET['search_field']) ? $_GET['search_field'] : 'all';
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 5;

// Validate the limit
if ($limit < 1 || $limit > 20) {
    $limit = 5;
}

// Get the search results
$results = getPredictiveSearchResults($contentType, $query, $searchField, $limit);

// Return the results
echo json_encode($results);
exit;
