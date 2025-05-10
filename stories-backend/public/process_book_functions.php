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

// Check if convertToMySQLDate function already exists (defined in direct_import.php)
if (!function_exists('convertToMySQLDate')) {
    /**
     * Convert a date string to MySQL format
     *
     * @param string|null $dateStr Date string
     * @return string|null MySQL formatted date or null
     */
    function convertToMySQLDate($dateStr) {
        if (empty($dateStr)) {
            return null;
        }

        // Store original for debugging
        $originalDate = $dateStr;

        // Clean up the date string
        $dateStr = trim($dateStr);

        // Case 1: Just a year (e.g., "1975", "1937")
        if (preg_match('/^\d{4}$/', $dateStr)) {
            return $dateStr . '-01-01'; // Add month and day
        }

        // Case 2: Year-month (e.g., "2003-05")
        if (preg_match('/^(\d{4})-(\d{1,2})$/', $dateStr, $matches)) {
            return $matches[1] . '-' . str_pad($matches[2], 2, '0', STR_PAD_LEFT) . '-01'; // Add day
        }

        // Case 3: Month Year (e.g., "May 2003", "February 2012", "September 2013")
        if (preg_match('/^([a-zA-Z]+)\s+(\d{4})$/i', $dateStr, $matches)) {
            $month = $matches[1];
            $year = $matches[2];

            // Map of month names to numbers
            $months = array(
                'january' => '01', 'february' => '02', 'march' => '03',
                'april' => '04', 'may' => '05', 'june' => '06',
                'july' => '07', 'august' => '08', 'september' => '09',
                'october' => '10', 'november' => '11', 'december' => '12'
            );

            $monthLower = strtolower($month);
            if (isset($months[$monthLower])) {
                return $year . '-' . $months[$monthLower] . '-01';
            }

            // If month name not found in map, try strtotime as fallback
            try {
                $timestamp = strtotime("$month 1, $year");
                if ($timestamp !== false) {
                    return date('Y-m-d', $timestamp);
                }
            } catch (Exception $e) {
                // Ignore and use default
            }

            // If all else fails, default to January of that year
            return $year . '-01-01';
        }

        // Case 4: Already in YYYY-MM-DD format
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr)) {
            // Validate the date
            try {
                $date = new DateTime($dateStr);
                return $date->format('Y-m-d');
            } catch (Exception $e) {
                // Invalid date, try to extract just the year
                if (preg_match('/(\d{4})/', $dateStr, $matches)) {
                    return $matches[1] . '-01-01';
                }
                return null;
            }
        }

        // Case 5: Try to extract month and year in various formats
        if (preg_match('/([a-zA-Z]+)[^\d]*(\d{4})/i', $dateStr, $matches)) {
            $month = $matches[1];
            $year = $matches[2];

            // Map of month names to numbers
            $months = array(
                'january' => '01', 'february' => '02', 'march' => '03',
                'april' => '04', 'may' => '05', 'june' => '06',
                'july' => '07', 'august' => '08', 'september' => '09',
                'october' => '10', 'november' => '11', 'december' => '12',
                // Add abbreviated months
                'jan' => '01', 'feb' => '02', 'mar' => '03',
                'apr' => '04', 'jun' => '06', 'jul' => '07',
                'aug' => '08', 'sep' => '09', 'sept' => '09',
                'oct' => '10', 'nov' => '11', 'dec' => '12'
            );

            $monthLower = strtolower($month);
            if (isset($months[$monthLower])) {
                return $year . '-' . $months[$monthLower] . '-01';
            }
        }

        // Case 6: Just try to find a year as last resort
        if (preg_match('/(\d{4})/', $dateStr, $matches)) {
            $year = $matches[1];
            return $year . '-01-01';
        }

        // Case 7: Other formats that PHP's strtotime can handle
        try {
            $timestamp = strtotime($dateStr);
            if ($timestamp !== false) {
                return date('Y-m-d', $timestamp);
            }
        } catch (Exception $e) {
            // Ignore and try next method
        }

        // If all else fails, return null
        return null;
    }
}

/**
 * Legacy function for backward compatibility
 *
 * @param string|null $dateStr Date string
 * @return string|null MySQL formatted date or null
 */
function convertToMySQLDateOLD($dateStr) {
    // If the main function exists, use it
    if (function_exists('convertToMySQLDate')) {
        return convertToMySQLDate($dateStr);
    }

    // Otherwise, provide a simple implementation
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

        // Extract data from markdown content sections
        echo "<p class='info'>Extracting metadata from markdown content sections</p>";
        echo "<p class='info'>Markdown content preview: " . substr(htmlspecialchars($markdownContent), 0, 300) . "...</p>";
        flushOutput();

        // Extract Book & Author Info section
        $bookInfoPattern = '/#{1,3}\s*Book\s*&\s*Author\s*Info.*?(?=#{1,3}|$)/is';
        echo "<p class='info'>Searching for Book & Author Info section with pattern: " . htmlspecialchars($bookInfoPattern) . "</p>";
        flushOutput();

        if (preg_match($bookInfoPattern, $markdownContent, $bookInfoMatch)) {
            $bookInfoContent = $bookInfoMatch[0];
            echo "<p class='success'>Found Book & Author Info section: " . substr(htmlspecialchars($bookInfoContent), 0, 200) . "...</p>";
            flushOutput();

            // Extract Book Title
            if (preg_match('/\*\*Book\s+Title:\*\*\s*(.*?)(?:\n|$)/i', $bookInfoContent, $match)) {
                $bookTitle = trim($match[1]);
                echo "<p class='info'>Found book title in content: '$bookTitle'</p>";
                flushOutput();
                // Update title if found in content
                if (!empty($bookTitle)) {
                    $title = $bookTitle;
                }
            }

            // Extract Book Author
            if (preg_match('/\*\*Book\s+Author:\*\*\s*(.*?)(?:\n|$)/i', $bookInfoContent, $match)) {
                $bookAuthor = trim($match[1]);
                echo "<p class='info'>Found book author in content: '$bookAuthor'</p>";
                flushOutput();
                if (!empty($bookAuthor)) {
                    $author = $bookAuthor;
                }
            }

            // Extract Book Series
            if (preg_match('/\*\*(?:Book\s+)?Series:\*\*\s*(.*?)(?:\n|$)/i', $bookInfoContent, $match)) {
                $series = trim($match[1]);
                echo "<p class='info'>Found book series in content: '$series'</p>";
                flushOutput();
            }

            // Extract Book Genre
            if (preg_match('/\*\*(?:Book\s+)?Genre:\*\*\s*(.*?)(?:\n|$)/i', $bookInfoContent, $match)) {
                $genre = trim($match[1]);
                echo "<p class='info'>Found book genre in content: '$genre'</p>";
                flushOutput();
            }

            // Extract Book Age Range
            if (preg_match('/\*\*(?:Book\s+)?Age\s+Range:\*\*\s*(.*?)(?:\n|$)/i', $bookInfoContent, $match)) {
                $bookAgeRange = trim($match[1]);
                echo "<p class='info'>Found book age range in content: '$bookAgeRange'</p>";
                flushOutput();
                if (!empty($bookAgeRange)) {
                    $ageRange = $bookAgeRange;
                }
            }
        }

        // Extract Plot section
        if (preg_match('/#{1,3}\s*.*?PLOT.*?(?=#{1,3}|$)/is', $markdownContent, $plotMatch)) {
            $plotContent = $plotMatch[0];
            // Remove the heading
            $plotContent = preg_replace('/#{1,3}\s*.*?PLOT.*?\n/is', '', $plotContent);
            $plotInfo = trim($plotContent);
            echo "<p class='info'>Found plot information in content section</p>";
            flushOutput();
        }

        // Extract Publisher Information section
        $publisherPattern = '/#{1,3}\s*Publisher\s*Information.*?(?=#{1,3}|$)/is';
        echo "<p class='info'>Searching for Publisher Information section with pattern: " . htmlspecialchars($publisherPattern) . "</p>";
        flushOutput();

        if (preg_match($publisherPattern, $markdownContent, $publisherMatch)) {
            $publisherContent = $publisherMatch[0];
            echo "<p class='success'>Found Publisher Information section: " . substr(htmlspecialchars($publisherContent), 0, 200) . "...</p>";
            flushOutput();
        } else {
            // Try alternative pattern
            $altPublisherPattern = '/#{1,3}\s*Publisher.*?(?=#{1,3}|$)/is';
            echo "<p class='info'>Trying alternative pattern for Publisher section: " . htmlspecialchars($altPublisherPattern) . "</p>";
            flushOutput();

            if (preg_match($altPublisherPattern, $markdownContent, $publisherMatch)) {
                $publisherContent = $publisherMatch[0];
                echo "<p class='success'>Found Publisher section with alternative pattern: " . substr(htmlspecialchars($publisherContent), 0, 200) . "...</p>";
                flushOutput();
            } else {
                echo "<p class='warning'>No Publisher Information section found in the content</p>";
                flushOutput();
                $publisherContent = $markdownContent; // Use the entire content as a fallback
            }
        }

        // Extract Publisher name
        if (preg_match('/published\s+by\s+(.*?)(?:\n|$)/i', $publisherContent, $match)) {
            $publisherName = trim($match[1]);
            echo "<p class='info'>Found publisher in content: '$publisherName'</p>";
            flushOutput();
            if (!empty($publisherName)) {
                $publisher = $publisherName;
            }
        }

        // Extract First published date with improved pattern
        if (preg_match('/First\s+[Pp]ublished\s+(.*?)(?:\n|$)/i', $publisherContent, $match)) {
            $publisherDate = trim($match[1]);
            echo "<p class='info'>Found publication date in publisher info: '$publisherDate'</p>";
            flushOutput();

            if (empty($pubDateStr)) {
                $pubDateStr = $publisherDate;
                echo "<p class='success'>Using publication date from publisher info</p>";
                flushOutput();
            }
        }

        // Extract ISBN - try multiple patterns
        if (preg_match('/ISBN\s*[-:]\s*([0-9-]+)/i', $publisherContent, $match)) {
            $extractedIsbn = trim($match[1]);
            echo "<p class='info'>Found ISBN in publisher info: '$extractedIsbn'</p>";
            flushOutput();

            // Determine if it's ISBN-10 or ISBN-13 based on length
            $cleanIsbn = preg_replace('/[^0-9]/', '', $extractedIsbn);
            if (strlen($cleanIsbn) == 10) {
                $isbn = $extractedIsbn;
                echo "<p class='success'>Using ISBN-10 from publisher info</p>";
            } elseif (strlen($cleanIsbn) == 13) {
                $isbn13 = $extractedIsbn;
                echo "<p class='success'>Using ISBN-13 from publisher info</p>";
            }
            flushOutput();
        } else {
            // Try alternative pattern for ISBN
            if (preg_match('/ISBN.*?(\d[\d-]+\d)/i', $publisherContent, $match)) {
                $extractedIsbn = trim($match[1]);
                echo "<p class='info'>Found ISBN with alternative pattern: '$extractedIsbn'</p>";
                flushOutput();

                // Determine if it's ISBN-10 or ISBN-13 based on length
                $cleanIsbn = preg_replace('/[^0-9]/', '', $extractedIsbn);
                if (strlen($cleanIsbn) == 10) {
                    $isbn = $extractedIsbn;
                    echo "<p class='success'>Using ISBN-10 from publisher info</p>";
                } elseif (strlen($cleanIsbn) == 13) {
                    $isbn13 = $extractedIsbn;
                    echo "<p class='success'>Using ISBN-13 from publisher info</p>";
                }
                flushOutput();
            }
        }

        // Extract Page Count
        if (preg_match('/(\d+)\s+pages/i', $publisherContent, $match)) {
            $extractedPageCount = intval($match[1]);
            echo "<p class='info'>Found page count in publisher info: $extractedPageCount</p>";
            flushOutput();

            if (empty($pageCount)) {
                $pageCount = $extractedPageCount;
                echo "<p class='success'>Using page count from publisher info</p>";
                flushOutput();
            }
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
            // Publisher info not needed as author_type is set in SQL

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
                    INSERT INTO authors (name, author_type, slug, created_at, updated_at)
                    VALUES (?, ?, ?, NOW(), NOW())
                ");

                // Generate slug
                $publisherSlug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $publisher));
                $publisherSlug = trim($publisherSlug, '-');

                $stmt->execute([$publisher, 'retail', $publisherSlug]);
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

        // Author info not needed as author_type is set in SQL

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
                INSERT INTO authors (name, author_type, slug, created_at, updated_at)
                VALUES (?, ?, ?, NOW(), NOW())
            ");

            // Generate slug
            $authorSlug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $authorName));
            $authorSlug = trim($authorSlug, '-');

            $stmt->execute([$authorName, 'retail', $authorSlug]);
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

                // Debug: Show the actual structure of the media table
                echo "<p class='info'>Checking media table structure...</p>";
                flushOutput();
                try {
                    $tableInfo = $db->query("DESCRIBE media");
                    echo "<pre>";
                    while ($row = $tableInfo->fetch(PDO::FETCH_ASSOC)) {
                        echo htmlspecialchars(print_r($row, true));
                    }
                    echo "</pre>";
                    flushOutput();
                } catch (Exception $e) {
                    echo "<p class='error'>Error getting table structure: " . $e->getMessage() . "</p>";
                    flushOutput();
                }

                // Always create a new media record for the image, even if the file already exists
                // This ensures that when "nuke images" is clicked, new records are created in the media table
                // Using only the columns we know exist from the screenshot
                $now = date('Y-m-d H:i:s');
                $stmt = $db->prepare("
                    INSERT INTO media (
                        filename, file_path, file_size, file_type,
                        alt_text, created_at, updated_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?)
                ");

                try {
                    $stmt->execute([
                        $uniqueImageName,
                        $relativePath, // Store relative path for consistency
                        $fileSize,
                        $fileType,
                        "Cover image for $title", // Alt text for the image
                        $now, // created_at
                        $now  // updated_at
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

            // Update existing directory item - using explicit column names to match the actual table structure
            $now = date('Y-m-d H:i:s');
            $stmt = $db->prepare("
                UPDATE directory_items SET
                    title = ?,
                    description = ?,
                    category_id = ?,
                    slug = ?,
                    website_url = ?,
                    cover_url = ?,
                    updated_at = ?,
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
                $now, // updated_at
                $directoryItemId
            ]);

            echo "<p class='success'>Updated existing directory item: $title (ID: $directoryItemId)</p>";
            flushOutput();
        } else {
            // Create new directory item - using explicit column names to match the actual table structure
            $now = date('Y-m-d H:i:s');
            $stmt = $db->prepare("
                INSERT INTO directory_items
                    (title, description, category_id, slug, website_url,
                    cover_url, is_published, created_at, updated_at, type)
                VALUES
                    (?, ?, ?, ?, ?, ?, ?, ?, ?, 'book')
            ");

            $stmt->execute([
                $title,
                $description,
                1,   // category_id (default to books category)
                $slug,
                '',  // website_url
                $coverImageUrl,
                1,   // is_published
                $now, // created_at
                $now  // updated_at
            ]);

            $directoryItemId = $db->lastInsertId();
            echo "<p class='success'>Created new directory item: $title (ID: $directoryItemId)</p>";
            flushOutput();
        }

        // Debug: Show the actual structure of the books table
        echo "<p class='info'>Checking books table structure...</p>";
        flushOutput();
        try {
            $tableInfo = $db->query("DESCRIBE books");
            echo "<pre>";
            while ($row = $tableInfo->fetch(PDO::FETCH_ASSOC)) {
                echo htmlspecialchars(print_r($row, true));
            }
            echo "</pre>";
            flushOutput();
        } catch (Exception $e) {
            echo "<p class='error'>Error getting books table structure: " . $e->getMessage() . "</p>";
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
                    series = ?
                WHERE directory_item_id = ?
            ");

            // Convert publication date to MySQL format
            $publicationDate = $pubDateStr ?: null;

            // Ensure we have a valid date format
            if ($publicationDate) {
                if (function_exists('convertToMySQLDate')) {
                    $formattedDate = convertToMySQLDate($publicationDate);
                } else {
                    // Fallback date conversion if function doesn't exist
                    $timestamp = strtotime($publicationDate);
                    $formattedDate = $timestamp ? date('Y-m-d', $timestamp) : null;
                }
                echo "<p class='info'>Converted to MySQL format: '$formattedDate'</p>";
            } else {
                $formattedDate = null;
                echo "<p class='warning'>No publication date found to convert</p>";
            }
            flushOutput();

            // Debug values before executing SQL
            echo "<p class='info'>Book data to be updated:</p>";
            echo "<ul>";
            echo "<li>directory_item_id: $directoryItemId</li>";
            echo "<li>isbn: $isbn</li>";
            echo "<li>isbn13: $isbn13</li>";
            echo "<li>author: $author</li>";
            echo "<li>publisher: $publisher</li>";
            echo "<li>publication_date: $formattedDate</li>";
            echo "<li>page_count: $pageCount</li>";
            echo "<li>age_range: $ageRange</li>";
            echo "<li>reading_level: $readingLevel</li>";
            echo "<li>cover_image_url: $coverImageUrl</li>";
            echo "</ul>";
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
                    metadata, genre, series
                ) VALUES (
                    ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?,
                    ?, ?, ?
                )
            ");

            // Convert publication date to MySQL format
            $publicationDate = $pubDateStr ?: null;

            // Ensure we have a valid date format
            if ($publicationDate) {
                if (function_exists('convertToMySQLDate')) {
                    $formattedDate = convertToMySQLDate($publicationDate);
                } else {
                    // Fallback date conversion if function doesn't exist
                    $timestamp = strtotime($publicationDate);
                    $formattedDate = $timestamp ? date('Y-m-d', $timestamp) : null;
                }
                echo "<p class='info'>Converted to MySQL format: '$formattedDate'</p>";
            } else {
                $formattedDate = null;
                echo "<p class='warning'>No publication date found to convert</p>";
            }
            flushOutput();

            // Debug values before executing SQL
            echo "<p class='info'>Book data to be inserted:</p>";
            echo "<ul>";
            echo "<li>directory_item_id: $directoryItemId</li>";
            echo "<li>isbn: $isbn</li>";
            echo "<li>isbn13: $isbn13</li>";
            echo "<li>author: $author</li>";
            echo "<li>publisher: $publisher</li>";
            echo "<li>publication_date: $formattedDate</li>";
            echo "<li>page_count: $pageCount</li>";
            echo "<li>age_range: $ageRange</li>";
            echo "<li>reading_level: $readingLevel</li>";
            echo "<li>cover_image_url: $coverImageUrl</li>";
            echo "</ul>";
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

        // Process tags for the book
        $tagCount = processBookTags($db, $directoryItemId, $markdownContent, $genre);
        echo "<p class='info'>Processed $tagCount tags for book: $title</p>";
        flushOutput();

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

        // Get the stack trace to help identify where the error is occurring
        echo "<p class='error'>Error processing book: " . $e->getMessage() . "</p>";
        echo "<p class='error'>Error occurred at: " . $e->getFile() . " line " . $e->getLine() . "</p>";
        echo "<pre class='error'>" . $e->getTraceAsString() . "</pre>";
        flushOutput();

        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Process tags for a book and associate them with the directory item
 *
 * @param PDO $db Database connection
 * @param int $directoryItemId Directory item ID
 * @param string $markdownContent Markdown content
 * @param string $genre Genre string (comma-separated)
 * @return int Number of tags processed
 */
function processBookTags($db, $directoryItemId, $markdownContent, $genre = '') {
    // Extract tags from markdown content
    $tags = [];
    if (preg_match('/Tags:\s*(.*?)(?:\n|$)/i', $markdownContent, $match)) {
        $tagsString = trim($match[1]);
        echo "<p class='info'>Found tags in content: '$tagsString'</p>";

        // Extract tags from HTML links or plain text
        if (preg_match_all('/<a.*?>(.*?)<\/a>/i', $tagsString, $matches)) {
            $tags = $matches[1];
        } else {
            // Split by commas if no HTML links
            $tags = array_map('trim', explode(',', $tagsString));
        }

        echo "<p class='info'>Extracted tags: " . implode(', ', $tags) . "</p>";
    }

    // If no tags found but we have genre, use genre as tags
    if (empty($tags) && !empty($genre)) {
        $genreTags = array_map('trim', explode(',', $genre));
        $tags = array_merge($tags, $genreTags);
        echo "<p class='info'>Using genre as tags: " . implode(', ', $genreTags) . "</p>";
    }

    // Process each tag
    foreach ($tags as $tagName) {
        if (empty(trim($tagName))) continue;

        // Get or create tag
        $stmt = $db->prepare("SELECT id FROM tags WHERE LOWER(name) = LOWER(?)");
        $stmt->execute([trim($tagName)]);
        $tag = $stmt->fetch();

        if ($tag) {
            $tagId = $tag['id'];
            echo "<p class='info'>Found existing tag: " . htmlspecialchars($tagName) . " (ID: $tagId)</p>";
        } else {
            // Create new tag
            $tagSlug = strtolower(preg_replace('/[^a-z0-9]+/', '-', trim($tagName)));
            $tagSlug = trim($tagSlug, '-');
            $now = date('Y-m-d H:i:s');

            $stmt = $db->prepare("INSERT INTO tags (name, slug, created_at, updated_at) VALUES (?, ?, ?, ?)");
            $stmt->execute([trim($tagName), $tagSlug, $now, $now]);
            $tagId = $db->lastInsertId();
            echo "<p class='success'>Created new tag: " . htmlspecialchars($tagName) . " (ID: $tagId)</p>";
        }

        // Check if tag is already associated with the directory item
        $stmt = $db->prepare("SELECT * FROM directory_item_tags WHERE directory_item_id = ? AND tag_id = ?");
        $stmt->execute([$directoryItemId, $tagId]);
        $existingRelation = $stmt->fetch();

        if (!$existingRelation) {
            // Associate tag with directory item
            $stmt = $db->prepare("INSERT INTO directory_item_tags (directory_item_id, tag_id) VALUES (?, ?)");
            $stmt->execute([$directoryItemId, $tagId]);
            echo "<p class='success'>Associated tag " . htmlspecialchars($tagName) . " with book ID $directoryItemId</p>";
        } else {
            echo "<p class='info'>Tag " . htmlspecialchars($tagName) . " already associated with book ID $directoryItemId</p>";
        }
    }

    return count($tags);
}