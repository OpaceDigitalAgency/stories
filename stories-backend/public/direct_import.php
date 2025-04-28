<?php
/**
 * Direct WordPress Import Tool
 * 
 * A comprehensive tool to import WordPress content with proper handling of
 * media files, authors, and tags.
 * 
 * Features:
 * - One-click import with cleaning option
 * - Robust database transactions for each story
 * - Accurate author extraction and handling
 * - Clean, meaningful excerpts
 * - Proper slug and story matching
 * - Better tag generation and linking
 * - Bullet-proof media uploads
 * - Real-time debug and error handling
 */

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

// Database connection function with error handling
function connectToDatabase() {
    try {
        $db = new PDO(
            'mysql:host=localhost;dbname=stories_db;charset=utf8mb4',
            'stories_user',
            '$tw1cac3*sOt',
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
        echo "<p class='success'>Database connection successful</p>";
        flushOutput();
        return $db;
    } catch (PDOException $e) {
        echo "<p class='error'>Database connection failed: " . $e->getMessage() . "</p>";
        flushOutput();
        exit;
    }
}

// Function to clean all child-story data
function cleanChildStoryData($db) {
    try {
        // Begin transaction
        $db->beginTransaction();

        // 1. Delete story_tags associations for child stories
        $db->exec("DELETE st FROM story_tags st 
                  JOIN stories s ON st.story_id = s.id 
                  WHERE s.source_type = 'child'");
        echo "<p class='info'>Deleted story-tag associations for child stories</p>";
        flushOutput();
        
        // 2. Delete story_authors associations for child stories
        $db->exec("DELETE sa FROM story_authors sa 
                  JOIN stories s ON sa.story_id = s.id 
                  WHERE s.source_type = 'child'");
        echo "<p class='info'>Deleted story-author associations for child stories</p>";
        flushOutput();
        
        // 3. Delete child stories
        $stmt = $db->prepare("DELETE FROM stories WHERE source_type = 'child'");
        $stmt->execute();
        $count = $stmt->rowCount();
        echo "<p class='info'>Deleted $count existing child stories</p>";
        flushOutput();
        
        // 4. Delete unused authors (those without any stories)
        $db->exec("DELETE a FROM authors a 
                  LEFT JOIN story_authors sa ON a.id = sa.author_id 
                  WHERE sa.author_id IS NULL AND a.author_type = 'child'");
        echo "<p class='info'>Deleted unused child authors</p>";
        flushOutput();
        
        // 5. Delete unused media files
        $db->exec("DELETE FROM media WHERE id > 1");
        echo "<p class='info'>Deleted existing media files</p>";
        flushOutput();
        
        // Commit transaction
        $db->commit();
        echo "<p class='success'>Database cleaned successfully</p>";
        flushOutput();
        return true;
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        echo "<p class='error'>Clean operation failed: " . $e->getMessage() . "</p>";
        flushOutput();
        return false;
    }
}

// Function to extract author info from title using reliable regex
function extractAuthorInfo($title) {
    $info = [
        'name' => null,
        'age' => null,
        'location' => null
    ];
    
    // Pattern 1: "by <Name>, aged <Age>, from <Location>"
    if (preg_match('/by\s+([^,]+?)(?:,?\s+aged\s+(\d+))?(?:,?\s+from\s+([^,.]+))?/i', $title, $matches)) {
        $info['name'] = trim($matches[1]);
        $info['age'] = isset($matches[2]) ? trim($matches[2]) : null;
        $info['location'] = isset($matches[3]) ? trim($matches[3]) : null;
    }
    // Pattern 2: "Name, aged X, from Location"
    else if (preg_match('/([^,]+),\s+aged\s+(\d+)(?:,\s+from\s+([^,.]+))?/i', $title, $matches)) {
        $info['name'] = trim($matches[1]);
        $info['age'] = isset($matches[2]) ? trim($matches[2]) : null;
        $info['location'] = isset($matches[3]) ? trim($matches[3]) : null;
    }
    
    echo "<p class='info'>Extracted author: " . ($info['name'] ?? 'Unknown') .
         ", age: " . ($info['age'] ?? 'Unknown') .
         ", location: " . ($info['location'] ?? 'Unknown') . "</p>";
    flushOutput();
    
    return $info;
}

// Function to get or create author with proper handling
function getOrCreateAuthor($db, $authorInfo) {
    if (empty($authorInfo['name'])) {
        echo "<p class='warning'>No author name found</p>";
        flushOutput();
        return null;
    }
    
    $slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $authorInfo['name']));
    
    // Check if author exists by name or slug (case-insensitive)
    $stmt = $db->prepare("SELECT id, bio FROM authors WHERE LOWER(slug) = LOWER(?) OR LOWER(name) = LOWER(?)");
    $stmt->execute([$slug, $authorInfo['name']]);
    $author = $stmt->fetch();
    
    if ($author) {
        echo "<p class='info'>Author already exists: {$authorInfo['name']} (ID: {$author['id']})</p>";
        flushOutput();
        
        // Always update age and location
        $bio = $author['bio'];
        if (empty($bio)) {
            $bio = "{$authorInfo['name']} is a child author" . 
                   ($authorInfo['age'] ? " aged {$authorInfo['age']}" : "") . 
                   ($authorInfo['location'] ? " from {$authorInfo['location']}" : "") . ".";
        }
        
        $stmt = $db->prepare("UPDATE authors SET age = ?, location = ?, bio = ?, author_type = 'child' WHERE id = ?");
        $stmt->execute([$authorInfo['age'], $authorInfo['location'], $bio, $author['id']]);
        echo "<p class='success'>Updated author information</p>";
        flushOutput();
        
        return $author['id'];
    } else {
        // Create new author
        $bio = "{$authorInfo['name']} is a child author" . 
               ($authorInfo['age'] ? " aged {$authorInfo['age']}" : "") . 
               ($authorInfo['location'] ? " from {$authorInfo['location']}" : "") . ".";
        
        $stmt = $db->prepare("INSERT INTO authors (name, slug, bio, author_type, age, location, is_published) VALUES (?, ?, ?, 'child', ?, ?, 1)");
        $stmt->execute([
            $authorInfo['name'],
            $slug,
            $bio,
            $authorInfo['age'],
            $authorInfo['location']
        ]);
        
        $authorId = $db->lastInsertId();
        echo "<p class='success'>Created author with ID: $authorId</p>";
        flushOutput();
        
        return $authorId;
    }
}

// Function to extract clean, meaningful excerpt
function extractExcerpt($title, $markdownContent) {
    // Strip out "by ... aged ... from ..." metadata from title
    $cleanTitle = preg_replace('/by\s+[^,]+(?:,?\s+aged\s+\d+)?(?:,?\s+from\s+[^,.]+)?/i', '', $title);
    $cleanTitle = trim($cleanTitle);
    
    // First try to get from Summary section
    if (preg_match('/Summary\s*\n(.*?)(?:\n\n|\n#|\n\*\*|$)/s', $markdownContent, $summaryMatch)) {
        $summary = trim($summaryMatch[1]);
        
        // Extract just the first sentence
        if (preg_match('/^(.*?[.!?])(?:\s|$)/s', $summary, $sentenceMatch)) {
            return trim($sentenceMatch[1]);
        } else {
            return $summary;
        }
    } 
    
    // If no summary or empty excerpt, use first paragraph
    $paragraphs = preg_split('/\n\s*\n/', $markdownContent);
    $firstPara = trim($paragraphs[0]);
    
    // Remove any metadata like Name/Age/Location
    $firstPara = preg_replace('/^(?:Name|Age|Location):\s+.*$/m', '', $firstPara);
    
    // Extract just the first sentence
    if (preg_match('/^(.*?[.!?])(?:\s|$)/s', $firstPara, $sentenceMatch)) {
        return trim($sentenceMatch[1]);
    } else {
        return substr(strip_tags($firstPara), 0, 150) . '...';
    }
}
// Function to handle media upload with proper error handling
function handleMediaUpload($db, $storyDir, $title) {
    // Include the image optimization library
    require_once __DIR__ . '/../includes/image_optimizer.php';
    
    $imagesDir = "$storyDir/images";
    // Use absolute URL for default cover image
    $defaultCoverUrl = 'https://' . $_SERVER['HTTP_HOST'] . '/images/default-cover.svg';
    $coverUrl = $defaultCoverUrl; // Default
    $mediaId = null;
    
    // Debug info for missing images
    echo "<p class='info'>Looking for images in: $imagesDir</p>";
    flushOutput();
    
    // Check for pre-optimized images first (for all stories)
    $usePreOptimized = false;
    $preOptimizedImage = null;
    
    // Define all possible locations for optimized images
    $optimizedDirs = [
        $_SERVER['DOCUMENT_ROOT'] . '/../_wp migration/uploads/2023/07', // Primary location shown in screenshot
        $_SERVER['DOCUMENT_ROOT'] . '/../_wp migration/uploads/2023',
        $_SERVER['DOCUMENT_ROOT'] . '/../_wp migration/uploads/2024/10',
        $_SERVER['DOCUMENT_ROOT'] . '/../_wp migration/uploads/2024',
        $_SERVER['DOCUMENT_ROOT'] . '/../_wp migration/uploads', // Fallback to main uploads dir
    ];
    
    // Extract the base name without extension to search for optimized versions
    $baseNameParts = explode('-by-', basename($storyDir));
    $baseName = $baseNameParts[0];
    $storySlug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $baseName));
    echo "<p class='info'>Looking for optimized images for: $storySlug</p>";
    flushOutput();
    
    // Try to find optimized versions with different sizes (prioritize 300x300)
    $sizePatterns = [
        '300x300', // Best size for balance between quality and performance
        '240x240',
        '150x150',
        '110x110',
        '50x50',
        '' // Also try without size pattern
    ];
    
    // Known image patterns that exist on the server (from screenshot)
    $knownPatterns = [
        'future-library-captivating-storybook',
        'library-books-reading',
        'Gangsta-Granny-by-David-Walliams',
        'Frogspell'
    ];
    
    // First try the known patterns that we know exist on the server
    $found = false;
    
    // Try each directory, prioritizing the 2023/07 directory
    foreach ($optimizedDirs as $optimizedDir) {
        if ($found) break;
        
        echo "<p class='info'>Searching in directory: $optimizedDir</p>";
        flushOutput();
        
        // First try known patterns with size variations
        foreach ($knownPatterns as $pattern) {
            if ($found) break;
            
            foreach ($sizePatterns as $sizePattern) {
                $sizeStr = empty($sizePattern) ? "" : "-$sizePattern";
                $searchPattern = "$optimizedDir/$pattern$sizeStr.{jpg,jpeg,png,gif}";
                echo "<p class='info'>Trying pattern: " . basename($searchPattern) . "</p>";
                $optimizedImages = glob($searchPattern, GLOB_BRACE);
                
                if (!empty($optimizedImages)) {
                    // Sort by file size and use the smallest one
                    usort($optimizedImages, function($a, $b) {
                        return filesize($a) - filesize($b);
                    });
                    
                    $preOptimizedImage = $optimizedImages[0];
                    $usePreOptimized = true;
                    echo "<p class='success'>Found pre-optimized image: " . basename($preOptimizedImage) . " (" . round(filesize($preOptimizedImage) / 1024) . " KB)</p>";
                    flushOutput();
                    $found = true;
                    break;
                }
            }
        }
        
        // If still not found, try story-specific patterns
        if (!$found) {
            $storyPatterns = [
                "$storySlug*",
                "*$storySlug*",
                "*library*",
                "*story*",
                "*book*",
                "*illustration*"
            ];
            
            foreach ($storyPatterns as $pattern) {
                if ($found) break;
                
                foreach ($sizePatterns as $sizePattern) {
                    $sizeStr = empty($sizePattern) ? "" : "-$sizePattern";
                    $searchPattern = "$optimizedDir/$pattern$sizeStr.{jpg,jpeg,png,gif}";
                    $optimizedImages = glob($searchPattern, GLOB_BRACE);
                    
                    if (!empty($optimizedImages)) {
                        // Sort by file size and use the smallest one
                        usort($optimizedImages, function($a, $b) {
                            return filesize($a) - filesize($b);
                        });
                        
                        $preOptimizedImage = $optimizedImages[0];
                        $usePreOptimized = true;
                        echo "<p class='success'>Found pre-optimized image using pattern '$pattern$sizeStr': " . basename($preOptimizedImage) . " (" . round(filesize($preOptimizedImage) / 1024) . " KB)</p>";
                        flushOutput();
                        $found = true;
                        break;
                    }
                }
            }
        }
        
        // If still not found, try all images in this directory
        if (!$found) {
            $allImages = glob("$optimizedDir/*.{jpg,jpeg,png,gif}", GLOB_BRACE);
            
            if (!empty($allImages)) {
                // Filter for smaller images first (< 300KB)
                $smallImages = array_filter($allImages, function($img) {
                    return filesize($img) < 300 * 1024;
                });
                
                // If we have small images, use those, otherwise use any images
                $imagesToUse = !empty($smallImages) ? $smallImages : $allImages;
                
                // Sort by file size and use the smallest one
                usort($imagesToUse, function($a, $b) {
                    return filesize($a) - filesize($b);
                });
                
                $preOptimizedImage = $imagesToUse[0];
                $usePreOptimized = true;
                echo "<p class='success'>Found image in directory: " . basename($preOptimizedImage) . " (" . round(filesize($preOptimizedImage) / 1024) . " KB)</p>";
                flushOutput();
                $found = true;
            }
        }
    }
    
    // Special handling for problematic stories
    if (!$usePreOptimized) {
        // Force specific images for known problematic stories
        if (strpos($title, "Omagh Library") !== false) {
            // Look for any future-library images
            $possibleImages = glob("$optimizedDir/future-library*.{jpg,jpeg,png,gif}", GLOB_BRACE);
            
            if (!empty($possibleImages)) {
                // Sort by file size and use the smallest one
                usort($possibleImages, function($a, $b) {
                    return filesize($a) - filesize($b);
                });
                
                $preOptimizedImage = $possibleImages[0];
                $usePreOptimized = true;
                echo "<p class='success'>Found fallback image for Omagh Library: " . basename($preOptimizedImage) . " (" . round(filesize($preOptimizedImage) / 1024) . " KB)</p>";
                flushOutput();
            }
        } else if (strpos($title, "The Reader") !== false || strpos($title, "Old Library") !== false) {
            // Look for any library-related images
            $possibleImages = glob("$optimizedDir/*library*.{jpg,jpeg,png,gif}", GLOB_BRACE);
            
            if (empty($possibleImages)) {
                // If no library images, try future-library as fallback
                $possibleImages = glob("$optimizedDir/future-library*.{jpg,jpeg,png,gif}", GLOB_BRACE);
            }
            
            if (!empty($possibleImages)) {
                // Sort by file size and use the smallest one
                usort($possibleImages, function($a, $b) {
                    return filesize($a) - filesize($b);
                });
                
                $preOptimizedImage = $possibleImages[0];
                $usePreOptimized = true;
                echo "<p class='success'>Found fallback image for library story: " . basename($preOptimizedImage) . " (" . round(filesize($preOptimizedImage) / 1024) . " KB)</p>";
                flushOutput();
            }
        }
    }
    
    // Last resort fallback - use any available optimized image rather than default
    if (!$usePreOptimized) {
        // Try to find any optimized image under 300KB
        $allOptimizedImages = glob("$optimizedDir/*.{jpg,jpeg,png,gif}", GLOB_BRACE);
        $smallImages = [];
        
        foreach ($allOptimizedImages as $img) {
            if (filesize($img) < 300 * 1024) { // Less than 300KB
                $smallImages[] = $img;
            }
        }
        
        if (!empty($smallImages)) {
            // Sort by file size and use the smallest one
            usort($smallImages, function($a, $b) {
                return filesize($a) - filesize($b);
            });
            
            $preOptimizedImage = $smallImages[0];
            $usePreOptimized = true;
            echo "<p class='success'>Using generic fallback image: " . basename($preOptimizedImage) . " (" . round(filesize($preOptimizedImage) / 1024) . " KB)</p>";
            flushOutput();
        }
    }
    
    // If still no match, try with generic "future-library" pattern which seems to be common
    if (!$usePreOptimized) {
        foreach ($sizePatterns as $sizePattern) {
            $futureLibraryImages = glob("$optimizedDir/future-library*$sizePattern*.{jpg,jpeg,png,gif}", GLOB_BRACE);
            
            if (!empty($futureLibraryImages)) {
                $preOptimizedImage = $futureLibraryImages[0];
                $usePreOptimized = true;
                echo "<p class='success'>Found generic pre-optimized future-library image ($sizePattern): " . basename($preOptimizedImage) . "</p>";
                flushOutput();
                break;
            }
        }
    }
    
    // If we're using a pre-optimized image, process it directly
    if ($usePreOptimized && $preOptimizedImage) {
        $coverImage = basename($preOptimizedImage);
        
        // Use absolute server path for uploads
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
            echo "<p class='info'>Created uploads directory</p>";
            flushOutput();
        }
        
        // Generate unique filename to avoid collisions
        $uniqueFilename = uniqid() . '-' . $coverImage;
        $destination = $uploadDir . $uniqueFilename;
        
        // Create absolute URL (always use HTTPS for admin panel compatibility)
        $relativeUrl = '/uploads/' . $uniqueFilename;
        $absoluteUrl = 'https://' . $_SERVER['HTTP_HOST'] . $relativeUrl;
        
        echo "<p class='info'>Using pre-optimized image: $preOptimizedImage</p>";
        echo "<p class='info'>Absolute URL will be: $absoluteUrl</p>";
        flushOutput();
        
        // Use the image optimization library to create multiple size variants
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/optimized/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
            echo "<p class='info'>Created optimized uploads directory</p>";
            flushOutput();
        }
        
        echo "<p class='info'>Creating multiple size variants using image optimization library</p>";
        flushOutput();
        
        // Create image variants
        $variants = createImageVariants($preOptimizedImage, $uploadDir, [
            'convert_format' => 'jpg',
            'include_original' => true
        ]);
        
        if ($variants) {
            echo "<p class='success'>Created multiple size variants successfully</p>";
            
            // Use the medium size as the primary image
            $destination = $variants['medium']['path'] ?? $variants['original']['path'];
            $absoluteUrl = $variants['medium']['url'] ?? $variants['original']['url'];
            $fileSize = $variants['medium']['size'] ?? $variants['original']['size'];
            
            // Get file info
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $destination);
            finfo_close($finfo);
            
            // Create alt text
            $altText = "Illustration for story: " . $title;
            
            echo "<p class='info'>Using optimized image: " . basename($destination) . "</p>";
            echo "<p class='info'>Size: " . round($fileSize / 1024) . " KB</p>";
            flushOutput();
        } else {
            // Fall back to copying the pre-optimized image if optimization fails
            echo "<p class='warning'>Image optimization failed, falling back to direct copy</p>";
            flushOutput();
            
            if (copy($preOptimizedImage, $destination)) {
                echo "<p class='success'>Copied pre-optimized image successfully</p>";
                
                // Set proper permissions
                chmod($destination, 0644);
                system("chmod -R 644 " . escapeshellarg($destination));
                system("chown -R www-data:www-data " . escapeshellarg($destination) . " 2>/dev/null");
                
                // Get file info
                $fileSize = filesize($destination);
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $destination);
                finfo_close($finfo);
                
                // Create alt text
                $altText = "Illustration for story: " . $title;
            }
        }
            
        // Add to media library with all image URLs
        try {
            if (isset($variants) && $variants) {
                $stmt = $db->prepare("INSERT INTO media (filename, file_path, thumbnail_url, small_url, medium_url, large_url, file_type, file_size, alt_text, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
                $stmt->execute([
                    $uniqueFilename,
                    $variants['medium']['url'] ?? $absoluteUrl,
                    $variants['thumbnail']['url'] ?? null,
                    $variants['small']['url'] ?? null,
                    $variants['medium']['url'] ?? null,
                    $variants['large']['url'] ?? null,
                    $mimeType,
                    $fileSize,
                    $altText
                ]);
                
                $mediaId = $db->lastInsertId();
                $coverUrl = $variants['medium']['url'] ?? $absoluteUrl;
                echo "<p class='success'>Added image with multiple size variants to media library (ID: $mediaId)</p>";
            } else {
                // Fall back to the old method if variants aren't available
                $stmt = $db->prepare("INSERT INTO media (filename, file_path, file_type, file_size, alt_text, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
                $stmt->execute([
                    $uniqueFilename,
                    $absoluteUrl,
                    $mimeType,
                    $fileSize,
                    $altText
                ]);
                
                $mediaId = $db->lastInsertId();
                $coverUrl = $absoluteUrl;
                echo "<p class='success'>Added pre-optimized image to media library (ID: $mediaId)</p>";
            }
            flushOutput();
            
            return [
                'cover_url' => $coverUrl,
                'media_id' => $mediaId
            ];
        } catch (Exception $e) {
            echo "<p class='error'>Failed to add pre-optimized image to media library: " . $e->getMessage() . "</p>";
            flushOutput();
            return null;
        }
    } else {
        echo "<p class='error'>Failed to copy pre-optimized image</p>";
        flushOutput();
    }
    
    // If we get here, either we're not using a pre-optimized image or it failed
    // Check if images directory exists
    if (!is_dir($imagesDir)) {
        echo "<p class='warning'>Images directory not found: $imagesDir</p>";
        flushOutput();
        
        // Try to find images in alternative locations
        $found = false;
        $alternativeLocations = [
            dirname($storyDir), // Parent directory
            $storyDir, // Story directory itself
            $_SERVER['DOCUMENT_ROOT'] . '/../_wp migration/uploads', // Main uploads directory
            $_SERVER['DOCUMENT_ROOT'] . '/../_wp migration/uploads/2023', // 2023 uploads
            $_SERVER['DOCUMENT_ROOT'] . '/../_wp migration/uploads/2023/07', // July 2023 uploads
            $_SERVER['DOCUMENT_ROOT'] . '/../_wp migration/uploads/2024', // 2024 uploads
            $_SERVER['DOCUMENT_ROOT'] . '/../_wp migration/uploads/2024/10', // October 2024 uploads
        ];
        
        foreach ($alternativeLocations as $location) {
            echo "<p class='info'>Looking for images in alternative location: $location</p>";
            // First try direct files in this directory
            $possibleImages = glob("$location/*.{jpg,jpeg,png,gif}", GLOB_BRACE);
            
            if (!empty($possibleImages)) {
                echo "<p class='success'>Found images in alternative location: $location</p>";
                $images = $possibleImages;
                $found = true;
                break;
            }
        }
        
        // If still not found, try a recursive search in the uploads directory
        if (!$found) {
            $uploadsDir = $_SERVER['DOCUMENT_ROOT'] . '/../_wp migration/uploads';
            echo "<p class='info'>Performing recursive search in uploads directory</p>";
            
            // Use RecursiveDirectoryIterator to search all subdirectories
            try {
                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($uploadsDir, RecursiveDirectoryIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::LEAVES_ONLY
                );
                
                $imageFiles = [];
                foreach ($iterator as $file) {
                    if ($file->isFile()) {
                        $extension = strtolower(pathinfo($file->getPathname(), PATHINFO_EXTENSION));
                        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
                            $imageFiles[] = $file->getPathname();
                        }
                    }
                }
                
                if (!empty($imageFiles)) {
                    echo "<p class='success'>Found " . count($imageFiles) . " images in recursive search</p>";
                    // Sort by file size (prefer smaller files)
                    usort($imageFiles, function($a, $b) {
                        return filesize($a) - filesize($b);
                    });
                    
                    // Use the first 10 images (or fewer if less than 10 found)
                    $images = array_slice($imageFiles, 0, 10);
                    $found = true;
                    
                    // Log the images found
                    echo "<p class='info'>Using images:</p>";
                    echo "<ul>";
                    foreach ($images as $img) {
                        echo "<li>" . basename($img) . " (" . round(filesize($img) / 1024) . " KB)</li>";
                    }
                    echo "</ul>";
                }
            } catch (Exception $e) {
                echo "<p class='warning'>Error during recursive search: " . $e->getMessage() . "</p>";
            }
        }
        
        if (!$found) {
            echo "<p class='error'>No images found in any location</p>";
            return [
                'cover_url' => $coverUrl,
                'media_id' => $mediaId
            ];
        }
    } else {
        $images = glob("$imagesDir/*.*");
        if (empty($images)) {
            echo "<p class='warning'>No images found in directory: $imagesDir</p>";
            flushOutput();
            return [
                'cover_url' => $coverUrl,
                'media_id' => $mediaId
            ];
        }
    }
    
    // Process the first image found
    $coverImage = basename($images[0]);
    
    // Use absolute server path for uploads
    $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
        echo "<p class='info'>Created uploads directory</p>";
        flushOutput();
    }
    
    // Check image size before processing
    $imageSize = filesize($images[0]);
    echo "<p class='info'>Original image size: " . round($imageSize / 1024) . " KB</p>";
    flushOutput();
    
    // Check if we should look for a smaller version of this image
    $smallerImage = null;
    $originalBasename = pathinfo($coverImage, PATHINFO_FILENAME);
    
    // Look for smaller versions in the uploads directory
    $uploadsDir = $_SERVER['DOCUMENT_ROOT'] . '/../_wp migration/uploads';
    if (is_dir($uploadsDir)) {
        // Try to find a smaller version (300x300 is a good size)
        $smallerVersions = glob("$uploadsDir/$originalBasename*300x300*.png");
        
        if (!empty($smallerVersions)) {
            $smallerImage = $smallerVersions[0];
            echo "<p class='success'>Found smaller version of image: $smallerImage</p>";
            flushOutput();
            
            // Use the smaller image instead
            $images[0] = $smallerImage;
            $coverImage = basename($smallerImage);
            $imageSize = filesize($smallerImage);
            echo "<p class='info'>Using smaller image: " . round($imageSize / 1024) . " KB</p>";
            flushOutput();
        }
    }
    
    // Generate unique filename to avoid collisions
    $uniqueFilename = uniqid() . '-' . $coverImage;
    $destination = $uploadDir . $uniqueFilename;
    
    // Create absolute URL (always use HTTPS for admin panel compatibility)
    $relativeUrl = '/uploads/' . $uniqueFilename;
    $absoluteUrl = 'https://' . $_SERVER['HTTP_HOST'] . $relativeUrl;
    
    echo "<p class='info'>Absolute URL: $absoluteUrl</p>";
    flushOutput();
            
    // Try to use ImageMagick first (much better compression than GD)
    $optimized = false;
    if (extension_loaded('imagick')) {
        try {
            echo "<p class='info'>Using ImageMagick for better compression</p>";
            flushOutput();
            
            $imagick = new Imagick($images[0]);
            
            // Strip metadata to reduce size
            $imagick->stripImage();
            
            // Always resize to 300px width for better performance
            $width = $imagick->getImageWidth();
            $height = $imagick->getImageHeight();
            
            // Force resize to 300px width regardless of original size
            $newWidth = 300;
            $newHeight = ($height / $width) * 300;
            $imagick->resizeImage($newWidth, $newHeight, Imagick::FILTER_LANCZOS, 1);
            echo "<p class='info'>Resized to {$newWidth}x{$newHeight}</p>";
            flushOutput();
            
            // Set quality based on image format - use extremely aggressive compression
            if ($imagick->getImageFormat() == 'JPEG') {
                $imagick->setImageCompressionQuality(50); // Very aggressive compression
                $imagick->setInterlaceScheme(Imagick::INTERLACE_PLANE);
                echo "<p class='info'>Applied JPEG compression (50% quality)</p>";
            } else if ($imagick->getImageFormat() == 'PNG') {
                // For PNG, optimize compression
                $imagick->setImageCompressionQuality(95);
                $imagick->setOption('png:compression-level', 9);
                $imagick->setOption('png:compression-strategy', 1);
                $imagick->setOption('png:exclude-chunk', 'all');
                echo "<p class='info'>Applied maximum PNG compression</p>";
            }
            
            // Strip all metadata to reduce size (do this again to be sure)
            $imagick->stripImage();
            
            // Write the optimized image
            $imagick->writeImage($destination);
            $imagick->destroy();
            
            $optimized = true;
            echo "<p class='success'>Image optimized with ImageMagick</p>";
            flushOutput();
        } catch (Exception $e) {
            echo "<p class='warning'>ImageMagick optimization failed: " . $e->getMessage() . "</p>";
            echo "<p class='info'>Falling back to GD</p>";
            flushOutput();
        }
    }
    
    // Fall back to GD if ImageMagick failed or isn't available
    if (!$optimized && extension_loaded('gd')) {
        try {
            echo "<p class='info'>Using GD for image optimization</p>";
            flushOutput();
            
            // Get image info
            list($width, $height, $type) = getimagesize($images[0]);
            
            // Only process if it's a supported image type
            if ($type === IMAGETYPE_JPEG || $type === IMAGETYPE_PNG) {
                // Create image resource
                if ($type === IMAGETYPE_JPEG) {
                    $source = imagecreatefromjpeg($images[0]);
                } else {
                    $source = imagecreatefrompng($images[0]);
                }
                
                if ($source) {
                    // Calculate new dimensions (max 300px width for better performance)
                    $maxWidth = 300; // Reduced from 500px to 300px
                    $newWidth = $width;
                    $newHeight = $height;
                    
                    // Always resize to 300px width regardless of original size
                    if ($width > 0) { // Avoid division by zero
                        $newWidth = $maxWidth;
                        $newHeight = ($height / $width) * $maxWidth;
                    }
                    
                    // Create resized image with proper alpha channel support for PNGs
                    $resized = imagecreatetruecolor($newWidth, $newHeight);
                    
                    // Preserve transparency for PNG images
                    if ($type === IMAGETYPE_PNG) {
                        imagealphablending($resized, false);
                        imagesavealpha($resized, true);
                        $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
                        imagefilledrectangle($resized, 0, 0, $newWidth, $newHeight, $transparent);
                    }
                    
                    imagecopyresampled($resized, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                    
                    // Save resized image with higher compression
                    if ($type === IMAGETYPE_JPEG) {
                        // Use 50% quality for better compression (reduced from 65%)
                        imagejpeg($resized, $destination, 50);
                    } else {
                        // For PNG, use maximum compression (9)
                        imagepng($resized, $destination, 9);
                    }
                    
                    imagedestroy($resized);
                    imagedestroy($source);
                    
                    echo "<p class='success'>Image optimized and resized to $newWidth x $newHeight</p>";
                    flushOutput();
                }
            } else {
                // Just copy the file if it's not a supported type
                copy($images[0], $destination);
                echo "<p class='info'>Unsupported image type, copied without optimization</p>";
                flushOutput();
            }
        } catch (Exception $e) {
            // If optimization fails, just copy the file
            copy($images[0], $destination);
            echo "<p class='warning'>Image optimization failed: " . $e->getMessage() . "</p>";
            echo "<p class='info'>Copied original image instead</p>";
            flushOutput();
        }
    } else {
        // If GD is not available, just copy the file
        copy($images[0], $destination);
        echo "<p class='info'>GD library not available, copied without optimization</p>";
        flushOutput();
    }
    
    // Set proper permissions - ensure web server can read the file
    chmod($destination, 0644);
    
    // Make sure everyone can read the file
    system("chmod -R 644 " . escapeshellarg($destination));
    system("chown -R www-data:www-data " . escapeshellarg($destination) . " 2>/dev/null");
    
    // Verify the file exists and is readable
    if (file_exists($destination) && is_readable($destination)) {
        echo "<p class='success'>Image saved to: $destination</p>";
        echo "<p class='info'>Public URL: $absoluteUrl</p>";
        
        // Check final file size
        $finalSize = filesize($destination);
        echo "<p class='info'>Final image size: " . round($finalSize / 1024) . " KB</p>";
        
        // Always use absolute URL for cover
        $coverUrl = $absoluteUrl;
    } else {
        echo "<p class='warning'>File saved but may not be readable: $destination</p>";
        echo "<p class='info'>Setting permissions again...</p>";
        chmod($destination, 0644);
        
        // Always use absolute URL for cover
        $coverUrl = $absoluteUrl;
    }
    flushOutput();
    
    // Get proper MIME type
    $fileSize = filesize($destination);
    
    // Try to use fileinfo extension if available
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $destination);
        finfo_close($finfo);
    } else {
        // Fallback: determine MIME type based on extension
        $extension = strtolower(pathinfo($destination, PATHINFO_EXTENSION));
        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            'pdf' => 'application/pdf'
        ];
        $mimeType = $mimeTypes[$extension] ?? 'application/octet-stream';
        echo "<p class='info'>Using fallback MIME type detection: $mimeType</p>";
        flushOutput();
    }
                
    // Create alt text
    $altText = "Illustration for story: " . $title;
    
    // Verify the file exists and is accessible via web
    $webAccessible = false;
    $ch = curl_init($absoluteUrl);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_exec($ch);
    $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($responseCode == 200) {
        $webAccessible = true;
        echo "<p class='success'>Image is accessible via web: $absoluteUrl</p>";
    } else {
        echo "<p class='warning'>Image may not be accessible via web (HTTP $responseCode): $absoluteUrl</p>";
        
        // Try to fix permissions again
        chmod($destination, 0644);
        system("chmod -R 644 " . escapeshellarg($destination));
        system("chown -R www-data:www-data " . escapeshellarg($destination) . " 2>/dev/null");
        
        // Create a symlink in a web-accessible directory if needed
        $publicDir = $_SERVER['DOCUMENT_ROOT'] . '/public/uploads/';
        if (!is_dir($publicDir)) {
            mkdir($publicDir, 0755, true);
        }
        
        $publicPath = $publicDir . $uniqueFilename;
        if (!file_exists($publicPath)) {
            copy($destination, $publicPath);
            chmod($publicPath, 0644);
        }
        
        // Update the URL to use the public directory
        $relativeUrl = '/public/uploads/' . $uniqueFilename;
        $absoluteUrl = 'https://' . $_SERVER['HTTP_HOST'] . $relativeUrl;
        echo "<p class='info'>Created public copy at: $absoluteUrl</p>";
    }
    
    // Add to media library
    try {
        // Always store absolute URLs in the database for admin panel compatibility
        $stmt = $db->prepare("INSERT INTO media (filename, file_path, file_type, file_size, alt_text, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
        $stmt->execute([
            $uniqueFilename,
            $absoluteUrl, // Use absolute URL with protocol
            $mimeType,
            $fileSize,
            $altText
        ]);
        $mediaId = $db->lastInsertId();
        echo "<p class='success'>Added to media library (ID: $mediaId)</p>";
        flushOutput();
        
        // Verify the media entry
        $verifyStmt = $db->prepare("SELECT * FROM media WHERE id = ?");
        $verifyStmt->execute([$mediaId]);
        $mediaEntry = $verifyStmt->fetch();
        
        if ($mediaEntry) {
            echo "<p class='info'>Verified media entry: " . json_encode($mediaEntry) . "</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>Failed to add to media library: " . $e->getMessage() . "</p>";
        flushOutput();
    }
    
    return [
        'cover_url' => $coverUrl,
        'media_id' => $mediaId
    ];
}

// Function to extract tags from front-matter and content
function extractTags($frontMatter, $markdownContent) {
    $tags = ['children\'s story']; // Always include this tag
    
    // Parse front-matter for tags or categories
    $lines = explode("\n", $frontMatter);
    foreach ($lines as $line) {
        if (preg_match('/^(tags|categories):\s*(.*)$/i', $line, $matches)) {
            $tagList = $matches[2];
            $frontMatterTags = explode(',', $tagList);
            foreach ($frontMatterTags as $tag) {
                $tag = trim($tag, " \t\n\r\0\x0B\"'[]");
                if (!empty($tag) && !in_array($tag, $tags)) {
                    $tags[] = $tag;
                }
            }
        }
    }
    
    // Add content-based tags if we don't have enough
    if (count($tags) < 3) {
        $keywords = [
            'adventure', 'animals', 'fantasy', 'friendship', 'magic', 
            'school', 'family', 'nature', 'space', 'dinosaurs', 
            'robots', 'monsters', 'fairy tale', 'mystery'
        ];
        
        $contentLower = strtolower($markdownContent);
        foreach ($keywords as $keyword) {
            if (strpos($contentLower, $keyword) !== false && !in_array($keyword, $tags)) {
                $tags[] = $keyword;
                if (count($tags) >= 5) break; // Limit to 5 tags total
            }
        }
    }
    
    return $tags;
}
// Function to create or update tags and link to story
function processStoryTags($db, $storyId, $tags) {
    foreach ($tags as $tagName) {
        try {
            $tagSlug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $tagName));
            
            // Check if tag exists (case-insensitive)
            $tagStmt = $db->prepare("SELECT id FROM tags WHERE LOWER(slug) = LOWER(?)");
            $tagStmt->execute([$tagSlug]);
            $tag = $tagStmt->fetch();
            
            if (!$tag) {
                // Create tag
                $createTagStmt = $db->prepare("INSERT INTO tags (name, slug) VALUES (?, ?)");
                $createTagStmt->execute([$tagName, $tagSlug]);
                $tagId = $db->lastInsertId();
                echo "<p class='success'>Created tag: $tagName</p>";
                flushOutput();
            } else {
                $tagId = $tag['id'];
            }
            
            // Associate tag with story if not already associated
            $checkTagStmt = $db->prepare("SELECT * FROM story_tags WHERE story_id = ? AND tag_id = ?");
            $checkTagStmt->execute([$storyId, $tagId]);
            if (!$checkTagStmt->fetch()) {
                $linkTagStmt = $db->prepare("INSERT INTO story_tags (story_id, tag_id) VALUES (?, ?)");
                $linkTagStmt->execute([$storyId, $tagId]);
                echo "<p class='success'>Added tag '$tagName' to story</p>";
                flushOutput();
            }
        } catch (Exception $e) {
            echo "<p class='error'>Error processing tag '$tagName': " . $e->getMessage() . "</p>";
            flushOutput();
            // Continue with other tags
        }
    }
}

// Function to check if story exists by title or slug (case-insensitive)
function findExistingStory($db, $title, $slug) {
    // First try by title (more reliable)
    $titleStmt = $db->prepare("SELECT id, slug FROM stories WHERE LOWER(title) = LOWER(?) OR title LIKE ?");
    $titleStmt->execute([trim($title), "%" . substr(trim($title), 0, 30) . "%"]);
    $existingStory = $titleStmt->fetch();
    
    if (!$existingStory) {
        // Fallback to slug check
        $slugStmt = $db->prepare("SELECT id, slug FROM stories WHERE LOWER(slug) = LOWER(?)");
        $slugStmt->execute([$slug]);
        $existingStory = $slugStmt->fetch();
    }
    
    return $existingStory;
}

// Function to generate a unique slug
function generateUniqueSlug($db, $title) {
    $baseSlug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $title));
    $baseSlug = trim($baseSlug, '-');
    $slug = $baseSlug;
    $counter = 1;
    
    $slugStmt = $db->prepare("SELECT id FROM stories WHERE LOWER(slug) = LOWER(?)");
    $slugStmt->execute([$slug]);
    
    while ($slugStmt->fetch()) {
        $slug = $baseSlug . '-' . $counter;
        $counter++;
        $slugStmt->execute([$slug]);
    }
    
    return $slug;
}

// Function to determine age group based on author age
function getAgeGroup($age) {
    if (!$age) return '7-12'; // Default
    $age = (int)$age;
    if ($age <= 6) return '0-6';
    if ($age <= 9) return '7-9';
    if ($age <= 12) return '10-12';
    return '13+';
}

// Function to estimate reading time
function getReadingTime($content) {
    $wordCount = str_word_count(strip_tags($content));
    $minutes = max(1, ceil($wordCount / 200));
    return "$minutes minute" . ($minutes !== 1 ? 's' : '');
}
// Function to process a single story with transaction
function processStory($db, $storyDir) {
    $mdFile = "$storyDir/index.md";
    
    if (!file_exists($mdFile)) {
        echo "<p class='error'>Markdown file not found: $mdFile</p>";
        flushOutput();
        return false;
    }
    
    // Begin transaction for this story
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
        
        $title = $data['title'] ?? basename($storyDir);
        echo "<h3>Importing: $title</h3>";
        flushOutput();
        
        // Extract author info
        $authorInfo = extractAuthorInfo($title);
        $authorId = getOrCreateAuthor($db, $authorInfo);
        
        // Process cover image
        $mediaData = handleMediaUpload($db, $storyDir, $title);
        $coverUrl = $mediaData['cover_url'];
        
        // Extract clean excerpt
        $excerpt = extractExcerpt($title, $markdownContent);
        echo "<p class='info'>Excerpt: " . htmlspecialchars(substr($excerpt, 0, 100)) . "...</p>";
        flushOutput();
        
        // Generate slug
        $slug = generateUniqueSlug($db, $title);
        
        // Calculate reading time
        $readingTime = getReadingTime($markdownContent);
        
        // Determine age group
        $ageGroup = getAgeGroup($authorInfo['age']);
        
        // Extract tags
        $tags = extractTags($frontMatter, $markdownContent);
        echo "<p class='info'>Tags: " . implode(', ', $tags) . "</p>";
        flushOutput();
        
        // Check if story exists
        $existingStory = findExistingStory($db, $title, $slug);
        
        if ($existingStory) {
            // No need to format cover URL as it's already absolute
            echo "<p class='info'>Using cover URL: $coverUrl</p>";
            flushOutput();
            
            // Update existing story
            $stmt = $db->prepare("
                UPDATE stories SET
                    content = ?,
                    excerpt = ?,
                    cover_url = ?,
                    estimated_reading_time = ?,
                    age_group = ?,
                    source_type = 'child',
                    allow_reviews = 0
                WHERE id = ?
            ");
            
            $stmt->execute([
                $markdownContent,
                $excerpt,
                $coverUrl, // Already absolute URL
                $readingTime,
                $ageGroup,
                $existingStory['id']
            ]);
            
            echo "<p class='success'>Updated story: $title (ID: {$existingStory['id']})</p>";
            flushOutput();
            
            // Make sure author is associated
            if ($authorId) {
                $checkStmt = $db->prepare("SELECT * FROM story_authors WHERE story_id = ? AND author_id = ?");
                $checkStmt->execute([$existingStory['id'], $authorId]);
                if (!$checkStmt->fetch()) {
                    $linkStmt = $db->prepare("INSERT INTO story_authors (story_id, author_id) VALUES (?, ?)");
                    $linkStmt->execute([$existingStory['id'], $authorId]);
                    echo "<p class='success'>Associated story with author ID: $authorId</p>";
                    flushOutput();
                }
            }
            
            // Process tags
            processStoryTags($db, $existingStory['id'], $tags);
            
            $storyId = $existingStory['id'];
        } else {
            // Insert new story
            $stmt = $db->prepare("
                INSERT INTO stories (
                    title, slug, content, excerpt, cover_url,
                    is_published, source_type, allow_reviews,
                    estimated_reading_time, age_group
                ) VALUES (?, ?, ?, ?, ?, 1, 'child', 0, ?, ?)
            ");
            
            $stmt->execute([
                $title,
                $slug,
                $markdownContent,
                $excerpt,
                $coverUrl, // Already absolute URL
                $readingTime,
                $ageGroup
            ]);
            
            $storyId = $db->lastInsertId();
            echo "<p class='success'>Created story with ID: $storyId</p>";
            flushOutput();
            
            // Associate with author
            if ($authorId) {
                $stmt = $db->prepare("INSERT INTO story_authors (story_id, author_id) VALUES (?, ?)");
                $stmt->execute([$storyId, $authorId]);
                echo "<p class='success'>Associated with author</p>";
                flushOutput();
            }
            
            // Process tags
            processStoryTags($db, $storyId, $tags);
        }
        
        // Commit the transaction
        $db->commit();
        echo "<p class='success'>Story transaction committed successfully</p>";
        flushOutput();
        
        return [
            'success' => true,
            'action' => $existingStory ? 'updated' : 'created',
            'id' => $storyId
        ];
    } catch (Exception $e) {
        // Rollback transaction on error
        if ($db->inTransaction()) {
            $db->rollBack();
            echo "<p class='error'>Transaction rolled back</p>";
            flushOutput();
        }
        echo "<p class='error'>Error processing story: " . $e->getMessage() . "</p>";
        flushOutput();
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}
// Function to verify import results
function verifyImportResults($db, $stats) {
    echo "<h2>Verifying Import Results</h2>";
    flushOutput();
    
    // Count child stories
    $stmt = $db->query("SELECT COUNT(*) FROM stories WHERE source_type = 'child'");
    $storyCount = $stmt->fetchColumn();
    echo "<p>Total child stories in database: $storyCount</p>";
    flushOutput();
    
    // Count child authors
    $stmt = $db->query("SELECT COUNT(*) FROM authors WHERE author_type = 'child'");
    $authorCount = $stmt->fetchColumn();
    echo "<p>Total child authors in database: $authorCount</p>";
    flushOutput();
    
    // Count media files
    $stmt = $db->query("SELECT COUNT(*) FROM media");
    $mediaCount = $stmt->fetchColumn();
    echo "<p>Total media files in database: $mediaCount</p>";
    flushOutput();
    
    // Count story-author links
    $stmt = $db->query("
        SELECT COUNT(*) FROM story_authors sa
        JOIN stories s ON sa.story_id = s.id
        WHERE s.source_type = 'child'
    ");
    $storyAuthorCount = $stmt->fetchColumn();
    echo "<p>Total story-author links for child stories: $storyAuthorCount</p>";
    flushOutput();
    
    // Count story-tag links
    $stmt = $db->query("
        SELECT COUNT(*) FROM story_tags st
        JOIN stories s ON st.story_id = s.id
        WHERE s.source_type = 'child'
    ");
    $storyTagCount = $stmt->fetchColumn();
    echo "<p>Total story-tag links for child stories: $storyTagCount</p>";
    flushOutput();
    
    // Verify story settings
    $stmt = $db->query("
        SELECT COUNT(*) FROM stories 
        WHERE source_type = 'child' AND allow_reviews = 0
    ");
    $correctSettingsCount = $stmt->fetchColumn();
    echo "<p>Stories with correct settings (source_type = 'child', allow_reviews = 0): $correctSettingsCount</p>";
    flushOutput();
    
    // Check for any stories with missing authors
    $stmt = $db->query("
        SELECT COUNT(*) FROM stories s
        LEFT JOIN story_authors sa ON s.id = sa.story_id
        WHERE s.source_type = 'child' AND sa.author_id IS NULL
    ");
    $missingAuthorCount = $stmt->fetchColumn();
    if ($missingAuthorCount > 0) {
        echo "<p class='warning'>Found $missingAuthorCount child stories without authors</p>";
    } else {
        echo "<p class='success'>All child stories have authors</p>";
    }
    flushOutput();
    
    // Check for any stories with missing cover images
    $stmt = $db->query("
        SELECT COUNT(*) FROM stories
        WHERE source_type = 'child' AND (cover_url IS NULL OR cover_url = '')
    ");
    $missingCoverCount = $stmt->fetchColumn();
    if ($missingCoverCount > 0) {
        echo "<p class='warning'>Found $missingCoverCount child stories without cover images</p>";
    } else {
        echo "<p class='success'>All child stories have cover images</p>";
    }
    flushOutput();
    
    return [
        'story_count' => $storyCount,
        'author_count' => $authorCount,
        'media_count' => $mediaCount,
        'story_author_count' => $storyAuthorCount,
        'story_tag_count' => $storyTagCount
    ];
}

// Main HTML output
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>WordPress Import Tool</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }
        h1, h2, h3 { color: #4a6ee0; }
        .log { background: #f5f5f5; padding: 15px; border-radius: 5px; max-height: 600px; overflow-y: auto; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        .info { color: blue; }
        .button-container { margin: 20px 0; }
        .button { 
            display: inline-block; 
            padding: 15px 25px; 
            background: #4CAF50; 
            color: white; 
            border-radius: 5px; 
            text-decoration: none;
            margin-right: 10px;
            cursor: pointer;
            border: none;
            font-size: 16px;
            font-weight: bold;
        }
        .button.danger { background: #e04a4a; }
    </style>
</head>
<body>
    <h1>WordPress Import Tool</h1>
    
    <div class="button-container">
        <form method="post">
            <button type="submit" name="action" value="import" class="button">Import All Content</button>
        </form>
        <a href="optimize_image.php" class="button" style="background: #e04a4a;">Optimize All Media Files</a>
    </div>
    
    <div class="log">
<?php
// Process the import action
if (isset($_POST['action']) && $_POST['action'] === 'import') {
    // Connect to database
    $db = connectToDatabase();
    if (!$db) {
        echo "<p class='error'>Failed to connect to database</p>";
        exit;
    }
    
    // Clean child story data first
    echo "<h2>Cleaning Existing Data</h2>";
    flushOutput();
    
    $cleanResult = cleanChildStoryData($db);
    if (!$cleanResult) {
        echo "<p class='error'>Failed to clean existing data. Import aborted.</p>";
        echo "</div></body></html>";
        exit;
    }
    
    // Process WordPress export directory
    $wpDir = __DIR__ . '/../_wp migration/wp-md/custom/childrens-story';
    
    // Fallback paths if the primary directory doesn't exist
    $fallbackPaths = [
        __DIR__ . '/../_wp migration/wp-md/custom/childrens-story',
        __DIR__ . '/../_wp migration/wp-md/custom/childrens-stories',
        __DIR__ . '/../_wp migration/wp-md/custom/children-story',
        __DIR__ . '/../_wp migration/wp-md/custom/children-stories',
        __DIR__ . '/../_wp migration/wp-md/pages/childrens-stories',
        __DIR__ . '/../_wp-migration/wp-md/custom/childrens-story',
        __DIR__ . '/../_wp-migration/wp-md/custom/childrens-stories'
    ];
    
    if (!is_dir($wpDir)) {
        foreach ($fallbackPaths as $path) {
            if (is_dir($path)) {
                $wpDir = $path;
                echo "<p class='info'>Using alternate WordPress export directory: $wpDir</p>";
                flushOutput();
                break;
            }
        }
    }
    
    if (!is_dir($wpDir)) {
        echo "<p class='error'>WordPress export directory not found. Tried:</p>";
        echo "<ul>";
        foreach ($fallbackPaths as $path) {
            echo "<li>$path</li>";
        }
        echo "</ul>";
        echo "<p>Please ensure the WordPress export directory exists and contains markdown files.</p>";
        echo "</div></body></html>";
        exit;
    }
    
    echo "<h2>Importing Children's Stories</h2>";
    echo "<p class='info'>Import source: $wpDir</p>";
    flushOutput();
    
    // Get all story directories
    $storyDirs = [];
    try {
        $storyDirs = array_filter(glob("$wpDir/*"), 'is_dir');
        echo "<p>Found " . count($storyDirs) . " potential story directories</p>";
        flushOutput();
        
        // If no directories found, try recursive search
        if (count($storyDirs) === 0) {
            echo "<p class='info'>No story directories found at top level, searching recursively...</p>";
            flushOutput();
            
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($wpDir, RecursiveDirectoryIterator::SKIP_DOTS)
            );
            
            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getFilename() === 'index.md') {
                    $storyDirs[] = dirname($file->getPathname());
                }
            }
            
            echo "<p>Found " . count($storyDirs) . " story directories through recursive search</p>";
            flushOutput();
        }
    } catch (Exception $e) {
        echo "<p class='error'>Error scanning directories: " . $e->getMessage() . "</p>";
        flushOutput();
    }
    
    if (count($storyDirs) === 0) {
        echo "<p class='error'>No story directories found. Import aborted.</p>";
        echo "</div></body></html>";
        exit;
    }
    
    // Stats
    $stats = [
        'created' => 0,
        'updated' => 0,
        'skipped' => 0,
        'errors' => 0
    ];
    
    // Process each story
    foreach ($storyDirs as $storyDir) {
        try {
            $result = processStory($db, $storyDir);
            
            if ($result && $result['success']) {
                $stats[$result['action']]++;
            } else {
                $stats['errors']++;
            }
        } catch (Exception $e) {
            echo "<p class='error'>Unexpected error processing story directory '$storyDir': " . $e->getMessage() . "</p>";
            flushOutput();
            $stats['errors']++;
            // Continue with next story
            continue;
        }
        
        // Add a small delay to prevent server overload
        usleep(100000); // 0.1 second
    }
    
    // Verify import results
    $verificationResults = verifyImportResults($db, $stats);
    
    // Display summary
    echo "<h2>Import Complete!</h2>";
    echo "<p class='success'>Summary:</p>";
    echo "<ul>";
    echo "<li>Created: {$stats['created']} stories</li>";
    echo "<li>Updated: {$stats['updated']} stories</li>";
    echo "<li>Skipped: {$stats['skipped']} stories</li>";
    echo "<li>Errors: {$stats['errors']} stories</li>";
    echo "</ul>";
    
    echo "<p>Now check the <a href='/admin/stories'>Stories Admin</a> to verify the imported content.</p>";
    flushOutput();
} else {
    // Display initial instructions
    echo "<p class='info'>Click the 'Import All Content' button to start the import process.</p>";
    echo "<p>This tool will:</p>";
    echo "<ol>";
    echo "<li>Clean all existing child story data (stories, authors, tags, media)</li>";
    echo "<li>Import all stories from the WordPress export directory</li>";
    echo "<li>Create or update authors, tags, and media files</li>";
    echo "<li>Verify the import results</li>";
    echo "</ol>";
    echo "<p>The import process may take several minutes to complete.</p>";
}
?>
    </div>
</body>
</html>