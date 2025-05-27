<?php
/**
 * Data Enrichment Functions
 *
 * Functions for enriching book data from multiple sources
 */

require_once __DIR__ . '/google-books-validation-functions.php';
require_once __DIR__ . '/open-library-validation-functions.php';
require_once __DIR__ . '/data-enrichment-fixes.php';

/**
 * Fix duplicate location strings like "London, London (England)" -> "London (England)"
 *
 * @param string $location The location string to fix
 * @return string The cleaned location string
 */
function fixDuplicateLocation($location) {
    // Pattern: "City, City (Country)" -> "City (Country)"
    $pattern = '/^([^,]+),\s*\1\s*(\([^)]+\))$/i';
    if (preg_match($pattern, $location, $matches)) {
        return $matches[1] . ' ' . $matches[2];
    }

    // Pattern: "City, City" -> "City" (no parentheses)
    $pattern2 = '/^([^,]+),\s*\1$/i';
    if (preg_match($pattern2, $location, $matches)) {
        return $matches[1];
    }

    return $location;
}

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

    $googleResults = [];
    $openLibraryResults = [];

    // If we have an ISBN, use direct ISBN fetch for enrichment (exact matches only)
    if (!empty($currentISBN)) {
        // Clean the ISBN
        $cleanISBN = preg_replace('/[^0-9X]/i', '', $currentISBN);

        // Get data from Google Books using exact ISBN match
        $googleData = fetchGoogleBooksDataNew($cleanISBN, $title, $author, true); // true = isForEnrichment
        if ($googleData && (!isset($googleData['_status']['status']) || $googleData['_status']['status'] === 'success')) {
            $googleResults = [$googleData];
        }
        $enrichedData['sources_checked'][] = 'google_books';

        // Get data from OpenLibrary using exact ISBN match
        $openLibraryData = fetchOpenLibraryDataNew($cleanISBN, $title, $author, true); // true = isForEnrichment
        if ($openLibraryData && (!isset($openLibraryData['_status']['status']) || $openLibraryData['_status']['status'] === 'success')) {
            $openLibraryResults = [$openLibraryData];
        }
        $enrichedData['sources_checked'][] = 'open_library';
    } else {
        // No ISBN provided, fall back to title/author search
        $googleResults = searchBooksByTitleAuthor($title, $author, 5);
        $enrichedData['sources_checked'][] = 'google_books';

        $openLibraryResults = searchOpenLibraryByTitleAuthor($title, $author, 5);
        $enrichedData['sources_checked'][] = 'open_library';
    }

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
    // Define fields that match actual database structure with enhanced mapping
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
        'tags' => ['confidence' => 50, 'label' => 'Genres'], // Maps from Google categories[] + OpenLibrary subject[] + subject_key[] + subject_facet[]
        'maturity_rating' => ['confidence' => 55, 'label' => 'Maturity Rating'], // Maps to age_range
        'age_range' => ['confidence' => 50, 'label' => 'Age Range'], // Derived from maturity_rating and subjects
        'reading_level' => ['confidence' => 40, 'label' => 'Reading Level'], // From OpenLibrary lexile
        'internet_archive_id' => ['confidence' => 70, 'label' => 'Internet Archive ID'], // From OpenLibrary
        'awards' => ['confidence' => 45, 'label' => 'Awards'], // From OpenLibrary subject_facet
        'characters' => ['confidence' => 40, 'label' => 'Characters'], // From OpenLibrary person
        'settings' => ['confidence' => 40, 'label' => 'Settings'], // From OpenLibrary place
        // Fields that require external sources
        'price_range' => ['confidence' => 0, 'label' => 'Price Range'],
        'alternative_isbns' => ['confidence' => 70, 'label' => 'Alternative ISBNs'],
        'purchase_links' => ['confidence' => 80, 'label' => 'Purchase Links']
    ];

    // Find best matches from each source
    $googleMatch = findBestDataMatch($googleResults, $title, $author, $currentISBN);
    $openLibraryMatch = findBestDataMatch($openLibraryResults, $title, $author, $currentISBN);

    // Validate OpenLibrary match if we have an ISBN - STRICT validation
    if (!empty($currentISBN) && $openLibraryMatch && !validateOpenLibraryISBNMatch($openLibraryMatch, $currentISBN)) {
        error_log("OpenLibrary match rejected - ISBN mismatch. Expected: $currentISBN, Got ISBNs: " . json_encode($openLibraryMatch['isbn'] ?? 'none'));
        $openLibraryMatch = null;
    }

    $combinedFields = [];
    $maxConfidence = 0;
    $isbnValidated = 'unknown';

    // Process each field
    foreach ($allFields as $fieldName => $fieldConfig) {
        $googleValue = extractFieldValue($googleMatch, $fieldName);
        $openLibraryValue = extractFieldValue($openLibraryMatch, $fieldName);

        // Check if we have data from either source
        if (!empty($googleValue) || !empty($openLibraryValue)) {
            // Special handling for tags - always merge them
            if ($fieldName === 'tags') {
                $mergedTags = mergeTagsFromSources($googleValue, $openLibraryValue);
                if (!empty($mergedTags)) {
                    $combinedFields[$fieldName] = [
                        'value' => $mergedTags,
                        'source' => 'google_books + open_library',
                        'confidence' => $fieldConfig['confidence'],
                        'label' => $fieldConfig['label']
                    ];
                }
            } elseif ($fieldName === 'alternative_isbns' && $openLibraryMatch) {
                // Special handling for alternative ISBNs - only from OpenLibrary
                $altISBNs = extractAlternativeISBNs($openLibraryMatch, $currentISBN);
                if (!empty($altISBNs)) {
                    $combinedFields[$fieldName] = [
                        'value' => $altISBNs,
                        'source' => 'open_library',
                        'confidence' => $fieldConfig['confidence'],
                        'label' => $fieldConfig['label']
                    ];
                }
            } elseif (!empty($googleValue) && !empty($openLibraryValue)) {
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
function findBestDataMatch($results, $title, $author, $currentISBN, $currentPublisher = null, $currentDate = null, $currentFormat = null, $currentCountry = null) {
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

        // ISBN matching (if we have current ISBN) - STRICT validation with highest priority
        if (!empty($currentISBN)) {
            $resultISBNs = [];
            $cleanCurrentISBN = preg_replace('/[^0-9X]/i', '', $currentISBN);

            // Collect all ISBNs from the result
            if (isset($result['isbn13'])) {
                $resultISBNs[] = $result['isbn13'];
            }
            if (isset($result['isbn'])) {
                if (is_array($result['isbn'])) {
                    $resultISBNs = array_merge($resultISBNs, $result['isbn']);
                } else {
                    $resultISBNs[] = $result['isbn'];
                }
            }

            // Check for exact match first
            $exactMatch = false;
            $equivalentMatch = false;

            foreach ($resultISBNs as $isbn) {
                if (is_string($isbn)) {
                    $cleanResultISBN = preg_replace('/[^0-9X]/i', '', $isbn);

                    // Exact match (highest priority)
                    if ($cleanResultISBN === $cleanCurrentISBN) {
                        $exactMatch = true;
                        break;
                    }

                    // Equivalent match (ISBN-10 vs ISBN-13)
                    if (areISBNsEquivalent($cleanCurrentISBN, $cleanResultISBN)) {
                        $equivalentMatch = true;
                    }
                }
            }

            if ($exactMatch) {
                $score += 1000; // VERY high bonus for exact ISBN match - this should dominate
                error_log("Exact ISBN match found for $currentISBN in result: " . ($result['title'] ?? 'unknown'));
            } elseif ($equivalentMatch) {
                $score += 800; // High bonus for equivalent ISBN match
                error_log("Equivalent ISBN match found for $currentISBN in result: " . ($result['title'] ?? 'unknown'));
            } else {
                // If no ISBN match, heavily penalize this result - it's probably wrong edition
                $score -= 500;
                error_log("No ISBN match for $currentISBN in result: " . ($result['title'] ?? 'unknown') . " with ISBNs: " . implode(', ', $resultISBNs));
            }
        } else {
            // No current ISBN - use fallback scoring based on other fields
            error_log("No current ISBN available, using fallback scoring for: " . ($result['title'] ?? 'unknown'));

            // Publisher matching (if we have current publisher)
            if (!empty($currentPublisher)) {
                $publisherSimilarity = calculateStringSimilarity($currentPublisher, $result['publisher'] ?? '');
                $score += $publisherSimilarity * 100; // High weight for publisher match
                error_log("Publisher similarity: $publisherSimilarity for '$currentPublisher' vs '" . ($result['publisher'] ?? '') . "'");
            }

            // Publication date matching (if we have current date)
            if (!empty($currentDate)) {
                $resultDate = $result['publication_date'] ?? '';
                if (!empty($resultDate)) {
                    // Extract year for comparison
                    $currentYear = date('Y', strtotime($currentDate));
                    $resultYear = date('Y', strtotime($resultDate));
                    if ($currentYear === $resultYear) {
                        $score += 200; // Good bonus for same year
                        error_log("Publication year match: $currentYear");
                    } elseif (abs($currentYear - $resultYear) <= 1) {
                        $score += 100; // Smaller bonus for close years
                        error_log("Publication year close: $currentYear vs $resultYear");
                    }
                }
            }

            // Format matching (if we have current format)
            if (!empty($currentFormat)) {
                $formatSimilarity = calculateStringSimilarity($currentFormat, $result['format'] ?? '');
                $score += $formatSimilarity * 80; // Good weight for format match
                error_log("Format similarity: $formatSimilarity for '$currentFormat' vs '" . ($result['format'] ?? '') . "'");
            }

            // Country/language matching (if available)
            if (!empty($currentCountry)) {
                $countrySimilarity = calculateStringSimilarity($currentCountry, $result['country'] ?? '');
                $score += $countrySimilarity * 60; // Moderate weight for country match
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
 * Convert ISBN-13 to ISBN-10
 */
function convertISBN13ToISBN10($isbn) {
    $clean = preg_replace('/[^0-9X]/i', '', $isbn);

    if (strlen($clean) === 10) {
        return $clean; // Already ISBN-10
    }

    if (strlen($clean) === 13 && substr($clean, 0, 3) === '978') {
        // Extract the middle 9 digits
        $isbn10 = substr($clean, 3, 9);

        // Calculate ISBN-10 checksum
        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $digit = intval($isbn10[$i]);
            $sum += $digit * (10 - $i);
        }
        $checkDigit = (11 - ($sum % 11)) % 11;

        if ($checkDigit === 10) {
            $checkDigit = 'X';
        } elseif ($checkDigit === 11) {
            $checkDigit = 0;
        }

        return $isbn10 . $checkDigit;
    }

    return null; // Cannot convert non-978 prefix or invalid format
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
 * Validate that OpenLibrary match contains our exact ISBN
 */
function validateOpenLibraryISBNMatch($openLibraryMatch, $currentISBN) {
    if (empty($currentISBN) || empty($openLibraryMatch)) {
        return false;
    }

    $cleanCurrentISBN = preg_replace('/[^0-9X]/i', '', $currentISBN);

    // Check all ISBNs in the OpenLibrary result
    $resultISBNs = [];

    // Get ISBN-13 if available
    if (isset($openLibraryMatch['isbn13'])) {
        $resultISBNs[] = $openLibraryMatch['isbn13'];
    }

    // Get all ISBNs from the isbn array
    if (isset($openLibraryMatch['isbn']) && is_array($openLibraryMatch['isbn'])) {
        $resultISBNs = array_merge($resultISBNs, $openLibraryMatch['isbn']);
    } elseif (isset($openLibraryMatch['isbn']) && is_string($openLibraryMatch['isbn'])) {
        $resultISBNs[] = $openLibraryMatch['isbn'];
    }

    // Check for exact match
    foreach ($resultISBNs as $isbn) {
        if (is_string($isbn)) {
            $cleanResultISBN = preg_replace('/[^0-9X]/i', '', $isbn);
            if ($cleanResultISBN === $cleanCurrentISBN) {
                return true; // Exact match found
            }

            // Also check if they're equivalent (ISBN-10 vs ISBN-13)
            if (areISBNsEquivalent($cleanCurrentISBN, $cleanResultISBN)) {
                return true; // Equivalent match found
            }
        }
    }

    error_log("OpenLibrary ISBN validation failed. Current: $cleanCurrentISBN, Result ISBNs: " . implode(', ', $resultISBNs));
    return false; // No match found
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
        case 'alternative_isbns':
            // Extract all ISBNs from OpenLibrary data except the main one
            if (isset($match['isbn']) && is_array($match['isbn'])) {
                $allIsbns = $match['isbn'];
                // Get the main ISBN (prefer ISBN13, fallback to first ISBN)
                $mainIsbn = $match['isbn13'] ?? $match['isbn'][0] ?? '';

                $alternativeIsbns = [];
                foreach ($allIsbns as $isbn) {
                    if (is_string($isbn) && strlen($isbn) >= 10 && $isbn !== $mainIsbn) {
                        $alternativeIsbns[] = $isbn;
                    }
                }

                // Return formatted for scrollable display
                if (!empty($alternativeIsbns)) {
                    $limitedIsbns = array_slice($alternativeIsbns, 0, 20);
                    return implode(',', $limitedIsbns);
                }
            }
            return null;

        case 'isbn':
            // Return ISBN-10 specifically - convert from ISBN-13 if needed
            if (isset($match['isbn']) && is_array($match['isbn'])) {
                // Find the first 10-digit ISBN
                foreach ($match['isbn'] as $isbn) {
                    if (is_string($isbn) && strlen(preg_replace('/[^0-9X]/i', '', $isbn)) === 10) {
                        return $isbn;
                    }
                }
                // If no 10-digit found, convert from 13-digit
                foreach ($match['isbn'] as $isbn) {
                    if (is_string($isbn) && strlen(preg_replace('/[^0-9X]/i', '', $isbn)) === 13) {
                        $isbn10 = convertISBN13ToISBN10($isbn);
                        if ($isbn10) {
                            return $isbn10;
                        }
                    }
                }
                // Fallback to first ISBN
                return !empty($match['isbn']) ? $match['isbn'][0] : null;
            } elseif (isset($match['isbn']) && is_string($match['isbn'])) {
                $cleanIsbn = preg_replace('/[^0-9X]/i', '', $match['isbn']);
                if (strlen($cleanIsbn) === 10) {
                    return $match['isbn'];
                } elseif (strlen($cleanIsbn) === 13) {
                    return convertISBN13ToISBN10($match['isbn']);
                }
                return $match['isbn'];
            } elseif (isset($match['isbn13']) && !empty($match['isbn13'])) {
                // Convert ISBN-13 to ISBN-10
                return convertISBN13ToISBN10($match['isbn13']);
            }
            return null;

        case 'isbn13':
            // Return only the main ISBN13
            if (isset($match['isbn13']) && !empty($match['isbn13'])) {
                return $match['isbn13'];
            } elseif (isset($match['isbn']) && is_array($match['isbn'])) {
                // Find the first 13-digit ISBN
                foreach ($match['isbn'] as $isbn) {
                    if (is_string($isbn) && strlen(preg_replace('/[^0-9X]/i', '', $isbn)) === 13) {
                        return $isbn;
                    }
                }
                // Convert from 10-digit if available
                foreach ($match['isbn'] as $isbn) {
                    if (is_string($isbn) && strlen(preg_replace('/[^0-9X]/i', '', $isbn)) === 10) {
                        return convertToISBN13($isbn);
                    }
                }
                // Fallback to first ISBN if no 13-digit found
                return !empty($match['isbn']) ? $match['isbn'][0] : null;
            } elseif (isset($match['isbn']) && is_string($match['isbn'])) {
                $cleanIsbn = preg_replace('/[^0-9X]/i', '', $match['isbn']);
                if (strlen($cleanIsbn) === 13) {
                    return $match['isbn'];
                } elseif (strlen($cleanIsbn) === 10) {
                    return convertToISBN13($match['isbn']);
                }
                return $match['isbn'];
            }
            return null;

        case 'tags':
            // Merge tags from all sources and deduplicate
            $allTags = [];

            // Get Google Books categories
            if (isset($match['categories']) && is_array($match['categories'])) {
                foreach ($match['categories'] as $category) {
                    if (is_string($category)) {
                        $allTags[] = trim($category);
                    }
                }
            }

            // Get OpenLibrary subjects
            if (isset($match['subject']) && is_array($match['subject'])) {
                foreach ($match['subject'] as $subject) {
                    if (is_string($subject)) {
                        $allTags[] = trim($subject);
                    }
                }
            }

            // Get OpenLibrary subject_key
            if (isset($match['subject_key']) && is_array($match['subject_key'])) {
                foreach ($match['subject_key'] as $key) {
                    if (is_string($key)) {
                        $formatted = ucwords(str_replace('_', ' ', trim($key)));
                        $allTags[] = $formatted;
                    }
                }
            }

            // Get OpenLibrary subject_facet
            if (isset($match['subject_facet']) && is_array($match['subject_facet'])) {
                foreach ($match['subject_facet'] as $facet) {
                    if (is_string($facet)) {
                        $allTags[] = trim($facet);
                    }
                }
            }

            // Clean, normalize and deduplicate tags
            if (!empty($allTags)) {
                $cleanTags = [];
                $ageTermsToExclude = [
                    'children\'s books/ages 9-12 fiction',
                    'tweens',
                    'young adult fiction',
                    'ages 9-12',
                    '9-12',
                    '8-12',
                    '12+',
                    'all ages'
                ];

                foreach ($allTags as $tag) {
                    $cleanTag = trim($tag);
                    if (empty($cleanTag) || strlen($cleanTag) <= 2 || strlen($cleanTag) > 100) {
                        continue;
                    }

                    $lowerTag = strtolower($cleanTag);

                    // Skip age-related terms
                    $isAgeRelated = false;
                    foreach ($ageTermsToExclude as $ageTerm) {
                        if (stripos($lowerTag, $ageTerm) !== false) {
                            $isAgeRelated = true;
                            break;
                        }
                    }

                    if (!$isAgeRelated) {
                        // Normalize capitalization
                        $normalizedTag = ucwords(strtolower($cleanTag));

                        // Check for duplicates (case-insensitive)
                        $isDuplicate = false;
                        foreach ($cleanTags as $existingTag) {
                            if (strtolower($existingTag) === strtolower($normalizedTag)) {
                                $isDuplicate = true;
                                break;
                            }
                        }

                        if (!$isDuplicate) {
                            $cleanTags[] = $normalizedTag;
                        }
                    }
                }

                // Limit to reasonable number and return
                return !empty($cleanTags) ? implode(', ', array_slice($cleanTags, 0, 15)) : null;
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

        case 'format':
            // For exact ISBN matches, return the specific format, not multiple formats
            // Each ISBN should correspond to one specific format
            if (isset($match['format'])) {
                if (is_string($match['format'])) {
                    // Single format - normalize and return
                    return normalizeFormat(trim($match['format']));
                } elseif (is_array($match['format']) && !empty($match['format'])) {
                    // Multiple formats - this suggests the match might be for multiple editions
                    // For exact ISBN matches, we should only get one format
                    // Take the first format and normalize it
                    $firstFormat = $match['format'][0];
                    error_log("Warning: Multiple formats found for exact ISBN match: " . implode(', ', $match['format']) . ". Using first: $firstFormat");
                    return normalizeFormat(trim($firstFormat));
                }
            }
            return null;

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

        case 'age_range':
            // Open Library subject_facet[] contains specific patterns
            $ageRange = null;

            if (isset($match['subject_facet']) && is_array($match['subject_facet'])) {
                foreach ($match['subject_facet'] as $subject) {
                    if (stripos($subject, "Children's Books/Ages 9-12 Fiction") !== false) {
                        $ageRange = '9-12'; // Match exact current database value
                        break;
                    } elseif (stripos($subject, 'Tweens') !== false) {
                        $ageRange = '8-12'; // Match exact database value
                        break;
                    } elseif (stripos($subject, 'Young Adult Fiction') !== false) {
                        $ageRange = '12+'; // Match exact database value
                        break;
                    }
                }
            }

            // Else fallback from maturity_rating
            if (!$ageRange && isset($match['maturity_rating'])) {
                $maturityRating = $match['maturity_rating'];
                if ($maturityRating === 'NOT_MATURE') {
                    $ageRange = 'All Ages'; // Keep as All Ages for NOT_MATURE
                } elseif ($maturityRating === 'MATURE') {
                    $ageRange = 'Adult'; // Match exact database value
                }
            }

            return $ageRange;

        case 'reading_level':
            // Open Library: use lexile[] with conversion to readable format
            if (isset($match['lexile']) && is_array($match['lexile']) && !empty($match['lexile'])) {
                $lexileValue = $match['lexile'][0];
                return convertLexileToReadingLevel($lexileValue);
            }
            // Also check if lexile is a direct value
            elseif (isset($match['lexile']) && is_numeric($match['lexile'])) {
                return convertLexileToReadingLevel($match['lexile']);
            }
            break;

        case 'average_rating':
            // Get average rating from OpenLibrary
            if (isset($match['ratings_average'])) {
                return round($match['ratings_average'], 2);
            }
            break;

        case 'rating_count':
            // Get rating count from OpenLibrary
            if (isset($match['ratings_count'])) {
                return $match['ratings_count'];
            }
            break;

        case 'internet_archive_id':
            // Get Internet Archive ID from OpenLibrary
            if (isset($match['lending_identifier_s'])) {
                return $match['lending_identifier_s'];
            } elseif (isset($match['ia']) && is_array($match['ia']) && !empty($match['ia'])) {
                return $match['ia'][0];
            }
            break;

        case 'awards':
            // Extract awards from OpenLibrary subject_facet
            $awards = [];
            if (isset($match['subject_facet']) && is_array($match['subject_facet'])) {
                foreach ($match['subject_facet'] as $subject) {
                    if (stripos($subject, 'award:hugo_award=2003') !== false) {
                        $awards[] = "Hugo Award (2003)";
                    } elseif (stripos($subject, 'award:hugo_award=novella') !== false) {
                        $awards[] = "Hugo Award (Novella)";
                    } elseif (stripos($subject, 'Hugo Award Winner') !== false) {
                        $awards[] = "Hugo Award Winner";
                    } elseif (stripos($subject, 'Newbery') !== false ||
                             stripos($subject, 'Caldecott') !== false) {
                        $awards[] = $subject;
                    }
                }
            }
            return !empty($awards) ? implode(', ', array_unique($awards)) : null;

        case 'characters':
            // Get characters from OpenLibrary person_facet data
            if (isset($match['person_facet']) && is_array($match['person_facet'])) {
                return implode(', ', array_slice($match['person_facet'], 0, 5));
            }
            // Fallback to person[] if person_facet not available
            elseif (isset($match['person']) && is_array($match['person'])) {
                return implode(', ', array_slice($match['person'], 0, 5));
            }
            break;

        case 'settings':
            // Use Open Library place_facet[] - simple processing
            $places = [];
            if (isset($match['place_facet']) && is_array($match['place_facet'])) {
                $places = $match['place_facet'];
            } elseif (isset($match['place']) && is_array($match['place'])) {
                $places = $match['place'];
            }

            if (!empty($places)) {
                $cleanPlaces = [];
                foreach ($places as $place) {
                    if (!is_string($place)) {
                        $place = (string) $place;
                    }
                    $place = trim($place);
                    if (!empty($place)) {
                        // Fix "London, London (England)" -> "London (England)"
                        $place = fixDuplicateLocation($place);
                        $cleanPlaces[] = ucwords(strtolower($place));
                    }
                }

                $uniquePlaces = array_unique($cleanPlaces);
                return implode(', ', array_slice($uniquePlaces, 0, 3));
            }
            break;

        case 'publisher':
            // Normalize publisher and get/create publisher_id
            if (isset($match['publisher']) && is_array($match['publisher']) && !empty($match['publisher'])) {
                $publisherName = $match['publisher'][0];
            } elseif (isset($match['publisher']) && is_string($match['publisher'])) {
                $publisherName = $match['publisher'];
            } else {
                return null;
            }

            // Clean and normalize publisher name
            $publisherName = normalizePublisherName($publisherName);
            return $publisherName;

        case 'price_range':
            // Scrape price from Amazon UK using ISBN
            if (isset($match['isbn13'])) {
                $isbn = $match['isbn13'];
            } elseif (isset($match['isbn']) && is_string($match['isbn'])) {
                $isbn = $match['isbn'];
            } elseif (isset($match['isbn']) && is_array($match['isbn']) && !empty($match['isbn'])) {
                // Handle OpenLibrary array format
                $isbn = is_array($match['isbn'][0]) ? $match['isbn'][0] : $match['isbn'][0];
            } else {
                return null;
            }

            // Use enhanced price scraping with fallbacks
            $priceRange = scrapePriceFromAmazonEnhanced($isbn);
            error_log("Price range result for ISBN " . (is_array($isbn) ? json_encode($isbn) : $isbn) . ": " . ($priceRange ?? 'null'));
            return $priceRange;

        case 'purchase_links':
            // Generate enhanced purchase links with edition-specific data
            $isbn13 = $match['isbn13'] ?? null;
            $isbn = $match['isbn'] ?? null;

            if (is_array($isbn) && !empty($isbn)) {
                $isbn = $isbn[0];
            }

            $purchaseLinks = [];

            if ($isbn13 || $isbn) {
                $mainIsbn = $isbn13 ?: $isbn;

                // Amazon UK link
                $purchaseLinks['amazon_uk'] = "https://www.amazon.co.uk/dp/" . $mainIsbn;

                // Goodreads link
                $purchaseLinks['goodreads'] = "https://www.goodreads.com/book/isbn/" . $mainIsbn;

                // Google Books link
                $purchaseLinks['google_books'] = "https://books.google.com/books?isbn=" . $mainIsbn;

                // Add edition information if available
                if (isset($match['publisher']) && !empty($match['publisher'])) {
                    $publisher = is_array($match['publisher']) ? $match['publisher'][0] : $match['publisher'];
                    $purchaseLinks['_edition_info'] = [
                        'publisher' => $publisher,
                        'isbn13' => $isbn13,
                        'isbn' => $isbn
                    ];
                }
            }

            return !empty($purchaseLinks) ? json_encode($purchaseLinks) : null;

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

    // Handle array of languages - prioritize English
    if (is_array($language)) {
        if (empty($language)) {
            return 'Unknown';
        }

        // Look for English variants first
        foreach ($language as $lang) {
            $langCode = strtolower(trim($lang));
            if (in_array($langCode, ['en', 'eng', 'english'])) {
                return 'English';
            }
        }

        // If no English found, take the first language
        $language = $language[0];
    } elseif (!is_string($language)) {
        $language = (string) $language;
    }

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
 * Normalize format strings
 */
function normalizeFormat($format) {
    if (empty($format)) {
        return null;
    }

    $format = trim($format);
    $formatMap = [
        'hardcover' => 'Hardcover',
        'hardback' => 'Hardcover',
        'gebundene ausgabe' => 'Hardcover',
        'paperback' => 'Paperback',
        'softcover' => 'Paperback',
        'brossura' => 'Paperback',
        'kindle' => 'Kindle',
        'ebook' => 'eBook',
        'e-book' => 'eBook',
        'electronic resource' => 'eBook',
        'audio cd' => 'Audio CD',
        'audiobook' => 'Audiobook',
        'audio cassette' => 'Audio Cassette',
        'library binding' => 'Library Binding',
        'school & library binding' => 'Library Binding',
        'mass market paperback' => 'Mass Market Paperback'
    ];

    $lowerFormat = strtolower($format);
    return $formatMap[$lowerFormat] ?? ucwords($format);
}

/**
 * Convert Lexile reading level to readable format
 */
function convertLexileToReadingLevel($lexileValue) {
    if (!is_numeric($lexileValue)) {
        return $lexileValue . 'L';
    }

    $lexile = (int) $lexileValue;

    // Convert Lexile to reading level categories
    if ($lexile < 200) {
        return 'Beginning Reader';
    } elseif ($lexile < 400) {
        return 'Early Reader';
    } elseif ($lexile < 600) {
        return 'Elementary';
    } elseif ($lexile < 800) {
        return 'Middle Grade';
    } elseif ($lexile < 1000) {
        return 'Young Adult';
    } else {
        return 'Advanced';
    }
}

/**
 * Deduplicate location strings (e.g., "London, London (England)" -> "London (England)")
 */
function deduplicateLocation($location) {
    if (empty($location)) {
        return $location;
    }

    // Handle concatenated duplicates like "LondonLondon (england)" -> "London"
    if (preg_match('/^([a-zA-Z]+)\1\s*\([^)]*\)$/i', $location, $matches)) {
        return trim($matches[1]);
    }

    // Handle patterns like "London, London (England)"
    if (preg_match('/^([^,]+),\s*\1\s*\(([^)]+)\)$/', $location, $matches)) {
        return $matches[1] . ' (' . $matches[2] . ')';
    }

    // Handle patterns like "New York, New York"
    if (preg_match('/^([^,]+),\s*\1$/', $location, $matches)) {
        return $matches[1];
    }

    return $location;
}

/**
 * Merge tags from multiple sources
 */
function mergeTagsFromSources($googleTags, $openLibraryTags) {
    $allTags = [];

    // Add Google Books tags
    if (!empty($googleTags)) {
        if (is_string($googleTags)) {
            $allTags = array_merge($allTags, explode(', ', $googleTags));
        } elseif (is_array($googleTags)) {
            $allTags = array_merge($allTags, $googleTags);
        }
    }

    // Add OpenLibrary tags
    if (!empty($openLibraryTags)) {
        if (is_string($openLibraryTags)) {
            $allTags = array_merge($allTags, explode(', ', $openLibraryTags));
        } elseif (is_array($openLibraryTags)) {
            $allTags = array_merge($allTags, $openLibraryTags);
        }
    }

    // Deduplicate and clean
    $uniqueTags = [];
    foreach ($allTags as $tag) {
        $cleanTag = trim($tag);
        if (!empty($cleanTag) && !in_array(strtolower($cleanTag), array_map('strtolower', $uniqueTags))) {
            $uniqueTags[] = $cleanTag;
        }
    }

    return !empty($uniqueTags) ? implode(', ', array_slice($uniqueTags, 0, 15)) : null;
}

/**
 * Extract alternative ISBNs from OpenLibrary data
 */
function extractAlternativeISBNs($openLibraryMatch, $currentISBN) {
    if (!isset($openLibraryMatch['isbn']) || !is_array($openLibraryMatch['isbn'])) {
        return null;
    }

    $cleanCurrentISBN = preg_replace('/[^0-9X]/i', '', $currentISBN);
    $alternativeISBNs = [];

    foreach ($openLibraryMatch['isbn'] as $isbn) {
        if (is_string($isbn)) {
            $cleanISBN = preg_replace('/[^0-9X]/i', '', $isbn);
            // Only include ISBNs that are different from current
            if ($cleanISBN !== $cleanCurrentISBN && strlen($cleanISBN) >= 10) {
                $alternativeISBNs[] = $isbn;
            }
        }
    }

    return !empty($alternativeISBNs) ? implode(',', array_slice($alternativeISBNs, 0, 20)) : null;
}



/**
 * Normalize publisher name
 */
function normalizePublisherName($publisherName) {
    // Clean up common publisher name variations
    $publisherName = trim($publisherName);

    // Remove common suffixes that create duplicates
    $suffixesToRemove = [
        ', an imprint of Random House Children\'s Books',
        ', an imprint of Random House',
        ' Children\'s Books',
        ' Publishing',
        ' Publishers',
        ' Ltd',
        ' Limited',
        ' Inc',
        ' LLC'
    ];

    foreach ($suffixesToRemove as $suffix) {
        if (stripos($publisherName, $suffix) !== false) {
            $publisherName = str_ireplace($suffix, '', $publisherName);
            break; // Only remove one suffix to avoid over-cleaning
        }
    }

    return trim($publisherName);
}

/**
 * Get or create publisher ID from normalized name
 */
function getOrCreatePublisherId($publisherName) {
    global $pdo;

    if (empty($publisherName)) {
        return null;
    }

    $normalizedName = normalizePublisherName($publisherName);

    // Check if publisher exists
    $stmt = $pdo->prepare("SELECT id FROM publishers WHERE name = ?");
    $stmt->execute([$normalizedName]);
    $publisher = $stmt->fetch();

    if ($publisher) {
        return $publisher['id'];
    }

    // Create new publisher
    $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $normalizedName));
    $slug = trim($slug, '-');

    $stmt = $pdo->prepare("INSERT INTO publishers (name, slug, created_at, updated_at) VALUES (?, ?, NOW(), NOW())");
    $stmt->execute([$normalizedName, $slug]);

    return $pdo->lastInsertId();
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

/**
 * Scrape price from Amazon UK using Google Search
 * Query: amazon.co.uk [ISBN] (without site: prefix)
 * Example SERP: '£7.35 · In stock · 4.7(4,507) · £2.99 delivery · 30-day returns'
 */
function scrapePriceFromAmazon($isbn) {
    if (empty($isbn)) {
        return null;
    }

    try {
        // Clean ISBN
        $cleanISBN = preg_replace('/[^0-9X]/i', '', $isbn);

        // Search Google for Amazon UK results
        $query = "amazon.co.uk " . $cleanISBN;
        $searchUrl = "https://www.google.com/search?q=" . urlencode($query);

        error_log("Price scraping: Searching for '$query' at $searchUrl");

        $ch = curl_init($searchUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Language: en-GB,en;q=0.5',
            'Accept-Encoding: gzip, deflate',
            'Connection: keep-alive',
            'Cache-Control: no-cache',
            'Pragma: no-cache'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || empty($response)) {
            error_log("Amazon price scraping failed for ISBN $isbn: HTTP $httpCode");
            return null;
        }

        // Handle gzip compressed response
        if (substr($response, 0, 3) === "\x1f\x8b\x08") {
            $response = gzdecode($response);
            if ($response === false) {
                error_log("Failed to decompress gzip response for ISBN $isbn");
                return null;
            }
        }

        // Look for structured price patterns in Google search results
        // Pattern examples: '£7.35 · In stock', '£7.35 · Available', '£7.35 · 4.7(4,507)'
        $patterns = [
            '/£(\d+)\.(\d{2})\s*·\s*(in stock|available)/i',
            '/£(\d+)\.(\d{2})\s*·\s*\d+\.\d+\(\d+\)/i', // Price with rating
            '/£(\d+)\.(\d{2})\s*·.*?(delivery|returns)/i', // Price with delivery info
            '/£(\d+)\.(\d{2})\s*(?:·|,|\s).*?amazon\.co\.uk/i' // Price near amazon.co.uk
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $response, $priceMatches)) {
                $price = floatval($priceMatches[1] . '.' . $priceMatches[2]);
                error_log("Price found for ISBN $isbn: £$price using pattern: $pattern");

                // Map price to range based on price_ranges table
                if ($price < 5) {
                    return 'Under £5';
                } elseif ($price <= 10) {
                    return '£5-£10';
                } elseif ($price <= 15) {
                    return '£10-£15';
                } elseif ($price <= 20) {
                    return '£15-£20';
                } else {
                    return 'Over £20';
                }
            }
        }

        // Fallback: look for any price pattern near amazon.co.uk
        if (preg_match('/amazon\.co\.uk.*?£(\d+)\.(\d{2})/i', $response, $priceMatches) ||
            preg_match('/£(\d+)\.(\d{2}).*?amazon\.co\.uk/i', $response, $priceMatches)) {

            $price = floatval($priceMatches[1] . '.' . $priceMatches[2]);
            error_log("Fallback price found for ISBN $isbn: £$price");

            // Map price to range
            if ($price < 5) {
                return 'Under £5';
            } elseif ($price <= 10) {
                return '£5-£10';
            } elseif ($price <= 15) {
                return '£10-£15';
            } elseif ($price <= 20) {
                return '£15-£20';
            } else {
                return 'Over £20';
            }
        }

        error_log("No valid price found for ISBN $isbn in Amazon search results");
        return null;

    } catch (Exception $e) {
        error_log("Error scraping Amazon price for ISBN $isbn: " . $e->getMessage());
        return null;
    }
}