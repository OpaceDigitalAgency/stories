<?php
/**
 * Goodreads Validation Functions
 *
 * This file contains functions for fetching and validating book data from Goodreads.
 */

/**
 * Fetch data from Goodreads
 *
 * @param string $isbn The ISBN to search for
 * @param string $title The book title
 * @param string $author The book author
 * @return array|null Book data or null if not found
 */
function fetchGoodreadsDataNew($isbn, $title, $author) {
    try {
        // Start timer for performance tracking
        $startTime = microtime(true);

        // Log that we're starting Goodreads fetch
        error_log("Starting Goodreads data fetch for ISBN: $isbn");

        // Try to build a direct book URL if possible
        $searchUrl = "";
        if (!empty($title) && !empty($author)) {
            // Format the URL with title and author for better results
            $titleSlug = preg_replace('/[^a-z0-9]+/i', '_', strtolower($title));
            $titleSlug = trim($titleSlug, '_');
            $searchUrl = "https://www.goodreads.com/search?q=" . urlencode($title . " " . $author);
        } else {
            // Fallback to search URL with ISBN
            $searchUrl = "https://www.goodreads.com/search?q=" . urlencode($isbn);
        }

        // First try using the Python script (most reliable method)
        $pythonScript = __DIR__ . '/../../../../../goodreads_book_info.py';
        if (file_exists($pythonScript)) {
            // Execute Python script with a longer timeout
            $command = "python3 " . escapeshellarg($pythonScript) . " " . escapeshellarg($searchUrl) . " 2>&1";
            $output = [];
            $returnCode = 0;

            // Log that we're executing the Python script
            error_log("Executing Python script: $command");

            // Execute with a longer timeout
            exec($command, $output, $returnCode);

            // Check if Python script executed successfully
            if ($returnCode === 0) {
                // Look for JSON output in the Python script output
                $jsonData = null;
                $jsonFile = null;

                // First check if the script created a JSON file
                foreach ($output as $line) {
                    if (strpos($line, 'Saved book information to ') !== false) {
                        $jsonFile = trim(str_replace('Saved book information to ', '', $line));
                        if (file_exists($jsonFile)) {
                            $jsonData = json_decode(file_get_contents($jsonFile), true);
                            break;
                        }
                    }
                }

                // If no file found, look for JSON in the output
                if (!$jsonData) {
                    foreach ($output as $line) {
                        if (strpos($line, '{') === 0) {
                            $jsonData = json_decode($line, true);
                            if ($jsonData) {
                                break;
                            }
                        }
                    }
                }

                if ($jsonData) {
                    // Log the full JSON data for debugging
                    error_log("Goodreads JSON data: " . json_encode($jsonData));

                    // Extract book details from JSON data
                    $bookData = [
                        'title' => $jsonData['title'] ?? '',
                        'author' => strip_tags($jsonData['author'] ?? ''),
                        'publisher' => $jsonData['publisher'] ?? '',
                        'publication_date' => $jsonData['published_date'] ?? ($jsonData['publication_date'] ?? ''),
                        'page_count' => $jsonData['pages'] ?? ($jsonData['page_count'] ?? ''),
                        'isbn' => $jsonData['isbn'] ?? '',
                        'isbn13' => $jsonData['isbn13'] ?? '',
                        'language' => $jsonData['language'] ?? '',
                        'format' => $jsonData['format'] ?? '',
                        'series' => $jsonData['series'] ?? '',
                        'awards' => $jsonData['awards'] ?? '',
                        'characters' => $jsonData['characters'] ?? [],
                        'settings' => $jsonData['settings'] ?? [],
                        'preview_link' => $jsonData['preview_link'] ?? '',
                        'cover_url' => $jsonData['cover_image'] ?? '',
                        'rating' => $jsonData['rating'] ?? '',
                        'rating_count' => $jsonData['rating_count'] ?? '',
                        'review_count' => $jsonData['review_count'] ?? '',
                        'maturity_rating' => $jsonData['maturity_rating'] ?? ''
                    ];

                    // Log success for debugging
                    $endTime = microtime(true);
                    $totalTime = round($endTime - $startTime, 2);
                    error_log("Successfully extracted Goodreads data via Python script for ISBN: $isbn in {$totalTime}s");

                    return $bookData;
                } else {
                    error_log("Python script executed but no valid JSON data found. Output: " . implode("\n", $output));
                }
            } else {
                error_log("Failed to execute Python script: " . implode("\n", $output));
            }
        } else {
            error_log("Python script not found at: $pythonScript");
        }

        // Fallback to direct curl request if Python script didn't work
        return fetchGoodreadsDataWithCurlNew($isbn, $title, $author, $searchUrl);
    } catch (Exception $e) {
        error_log("Error fetching Goodreads data: " . $e->getMessage());
        return null;
    }
}

/**
 * Fetch Goodreads data using direct curl request
 *
 * @param string $isbn The ISBN to search for
 * @param string $title The book title
 * @param string $author The book author
 * @param string $searchUrl The search URL to use
 * @return array|null Book data or null if not found
 */
function fetchGoodreadsDataWithCurlNew($isbn, $title, $author, $searchUrl) {
    try {
        $startTime = microtime(true);

        error_log("Trying direct curl request as fallback for Goodreads data");

        $ch = curl_init($searchUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Longer timeout for fallback
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Follow redirects
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response && $httpCode == 200) {
            // Check if we found a book
            if (strpos($response, 'No results') === false &&
                (strpos($response, 'class="bookTitle"') !== false ||
                 strpos($response, 'class="bookCover"') !== false ||
                 strpos($response, 'data-testid="bookTitle"') !== false)) {

                // Extract book details using regex - updated for current Goodreads HTML structure
                preg_match('/<h1[^>]*>(.*?)<\/h1>/s', $response, $titleMatches);
                if (empty($titleMatches)) {
                    preg_match('/<a[^>]+data-testid="bookTitle"[^>]*>(.*?)<\/a>/s', $response, $titleMatches);
                }

                preg_match('/<span itemprop="author".*?>(.*?)<\/span>/s', $response, $authorMatches);
                if (empty($authorMatches)) {
                    preg_match('/<a[^>]+data-testid="authorLink"[^>]*>(.*?)<\/a>/s', $response, $authorMatches);
                }

                preg_match('/ISBN.*?([0-9X]{10})/i', $response, $isbnMatches);
                preg_match('/ISBN.*?([0-9]{13})/i', $response, $isbn13Matches);
                preg_match('/Published.*?(\d{4}).*?by\s+(.*?)(<|\n)/is', $response, $publisherMatches);
                preg_match('/(\d+)\s+pages/i', $response, $pageCountMatches);

                // Extract cover image - updated for current Goodreads HTML structure
                preg_match('/<img id="coverImage".*?src="(.*?)"/s', $response, $coverMatches);
                if (empty($coverMatches)) {
                    preg_match('/<img[^>]+data-testid="bookCover"[^>]+src="([^"]+)"/s', $response, $coverMatches);
                }

                // Extract description - updated for current Goodreads HTML structure
                preg_match('/<div id="description".*?<span[^>]*>(.*?)<\/span>/s', $response, $descMatches);
                if (empty($descMatches)) {
                    preg_match('/<div[^>]+data-testid="description"[^>]*>(.*?)<\/div>/s', $response, $descMatches);
                }

                // Extract series information
                preg_match('/<a[^>]+href="\/series\/[^"]+"[^>]*>(.*?)<\/a>/s', $response, $seriesMatches);

                // Extract rating information
                preg_match('/<span itemprop="ratingValue">(.*?)<\/span>/s', $response, $ratingMatches);
                preg_match('/<meta itemprop="ratingCount" content="(\d+)"/', $response, $ratingCountMatches);
                preg_match('/<meta itemprop="reviewCount" content="(\d+)"/', $response, $reviewCountMatches);

                $title = $titleMatches[1] ?? '';
                $author = $authorMatches[1] ?? '';
                $isbn10 = $isbnMatches[1] ?? '';
                $isbn13 = $isbn13Matches[1] ?? '';
                $publisher = $publisherMatches[2] ?? '';
                $pageCount = $pageCountMatches[1] ?? '';
                $coverUrl = $coverMatches[1] ?? '';
                $description = $descMatches[1] ?? '';
                $series = $seriesMatches[1] ?? '';
                $rating = $ratingMatches[1] ?? '';
                $ratingCount = $ratingCountMatches[1] ?? '';
                $reviewCount = $reviewCountMatches[1] ?? '';

                // Clean up extracted data
                $title = strip_tags($title);
                $author = strip_tags($author);
                $publisher = trim(strip_tags($publisher));
                $description = strip_tags($description);
                $series = strip_tags($series);

                // Extract genres/categories - updated for current Goodreads HTML structure
                $categories = [];
                if (preg_match_all('/<a class="actionLinkLite bookPageGenreLink"[^>]*>(.*?)<\/a>/s', $response, $genreMatches)) {
                    $categories = $genreMatches[1];
                }
                if (empty($categories) && preg_match_all('/<a[^>]+data-testid="genreLink"[^>]*>(.*?)<\/a>/s', $response, $genreMatches)) {
                    $categories = $genreMatches[1];
                }

                $bookData = [
                    'title' => $title,
                    'author' => $author,
                    'publisher' => $publisher,
                    'publication_date' => $publisherMatches[1] ?? '',
                    'page_count' => $pageCount,
                    'isbn' => $isbn10,
                    'isbn13' => $isbn13,
                    'language' => '',
                    'format' => '',
                    'series' => $series,
                    'awards' => '',
                    'characters' => '',
                    'settings' => '',
                    'preview_link' => '',
                    'cover_url' => $coverUrl,
                    'rating' => $rating,
                    'rating_count' => $ratingCount,
                    'review_count' => $reviewCount,
                    'maturity_rating' => ''
                ];

                // Log success for debugging
                $endTime = microtime(true);
                $totalTime = round($endTime - $startTime, 2);
                error_log("Successfully extracted Goodreads data via direct curl for ISBN: $isbn in {$totalTime}s");

                return $bookData;
            } else {
                error_log("No book found on Goodreads for ISBN: $isbn");
            }
        } else {
            error_log("Failed to fetch Goodreads data. HTTP Code: $httpCode");
        }

        return null;
    } catch (Exception $e) {
        error_log("Error in Goodreads curl fetch: " . $e->getMessage());
        return null;
    }
}
