<?php
/**
 * Open Library Review Fetcher
 *
 * This class fetches book information from Open Library and reviews from Internet Archive.
 */

namespace Services\ReviewFetcher;

use PDO;

class OpenLibraryReviewFetcher extends AbstractReviewFetcher {
    /**
     * @var string Open Library API base URL
     */
    private $apiBaseUrl = 'https://openlibrary.org/api';

    /**
     * @var string Internet Archive API base URL
     */
    private $iaApiBaseUrl = 'https://archive.org/metadata';

    /**
     * Constructor
     *
     * @param PDO $db Database connection
     * @param int $sourceId Source ID in the database
     */
    public function __construct(PDO $db, int $sourceId) {
        parent::__construct($db, $sourceId, 'Open Library');
    }

    /**
     * Check if the fetcher is configured correctly
     *
     * @return bool True if the fetcher is configured correctly, false otherwise
     */
    public function isConfigured(): bool {
        // Open Library API doesn't require authentication
        return true;
    }

    /**
     * Fetch reviews for a book by ISBN
     *
     * @param string $isbn The ISBN of the book (can be ISBN-10 or ISBN-13)
     * @param int $limit Maximum number of reviews to fetch
     * @return array Array of review data
     */
    public function fetchReviewsByISBN(string $isbn, int $limit = 10): array {
        // Standardize ISBN format
        $isbnData = $this->standardizeISBN($isbn);

        // Try ISBN-13 first, then ISBN-10
        $isbnToUse = !empty($isbnData['isbn13']) ? $isbnData['isbn13'] : $isbnData['isbn'];

        // Build the API URL
        $url = "{$this->apiBaseUrl}/books?bibkeys=ISBN:{$isbnToUse}&format=json&jscmd=data";

        // Make the request
        $response = $this->makeRequest($url);

        if ($response === false) {
            return [];
        }

        // Parse the response
        $data = json_decode($response, true);

        if (empty($data["ISBN:$isbnToUse"])) {
            // Try with ISBN-10 if we used ISBN-13 before
            if (!empty($isbnData['isbn']) && $isbnData['isbn13'] == $isbnToUse) {
                $isbnToUse = $isbnData['isbn'];
                $url = "{$this->apiBaseUrl}/books?bibkeys=ISBN:{$isbnToUse}&format=json&jscmd=data";
                $response = $this->makeRequest($url);

                if ($response === false) {
                    return [];
                }

                $data = json_decode($response, true);
            }

            if (empty($data["ISBN:$isbnToUse"])) {
                $this->lastError = "No books found for ISBN: $isbnToUse";
                return [];
            }
        }

        // Get the book data
        $bookData = $data["ISBN:$isbnToUse"];

        // Get the Open Library ID
        $olid = null;
        if (!empty($bookData['identifiers']['openlibrary'])) {
            $olid = $bookData['identifiers']['openlibrary'][0];
        }

        if (empty($olid)) {
            $this->lastError = "No Open Library ID found for ISBN: $isbnToUse";
            return [];
        }

        // Get the Open Library work data
        $workData = $this->getOpenLibraryWorkData($olid);

        if (empty($workData)) {
            $this->lastError = "Failed to get Open Library work data for ID: $olid";
            return [];
        }

        // Generate reviews from the Open Library data
        $reviews = $this->generateReviewsFromOpenLibraryData($workData, $limit);

        // If we have no reviews, return an empty array
        if (empty($reviews)) {
            $this->lastError = "No reviews found for this book on Open Library or Internet Archive";
            return [];
        }

        // Add book metadata to each review
        foreach ($reviews as &$review) {
            $review['book_metadata'] = [
                'title' => $bookData['title'] ?? '',
                'authors' => array_map(function($author) {
                    return $author['name'] ?? '';
                }, $bookData['authors'] ?? []),
                'publisher' => $bookData['publishers'][0]['name'] ?? '',
                'published_date' => $bookData['publish_date'] ?? '',
                'number_of_pages' => $bookData['number_of_pages'] ?? null,
                'subjects' => array_map(function($subject) {
                    return $subject['name'] ?? '';
                }, $bookData['subjects'] ?? []),
                'cover_url' => $bookData['cover']['large'] ?? $bookData['cover']['medium'] ?? $bookData['cover']['small'] ?? '',
                'url' => $bookData['url'] ?? '',
                'isbn' => $isbnToUse
            ];
        }

        return $reviews;
    }

    /**
     * Generate a basic review text from book data
     *
     * @param array $bookData The book data from Open Library API
     * @return string The generated review text
     */
    private function generateBasicReviewText(array $bookData): string {
        $title = $bookData['title'] ?? 'This book';

        // Get authors
        $authors = array_map(function($author) {
            return $author['name'] ?? '';
        }, $bookData['authors'] ?? []);
        $authorText = !empty($authors) ? implode(', ', $authors) : 'Unknown author';

        // Start with basic info
        $text = "{$title} by {$authorText} is available on Open Library.";

        // Add description if available
        if (!empty($bookData['notes'])) {
            $notes = $bookData['notes'];
            // Truncate if too long
            if (strlen($notes) > 500) {
                $notes = substr($notes, 0, 500) . '...';
            }
            $text .= "\n\nNotes: {$notes}";
        }

        // Add subjects if available
        if (!empty($bookData['subjects'])) {
            $subjects = array_map(function($subject) {
                return $subject['name'] ?? '';
            }, $bookData['subjects'] ?? []);
            $subjects = array_slice($subjects, 0, 10); // Limit to 10 subjects
            $subjectText = implode(', ', $subjects);
            $text .= "\n\nSubjects: {$subjectText}";
        }

        // Add publication info
        if (!empty($bookData['publish_date'])) {
            $text .= "\n\nPublished: {$bookData['publish_date']}";
        }

        if (!empty($bookData['publishers'])) {
            $publishers = array_map(function($publisher) {
                return $publisher['name'] ?? '';
            }, $bookData['publishers'] ?? []);
            $publisherText = implode(', ', $publishers);
            $text .= " by {$publisherText}";
        }

        return $text;
    }

    /**
     * Get the Open Library work ID and additional data for a book
     *
     * @param string $olid The Open Library ID
     * @return array|null The Open Library work data or null if not found
     */
    private function getOpenLibraryWorkData(string $olid): ?array {
        // Build the API URL for the edition
        $url = "https://openlibrary.org/books/$olid.json";

        // Make the request
        $response = $this->makeRequest($url);

        if ($response === false) {
            return null;
        }

        // Parse the response
        $editionData = json_decode($response, true);

        // Check if we have a works reference
        if (empty($editionData['works'][0]['key'])) {
            return null;
        }

        // Get the work ID
        $workKey = $editionData['works'][0]['key'];
        $workId = str_replace('/works/', '', $workKey);

        // Get the work data
        $workUrl = "https://openlibrary.org/works/$workId.json";
        $workResponse = $this->makeRequest($workUrl);

        if ($workResponse === false) {
            return null;
        }

        // Parse the work data
        $workData = json_decode($workResponse, true);

        // Combine relevant data
        return [
            'edition' => $editionData,
            'work' => $workData,
            'work_id' => $workId,
            'ia_id' => $editionData['ocaid'] ?? null
        ];
    }

    /**
     * Generate reviews from Open Library data
     *
     * @param array $bookData The book data from Open Library API
     * @param int $limit Maximum number of reviews to generate
     * @return array Array of review data
     */
    private function generateReviewsFromOpenLibraryData(array $bookData, int $limit): array {
        $reviews = [];

        // Extract edition data
        $edition = $bookData['edition'] ?? [];
        $work = $bookData['work'] ?? [];

        // Check if we have ratings
        $hasRatings = isset($work['ratings_average']) && isset($work['ratings_count']);
        $averageRating = $hasRatings ? (float)$work['ratings_average'] : 0;
        $ratingCount = $hasRatings ? (int)$work['ratings_count'] : 0;

        // If we have ratings, create an aggregate review
        if ($hasRatings && $ratingCount > 0) {
            // Normalize the rating to 0-5 scale if needed
            $normalizedRating = $averageRating;
            if ($averageRating > 5) {
                $normalizedRating = $averageRating / 2; // Assuming it's on a 10-point scale
            }

            $reviews[] = [
                'source_id' => $this->sourceId,
                'reviewer_name' => "Open Library Aggregate",
                'reviewer_age' => null,
                'review_date' => date('Y-m-d'),
                'original_rating' => "{$averageRating}/5",
                'rating_value' => $normalizedRating,
                'rating_scale' => 5,
                'rating_normalised' => $this->normalizeRating($normalizedRating, 5),
                'review_text' => $this->generateReviewText($bookData),
                'metadata' => json_encode([
                    'work_id' => $bookData['work_id'] ?? '',
                    'ia_id' => $bookData['ia_id'] ?? '',
                    'book_url' => "https://openlibrary.org" . ($edition['key'] ?? ''),
                    'is_synthetic' => false,
                    'is_aggregate' => true,
                    'ratings_count' => $ratingCount
                ])
            ];
        }

        // If we have an Internet Archive ID, try to get reviews from there
        if (!empty($bookData['ia_id'])) {
            $iaReviews = $this->fetchReviewsFromInternetArchive($bookData['ia_id'], $limit - count($reviews));
            $reviews = array_merge($reviews, $iaReviews);
        }

        return $reviews;
    }

    /**
     * Generate review text based on Open Library data
     *
     * @param array $bookData The book data from Open Library API
     * @return string The generated review text
     */
    private function generateReviewText(array $bookData): string {
        $edition = $bookData['edition'] ?? [];
        $work = $bookData['work'] ?? [];

        $title = $edition['title'] ?? $work['title'] ?? 'This book';

        // Get authors
        $authors = [];
        if (!empty($edition['authors'])) {
            foreach ($edition['authors'] as $author) {
                if (isset($author['name'])) {
                    $authors[] = $author['name'];
                }
            }
        }
        $authorText = !empty($authors) ? implode(', ', $authors) : 'Unknown author';

        // Start with basic info
        $text = "{$title} by {$authorText}";

        // Add ratings if available
        if (isset($work['ratings_average']) && isset($work['ratings_count'])) {
            $averageRating = (float)$work['ratings_average'];
            $ratingCount = (int)$work['ratings_count'];
            $text .= " has an average rating of {$averageRating}/5 based on {$ratingCount} ratings on Open Library.";
        } else {
            $text .= " is available on Open Library.";
        }

        // Add description if available
        if (!empty($work['description'])) {
            $description = is_array($work['description']) ? ($work['description']['value'] ?? '') : $work['description'];
            // Truncate description if too long
            if (strlen($description) > 500) {
                $description = substr($description, 0, 500) . '...';
            }
            $text .= "\n\nDescription: {$description}";
        }

        // Add subjects if available
        if (!empty($work['subjects'])) {
            $subjects = array_slice($work['subjects'], 0, 10); // Limit to 10 subjects
            $subjectText = implode(', ', $subjects);
            $text .= "\n\nSubjects: {$subjectText}";
        }

        // Add publication info
        if (!empty($edition['publish_date'])) {
            $text .= "\n\nPublished: {$edition['publish_date']}";
        }

        if (!empty($edition['publishers'])) {
            $publishers = is_array($edition['publishers']) ? implode(', ', $edition['publishers']) : $edition['publishers'];
            $text .= " by {$publishers}";
        }

        return $text;
    }

    /**
     * Fetch reviews from Internet Archive
     *
     * @param string $iaId The Internet Archive ID
     * @param int $limit Maximum number of reviews to fetch
     * @return array Array of review data
     */
    private function fetchReviewsFromInternetArchive(string $iaId, int $limit): array {
        // Build the API URL
        $url = "{$this->iaApiBaseUrl}/{$iaId}/reviews";

        // Make the request
        $response = $this->makeRequest($url);

        if ($response === false) {
            return [];
        }

        // Parse the response
        $data = json_decode($response, true);

        if (empty($data['reviews'])) {
            return [];
        }

        // Process reviews
        $reviews = [];
        $count = 0;

        foreach ($data['reviews'] as $review) {
            if ($count >= $limit) {
                break;
            }

            // Skip reviews without text or stars
            if (empty($review['reviewtext']) || !isset($review['stars']) || empty($review['stars'])) {
                continue;
            }

            // Ensure stars is a valid number
            $stars = (float)$review['stars'];
            if ($stars <= 0) {
                continue;
            }

            $reviewDate = null;
            if (!empty($review['createdate'])) {
                $reviewDate = $this->formatDate($review['createdate']);
            }

            $reviewerName = !empty($review['reviewer']) ? $review['reviewer'] : 'Anonymous';

            $reviews[] = [
                'source_id' => $this->sourceId,
                'reviewer_name' => $reviewerName,
                'reviewer_age' => null,
                'review_date' => $reviewDate,
                'original_rating' => "{$review['stars']}/5",
                'rating_value' => (float)$review['stars'],
                'rating_scale' => 5,
                'rating_normalised' => $this->normalizeRating((float)$review['stars'], 5),
                'review_text' => $this->cleanText($review['reviewtext']),
                'metadata' => json_encode([
                    'ia_id' => $iaId,
                    'review_id' => $review['reviewid'] ?? '',
                    'review_url' => "https://archive.org/details/{$iaId}/reviews",
                    'is_synthetic' => false
                ])
            ];

            $count++;
        }

        return $reviews;
    }


}
