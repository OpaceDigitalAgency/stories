<?php
header('Content-Type: application/json');

// Make request to stories endpoint
$response = file_get_contents('https://api.storiesfromtheweb.org/api/v1/stories?populate=*');
if ($response === false) {
    die(json_encode(['error' => 'Failed to get response from stories endpoint']));
}

// Decode JSON response
$stories = json_decode($response, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    die(json_encode(['error' => 'Failed to decode JSON response: ' . json_last_error_msg()]));
}

// Verify response is an array
if (!is_array($stories)) {
    die(json_encode(['error' => 'Response should be an array']));
}

// If we have stories, verify the first one
if (count($stories) > 0) {
    $story = $stories[0];
    
    // Required fields to check
    $requiredFields = [
        'id', 'title', 'slug', 'excerpt', 'content', 'publishedAt',
        'featured', 'rating', 'reviewCount', 'estimatedReadingTime',
        'isSponsored', 'ageGroup', 'needsModeration', 'isSelfPublished',
        'isAIEnhanced', 'coverImage', 'tags', 'author'
    ];
    
    // Check all required fields exist
    $missingFields = [];
    foreach ($requiredFields as $field) {
        if (!isset($story[$field])) {
            $missingFields[] = $field;
        }
    }
    
    if (!empty($missingFields)) {
        die(json_encode(['error' => 'Missing required fields: ' . implode(', ', $missingFields)]));
    }
    
    // Check author structure
    if (!isset($story['author']['name']) || !isset($story['author']['slug']) || !isset($story['author']['avatar'])) {
        die(json_encode(['error' => 'Invalid author structure']));
    }
    
    // Verify publishedAt is in ISO-8601 format
    if (!\DateTime::createFromFormat(\DateTime::ISO8601, $story['publishedAt'])) {
        die(json_encode(['error' => 'publishedAt should be in ISO-8601 format']));
    }
    
    // All checks passed
    echo json_encode([
        'success' => true,
        'message' => 'Stories endpoint is returning data in the correct format',
        'example' => $story
    ]);
} else {
    echo json_encode([
        'warning' => 'No stories found to verify format',
        'response' => $stories
    ]);
}