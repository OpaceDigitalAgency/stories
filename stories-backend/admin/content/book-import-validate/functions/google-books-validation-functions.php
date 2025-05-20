<?php
/**
 * Google Books Validation Functions
 *
 * This file contains functions for fetching and validating book data from Google Books.
 */

/**
 * Fetch data from Google Books
 *
 * @param string $isbn The ISBN to search for
 * @param string $title The book title
 * @param string $author The book author
 * @return array|null Book data or null if not found
 */
function fetchGoogleBooksDataNew($isbn, $title, $author) {
    try {
        // Start timer for performance tracking
        $startTime = microtime(true);

        // Log that we're starting Google Books fetch
        error_log("Starting Google Books data fetch for ISBN: $isbn");

        // Try ISBN search first
        $url = "https://www.googleapis.com/books/v1/volumes?q=isbn:" . urlencode($isbn);

        // Make the request
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode($response, true);

        // If no results, try title and author search
        if (empty($data['items']) && (!empty($title) || !empty($author))) {
            $query = '';
            if (!empty($title)) {
                $query .= "intitle:" . urlencode($title);
            }
            if (!empty($author)) {
                if (!empty($query)) {
                    $query .= "+";
                }
                $query .= "inauthor:" . urlencode($author);
            }

            $url = "https://www.googleapis.com/books/v1/volumes?q=" . $query;

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
            $response = curl_exec($ch);
            curl_close($ch);

            $data = json_decode($response, true);
        }

        if (!empty($data['items'])) {
            $volumeInfo = $data['items'][0]['volumeInfo'] ?? [];

            // Extract ISBNs
            $isbn10 = '';
            $isbn13 = '';
            if (!empty($volumeInfo['industryIdentifiers'])) {
                foreach ($volumeInfo['industryIdentifiers'] as $identifier) {
                    if ($identifier['type'] === 'ISBN_10') {
                        $isbn10 = $identifier['identifier'];
                    } else if ($identifier['type'] === 'ISBN_13') {
                        $isbn13 = $identifier['identifier'];
                    }
                }
            }

            // Extract categories/genres
            $categories = $volumeInfo['categories'] ?? [];

            // Build book data
            $bookData = [
                'title' => $volumeInfo['title'] ?? '',
                'author' => implode(', ', $volumeInfo['authors'] ?? []),
                'publisher' => $volumeInfo['publisher'] ?? '',
                'publication_date' => $volumeInfo['publishedDate'] ?? '',
                'page_count' => $volumeInfo['pageCount'] ?? '',
                'isbn' => $isbn10,
                'isbn13' => $isbn13,
                'language' => $volumeInfo['language'] ?? '',
                'format' => '',
                'series' => '',
                'awards' => '',
                'characters' => '',
                'settings' => '',
                'preview_link' => $volumeInfo['previewLink'] ?? '',
                'cover_url' => isset($volumeInfo['imageLinks']['thumbnail']) ? str_replace('http://', 'https://', $volumeInfo['imageLinks']['thumbnail']) : '',
                'rating' => $volumeInfo['averageRating'] ?? '',
                'rating_count' => $volumeInfo['ratingsCount'] ?? '',
                'review_count' => '',
                'maturity_rating' => $volumeInfo['maturityRating'] ?? ''
            ];

            // Log success for debugging
            $endTime = microtime(true);
            $totalTime = round($endTime - $startTime, 2);
            error_log("Successfully extracted Google Books data for ISBN: $isbn in {$totalTime}s");

            return $bookData;
        }

        // Log failure for debugging
        error_log("No book found on Google Books for ISBN: $isbn");
        return null;
    } catch (Exception $e) {
        error_log("Error fetching Google Books data: " . $e->getMessage());
        return null;
    }
}

/**
 * Validate ISBN using Google Books
 *
 * @param string $isbn The ISBN to validate
 * @return bool True if valid, false otherwise
 */
function validateIsbnWithGoogleBooks($isbn) {
    try {
        // Clean ISBN
        $cleanIsbn = preg_replace('/[^0-9X]/i', '', $isbn);

        // Check if ISBN is valid format
        if (strlen($cleanIsbn) !== 10 && strlen($cleanIsbn) !== 13) {
            return false;
        }

        // Try ISBN search
        $url = "https://www.googleapis.com/books/v1/volumes?q=isbn:" . urlencode($cleanIsbn);

        // Make the request
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            return false;
        }

        $data = json_decode($response, true);

        // Check if we found any books
        return !empty($data['items']);
    } catch (Exception $e) {
        error_log("Error validating ISBN with Google Books: " . $e->getMessage());
        return false;
    }
}

/**
 * Search for books by title and author using Google Books
 *
 * @param string $title The book title
 * @param string $author The book author
 * @param int $limit Maximum number of results to return
 * @return array List of book suggestions
 */
function searchBooksByTitleAuthor($title, $author = '', $limit = 5) {
    try {
        $query = '';
        if (!empty($title)) {
            $query .= "intitle:" . urlencode($title);
        }
        if (!empty($author)) {
            if (!empty($query)) {
                $query .= "+";
            }
            $query .= "inauthor:" . urlencode($author);
        }

        $url = "https://www.googleapis.com/books/v1/volumes?q=" . $query . "&maxResults=" . $limit;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);

        $suggestions = [];
        if (!empty($data['items'])) {
            foreach ($data['items'] as $item) {
                $volumeInfo = $item['volumeInfo'] ?? [];

                // Extract ISBNs
                $isbn10 = '';
                $isbn13 = '';
                if (!empty($volumeInfo['industryIdentifiers'])) {
                    foreach ($volumeInfo['industryIdentifiers'] as $identifier) {
                        if ($identifier['type'] === 'ISBN_10') {
                            $isbn10 = $identifier['identifier'];
                        } else if ($identifier['type'] === 'ISBN_13') {
                            $isbn13 = $identifier['identifier'];
                        }
                    }
                }

                $suggestions[] = [
                    'title' => $volumeInfo['title'] ?? '',
                    'author' => implode(', ', $volumeInfo['authors'] ?? []),
                    'publisher' => $volumeInfo['publisher'] ?? '',
                    'publication_date' => $volumeInfo['publishedDate'] ?? '',
                    'isbn' => $isbn10,
                    'isbn13' => $isbn13,
                    'cover_url' => isset($volumeInfo['imageLinks']['thumbnail']) ? str_replace('http://', 'https://', $volumeInfo['imageLinks']['thumbnail']) : '',
                    'preview_link' => $volumeInfo['previewLink'] ?? ''
                ];

                if (count($suggestions) >= $limit) {
                    break;
                }
            }
        }

        return $suggestions;
    } catch (Exception $e) {
        error_log("Error searching books by title/author: " . $e->getMessage());
        return [];
    }
}
