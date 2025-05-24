<?php
/**
 * Data Enrichment Functions
 * 
 * Functions for enriching book data from multiple sources
 */

require_once __DIR__ . '/google-books-validation-functions.php';
require_once __DIR__ . '/open-library-validation-functions.php';

/**
 * Get comprehensive book data from multiple sources for enrichment
 *
 * @param string $title Book title
 * @param string $author Book author
 * @param string $currentISBN Current ISBN (if any) for validation
 * @return array Enriched book data with confidence scores
 */
function getEnrichedBookData($title, $author, $currentISBN = '') {
    $enrichedData = [
        'sources_checked' => [],
        'confidence_score' => 0,
        'isbn_validated' => false,
        'fields' => []
    ];
    
    // Get data from Google Books
    $googleResults = searchBooksByTitleAuthor($title, $author, 5);
    $enrichedData['sources_checked'][] = 'google_books';
    
    // Get data from OpenLibrary
    $openLibraryResults = searchOpenLibraryByTitleAuthor($title, $author, 5);
    $enrichedData['sources_checked'][] = 'open_library';
    
    // Combine and analyze results
    $allResults = array_merge($googleResults, $openLibraryResults);
    
    if (empty($allResults)) {
        return $enrichedData;
    }
    
    // Find the best match based on title/author similarity
    $bestMatch = findBestDataMatch($allResults, $title, $author, $currentISBN);
    
    if ($bestMatch) {
        $enrichedData = extractEnrichmentData($bestMatch, $enrichedData);
        $enrichedData['confidence_score'] = calculateConfidenceScore($bestMatch, $title, $author, $currentISBN);
        
        // Validate ISBN if we have one
        if (!empty($bestMatch['isbn13']) || !empty($bestMatch['isbn'])) {
            $enrichedData['isbn_validated'] = validateISBNMatch($bestMatch, $currentISBN);
        }
    }
    
    return $enrichedData;
}

/**
 * Find the best matching book from search results
 */
function findBestDataMatch($results, $title, $author, $currentISBN) {
    $bestMatch = null;
    $bestScore = 0;
    
    foreach ($results as $result) {
        $score = 0;
        
        // Title matching (most important)
        $titleSimilarity = calculateStringSimilarity($title, $result['title'] ?? '');
        $score += $titleSimilarity * 100;
        
        // Author matching
        $authorSimilarity = calculateStringSimilarity($author, $result['author'] ?? '');
        $score += $authorSimilarity * 50;
        
        // ISBN matching (if we have current ISBN)
        if (!empty($currentISBN)) {
            $resultISBN = $result['isbn13'] ?? $result['isbn'] ?? '';
            if ($resultISBN === $currentISBN) {
                $score += 200; // High bonus for exact ISBN match
            }
        }
        
        // Prefer results with more complete data
        $dataCompleteness = calculateDataCompleteness($result);
        $score += $dataCompleteness * 10;
        
        if ($score > $bestScore) {
            $bestScore = $score;
            $bestMatch = $result;
            $bestMatch['match_score'] = $score;
        }
    }
    
    return $bestMatch;
}

/**
 * Extract enrichment data from the best match
 */
function extractEnrichmentData($match, $enrichedData) {
    $fieldMappings = [
        'isbn' => 'isbn',
        'isbn13' => 'isbn13',
        'author' => 'author',
        'publisher' => 'publisher',
        'publication_date' => 'publication_date',
        'page_count' => 'page_count',
        'language' => 'language',
        'format' => 'format',
        'cover_url' => 'cover_url',
        'preview_link' => 'preview_link',
        'series' => 'series'
    ];
    
    foreach ($fieldMappings as $sourceField => $dbField) {
        if (!empty($match[$sourceField])) {
            $enrichedData['fields'][$dbField] = [
                'value' => $match[$sourceField],
                'source' => $match['source'] ?? 'unknown',
                'confidence' => calculateFieldConfidence($sourceField, $match[$sourceField])
            ];
        }
    }
    
    return $enrichedData;
}

/**
 * Calculate string similarity between two strings
 */
function calculateStringSimilarity($str1, $str2) {
    $str1 = strtolower(trim($str1));
    $str2 = strtolower(trim($str2));
    
    if (empty($str1) || empty($str2)) {
        return 0;
    }
    
    // Use Levenshtein distance for similarity
    $maxLen = max(strlen($str1), strlen($str2));
    if ($maxLen === 0) return 1;
    
    $distance = levenshtein($str1, $str2);
    return 1 - ($distance / $maxLen);
}

/**
 * Calculate data completeness score
 */
function calculateDataCompleteness($data) {
    $importantFields = ['title', 'author', 'publisher', 'publication_date', 'isbn13', 'isbn'];
    $filledFields = 0;
    
    foreach ($importantFields as $field) {
        if (!empty($data[$field])) {
            $filledFields++;
        }
    }
    
    return $filledFields / count($importantFields);
}

/**
 * Calculate confidence score for the overall match
 */
function calculateConfidenceScore($match, $title, $author, $currentISBN) {
    $score = 0;
    $maxScore = 100;
    
    // Title similarity (40% weight)
    $titleSim = calculateStringSimilarity($title, $match['title'] ?? '');
    $score += $titleSim * 40;
    
    // Author similarity (30% weight)
    $authorSim = calculateStringSimilarity($author, $match['author'] ?? '');
    $score += $authorSim * 30;
    
    // Data completeness (20% weight)
    $completeness = calculateDataCompleteness($match);
    $score += $completeness * 20;
    
    // Source reliability (10% weight)
    $sourceScore = ($match['source'] === 'google_books') ? 10 : 8; // Google Books slightly more reliable
    $score += $sourceScore;
    
    return min($score, $maxScore);
}

/**
 * Calculate confidence for individual fields
 */
function calculateFieldConfidence($fieldName, $value) {
    if (empty($value)) return 0;
    
    // Different fields have different reliability
    $fieldReliability = [
        'isbn' => 95,
        'isbn13' => 95,
        'title' => 90,
        'author' => 90,
        'publisher' => 85,
        'publication_date' => 80,
        'page_count' => 75,
        'language' => 70,
        'format' => 70,
        'cover_url' => 60,
        'preview_link' => 60,
        'series' => 50
    ];
    
    return $fieldReliability[$fieldName] ?? 50;
}

/**
 * Validate if the found ISBN matches expectations
 */
function validateISBNMatch($match, $currentISBN) {
    if (empty($currentISBN)) {
        return true; // No current ISBN to validate against
    }
    
    $foundISBN = $match['isbn13'] ?? $match['isbn'] ?? '';
    return $foundISBN === $currentISBN;
}

/**
 * Validate ISBN exists on Goodreads (for review scraping confidence)
 */
function validateISBNOnGoodreads($isbn) {
    if (empty($isbn)) return false;
    
    $searchUrl = "https://www.goodreads.com/search?q=" . urlencode($isbn);
    
    $ch = curl_init($searchUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        return false;
    }
    
    // Check if we got actual results (not a "no results" page)
    return (strpos($response, 'No results') === false && 
            strpos($response, 'bookTitle') !== false);
}
?>
