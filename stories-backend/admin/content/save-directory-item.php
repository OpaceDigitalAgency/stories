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
    
    // Get book-specific data if applicable
    $bookData = [];
    if ($type === 'book') {
        $bookData = [
            'author' => trim($_POST['book_author'] ?? ''),
            'publisher' => trim($_POST['book_publisher'] ?? ''),
            'isbn' => trim($_POST['book_isbn'] ?? ''),
            'isbn13' => trim($_POST['book_isbn13'] ?? ''),
            'publication_date' => trim($_POST['book_publication_date'] ?? ''),
            'page_count' => !empty($_POST['book_page_count']) ? intval($_POST['book_page_count']) : null,
            'genre' => trim($_POST['book_genre'] ?? ''),
            'series' => trim($_POST['book_series'] ?? ''),
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
        $stmt = $db->prepare("UPDATE directory_items SET
            title = ?,
            description = ?,
            category_id = ?,
            website_url = ?,
            contact_email = ?,
            contact_phone = ?,
            address = ?,
            featured = ?,
            is_published = ?,
            slug = ?,
            published_at = ?,
            cover_url = ?,
            updated_at = NOW()
            WHERE id = ?");
        $stmt->execute([
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
            $published_at,
            $cover_url,
            $id
        ]);
        $success = "Directory item updated successfully";
    } else {
        // Create new directory item
        $stmt = $db->prepare("INSERT INTO directory_items (
            title,
            description,
            category_id,
            website_url,
            contact_email,
            contact_phone,
            address,
            featured,
            is_published,
            slug,
            published_at,
            cover_url,
            created_at,
            updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
        $stmt->execute([
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
            $published_at,
            $cover_url
        ]);
        $success = "Directory item created successfully";
    }

    // Process book data if item type is book
    if ($type === 'book') {
        // Check if book record already exists
        if ($id) {
            $bookStmt = $db->prepare("SELECT id FROM books WHERE directory_item_id = ?");
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
                isbn = ?,
                isbn13 = ?,
                author = ?,
                publisher = ?,
                publication_date = ?,
                page_count = ?,
                age_range = ?,
                reading_level = ?,
                cover_image_url = ?,
                purchase_links = ?,
                genre = ?,
                series = ?
                WHERE directory_item_id = ?");
            $stmt->execute([
                $bookData['isbn'],
                $bookData['isbn13'],
                $bookData['author'],
                $bookData['publisher'],
                $bookData['publication_date'],
                $bookData['page_count'],
                $bookData['age_range'],
                $bookData['reading_level'],
                $cover_url, // Use the same cover URL as directory item
                $purchaseLinks,
                $bookData['genre'],
                $bookData['series'],
                $id
            ]);
        } else {
            // Insert new book record
            $stmt = $db->prepare("INSERT INTO books (
                directory_item_id,
                isbn,
                isbn13,
                author,
                publisher,
                publication_date,
                page_count,
                age_range,
                reading_level,
                cover_image_url,
                purchase_links,
                genre,
                series
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $id,
                $bookData['isbn'],
                $bookData['isbn13'],
                $bookData['author'],
                $bookData['publisher'],
                $bookData['publication_date'],
                $bookData['page_count'],
                $bookData['age_range'],
                $bookData['reading_level'],
                $cover_url, // Use the same cover URL as directory item
                $purchaseLinks,
                $bookData['genre'],
                $bookData['series']
            ]);
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

    // Store error in session and redirect back to form
    $_SESSION['error'] = $e->getMessage();

    $redirect = $id ? "directory-item-form.php?id=$id" : "directory-item-form.php";
    header("Location: $redirect");
    exit;
}

// No footer needed for processing script
