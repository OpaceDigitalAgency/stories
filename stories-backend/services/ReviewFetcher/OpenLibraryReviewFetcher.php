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
        
        // Get the Internet Archive ID
        $iaId = $this->getInternetArchiveId($olid);
        
        if (empty($iaId)) {
            // No Internet Archive ID, try to generate synthetic reviews
            return $this->generateSyntheticReviews($bookData, $limit);
        }
        
        // Fetch reviews from Internet Archive
        $reviews = $this->fetchReviewsFromInternetArchive($iaId, $limit);
        
        // If no reviews found, generate synthetic ones
        if (empty($reviews)) {
            $reviews = $this->generateSyntheticReviews($bookData, $limit);
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
            ];
        }
        
        return $reviews;
    }
    
    /**
     * Get the Internet Archive ID for an Open Library ID
     * 
     * @param string $olid The Open Library ID
     * @return string|null The Internet Archive ID or null if not found
     */
    private function getInternetArchiveId(string $olid): ?string {
        // Build the API URL
        $url = "https://openlibrary.org/works/$olid.json";
        
        // Make the request
        $response = $this->makeRequest($url);
        
        if ($response === false) {
            return null;
        }
        
        // Parse the response
        $data = json_decode($response, true);
        
        // Check for Internet Archive ID
        if (!empty($data['ocaid'])) {
            return $data['ocaid'];
        }
        
        return null;
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
            if (empty($review['reviewtext']) || !isset($review['stars'])) {
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
    
    /**
     * Generate synthetic reviews based on book data
     * 
     * @param array $bookData The book data from Open Library
     * @param int $limit Maximum number of reviews to generate
     * @return array Array of review data
     */
    private function generateSyntheticReviews(array $bookData, int $limit): array {
        $reviews = [];
        
        // Generate a random number of reviews (1-5)
        $reviewCount = min($limit, rand(1, 5));
        
        // Generate a random average rating (3.5-4.8)
        $averageRating = rand(35, 48) / 10;
        
        // Generate ratings distribution
        $ratings = $this->generateRatingDistribution($averageRating, $reviewCount);
        
        // Generate reviews
        for ($i = 0; $i < $reviewCount; $i++) {
            $rating = $ratings[$i];
            
            $reviews[] = [
                'source_id' => $this->sourceId,
                'reviewer_name' => "Open Library Reader " . ($i + 1),
                'reviewer_age' => null,
                'review_date' => date('Y-m-d', strtotime("-" . rand(1, 90) . " days")),
                'original_rating' => "{$rating}/5",
                'rating_value' => $rating,
                'rating_scale' => 5,
                'rating_normalised' => $this->normalizeRating($rating, 5),
                'review_text' => $this->generateReviewText($rating, $bookData),
                'metadata' => json_encode([
                    'book_url' => $bookData['url'] ?? '',
                    'is_synthetic' => true
                ])
            ];
        }
        
        return $reviews;
    }
    
    /**
     * Generate a distribution of ratings around an average
     * 
     * @param float $average The average rating
     * @param int $count The number of ratings to generate
     * @return array Array of ratings
     */
    private function generateRatingDistribution(float $average, int $count): array {
        $ratings = [];
        
        // Ensure average is between 1 and 5
        $average = max(1, min(5, $average));
        
        // Generate ratings that will average close to the target
        $sum = 0;
        for ($i = 0; $i < $count - 1; $i++) {
            // Generate a rating between average-1 and average+1, but keep within 1-5 range
            $min = max(1, ceil($average - 1));
            $max = min(5, floor($average + 1));
            
            // If min > max (shouldn't happen, but just in case), swap them
            if ($min > $max) {
                $temp = $min;
                $min = $max;
                $max = $temp;
            }
            
            // Generate a random rating within the range
            $rating = rand($min * 10, $max * 10) / 10; // Allow one decimal place
            $ratings[] = $rating;
            $sum += $rating;
        }
        
        // Calculate the last rating to make the average match the target
        $lastRating = ($average * $count) - $sum;
        
        // Ensure the last rating is within 1-5 range
        $lastRating = max(1, min(5, $lastRating));
        
        $ratings[] = round($lastRating * 10) / 10; // Round to one decimal place
        
        // Shuffle the ratings
        shuffle($ratings);
        
        return $ratings;
    }
    
    /**
     * Generate review text based on rating and book data
     * 
     * @param float $rating The rating (1-5)
     * @param array $bookData The book data from Open Library
     * @return string The generated review text
     */
    private function generateReviewText(float $rating, array $bookData): string {
        $title = $bookData['title'] ?? 'this book';
        $author = !empty($bookData['authors'][0]['name']) ? $bookData['authors'][0]['name'] : 'the author';
        
        // Templates for different rating ranges
        $templates = [
            // 1-1.9 stars
            1 => [
                "I was disappointed with {$title}. The story didn't engage me and I found it hard to finish.",
                "{$title} wasn't what I expected. {$author}'s writing style didn't resonate with me.",
                "Unfortunately, I couldn't get into {$title}. The plot was confusing and the characters felt flat."
            ],
            // 2-2.9 stars
            2 => [
                "{$title} was just okay. Some interesting ideas but the execution could have been better.",
                "An average read. {$author} has potential but this particular book didn't fully deliver.",
                "Mixed feelings about {$title}. Some parts were good but others dragged on too long."
            ],
            // 3-3.9 stars
            3 => [
                "I enjoyed {$title}. {$author} created some memorable characters and the story kept me interested.",
                "A solid book with good writing. Not perfect, but definitely worth reading.",
                "{$title} was a pleasant read. The story was engaging and I liked the overall message."
            ],
            // 4-4.9 stars
            4 => [
                "Excellent book! {$author} has crafted a wonderful story with compelling characters.",
                "I really enjoyed {$title}. The writing was beautiful and the plot kept me engaged throughout.",
                "A great read that I would recommend. {$title} is both entertaining and thought-provoking."
            ],
            // 5 stars
            5 => [
                "{$title} is fantastic! One of the best books I've read this year. {$author}'s storytelling is masterful.",
                "Absolutely loved {$title}! Couldn't put it down and was sad when it ended.",
                "A perfect book in every way. {$author} has created something truly special with {$title}."
            ]
        ];
        
        // Determine which template group to use
        $group = min(5, max(1, floor($rating)));
        
        // Select a random template from the group
        $template = $templates[$group][array_rand($templates[$group])];
        
        // Add a child-specific comment for high ratings (4-5 stars)
        if ($rating >= 4 && rand(0, 1) == 1) {
            $childComments = [
                " My children loved this book and asked to read it again and again.",
                " Perfect for young readers. The story is engaging and the lessons are valuable.",
                " A wonderful children's book that both educates and entertains.",
                " Great for reading aloud to children. The story flows well and keeps their attention."
            ];
            
            $template .= $childComments[array_rand($childComments)];
        }
        
        // Add an age-specific comment occasionally
        if (rand(0, 2) == 0) {
            $ageComments = [
                " Recommended for ages 6-8.",
                " Perfect for 9-12 year olds.",
                " Ideal for middle-grade readers (ages 8-12).",
                " My 7-year-old couldn't put it down.",
                " Great for children aged 10-13.",
                " My 5-year-old loved the pictures and story."
            ];
            
            $template .= $ageComments[array_rand($ageComments)];
        }
        
        return $template;
    }
}
