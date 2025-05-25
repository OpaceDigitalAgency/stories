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
    $title = $_POST['title'] ?? '';
    $author = $_POST['author'] ?? '';
    $currentISBN = $_POST['current_isbn'] ?? '';

    error_log("Data enrichment request: title='$title', author='$author', isbn='$currentISBN'");

    if (empty($title)) {
        echo json_encode(['success' => false, 'message' => 'Title is required']);
        return;
    }

    try {
        // Get enriched data
        $enrichedData = getEnrichedBookData($title, $author, $currentISBN);
        error_log("Raw enriched data: " . json_encode($enrichedData));

        // Filter out fields that are empty or same as current
        $enrichedData['fields'] = filterRelevantFields($enrichedData['fields'], $currentISBN);
        error_log("Filtered enriched data: " . json_encode($enrichedData));

        echo json_encode([
            'success' => true,
            'data' => $enrichedData
        ]);
    } catch (Exception $e) {
        error_log("Enrichment error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Error getting enrichment data: ' . $e->getMessage(),
            'debug' => [
                'title' => $title,
                'author' => $author,
                'isbn' => $currentISBN
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
            case 'genres':
            case 'categories':
            case 'subjects':
                // Handle tags/genres using proper directory_item_tags junction table
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
                // Map maturity rating to age_range using actual age_ranges table
                if (!empty($value)) {
                    $ageRange = mapMaturityToAgeRangeFromTable($value);
                    if ($ageRange && columnExists('books', 'age_range')) {
                        $updateFields[] = "age_range = ?";
                        $params[] = $ageRange;
                    }

                    // Also store the raw maturity rating if field exists
                    if (columnExists('books', 'maturity_rating')) {
                        $updateFields[] = "maturity_rating = ?";
                        $params[] = $value;
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
 * Process enrichment fields for display - show ALL fields including unknown ones
 */
function filterRelevantFields($fields, $currentISBN) {
    // Don't filter anything - show all fields including unknown ones
    // This gives users complete visibility into what data is available

    foreach ($fields as $fieldName => $fieldData) {
        // Ensure all fields have required properties for both single and multi-source fields
        if (!isset($fieldData['label'])) {
            $fields[$fieldName]['label'] = ucfirst(str_replace('_', ' ', $fieldName));
        }

        // Handle multi-source fields (with options array)
        if (isset($fieldData['options']) && is_array($fieldData['options'])) {
            // Multi-source field - ensure each option has proper structure
            foreach ($fieldData['options'] as $index => $option) {
                if (!isset($option['label'])) {
                    $fields[$fieldName]['options'][$index]['label'] = $fieldData['label'];
                }
            }
        } else {
            // Single source field - handle normally

            // Mark fields as unknown if they have no value and no status
            if ((empty($fieldData['value']) || $fieldData['value'] === null) && !isset($fieldData['status'])) {
                $fields[$fieldName]['status'] = 'unknown';
                $fields[$fieldName]['value'] = 'Unknown';
                $fields[$fieldName]['source'] = 'unknown';
            }
        }

        // Show ISBN fields when they could be useful
        if (($fieldName === 'isbn' || $fieldName === 'isbn13') && !empty($currentISBN)) {
            // Only show ISBN fields if we're missing the complementary one
            $currentLength = strlen(preg_replace('/[^0-9X]/i', '', $currentISBN));
            if ($fieldName === 'isbn' && $currentLength === 13) {
                // Show ISBN-10 option when we have ISBN-13
                $fields[$fieldName]['helpful'] = true;
            } elseif ($fieldName === 'isbn13' && $currentLength === 10) {
                // Show ISBN-13 option when we have ISBN-10
                $fields[$fieldName]['helpful'] = true;
            }
        }
    }

    return $fields;
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
