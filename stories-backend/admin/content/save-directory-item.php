<?php
// Include auth check
require_once '../includes/auth-check.php';

// This is a processing script, no UI needed

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: directory-items.php");
    exit;
}

try {
    // Connect to database
    $db = new PDO(
        "mysql:host={$config['host']};dbname={$config['name']};charset={$config['charset']}",
        $config['user'],
        $config['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );

    // Start transaction
    $db->beginTransaction();

    // Log the entire POST data for debugging
    error_log("Save directory item POST data: " . print_r($_POST, true));

    // Get form data
    $id = $_POST['id'] ?? null;
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category_id = !empty($_POST['category_id']) ? $_POST['category_id'] : null;
    $website_url = trim($_POST['website_url'] ?? '');
    $contact_email = trim($_POST['contact_email'] ?? '');
    $contact_phone = trim($_POST['contact_phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $featured = isset($_POST['featured']) ? 1 : 0;
    $is_published = isset($_POST['is_published']) ? 1 : 0;
    $slug = trim($_POST['slug'] ?? '');
    $published_at = $_POST['published_at'] ?? null;
    $cover_url = trim($_POST['cover_url'] ?? '');
    $type = trim($_POST['type'] ?? 'general');

    // Log the cover URL for debugging
    error_log("Cover URL from form: " . $cover_url);

    // If cover_url is empty, set it to NULL
    if (empty($cover_url)) {
        $cover_url = null;
        error_log("Setting cover_url to NULL");
    } else {
        error_log("Setting cover_url to: " . $cover_url);
    }

    // Get book-specific data if applicable
    $bookData = [];
    if ($type === 'book') {
        // Handle custom author field
        $author = trim($_POST['book_author'] ?? '');
        if ($author === 'custom' && !empty($_POST['custom_author'])) {
            $author = trim($_POST['custom_author']);
        }

        // Handle custom publisher field
        $publisher = trim($_POST['book_publisher'] ?? '');
        $publisherId = null;

        // Check if publisher is a numeric ID (from publishers table)
        if (is_numeric($publisher)) {
            $publisherId = (int)$publisher;

            // Get the publisher name from the ID
            $publisherStmt = $db->prepare("SELECT name FROM publishers WHERE id = ?");
            $publisherStmt->execute([$publisherId]);
            $publisherData = $publisherStmt->fetch();

            if ($publisherData) {
                $publisher = $publisherData['name'];
            }
        } elseif ($publisher === 'custom' && !empty($_POST['custom_publisher'])) {
            $publisher = trim($_POST['custom_publisher']);

            // Check if this publisher exists in the publishers table
            $publisherStmt = $db->prepare("SELECT id FROM publishers WHERE name = ?");
            $publisherStmt->execute([$publisher]);
            $publisherData = $publisherStmt->fetch();

            if ($publisherData) {
                $publisherId = $publisherData['id'];
            } else {
                // Create a new publisher
                $slug = strtolower(preg_replace('/[^\w\s-]+/', '', $publisher));
                $slug = preg_replace('/[\s-]+/', '-', $slug);
                $slug = trim($slug, '-');

                $publisherStmt = $db->prepare("INSERT INTO publishers (name, slug) VALUES (?, ?)");
                $publisherStmt->execute([$publisher, $slug]);
                $publisherId = $db->lastInsertId();
            }
        }

        // Handle custom series field
        $series = trim($_POST['book_series'] ?? '');
        if ($series === 'custom' && !empty($_POST['custom_series'])) {
            $series = trim($_POST['custom_series']);
        }

        $bookData = [
            'author' => $author,
            'publisher' => $publisher,
            'publisher_id' => $publisherId,
            'isbn' => trim($_POST['book_isbn'] ?? ''),
            'isbn13' => trim($_POST['book_isbn13'] ?? ''),
            'publication_date' => trim($_POST['book_publication_date'] ?? ''),
            'page_count' => !empty($_POST['book_page_count']) ? intval($_POST['book_page_count']) : null,
            'genre' => trim($_POST['book_genre'] ?? ''),
            'series' => $series,
            'age_range' => trim($_POST['book_age_range'] ?? ''),
            'reading_level' => trim($_POST['book_reading_level'] ?? ''),
            'purchase_links' => trim($_POST['book_purchase_links'] ?? '')
        ];
    }

    // Validate required fields
    if (empty($title)) {
        throw new Exception("Title is required");
    }

    // Generate slug if not provided
    if (empty($slug)) {
        $slug = strtolower(preg_replace('/[^\w\s-]+/', '', $title));
        $slug = preg_replace('/[\s-]+/', '-', $slug);
        $slug = trim($slug, '-');
    }

    // Format published_at
    if (!empty($published_at)) {
        $date = new DateTime($published_at);
        $published_at = $date->format('Y-m-d H:i:s');
    } else {
        $published_at = null;
    }

    if ($id) {
        // Update existing directory item
        $setClause = [
            "title = ?",
            "description = ?",
            "category_id = ?",
            "website_url = ?",
            "contact_email = ?",
            "contact_phone = ?",
            "address = ?",
            "featured = ?",
            "is_published = ?",
            "slug = ?",
            "published_at = ?"
        ];

        $params = [
            $title,
            $description,
            $category_id,
            $website_url,
            $contact_email,
            $contact_phone,
            $address,
            $featured,
            $is_published,
            $slug,
            $published_at
        ];

        // Handle NULL values properly in SQL for cover_url
        if ($cover_url === null) {
            $setClause[] = "cover_url = NULL";
        } else {
            $setClause[] = "cover_url = ?";
            $params[] = $cover_url;
        }

        $setClause[] = "updated_at = NOW()";
        $params[] = $id; // Add ID for WHERE clause

        $sql = "UPDATE directory_items SET " . implode(', ', $setClause) . " WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $success = "Directory item updated successfully";
    } else {
        // Create new directory item
        $columns = [
            "title",
            "description",
            "category_id",
            "website_url",
            "contact_email",
            "contact_phone",
            "address",
            "featured",
            "is_published",
            "slug",
            "published_at"
        ];

        $placeholders = ["?", "?", "?", "?", "?", "?", "?", "?", "?", "?", "?"];

        $params = [
            $title,
            $description,
            $category_id,
            $website_url,
            $contact_email,
            $contact_phone,
            $address,
            $featured,
            $is_published,
            $slug,
            $published_at
        ];

        // Handle NULL values properly in SQL for cover_url
        $columns[] = "cover_url";
        if ($cover_url === null) {
            $placeholders[] = "NULL";
        } else {
            $placeholders[] = "?";
            $params[] = $cover_url;
        }

        // Add created_at and updated_at
        $columns[] = "created_at";
        $columns[] = "updated_at";
        $placeholders[] = "NOW()";
        $placeholders[] = "NOW()";

        $sql = "INSERT INTO directory_items (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $success = "Directory item created successfully";
    }

    // Process book data if item type is book
    if ($type === 'book') {
        // Check if book record already exists
        if ($id) {
            $bookStmt = $db->prepare("SELECT directory_item_id FROM books WHERE directory_item_id = ?");
            $bookStmt->execute([$id]);
            $existingBook = $bookStmt->fetch();
        } else {
            $existingBook = false;
            $id = $db->lastInsertId(); // Get ID of newly created directory item
        }

        // Format purchase links as JSON if provided
        $purchaseLinks = !empty($bookData['purchase_links']) ? $bookData['purchase_links'] : '{}';
        try {
            // Validate JSON
            json_decode($purchaseLinks);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $purchaseLinks = '{}'; // Reset to empty object if invalid
            }
        } catch (Exception $e) {
            $purchaseLinks = '{}';
        }

        // Convert publication date to MySQL format if needed
        if (!empty($bookData['publication_date'])) {
            // Use direct format if it's already YYYY-MM-DD
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $bookData['publication_date'])) {
                // Try to convert using the format conversion function if available
                if (function_exists('convertToMySQLDate')) {
                    $bookData['publication_date'] = convertToMySQLDate($bookData['publication_date']);
                }
            }
        }

        if ($existingBook) {
            // Update existing book record
            $stmt = $db->prepare("UPDATE books SET
                title = ?,
                isbn = ?,
                isbn13 = ?,
                author = ?,
                publisher = ?,
                publisher_id = ?,
                publication_date = ?,
                page_count = ?,
                age_range = ?,
                reading_level = ?,
                cover_url = ?,  /* Using cover_url as that's now the column name in the books table */
                purchase_links = ?,
                genre = ?,
                series = ?
                WHERE directory_item_id = ?");
            $stmt->execute([
                $title, // Use the directory item title
                $bookData['isbn'],
                $bookData['isbn13'],
                $bookData['author'],
                $bookData['publisher'],
                $bookData['publisher_id'],
                $bookData['publication_date'],
                $bookData['page_count'],
                $bookData['age_range'],
                $bookData['reading_level'],
                $cover_url, // Use the directory item's cover_url for the book's cover_image_url
                $purchaseLinks,
                $bookData['genre'],
                $bookData['series'],
                $id
            ]);
        } else {
            // Insert new book record
            $stmt = $db->prepare("INSERT INTO books (
                directory_item_id,
                title,
                isbn,
                isbn13,
                author,
                publisher,
                publisher_id,
                publication_date,
                page_count,
                age_range,
                reading_level,
                cover_url,  /* Using cover_url as that's now the column name in the books table */
                purchase_links,
                genre,
                series
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $id,
                $title, // Use the directory item title
                $bookData['isbn'],
                $bookData['isbn13'],
                $bookData['author'],
                $bookData['publisher'],
                $bookData['publisher_id'],
                $bookData['publication_date'],
                $bookData['page_count'],
                $bookData['age_range'],
                $bookData['reading_level'],
                $cover_url, // Use the directory item's cover_url for the book's cover_image_url
                $purchaseLinks,
                $bookData['genre'],
                $bookData['series']
            ]);
        }

        // Process book author relationships
        // First, check if we have author information
        $authorId = null;
        if (!empty($bookData['author'])) {
            // Handle custom author field
            $author = trim($bookData['author']);
            if ($author === 'custom' && !empty($_POST['custom_author'])) {
                $author = trim($_POST['custom_author']);
            }

            // Check if author exists by name
            $stmt = $db->prepare("SELECT id FROM authors WHERE LOWER(name) = LOWER(?)");
            $stmt->execute([$author]);
            $authorResult = $stmt->fetch();

            if ($authorResult) {
                $authorId = $authorResult['id'];
            } else {
                // Add new author
                $stmt = $db->prepare("
                    INSERT INTO authors (name, author_type, slug, created_at, updated_at)
                    VALUES (?, ?, ?, NOW(), NOW())
                ");

                // Generate slug
                $authorSlug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $author));
                $authorSlug = trim($authorSlug, '-');

                $stmt->execute([$author, 'retail', $authorSlug]);
                $authorId = $db->lastInsertId();
            }
        }

        // Process publisher relationship
        $publisherId = null;
        if (!empty($bookData['publisher'])) {
            // Handle custom publisher field
            $publisher = trim($bookData['publisher']);
            if ($publisher === 'custom' && !empty($_POST['custom_publisher'])) {
                $publisher = trim($_POST['custom_publisher']);
            }

            // Check if publisher exists by name
            $stmt = $db->prepare("SELECT id FROM authors WHERE LOWER(name) = LOWER(?)");
            $stmt->execute([$publisher]);
            $publisherResult = $stmt->fetch();

            if ($publisherResult) {
                $publisherId = $publisherResult['id'];
            } else {
                // Add new publisher
                $stmt = $db->prepare("
                    INSERT INTO authors (name, author_type, slug, created_at, updated_at)
                    VALUES (?, ?, ?, NOW(), NOW())
                ");

                // Generate slug
                $publisherSlug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $publisher));
                $publisherSlug = trim($publisherSlug, '-');

                $stmt->execute([$publisher, 'retail', $publisherSlug]);
                $publisherId = $db->lastInsertId();
            }
        }

        // Create book-author relationships
        // First, check if book_authors table exists, create if not
        $stmt = $db->query("SHOW TABLES LIKE 'book_authors'");
        if ($stmt->rowCount() === 0) {
            $db->exec("CREATE TABLE IF NOT EXISTS book_authors (
                directory_item_id INT NOT NULL,
                author_id INT NOT NULL,
                role ENUM('author', 'publisher') NOT NULL DEFAULT 'author',
                PRIMARY KEY (directory_item_id, author_id, role),
                FOREIGN KEY (directory_item_id) REFERENCES directory_items(id) ON DELETE CASCADE,
                FOREIGN KEY (author_id) REFERENCES authors(id) ON DELETE CASCADE
            )");
            error_log("Created book_authors table");
        }

        // Delete any existing relationships
        $stmt = $db->prepare("DELETE FROM book_authors WHERE directory_item_id = ?");
        $stmt->execute([$id]);

        // Add book author relationship
        if ($authorId) {
            $stmt = $db->prepare("
                INSERT INTO book_authors (directory_item_id, author_id, role)
                VALUES (?, ?, 'author')
            ");
            $stmt->execute([$id, $authorId]);
            error_log("Created book-author relationship for book ID $id and author ID $authorId");
        } else {
            error_log("No author ID found for book ID $id - author name: " . ($bookData['author'] ?? 'Not set'));
        }

        // Add book publisher relationship
        if ($publisherId) {
            $stmt = $db->prepare("
                INSERT INTO book_authors (directory_item_id, author_id, role)
                VALUES (?, ?, 'publisher')
            ");
            $stmt->execute([$id, $publisherId]);
            error_log("Created book-publisher relationship for book ID $id and publisher ID $publisherId");
        } else {
            error_log("No publisher ID found for book ID $id - publisher name: " . ($bookData['publisher'] ?? 'Not set'));
        }
    }

    // Process tags if provided
    if (isset($_POST['tags']) && is_array($_POST['tags'])) {
        // Check if item_tags table exists, create if not
        $stmt = $db->query("SHOW TABLES LIKE 'item_tags'");
        if ($stmt->rowCount() === 0) {
            $db->exec("CREATE TABLE IF NOT EXISTS item_tags (
                item_id INT NOT NULL,
                tag_id INT NOT NULL,
                item_type VARCHAR(50) NOT NULL,
                PRIMARY KEY (item_id, tag_id, item_type)
            )");
        }

        // Remove existing tags for this item
        $stmt = $db->prepare("DELETE FROM item_tags WHERE item_id = ? AND item_type = 'directory_item'");
        $stmt->execute([$id]);

        // Add new tags
        $stmt = $db->prepare("INSERT INTO item_tags (item_id, tag_id, item_type) VALUES (?, ?, 'directory_item')");
        foreach ($_POST['tags'] as $tagId) {
            $stmt->execute([$id, $tagId]);
        }
    }

    // Commit transaction
    $db->commit();

    // Store success message and redirect
    $_SESSION['success'] = $success;

    header("Location: directory-items.php");
    exit;

} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($db)) {
        $db->rollBack();
    }

    error_log("Save directory item error: " . $e->getMessage());
    error_log("Error trace: " . $e->getTraceAsString());

    // Add more detailed error information for debugging
    $errorDetails = "Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine();
    error_log($errorDetails);

    // Store error in session and redirect back to form
    $_SESSION['error'] = $e->getMessage();

    $redirect = $id ? "directory-item-form.php?id=$id" : "directory-item-form.php";
    header("Location: $redirect");
    exit;
}

// No footer needed for processing script
