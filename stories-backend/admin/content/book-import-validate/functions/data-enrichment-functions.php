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
        return 'new'; // No current ISBN, so any found ISBN is new data
    }

    $foundISBN13 = $match['isbn13'] ?? '';
    $foundISBN10 = $match['isbn'] ?? '';

    // Clean current ISBN for comparison
    $cleanCurrentISBN = preg_replace('/[^0-9X]/i', '', $currentISBN);

    // Check exact matches first
    if ($foundISBN13 === $cleanCurrentISBN || $foundISBN10 === $cleanCurrentISBN) {
        return 'exact_match';
    }

    // Check if they're ISBN-10/ISBN-13 conversions of each other
    if (areISBNsEquivalent($cleanCurrentISBN, $foundISBN13) ||
        areISBNsEquivalent($cleanCurrentISBN, $foundISBN10)) {
        return 'converted_match';
    }

    // Different ISBN entirely
    return 'different';
}

/**
 * Check if two ISBNs are equivalent (ISBN-10 vs ISBN-13 conversion)
 */
function areISBNsEquivalent($isbn1, $isbn2) {
    if (empty($isbn1) || empty($isbn2)) {
        return false;
    }

    // Convert both to ISBN-13 for comparison
    $isbn1_13 = convertToISBN13($isbn1);
    $isbn2_13 = convertToISBN13($isbn2);

    return $isbn1_13 === $isbn2_13;
}

/**
 * Convert ISBN-10 to ISBN-13 (basic conversion)
 */
function convertToISBN13($isbn) {
    $clean = preg_replace('/[^0-9X]/i', '', $isbn);

    if (strlen($clean) === 13) {
        return $clean; // Already ISBN-13
    }

    if (strlen($clean) === 10) {
        // Convert ISBN-10 to ISBN-13
        $isbn13 = '978' . substr($clean, 0, 9);

        // Calculate ISBN-13 checksum
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $digit = intval($isbn13[$i]);
            $sum += ($i % 2 == 0) ? $digit : $digit * 3;
        }
        $checkDigit = (10 - ($sum % 10)) % 10;

        return $isbn13 . $checkDigit;
    }

    return $isbn; // Return as-is if not valid format
}

/**
 * Validate ISBN exists on Goodreads (for review scraping confidence)
 */
function validateISBNOnGoodreads($isbn) {
    if (empty($isbn)) {
        error_log("Goodreads validation: Empty ISBN provided");
        return false;
    }

    // Clean the ISBN
    $cleanISBN = preg_replace('/[^0-9X]/i', '', $isbn);

    if (strlen($cleanISBN) < 10) {
        error_log("Goodreads validation: Invalid ISBN length for $isbn");
        return false;
    }

    $searchUrl = "https://www.goodreads.com/search?q=" . urlencode($cleanISBN);
    error_log("Goodreads validation: Checking URL: $searchUrl");

    $ch = curl_init($searchUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
        'Accept-Language: en-US,en;q=0.5',
        'Accept-Encoding: gzip, deflate',
        'Connection: keep-alive',
        'Upgrade-Insecure-Requests: 1'
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        error_log("Goodreads validation CURL error for $isbn: $error");
        return false;
    }

    if ($httpCode !== 200) {
        error_log("Goodreads validation HTTP error for $isbn: HTTP $httpCode");
        return false;
    }

    if (empty($response)) {
        error_log("Goodreads validation: Empty response for $isbn");
        return false;
    }

    // More comprehensive checks for book existence
    $hasResults = (
        strpos($response, 'No results') === false &&
        strpos($response, 'no results') === false &&
        strpos($response, 'No books found') === false &&
        (
            strpos($response, 'bookTitle') !== false ||
            strpos($response, 'book-title') !== false ||
            strpos($response, 'class="bookTitle"') !== false ||
            strpos($response, 'data-testid="title"') !== false ||
            strpos($response, '/book/show/') !== false
        )
    );

    error_log("Goodreads validation result for $isbn: " . ($hasResults ? 'FOUND' : 'NOT FOUND'));

    return $hasResults;
}
?>
