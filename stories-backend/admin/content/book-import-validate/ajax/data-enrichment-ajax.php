<?php
/**
 * AJAX handler for data enrichment operations
 */

// Set JSON header first
header('Content-Type: application/json');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in output, but log them

try {
    // Include auth check
    require_once '../../../includes/auth-check.php';

    // Include database connection
    require_once '../../../includes/db-connect.php';
    require_once '../functions/data-enrichment-functions.php';
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to load required files: ' . $e->getMessage()]);
    exit;
}

// Check if action is provided
if (!isset($_POST['action'])) {
    echo json_encode(['success' => false, 'message' => 'No action specified']);
    exit;
}

$action = $_POST['action'];

try {
    switch ($action) {
        case 'test':
            echo json_encode(['success' => true, 'message' => 'Data enrichment AJAX is working!', 'timestamp' => date('Y-m-d H:i:s')]);
            break;

        case 'get_enrichment_data':
            handleGetEnrichmentData();
            break;

        case 'apply_enrichment':
            handleApplyEnrichment();
            break;

        case 'check_goodreads_isbn':
            handleCheckGoodreadsISBN();
            break;

        case 'get_amazon_data':
            $isbn = $_POST['isbn'] ?? '';
            if (empty($isbn)) {
                echo json_encode(['success' => false, 'message' => 'ISBN is required']);
            } else {
                // Log the request for debugging
                error_log("Amazon data request for ISBN: $isbn");

                // Convert ISBN-13 to ISBN-10 for Amazon (Amazon requires ISBN-10)
                $isbn10 = convertISBN13ToISBN10($isbn);
                if (!$isbn10) {
                    // If conversion fails, try using the original ISBN
                    $isbn10 = $isbn;
                }
                error_log("Amazon ISBN conversion: $isbn -> $isbn10");

                // Ensure AMAZON_DEBUG is defined for this context
                if (!defined('AMAZON_DEBUG')) {
                    define('AMAZON_DEBUG', false); // Disable debug for production
                }

                // Fetch cached Amazon enrichment payload (includes all options, default format, and price)
                $amazonPayload = getAmazonEnrichmentData($isbn10);

                // Log the result for debugging
                error_log("Amazon payload result: " . json_encode($amazonPayload));

                // Structure the Amazon data properly for the enrichment system
                $structuredData = [];

                // Add purchase_links field if we have buying options
                if (!empty($amazonPayload['buying_options'])) {
                    $structuredData['purchase_links'] = [
                        'new_data' => [
                            'value' => json_encode($amazonPayload['buying_options']),
                            'source' => 'amazon_derived',
                            'confidence' => 90,
                            'status' => 'ready'
                        ]
                    ];
                }

                // Add format field if we have selected format
                if (!empty($amazonPayload['selected_format'])) {
                    $structuredData['format'] = [
                        'new_data' => [
                            'value' => $amazonPayload['selected_format'],
                            'source' => 'amazon_derived',
                            'confidence' => 95,
                            'status' => 'ready'
                        ]
                    ];
                }

                // Add price_range field if we have selected price
                if (!empty($amazonPayload['selected_price'])) {
                    // Calculate price range from selected price
                    $price = floatval(str_replace('£', '', $amazonPayload['selected_price']));
                    $priceRange = '';
                    if ($price < 5) {
                        $priceRange = 'Under £5';
                    } elseif ($price <= 10) {
                        $priceRange = '£5-£10';
                    } elseif ($price <= 15) {
                        $priceRange = '£10-£15';
                    } elseif ($price <= 20) {
                        $priceRange = '£15-£20';
                    } else {
                        $priceRange = 'Over £20';
                    }

                    $structuredData['price_range'] = [
                        'new_data' => [
                            'value' => $priceRange,
                            'source' => 'amazon_derived',
                            'confidence' => 90,
                            'status' => 'ready'
                        ]
                    ];
                }

                echo json_encode([
                    'success' => true,
                    'data' => $structuredData,
                    'debug' => [
                        'isbn_original' => $isbn,
                        'isbn_used' => $isbn10,
                        'options_count' => count($amazonPayload['buying_options'] ?? []),
                        'selected_format' => $amazonPayload['selected_format'] ?? null,
                        'selected_price' => $amazonPayload['selected_price'] ?? null,
                        'raw_amazon_payload' => $amazonPayload
                    ]
                ]);
            }
            break;

        case 'update_publisher_relationship':
            handleUpdatePublisherRelationship();
            break;

        case 'create_publisher':
            handleCreatePublisher();
            break;

        case 'fix_publisher_relationship':
            handleFixPublisherRelationship();
            break;

        case 'preview_merge_changes':
            handlePreviewMergeChanges();
            break;

        case 'merge_group_into_master':
            handleMergeGroupIntoMaster();
            break;

        case 'consolidate_authors':
            handleConsolidateAuthors();
            break;

        case 'merge_into_master':
            handleMergeIntoMaster();
            break;

        case 'merge_all_in_group':
            handleMergeAllInGroup();
            break;

        case 'fix_all_publisher_relationships':
            handleFixAllPublisherRelationships();
            break;

        case 'create_standard_reading_levels':
            handleCreateStandardReadingLevels();
            break;

        case 'create_publisher':
            handleCreatePublisher();
            break;

        case 'update_publisher_relationship':
            handleUpdatePublisherRelationship();
            break;

        case 'bulk_fix_selected':
            handleBulkFixSelected();
            break;

        case 'clean_erroneous_data':
            handleCleanErroneousData();
            break;

        case 'edit_erroneous_data':
            handleEditErroneousData();
            break;

        case 'clean_all_erroneous_series':
            handleCleanAllErroneousSeriesData();
            break;

        case 'migrate_all_reading_levels':
            handleMigrateAllReadingLevels();
            break;

        case 'synchronize_age_ranges':
            handleSynchronizeAgeRanges();
            break;

        case 'standardize_reading_level':
            handleStandardizeReadingLevel();
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action: ' . $action]);
            break;
    }
} catch (Exception $e) {
    error_log("Data enrichment error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error occurred']);
}

// Exit to prevent any further output
exit;

/**
 * Handle getting enrichment data for a book
 */
function handleGetEnrichmentData() {
    global $db;

    $title = $_POST['title'] ?? '';
    $author = $_POST['author'] ?? '';
    $currentISBN = $_POST['current_isbn'] ?? '';
    $bookId = $_POST['book_id'] ?? '';

    error_log("Data enrichment request: title='$title', author='$author', isbn='$currentISBN', bookId='$bookId'");

    if (empty($title)) {
        echo json_encode(['success' => false, 'message' => 'Title is required']);
        return;
    }

    try {
        // Get current book data from database
        $currentBookData = getCurrentBookData($bookId);

        // Get enriched data from APIs
        $enrichedData = getEnrichedBookData($title, $author, $currentISBN);

        // Filter and combine with current data
        $enrichedData['fields'] = filterRelevantFields($enrichedData['fields'], $currentBookData);
        $enrichedData['current_data'] = $currentBookData;
        error_log("Filtered enriched data: " . json_encode($enrichedData));

        // Get raw data before filtering for debugging
        $rawEnrichedData = getEnrichedBookData($title, $author, $currentISBN);

        echo json_encode([
            'success' => true,
            'data' => $enrichedData,
            'debug' => [
                'google_results_count' => count($enrichedData['sources_checked']),
                'sources_checked' => $enrichedData['sources_checked'],
                'confidence_score' => $enrichedData['confidence_score'],
                'isbn_validated' => $enrichedData['isbn_validated'],
                'fields_found' => array_keys($enrichedData['fields']),
                'request_params' => [
                    'title' => $title,
                    'author' => $author,
                    'isbn' => $currentISBN,
                    'book_id' => $bookId
                ],
                'raw_tags_before_filter' => $rawEnrichedData['fields']['tags'] ?? 'NOT_FOUND',
                'filtered_tags_after_filter' => $enrichedData['fields']['tags'] ?? 'NOT_FOUND',
                'google_books_raw' => $rawEnrichedData['google_match'] ?? 'NOT_FOUND',
                'openlibrary_raw' => $rawEnrichedData['openlibrary_match'] ?? 'NOT_FOUND'
            ]
        ]);
    } catch (Exception $e) {
        error_log("Enrichment error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Error getting enrichment data: ' . $e->getMessage(),
            'debug' => [
                'title' => $title,
                'author' => $author,
                'isbn' => $currentISBN,
                'book_id' => $bookId
            ]
        ]);
    }
}

/**
 * Handle applying enrichment changes to a book
 */
function handleApplyEnrichment() {
    global $db;

    $bookId = $_POST['book_id'] ?? '';
    $fieldsJson = $_POST['fields'] ?? '';

    // Enhanced debugging
    error_log("=== APPLY ENRICHMENT DEBUG START ===");
    error_log("Book ID: " . $bookId);
    error_log("Fields JSON: " . $fieldsJson);
    error_log("Raw POST data: " . json_encode($_POST));

    if (empty($bookId) || empty($fieldsJson)) {
        $errorMsg = 'Book ID and fields are required';
        error_log("ERROR: " . $errorMsg);
        echo json_encode(['success' => false, 'message' => $errorMsg]);
        return;
    }

    $fields = json_decode($fieldsJson, true);
    if (!$fields) {
        $errorMsg = 'Invalid fields data - JSON decode failed';
        error_log("ERROR: " . $errorMsg);
        error_log("JSON Error: " . json_last_error_msg());
        echo json_encode(['success' => false, 'message' => $errorMsg]);
        return;
    }

    error_log("Decoded fields: " . json_encode($fields));
    error_log("Number of fields to process: " . count($fields));

    // Build update query
    $updateFields = [];
    $params = [];
    $tagsToProcess = [];
    $publisherToProcess = null;
    $coverUrlToProcess = null;

    foreach ($fields as $fieldName => $fieldData) {
        $value = $fieldData['value'];

        error_log("Processing field: $fieldName with value: " . json_encode($value));
        error_log("Field data structure: " . json_encode($fieldData));

        // Handle special field mappings
        switch ($fieldName) {
            case 'publication_date':
                // Ensure date format is correct
                if (!empty($value)) {
                    $date = date('Y-m-d', strtotime($value));
                    if ($date !== '1970-01-01') {
                        $updateFields[] = "publication_date = ?";
                        $params[] = $date;
                    }
                }
                break;

            case 'page_count':
                // Ensure it's a number
                if (is_numeric($value)) {
                    $updateFields[] = "page_count = ?";
                    $params[] = intval($value);
                }
                break;

            case 'tags':
                // Handle tags (displayed as genres) using proper directory_item_tags junction table
                if (!empty($value)) {
                    // Store for later processing after main update
                    $tagsToProcess[$fieldName] = $value;
                }
                break;

            case 'publisher':
                // Handle publisher using authors table relationship
                if (!empty($value)) {
                    $publisherToProcess = $value;
                    // Also update the books.publisher field for backward compatibility
                    if (columnExists('books', 'publisher')) {
                        $updateFields[] = "publisher = ?";
                        $params[] = $value;
                    }
                }
                break;

            case 'maturity_rating':
                // Store the raw maturity rating if field exists
                if (!empty($value) && columnExists('books', 'maturity_rating')) {
                    $updateFields[] = "maturity_rating = ?";
                    $params[] = $value;
                }
                break;

            case 'average_rating':
            case 'rating_count':
                // Store rating data if fields exist
                if (!empty($value) && columnExists('books', $fieldName)) {
                    $updateFields[] = "$fieldName = ?";
                    $params[] = is_numeric($value) ? floatval($value) : $value;
                }
                break;

            case 'internet_archive_id':
                // Store Internet Archive ID if field exists
                if (!empty($value) && columnExists('books', 'internet_archive_id')) {
                    $updateFields[] = "internet_archive_id = ?";
                    $params[] = $value;
                }
                break;

            case 'publication_date':
                // Handle publication date with proper format conversion
                if (!empty($value) && columnExists('books', 'publication_date')) {
                    $formattedDate = formatPublicationDate($value);
                    if ($formattedDate) {
                        $updateFields[] = "publication_date = ?";
                        $params[] = $formattedDate;
                        error_log("Added publication_date to update: $value -> $formattedDate");
                    } else {
                        error_log("Skipping publication_date - invalid format: $value");
                    }
                }
                break;

            case 'cover_url':
                // Handle cover URL with download and optimization
                if (!empty($value)) {
                    // Store for later processing after main update
                    $coverUrlToProcess = $value;
                }
                break;

            default:
                // Standard string fields - only update if the field exists in the books table
                $columnExistsResult = columnExists('books', $fieldName);
                error_log("Column '$fieldName' exists in books table: " . ($columnExistsResult ? 'YES' : 'NO'));

                if (!empty($value) && $columnExistsResult) {
                    $updateFields[] = "$fieldName = ?";
                    $params[] = $value;
                    error_log("Added $fieldName to update: $value");
                } else {
                    if (empty($value)) {
                        error_log("Skipping $fieldName - empty value");
                    } else {
                        error_log("Skipping $fieldName - column does not exist in books table");
                    }
                }
                break;
        }
    }

    error_log("Total update fields prepared: " . count($updateFields));
    error_log("Update fields: " . json_encode($updateFields));
    error_log("Update params: " . json_encode($params));

    if (empty($updateFields)) {
        $errorMsg = 'No valid fields to update';
        error_log("ERROR: $errorMsg");
        echo json_encode(['success' => false, 'message' => $errorMsg]);
        return;
    }

    // Add validation status update
    $updateFields[] = "validation_status = 'partial'";
    $updateFields[] = "last_validated = NOW()";

    // Add book ID parameter
    $params[] = $bookId;

    // Execute update
    $sql = "UPDATE books SET " . implode(', ', $updateFields) . " WHERE directory_item_id = ?";

    error_log("Final SQL: $sql");
    error_log("Final params: " . json_encode($params));

    $stmt = $db->prepare($sql);
    if ($stmt->execute($params)) {
        error_log("SQL execution successful");
        $affectedRows = $stmt->rowCount();
        error_log("Affected rows: $affectedRows");
        // Process additional relationships and complex fields
        $additionalUpdates = [];

        // Handle publisher relationship
        if ($publisherToProcess) {
            $publisherId = processPublisherRelationship($bookId, $publisherToProcess);
            if ($publisherId) {
                $additionalUpdates[] = "Publisher relationship created (ID: $publisherId)";
            }
        }

        // Handle tags/genres relationships
        if (!empty($tagsToProcess)) {
            $tagResults = processTagsRelationships($bookId, $tagsToProcess);
            $additionalUpdates = array_merge($additionalUpdates, $tagResults);
        }

        // Handle cover URL download and optimization
        if ($coverUrlToProcess) {
            $coverResult = processCoverUrlDownload($bookId, $coverUrlToProcess);
            if ($coverResult) {
                $additionalUpdates[] = $coverResult;
            }
        }

        // Log the enrichment
        logEnrichmentActivity($bookId, array_keys($fields));

        echo json_encode([
            'success' => true,
            'message' => 'Book data updated successfully',
            'updated_fields' => array_keys($fields),
            'additional_updates' => $additionalUpdates
        ]);
    } else {
        $errorInfo = $stmt->errorInfo();
        error_log("SQL execution failed. Error info: " . json_encode($errorInfo));
        echo json_encode(['success' => false, 'message' => 'Database update failed: ' . $errorInfo[2]]);
    }
}

/**
 * Handle checking if ISBN exists on Goodreads
 */
function handleCheckGoodreadsISBN() {
    $isbn = $_POST['isbn'] ?? '';

    if (empty($isbn)) {
        echo json_encode(['success' => false, 'message' => 'ISBN is required']);
        return;
    }

    error_log("Checking Goodreads for ISBN: $isbn");

    try {
        $exists = validateISBNOnGoodreads($isbn);

        echo json_encode([
            'success' => true,
            'exists' => $exists,
            'isbn' => $isbn,
            'debug' => "Checked ISBN $isbn on Goodreads: " . ($exists ? 'FOUND' : 'NOT FOUND')
        ]);
    } catch (Exception $e) {
        error_log("Goodreads validation error for $isbn: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Error checking Goodreads: ' . $e->getMessage(),
            'isbn' => $isbn
        ]);
    }
}

/**
 * Get current book data from database
 */
function getCurrentBookData($bookId) {
    global $db;

    if (empty($bookId)) {
        return [];
    }

    try {
        // Get book data from books table
        $stmt = $db->prepare("
            SELECT b.*, di.title as directory_title
            FROM books b
            JOIN directory_items di ON b.directory_item_id = di.id
            WHERE b.directory_item_id = ?
        ");
        $stmt->execute([$bookId]);
        $bookData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$bookData) {
            return [];
        }

        // Get current tags from directory_item_tags junction table
        $stmt = $db->prepare("
            SELECT t.id, t.name
            FROM tags t
            JOIN directory_item_tags dit ON t.id = dit.tag_id
            WHERE dit.directory_item_id = ?
            ORDER BY t.name ASC
        ");
        $stmt->execute([$bookId]);
        $tags = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $bookData['current_tags'] = $tags;

        return $bookData;

    } catch (Exception $e) {
        error_log("Error getting current book data: " . $e->getMessage());
        return [];
    }
}

/**
 * Process enrichment fields for display - show only database fields with current vs new values
 */
function filterRelevantFields($fields, $currentBookData) {
    // Define actual database fields that exist in books table
    $validDbFields = [
        'isbn' => 'ISBN-10',
        'isbn13' => 'ISBN-13',
        'author' => 'Author',
        'publisher' => 'Publisher',
        'publication_date' => 'Publication Date',
        'page_count' => 'Page Count',
        'age_range' => 'Age Range',
        'reading_level' => 'Reading Level',
        'cover_url' => 'Cover Image',
        'series' => 'Series',
        'language' => 'Language',
        'format' => 'Format',
        'preview_link' => 'Preview Link',
        'price_range' => 'Price Range',
        'awards' => 'Awards',
        'characters' => 'Characters',
        'settings' => 'Settings',
        'tags' => 'Genres', // Special case - uses directory_item_tags junction table
        'maturity_rating' => 'Maturity Rating',
        'average_rating' => 'Average Rating',
        'rating_count' => 'Rating Count',
        'internet_archive_id' => 'Internet Archive ID',
        'alternative_isbns' => 'Alternative ISBNs',
        'purchase_links' => 'Purchase Links'
    ];

    $filteredFields = [];

    foreach ($validDbFields as $fieldName => $label) {
        // Get current value from database
        $currentValue = null;
        if ($fieldName === 'tags') {
            // Special handling for tags (displayed as genres)
            $currentValue = isset($currentBookData['current_tags']) ?
                array_column($currentBookData['current_tags'], 'name') : [];
        } else {
            $currentValue = $currentBookData[$fieldName] ?? null;
        }

        // Get new value from API data
        $newFieldData = $fields[$fieldName] ?? null;

        // Only include field if we have new data OR it's a field we want to show
        if ($newFieldData || $fieldName === 'tags') {
            $filteredFields[$fieldName] = [
                'label' => $label,
                'current_value' => $currentValue,
                'new_data' => $newFieldData
            ];

            // Handle multi-source fields
            if ($newFieldData && isset($newFieldData['options'])) {
                $filteredFields[$fieldName]['new_data'] = [
                    'options' => $newFieldData['options']
                ];
            } elseif ($newFieldData && isset($newFieldData['new_data'])) {
                // Amazon-derived fields that already have the correct structure
                $filteredFields[$fieldName]['new_data'] = $newFieldData['new_data'];
                error_log("Preserved Amazon field structure for $fieldName: " . json_encode($newFieldData['new_data']));
            } elseif ($newFieldData && isset($newFieldData['value'])) {
                // Single source field with actual data
                $filteredFields[$fieldName]['new_data'] = [
                    'value' => $newFieldData['value'],
                    'source' => $newFieldData['source'] ?? 'unknown',
                    'confidence' => $newFieldData['confidence'] ?? 0,
                    'status' => $newFieldData['status'] ?? 'available'
                ];
            } else {
                // No new data available
                $filteredFields[$fieldName]['new_data'] = [
                    'status' => 'unknown',
                    'value' => null,
                    'source' => 'unknown',
                    'confidence' => 0
                ];
            }
        }
    }

    return $filteredFields;
}

/**
 * Log enrichment activity for audit purposes
 */
function logEnrichmentActivity($bookId, $updatedFields) {
    global $db;

    try {
        $logData = [
            'book_id' => $bookId,
            'action' => 'data_enrichment',
            'fields_updated' => $updatedFields,
            'timestamp' => date('Y-m-d H:i:s'),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        ];

        // You could insert this into a log table if you have one
        error_log("Book enrichment: " . json_encode($logData));

    } catch (Exception $e) {
        error_log("Failed to log enrichment activity: " . $e->getMessage());
    }
}

/**
 * Validate that the book exists and user has permission
 */
function validateBookAccess($bookId) {
    global $db;

    $stmt = $db->prepare("SELECT directory_item_id FROM books WHERE directory_item_id = ?");
    $stmt->execute([$bookId]);

    return $stmt->fetch() !== false;
}

/**
 * Check if a column exists in a table
 */
function columnExists($table, $column) {
    global $db;

    try {
        // For SHOW COLUMNS, we need to sanitize inputs manually since prepared statements don't work
        // Only allow alphanumeric characters and underscores for security
        $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
        $column = preg_replace('/[^a-zA-Z0-9_]/', '', $column);

        if (empty($table) || empty($column)) {
            return false;
        }

        $sql = "SHOW COLUMNS FROM `$table` LIKE '$column'";
        $stmt = $db->query($sql);
        return $stmt->fetch() !== false;
    } catch (Exception $e) {
        error_log("Error checking column existence: " . $e->getMessage());
        return false;
    }
}

/**
 * Map Google Books maturity rating to age range using actual age_ranges table
 */
function mapMaturityToAgeRangeFromTable($maturityRating) {
    global $db;

    try {
        // Check if age_ranges table exists
        $stmt = $db->query("SHOW TABLES LIKE 'age_ranges'");
        if ($stmt->rowCount() === 0) {
            // Fallback to simple mapping if table doesn't exist
            return mapMaturityToAgeRange($maturityRating);
        }

        // Map maturity rating to age range names in the database
        $mappings = [
            'NOT_MATURE' => ['All Ages', '0-12', '0-18', 'Children', 'Young Adult'],
            'MATURE' => ['18+', 'Adult', 'Mature']
        ];

        $searchTerms = $mappings[strtoupper($maturityRating)] ?? [];

        foreach ($searchTerms as $term) {
            $stmt = $db->prepare("SELECT range_name FROM age_ranges WHERE range_name LIKE ? LIMIT 1");
            $stmt->execute(["%$term%"]);
            $result = $stmt->fetch();

            if ($result) {
                return $result['range_name'];
            }
        }

        // If no match found, return the first available age range for the category
        if (strtoupper($maturityRating) === 'NOT_MATURE') {
            $stmt = $db->query("SELECT range_name FROM age_ranges ORDER BY id ASC LIMIT 1");
        } else {
            $stmt = $db->query("SELECT range_name FROM age_ranges ORDER BY id DESC LIMIT 1");
        }

        $result = $stmt->fetch();
        return $result ? $result['range_name'] : null;

    } catch (Exception $e) {
        error_log("Error mapping maturity rating: " . $e->getMessage());
        return mapMaturityToAgeRange($maturityRating);
    }
}

/**
 * Map Google Books maturity rating to age range (fallback)
 */
function mapMaturityToAgeRange($maturityRating) {
    switch (strtoupper($maturityRating)) {
        case 'NOT_MATURE':
            return 'All Ages';
        case 'MATURE':
            return '18+';
        default:
            return null;
    }
}

/**
 * Process publisher relationship using authors table
 */
function processPublisherRelationship($bookId, $publisherName) {
    global $db;

    try {
        // Clean up publisher name
        $cleanPublisherName = trim($publisherName);

        // Check which column exists for publisher type
        $hasTypeColumn = false;
        $hasAuthorTypeColumn = false;

        try {
            $stmt = $db->query("SHOW COLUMNS FROM authors LIKE 'type'");
            $hasTypeColumn = $stmt->fetch() !== false;
        } catch (Exception $e) {}

        try {
            $stmt = $db->query("SHOW COLUMNS FROM authors LIKE 'author_type'");
            $hasAuthorTypeColumn = $stmt->fetch() !== false;
        } catch (Exception $e) {}

        // Check for exact match first
        if ($hasTypeColumn) {
            $stmt = $db->prepare("SELECT id, name FROM authors WHERE name = ? AND type = 'publisher'");
        } elseif ($hasAuthorTypeColumn) {
            $stmt = $db->prepare("SELECT id, name FROM authors WHERE name = ? AND author_type = 'publisher'");
        } else {
            // No type column - just match by name (less precise)
            $stmt = $db->prepare("SELECT id, name FROM authors WHERE name = ?");
        }
        $stmt->execute([$cleanPublisherName]);
        $existingPublisher = $stmt->fetch();

        if ($existingPublisher) {
            $publisherId = $existingPublisher['id'];
            error_log("Found exact publisher match: {$existingPublisher['name']} (ID: $publisherId)");
        } else {
            // Check for similar publishers to prevent duplicates
            if ($hasTypeColumn) {
                $stmt = $db->prepare("SELECT id, name FROM authors WHERE type = 'publisher'");
            } elseif ($hasAuthorTypeColumn) {
                $stmt = $db->prepare("SELECT id, name FROM authors WHERE author_type = 'publisher'");
            } else {
                // No type column - get all authors
                $stmt = $db->prepare("SELECT id, name FROM authors");
            }
            $stmt->execute();
            $allPublishers = $stmt->fetchAll();

            $bestMatch = null;
            $bestSimilarity = 0;

            foreach ($allPublishers as $publisher) {
                // Calculate similarity
                $similarity = similar_text(strtolower($cleanPublisherName), strtolower($publisher['name']), $percent);

                // Check for common variations
                $isVariation = false;
                $name1 = strtolower(str_replace([' ', "'", '"', '-'], '', $cleanPublisherName));
                $name2 = strtolower(str_replace([' ', "'", '"', '-'], '', $publisher['name']));

                // Check if one is contained in the other (e.g., "Harper Collins" vs "HarperCollins Children's Books")
                if (strpos($name1, $name2) !== false || strpos($name2, $name1) !== false) {
                    $isVariation = true;
                    $percent = 90; // High similarity for variations
                }

                if ($percent > $bestSimilarity && ($percent >= 85 || $isVariation)) {
                    $bestMatch = $publisher;
                    $bestSimilarity = $percent;
                }
            }

            if ($bestMatch && $bestSimilarity >= 85) {
                // Use existing similar publisher
                $publisherId = $bestMatch['id'];
                error_log("Found similar publisher match: '{$bestMatch['name']}' for '$cleanPublisherName' (similarity: {$bestSimilarity}%)");
            } else {
                // Create new publisher
                $slug = createSlug($cleanPublisherName);
                if ($hasTypeColumn) {
                    $stmt = $db->prepare("INSERT INTO authors (name, type, slug) VALUES (?, 'publisher', ?)");
                    $stmt->execute([$cleanPublisherName, $slug]);
                } elseif ($hasAuthorTypeColumn) {
                    $stmt = $db->prepare("INSERT INTO authors (name, author_type, slug) VALUES (?, 'publisher', ?)");
                    $stmt->execute([$cleanPublisherName, $slug]);
                } else {
                    // No type column - just create as regular author
                    $stmt = $db->prepare("INSERT INTO authors (name, slug) VALUES (?, ?)");
                    $stmt->execute([$cleanPublisherName, $slug]);
                }
                $publisherId = $db->lastInsertId();
                error_log("Created new publisher: '$cleanPublisherName' (ID: $publisherId)");
            }
        }

        // Update books.publisher_id
        $stmt = $db->prepare("UPDATE books SET publisher_id = ? WHERE directory_item_id = ?");
        $stmt->execute([$publisherId, $bookId]);

        return $publisherId;

    } catch (Exception $e) {
        error_log("Error processing publisher relationship: " . $e->getMessage());
        return null;
    }
}

/**
 * Process tags/genres relationships using directory_item_tags junction table
 */
function processTagsRelationships($bookId, $tagsToProcess) {
    global $db;

    $results = [];

    try {
        // Get current tags for this book
        $stmt = $db->prepare("
            SELECT t.id, t.name
            FROM tags t
            JOIN directory_item_tags dit ON t.id = dit.tag_id
            WHERE dit.directory_item_id = ?
        ");
        $stmt->execute([$bookId]);
        $currentTags = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $currentTagNames = array_column($currentTags, 'name');

        foreach ($tagsToProcess as $fieldName => $value) {
            // Parse tags from value
            $newTags = [];
            if (is_array($value)) {
                $newTags = $value;
            } elseif (is_string($value)) {
                $newTags = array_map('trim', explode(',', $value));
            }

            // Filter out age-related and award-related tags
            $filteredTags = [];
            foreach ($newTags as $tagName) {
                if (empty($tagName)) continue;

                $tagLower = strtolower(trim($tagName));

                // Skip age-related tags
                if (preg_match('/^\d+-\d+$/', $tagLower) ||
                    preg_match('/^\d+\+$/', $tagLower) ||
                    strpos($tagLower, 'years') !== false ||
                    strpos($tagLower, 'age') !== false ||
                    $tagLower === 'teen' ||
                    $tagLower === 'young adult' ||
                    $tagLower === 'adult' ||
                    $tagLower === 'coming of age' ||
                    in_array($tagLower, ['12+', '13+', '14+', '16+', '18+'])) {
                    continue;
                }

                // Skip award-related tags
                if (strpos($tagLower, 'award') !== false ||
                    strpos($tagLower, 'winner') !== false ||
                    strpos($tagLower, 'medal') !== false ||
                    strpos($tagLower, 'prize') !== false) {
                    continue;
                }

                $filteredTags[] = trim($tagName);
            }

            // Merge with current tags and deduplicate
            $allTags = array_merge($currentTagNames, $filteredTags);
            $uniqueTags = [];

            foreach ($allTags as $tagName) {
                $tagLower = strtolower(trim($tagName));
                $found = false;

                // Check for duplicates (case-insensitive)
                foreach ($uniqueTags as $existingTag) {
                    if (strtolower($existingTag) === $tagLower) {
                        $found = true;
                        break;
                    }
                }

                if (!$found) {
                    $uniqueTags[] = trim($tagName);
                }
            }

            $addedTags = [];

            foreach ($filteredTags as $tagName) {
                if (empty($tagName)) continue;

                // Skip if tag already exists for this book
                if (in_array($tagName, $currentTagNames)) {
                    continue;
                }

                // Check if tag exists in database
                $stmt = $db->prepare("SELECT id FROM tags WHERE LOWER(name) = LOWER(?)");
                $stmt->execute([$tagName]);
                $existingTag = $stmt->fetch();

                if ($existingTag) {
                    $tagId = $existingTag['id'];
                } else {
                    // Create new tag
                    $stmt = $db->prepare("INSERT INTO tags (name, slug) VALUES (?, ?)");
                    $slug = createSlug($tagName);
                    $stmt->execute([$tagName, $slug]);
                    $tagId = $db->lastInsertId();
                }

                // Check if relationship already exists
                $stmt = $db->prepare("SELECT * FROM directory_item_tags WHERE directory_item_id = ? AND tag_id = ?");
                $stmt->execute([$bookId, $tagId]);

                if (!$stmt->fetch()) {
                    // Create relationship
                    $stmt = $db->prepare("INSERT INTO directory_item_tags (directory_item_id, tag_id) VALUES (?, ?)");
                    $stmt->execute([$bookId, $tagId]);
                    $addedTags[] = $tagName;
                }
            }

            if (!empty($addedTags)) {
                $results[] = ucfirst($fieldName) . " added: " . implode(', ', $addedTags);
            } else {
                $results[] = ucfirst($fieldName) . ": No new tags added (filtered or already exist)";
            }
        }

    } catch (Exception $e) {
        error_log("Error processing tags relationships: " . $e->getMessage());
        $results[] = "Error processing tags: " . $e->getMessage();
    }

    return $results;
}

/**
 * Process cover URL download and optimization
 */
function processCoverUrlDownload($bookId, $coverUrl) {
    global $db;

    try {
        // Check if media processing functions are available
        if (!function_exists('downloadAndOptimizeImage')) {
            // Fallback: just update the cover_url field with external URL
            $stmt = $db->prepare("UPDATE books SET cover_url = ? WHERE directory_item_id = ?");
            $stmt->execute([$coverUrl, $bookId]);
            return "Cover URL updated (external link)";
        }

        // Get book title for filename
        $stmt = $db->prepare("SELECT di.title FROM directory_items di JOIN books b ON di.id = b.directory_item_id WHERE b.directory_item_id = ?");
        $stmt->execute([$bookId]);
        $book = $stmt->fetch();

        if (!$book) {
            return "Error: Book not found";
        }

        // Create filename from title
        $filename = createSlug($book['title']) . '_cover';

        // Download and optimize image (this would need to be implemented based on your media.php logic)
        $optimizedUrl = downloadAndOptimizeImage($coverUrl, $filename, 'book_covers');

        if ($optimizedUrl) {
            // Update with optimized local URL
            $stmt = $db->prepare("UPDATE books SET cover_url = ? WHERE directory_item_id = ?");
            $stmt->execute([$optimizedUrl, $bookId]);
            return "Cover image downloaded and optimized";
        } else {
            // Fallback to external URL
            $stmt = $db->prepare("UPDATE books SET cover_url = ? WHERE directory_item_id = ?");
            $stmt->execute([$coverUrl, $bookId]);
            return "Cover URL updated (download failed, using external link)";
        }

    } catch (Exception $e) {
        error_log("Error processing cover URL: " . $e->getMessage());
        return "Error processing cover: " . $e->getMessage();
    }
}

/**
 * Format publication date to MySQL date format
 */
function formatPublicationDate($dateString) {
    if (empty($dateString)) {
        return null;
    }

    try {
        // Handle various date formats
        $dateString = trim($dateString);

        // If it's already in YYYY-MM-DD format, return as is
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateString)) {
            return $dateString;
        }

        // Handle DD/MM/YYYY format (like 28/05/2025)
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $dateString, $matches)) {
            $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
            $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
            $year = $matches[3];
            return "$year-$month-$day";
        }

        // Handle MM/DD/YYYY format
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $dateString, $matches)) {
            // Ambiguous - assume MM/DD/YYYY if first number > 12
            if ($matches[1] > 12) {
                $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
                $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
            } else {
                $month = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
                $day = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
            }
            $year = $matches[3];
            return "$year-$month-$day";
        }

        // Handle YYYY-MM-DD format with different separators
        if (preg_match('/^(\d{4})[-\/](\d{1,2})[-\/](\d{1,2})$/', $dateString, $matches)) {
            $year = $matches[1];
            $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
            $day = str_pad($matches[3], 2, '0', STR_PAD_LEFT);
            return "$year-$month-$day";
        }

        // Handle year only (like "2013")
        if (preg_match('/^\d{4}$/', $dateString)) {
            return "$dateString-01-01";
        }

        // Try to parse with DateTime
        $date = new DateTime($dateString);
        return $date->format('Y-m-d');

    } catch (Exception $e) {
        error_log("Failed to parse publication date '$dateString': " . $e->getMessage());
        return null;
    }
}

/**
 * Create URL-friendly slug from string
 */
function createSlug($string) {
    $slug = strtolower(trim($string));
    $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    $slug = trim($slug, '-');
    return $slug;
}

/**
 * Handle updating publisher relationship for a book
 */
function handleUpdatePublisherRelationship() {
    global $db;

    $bookId = $_POST['book_id'] ?? '';
    $publisherId = $_POST['publisher_id'] ?? '';

    if (empty($bookId) || empty($publisherId)) {
        echo json_encode(['success' => false, 'message' => 'Book ID and Publisher ID are required']);
        return;
    }

    try {
        $stmt = $db->prepare("UPDATE books SET publisher_id = ? WHERE directory_item_id = ?");
        if ($stmt->execute([$publisherId, $bookId])) {
            echo json_encode([
                'success' => true,
                'message' => "Publisher relationship updated successfully",
                'book_id' => $bookId,
                'publisher_id' => $publisherId
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update publisher relationship']);
        }
    } catch (Exception $e) {
        error_log("Error updating publisher relationship: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Handle creating a new publisher and linking to book
 */
function handleCreatePublisher() {
    global $db;

    $publisherName = $_POST['publisher_name'] ?? '';
    $bookId = $_POST['book_id'] ?? '';

    if (empty($publisherName) || empty($bookId)) {
        echo json_encode(['success' => false, 'message' => 'Publisher name and Book ID are required']);
        return;
    }

    try {
        // Check which column exists for publisher type
        $hasTypeColumn = false;
        $hasAuthorTypeColumn = false;

        try {
            $stmt = $db->query("SHOW COLUMNS FROM authors LIKE 'type'");
            $hasTypeColumn = $stmt->fetch() !== false;
        } catch (Exception $e) {}

        try {
            $stmt = $db->query("SHOW COLUMNS FROM authors LIKE 'author_type'");
            $hasAuthorTypeColumn = $stmt->fetch() !== false;
        } catch (Exception $e) {}

        // Create slug
        $slug = createSlug($publisherName);

        // Create publisher
        if ($hasTypeColumn) {
            $stmt = $db->prepare("INSERT INTO authors (name, type, slug) VALUES (?, 'publisher', ?)");
            $stmt->execute([$publisherName, $slug]);
        } elseif ($hasAuthorTypeColumn) {
            $stmt = $db->prepare("INSERT INTO authors (name, author_type, slug) VALUES (?, 'publisher', ?)");
            $stmt->execute([$publisherName, $slug]);
        } else {
            $stmt = $db->prepare("INSERT INTO authors (name, slug) VALUES (?, ?)");
            $stmt->execute([$publisherName, $slug]);
        }

        $publisherId = $db->lastInsertId();

        // Link to book
        $stmt = $db->prepare("UPDATE books SET publisher_id = ?, publisher = ? WHERE directory_item_id = ?");
        $stmt->execute([$publisherId, $publisherName, $bookId]);

        echo json_encode([
            'success' => true,
            'message' => "Publisher created and linked successfully",
            'publisher_id' => $publisherId,
            'publisher_name' => $publisherName,
            'book_id' => $bookId
        ]);

    } catch (Exception $e) {
        error_log("Error creating publisher: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Handle automatically fixing publisher relationship for a book
 */
function handleFixPublisherRelationship() {
    global $db;

    $bookId = $_POST['book_id'] ?? '';

    if (empty($bookId)) {
        echo json_encode(['success' => false, 'message' => 'Book ID is required']);
        return;
    }

    try {
        // Get book's publisher name
        $stmt = $db->prepare("SELECT publisher FROM books WHERE directory_item_id = ?");
        $stmt->execute([$bookId]);
        $book = $stmt->fetch();

        if (!$book || empty($book['publisher'])) {
            echo json_encode(['success' => false, 'message' => 'Book has no publisher name to match']);
            return;
        }

        $publisherName = trim($book['publisher']);

        // Try to find existing publisher
        $stmt = $db->prepare("SELECT id, name FROM authors WHERE name = ?");
        $stmt->execute([$publisherName]);
        $existingPublisher = $stmt->fetch();

        if ($existingPublisher) {
            // Update relationship
            $stmt = $db->prepare("UPDATE books SET publisher_id = ? WHERE directory_item_id = ?");
            $stmt->execute([$existingPublisher['id'], $bookId]);

            echo json_encode([
                'success' => true,
                'message' => "Linked to existing publisher: {$existingPublisher['name']} (ID: {$existingPublisher['id']})",
                'publisher_id' => $existingPublisher['id']
            ]);
        } else {
            // Create new publisher using the existing create function logic
            $_POST['publisher_name'] = $publisherName;
            handleCreatePublisher();
        }

    } catch (Exception $e) {
        error_log("Error fixing publisher relationship: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Handle previewing merge changes
 */
function handlePreviewMergeChanges() {
    global $db;

    $masterId = $_POST['master_id'] ?? '';
    $otherIds = $_POST['other_ids'] ?? '';

    if (empty($masterId) || empty($otherIds)) {
        echo json_encode(['success' => false, 'message' => 'Master ID and other IDs are required']);
        return;
    }

    try {
        $otherIdsArray = explode(',', $otherIds);
        $otherIdsArray = array_map('trim', $otherIdsArray);

        // Count books that will be updated
        $placeholders = str_repeat('?,', count($otherIdsArray) - 1) . '?';
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM books WHERE publisher_id IN ($placeholders)");
        $stmt->execute($otherIdsArray);
        $booksToUpdate = $stmt->fetchColumn();

        // Get sample affected books
        $stmt = $db->prepare("
            SELECT di.title
            FROM books b
            JOIN directory_items di ON b.directory_item_id = di.id
            WHERE b.publisher_id IN ($placeholders)
            LIMIT 10
        ");
        $stmt->execute($otherIdsArray);
        $affectedBooks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'books_to_update' => $booksToUpdate,
            'publishers_to_remove' => count($otherIdsArray),
            'affected_books' => $affectedBooks
        ]);

    } catch (Exception $e) {
        error_log("Error previewing merge changes: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Handle merging a group into master
 */
function handleMergeGroupIntoMaster() {
    global $db;

    $masterId = $_POST['master_id'] ?? '';
    $otherIds = $_POST['other_ids'] ?? '';
    $masterName = $_POST['master_name'] ?? '';

    if (empty($masterId) || empty($otherIds)) {
        echo json_encode(['success' => false, 'message' => 'Master ID and other IDs are required']);
        return;
    }

    try {
        $db->beginTransaction();

        $otherIdsArray = explode(',', $otherIds);
        $otherIdsArray = array_map('trim', $otherIdsArray);

        // First, get the names of all publishers being merged
        $allIds = array_merge([$masterId], $otherIdsArray);
        $placeholders = str_repeat('?,', count($allIds) - 1) . '?';
        $stmt = $db->prepare("SELECT id, name FROM authors WHERE id IN ($placeholders)");
        $stmt->execute($allIds);
        $publishers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get master publisher name
        $masterPublisher = null;
        foreach ($publishers as $pub) {
            if ($pub['id'] == $masterId) {
                $masterPublisher = $pub;
                break;
            }
        }

        if (!$masterPublisher) {
            throw new Exception("Master publisher not found");
        }

        $publisherNames = array_column($publishers, 'name');

        // Update books that match by publisher name (text field) to use master publisher_id AND master publisher name
        $namePlaceholders = str_repeat('?,', count($publisherNames) - 1) . '?';
        $stmt = $db->prepare("UPDATE books SET publisher_id = ?, publisher = ? WHERE publisher IN ($namePlaceholders)");
        $nameParams = array_merge([$masterId, $masterPublisher['name']], $publisherNames);
        $stmt->execute($nameParams);
        $booksUpdatedByName = $stmt->rowCount();

        // Update books that already have publisher_id set to other IDs
        if (!empty($otherIdsArray)) {
            $idPlaceholders = str_repeat('?,', count($otherIdsArray) - 1) . '?';
            $stmt = $db->prepare("UPDATE books SET publisher_id = ?, publisher = ? WHERE publisher_id IN ($idPlaceholders)");
            $idParams = array_merge([$masterId, $masterPublisher['name']], $otherIdsArray);
            $stmt->execute($idParams);
            $booksUpdatedById = $stmt->rowCount();
        } else {
            $booksUpdatedById = 0;
        }

        $totalBooksUpdated = $booksUpdatedByName + $booksUpdatedById;

        // Delete the other publisher records
        if (!empty($otherIdsArray)) {
            $stmt = $db->prepare("DELETE FROM authors WHERE id IN ($idPlaceholders)");
            $stmt->execute($otherIdsArray);
            $publishersRemoved = $stmt->rowCount();
        } else {
            $publishersRemoved = 0;
        }

        $db->commit();

        echo json_encode([
            'success' => true,
            'books_updated' => $totalBooksUpdated,
            'publishers_removed' => $publishersRemoved,
            'master_name' => $masterName
        ]);

    } catch (Exception $e) {
        $db->rollBack();
        error_log("Error merging group into master: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Handle consolidating duplicate authors
 */
function handleConsolidateAuthors() {
    global $db;

    $ids = $_POST['ids'] ?? '';
    $name = $_POST['name'] ?? '';

    if (empty($ids) || empty($name)) {
        echo json_encode(['success' => false, 'message' => 'IDs and name are required']);
        return;
    }

    try {
        $db->beginTransaction();

        $idsArray = explode(',', $ids);
        $idsArray = array_map('trim', $idsArray);

        // Keep the first ID as master
        $masterId = $idsArray[0];
        $otherIds = array_slice($idsArray, 1);

        if (!empty($otherIds)) {
            // Update books to use master ID
            $placeholders = str_repeat('?,', count($otherIds) - 1) . '?';
            $stmt = $db->prepare("UPDATE books SET publisher_id = ? WHERE publisher_id IN ($placeholders)");
            $params = array_merge([$masterId], $otherIds);
            $stmt->execute($params);

            // Delete duplicate records
            $stmt = $db->prepare("DELETE FROM authors WHERE id IN ($placeholders)");
            $stmt->execute($otherIds);
        }

        $db->commit();

        echo json_encode([
            'success' => true,
            'message' => "Consolidated duplicates for: $name",
            'master_id' => $masterId
        ]);

    } catch (Exception $e) {
        $db->rollBack();
        error_log("Error consolidating authors: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Handle merging into master (legacy function)
 */
function handleMergeIntoMaster() {
    global $db;

    $sourceId = $_POST['source_id'] ?? '';
    $masterId = $_POST['master_id'] ?? '';

    if (empty($sourceId) || empty($masterId)) {
        echo json_encode(['success' => false, 'message' => 'Source ID and master ID are required']);
        return;
    }

    try {
        $db->beginTransaction();

        // Get publisher names for both source and master
        $stmt = $db->prepare("SELECT id, name FROM authors WHERE id IN (?, ?)");
        $stmt->execute([$sourceId, $masterId]);
        $publishers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $sourcePublisher = null;
        $masterPublisher = null;
        foreach ($publishers as $pub) {
            if ($pub['id'] == $sourceId) {
                $sourcePublisher = $pub;
            } elseif ($pub['id'] == $masterId) {
                $masterPublisher = $pub;
            }
        }

        // Update books that match by publisher name (text field) to use master publisher_id AND master publisher name
        if ($sourcePublisher && $masterPublisher) {
            $stmt = $db->prepare("UPDATE books SET publisher_id = ?, publisher = ? WHERE publisher = ?");
            $stmt->execute([$masterId, $masterPublisher['name'], $sourcePublisher['name']]);
            $booksUpdatedByName = $stmt->rowCount();
        } else {
            $booksUpdatedByName = 0;
        }

        // Update books that already have publisher_id set to source ID
        if ($masterPublisher) {
            $stmt = $db->prepare("UPDATE books SET publisher_id = ?, publisher = ? WHERE publisher_id = ?");
            $stmt->execute([$masterId, $masterPublisher['name'], $sourceId]);
            $booksUpdatedById = $stmt->rowCount();
        } else {
            $booksUpdatedById = 0;
        }

        $totalBooksUpdated = $booksUpdatedByName + $booksUpdatedById;

        // Delete source publisher
        $stmt = $db->prepare("DELETE FROM authors WHERE id = ?");
        $stmt->execute([$sourceId]);

        $db->commit();

        echo json_encode([
            'success' => true,
            'books_updated' => $totalBooksUpdated
        ]);

    } catch (Exception $e) {
        $db->rollBack();
        error_log("Error merging into master: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Handle merging all in group (legacy function)
 */
function handleMergeAllInGroup() {
    // Redirect to the new function
    handleMergeGroupIntoMaster();
}

/**
 * Handle fixing all publisher relationships
 */
function handleFixAllPublisherRelationships() {
    global $db;

    try {
        $db->beginTransaction();

        // Get all books with missing publisher relationships
        $stmt = $db->query("
            SELECT directory_item_id, publisher
            FROM books
            WHERE publisher IS NOT NULL
            AND publisher != ''
            AND publisher_id IS NULL
        ");
        $books = $stmt->fetchAll();

        $fixed = 0;
        $created = 0;

        foreach ($books as $book) {
            $publisherName = trim($book['publisher']);

            // Try to find existing publisher
            $stmt = $db->prepare("SELECT id FROM authors WHERE name = ?");
            $stmt->execute([$publisherName]);
            $existingPublisher = $stmt->fetch();

            if ($existingPublisher) {
                // Update relationship
                $stmt = $db->prepare("UPDATE books SET publisher_id = ? WHERE directory_item_id = ?");
                $stmt->execute([$existingPublisher['id'], $book['directory_item_id']]);
                $fixed++;
            } else {
                // Create new publisher with slug
                $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $publisherName)));
                $stmt = $db->prepare("INSERT INTO authors (name, slug) VALUES (?, ?)");
                $stmt->execute([$publisherName, $slug]);
                $newPublisherId = $db->lastInsertId();

                // Update relationship
                $stmt = $db->prepare("UPDATE books SET publisher_id = ? WHERE directory_item_id = ?");
                $stmt->execute([$newPublisherId, $book['directory_item_id']]);
                $created++;
            }
        }

        $db->commit();

        echo json_encode([
            'success' => true,
            'message' => "Fixed $fixed relationships, created $created new publishers",
            'fixed' => $fixed,
            'created' => $created
        ]);

    } catch (Exception $e) {
        $db->rollBack();
        error_log("Error fixing all publisher relationships: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Handle creating standard reading levels
 */
function handleCreateStandardReadingLevels() {
    global $db;

    try {
        // Create reading_levels table if it doesn't exist
        $sql = "CREATE TABLE IF NOT EXISTS reading_levels (
            id INT AUTO_INCREMENT PRIMARY KEY,
            level_name VARCHAR(100) NOT NULL,
            age_range VARCHAR(50),
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $db->exec($sql);

        // Insert standard UK reading levels
        $standardLevels = [
            ['Pre-literacy Sensory (0-12mo)', '0-1', 'Sensory exploration, visual tracking'],
            ['Pre-literacy Naming (12-24mo)', '1-2', 'Object naming, simple words'],
            ['Pre-literacy Mimicry/BR (2-3yrs)', '2-3', 'Mimicking sounds, basic recognition'],
            ['Early Pre-reader/BR (3-4yrs)', '3-4', 'Letter recognition, phonics basics'],
            ['Beginning Reader (4-5yrs)', '4-5', 'Simple words, basic sentences'],
            ['Developing Reader (5-6yrs)', '5-6', 'Short books, building fluency'],
            ['Early Reader (6-7yrs)', '6-7', 'Chapter books, independent reading'],
            ['Transitional Reader (7-8yrs)', '7-8', 'Longer books, complex stories'],
            ['Fluent Reader (8-11yrs)', '8-11', 'Advanced vocabulary, series books'],
            ['Advanced Reader (11-14yrs)', '11-14', 'Complex themes, young adult content'],
            ['Proficient Reader (14-16yrs)', '14-16', 'Adult themes, advanced literature'],
            ['Expert Reader (16+yrs)', '16+', 'All content levels, academic texts']
        ];

        $stmt = $db->prepare("INSERT IGNORE INTO reading_levels (level_name, age_range, description) VALUES (?, ?, ?)");
        $inserted = 0;
        foreach ($standardLevels as $level) {
            $stmt->execute($level);
            if ($stmt->rowCount() > 0) $inserted++;
        }

        echo json_encode([
            'success' => true,
            'message' => "Created reading_levels table and inserted $inserted standard levels",
            'inserted' => $inserted
        ]);

    } catch (Exception $e) {
        error_log("Error creating standard reading levels: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Handle cleaning erroneous data
 */
function handleCleanErroneousData() {
    global $db;

    $field = $_POST['field'] ?? '';
    $value = $_POST['value'] ?? '';

    if (empty($field) || empty($value)) {
        echo json_encode(['success' => false, 'message' => 'Field and value are required']);
        return;
    }

    try {
        $db->beginTransaction();

        // Clean the erroneous data by setting it to NULL
        $stmt = $db->prepare("UPDATE books SET $field = NULL WHERE $field = ?");
        $stmt->execute([$value]);
        $booksUpdated = $stmt->rowCount();

        $db->commit();

        echo json_encode([
            'success' => true,
            'message' => "Cleaned erroneous data from $field field",
            'books_updated' => $booksUpdated
        ]);

    } catch (Exception $e) {
        $db->rollBack();
        error_log("Error cleaning erroneous data: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Handle editing erroneous data
 */
function handleEditErroneousData() {
    global $db;

    $field = $_POST['field'] ?? '';
    $oldValue = $_POST['old_value'] ?? '';
    $newValue = $_POST['new_value'] ?? '';

    if (empty($field) || empty($oldValue)) {
        echo json_encode(['success' => false, 'message' => 'Field and old value are required']);
        return;
    }

    try {
        $db->beginTransaction();

        // Update the erroneous data with the new value
        $stmt = $db->prepare("UPDATE books SET $field = ? WHERE $field = ?");
        $stmt->execute([$newValue, $oldValue]);
        $booksUpdated = $stmt->rowCount();

        $db->commit();

        echo json_encode([
            'success' => true,
            'message' => "Updated erroneous data in $field field",
            'books_updated' => $booksUpdated,
            'old_value' => $oldValue,
            'new_value' => $newValue
        ]);

    } catch (Exception $e) {
        $db->rollBack();
        error_log("Error editing erroneous data: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Handle cleaning all erroneous series data
 */
function handleCleanAllErroneousSeriesData() {
    global $db;

    try {
        $db->beginTransaction();

        // Find and clean erroneous series data
        $stmt = $db->query("
            SELECT DISTINCT series, COUNT(*) as count
            FROM books
            WHERE series IS NOT NULL
            AND series != ''
            AND (
                LENGTH(series) > 100
                OR LOWER(series) LIKE '%studied%'
                OR LOWER(series) LIKE '%oxford%'
                OR LOWER(series) LIKE '%author%'
                OR LOWER(series) LIKE '%writing%'
                OR LOWER(series) LIKE '%publisher%'
                OR LOWER(series) LIKE '%novel%'
            )
            GROUP BY series
        ");
        $erroneousSeries = $stmt->fetchAll();

        $booksUpdated = 0;
        $seriesCleaned = count($erroneousSeries);

        foreach ($erroneousSeries as $series) {
            $stmt = $db->prepare("UPDATE books SET series = NULL WHERE series = ?");
            $stmt->execute([$series['series']]);
            $booksUpdated += $stmt->rowCount();
        }

        $db->commit();

        echo json_encode([
            'success' => true,
            'message' => "Cleaned all erroneous series data",
            'books_updated' => $booksUpdated,
            'series_cleaned' => $seriesCleaned
        ]);

    } catch (Exception $e) {
        $db->rollBack();
        error_log("Error cleaning all erroneous series data: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Handle migrating all reading levels
 */
function handleMigrateAllReadingLevels() {
    global $db;

    try {
        $db->beginTransaction();

        // Define migration mapping
        $levelMapping = [
            'middle-grade' => 'Transitional Reader (7-8 years)',
            'Middle Grade' => 'Transitional Reader (7-8 years)',
            'chapter-book' => 'Fluent Reader (8-11 years)',
            'early reader' => 'Early Reader (6-7 years)',
            'picture book' => 'Beginning Reader (4-5 years)',
            'young adult' => 'Advanced Reader (14-16 years)',
            'adult' => 'Proficient Reader (18+ years)',
            'beginner' => 'Beginning Reader (4-5 years)',
            'intermediate' => 'Developing Reader (6-7 years)',
            'advanced' => 'Advanced Reader (11-14 years)'
        ];

        $booksUpdated = 0;
        foreach ($levelMapping as $oldLevel => $newLevel) {
            $stmt = $db->prepare("UPDATE books SET reading_level = ? WHERE reading_level = ?");
            $stmt->execute([$newLevel, $oldLevel]);
            $booksUpdated += $stmt->rowCount();
        }

        $db->commit();

        echo json_encode([
            'success' => true,
            'message' => "Migrated all reading levels to standard system",
            'books_updated' => $booksUpdated
        ]);

    } catch (Exception $e) {
        $db->rollBack();
        error_log("Error migrating reading levels: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Handle synchronizing age ranges with reading level age groups
 */
function handleSynchronizeAgeRanges() {
    global $db;

    try {
        $db->beginTransaction();

        // Check if standard_reading_levels table exists, create if not
        $stmt = $db->query("SHOW TABLES LIKE 'standard_reading_levels'");
        if ($stmt->rowCount() === 0) {
            // Create the standard_reading_levels table
            $createTableSQL = "CREATE TABLE `standard_reading_levels` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `age_group` varchar(20) NOT NULL,
                `school_year` varchar(20) DEFAULT NULL,
                `reading_stage` varchar(50) NOT NULL,
                `lexile_range` varchar(20) DEFAULT NULL,
                `typical_skills` text,
                `sort_order` int(11) NOT NULL,
                `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `age_group` (`age_group`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

            $db->exec($createTableSQL);

            // Insert standard data
            $insertSQL = "INSERT INTO `standard_reading_levels` (`age_group`, `school_year`, `reading_stage`, `lexile_range`, `typical_skills`, `sort_order`) VALUES
                ('0-12 months', NULL, 'Pre-literacy (Sensory)', 'N/A', 'Listening to voices, looking at pictures', 1),
                ('12-24 months', NULL, 'Pre-literacy (Naming)', 'N/A', 'Responding to stories, pointing at objects', 2),
                ('2-3 years', NULL, 'Pre-literacy (Mimicry)', 'BR', 'Repeating phrases, reading from memory', 3),
                ('3-4 years', NULL, 'Early Pre-reader', 'BR', 'Identifying letters, understanding sequences', 4),
                ('4-5 years', 'Reception', 'Beginning Reader', 'BR-120L', 'Introduction to phonics, basic sentences', 5),
                ('5-6 years', 'Year 1', 'Early Reader', '120L-220L', 'Simple books, building fluency', 6),
                ('6-7 years', 'Year 2', 'Developing Reader', '220L-420L', 'Chapter books, independent reading', 7),
                ('7-8 years', 'Year 3', 'Transitional Reader', '420L-620L', 'Longer books, complex stories', 8),
                ('8-9 years', 'Year 4', 'Fluent Reader', '620L-820L', 'Advanced vocabulary, series books', 9),
                ('9-10 years', 'Year 5', 'Fluent Reader', '820L-940L', 'Complex texts, critical thinking', 10),
                ('10-11 years', 'Year 6', 'Fluent Reader', '940L-1000L+', 'Advanced comprehension', 11),
                ('11-14 years', 'Years 7-9', 'Advanced Reader', '1000L-1100L+', 'Critical analysis, complex themes', 12),
                ('14-16 years', 'Years 10-11', 'Advanced Reader', '1100L-1200L+', 'GCSE level, young adult content', 13),
                ('16-18 years', 'Years 12-13', 'Advanced Reader', '1200L-1300L+', 'A-level, advanced literature', 14),
                ('18+ years', 'Adult', 'Proficient Reader', '1300L-1600L+', 'Professional reading, all content levels', 15)";

            $db->exec($insertSQL);
        }

        // Get the standard age groups from reading levels
        $stmt = $db->query("SELECT DISTINCT age_group FROM standard_reading_levels ORDER BY sort_order");
        $standardAgeGroups = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($standardAgeGroups)) {
            echo json_encode(['success' => false, 'message' => 'No standard age groups found in reading levels table.']);
            return;
        }

        // Create mapping from current age ranges to standard age groups
        $ageRangeMapping = [
            // Direct matches
            '0-12 months' => '0-12 months',
            '12-24 months' => '12-24 months',
            '2-3 years' => '2-3 years',
            '3-4 years' => '3-4 years',
            '4-5 years' => '4-5 years',
            '5-6 years' => '5-6 years',
            '6-7 years' => '6-7 years',
            '7-8 years' => '7-8 years',
            '8-9 years' => '8-9 years',
            '9-10 years' => '9-10 years',
            '10-11 years' => '10-11 years',
            '11-14 years' => '11-14 years',
            '14-16 years' => '14-16 years',
            '16-18 years' => '16-18 years',
            '18+ years' => '18+ years',

            // Map existing inconsistent ranges to standard groups
            '9-12' => '9-10 years',
            '8-12' => '8-9 years',
            '7-10' => '7-8 years',
            '10+' => '10-11 years',
            '12+' => '11-14 years',
            '12 and up' => '11-14 years',
            '9+' => '9-10 years',
            'Unknown' => null, // Keep as null for unknown

            // Additional mappings for common variations
            '0-3' => '2-3 years',
            '3-5' => '3-4 years',
            '4-6' => '4-5 years',
            '5-7' => '5-6 years',
            '6-8' => '6-7 years',
            '7-9' => '7-8 years',
            '8-10' => '8-9 years',
            '9-11' => '9-10 years',
            '10-12' => '10-11 years',
            '11-13' => '11-14 years',
            '12-14' => '11-14 years',
            '13-15' => '14-16 years',
            '14-17' => '14-16 years',
            '15-17' => '16-18 years',
            '16+' => '16-18 years',
            '18+' => '18+ years'
        ];

        $booksUpdated = 0;
        foreach ($ageRangeMapping as $oldRange => $newRange) {
            if ($newRange === null) {
                // Skip null mappings (like Unknown)
                continue;
            }

            $stmt = $db->prepare("UPDATE books SET age_range = ? WHERE age_range = ?");
            $stmt->execute([$newRange, $oldRange]);
            $updated = $stmt->rowCount();
            $booksUpdated += $updated;

            if ($updated > 0) {
                error_log("Synchronized age range: '$oldRange' → '$newRange' ($updated books)");
            }
        }

        $db->commit();

        echo json_encode([
            'success' => true,
            'message' => "Synchronized age ranges with reading level age groups",
            'books_updated' => $booksUpdated,
            'standard_groups' => $standardAgeGroups
        ]);

    } catch (Exception $e) {
        $db->rollBack();
        error_log("Error synchronizing age ranges: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Handle standardizing a specific reading level
 */
function handleStandardizeReadingLevel() {
    global $db;

    $currentLevel = $_POST['current_level'] ?? '';
    $standardLevel = $_POST['standard_level'] ?? '';

    if (empty($currentLevel) || empty($standardLevel)) {
        echo json_encode(['success' => false, 'message' => 'Current level and standard level are required']);
        return;
    }

    try {
        $db->beginTransaction();

        $stmt = $db->prepare("UPDATE books SET reading_level = ? WHERE reading_level = ?");
        $stmt->execute([$standardLevel, $currentLevel]);
        $booksUpdated = $stmt->rowCount();

        $db->commit();

        echo json_encode([
            'success' => true,
            'message' => "Standardized reading level: '$currentLevel' → '$standardLevel'",
            'books_updated' => $booksUpdated
        ]);

    } catch (Exception $e) {
        $db->rollBack();
        error_log("Error standardizing reading level: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Handle bulk fixing selected items
 */
function handleBulkFixSelected() {
    global $db;

    $bookIds = $_POST['book_ids'] ?? '';

    if (empty($bookIds)) {
        echo json_encode(['success' => false, 'message' => 'Book IDs are required']);
        return;
    }

    $bookIdsArray = explode(',', $bookIds);
    $bookIdsArray = array_map('trim', $bookIdsArray);
    $bookIdsArray = array_filter($bookIdsArray);

    if (empty($bookIdsArray)) {
        echo json_encode(['success' => false, 'message' => 'Valid book IDs are required']);
        return;
    }

    try {
        $db->beginTransaction();

        $fixed = 0;
        $created = 0;

        foreach ($bookIdsArray as $bookId) {
            // Get book publisher
            $stmt = $db->prepare("SELECT publisher FROM books WHERE directory_item_id = ?");
            $stmt->execute([$bookId]);
            $book = $stmt->fetch();

            if (!$book || empty($book['publisher'])) {
                continue;
            }

            $publisherName = trim($book['publisher']);

            // Try to find existing publisher
            $stmt = $db->prepare("SELECT id FROM authors WHERE name = ?");
            $stmt->execute([$publisherName]);
            $existingPublisher = $stmt->fetch();

            if ($existingPublisher) {
                // Update relationship
                $stmt = $db->prepare("UPDATE books SET publisher_id = ? WHERE directory_item_id = ?");
                $stmt->execute([$existingPublisher['id'], $bookId]);
                $fixed++;
            } else {
                // Create new publisher with slug
                $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $publisherName)));
                $stmt = $db->prepare("INSERT INTO authors (name, slug) VALUES (?, ?)");
                $stmt->execute([$publisherName, $slug]);
                $newPublisherId = $db->lastInsertId();

                // Update relationship
                $stmt = $db->prepare("UPDATE books SET publisher_id = ? WHERE directory_item_id = ?");
                $stmt->execute([$newPublisherId, $bookId]);
                $created++;
            }
        }

        $db->commit();

        echo json_encode([
            'success' => true,
            'message' => "Bulk fix completed: Fixed $fixed relationships, created $created new publishers",
            'fixed' => $fixed,
            'created' => $created
        ]);

    } catch (Exception $e) {
        $db->rollBack();
        error_log("Error in bulk fix: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
