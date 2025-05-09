<?php
/**
 * Enhanced Direct Import Tool
 * 
 * A comprehensive tool to import content with proper handling of
 * media files, authors, and tags.
 */

// Include auth check
require_once '../admin/includes/auth-check.php';

// Include database connection
require_once '../admin/includes/db-connect.php';

// Basic error handling and setup
ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit(0);
ini_set('output_buffering', 'off');
ini_set('implicit_flush', true);
ob_implicit_flush(true);

// Function to flush output buffer to ensure real-time progress display
function flushOutput() {
    if (ob_get_level() > 0) {
        ob_flush();
        flush();
    }
}

// Function to process a book
function processBook($db, $bookDir) {
    $mdFile = "$bookDir/index.md";
    
    if (!file_exists($mdFile)) {
        echo "<p class='error'>Markdown file not found: $mdFile</p>";
        flushOutput();
        return false;
    }
    
    // Begin transaction for this book
    $db->beginTransaction();
    
    try {
        // Read markdown file
        $content = file_get_contents($mdFile);
        
        // Extract front matter
        $pattern = '/^---\s*\n(.*?)\n---\s*\n(.*)/s';
        if (!preg_match($pattern, $content, $matches)) {
            echo "<p class='error'>Invalid markdown format in: $mdFile</p>";
            flushOutput();
            $db->rollBack();
            return false;
        }
        
        $frontMatter = $matches[1];
        $markdownContent = $matches[2];
        
        // Parse front matter
        $data = [];
        $lines = explode("\n", $frontMatter);
        foreach ($lines as $line) {
            if (preg_match('/^(\w+):\s*(.*)$/', $line, $parts)) {
                $key = $parts[1];
                $value = trim($parts[2], '"\'');
                $data[$key] = $value;
            }
        }
        
        $title = isset($data['title']) ? $data['title'] : basename($bookDir);
        echo "<h3>Processing Book: $title</h3>";
        flushOutput();
        
        // Extract book metadata from front matter
        $author = isset($data['author']) ? $data['author'] : '';
        $publisher = isset($data['publisher']) ? $data['publisher'] : '';
        $isbn = isset($data['isbn']) ? $data['isbn'] : '';
        $isbn13 = isset($data['isbn13']) ? $data['isbn13'] : '';
        
        // Get publication date string from available sources
        $pubDateStr = null;
        
        echo "<p class='info'>Checking front matter for publication_date: " . (isset($data['publication_date']) ? "'{$data['publication_date']}'" : "Not set") . "</p>";
        flushOutput();
        
        if (isset($data['publication_date'])) {
            $pubDateStr = $data['publication_date'];
        }
        
        // Extract page count
        $pageCount = isset($data['page_count']) ? intval($data['page_count']) : null;
        
        // Extract age range
        $ageRange = isset($data['age_range']) ? $data['age_range'] : '';
        
        // Extract reading level
        $readingLevel = isset($data['reading_level']) ? $data['reading_level'] : '';
        
        // Extract plot or summary info
        $plotInfo = '';
        if (isset($data['plot'])) {
            $plotInfo = $data['plot'];
        } elseif (isset($data['summary'])) {
            $plotInfo = $data['summary'];
        }
        
        // Extract genre and series data
        $genre = isset($data['genre']) ? $data['genre'] : '';
        $series = isset($data['series']) ? $data['series'] : '';
        
        // Extract enhanced data for JSON storage
        $enhancedData = [];
        foreach ($data as $key => $value) {
            // Skip keys we're already handling explicitly
            if (!in_array($key, ['title', 'author', 'publisher', 'isbn', 'isbn13', 'publication_date', 'page_count', 'age_range', 'reading_level', 'plot', 'summary', 'genre', 'series'])) {
                $enhancedData[$key] = $value;
            }
        }
        
        // Process publisher as an author with role 'publisher'
        $publisherId = null;
        if (!empty($publisher)) {
            $publisherInfo = [
                'name' => $publisher,
                'type' => 'publisher'
            ];
            
            // Check if publisher exists by name
            $stmt = $db->prepare("SELECT id FROM authors WHERE LOWER(name) = LOWER(?)");
            $stmt->execute([$publisher]);
            $publisherResult = $stmt->fetch();
            
            if ($publisherResult) {
                $publisherId = $publisherResult['id'];
                echo "<p class='info'>Publisher '" . htmlspecialchars($publisher) . "' already exists in authors table (ID: $publisherId)</p>";
                flushOutput();
            } else {
                // Add new publisher
                $stmt = $db->prepare("
                    INSERT INTO authors (name, type, slug, created_at, updated_at)
                    VALUES (?, 'publisher', ?, NOW(), NOW())
                ");
                
                // Generate slug
                $publisherSlug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $publisher));
                $publisherSlug = trim($publisherSlug, '-');
                
                $stmt->execute([$publisher, $publisherSlug]);
                $publisherId = $db->lastInsertId();
                echo "<p class='success'>Added publisher '" . htmlspecialchars($publisher) . "' to authors table (ID: $publisherId)</p>";
                flushOutput();
            }
        }
        
        // Process book author
        $authorId = null;
        $authorName = !empty($author) ? $author : 'Unknown Author';
        
        // Prefix with ** to identify as book author in the system
        if (strpos($authorName, '**') !== 0) {
            $authorName = "** $authorName";
        }
        
        $authorInfo = [
            'name' => $authorName,
            'type' => 'book_author'
        ];
        
        // Check if author exists by name
        $stmt = $db->prepare("SELECT id FROM authors WHERE LOWER(name) = LOWER(?)");
        $stmt->execute([$authorName]);
        $authorResult = $stmt->fetch();
        
        if ($authorResult) {
            $authorId = $authorResult['id'];
            echo "<p class='info'>Book author '" . htmlspecialchars($authorName) . "' already exists in authors table (ID: $authorId)</p>";
            flushOutput();
        } else {
            // Add new author
            $stmt = $db->prepare("
                INSERT INTO authors (name, type, slug, created_at, updated_at)
                VALUES (?, 'book_author', ?, NOW(), NOW())
            ");
            
            // Generate slug
            $authorSlug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $authorName));
            $authorSlug = trim($authorSlug, '-');
            
            $stmt->execute([$authorName, $authorSlug]);
            $authorId = $db->lastInsertId();
            echo "<p class='success'>Added book author '" . htmlspecialchars($authorName) . "' to authors table (ID: $authorId)</p>";
            flushOutput();
        }
        
        // Process cover image and add to media table
        $coverImageUrl = '';
        $mediaId = null;
        
        // Look for images in multiple potential locations
        $imagePaths = [];
        
        // Check in the main directory
        $imagePatterns = [
            "$bookDir/*.{jpg,jpeg,png,gif}",                // Root directory
            "$bookDir/images/*.{jpg,jpeg,png,gif}",         // images subdirectory
            "$bookDir/image/*.{jpg,jpeg,png,gif}",          // image subdirectory
            "$bookDir/cover/*.{jpg,jpeg,png,gif}",          // cover subdirectory
            "$bookDir/covers/*.{jpg,jpeg,png,gif}",         // covers subdirectory
            "$bookDir/media/*.{jpg,jpeg,png,gif}"           // media subdirectory
        ];
        
        foreach ($imagePatterns as $pattern) {
            $matches = glob($pattern, GLOB_BRACE);
            if (!empty($matches)) {
                $imagePaths = array_merge($imagePaths, $matches);
            }
        }
        
        echo "<p class='info'>Found " . count($imagePaths) . " potential images for book: $title</p>";
        flushOutput();
        
        if (!empty($imagePaths)) {
            // Sort by filename to prioritize cover images
            usort($imagePaths, function($a, $b) {
                $aName = strtolower(basename($a));
                $bName = strtolower(basename($b));
                
                // Prioritize files with "cover" in the name
                $aHasCover = strpos($aName, 'cover') !== false;
                $bHasCover = strpos($bName, 'cover') !== false;
                
                if ($aHasCover && !$bHasCover) return -1;
                if (!$aHasCover && $bHasCover) return 1;
                
                return strcmp($aName, $bName);
            });
            
            $imageFile = $imagePaths[0];
            $imageName = basename($imageFile);
            
            echo "<p class='info'>Selected image: $imageName from path: $imageFile</p>";
            flushOutput();
            
            // Copy image to uploads directory
            $uploadsDir = __DIR__ . '/../uploads/books';
            if (!is_dir($uploadsDir)) {
                mkdir($uploadsDir, 0755, true);
            }
            
            $destinationPath = $uploadsDir . '/' . $imageName;
            copy($imageFile, $destinationPath);
            
            // Add image to media table
            $fileSize = filesize($imageFile);
            $fileType = mime_content_type($imageFile);
            $relativePath = '/uploads/books/' . $imageName;
            
            $stmt = $db->prepare("
                INSERT INTO media (
                    filename, file_path, file_size, file_type,
                    created_at, updated_at
                ) VALUES (?, ?, ?, ?, NOW(), NOW())
            ");
            
            // Fix: Only execute once to avoid duplicate inserts
            $stmt->execute([
                $imageName,
                $relativePath,
                $fileSize,
                $fileType
            ]);
            
            $mediaId = $db->lastInsertId();
            $coverImageUrl = $relativePath;
            
            echo "<p class='success'>Added image: $imageName to media table (ID: $mediaId)</p>";
            flushOutput();
        }
        
        // Extract description from content
        $description = '';
        if (!empty($plotInfo)) {
            // Use plot information as description
            $description = $plotInfo;
        } elseif (preg_match('/Summary\s*\n(.*?)(?:\n\n|\n#|\n\*\*|$)/s', $markdownContent, $summaryMatch)) {
            $description = trim($summaryMatch[1]);
        } else {
            // Use first paragraph as description
            $paragraphs = preg_split('/\n\s*\n/', $markdownContent);
            $description = trim($paragraphs[0]);
        }
        
        // Generate purchase links
        $purchaseLinks = [];
        
        // Add to amazon if ISBN is available
        if (!empty($isbn)) {
            $purchaseLinks['amazon'] = "https://www.amazon.com/dp/$isbn/";
        } elseif (!empty($isbn13)) {
            $purchaseLinks['amazon'] = "https://www.amazon.com/dp/$isbn13/";
        }
        
        // Add to goodreads if ISBN is available
        if (!empty($isbn)) {
            $purchaseLinks['goodreads'] = "https://www.goodreads.com/book/isbn/$isbn";
        } elseif (!empty($isbn13)) {
            $purchaseLinks['goodreads'] = "https://www.goodreads.com/book/isbn/$isbn13";
        }
        
        // Generate slug from title
        $slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $title));
        $slug = trim($slug, '-');
        
        // Check if directory item already exists
        $existingDirectoryItem = null;
        $stmt = $db->prepare("SELECT * FROM directory_items WHERE slug = ? OR title = ?");
        $stmt->execute([$slug, $title]);
        $existingDirectoryItem = $stmt->fetch();
        
        $directoryItemId = null;
        $action = 'created';
        
        if ($existingDirectoryItem) {
            $directoryItemId = $existingDirectoryItem['id'];
            $action = 'updated';
            
            // Update existing directory item
            $stmt = $db->prepare("
                UPDATE directory_items SET
                    title = ?,
                    description = ?,
                    website_url = ?,
                    category_id = ?,
                    slug = ?,
                    cover_url = ?,
                    is_published = 1,
                    updated_at = NOW()
                WHERE id = ?
            ");
            
            $stmt->execute([
                $title,
                $description,
                '',  // website_url
                1,   // category_id (default to books category)
                $slug,
                $coverImageUrl,
                $directoryItemId
            ]);
            
            echo "<p class='success'>Updated existing directory item: $title (ID: $directoryItemId)</p>";
            flushOutput();
        } else {
            // Create new directory item
            $stmt = $db->prepare("
                INSERT INTO directory_items (
                    title, description, website_url, category_id, type, slug, cover_url, is_published, published_at, created_at, updated_at
                ) VALUES (?, ?, ?, ?, 'book', ?, ?, 1, NOW(), ?, ?)
            ");
            
            $now = date('Y-m-d H:i:s');
            
            $stmt->execute([
                $title,
                $description,
                '',  // website_url
                1,   // category_id (default to books category)
                $slug,
                $coverImageUrl,
                $now,
                $now
            ]);
            
            $directoryItemId = $db->lastInsertId();
            echo "<p class='success'>Created new directory item: $title (ID: $directoryItemId)</p>";
            flushOutput();
        }
        
        // Check if book already exists
        $stmt = $db->prepare("SELECT * FROM books WHERE directory_item_id = ?");
        $stmt->execute([$directoryItemId]);
        $existingBook = $stmt->fetch();
        
        if ($existingBook) {
            // Update existing book
            $stmt = $db->prepare("
                UPDATE books SET
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
                    metadata = ?,
                    genre = ?,
                    series = ?,
                    updated_at = NOW()
                WHERE directory_item_id = ?
            ");
            
            // Convert publication date to MySQL format
            $publicationDate = $pubDateStr ?: null;
            $formattedDate = convertToMySQLDate($publicationDate);
            echo "<p class='info'>Converted to MySQL format: '$formattedDate'</p>";
            flushOutput();
            
            $stmt->execute([
                $isbn,
                $isbn13,
                $author,
                $publisher,
                $formattedDate,
                $pageCount,
                $ageRange,
                $readingLevel,
                $coverImageUrl,
                json_encode($purchaseLinks),
                json_encode($enhancedData),
                $genre,
                $series,
                $directoryItemId
            ]);
            
            echo "<p class='success'>Updated book data for: $title (ID: $directoryItemId)</p>";
            flushOutput();
        } else {
            // Create new book
            $stmt = $db->prepare("
                INSERT INTO books (
                    directory_item_id, isbn, isbn13, author, publisher, publication_date,
                    page_count, age_range, reading_level, cover_image_url, purchase_links,
                    metadata, genre, series, created_at, updated_at
                ) VALUES (
                    ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, NOW(), NOW()
                )
            ");
            
            // Convert publication date to MySQL format
            $publicationDate = $pubDateStr ?: null;
            $formattedDate = convertToMySQLDate($publicationDate);
            echo "<p class='info'>Converted to MySQL format: '$formattedDate'</p>";
            flushOutput();
            
            $stmt->execute([
                $directoryItemId,
                $isbn,
                $isbn13,
                $author,
                $publisher,
                $formattedDate, // Use the properly converted date
                $pageCount,
                $ageRange,
                $readingLevel,
                $coverImageUrl,
                json_encode($purchaseLinks),
                json_encode($enhancedData),
                $genre,
                $series
            ]);
            
            echo "<p class='success'>Created new book: $title (ID: $directoryItemId)</p>";
            flushOutput();
        }
        
        // Create book-author relationships
        // First, delete any existing relationships
        $stmt = $db->prepare("DELETE FROM book_authors WHERE directory_item_id = ?");
        $stmt->execute([$directoryItemId]);
        
        // Add book author relationship
        if ($authorId) {
            $stmt = $db->prepare("
                INSERT INTO book_authors (directory_item_id, author_id, role)
                VALUES (?, ?, 'author')
            ");
            $stmt->execute([$directoryItemId, $authorId]);
            echo "<p class='success'>Created book-author relationship for '$title' and author ID $authorId</p>";
            flushOutput();
        }
        
        // Add book publisher relationship
        if ($publisherId) {
            $stmt = $db->prepare("
                INSERT INTO book_authors (directory_item_id, author_id, role)
                VALUES (?, ?, 'publisher')
            ");
            $stmt->execute([$directoryItemId, $publisherId]);
            echo "<p class='success'>Created book-publisher relationship for '$title' and publisher ID $publisherId</p>";
            flushOutput();
        }
        
        // Commit the transaction
        $db->commit();
        echo "<p class='success'>Book transaction committed successfully</p>";
        flushOutput();
        
        return [
            'success' => true,
            'action' => $existingDirectoryItem ? 'updated' : 'created',
            'id' => $directoryItemId
        ];
    } catch (Exception $e) {
        // Rollback transaction on error
        if ($db->inTransaction()) {
            $db->rollBack();
            echo "<p class='error'>Transaction rolled back</p>";
            flushOutput();
        }
        echo "<p class='error'>Error processing book: " . $e->getMessage() . "</p>";
        flushOutput();
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}