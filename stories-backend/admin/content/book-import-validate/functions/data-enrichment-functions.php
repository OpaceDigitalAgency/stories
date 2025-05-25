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
        'isbn_validated' => 'unknown',
        'fields' => []
    ];

    // Get data from Google Books
    $googleResults = searchBooksByTitleAuthor($title, $author, 5);
    $enrichedData['sources_checked'][] = 'google_books';

    // Get data from OpenLibrary
    $openLibraryResults = searchOpenLibraryByTitleAuthor($title, $author, 5);
    $enrichedData['sources_checked'][] = 'open_library';

    // Combine and analyze results from both sources
    $combinedData = combineMultiSourceData($googleResults, $openLibraryResults, $title, $author, $currentISBN);

    if (!empty($combinedData)) {
        $enrichedData['fields'] = $combinedData['fields'];
        $enrichedData['confidence_score'] = $combinedData['confidence_score'];
        $enrichedData['isbn_validated'] = $combinedData['isbn_validated'];
    }

    return $enrichedData;
}

/**
 * Combine data from multiple sources intelligently
 */
function combineMultiSourceData($googleResults, $openLibraryResults, $title, $author, $currentISBN) {
    // Define fields that match actual database structure
    $allFields = [
        'isbn' => ['confidence' => 95, 'label' => 'ISBN-10'],
        'isbn13' => ['confidence' => 95, 'label' => 'ISBN-13'],
        'author' => ['confidence' => 90, 'label' => 'Author'],
        'publisher' => ['confidence' => 85, 'label' => 'Publisher'],
        'publication_date' => ['confidence' => 80, 'label' => 'Publication Date'],
        'page_count' => ['confidence' => 75, 'label' => 'Page Count'],
        'language' => ['confidence' => 70, 'label' => 'Language'],
        'format' => ['confidence' => 70, 'label' => 'Format'],
        'cover_url' => ['confidence' => 60, 'label' => 'Cover Image'],
        'preview_link' => ['confidence' => 60, 'label' => 'Preview Link'],
        'series' => ['confidence' => 50, 'label' => 'Series'],
        'tags' => ['confidence' => 45, 'label' => 'Tags'], // Maps from API categories/subjects
        'maturity_rating' => ['confidence' => 55, 'label' => 'Maturity Rating'], // Maps to age_range
        // Fields that don't exist in APIs - always show as unknown
        'price_range' => ['confidence' => 0, 'label' => 'Price Range'],
        'age_range' => ['confidence' => 0, 'label' => 'Age Range'],
        'reading_level' => ['confidence' => 0, 'label' => 'Reading Level'],
        'awards' => ['confidence' => 0, 'label' => 'Awards'],
        'characters' => ['confidence' => 0, 'label' => 'Characters'],
        'settings' => ['confidence' => 0, 'label' => 'Settings']
    ];

    // Find best matches from each source
    $googleMatch = findBestDataMatch($googleResults, $title, $author, $currentISBN);
    $openLibraryMatch = findBestDataMatch($openLibraryResults, $title, $author, $currentISBN);

    $combinedFields = [];
    $maxConfidence = 0;
    $isbnValidated = 'unknown';

    // Process each field
    foreach ($allFields as $fieldName => $fieldConfig) {
        $googleValue = extractFieldValue($googleMatch, $fieldName);
        $openLibraryValue = extractFieldValue($openLibraryMatch, $fieldName);

        // Check if we have data from either source
        if (!empty($googleValue) || !empty($openLibraryValue)) {
            if (!empty($googleValue) && !empty($openLibraryValue)) {
                // Both sources have data - check if they match
                if (normalizeForComparison($googleValue) === normalizeForComparison($openLibraryValue)) {
                    // Values match - use combined source
                    $combinedFields[$fieldName] = [
                        'value' => preferEnglishVersion($googleValue, $openLibraryValue),
                        'source' => 'google_books + open_library',
                        'confidence' => min($fieldConfig['confidence'] + 10, 100), // Boost confidence for matching sources
                        'label' => $fieldConfig['label']
                    ];
                } else {
                    // Values differ - offer both options
                    $combinedFields[$fieldName] = [
                        'options' => [
                            [
                                'value' => $googleValue,
                                'source' => 'google_books',
                                'confidence' => $fieldConfig['confidence'],
                                'label' => $fieldConfig['label']
                            ],
                            [
                                'value' => $openLibraryValue,
                                'source' => 'open_library',
                                'confidence' => $fieldConfig['confidence'] - 5, // Slightly lower confidence for OpenLibrary
                                'label' => $fieldConfig['label']
                            ]
                        ]
                    ];
                }
            } else {
                // Only one source has data
                $value = !empty($googleValue) ? $googleValue : $openLibraryValue;
                $source = !empty($googleValue) ? 'google_books' : 'open_library';
                $confidence = !empty($googleValue) ? $fieldConfig['confidence'] : $fieldConfig['confidence'] - 5;

                $combinedFields[$fieldName] = [
                    'value' => $value,
                    'source' => $source,
                    'confidence' => $confidence,
                    'label' => $fieldConfig['label']
                ];
            }
        } else {
            // No data from either source - show as unknown
            $combinedFields[$fieldName] = [
                'value' => null,
                'source' => 'unknown',
                'confidence' => $fieldConfig['confidence'], // Use base confidence even for unknown
                'label' => $fieldConfig['label'],
                'status' => 'unknown'
            ];
        }

        // Track maximum confidence
        if (isset($combinedFields[$fieldName]['confidence'])) {
            $maxConfidence = max($maxConfidence, $combinedFields[$fieldName]['confidence']);
        }
    }

    // Validate ISBN if we have current ISBN
    if (!empty($currentISBN)) {
        $isbnValidated = validateCombinedISBN($combinedFields, $currentISBN);
    }

    return [
        'fields' => $combinedFields,
        'confidence_score' => $maxConfidence,
        'isbn_validated' => $isbnValidated
    ];
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

/**
 * Normalize values for comparison
 */
function normalizeForComparison($value) {
    if (is_string($value)) {
        return strtolower(trim(preg_replace('/[^a-zA-Z0-9\s]/', '', $value)));
    }
    return $value;
}

/**
 * Prefer English version when comparing values
 */
function preferEnglishVersion($value1, $value2) {
    // Simple heuristic: prefer the value that doesn't contain non-English characters
    $englishPattern = '/^[a-zA-Z0-9\s\-\.\,\:\;\!\?\(\)\'\"]+$/';

    if (preg_match($englishPattern, $value1) && !preg_match($englishPattern, $value2)) {
        return $value1;
    } elseif (!preg_match($englishPattern, $value1) && preg_match($englishPattern, $value2)) {
        return $value2;
    }

    // If both or neither match English pattern, prefer Google Books (first value)
    return $value1;
}

/**
 * Extract field value with special handling for complex fields
 */
function extractFieldValue($match, $fieldName) {
    if (!$match || !is_array($match)) {
        return null;
    }

    // Handle special field mappings and transformations
    switch ($fieldName) {
        case 'tags':
            // Combine categories and subjects from both APIs to create comprehensive tag list
            $allTags = [];

            // Get Google Books categories
            if (isset($match['categories']) && is_array($match['categories'])) {
                $allTags = array_merge($allTags, $match['categories']);
            }

            // Get OpenLibrary subjects
            if (isset($match['subjects']) && is_array($match['subjects'])) {
                $allTags = array_merge($allTags, array_slice($match['subjects'], 0, 10));
            } elseif (isset($match['subject_facet']) && is_array($match['subject_facet'])) {
                $allTags = array_merge($allTags, array_slice($match['subject_facet'], 0, 10));
            }

            // Clean and filter tags
            if (!empty($allTags)) {
                $cleanTags = [];
                foreach ($allTags as $tag) {
                    $cleanTag = trim($tag);
                    if (!empty($cleanTag) && strlen($cleanTag) > 2 && strlen($cleanTag) < 50) {
                        $cleanTags[] = $cleanTag;
                    }
                }

                // Remove duplicates and limit to reasonable number
                $uniqueTags = array_unique($cleanTags);
                return implode(', ', array_slice($uniqueTags, 0, 12));
            }
            break;

        case 'maturity_rating':
            // Google Books: maturityRating, try to map to age ranges
            if (isset($match['maturity_rating'])) {
                return mapMaturityRatingToAgeRange($match['maturity_rating']);
            }
            break;

        case 'language':
            // Normalize language codes
            if (isset($match['language'])) {
                return normalizeLanguage($match['language']);
            }
            break;

        case 'summary':
            // Handle both description and first_sentence
            if (isset($match['summary']) && !empty($match['summary'])) {
                return truncateText($match['summary'], 500);
            }
            break;

        case 'ratings_average':
        case 'ratings_count':
            // Ensure numeric values
            if (isset($match[$fieldName]) && is_numeric($match[$fieldName])) {
                return $match[$fieldName];
            }
            break;

        default:
            // Standard field extraction
            return $match[$fieldName] ?? null;
    }

    return null;
}

/**
 * Map Google Books maturity rating to age range
 */
function mapMaturityRatingToAgeRange($maturityRating) {
    switch (strtoupper($maturityRating)) {
        case 'NOT_MATURE':
            return 'All Ages';
        case 'MATURE':
            return '18+';
        default:
            return $maturityRating;
    }
}

/**
 * Normalize language codes to readable names
 */
function normalizeLanguage($language) {
    $languageMap = [
        'en' => 'English',
        'eng' => 'English',
        'es' => 'Spanish',
        'spa' => 'Spanish',
        'fr' => 'French',
        'fre' => 'French',
        'de' => 'German',
        'ger' => 'German',
        'it' => 'Italian',
        'ita' => 'Italian',
        'pt' => 'Portuguese',
        'por' => 'Portuguese'
    ];

    $lang = strtolower(trim($language));
    return $languageMap[$lang] ?? $language;
}

/**
 * Truncate text to specified length
 */
function truncateText($text, $maxLength = 500) {
    if (strlen($text) <= $maxLength) {
        return $text;
    }

    return substr($text, 0, $maxLength) . '...';
}

/**
 * Validate ISBN against combined data
 */
function validateCombinedISBN($combinedFields, $currentISBN) {
    $currentClean = preg_replace('/[^0-9X]/i', '', $currentISBN);

    // Check if we have ISBN data from sources
    $isbn10 = $combinedFields['isbn']['value'] ?? null;
    $isbn13 = $combinedFields['isbn13']['value'] ?? null;

    if ($isbn10) {
        $isbn10Clean = preg_replace('/[^0-9X]/i', '', $isbn10);
        if ($isbn10Clean === $currentClean) {
            return 'exact_match';
        }
    }

    if ($isbn13) {
        $isbn13Clean = preg_replace('/[^0-9X]/i', '', $isbn13);
        if ($isbn13Clean === $currentClean) {
            return 'exact_match';
        }
    }

    // Check if current ISBN can be converted to match
    if (strlen($currentClean) === 10 && $isbn13) {
        $convertedISBN13 = convertToISBN13($currentClean);
        $isbn13Clean = preg_replace('/[^0-9X]/i', '', $isbn13);
        if ($convertedISBN13 === $isbn13Clean) {
            return 'convertible_match';
        }
    }

    return 'different';
}
