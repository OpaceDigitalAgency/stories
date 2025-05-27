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

                // Ensure AMAZON_DEBUG is defined for this context
                if (!defined('AMAZON_DEBUG')) {
                    define('AMAZON_DEBUG', false);
                }

                // Fetch cached Amazon enrichment payload (includes all options, default format, and price)
                $amazonPayload = getAmazonEnrichmentData($isbn);

                // Log the result for debugging
                error_log("Amazon payload result: " . json_encode($amazonPayload));

                echo json_encode([
                    'success' => true,
                    'data' => $amazonPayload,
                    'debug' => [
                        'isbn_used' => $isbn,
                        'options_count' => count($amazonPayload['buying_options'] ?? []),
                        'selected_format' => $amazonPayload['selected_format'] ?? null
                    ]
                ]);
            }
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

    if (empty($bookId) || empty($fieldsJson)) {
        echo json_encode(['success' => false, 'message' => 'Book ID and fields are required']);
        return;
    }

    $fields = json_decode($fieldsJson, true);
    if (!$fields) {
        echo json_encode(['success' => false, 'message' => 'Invalid fields data']);
        return;
    }

    // Build update query
    $updateFields = [];
    $params = [];
    $tagsToProcess = [];
    $publisherToProcess = null;
    $coverUrlToProcess = null;

    foreach ($fields as $fieldName => $fieldData) {
        $value = $fieldData['value'];

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

            case 'cover_url':
                // Handle cover URL with download and optimization
                if (!empty($value)) {
                    // Store for later processing after main update
                    $coverUrlToProcess = $value;
                }
                break;

            default:
                // Standard string fields - only update if the field exists in the books table
                if (!empty($value) && columnExists('books', $fieldName)) {
                    $updateFields[] = "$fieldName = ?";
                    $params[] = $value;
                }
                break;
        }
    }

    if (empty($updateFields)) {
        echo json_encode(['success' => false, 'message' => 'No valid fields to update']);
        return;
    }

    // Add validation status update
    $updateFields[] = "validation_status = 'partial'";
    $updateFields[] = "last_validated = NOW()";

    // Add book ID parameter
    $params[] = $bookId;

    // Execute update
    $sql = "UPDATE books SET " . implode(', ', $updateFields) . " WHERE directory_item_id = ?";

    $stmt = $db->prepare($sql);
    if ($stmt->execute($params)) {
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
        echo json_encode(['success' => false, 'message' => 'Database update failed']);
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
        $stmt = $db->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
        $stmt->execute([$column]);
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
        // Check if publisher already exists in authors table
        $stmt = $db->prepare("SELECT id FROM authors WHERE name = ? AND type = 'publisher'");
        $stmt->execute([$publisherName]);
        $existingPublisher = $stmt->fetch();

        if ($existingPublisher) {
            $publisherId = $existingPublisher['id'];
        } else {
            // Create new publisher in authors table
            $stmt = $db->prepare("INSERT INTO authors (name, type, slug) VALUES (?, 'publisher', ?)");
            $slug = createSlug($publisherName);
            $stmt->execute([$publisherName, $slug]);
            $publisherId = $db->lastInsertId();
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
        foreach ($tagsToProcess as $fieldName => $value) {
            // Parse tags from value
            $tags = [];
            if (is_array($value)) {
                $tags = $value;
            } elseif (is_string($value)) {
                $tags = array_map('trim', explode(',', $value));
            }

            $addedTags = [];

            foreach ($tags as $tagName) {
                if (empty($tagName)) continue;

                // Check if tag exists
                $stmt = $db->prepare("SELECT id FROM tags WHERE name = ?");
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
 * Create URL-friendly slug from string
 */
function createSlug($string) {
    $slug = strtolower(trim($string));
    $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    $slug = trim($slug, '-');
    return $slug;
}
