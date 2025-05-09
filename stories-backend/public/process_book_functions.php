<?php
/**
 * Book Processing Functions
 * This file contains the functions needed to process books for import
 */

/**
 * Convert a file path to a web-accessible URL
 *
 * @param string $path File path
 * @return string Web-accessible URL
 */
function pathToUrl($path) {
    // Get server information
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'api.storiesfromtheweb.org';
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';

    // If path is already a URL, return it
    if (strpos($path, 'http') === 0) {
        return $path;
    }

    // If path is relative (starts with /), make it absolute
    if (strpos($path, '/') === 0) {
        return "$protocol://$host$path";
    }

    // Otherwise, assume it's a relative path and convert it
    $basePath = realpath(dirname(__FILE__) . '/../');
    $relativePath = str_replace($basePath, '', $path);
    $relativePath = '/' . ltrim($relativePath, '/');

    return "$protocol://$host$relativePath";
}

/**
 * Convert a date string to MySQL format
 *
 * @param string|null $dateStr Date string
 * @return string|null MySQL formatted date or null
 */
function convertToMySQLDateOLD($dateStr) {
    if (empty($dateStr)) {
        return null;
    }

    // Try to parse the date
    $timestamp = strtotime($dateStr);
    if ($timestamp === false) {
        return null;
    }

    // Format as MySQL date
    return date('Y-m-d', $timestamp);
}

/**
 * Check image optimizer setup and report any issues
 */
function checkImageOptimizerSetup() {
    $imageOptimizerPath = __DIR__ . '/../includes/image_optimizer.php';
    $result = [
        'found' => false,
        'functions_available' => false,
        'message' => ''
    ];

    if (file_exists($imageOptimizerPath)) {
        $result['found'] = true;
        $result['message'] = "Image optimizer found at $imageOptimizerPath";

        // Include the file
        require_once $imageOptimizerPath;

        // Check for required functions
        $requiredFunctions = ['createImageVariants', 'resizeImage', 'updateMediaRecord'];
        $missingFunctions = [];

        foreach ($requiredFunctions as $function) {
            if (!function_exists($function)) {
                $missingFunctions[] = $function;
            }
        }

        if (empty($missingFunctions)) {
            $result['functions_available'] = true;
            $result['message'] .= ". All required functions are available.";
        } else {
            $result['message'] .= ". Missing functions: " . implode(', ', $missingFunctions);
        }
    } else {
        $result['message'] = "Image optimizer not found at $imageOptimizerPath";
    }

    return $result;
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

        // Enhanced image search patterns - include more directories and nested folders
        $imagePatterns = [
            "$bookDir/*.{jpg,jpeg,png,gif,webp}",                // Root directory
            "$bookDir/images/*.{jpg,jpeg,png,gif,webp}",         // images subdirectory
            "$bookDir/images/**/*.{jpg,jpeg,png,gif,webp}",      // nested images subdirectories
            "$bookDir/image/*.{jpg,jpeg,png,gif,webp}",          // image subdirectory
            "$bookDir/image/**/*.{jpg,jpeg,png,gif,webp}",       // nested image subdirectories
            "$bookDir/cover/*.{jpg,jpeg,png,gif,webp}",          // cover subdirectory
            "$bookDir/covers/*.{jpg,jpeg,png,gif,webp}",         // covers subdirectory
            "$bookDir/media/*.{jpg,jpeg,png,gif,webp}",          // media subdirectory
            "$bookDir/media/**/*.{jpg,jpeg,png,gif,webp}",       // nested media subdirectories
            "$bookDir/assets/*.{jpg,jpeg,png,gif,webp}",         // assets subdirectory
            "$bookDir/assets/**/*.{jpg,jpeg,png,gif,webp}",      // nested assets subdirectories
            "$bookDir/img/*.{jpg,jpeg,png,gif,webp}",            // img subdirectory
            "$bookDir/uploads/*.{jpg,jpeg,png,gif,webp}",        // uploads subdirectory
            "$bookDir/wp-content/uploads/*.{jpg,jpeg,png,gif,webp}", // WordPress uploads
            "$bookDir/wp-content/uploads/**/*.{jpg,jpeg,png,gif,webp}" // Nested WordPress uploads
        ];

        // First try with GLOB_BRACE which is more efficient
        foreach ($imagePatterns as $pattern) {
            $matches = glob($pattern, GLOB_BRACE);
            if (!empty($matches)) {
                $imagePaths = array_merge($imagePaths, $matches);
            }
        }

        // If no images found, try a more thorough recursive search
        if (empty($imagePaths)) {
            echo "<p class='info'>No images found with standard patterns, trying recursive search...</p>";
            flushOutput();

            // Recursive function to find all images in a directory and its subdirectories
            function findImagesRecursively($dir) {
                $images = [];
                if (!is_dir($dir)) return $images;

                $files = scandir($dir);
                foreach ($files as $file) {
                    if ($file == '.' || $file == '..') continue;

                    $path = $dir . '/' . $file;
                    if (is_dir($path)) {
                        $images = array_merge($images, findImagesRecursively($path));
                    } else {
                        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                            $images[] = $path;
                        }
                    }
                }
                return $images;
            }

            $recursiveImages = findImagesRecursively($bookDir);
            $imagePaths = array_merge($imagePaths, $recursiveImages);
        }

        echo "<p class='info'>Found " . count($imagePaths) . " potential images for book: $title</p>";
        if (count($imagePaths) > 0) {
            echo "<p class='info'>Image paths found: " . implode(", ", array_map('basename', $imagePaths)) . "</p>";
        }
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

            // Create uploads directory with proper permissions
            $uploadsDir = __DIR__ . '/../uploads/books';
            if (!is_dir($uploadsDir)) {
                if (!mkdir($uploadsDir, 0755, true)) {
                    echo "<p class='error'>Failed to create uploads directory: $uploadsDir</p>";
                    flushOutput();
                } else {
                    echo "<p class='success'>Created uploads directory: $uploadsDir</p>";
                    flushOutput();
                }
            }

            // Generate a unique filename to prevent overwrites
            $fileExt = pathinfo($imageName, PATHINFO_EXTENSION);
            $uniqueImageName = uniqid('book_') . '_' . preg_replace('/[^a-zA-Z0-9\.]/', '_', $imageName);
            $destinationPath = $uploadsDir . '/' . $uniqueImageName;

            // Copy image with error checking
            if (!copy($imageFile, $destinationPath)) {
                echo "<p class='error'>Failed to copy image from $imageFile to $destinationPath</p>";
                flushOutput();
            } else {
                echo "<p class='success'>Successfully copied image to $destinationPath</p>";
                flushOutput();

                // Set proper permissions
                chmod($destinationPath, 0644);

                // Get file information
                $fileSize = filesize($destinationPath);
                $fileType = mime_content_type($destinationPath);

                // Try to optimize the image if the image_optimizer.php file exists
                $optimizedDir = __DIR__ . '/../uploads/optimized';
                $variants = null;
                $relativePath = '/uploads/books/' . $uniqueImageName;
                $absoluteUrl = pathToUrl($relativePath);

                // Check if image optimizer exists and include it
                $imageOptimizerPath = __DIR__ . '/../includes/image_optimizer.php';
                if (file_exists($imageOptimizerPath)) {
                    require_once $imageOptimizerPath;

                    // Check if the createImageVariants function exists
                    if (function_exists('createImageVariants')) {
                        echo "<p class='info'>Attempting to optimize image...</p>";
                        flushOutput();

                        // Create optimized directory if it doesn't exist
                        if (!is_dir($optimizedDir)) {
                            mkdir($optimizedDir, 0755, true);
                        }

                        // Create optimized variants
                        $variants = createImageVariants($destinationPath, $optimizedDir);

                        if ($variants && !empty($variants)) {
                            echo "<p class='success'>Successfully created optimized image variants</p>";
                            flushOutput();

                            // Use medium size as the primary image if available
                            if (isset($variants['medium'])) {
                                $relativePath = $variants['medium']['url'];
                                $fileSize = $variants['medium']['size'];
                                $fileType = 'image/webp'; // Assuming WebP conversion

                                echo "<p class='info'>Using optimized medium variant: $relativePath</p>";
                                flushOutput();
                            } else if (isset($variants['original'])) {
                                $relativePath = $variants['original']['url'];
                                echo "<p class='info'>Using optimized original variant: $relativePath</p>";
                                flushOutput();
                            }
                        } else {
                            echo "<p class='warning'>Failed to create optimized variants, using original image</p>";
                            flushOutput();
                        }
                    } else {
                        echo "<p class='warning'>createImageVariants function not found, using original image</p>";
                        flushOutput();
                    }
                } else {
                    echo "<p class='warning'>Image optimizer not found at $imageOptimizerPath, using original image</p>";
                    flushOutput();
                }

                // Check if image already exists in media table
                $stmt = $db->prepare("SELECT id FROM media WHERE file_path = ?");
                $stmt->execute([$relativePath]);
                $existingMedia = $stmt->fetch();

                if ($existingMedia) {
                    // Use existing media record
                    $mediaId = $existingMedia['id'];
                    echo "<p class='info'>Using existing media record (ID: $mediaId) for path: $relativePath</p>";
                    flushOutput();
                } else {
                    // Create a new media record
                    $stmt = $db->prepare("
                        INSERT INTO media (
                            filename, file_path, file_size, file_type,
                            created_at, updated_at
                        ) VALUES (?, ?, ?, ?, NOW(), NOW())
                    ");

                    try {
                        $stmt->execute([
                            $uniqueImageName,
                            $relativePath, // Store relative path for consistency
                            $fileSize,
                            $fileType
                        ]);

                        $mediaId = $db->lastInsertId();
                        echo "<p class='success'>Added image to media table (ID: $mediaId)</p>";
                        echo "<p class='info'>Image URL: $absoluteUrl</p>";
                        flushOutput();

                        // Update media record with variants if available
                        if ($variants && function_exists('updateMediaRecord')) {
                            $updateResult = updateMediaRecord($db, $mediaId, $variants);
                            if ($updateResult) {
                                echo "<p class='success'>Updated media record with variant information</p>";
                            } else {
                                echo "<p class='warning'>Failed to update media record with variant information</p>";
                            }
                            flushOutput();
                        }
                    } catch (Exception $e) {
                        echo "<p class='error'>Database error adding image to media table: " . $e->getMessage() . "</p>";
                        flushOutput();
                    }
                }

                // Set the cover image URL
                $coverImageUrl = $relativePath;
            }
        } else {
            echo "<p class='warning'>No images found for book: $title</p>";
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
                    category_id = ?,
                    slug = ?,
                    website_url = ?,
                    contact_email = NULL,
                    contact_phone = NULL,
                    address = NULL,
                    featured = 0,
                    rating = 0.0,
                    price_range = NULL,
                    cover_url = ?,
                    is_published = 0,
                    updated_at = NOW(),
                    story_id = NULL,
                    type = 'book'
                WHERE id = ?
            ");

            $stmt->execute([
                $title,
                $description,
                1,   // category_id (default to books category)
                $slug,
                '',  // website_url
                $coverImageUrl,
                $directoryItemId
            ]);

            echo "<p class='success'>Updated existing directory item: $title (ID: $directoryItemId)</p>";
            flushOutput();
        } else {
            // Create new directory item
            $stmt = $db->prepare("
                INSERT INTO directory_items (
                    title, description, category_id, slug, published_at, website_url,
                    contact_email, contact_phone, address, featured, rating,
                    price_range, cover_url, is_published, created_at, updated_at,
                    story_id, type
                ) VALUES (
                    ?, ?, ?, ?, NOW(), ?,
                    NULL, NULL, NULL, 0, 0.0,
                    NULL, ?, 0, ?, ?,
                    NULL, 'book'
                )
            ");

            $now = date('Y-m-d H:i:s');

            $stmt->execute([
                $title,
                $description,
                1,   // category_id (default to books category)
                $slug,
                '',  // website_url
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