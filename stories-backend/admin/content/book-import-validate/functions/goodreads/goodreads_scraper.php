<?php
/**
 * Goodreads Scraper
 * 
 * A PHP-based scraper for Goodreads book information.
 * This script uses PHP's DOM functions to extract book data from Goodreads pages.
 */

/**
 * Fetch a Goodreads page with proper headers
 * 
 * @param string $url The URL to fetch
 * @return string|false The HTML content or false on failure
 */
function fetchGoodreadsPage($url) {
    // Initialize cURL session
    $ch = curl_init();
    
    // Set cURL options
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/113.0.0.0 Safari/537.36');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept-Language: en-US,en;q=0.9',
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
        'Referer: https://www.goodreads.com/'
    ]);
    
    // Execute cURL session
    $html = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    // Check for errors
    if ($html === false || $httpCode >= 400) {
        error_log("Error fetching Goodreads page: " . curl_error($ch) . " (HTTP Code: $httpCode)");
        curl_close($ch);
        return false;
    }
    
    // Close cURL session
    curl_close($ch);
    
    return $html;
}

/**
 * Clean text by removing HTML tags, extra whitespace, etc.
 * 
 * @param string $text The text to clean
 * @return string The cleaned text
 */
function cleanText($text) {
    // Remove HTML tags
    $text = strip_tags($text);
    // Clean up whitespace
    $text = preg_replace('/\s+/', ' ', $text);
    // Trim whitespace
    $text = trim($text);
    
    return $text;
}

/**
 * Extract book information from Goodreads HTML
 * 
 * @param string $html The HTML content
 * @param string $url The source URL
 * @return array|null The extracted book information or null on failure
 */
function extractGoodreadsBookInfo($html, $url = '') {
    if (empty($html)) {
        error_log("No HTML content to analyze");
        return null;
    }
    
    // Initialize book info array
    $bookInfo = [
        'source_url' => $url
    ];
    
    // Create a new DOM Document
    $dom = new DOMDocument();
    
    // Suppress warnings for malformed HTML
    libxml_use_internal_errors(true);
    $dom->loadHTML($html);
    libxml_clear_errors();
    
    // Create a new XPath object
    $xpath = new DOMXPath($dom);
    
    // Extract title
    $titleNodes = $xpath->query('//h1[@data-testid="bookTitle"]');
    if ($titleNodes->length > 0) {
        $bookInfo['title'] = cleanText($titleNodes->item(0)->textContent);
    }
    
    // Extract author
    $authorNodes = $xpath->query('//a[@data-testid="authorLink"]');
    if ($authorNodes->length > 0) {
        $bookInfo['author'] = cleanText($authorNodes->item(0)->textContent);
    }
    
    // Extract rating
    $ratingNodes = $xpath->query('//div[@data-testid="ratingsSection"]//span[@data-testid="averageRating"]');
    if ($ratingNodes->length > 0) {
        $bookInfo['rating'] = cleanText($ratingNodes->item(0)->textContent);
    }
    
    // Extract description
    $descriptionNodes = $xpath->query('//div[@data-testid="description"]//div[@data-testid="contentContainer"]');
    if ($descriptionNodes->length > 0) {
        $bookInfo['description'] = cleanText($descriptionNodes->item(0)->textContent);
    }
    
    // Extract cover image
    $coverNodes = $xpath->query('//div[contains(@class, "BookPage__rightCover")]//img[@data-testid="coverImage"]');
    if ($coverNodes->length > 0) {
        $bookInfo['cover_image'] = $coverNodes->item(0)->getAttribute('src');
    }
    
    // Extract genres
    $genreNodes = $xpath->query('//div[@data-testid="genresList"]//a');
    if ($genreNodes->length > 0) {
        $genres = [];
        foreach ($genreNodes as $genreNode) {
            $genres[] = cleanText($genreNode->textContent);
        }
        $bookInfo['genres'] = $genres;
    }
    
    // Extract book details
    $detailItems = $xpath->query('//div[contains(@class, "BookDetails")]//div[contains(@class, "DescListItem")]');
    foreach ($detailItems as $item) {
        $labelNodes = $xpath->query('.//dt', $item);
        $valueNodes = $xpath->query('.//dd', $item);
        
        if ($labelNodes->length > 0 && $valueNodes->length > 0) {
            $label = strtolower(cleanText($labelNodes->item(0)->textContent));
            $value = cleanText($valueNodes->item(0)->textContent);
            
            // Process different types of details
            if (strpos($label, 'isbn') !== false) {
                // Extract ISBN numbers
                preg_match_all('/(\d{10}|\d{13})/', $value, $matches);
                if (!empty($matches[0])) {
                    foreach ($matches[0] as $isbn) {
                        if (strlen($isbn) === 13) {
                            $bookInfo['isbn13'] = $isbn;
                        } else {
                            $bookInfo['isbn'] = $isbn;
                        }
                    }
                }
            } elseif (strpos($label, 'format') !== false) {
                // Extract format and pages
                if (preg_match('/(\d+)\s*pages?,?\s*([^,]+)/', $value, $matches)) {
                    $bookInfo['pages'] = $matches[1];
                    $bookInfo['format'] = trim($matches[2]);
                }
            } elseif (strpos($label, 'published') !== false) {
                // Extract published date and publisher
                if (preg_match('/(.*?)\s+by\s+(.*?)(?:\s*$|\s*\()/', $value, $matches)) {
                    $bookInfo['published_date'] = trim($matches[1]);
                    $bookInfo['publisher'] = trim($matches[2]);
                }
            } elseif (strpos($label, 'language') !== false) {
                $bookInfo['language'] = $value;
            } elseif (strpos($label, 'asin') !== false) {
                $bookInfo['asin'] = $value;
            }
        }
    }
    
    // Check if we have the minimum required information
    if (empty($bookInfo['title']) && empty($bookInfo['author'])) {
        // We might be on a search results page
        return processSearchResults($dom, $xpath, $url);
    }
    
    return $bookInfo;
}

/**
 * Process search results page to find the best match
 * 
 * @param DOMDocument $dom The DOM document
 * @param DOMXPath $xpath The XPath object
 * @param string $url The source URL
 * @return array|null The book information or null if no match found
 */
function processSearchResults($dom, $xpath, $url) {
    // Extract search query from URL
    $searchQuery = '';
    if (strpos($url, '?q=') !== false) {
        $parts = explode('?q=', $url);
        if (isset($parts[1])) {
            $searchQuery = urldecode($parts[1]);
        }
    }
    
    // Find search results
    $results = $xpath->query('//div[@data-testid="searchResults"]/div');
    if ($results->length === 0) {
        // Try alternative selectors
        $results = $xpath->query('//div[contains(@class, "BookSearchResult")]');
    }
    
    if ($results->length === 0) {
        error_log("No search results found");
        return null;
    }
    
    error_log("Found " . $results->length . " search results");
    
    // Find the best match
    $bestMatch = null;
    $bestScore = 0;
    
    foreach ($results as $result) {
        $resultData = [];
        $score = 0;
        
        // Extract title
        $titleNodes = $xpath->query('.//a[@data-testid="bookTitle"]', $result);
        if ($titleNodes->length === 0) {
            $titleNodes = $xpath->query('.//a[contains(@href, "/book/show/")]', $result);
        }
        
        if ($titleNodes->length > 0) {
            $resultData['title'] = cleanText($titleNodes->item(0)->textContent);
            $resultData['url'] = $titleNodes->item(0)->getAttribute('href');
            
            // Make sure URL is absolute
            if (strpos($resultData['url'], 'http') !== 0) {
                $resultData['url'] = 'https://www.goodreads.com' . $resultData['url'];
            }
        }
        
        // Extract author
        $authorNodes = $xpath->query('.//a[@data-testid="authorLink"]', $result);
        if ($authorNodes->length === 0) {
            $authorNodes = $xpath->query('.//a[contains(@href, "/author/show/")]', $result);
        }
        
        if ($authorNodes->length > 0) {
            $resultData['author'] = cleanText($authorNodes->item(0)->textContent);
        }
        
        // Score the match
        if (!empty($searchQuery)) {
            // Title match
            if (!empty($resultData['title']) && stripos($searchQuery, $resultData['title']) !== false) {
                $score += 40;
            }
            
            // Author match
            if (!empty($resultData['author']) && stripos($searchQuery, $resultData['author']) !== false) {
                $score += 40;
            }
        }
        
        error_log("Result score: $score for " . ($resultData['title'] ?? 'Unknown') . " by " . ($resultData['author'] ?? 'Unknown'));
        
        if ($score > $bestScore) {
            $bestMatch = $resultData;
            $bestScore = $score;
        }
    }
    
    if ($bestMatch && !empty($bestMatch['url'])) {
        error_log("Selected best match (score: $bestScore): " . $bestMatch['url']);
        
        // Fetch the actual book page
        $html = fetchGoodreadsPage($bestMatch['url']);
        if ($html) {
            // Create a new DOM for the book page
            $bookDom = new DOMDocument();
            libxml_use_internal_errors(true);
            $bookDom->loadHTML($html);
            libxml_clear_errors();
            
            // Create a new XPath for the book page
            $bookXpath = new DOMXPath($bookDom);
            
            // Extract book info from the book page
            return extractGoodreadsBookInfo($html, $bestMatch['url']);
        }
    }
    
    return null;
}

/**
 * Main function to fetch and extract Goodreads book information
 * 
 * @param string $url The Goodreads URL
 * @return array The status and book information
 */
function getGoodreadsBookInfo($url) {
    // Initialize status
    $status = [
        'status' => 'initializing',
        'message' => 'Starting Goodreads data extraction',
        'steps' => [],
        'data' => []
    ];
    
    // Add initialization step
    $status['steps'][] = [
        'name' => 'initialization',
        'status' => 'success',
        'message' => "URL: $url"
    ];
    
    // Fetch the page
    $status['status'] = 'fetching';
    $status['message'] = "Fetching page: $url";
    
    $html = fetchGoodreadsPage($url);
    
    if ($html === false) {
        $status['status'] = 'error';
        $status['message'] = 'Failed to fetch page';
        $status['steps'][] = [
            'name' => 'fetch_page',
            'status' => 'error',
            'message' => 'Failed to fetch page content'
        ];
        return $status;
    }
    
    // Successfully fetched the page
    $status['steps'][] = [
        'name' => 'fetch_page',
        'status' => 'success',
        'message' => 'Successfully fetched page content'
    ];
    
    // Extract book information
    $status['status'] = 'extracting';
    $status['message'] = 'Extracting book information';
    
    $bookInfo = extractGoodreadsBookInfo($html, $url);
    
    if ($bookInfo === null) {
        $status['status'] = 'error';
        $status['message'] = 'Failed to extract book information';
        $status['steps'][] = [
            'name' => 'extract_info',
            'status' => 'error',
            'message' => 'No book information could be extracted'
        ];
        return $status;
    }
    
    // Successfully extracted book info
    $status['steps'][] = [
        'name' => 'extract_info',
        'status' => 'success',
        'message' => 'Successfully extracted book information'
    ];
    
    // Add the book data to the status object
    $status['data'] = $bookInfo;
    
    // Check for key fields to determine completeness
    $requiredFields = ['title', 'author', 'publisher', 'isbn'];
    $missingFields = [];
    
    foreach ($requiredFields as $field) {
        if (empty($bookInfo[$field])) {
            $missingFields[] = $field;
        }
    }
    
    if (!empty($missingFields)) {
        $status['status'] = 'partial';
        $status['message'] = 'Extracted partial book information (missing: ' . implode(', ', $missingFields) . ')';
    } else {
        $status['status'] = 'success';
        $status['message'] = 'Successfully extracted complete book information';
    }
    
    return $status;
}
