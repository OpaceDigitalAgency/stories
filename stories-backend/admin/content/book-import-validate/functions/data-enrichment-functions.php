<?php
// Toggle Amazon debug output (disable for JSON API use)
if (!defined('AMAZON_DEBUG')) {
    define('AMAZON_DEBUG', false);
}
/**
 * Data Enrichment Functions
 *
 * Functions for enriching book data from multiple sources
 */

require_once __DIR__ . '/google-books-validation-functions.php';
require_once __DIR__ . '/open-library-validation-functions.php';
require_once __DIR__ . '/data-enrichment-fixes.php';

// Global debug function for browser console output
function debugLog($message, $data = null) {
    // Check if we're in an AJAX context (JSON response expected)
    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
              strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    // Also check if Content-Type is set to JSON
    $isJsonResponse = false;
    foreach (headers_list() as $header) {
        if (stripos($header, 'Content-Type: application/json') !== false) {
            $isJsonResponse = true;
            break;
        }
    }

    if ($isAjax || $isJsonResponse) {
        // In AJAX context, don't output anything to avoid breaking JSON
        // The debugging will be handled by book-check-compare.php instead
        return;
    } else {
        // In regular page context, output to browser console
        $logData = $data ? json_encode($data) : '';
        echo "<script>console.log('SCRAPE_TEST: " . addslashes($message) . "', " . ($logData ?: '""') . ");</script>";
        flush(); // Ensure immediate output
    }
}

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
 * @param string $currentPublisher Current publisher (if any) for database matching
 * @return array Enriched book data with confidence scores
 */
function getEnrichedBookData($title, $author, $currentISBN = '', $currentPublisher = null, $db = null) {
    // Set global database connection if provided
    if ($db !== null) {
        global $db;
    }
    // PUBLISHER DEBUG - Track the publisher parameter
    error_log("PUBLISHER_DEBUG: getEnrichedBookData called with currentPublisher='$currentPublisher'");

    // FUNCTION ENTRY DEBUG
    error_log("FUNCTION_ENTRY: title='$title', author='$author', isbn='$currentISBN'");

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

    // CRITICAL FIX: Add Amazon data integration
    $amazonData = [];
    if (!empty($currentISBN)) {
        // Try to get Amazon data for age range, format, price, etc.
        try {
            debugLog("Starting Amazon scraping for ISBN $currentISBN");

            // CRITICAL: Enable Amazon debug mode for detailed logging
            if (!defined('AMAZON_DEBUG')) {
                define('AMAZON_DEBUG', true);
            }

            // CRITICAL FIX: Amazon requires ISBN-10, so convert ISBN-13 to ISBN-10
            $amazonISBN = $currentISBN;
            if (strlen(preg_replace('/[^0-9X]/i', '', $currentISBN)) === 13) {
                $isbn10 = convertISBN13ToISBN10($currentISBN);
                if ($isbn10) {
                    $amazonISBN = $isbn10;
                    debugLog("Converted ISBN-13 to ISBN-10 for Amazon: $currentISBN -> $amazonISBN");
                } else {
                    debugLog("Failed to convert ISBN-13 to ISBN-10: $currentISBN");
                }
            } else {
                debugLog("Using original ISBN for Amazon (already ISBN-10): $amazonISBN");
            }

            $amazonData = scrapeAmazonBuyingOptions($amazonISBN);
            $enrichedData['sources_checked'][] = 'amazon';

            // CRITICAL DEBUG: Log what Amazon data was actually retrieved
            debugLog("Amazon data retrieved for ISBN $currentISBN");
            debugLog("Amazon data structure", $amazonData);
            debugLog("Amazon metadata", $amazonData['metadata'] ?? []);
            debugLog("Amazon buying_options", $amazonData['buying_options'] ?? []);

            // Check if Amazon data is completely empty
            if (empty($amazonData) || (empty($amazonData['metadata']) && empty($amazonData['buying_options']))) {
                debugLog("WARNING: Amazon scraping returned empty data for ISBN $currentISBN");
                debugLog("Amazon data empty check", [
                    'amazonData_empty' => empty($amazonData),
                    'metadata_empty' => empty($amazonData['metadata'] ?? []),
                    'buying_options_empty' => empty($amazonData['buying_options'] ?? [])
                ]);
            }

            // Check specifically for reading_age
            if (isset($amazonData['metadata']['reading_age'])) {
                debugLog("Found reading_age in Amazon data: '" . $amazonData['metadata']['reading_age'] . "'");
            } else {
                debugLog("NO reading_age found in Amazon metadata");
                if (isset($amazonData['metadata'])) {
                    debugLog("Available metadata keys: " . implode(', ', array_keys($amazonData['metadata'])));
                }
            }
        } catch (Exception $e) {
            error_log("ENRICHMENT_DEBUG: Amazon scraping failed for ISBN $currentISBN: " . $e->getMessage());
        }
    }

    // Combine and analyze results from all sources including Amazon
    $combinedData = combineMultiSourceData($googleResults, $openLibraryResults, $amazonData, $title, $author, $currentISBN, $currentPublisher, []);

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
function combineMultiSourceData($googleResults, $openLibraryResults, $amazonData, $title, $author, $currentISBN, $currentPublisher = null, $currentValues = []) {
    // Define fields that match actual database structure with enhanced mapping
    $allFields = [
        'title' => ['confidence' => 95, 'label' => 'Title'], // CRITICAL: Was missing!
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
        'age_range' => ['confidence' => 50, 'label' => 'Age Range'], // Derived from Amazon + subjects
        'reading_level' => ['confidence' => 40, 'label' => 'Reading Level'], // Derived from age_range using database
        'price_range' => ['confidence' => 45, 'label' => 'Price Range'], // From Amazon pricing
        'purchase_links' => ['confidence' => 50, 'label' => 'Purchase Links'], // From Amazon + Google
        'internet_archive_id' => ['confidence' => 70, 'label' => 'Internet Archive ID'], // From OpenLibrary
        'awards' => ['confidence' => 45, 'label' => 'Awards'], // From OpenLibrary subject_facet
        'characters' => ['confidence' => 40, 'label' => 'Characters'], // From OpenLibrary person
        'settings' => ['confidence' => 40, 'label' => 'Settings'], // From OpenLibrary place
        'alternative_isbns' => ['confidence' => 70, 'label' => 'Alternative ISBNs']
    ];

    // Find best matches from each source (with fallback parameters for non-ISBN matching)
    $googleMatch = findBestDataMatch($googleResults, $title, $author, $currentISBN);
    $openLibraryMatch = findBestDataMatch($openLibraryResults, $title, $author, $currentISBN);

    // CRITICAL DEBUG: Log the actual matches found
    debugLog("Google match found: " . ($googleMatch ? 'YES' : 'NO'));
    debugLog("Google match data", $googleMatch);
    debugLog("OpenLibrary match found: " . ($openLibraryMatch ? 'YES' : 'NO'));
    debugLog("OpenLibrary match data", $openLibraryMatch);

    // Validate OpenLibrary match if we have an ISBN - STRICT validation
    if (!empty($currentISBN) && $openLibraryMatch) {
        $isValidMatch = validateOpenLibraryISBNMatch($openLibraryMatch, $currentISBN);
        error_log("OpenLibrary ISBN validation for $currentISBN: " . ($isValidMatch ? 'PASSED' : 'FAILED'));
        error_log("OpenLibrary ISBNs found: " . json_encode($openLibraryMatch['isbn'] ?? 'none'));

        if (!$isValidMatch) {
            error_log("OpenLibrary match REJECTED - ISBN mismatch. Expected: $currentISBN");
            $openLibraryMatch = null;
        } else {
            error_log("OpenLibrary match ACCEPTED - ISBN validation passed");
        }
    }

    $combinedFields = [];
    $maxConfidence = 0;
    $isbnValidated = 'unknown';

    // Process each field
    foreach ($allFields as $fieldName => $fieldConfig) {
        $googleValue = extractFieldValue($googleMatch, $fieldName, $currentISBN);
        $openLibraryValue = extractFieldValue($openLibraryMatch, $fieldName, $currentISBN);
        $amazonValue = extractAmazonFieldValue($amazonData, $fieldName, $currentISBN);

        // CRITICAL DEBUG: Log Amazon field extraction for key fields
        if (in_array($fieldName, ['age_range', 'reading_level', 'format', 'price_range', 'page_count', 'publisher', 'publication_date', 'language'])) {
            debugLog("Field '$fieldName' - Amazon data available: " . (!empty($amazonData) ? 'YES' : 'NO'));
            debugLog("Field '$fieldName' - Google value: " . (is_null($googleValue) ? 'NULL' : "'$googleValue'"));
            debugLog("Field '$fieldName' - OpenLibrary value: " . (is_null($openLibraryValue) ? 'NULL' : "'$openLibraryValue'"));
            debugLog("Field '$fieldName' - Amazon value extracted: " . (is_null($amazonValue) ? 'NULL' : "'$amazonValue'"));
            debugLog("Field '$fieldName' - Google empty check: " . (empty($googleValue) ? 'TRUE' : 'FALSE'));
            debugLog("Field '$fieldName' - OpenLibrary empty check: " . (empty($openLibraryValue) ? 'TRUE' : 'FALSE'));
            debugLog("Field '$fieldName' - Amazon empty check: " . (empty($amazonValue) ? 'TRUE' : 'FALSE'));
            if (!empty($amazonData)) {
                debugLog("Amazon metadata for field '$fieldName'", $amazonData['metadata'] ?? []);
                debugLog("Amazon buying_options for field '$fieldName'", $amazonData['buying_options'] ?? []);

                // CRITICAL: Debug the specific field extraction
                if ($fieldName === 'age_range' && isset($amazonData['metadata']['reading_age'])) {
                    debugLog("CRITICAL: Amazon has reading_age but extraction returned NULL: " . $amazonData['metadata']['reading_age']);
                }
                if ($fieldName === 'format' && isset($amazonData['buying_options'])) {
                    debugLog("CRITICAL: Amazon has buying_options but format extraction returned NULL");
                    debugLog("Available formats", array_keys($amazonData['buying_options']));
                }
            }
        }

        // CRITICAL DEBUG: Log author field processing
        if ($fieldName === 'author') {
            debugLog("Processing author field");
            debugLog("Author - Google match data", $googleMatch);
            debugLog("Author - OpenLibrary match data", $openLibraryMatch);
            debugLog("Author - Google value extracted", $googleValue);
            debugLog("Author - OpenLibrary value extracted", $openLibraryValue);
        }

        // CRITICAL DEBUG: Log title field processing
        if ($fieldName === 'title') {
            debugLog("Processing title field");
            debugLog("Title - Google match data", $googleMatch);
            debugLog("Title - OpenLibrary match data", $openLibraryMatch);
            debugLog("Title - Google value extracted", $googleValue);
            debugLog("Title - OpenLibrary value extracted", $openLibraryValue);
        }

        // Check if we have data from any source (Google, OpenLibrary, or Amazon)
        if (!empty($googleValue) || !empty($openLibraryValue) || !empty($amazonValue)) {
            // Special handling for Amazon-priority fields
            if (in_array($fieldName, ['age_range', 'reading_level', 'format', 'price_range', 'page_count', 'publisher', 'publication_date', 'language', 'isbn', 'isbn13', 'series']) && !empty($amazonValue)) {
                // Amazon data takes priority for these fields
                $combinedFields[$fieldName] = [
                    'value' => $amazonValue,
                    'source' => 'amazon',
                    'confidence' => $fieldConfig['confidence'],
                    'label' => $fieldConfig['label']
                ];
                debugLog("Using Amazon data for $fieldName: $amazonValue");
            } elseif ($fieldName === 'tags') {
                // Special handling for tags - always merge them
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
            } elseif (!empty($amazonValue) && empty($googleValue) && empty($openLibraryValue)) {
                // Only Amazon has data for this field
                $combinedFields[$fieldName] = [
                    'value' => $amazonValue,
                    'source' => 'amazon',
                    'confidence' => $fieldConfig['confidence'],
                    'label' => $fieldConfig['label']
                ];
                error_log("ENRICHMENT_DEBUG: Using Amazon-only data for $fieldName: $amazonValue");
            } elseif (!empty($googleValue) && !empty($openLibraryValue)) {
                // Both sources have data - check if they match
                debugLog("Field '$fieldName' has both Google and OpenLibrary values", [
                    'google' => $googleValue,
                    'openLibrary' => $openLibraryValue
                ]);

                if (normalizeForComparison($googleValue) === normalizeForComparison($openLibraryValue)) {
                    // Values match - use combined source
                    debugLog("Field '$fieldName' values match after normalization");

                    // Check if this matches the current value exactly for 100% confidence
                    $finalValue = preferEnglishVersion($googleValue, $openLibraryValue);
                    $confidence = min($fieldConfig['confidence'] + 10, 100); // Boost confidence for matching sources

                    // If it matches current value exactly, set to 100%
                    if (isset($currentValues[$fieldName]) && isExactValueMatch($currentValues[$fieldName], $finalValue)) {
                        $confidence = 100;
                        debugLog("Field '$fieldName' exactly matches current value - setting confidence to 100%");
                    }

                    $combinedFields[$fieldName] = [
                        'value' => $finalValue,
                        'source' => 'google_books + open_library',
                        'confidence' => $confidence,
                        'label' => $fieldConfig['label']
                    ];
                } else {
                    // Values differ - for core fields like title/author, prefer Google Books
                    // For other fields, create options
                    debugLog("Field '$fieldName' values differ", [
                        'google' => $googleValue,
                        'openLibrary' => $openLibraryValue
                    ]);

                    if (in_array($fieldName, ['title', 'author'])) {
                        // CRITICAL FIX: For author, prefer OpenLibrary; for title, prefer Google Books
                        if ($fieldName === 'author') {
                            debugLog("Using OpenLibrary value for author field: $openLibraryValue");
                            $combinedFields[$fieldName] = [
                                'value' => $openLibraryValue,
                                'source' => 'open_library',
                                'confidence' => $fieldConfig['confidence'],
                                'label' => $fieldConfig['label'],
                                'alternative' => $googleValue // Keep alternative for reference
                            ];
                        } else {
                            debugLog("Using Google Books value for title field: $googleValue");
                            $combinedFields[$fieldName] = [
                                'value' => $googleValue,
                                'source' => 'google_books',
                                'confidence' => $fieldConfig['confidence'],
                                'label' => $fieldConfig['label'],
                                'alternative' => $openLibraryValue // Keep alternative for reference
                            ];
                        }
                    } else {
                        // For other fields, create options as before
                        $options = [
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
                        ];

                        // For publisher field, add recommended matches from existing database
                        if ($fieldName === 'publisher') {
                            debugLog("Processing publisher field with options", $options);
                            foreach ($options as $index => &$option) {
                                if (isset($option['value']) && !empty($option['value'])) {
                                    $bestMatch = findBestPublisherMatch($option['value']);
                                    if ($bestMatch && $bestMatch['confidence'] >= 80) { // Higher threshold - only recommend very good matches
                                        $option['recommended'] = $bestMatch['name'];
                                        $option['recommendation_confidence'] = $bestMatch['confidence'];
                                        $option['match_type'] = $bestMatch['match_type'];
                                        debugLog("Added publisher recommendation: " . $bestMatch['name'] . " with confidence " . $bestMatch['confidence']);
                                    } else {
                                        $option['recommended'] = false; // Explicitly set to false when no good match
                                        debugLog("No suitable publisher match found");
                                    }
                                }
                            }
                            unset($option); // Break the reference to avoid issues
                        }

                        $combinedFields[$fieldName] = [
                            'current_value' => $currentValues[$fieldName] ?? null,
                            'new_data' => ['options' => $options],
                            'label' => $fieldConfig['label']
                        ];
                    }
                }
            } else {
                // Only one source has data - determine which one
                if (!empty($amazonValue)) {
                    // Amazon has data
                    $combinedFields[$fieldName] = [
                        'value' => $amazonValue,
                        'source' => 'amazon',
                        'confidence' => $fieldConfig['confidence'],
                        'label' => $fieldConfig['label']
                    ];
                    error_log("ENRICHMENT_DEBUG: Using Amazon data for $fieldName: $amazonValue");
                } else {
                    // Google or OpenLibrary has data
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
            }
        } else {
            // No data from any source - show as unknown

            // CRITICAL DEBUG: Log when fields are marked as unknown
            if ($fieldName === 'author' || $fieldName === 'title') {
                debugLog("$fieldName field marked as unknown - no data from either source");
                debugLog("$fieldName - googleValue empty: " . (empty($googleValue) ? 'YES' : 'NO'));
                debugLog("$fieldName - openLibraryValue empty: " . (empty($openLibraryValue) ? 'YES' : 'NO'));
                debugLog("$fieldName - googleValue", $googleValue);
                debugLog("$fieldName - openLibraryValue", $openLibraryValue);
            }

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

    // Special handling for publisher field - ALWAYS check current value against database for recommendations
    if (!empty($currentPublisher)) {
        error_log("PUBLISHER_TEST: Checking current publisher against database: $currentPublisher");
        $currentPublisherMatch = findBestPublisherMatch($currentPublisher);

        if ($currentPublisherMatch) {
            if ($currentPublisherMatch['match_type'] === 'exact' && $currentPublisherMatch['confidence'] === 100) {
                // Current value exactly matches database - show as confirmed
                if (!isset($combinedFields['publisher'])) {
                    $combinedFields['publisher'] = [
                        'value' => $currentPublisher,
                        'source' => 'database_confirmed',
                        'confidence' => 100,
                        'label' => 'Publisher',
                        'database_match' => $currentPublisherMatch
                    ];
                }
                error_log("PUBLISHER_TEST: Current publisher exactly matches database: " . $currentPublisherMatch['name']);
            } elseif ($currentPublisherMatch['confidence'] >= 30) {
                // Current value has a good match - offer recommendation
                if (!isset($combinedFields['publisher'])) {
                    // No new data, create field with database recommendation
                    $combinedFields['publisher'] = [
                        'current_value' => $currentPublisher,
                        'new_data' => [
                            'value' => $currentPublisherMatch['name'],
                            'source' => 'database_recommendation',
                            'confidence' => $currentPublisherMatch['confidence'],
                            'label' => 'Publisher',
                            'match_type' => $currentPublisherMatch['match_type'],
                            'recommendation_reason' => "Better match found in database"
                        ]
                    ];
                } else {
                    // New data exists, add database recommendation to existing field
                    if (!isset($combinedFields['publisher']['database_match'])) {
                        $combinedFields['publisher']['database_match'] = $currentPublisherMatch;
                        error_log("PUBLISHER_TEST: Added database recommendation to existing publisher field: " . $currentPublisherMatch['name'] . " (confidence: " . $currentPublisherMatch['confidence'] . "%)");
                    }
                }
                error_log("PUBLISHER_TEST: Found database publisher match: " . $currentPublisherMatch['name'] . " (confidence: " . $currentPublisherMatch['confidence'] . "%)");
            }
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
 * Check if two values are exactly the same (for confidence scoring)
 */
function isExactValueMatch($currentValue, $newValue) {
    // Handle null/empty cases
    if (empty($currentValue) && empty($newValue)) {
        return true;
    }
    if (empty($currentValue) || empty($newValue)) {
        return false;
    }

    // For JSON objects (like purchase_links), compare the actual data
    if (is_array($currentValue) && is_array($newValue)) {
        return json_encode($currentValue) === json_encode($newValue);
    }

    // For JSON strings, parse and compare
    if (is_string($currentValue) && is_string($newValue)) {
        // Try to parse as JSON first
        $currentParsed = json_decode($currentValue, true);
        $newParsed = json_decode($newValue, true);
        if ($currentParsed !== null && $newParsed !== null) {
            return json_encode($currentParsed) === json_encode($newParsed);
        }
    }

    // For numbers, handle string vs number comparison
    if ((is_numeric($currentValue)) && (is_numeric($newValue))) {
        return floatval($currentValue) === floatval($newValue);
    }

    // For strings, normalize and compare
    if (is_string($currentValue) && is_string($newValue)) {
        $normalize = function($str) {
            return trim(strtolower(preg_replace('/\s+/', ' ', $str)));
        };
        return $normalize($currentValue) === $normalize($newValue);
    }

    // Default comparison
    return $currentValue === $newValue;
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
function extractFieldValue($match, $fieldName, $currentISBN = null) {
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
            // If we have a current ISBN-13, convert it to ISBN-10 for consistency
            if (!empty($currentISBN)) {
                $cleanCurrentISBN = preg_replace('/[^0-9X]/i', '', $currentISBN);
                if (strlen($cleanCurrentISBN) === 13) {
                    $convertedISBN10 = convertISBN13ToISBN10($cleanCurrentISBN);
                    if ($convertedISBN10) {
                        return $convertedISBN10;
                    }
                } elseif (strlen($cleanCurrentISBN) === 10) {
                    return $cleanCurrentISBN;
                }
            }

            // Fallback to original logic if no current ISBN or conversion failed
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
            // If we have a current ISBN, return it (prioritize consistency)
            if (!empty($currentISBN)) {
                $cleanCurrentISBN = preg_replace('/[^0-9X]/i', '', $currentISBN);
                if (strlen($cleanCurrentISBN) === 13) {
                    return $cleanCurrentISBN;
                } elseif (strlen($cleanCurrentISBN) === 10) {
                    $convertedISBN13 = convertToISBN13($cleanCurrentISBN);
                    if ($convertedISBN13) {
                        return $convertedISBN13;
                    }
                }
            }

            // Fallback to original logic if no current ISBN or conversion failed
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
            // REMOVED: Maturity rating logic removed as it causes confusion
            // Age ranges should come from OpenLibrary subjects and Amazon metadata only
            return null;

        case 'language':
            // Normalize language codes
            if (isset($match['language'])) {
                return normalizeLanguage($match['language']);
            }
            break;

        case 'format':
            // OpenLibrary returns all formats for all editions, not specific to the ISBN
            // Only return format if it's from Google Books or if it's a single format
            if (isset($match['source']) && $match['source'] === 'google_books' && isset($match['format'])) {
                if (is_string($match['format'])) {
                    return normalizeFormat(trim($match['format']));
                }
            } elseif (isset($match['format']) && is_string($match['format'])) {
                // Single format from other sources - might be reliable
                return normalizeFormat(trim($match['format']));
            } elseif (isset($match['format']) && is_array($match['format']) && count($match['format']) === 1) {
                // Single format in array - might be reliable
                return normalizeFormat(trim($match['format'][0]));
            }
            // Don't return format from OpenLibrary when it's an array of multiple formats
            // as it represents all editions, not the specific ISBN
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
            // REMOVED: All maturity rating processing to eliminate 12+ issue
            // Google Books age range extraction - only from categories and subject_facet
            $ageRange = null;
            error_log("AGE_TEST: AGE_RANGE_EXTRACT_DEBUG: Starting age range extraction for source: " . ($match['source'] ?? 'unknown'));

            // Check Google Books categories for explicit age patterns
            if (isset($match['categories']) && is_array($match['categories'])) {
                error_log("AGE_TEST: AGE_RANGE_EXTRACT_DEBUG: Found categories: " . json_encode($match['categories']));
                foreach ($match['categories'] as $category) {
                    if (is_string($category)) {
                        error_log("AGE_TEST: AGE_RANGE_EXTRACT_DEBUG: Checking category: '$category'");
                        // Look for explicit age patterns in categories
                        if (preg_match('/(\d+)\s*-\s*(\d+)\s*years?/i', $category, $matches)) {
                            $rawAgeRange = $matches[0]; // e.g., "8-12 years"
                            error_log("AGE_TEST: AGE_RANGE_EXTRACT_DEBUG: Found age pattern '$rawAgeRange' in category");
                            $ageRange = mapAmazonAgeRangeToStandard($rawAgeRange);
                            error_log("AGE_TEST: AGE_RANGE_EXTRACT_DEBUG: Mapped to standard range: '$ageRange'");
                            if ($ageRange) break;
                        } elseif (stripos($category, 'young adult') !== false) {
                            $ageRange = '14-16 years';
                            error_log("AGE_TEST: AGE_RANGE_EXTRACT_DEBUG: Found 'young adult' in category, setting to '14-16 years'");
                            break;
                        } elseif (stripos($category, 'teen') !== false) {
                            $ageRange = '11-14 years';
                            error_log("AGE_TEST: AGE_RANGE_EXTRACT_DEBUG: Found 'teen' in category, setting to '11-14 years'");
                            break;
                        }
                    }
                }
            } else {
                error_log("AGE_TEST: AGE_RANGE_EXTRACT_DEBUG: No categories found or categories not an array");
            }

            // Check if there's a direct age_range field (unlikely but possible)
            if (!$ageRange && isset($match['age_range']) && !empty($match['age_range'])) {
                $rawAgeRange = $match['age_range'];
                // FILTER OUT PROBLEMATIC VALUES
                if (strpos($rawAgeRange, '12+') !== false || strpos($rawAgeRange, 'unknown') !== false) {
                    // Skip problematic values - don't process them
                } else {
                    $ageRange = mapAmazonAgeRangeToStandard($rawAgeRange);
                }
            }

            // Open Library subject_facet[] contains specific patterns
            if (!$ageRange && isset($match['subject_facet']) && is_array($match['subject_facet'])) {
                foreach ($match['subject_facet'] as $subject) {
                    if (stripos($subject, "Children's Books/Ages 9-12 Fiction") !== false) {
                        $ageRange = '9-10 years';
                        break;
                    } elseif (stripos($subject, 'Tweens') !== false) {
                        $ageRange = '8-9 years';
                        break;
                    } elseif (stripos($subject, 'Young Adult Fiction') !== false) {
                        $ageRange = '11-14 years';
                        break;
                    }
                }
            }

            // FINAL FILTER: Remove any remaining problematic values
            if ($ageRange && (strpos($ageRange, '12+') !== false || strpos($ageRange, 'unknown') !== false)) {
                error_log("FILTERED OUT problematic age range value: '$ageRange'");
                return null;
            }

            // NO MATURITY RATING PROCESSING - this was causing the 12+ issue
            // Return null if no age range found from explicit sources
            error_log("AGE_TEST: AGE_RANGE_EXTRACT_DEBUG: Final age range result: " . ($ageRange ?? 'null'));
            return $ageRange;

        case 'reading_level':
            // Open Library: use lexile[] with conversion to readable format
            if (isset($match['lexile']) && is_array($match['lexile']) && !empty($match['lexile'])) {
                $lexileValue = $match['lexile'][0];
                $readingLevel = convertLexileToReadingLevel($lexileValue);
                // FILTER OUT UNKNOWN VALUES
                if ($readingLevel && stripos($readingLevel, 'unknown') === false) {
                    return $readingLevel;
                }
            }
            // Also check if lexile is a direct value
            elseif (isset($match['lexile']) && is_numeric($match['lexile'])) {
                $readingLevel = convertLexileToReadingLevel($match['lexile']);
                // FILTER OUT UNKNOWN VALUES
                if ($readingLevel && stripos($readingLevel, 'unknown') === false) {
                    return $readingLevel;
                }
            }

            // CRITICAL FIX: Add Google Books categories to reading level mapping
            if (isset($match['categories']) && is_array($match['categories'])) {
                foreach ($match['categories'] as $category) {
                    if (is_string($category)) {
                        $readingLevel = mapCategoryToReadingLevel($category);
                        if ($readingLevel) {
                            return $readingLevel;
                        }
                    }
                }
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
            // Use Open Library place_facet[] with enhanced processing
            $places = [];
            if (isset($match['place_facet']) && is_array($match['place_facet'])) {
                $places = $match['place_facet'];
            } elseif (isset($match['place']) && is_array($match['place'])) {
                $places = $match['place'];
            }

            if (!empty($places)) {
                // Convert array to comma-separated string for processing
                $placesString = implode(', ', $places);

                // Use enhanced location processing
                $processedPlaces = processLocationValues($placesString);

                // Limit to 3 locations and ensure proper capitalization
                if (!empty($processedPlaces)) {
                    $finalPlaces = array_slice(explode(', ', $processedPlaces), 0, 3);
                    $capitalizedPlaces = array_map(function($place) {
                        return ucwords(strtolower(trim($place)));
                    }, $finalPlaces);

                    return implode(', ', $capitalizedPlaces);
                }
            }
            break;

        case 'publisher':
            // Enhanced publisher processing with matching recommendations
            if (isset($match['publisher']) && is_array($match['publisher']) && !empty($match['publisher'])) {
                $publisherName = $match['publisher'][0];
            } elseif (isset($match['publisher']) && is_string($match['publisher'])) {
                $publisherName = $match['publisher'];
            } else {
                return null;
            }

            // Clean and normalize publisher name
            $publisherName = normalizePublisherName($publisherName);

            // Try to find a better match from existing publishers
            $bestMatch = findBestPublisherMatch($publisherName);

            if ($bestMatch && $bestMatch['confidence'] > 30) {
                // High confidence match - recommend the existing publisher
                return $bestMatch['name'] . ' (recommended: ' . $bestMatch['confidence'] . '% match)';
            }

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

            // Use price scraping function
            $priceRange = scrapePriceFromAmazon($isbn);
            error_log("Price range result for ISBN " . (is_array($isbn) ? json_encode($isbn) : $isbn) . ": " . ($priceRange ?? 'null'));
            return $priceRange;

        case 'page_count':
            // Handle multiple page count fields from different sources
            if (isset($match['page_count']) && is_numeric($match['page_count'])) {
                return intval($match['page_count']);
            } elseif (isset($match['number_of_pages']) && is_numeric($match['number_of_pages'])) {
                return intval($match['number_of_pages']);
            } elseif (isset($match['number_of_pages_median']) && is_numeric($match['number_of_pages_median'])) {
                // OpenLibrary specific field
                return intval($match['number_of_pages_median']);
            } elseif (isset($match['pageCount']) && is_numeric($match['pageCount'])) {
                // Google Books field
                return intval($match['pageCount']);
            }
            return null;

        case 'author':
            // CRITICAL FIX: Handle different author field formats between APIs
            debugLog("AUTHOR_EXTRACT: Processing author field for match", $match);

            // Google Books format: 'author' field (string, already joined)
            if (isset($match['author']) && !empty($match['author'])) {
                debugLog("AUTHOR_EXTRACT: Found Google Books author field: " . $match['author']);
                return $match['author'];
            }

            // OpenLibrary format: author_name array
            if (isset($match['author_name']) && is_array($match['author_name'])) {
                $author = !empty($match['author_name']) ? $match['author_name'][0] : null;
                debugLog("AUTHOR_EXTRACT: Found OpenLibrary author_name array", $author);
                return $author;
            } elseif (isset($match['author_name']) && is_string($match['author_name'])) {
                debugLog("AUTHOR_EXTRACT: Found OpenLibrary author_name string: " . $match['author_name']);
                return $match['author_name'];
            }

            // Fallback: check for 'authors' array (some APIs use this)
            if (isset($match['authors']) && is_array($match['authors'])) {
                $author = !empty($match['authors']) ? $match['authors'][0] : null;
                debugLog("AUTHOR_EXTRACT: Found authors array", $author);
                return $author;
            }

            debugLog("AUTHOR_EXTRACT: No author field found in match data");
            return null;

        case 'title':
            // CRITICAL FIX: Handle title field extraction
            debugLog("TITLE_EXTRACT: Processing title field for match", $match);

            // Standard title field (used by both Google Books and OpenLibrary)
            if (isset($match['title']) && !empty($match['title'])) {
                debugLog("TITLE_EXTRACT: Found title field: " . $match['title']);
                return $match['title'];
            }

            debugLog("TITLE_EXTRACT: No title field found in match data");
            return null;

        default:
            // Standard field extraction with debug logging
            $value = $match[$fieldName] ?? null;
            if ($fieldName === 'author' || $fieldName === 'title') {
                error_log("FIELD_EXTRACT_DEBUG: Standard extraction for '$fieldName': " . json_encode($value));
            }
            return $value;
    }

    return null;
}

/**
 * Map Google Books categories to reading levels
 */
function mapCategoryToReadingLevel($category) {
    $category = strtolower(trim($category));

    // Map Google Books categories to reading levels
    $categoryMappings = [
        'adult' => 'Proficient Reader',
        'young adult' => 'Advanced Reader',
        'teen' => 'Advanced Reader',
        'middle grade' => 'Fluent Reader',
        'children' => 'Early Reader',
        'juvenile' => 'Early Reader',
        'picture books' => 'Beginning Reader',
        'early readers' => 'Early Reader',
        'chapter books' => 'Transitional Reader',
        'fiction / general' => 'Proficient Reader',
        'fiction / literary' => 'Proficient Reader',
        'fiction / contemporary' => 'Advanced Reader'
    ];

    // Try exact match first
    if (isset($categoryMappings[$category])) {
        return $categoryMappings[$category];
    }

    // Try partial matches
    foreach ($categoryMappings as $key => $readingLevel) {
        if (strpos($category, $key) !== false) {
            return $readingLevel;
        }
    }

    return null;
}

// REMOVED: mapMaturityRatingToAgeRange function - maturity rating logic removed as it causes confusion

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
 * Convert Lexile reading level to standardized reading level format
 */
/**
 * Get age range to reading level mapping from database
 * @return array Associative array mapping age_group to reading_stage
 */
function getAgeToReadingMapping() {
    global $db;
    static $mapping = null;

    if ($mapping === null) {
        try {
            $stmt = $db->query("SELECT age_group, reading_stage FROM standard_reading_levels ORDER BY sort_order ASC");
            $mapping = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $mapping[$row['age_group']] = $row['reading_stage'];
            }
            error_log("DATABASE_MAPPING: Loaded " . count($mapping) . " age-to-reading mappings from database");
        } catch (Exception $e) {
            error_log("DATABASE_MAPPING: Error loading mappings: " . $e->getMessage());
            $mapping = []; // Fallback to empty array
        }
    }

    return $mapping;
}

/**
 * Get reading level to age range mapping from database
 * @return array Associative array mapping reading_stage to age_group
 */
function getReadingToAgeMapping() {
    global $db;
    static $mapping = null;

    if ($mapping === null) {
        try {
            $stmt = $db->query("SELECT age_group, reading_stage FROM standard_reading_levels ORDER BY sort_order ASC");
            $mapping = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $mapping[$row['reading_stage']] = $row['age_group'];
            }
            error_log("DATABASE_MAPPING: Loaded " . count($mapping) . " reading-to-age mappings from database");
        } catch (Exception $e) {
            error_log("DATABASE_MAPPING: Error loading mappings: " . $e->getMessage());
            $mapping = []; // Fallback to empty array
        }
    }

    return $mapping;
}

/**
 * Convert Lexile score to reading level using database mappings
 * @param string $lexileValue The Lexile score (e.g., "750L")
 * @return string|null The corresponding reading stage or null if not found
 */
function convertLexileToReadingLevel($lexileValue) {
    global $db;

    if (!is_numeric($lexileValue)) {
        return $lexileValue . 'L';
    }

    $lexile = (int) $lexileValue;

    try {
        // Query database for the appropriate reading level based on Lexile range
        $stmt = $db->query("
            SELECT reading_stage, lexile_range
            FROM standard_reading_levels
            WHERE lexile_range IS NOT NULL
            AND lexile_range != ''
            ORDER BY sort_order ASC
        ");

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $lexileRange = $row['lexile_range'];

            // Parse lexile range (e.g., "620L-820L" or "BR-120L")
            if (preg_match('/(\d+)L?-(\d+)L?/', $lexileRange, $matches)) {
                $minLexile = (int)$matches[1];
                $maxLexile = (int)$matches[2];

                if ($lexile >= $minLexile && $lexile <= $maxLexile) {
                    error_log("DATABASE_MAPPING: Lexile $lexile mapped to {$row['reading_stage']} (range: $lexileRange)");
                    return $row['reading_stage'];
                }
            } elseif (strpos($lexileRange, 'BR') === 0) {
                // Beginning Reader range (e.g., "BR-120L")
                if (preg_match('/BR-(\d+)L?/', $lexileRange, $matches)) {
                    $maxLexile = (int)$matches[1];
                    if ($lexile <= $maxLexile) {
                        error_log("DATABASE_MAPPING: Lexile $lexile mapped to {$row['reading_stage']} (BR range: $lexileRange)");
                        return $row['reading_stage'];
                    }
                }
            }
        }

        error_log("DATABASE_MAPPING: No database match for Lexile $lexile, using fallback logic");

    } catch (Exception $e) {
        error_log("DATABASE_MAPPING: Error querying Lexile ranges: " . $e->getMessage());
    }

    // Fallback to hardcoded logic if database query fails
    if ($lexile < 200) {
        return 'Beginning Reader';
    } elseif ($lexile < 400) {
        return 'Early Reader';
    } elseif ($lexile < 600) {
        return 'Developing Reader';
    } elseif ($lexile < 800) {
        return 'Fluent Reader';
    } elseif ($lexile < 1000) {
        return 'Advanced Reader';
    } else {
        return 'Proficient Reader';
    }
}

/**
 * Deduplicate location strings with enhanced smart processing
 * E.g., "London, London (England)" -> "London (England)"
 */
function deduplicateLocation($location) {
    if (empty($location)) {
        return $location;
    }

    $location = trim($location);

    // Handle concatenated duplicates like "LondonLondon (england)" -> "London (England)"
    if (preg_match('/^([a-zA-Z]+)\1\s*\(([^)]*)\)$/i', $location, $matches)) {
        return trim($matches[1]) . ' (' . trim($matches[2]) . ')';
    }

    // Handle patterns like "London, London (England)" -> "London (England)"
    if (preg_match('/^([^,]+),\s*\1\s*\(([^)]+)\)$/i', $location, $matches)) {
        return trim($matches[1]) . ' (' . trim($matches[2]) . ')';
    }

    // Handle patterns like "New York, New York" -> "New York"
    if (preg_match('/^([^,]+),\s*\1$/i', $location, $matches)) {
        return trim($matches[1]);
    }

    // Handle case variations like "london, London" -> "London"
    if (preg_match('/^([^,]+),\s*([^,]+)$/i', $location, $matches)) {
        $part1 = trim($matches[1]);
        $part2 = trim($matches[2]);
        if (strtolower($part1) === strtolower($part2)) {
            // Use the version with better capitalization (more uppercase letters)
            return (substr_count($part1, strtoupper($part1)) >= substr_count($part2, strtoupper($part2))) ? $part1 : $part2;
        }
    }

    return $location;
}

/**
 * Process and deduplicate multiple location values
 * Returns comma-separated unique locations
 */
function processLocationValues($locations) {
    if (empty($locations)) {
        return '';
    }

    // Convert to array if string
    if (is_string($locations)) {
        $locationArray = array_map('trim', explode(',', $locations));
    } else {
        $locationArray = is_array($locations) ? $locations : [$locations];
    }

    $processedLocations = [];

    foreach ($locationArray as $location) {
        if (empty($location)) {
            continue;
        }

        // Deduplicate individual location
        $cleanLocation = deduplicateLocation(trim($location));

        // Check if this location (or a very similar one) already exists
        $isDuplicate = false;
        foreach ($processedLocations as $existing) {
            if (strtolower($cleanLocation) === strtolower($existing)) {
                $isDuplicate = true;
                break;
            }

            // Check for partial matches (e.g., "London" vs "London (England)")
            if (strpos(strtolower($existing), strtolower($cleanLocation)) !== false ||
                strpos(strtolower($cleanLocation), strtolower($existing)) !== false) {
                // Keep the more specific version (longer one)
                if (strlen($cleanLocation) > strlen($existing)) {
                    // Replace existing with more specific version
                    $index = array_search($existing, $processedLocations);
                    if ($index !== false) {
                        $processedLocations[$index] = $cleanLocation;
                    }
                }
                $isDuplicate = true;
                break;
            }
        }

        if (!$isDuplicate) {
            $processedLocations[] = $cleanLocation;
        }
    }

    return implode(', ', $processedLocations);
}

/**
 * Merge tags from multiple sources with intelligent filtering and deduplication
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

    // Clean, filter and intelligently deduplicate tags
    $cleanTags = filterAndDeduplicateTags($allTags);

    return !empty($cleanTags) ? implode(', ', array_slice($cleanTags, 0, 15)) : null;
}

/**
 * Filter out awards, age-related tags and intelligently deduplicate similar tags
 */
function filterAndDeduplicateTags($tags) {
    $cleanTags = [];

    // Define patterns to exclude
    $excludePatterns = [
        // Awards patterns
        '/award/i',
        '/winner/i',
        '/nominee/i',
        '/medal/i',
        '/prize/i',
        '/honor/i',
        '/newbery/i',
        '/caldecott/i',
        '/hugo/i',
        '/nebula/i',
        '/booker/i',
        '/pulitzer/i',

        // Age-related patterns
        '/\d+-\d+\s*(years?|yrs?)/i',
        '/\d+\+/i',
        '/ages?\s*\d+/i',
        '/children\'?s?\s*books?/i',
        '/juvenile/i',
        '/young\s*adult/i',
        '/middle\s*grade/i',
        '/teen/i',
        '/tween/i',
        '/early\s*reader/i',
        '/picture\s*book/i',
        '/board\s*book/i',
    ];

    foreach ($tags as $tag) {
        $cleanTag = trim($tag);
        if (empty($cleanTag) || strlen($cleanTag) <= 2 || strlen($cleanTag) > 100) {
            continue;
        }

        // Check if tag matches any exclude pattern
        $shouldExclude = false;
        foreach ($excludePatterns as $pattern) {
            if (preg_match($pattern, $cleanTag)) {
                $shouldExclude = true;
                break;
            }
        }

        if ($shouldExclude) {
            continue;
        }

        // Normalize tag
        $normalizedTag = ucwords(strtolower($cleanTag));

        // Check for intelligent deduplication
        if (!isDuplicateOrSubsetTag($normalizedTag, $cleanTags)) {
            $cleanTags[] = $normalizedTag;
        }
    }

    return $cleanTags;
}

/**
 * Check if a tag is a duplicate or subset of existing tags
 * E.g., "supernatural - juvenile fiction" is a subset of "supernatural"
 */
function isDuplicateOrSubsetTag($newTag, $existingTags) {
    $newTagLower = strtolower($newTag);

    foreach ($existingTags as $existingTag) {
        $existingTagLower = strtolower($existingTag);

        // Exact match (case-insensitive)
        if ($newTagLower === $existingTagLower) {
            return true;
        }

        // Check if new tag is a longer version of existing tag
        // E.g., "supernatural - juvenile fiction" contains "supernatural"
        if (strpos($newTagLower, $existingTagLower) === 0 && strlen($newTagLower) > strlen($existingTagLower)) {
            // New tag starts with existing tag and is longer - it's likely a subset
            return true;
        }

        // Check if existing tag is a longer version of new tag
        // E.g., existing "supernatural - juvenile fiction" vs new "supernatural"
        if (strpos($existingTagLower, $newTagLower) === 0 && strlen($existingTagLower) > strlen($newTagLower)) {
            // Replace the longer existing tag with the shorter, more general one
            $index = array_search($existingTag, $existingTags);
            if ($index !== false) {
                $existingTags[$index] = $newTag;
            }
            return true;
        }
    }

    return false;
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
 * Normalize publisher name with enhanced cleaning
 */
function normalizePublisherName($publisherName) {
    // Clean up common publisher name variations
    $publisherName = trim($publisherName);

    // Remove common suffixes that create duplicates - but be more conservative
    // Don't remove important distinguishing parts like "Children's Books"
    $suffixesToRemove = [
        ', an imprint of Random House Children\'s Books',
        ', an imprint of Random House',
        ' Publishing',
        ' Publishers',
        ' Ltd',
        ' Limited',
        ' Inc',
        ' LLC',
        ' Plc',
        ' PLC'
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
 * Find best matching publisher from existing database entries
 * Returns array with recommended publisher and confidence score
 * Uses enhanced similarity algorithm from comprehensive cleanup script
 */
function findBestPublisherMatch($publisherName) {
    global $db;

    error_log("PUBLISHER_TEST: findBestPublisherMatch called with: '$publisherName'");

    // Debug: Check if $db is available
    if (!isset($db) || !$db) {
        error_log("PUBLISHER_TEST: ERROR: \$db is not available in findBestPublisherMatch function!");
        error_log("PUBLISHER_TEST: Global variables available: " . implode(', ', array_keys($GLOBALS)));
        return null;
    }

    error_log("PUBLISHER_TEST: SUCCESS: \$db is available, type: " . get_class($db));

    if (empty($publisherName)) {
        error_log("PUBLISHER_TEST: Publisher name is empty, returning null");
        return null;
    }

    try {
        // Get all existing publishers from authors table (no type column exists)
        $stmt = $db->prepare("
            SELECT id, name
            FROM authors
            WHERE name IS NOT NULL
            AND name != ''
            ORDER BY name
        ");
        $stmt->execute();
        $existingPublishers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        error_log("PUBLISHER_TEST: Found " . count($existingPublishers) . " publishers in database");

        // Log first few publishers for debugging
        if (count($existingPublishers) > 0) {
            $samplePublishers = array_slice($existingPublishers, 0, 5);
            error_log("PUBLISHER_TEST: Sample publishers: " . json_encode(array_column($samplePublishers, 'name')));
        }

        $bestMatch = null;
        $bestScore = 0;

        foreach ($existingPublishers as $publisher) {
            $similarity = calculateEnhancedPublisherSimilarity($publisherName, $publisher['name']);

            if ($similarity > 50) { // Log any decent matches
                error_log("PUBLISHER_TEST: Publisher similarity: '$publisherName' vs '{$publisher['name']}' = $similarity%");
            }

            if ($similarity > $bestScore && $similarity >= 30) { // Much lower threshold for debugging
                $bestMatch = [
                    'id' => $publisher['id'],
                    'name' => $publisher['name'],
                    'confidence' => $similarity,
                    'match_type' => $similarity >= 90 ? 'exact' : ($similarity >= 80 ? 'partial' : 'fuzzy')
                ];
                $bestScore = $similarity;
                error_log("PUBLISHER_TEST: New best match: " . json_encode($bestMatch));
            }
        }

        // Only return matches with confidence >= 30% (lowered for debugging)
        $result = ($bestScore >= 30) ? $bestMatch : null;
        error_log("PUBLISHER_TEST: Final result for '$publisherName': " . json_encode($result));
        return $result;

    } catch (Exception $e) {
        error_log("PUBLISHER_TEST: Error finding publisher match: " . $e->getMessage());
        return null;
    }
}

/**
 * Enhanced publisher similarity calculation using the algorithm from comprehensive cleanup
 * Returns similarity score from 0-100
 */
function calculateEnhancedPublisherSimilarity($str1, $str2) {
    $original1 = $str1;
    $original2 = $str2;

    $str1 = strtolower(trim($str1));
    $str2 = strtolower(trim($str2));

    // If identical, return 100%
    if ($str1 === $str2) return 100;

    // Create normalized versions for better matching
    $norm1 = $str1;
    $norm2 = $str2;

    // Remove common publisher suffixes/prefixes that don't affect identity
    $commonWords = [
        'ltd', 'limited', 'plc', 'inc', 'books', 'publishing', 'publishers', 'press',
        'children\'s', 'childrens', 'uk', 'usa', 'group', 'imprint', 'young', 'readers',
        'an', 'of', 'for', '&', 'and', 'the'
    ];

    foreach ($commonWords as $word) {
        $norm1 = preg_replace('/\b' . preg_quote($word, '/') . '\b/', '', $norm1);
        $norm2 = preg_replace('/\b' . preg_quote($word, '/') . '\b/', '', $norm2);
    }

    // Clean up extra spaces
    $norm1 = preg_replace('/\s+/', ' ', trim($norm1));
    $norm2 = preg_replace('/\s+/', ' ', trim($norm2));

    // Special cases for known publisher variations
    $specialCases = [
        ['harper', 'harpercollins'],
        ['penguin', 'penguinrandomhouse'],
        ['random', 'penguinrandomhouse'],
        ['macmillan', 'macmillanchildrens'],
        ['scholastic', 'scholasticpress'],
        ['simon', 'simonschuster'],
        ['hachette', 'hachettechildrens']
    ];

    foreach ($specialCases as $case) {
        if ((strpos($norm1, $case[0]) !== false && strpos($norm2, $case[1]) !== false) ||
            (strpos($norm1, $case[1]) !== false && strpos($norm2, $case[0]) !== false)) {
            return 90;
        }
    }

    // Calculate various similarity metrics
    $scores = [];

    // 1. Levenshtein on normalized strings
    if (strlen($norm1) > 0 && strlen($norm2) > 0) {
        $levenshtein = levenshtein($norm1, $norm2);
        $maxLen = max(strlen($norm1), strlen($norm2));
        $scores[] = $maxLen > 0 ? (1 - $levenshtein / $maxLen) * 100 : 0;
    }

    // 2. Substring matching on original strings
    if (strlen($str1) > 3 && strlen($str2) > 3) {
        if (strpos($str1, $str2) !== false || strpos($str2, $str1) !== false) {
            $scores[] = 85;
        }
    }

    // 3. Word overlap on normalized strings
    $words1 = array_filter(explode(' ', $norm1));
    $words2 = array_filter(explode(' ', $norm2));
    if (count($words1) > 0 && count($words2) > 0) {
        $commonWords = array_intersect($words1, $words2);
        $wordOverlap = (count($commonWords) / max(count($words1), count($words2))) * 100;
        $scores[] = $wordOverlap;
    }

    // 4. Core publisher name matching (first significant word)
    $core1 = '';
    $core2 = '';
    if (count($words1) > 0) $core1 = $words1[0];
    if (count($words2) > 0) $core2 = $words2[0];

    if (strlen($core1) > 3 && strlen($core2) > 3) {
        if ($core1 === $core2) {
            $scores[] = 80;
        } elseif (strpos($core1, $core2) !== false || strpos($core2, $core1) !== false) {
            $scores[] = 70;
        }
    }

    // Return the highest similarity score
    return count($scores) > 0 ? max($scores) : 0;
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
 * Scrape Amazon buying options AND metadata (age range, reading level, etc.) with debugging output
 */
function scrapeAmazonBuyingOptions($isbn) {
    // Define AMAZON_DEBUG if not already defined
    if (!defined('AMAZON_DEBUG')) {
        define('AMAZON_DEBUG', false);
    }

    debugLog("scrapeAmazonBuyingOptions called with ISBN: $isbn");

    if (empty($isbn)) {
        debugLog("ERROR: ISBN is empty");
        if (AMAZON_DEBUG) {
            echo "<p><strong>❌ Error:</strong> ISBN is empty.</p>\n";
        }
        return [];
    }

    $cleanISBN = preg_replace('/[^0-9X]/i', '', $isbn);
    debugLog("Cleaned ISBN: $cleanISBN");

    $endpoints = [
        'desktop' => "https://www.amazon.co.uk/dp/{$cleanISBN}",
        'mobile'  => "https://www.amazon.co.uk/gp/aw/d/{$cleanISBN}"
    ];

    debugLog("Amazon endpoints", $endpoints);
    $userAgents = [
        'desktop' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'mobile'  => 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.0 Mobile/15E148 Safari/604.1'
    ];

    // Fetch both desktop and mobile versions
    $responses = [];
    foreach ($endpoints as $type => $url) {
        if (AMAZON_DEBUG) {
            echo "<p><strong>🔍 [{$type}] Scraping URL:</strong> <a href=\"{$url}\" target=\"_blank\">{$url}</a></p>\n";
        }
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, $userAgents[$type]);
        curl_setopt($ch, CURLOPT_ENCODING, '');
        $body = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        debugLog("Amazon $type request: HTTP $httpCode, " . strlen($body ?: '') . " bytes" . ($curlError ? ", Error: $curlError" : ""));

        if ($httpCode === 200 && $body) {
            $responses[$type] = $body;
            debugLog("Amazon $type response successful");
            if (AMAZON_DEBUG) {
                echo "<p><strong>📡 HTTP Status Code ({$type}):</strong> {$httpCode}</p>\n";
            }
        } else {
            debugLog("Amazon $type request failed: HTTP $httpCode" . ($curlError ? ", cURL Error: $curlError" : ""));
            if (AMAZON_DEBUG) {
                echo "<p><strong>⚠️ {$type} fetch failed or empty response (HTTP {$httpCode})</strong></p>\n";
            }
        }
    }
    if (empty($responses)) {
        if (AMAZON_DEBUG) {
            echo "<p><strong>❌ All fetch attempts failed. No response.</strong></p>\n";
        }
        return [];
    }
    // Save first successful response for inspection
    $debugFile = "/tmp/amazon_response_{$cleanISBN}.html";
    file_put_contents($debugFile, reset($responses));
    if (AMAZON_DEBUG) {
        echo "<p><strong>📁 Response saved to:</strong> {$debugFile}</p>\n";
    }

    // Updated patterns based on actual Amazon HTML structure
    // Look for the format divs and extract both href and price from aria-label
    // Account for complex nested structure with multiple spans and divs
    $patterns = [
        'Hardcover' => '/id="tmm-grid-swatch-HARDCOVER".*?<a href="([^"]*)".*?aria-label="£(\d+\.\d{2})"/is',
        'Paperback' => '/id="tmm-grid-swatch-PAPERBACK".*?<a href="([^"]*)".*?aria-label="£(\d+\.\d{2})"/is',
        'Kindle' => '/id="tmm-grid-swatch-KINDLE".*?<a href="([^"]*)".*?aria-label="£(\d+\.\d{2})"/is',
        'Audio CD' => '/id="tmm-grid-swatch-AUDIOBOOK".*?<a href="([^"]*)".*?aria-label="£(\d+\.\d{2})"/is',
    ];

    $buyingOptions = [];
    $selectedFormat = null;

    // First, detect which format is selected by checking each format individually
    foreach ($responses as $responseType => $resp) {
        // Check each format to see which one has "selected" class and javascript:void(0)
        $formatChecks = [
            'HARDCOVER' => 'Hardcover',
            'PAPERBACK' => 'Paperback',
            'KINDLE' => 'Kindle',
            'AUDIOBOOK' => 'Audio CD'
        ];

        foreach ($formatChecks as $formatKey => $formatName) {
            // Look for this specific format with selected class and javascript:void(0)
            $pattern = '/id="tmm-grid-swatch-' . $formatKey . '"[^>]*class="[^"]*selected[^"]*".*?href="javascript:void\(0\)".*?aria-label="£(\d+\.\d{2})"/is';

            if (preg_match($pattern, $resp, $selectedMatch)) {
                $selectedFormat = $formatName;
                $selectedPrice = $selectedMatch[1];

                if (AMAZON_DEBUG) {
                    echo "<p><strong>🎯 Selected format detected:</strong> {$selectedFormat} at £{$selectedPrice}</p>\n";
                }
                break 2; // Break out of both loops
            }
        }
    }

    foreach ($patterns as $label => $pattern) {
        $found = false;

        // Try the pattern against each response type (desktop/mobile)
        foreach ($responses as $responseType => $resp) {
            if (preg_match($pattern, $resp, $m)) {
                $relativeUrl = $m[1];  // URL captured in group 1
                $price = $m[2];        // Price captured in group 2

                if (AMAZON_DEBUG) {
                    echo "<p><strong>🎯 Pattern matched for {$label} in {$responseType}:</strong> URL: {$relativeUrl}, Price: £{$price}</p>\n";
                }

                // Determine full URL
                if (stripos($relativeUrl, 'javascript:') === 0 || empty($relativeUrl)) {
                    // This is the selected format - construct ref-based URL
                    $formatMap = [
                        'Hardcover' => 'hardcover',
                        'Paperback' => 'pap',
                        'Kindle' => 'kin',
                        'Audio CD' => 'abk'
                    ];
                    $suffix = 'tmm_' . $formatMap[$label] . '_swatch_0';
                    $fullUrl = "https://www.amazon.co.uk/gp/product/{$cleanISBN}/ref={$suffix}";
                } else {
                    $fullUrl = 'https://www.amazon.co.uk' . $relativeUrl;
                }

                $buyingOptions[$label] = [
                    'price' => '£' . $price,
                    'url'   => $fullUrl,
                    'is_selected' => ($label === $selectedFormat)
                ];

                if (AMAZON_DEBUG) {
                    echo "<p><strong>✅ Found {$label}:</strong> Price £{$price}, URL: {$fullUrl}" .
                         ($label === $selectedFormat ? " (SELECTED)" : "") . "</p>\n";
                }

                $found = true;
                break; // Move to next format after finding this one
            }
        }

        if (AMAZON_DEBUG && !$found) {
            echo "<p><strong>❌ No {$label} found in any response.</strong></p>\n";
        }
    }

    if (empty($buyingOptions)) {
        if (AMAZON_DEBUG) {
            echo "<p><strong>❌ No buying options detected after parsing.</strong></p>\n";
        }
    }

    // Extract additional metadata from detail bullets section
    $metadata = extractAmazonMetadata($responses);

    if (AMAZON_DEBUG && !empty($metadata)) {
        echo "<h4>📊 Amazon Metadata Found:</h4>\n";
        foreach ($metadata as $key => $value) {
            echo "<p><strong>$key:</strong> $value</p>\n";
        }
    }

    $result = [
        'buying_options' => $buyingOptions,
        'metadata' => $metadata
    ];

    debugLog("scrapeAmazonBuyingOptions returning", [
        'buying_options_count' => count($buyingOptions),
        'metadata_count' => count($metadata),
        'buying_options' => $buyingOptions,
        'metadata' => $metadata
    ]);

    return $result;
}

/**
 * Extract metadata from Amazon detail bullets section
 */
function extractAmazonMetadata($responses) {
    $metadata = [];

    foreach ($responses as $response) {
        if (empty($response)) continue;

        // Extract from detail bullets section
        if (preg_match('/<div[^>]*id="detailBullets_feature_div"[^>]*>(.*?)<\/div>/is', $response, $bulletMatch)) {
            $bulletContent = $bulletMatch[1];

            // Extract individual bullet points - FIXED patterns based on actual Amazon HTML structure
            // The HTML structure is: <span class="a-text-bold">Label‏:‎</span><span>Value</span>
            // Unicode characters ‏ (U+200F) and ‎ (U+200E) appear between label and colon
            $bulletPatterns = [
                'reading_age' => '/<span[^>]*class="a-text-bold"[^>]*>Reading age[^<]*<\/span>\s*<span[^>]*>([^<]+)<\/span>/i',
                'publisher' => '/<span[^>]*class="a-text-bold"[^>]*>Publisher[^<]*<\/span>\s*<span[^>]*>([^<]+)<\/span>/i',
                'publication_date' => '/<span[^>]*class="a-text-bold"[^>]*>Publication date[^<]*<\/span>\s*<span[^>]*>([^<]+)<\/span>/i',
                'language' => '/<span[^>]*class="a-text-bold"[^>]*>Language[^<]*<\/span>\s*<span[^>]*>([^<]+)<\/span>/i',
                'print_length' => '/<span[^>]*class="a-text-bold"[^>]*>Print length[^<]*<\/span>\s*<span[^>]*>([^<]+)<\/span>/i',
                'isbn_10' => '/<span[^>]*class="a-text-bold"[^>]*>ISBN-10[^<]*<\/span>\s*<span[^>]*>([^<]+)<\/span>/i',
                'isbn_13' => '/<span[^>]*class="a-text-bold"[^>]*>ISBN-13[^<]*<\/span>\s*<span[^>]*>([^<]+)<\/span>/i',
                'dimensions' => '/<span[^>]*class="a-text-bold"[^>]*>Dimensions[^<]*<\/span>\s*<span[^>]*>([^<]+)<\/span>/i',
                'item_weight' => '/<span[^>]*class="a-text-bold"[^>]*>Item weight[^<]*<\/span>\s*<span[^>]*>([^<]+)<\/span>/i',
                'series' => '/<span[^>]*class="a-text-bold"[^>]*>Book \d+ of \d+[^<]*<\/span>\s*<a[^>]*><span[^>]*>([^<]+)<\/span><\/a>/i',
            ];

            foreach ($bulletPatterns as $key => $pattern) {
                if (preg_match($pattern, $bulletContent, $matches)) {
                    $value = trim(strip_tags($matches[1]));

                    // CRITICAL FIX: Comprehensive cleaning of Amazon text
                    // Log raw value for debugging
                    error_log("AMAZON_EXTRACT_DEBUG: Raw value for '$key': '" . $value . "' (hex: " . bin2hex($value) . ")");

                    // Remove all Unicode directional marks and invisible characters
                    $value = preg_replace('/[\x{200E}\x{200F}\x{202A}-\x{202E}\x{2066}-\x{2069}\x{200B}\x{FEFF}]/u', '', $value);
                    // Remove HTML entities
                    $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    // Clean up whitespace
                    $value = preg_replace('/\s+/', ' ', $value);
                    // Final trim
                    $value = trim($value);

                    // Log cleaned value for debugging
                    error_log("AMAZON_EXTRACT_DEBUG: Cleaned value for '$key': '" . $value . "' (hex: " . bin2hex($value) . ")");

                    error_log("AMAZON_EXTRACT_DEBUG: Key '$key' extracted value: '" . $value . "' (length: " . strlen($value) . ")");

                    if (!empty($value) && $value !== '‎' && strlen($value) > 1) {
                        // For reading age alternatives, map them all to 'reading_age'
                        if (strpos($key, 'reading_age') === 0) {
                            $metadata['reading_age'] = $value;
                            error_log("AMAZON_EXTRACT_DEBUG: Set reading_age to: '$value'");
                        } else {
                            $metadata[$key] = $value;
                        }
                    } else {
                        error_log("AMAZON_EXTRACT_DEBUG: Rejected empty or invalid value for '$key': '" . $value . "'");
                    }
                }
            }
        }

        // Also try the carousel format for reading age and grade level
        if (preg_match('/<ol[^>]*class="a-carousel"[^>]*>(.*?)<\/ol>/is', $response, $carouselMatch)) {
            $carouselContent = $carouselMatch[1];

            // Extract reading age from carousel
            if (preg_match('/<span>Reading age<\/span>.*?<span[^>]*>([^<]+)<\/span>/is', $carouselContent, $readingMatch)) {
                $readingAge = trim(strip_tags($readingMatch[1]));

                // CRITICAL FIX: Apply same cleaning as bullet points
                error_log("AMAZON_CAROUSEL_DEBUG: Raw reading age: '" . $readingAge . "' (hex: " . bin2hex($readingAge) . ")");
                $readingAge = preg_replace('/[\x{200E}\x{200F}\x{202A}-\x{202E}\x{2066}-\x{2069}\x{200B}\x{FEFF}]/u', '', $readingAge);
                $readingAge = html_entity_decode($readingAge, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $readingAge = preg_replace('/\s+/', ' ', $readingAge);
                $readingAge = trim($readingAge);
                error_log("AMAZON_CAROUSEL_DEBUG: Cleaned reading age: '" . $readingAge . "' (hex: " . bin2hex($readingAge) . ")");

                error_log("AMAZON_CAROUSEL_DEBUG: Extracted reading age: '$readingAge' (length: " . strlen($readingAge) . ")");

                if (!empty($readingAge) && $readingAge !== '‎' && strlen($readingAge) > 1 && !isset($metadata['reading_age'])) {
                    $metadata['reading_age'] = $readingAge;
                    error_log("AMAZON_CAROUSEL_DEBUG: Set reading_age from carousel to: '$readingAge'");
                }
            }

            // REMOVED: Grade level extraction - Amazon grade levels are US-based, not UK standard
            // We only use Amazon reading age (which is UK-compatible) and map it to our standard age ranges
        }

        // If we found some metadata, we can break early
        if (!empty($metadata)) {
            break;
        }
    }

    return $metadata;
}

/**
 * Extract ASIN from Amazon URL
 */
function extractASINFromURL($url) {
    // Extract ASIN from URLs like /Coraline-Neil-Gaiman-ebook/dp/B0037B6Q66/ref=tmm_kin_swatch_0
    if (preg_match('/\/dp\/([A-Z0-9]{10})/', $url, $matches)) {
        return $matches[1];
    }
    return '';
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

/**
 * Get Amazon enrichment payload for a given ISBN:
 * - buying_options: array of all formats with price & URL
 * - selected_format: the default format (first in list, typically Hardcover)
 * - selected_price: price of the selected_format
 *
 * No caching since prices change frequently.
 *
 * @param string $isbn ISBN-10 or 13 for lookup
 * @return array {
 *   @type array  "buying_options"
 *   @type string "selected_format"
 *   @type string "selected_price"
 * }
 */
function getAmazonEnrichmentData($isbn, $currentBookData = null) {
    $cleanISBN = preg_replace('/[^0-9X]/i', '', $isbn);

    // Log the request for debugging
    error_log("getAmazonEnrichmentData called with ISBN: $isbn, cleaned: $cleanISBN");
    error_log("DUPLICATE_FIX: Current book data provided: " . ($currentBookData ? 'YES' : 'NO'));

    // Fetch raw buying options and metadata (no caching since prices change frequently)
    $amazonData = scrapeAmazonBuyingOptions($cleanISBN);

    error_log("scrapeAmazonBuyingOptions returned: " . json_encode($amazonData));

    $options = $amazonData['buying_options'] ?? [];
    $amazonMetadata = $amazonData['metadata'] ?? [];

    $selectedFormat = null;
    $selectedPrice = null;
    if (!empty($options)) {
        // Find the selected format (marked with is_selected = true)
        foreach ($options as $format => $data) {
            if (isset($data['is_selected']) && $data['is_selected']) {
                $selectedFormat = $format;
                $selectedPrice = $data['price'];
                break;
            }
        }

        // Fallback to first format if no selected format found
        if (!$selectedFormat) {
            $formats = array_keys($options);
            $selectedFormat = $formats[0];
            $selectedPrice = $options[$selectedFormat]['price'] ?? null;
        }
    }

    // Convert Amazon metadata to enrichment fields
    $enrichmentFields = [];

    if (!empty($amazonMetadata)) {
        // Map Amazon reading age to our standardized age_range field
        if (isset($amazonMetadata['reading_age'])) {
            $standardizedAgeRange = mapAmazonAgeRangeToStandard($amazonMetadata['reading_age']);
            if ($standardizedAgeRange) {
                $enrichmentFields['age_range'] = [
                    'label' => 'Age Range',
                    'new_data' => [
                        'value' => $standardizedAgeRange,
                        'source' => 'amazon',
                        'confidence' => 90, // Amazon data is usually very accurate
                        'status' => 'available',
                        'original_value' => $amazonMetadata['reading_age'] // Keep original for reference
                    ]
                ];

                // Also map to reading level using standard_reading_levels table
                global $db;
                $readingLevel = mapAgeRangeToReadingLevel($standardizedAgeRange, $db);
                if ($readingLevel) {
                    $enrichmentFields['reading_level'] = [
                        'label' => 'Reading Level',
                        'new_data' => [
                            'value' => $readingLevel,
                            'source' => 'amazon_derived',
                            'confidence' => 85, // Slightly lower confidence as it's derived
                            'status' => 'available',
                            'derived_from' => $standardizedAgeRange
                        ]
                    ];
                }
            }
        }

        // REMOVED: Amazon grade level to reading level mapping
        // Amazon grade levels are US-based and not compatible with UK reading standards
        // We only use Amazon reading age (which maps to our age ranges) and derive reading levels from that

        // Map other Amazon fields
        $fieldMappings = [
            'publisher' => 'Publisher',
            'publication_date' => 'Publication Date',
            'language' => 'Language',
            'print_length' => 'Page Count',
            'isbn_10' => 'ISBN-10',
            'isbn_13' => 'ISBN-13'
        ];

        foreach ($fieldMappings as $amazonField => $label) {
            if (isset($amazonMetadata[$amazonField])) {
                $value = $amazonMetadata[$amazonField];

                // Clean up page count to just the number
                if ($amazonField === 'print_length') {
                    $value = preg_replace('/[^0-9]/', '', $value);
                    if (empty($value)) continue;
                }

                // Clean up publication date
                if ($amazonField === 'publication_date') {
                    $value = formatPublicationDate($value);
                    if (empty($value)) continue;
                }

                // CRITICAL FIX: Map Amazon field names to database field names
                $fieldKey = $amazonField;
                if ($amazonField === 'print_length') {
                    $fieldKey = 'page_count';
                } elseif ($amazonField === 'isbn_10') {
                    $fieldKey = 'isbn';
                } elseif ($amazonField === 'isbn_13') {
                    $fieldKey = 'isbn13';
                }

                // FIXED: Allow ISBN fields to appear in enrichment modal for validation purposes
                // Even if they match current values, users should be able to see and validate them
                if (($fieldKey === 'isbn' || $fieldKey === 'isbn13') && $currentBookData) {
                    $currentValue = null;
                    if ($fieldKey === 'isbn' && isset($currentBookData['isbn'])) {
                        $currentValue = $currentBookData['isbn'];
                    } elseif ($fieldKey === 'isbn13' && isset($currentBookData['isbn13'])) {
                        $currentValue = $currentBookData['isbn13'];
                    }

                    // Normalize both values for comparison (remove hyphens)
                    $normalizedCurrent = preg_replace('/[^0-9X]/i', '', $currentValue ?? '');
                    $normalizedAmazon = preg_replace('/[^0-9X]/i', '', $value);

                    if ($normalizedCurrent === $normalizedAmazon) {
                        error_log("ISBN_VALIDATION: Amazon $fieldKey field matches current value ($normalizedCurrent) - showing for validation");
                        // Don't skip - show for validation purposes with 100% confidence
                    } else {
                        error_log("ISBN_VALIDATION: Amazon $fieldKey field differs from current - current: '$normalizedCurrent', amazon: '$normalizedAmazon'");
                    }
                }

                $enrichmentFields[$fieldKey] = [
                    'label' => $label,
                    'new_data' => [
                        'value' => $value,
                        'source' => 'amazon',
                        'confidence' => 90,
                        'status' => 'available',
                        'original_value' => $amazonMetadata[$amazonField] // Keep original for reference
                    ]
                ];
            }
        }
    }

    $payload = [
        'buying_options'   => $options,
        'selected_format'  => $selectedFormat,
        'selected_price'   => $selectedPrice,
        'enrichment_fields' => $enrichmentFields,
        'raw_metadata' => $amazonMetadata
    ];

    error_log("Final Amazon payload: " . json_encode($payload));

    return $payload;
}

// REMOVED: mapGradeLevelToReadingLevel function
// Amazon grade levels are US-based and not compatible with UK reading standards
// We only use Amazon reading age and map it to our standardized UK age ranges
// Reading levels are derived from age ranges using the standard_reading_levels table

/**
 * Map age range to reading level using standard_reading_levels table
 */
function mapAgeRangeToReadingLevel($ageRange, $dbConnection = null) {
    global $db;

    // Use provided connection or global connection
    $connection = $dbConnection ?: $db;

    if (!$connection) {
        error_log("READING_LEVEL_MAPPING: No database connection available");
        return null;
    }

    try {
        error_log("READING_LEVEL_MAPPING: Mapping age range '$ageRange' to reading level");
        $stmt = $connection->prepare("SELECT reading_stage FROM standard_reading_levels WHERE age_group = ? LIMIT 1");
        $stmt->execute([$ageRange]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            error_log("READING_LEVEL_MAPPING: Age range '$ageRange' mapped to reading level: " . $result['reading_stage']);
            return $result['reading_stage'];
        }

        error_log("READING_LEVEL_MAPPING: No reading level found for age range: $ageRange");
        return null;

    } catch (PDOException $e) {
        error_log("READING_LEVEL_MAPPING: Database error mapping age range to reading level: " . $e->getMessage());
        return null;
    }
}

/**
 * Map Amazon age range to our standardized age ranges
 */
function mapAmazonAgeRangeToStandard($amazonAgeRange) {
    // Clean up the Amazon age range - remove extra text like "from customers"
    $amazonAgeRange = trim($amazonAgeRange);
    $amazonAgeRange = preg_replace('/,?\s*from\s+customers?/i', '', $amazonAgeRange);
    $amazonAgeRange = trim($amazonAgeRange);

    error_log("SCRAPE_TEST: Processing Amazon age range: '$amazonAgeRange'");

    // Our standardized age ranges (from debug-age-ranges.php)
    $standardRanges = [
        '0-12 months', '12-24 months', '2-3 years', '3-4 years', '4-5 years',
        '5-6 years', '6-7 years', '7-8 years', '8-9 years', '9-10 years',
        '10-11 years', '11-14 years', '14-16 years', '16-18 years', '18+ years'
    ];

    // Direct mappings for common Amazon formats
    $directMappings = [
        // Exact matches
        '8-9 years' => '8-9 years',
        '9-10 years' => '9-10 years',
        '10-11 years' => '10-11 years',
        '11-14 years' => '11-14 years',

        // Amazon variations with spaces
        '8 - 9 years' => '8-9 years',
        '9 - 10 years' => '9-10 years',
        '10 - 11 years' => '10-11 years',
        '11 - 14 years' => '11-14 years',

        // Common Amazon ranges that need mapping
        '6-9 years' => '7-8 years',     // Map to midpoint (6+9)/2 = 7.5 → 7-8 years
        '6 - 9 years' => '7-8 years',   // With spaces
        '8-11 years' => '8-9 years',    // Map to closest standard range
        '8 - 11 years' => '8-9 years',  // With spaces
        '7-10 years' => '7-8 years',    // Map to closest standard range
        '7 - 10 years' => '7-8 years',  // With spaces
        '9-12 years' => '9-10 years',   // Map to closest standard range
        '9 - 12 years' => '9-10 years', // With spaces

        // Early years
        '0-3 years' => '2-3 years',
        '3-5 years' => '3-4 years',
        '5-7 years' => '5-6 years',

        // Teen/adult - REMOVED 12+ mappings to prevent hardcoded values
        '13+ years' => '11-14 years',
        '14+ years' => '14-16 years',
        '15+ years' => '14-16 years',
        '16+ years' => '16-18 years',
        '18+ years' => '18+ years',
        'Adult' => '18+ years',
        'Young Adult' => '14-16 years',
        'Teen' => '11-14 years'
    ];

    // Try direct mapping first
    if (isset($directMappings[$amazonAgeRange])) {
        $result = $directMappings[$amazonAgeRange];
        error_log("SCRAPE_TEST: Direct mapping found: '$amazonAgeRange' -> '$result'");
        return $result;
    }

    // Try case-insensitive mapping
    $lowerAmazonRange = strtolower($amazonAgeRange);
    foreach ($directMappings as $key => $value) {
        if (strtolower($key) === $lowerAmazonRange) {
            return $value;
        }
    }

    // Try to parse numeric ranges and find best fit
    if (preg_match('/(\d+)\s*[-–]\s*(\d+)\s*years?/i', $amazonAgeRange, $matches)) {
        $startAge = intval($matches[1]);
        $endAge = intval($matches[2]);
        $midAge = ($startAge + $endAge) / 2;

        debugLog("Amazon age range mapping: '$amazonAgeRange' → start:$startAge, end:$endAge, midpoint:$midAge");

        // Find the best matching standard range based on midpoint
        if ($midAge <= 0.5) return '0-12 months';
        if ($midAge <= 1.5) return '12-24 months';
        if ($midAge <= 2.5) return '2-3 years';
        if ($midAge <= 3.5) return '3-4 years';
        if ($midAge <= 4.5) return '4-5 years';
        if ($midAge <= 5.5) return '5-6 years';
        if ($midAge <= 6.5) return '6-7 years';
        if ($midAge <= 7.5) return '7-8 years';
        if ($midAge <= 8.5) return '8-9 years';
        if ($midAge <= 9.5) return '9-10 years';
        if ($midAge <= 10.5) return '10-11 years';
        if ($midAge <= 12.5) return '11-14 years';
        if ($midAge <= 15) return '14-16 years';
        if ($midAge <= 17) return '16-18 years';
        return '18+ years';
    }

    // Try single age parsing
    if (preg_match('/(\d+)\+?\s*years?/i', $amazonAgeRange, $matches)) {
        $age = intval($matches[1]);

        if ($age <= 1) return '0-12 months';
        if ($age <= 2) return '2-3 years';
        if ($age <= 3) return '3-4 years';
        if ($age <= 4) return '4-5 years';
        if ($age <= 5) return '5-6 years';
        if ($age <= 6) return '6-7 years';
        if ($age <= 7) return '7-8 years';
        if ($age <= 8) return '8-9 years';
        if ($age <= 9) return '9-10 years';
        if ($age <= 10) return '10-11 years';
        if ($age <= 12) return '11-14 years';
        if ($age <= 15) return '14-16 years';
        if ($age <= 17) return '16-18 years';
        return '18+ years';
    }

    // If no mapping found, return null
    error_log("Unable to map Amazon age range '$amazonAgeRange' to standard range");
    return null;
}

/**
 * Extract field value from Amazon data
 */
function extractAmazonFieldValue($amazonData, $fieldName, $currentISBN = '') {
    if (empty($amazonData) || !is_array($amazonData)) {
        return null;
    }

    switch ($fieldName) {
        case 'age_range':
            // Extract age range from Amazon metadata
            $readingAge = $amazonData['metadata']['reading_age'] ?? null;
            if ($readingAge) {
                debugLog("Processing reading_age: '$readingAge'");
                // Map Amazon age range to our standard format
                $standardAge = mapAmazonAgeRangeToStandard($readingAge);
                debugLog("Mapped to standard age: '$standardAge'");
                return $standardAge;
            }
            debugLog("No reading_age found in Amazon metadata");
            return null;

        case 'reading_level':
            // Derive reading level from age range
            $ageRange = extractAmazonFieldValue($amazonData, 'age_range', $currentISBN);
            if ($ageRange) {
                global $db;
                return mapAgeRangeToReadingLevel($ageRange, $db);
            }
            return null;

        case 'format':
            // Check for format in buying options or metadata
            $selectedFormat = $amazonData['selected_format'] ?? null;
            debugLog("Format extraction - selected_format: " . ($selectedFormat ?? 'NULL'));
            if ($selectedFormat) {
                debugLog("Found selected_format: '$selectedFormat'");
                return $selectedFormat;
            }

            // Check buying options for format
            $buyingOptions = $amazonData['buying_options'] ?? [];
            debugLog("Format extraction - buying_options available: " . (!empty($buyingOptions) ? 'YES' : 'NO'));
            if (!empty($buyingOptions)) {
                debugLog("Format extraction - available formats: " . implode(', ', array_keys($buyingOptions)));
                foreach ($buyingOptions as $format => $option) {
                    if (isset($option['is_selected']) && $option['is_selected']) {
                        debugLog("Found selected format from buying_options: '$format'");
                        return $format;
                    }
                }
                // If no selected format, return first available
                $firstFormat = array_keys($buyingOptions)[0] ?? null;
                if ($firstFormat) {
                    debugLog("Using first available format: '$firstFormat'");
                    return $firstFormat;
                }
            }

            debugLog("No format found in Amazon data");
            return null;

        case 'price_range':
            // Check for selected price first
            $selectedPrice = $amazonData['selected_price'] ?? null;
            if ($selectedPrice) {
                error_log("SCRAPE_TEST: Found selected_price: '$selectedPrice'");
                if (preg_match('/£(\d+\.\d{2})/', $selectedPrice, $matches)) {
                    $numericPrice = floatval($matches[1]);
                    $priceRange = mapPriceToRange($numericPrice);
                    error_log("SCRAPE_TEST: Mapped price £$numericPrice to range: '$priceRange'");
                    return $priceRange;
                }
            }

            // Check buying options for price
            $buyingOptions = $amazonData['buying_options'] ?? [];
            debugLog("Price extraction - buying_options available: " . (!empty($buyingOptions) ? 'YES' : 'NO'));
            if (!empty($buyingOptions)) {
                debugLog("Price extraction - buying_options data", $buyingOptions);
                foreach ($buyingOptions as $format => $option) {
                    if (isset($option['is_selected']) && $option['is_selected'] && isset($option['price'])) {
                        debugLog("Found selected price from buying_options: '{$option['price']}'");
                        if (preg_match('/£(\d+\.\d{2})/', $option['price'], $matches)) {
                            $numericPrice = floatval($matches[1]);
                            $priceRange = mapPriceToRange($numericPrice);
                            debugLog("Mapped price £$numericPrice to range: '$priceRange'");
                            return $priceRange;
                        }
                    }
                }
                // If no selected price, use first available
                $firstOption = reset($buyingOptions);
                if ($firstOption && isset($firstOption['price'])) {
                    debugLog("Using first available price: '{$firstOption['price']}'");
                    if (preg_match('/£(\d+\.\d{2})/', $firstOption['price'], $matches)) {
                        $numericPrice = floatval($matches[1]);
                        $priceRange = mapPriceToRange($numericPrice);
                        debugLog("Mapped price £$numericPrice to range: '$priceRange'");
                        return $priceRange;
                    }
                }
            }

            debugLog("No price found in Amazon data");
            return null;

        case 'purchase_links':
            $buyingOptions = $amazonData['buying_options'] ?? [];
            if (!empty($buyingOptions)) {
                // Return the selected format's URL or first available
                foreach ($buyingOptions as $option) {
                    if (isset($option['is_selected']) && $option['is_selected']) {
                        return $option['url'] ?? null;
                    }
                }
                // Fallback to first option
                $firstOption = reset($buyingOptions);
                return $firstOption['url'] ?? null;
            }
            return null;

        case 'publisher':
            $publisher = $amazonData['metadata']['publisher'] ?? null;
            if ($publisher) {
                error_log("SCRAPE_TEST: Found publisher: '$publisher'");
                return $publisher;
            }
            error_log("SCRAPE_TEST: No publisher found in Amazon data");
            return null;

        case 'page_count':
            // Amazon stores this as 'print_length' like "208 pages"
            $printLength = $amazonData['metadata']['print_length'] ?? null;
            if ($printLength) {
                error_log("SCRAPE_TEST: Found print_length: '$printLength'");
                // Extract number from "208 pages"
                if (preg_match('/(\d+)\s*pages?/i', $printLength, $matches)) {
                    $pageCount = intval($matches[1]);
                    error_log("SCRAPE_TEST: Extracted page count: $pageCount");
                    return $pageCount;
                }
            }
            error_log("SCRAPE_TEST: No page count found in Amazon data");
            return null;

        case 'language':
            $language = $amazonData['metadata']['language'] ?? null;
            if ($language) {
                error_log("SCRAPE_TEST: Found language: '$language'");
                return $language;
            }
            error_log("SCRAPE_TEST: No language found in Amazon data");
            return null;

        case 'publication_date':
            $pubDate = $amazonData['metadata']['publication_date'] ?? null;
            if ($pubDate) {
                error_log("SCRAPE_TEST: Found publication_date: '$pubDate'");
                return $pubDate;
            }
            error_log("SCRAPE_TEST: No publication_date found in Amazon data");
            return null;

        case 'isbn':
            $isbn10 = $amazonData['metadata']['isbn_10'] ?? null;
            if ($isbn10) {
                error_log("SCRAPE_TEST: Found ISBN-10: '$isbn10'");
                return $isbn10;
            }
            error_log("SCRAPE_TEST: No ISBN-10 found in Amazon data");
            return null;

        case 'isbn13':
            $isbn13 = $amazonData['metadata']['isbn_13'] ?? null;
            if ($isbn13) {
                error_log("SCRAPE_TEST: Found ISBN-13: '$isbn13'");
                return $isbn13;
            }
            error_log("SCRAPE_TEST: No ISBN-13 found in Amazon data");
            return null;

        case 'series':
            $series = $amazonData['metadata']['series'] ?? null;
            if ($series) {
                error_log("SCRAPE_TEST: Found series: '$series'");
                return $series;
            }
            error_log("SCRAPE_TEST: No series found in Amazon data");
            return null;

        default:
            return null;
    }
}

/**
 * Map Amazon age range to our standard format
 */
function mapAmazonAgeToStandardRange($amazonAge) {
    // Extract numbers from Amazon age string like "6 - 9 years, from customers"
    if (preg_match('/(\d+)\s*-\s*(\d+)\s*years?/i', $amazonAge, $matches)) {
        $minAge = intval($matches[1]);
        $maxAge = intval($matches[2]);

        // Calculate midpoint and map to our standard ranges
        $midpoint = ($minAge + $maxAge) / 2;

        if ($midpoint <= 1) return '0-12 months';
        if ($midpoint <= 2) return '12-24 months';
        if ($midpoint <= 3) return '2-3 years';
        if ($midpoint <= 4) return '3-4 years';
        if ($midpoint <= 5) return '4-5 years';
        if ($midpoint <= 6) return '5-6 years';
        if ($midpoint <= 7) return '6-7 years';
        if ($midpoint <= 8) return '7-8 years';
        if ($midpoint <= 9) return '8-9 years';
        if ($midpoint <= 10) return '9-10 years';
        if ($midpoint <= 11) return '10-11 years';
        if ($midpoint <= 14) return '11-14 years';
        if ($midpoint <= 16) return '14-16 years';
        if ($midpoint <= 18) return '16-18 years';
        return '18+ years';
    }

    return null;
}



/**
 * Map price to price range
 */
function mapPriceToRange($price) {
    if ($price < 5) return 'Under £5';
    if ($price < 10) return '£5-£10';
    if ($price < 15) return '£10-£15';
    if ($price < 20) return '£15-£20';
    if ($price < 30) return '£20-£30';
    return 'Over £30';
}