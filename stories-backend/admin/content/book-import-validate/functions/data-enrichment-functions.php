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
        'average_rating' => ['confidence' => 60, 'label' => 'Average Rating'], // From OpenLibrary ratings
        'rating_count' => ['confidence' => 60, 'label' => 'Rating Count'], // From OpenLibrary ratings
        'internet_archive_id' => ['confidence' => 70, 'label' => 'Internet Archive ID'], // From OpenLibrary
        'awards' => ['confidence' => 45, 'label' => 'Awards'], // From OpenLibrary subject_facet
        'characters' => ['confidence' => 40, 'label' => 'Characters'], // From OpenLibrary person
        'settings' => ['confidence' => 40, 'label' => 'Settings'], // From OpenLibrary place
        // Fields that require external sources
        'price_range' => ['confidence' => 0, 'label' => 'Price Range']
    ];

    // Find best matches from each source
    $googleMatch = findBestDataMatch($googleResults, $title, $author, $currentISBN);
    $openLibraryMatch = findBestDataMatch($openLibraryResults, $title, $author, $currentISBN);

    // Debug logging
    error_log("Google match data: " . json_encode($googleMatch));
    error_log("OpenLibrary match data: " . json_encode($openLibraryMatch));

    $combinedFields = [];
    $maxConfidence = 0;
    $isbnValidated = 'unknown';

    // Process each field
    foreach ($allFields as $fieldName => $fieldConfig) {
        $googleValue = extractFieldValue($googleMatch, $fieldName);
        $openLibraryValue = extractFieldValue($openLibraryMatch, $fieldName);

        // Debug specific fields
        if (in_array($fieldName, ['tags', 'price_range', 'age_range', 'settings'])) {
            error_log("Field $fieldName - Google: " . json_encode($googleValue) . ", OpenLibrary: " . json_encode($openLibraryValue));
        }

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
            // Google Books categories[] + OpenLibrary subject[] + subject_key[] + OpenLibrary subject_facet[]
            // Processing: Deduplicates and capitalizes entries
            $allTags = [];

            // Get Google Books categories
            if (isset($match['categories']) && is_array($match['categories'])) {
                $allTags = array_merge($allTags, $match['categories']);
            }

            // Get OpenLibrary subjects (full list)
            if (isset($match['subject']) && is_array($match['subject'])) {
                $allTags = array_merge($allTags, $match['subject']);
            }

            // Get OpenLibrary subject_key
            if (isset($match['subject_key']) && is_array($match['subject_key'])) {
                $subjectKeys = array_map(function($key) {
                    return ucwords(str_replace('_', ' ', $key));
                }, $match['subject_key']);
                $allTags = array_merge($allTags, $subjectKeys);
            }

            // Get OpenLibrary subject_facet
            if (isset($match['subject_facet']) && is_array($match['subject_facet'])) {
                $allTags = array_merge($allTags, $match['subject_facet']);
            }

            // Clean and filter tags - exclude age-related terms that should go to age_range
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
                    $lowerTag = strtolower($cleanTag);

                    // Skip age-related terms
                    $isAgeRelated = false;
                    foreach ($ageTermsToExclude as $ageTerm) {
                        if (stripos($lowerTag, $ageTerm) !== false) {
                            $isAgeRelated = true;
                            break;
                        }
                    }

                    if (!$isAgeRelated && !empty($cleanTag) && strlen($cleanTag) > 2 && strlen($cleanTag) < 100) {
                        // Capitalize first letter of each word
                        $cleanTags[] = ucwords(strtolower($cleanTag));
                    }
                }

                // Remove duplicates and return full list
                $uniqueTags = array_unique($cleanTags);
                return implode(', ', $uniqueTags);
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

        case 'age_range':
            // Open Library subject_facet[] contains specific patterns
            $ageRange = null;

            if (isset($match['subject_facet']) && is_array($match['subject_facet'])) {
                foreach ($match['subject_facet'] as $subject) {
                    if (stripos($subject, "Children's Books/Ages 9-12 Fiction") !== false) {
                        $ageRange = '9-12 years'; // Match exact database value
                        break;
                    } elseif (stripos($subject, 'Tweens') !== false) {
                        $ageRange = '8-12 years'; // Match exact database value
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
                    $ageRange = 'Young Adult'; // Match database value instead of "All Ages"
                } elseif ($maturityRating === 'MATURE') {
                    $ageRange = 'Adult'; // Match exact database value
                }
            }

            return $ageRange;

        case 'reading_level':
            // Open Library: use lexile[]
            if (isset($match['lexile']) && is_array($match['lexile']) && !empty($match['lexile'])) {
                return $match['lexile'][0] . 'L';
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
                    if (stripos($subject, 'award:') === 0) {
                        // Transform "award:hugo_award=2003" to "Hugo Award (2003)"
                        $awardParts = explode('=', str_replace('award:', '', $subject));
                        if (count($awardParts) === 2) {
                            $awardName = ucwords(str_replace('_', ' ', $awardParts[0]));
                            $awardYear = $awardParts[1];
                            $awards[] = "$awardName ($awardYear)";
                        }
                    } elseif (stripos($subject, 'Hugo Award') !== false ||
                             stripos($subject, 'Newbery') !== false ||
                             stripos($subject, 'Caldecott') !== false) {
                        $awards[] = $subject;
                    }
                }
            }
            return !empty($awards) ? implode(', ', array_unique($awards)) : null;

        case 'characters':
            // Get characters from OpenLibrary person data
            if (isset($match['person']) && is_array($match['person'])) {
                return implode(', ', array_slice($match['person'], 0, 5));
            }
            break;

        case 'settings':
            // Use Open Library place_facet[]
            if (isset($match['place_facet']) && is_array($match['place_facet'])) {
                $places = array_map(function($place) {
                    return ucwords(strtolower(trim($place)));
                }, $match['place_facet']);
                return implode(', ', array_slice($places, 0, 3));
            }
            break;

        case 'price_range':
            // Scrape price from Amazon UK using ISBN
            if (isset($match['isbn13']) || isset($match['isbn'])) {
                $isbn = $match['isbn13'] ?? $match['isbn'];
                $priceRange = scrapePriceFromAmazon($isbn);
                error_log("Price range result for ISBN $isbn: " . ($priceRange ?? 'null'));
                return $priceRange;
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