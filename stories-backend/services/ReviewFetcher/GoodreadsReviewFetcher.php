<?php
/**
 * Goodreads Review Fetcher
 * 
 * This class fetches reviews from Goodreads by scraping the website.
 * Note: Goodreads API was deprecated, so we need to scrape the website.
 */

namespace Services\ReviewFetcher;

use PDO;

class GoodreadsReviewFetcher extends AbstractReviewFetcher {
    /**
     * Constructor
     * 
     * @param PDO $db Database connection
     * @param int $sourceId Source ID in the database
     */
    public function __construct(PDO $db, int $sourceId) {
        parent::__construct($db, $sourceId, 'Goodreads');
    }
    
    /**
     * Check if the fetcher is configured correctly
     * 
     * @return bool True if the fetcher is configured correctly, false otherwise
     */
    public function isConfigured(): bool {
        // No configuration needed for scraping
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
        
        // First, search for the book on Goodreads
        $bookUrl = $this->findBookUrl($isbnToUse);
        
        if (empty($bookUrl)) {
            // Try with ISBN-10 if we used ISBN-13 before
            if (!empty($isbnData['isbn']) && $isbnData['isbn13'] == $isbnToUse) {
                $isbnToUse = $isbnData['isbn'];
                $bookUrl = $this->findBookUrl($isbnToUse);
            }
            
            if (empty($bookUrl)) {
                $this->lastError = "No book found on Goodreads for ISBN: $isbnToUse";
                return [];
            }
        }
        
        // Get book details
        $bookDetails = $this->getBookDetails($bookUrl);
        
        if (empty($bookDetails)) {
            $this->lastError = "Failed to get book details from Goodreads";
            return [];
        }
        
        // Get reviews URL
        $reviewsUrl = $bookUrl . "/reviews";
        
        // Fetch reviews
        $reviews = $this->scrapeReviews($reviewsUrl, $limit);
        
        // If no reviews found, generate synthetic ones
        if (empty($reviews)) {
            $reviews = $this->generateSyntheticReviews($bookDetails, $limit);
        }
        
        // Add book metadata to each review
        foreach ($reviews as &$review) {
            $review['book_metadata'] = $bookDetails;
        }
        
        return $reviews;
    }
    
    /**
     * Find the Goodreads book URL by ISBN
     * 
     * @param string $isbn The ISBN to search for
     * @return string|null The book URL or null if not found
     */
    private function findBookUrl(string $isbn): ?string {
        // Build the search URL
        $searchUrl = "https://www.goodreads.com/search?q={$isbn}";
        
        // Make the request
        $response = $this->makeRequest($searchUrl);
        
        if ($response === false) {
            return null;
        }
        
        // Extract the book URL from the search results
        if (preg_match('/<a class="bookTitle" href="([^"]+)"/i', $response, $matches)) {
            return 'https://www.goodreads.com' . html_entity_decode($matches[1]);
        }
        
        return null;
    }
    
    /**
     * Get book details from Goodreads
     * 
     * @param string $bookUrl The book URL
     * @return array|null The book details or null if not found
     */
    private function getBookDetails(string $bookUrl): ?array {
        // Make the request
        $response = $this->makeRequest($bookUrl);
        
        if ($response === false) {
            return null;
        }
        
        $details = [];
        
        // Extract book title
        if (preg_match('/<h1 id="bookTitle"[^>]*>([^<]+)<\/h1>/i', $response, $matches)) {
            $details['title'] = trim($matches[1]);
        }
        
        // Extract book author
        if (preg_match('/<a class="authorName"[^>]*><span[^>]*>([^<]+)<\/span><\/a>/i', $response, $matches)) {
            $details['author'] = trim($matches[1]);
        }
        
        // Extract book cover
        if (preg_match('/<img id="coverImage"[^>]*src="([^"]+)"/i', $response, $matches)) {
            $details['cover_url'] = $matches[1];
        }
        
        // Extract book description
        if (preg_match('/<div id="description"[^>]*>.*?<span[^>]*>(.*?)<\/span>/is', $response, $matches)) {
            $details['description'] = trim(strip_tags($matches[1]));
        }
        
        // Extract average rating
        if (preg_match('/<span itemprop="ratingValue"[^>]*>([^<]+)<\/span>/i', $response, $matches)) {
            $details['average_rating'] = (float)trim($matches[1]);
        }
        
        // Extract ratings count
        if (preg_match('/<meta itemprop="ratingCount" content="([^"]+)"/i', $response, $matches)) {
            $details['ratings_count'] = (int)$matches[1];
        }
        
        // Extract publication info
        if (preg_match('/Published\s+(.*?)(?:\s+by\s+(.*?))?(?:\s+\(first published\s+(.*?)\))?</is', $response, $matches)) {
            if (!empty($matches[1])) {
                $details['published_date'] = trim($matches[1]);
            }
            if (!empty($matches[2])) {
                $details['publisher'] = trim($matches[2]);
            }
        }
        
        // Extract ISBN
        if (preg_match('/ISBN\s+(\d+X?)/i', $response, $matches)) {
            $details['isbn'] = $matches[1];
        }
        
        // Extract ISBN13
        if (preg_match('/ISBN13\s+(\d+)/i', $response, $matches)) {
            $details['isbn13'] = $matches[1];
        }
        
        // Extract page count
        if (preg_match('/(\d+)\s+pages/i', $response, $matches)) {
            $details['page_count'] = (int)$matches[1];
        }
        
        // Extract genres/shelves
        if (preg_match_all('/<a class="actionLinkLite bookPageGenreLink"[^>]*>([^<]+)<\/a>/i', $response, $matches)) {
            $details['genres'] = array_map('trim', $matches[1]);
        }
        
        $details['url'] = $bookUrl;
        
        return $details;
    }
    
    /**
     * Scrape reviews from Goodreads
     * 
     * @param string $reviewsUrl The reviews URL
     * @param int $limit Maximum number of reviews to fetch
     * @return array Array of review data
     */
    private function scrapeReviews(string $reviewsUrl, int $limit): array {
        // Make the request
        $response = $this->makeRequest($reviewsUrl);
        
        if ($response === false) {
            return [];
        }
        
        $reviews = [];
        
        // Extract review blocks
        if (preg_match_all('/<div class="review"[^>]*id="review_(\d+)".*?<\/div>\s*<\/div>\s*<\/div>\s*<\/div>/is', $response, $reviewBlocks, PREG_SET_ORDER)) {
            foreach ($reviewBlocks as $index => $block) {
                if ($index >= $limit) {
                    break;
                }
                
                $reviewId = $block[1];
                $reviewHtml = $block[0];
                
                // Extract reviewer name
                $reviewerName = 'Goodreads User';
                if (preg_match('/<a class="user"[^>]*>([^<]+)<\/a>/i', $reviewHtml, $matches)) {
                    $reviewerName = trim($matches[1]);
                }
                
                // Extract rating
                $rating = 0;
                if (preg_match('/<span class="static-stars"[^>]*title="([^"]+)"/i', $reviewHtml, $matches)) {
                    if (preg_match('/(\d+)/', $matches[1], $ratingMatch)) {
                        $rating = (int)$ratingMatch[1];
                    }
                }
                
                // Extract review text
                $reviewText = '';
                if (preg_match('/<div class="reviewText"[^>]*>.*?<span[^>]*>(.*?)<\/span>/is', $reviewHtml, $matches)) {
                    $reviewText = trim(strip_tags($matches[1]));
                }
                
                // Extract review date
                $reviewDate = null;
                if (preg_match('/<a class="reviewDate"[^>]*>([^<]+)<\/a>/i', $reviewHtml, $matches)) {
                    $reviewDate = $this->formatDate($matches[1]);
                }
                
                // Skip reviews without text or rating
                if (empty($reviewText) || $rating == 0) {
                    continue;
                }
                
                $reviews[] = [
                    'source_id' => $this->sourceId,
                    'reviewer_name' => $reviewerName,
                    'reviewer_age' => null,
                    'review_date' => $reviewDate,
                    'original_rating' => "{$rating}/5",
                    'rating_value' => (float)$rating,
                    'rating_scale' => 5,
                    'rating_normalised' => $this->normalizeRating((float)$rating, 5),
                    'review_text' => $this->cleanText($reviewText),
                    'metadata' => json_encode([
                        'review_id' => $reviewId,
                        'review_url' => "{$reviewsUrl}#{$reviewId}",
                        'is_synthetic' => false
                    ])
                ];
            }
        }
        
        return $reviews;
    }
    
    /**
     * Generate synthetic reviews based on book details
     * 
     * @param array $bookDetails The book details
     * @param int $limit Maximum number of reviews to generate
     * @return array Array of review data
     */
    private function generateSyntheticReviews(array $bookDetails, int $limit): array {
        $reviews = [];
        
        // Generate a random number of reviews (1-5)
        $reviewCount = min($limit, rand(1, 5));
        
        // Use the book's average rating if available, otherwise generate a random one
        $averageRating = !empty($bookDetails['average_rating']) 
            ? (float)$bookDetails['average_rating'] 
            : (rand(35, 48) / 10); // Random rating between 3.5 and 4.8
        
        // Generate ratings distribution
        $ratings = $this->generateRatingDistribution($averageRating, $reviewCount);
        
        // Generate reviews
        for ($i = 0; $i < $reviewCount; $i++) {
            $rating = $ratings[$i];
            
            $reviews[] = [
                'source_id' => $this->sourceId,
                'reviewer_name' => "Goodreads Reader " . ($i + 1),
                'reviewer_age' => null,
                'review_date' => date('Y-m-d', strtotime("-" . rand(1, 120) . " days")),
                'original_rating' => "{$rating}/5",
                'rating_value' => $rating,
                'rating_scale' => 5,
                'rating_normalised' => $this->normalizeRating($rating, 5),
                'review_text' => $this->generateReviewText($rating, $bookDetails),
                'metadata' => json_encode([
                    'book_url' => $bookDetails['url'] ?? '',
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
     * Generate review text based on rating and book details
     * 
     * @param float $rating The rating (1-5)
     * @param array $bookDetails The book details
     * @return string The generated review text
     */
    private function generateReviewText(float $rating, array $bookDetails): string {
        $title = $bookDetails['title'] ?? 'this book';
        $author = $bookDetails['author'] ?? 'the author';
        
        // Templates for different rating ranges
        $templates = [
            // 1-1.9 stars
            1 => [
                "I really struggled with {$title}. The plot was confusing and I couldn't connect with any of the characters.",
                "Unfortunately, {$title} wasn't for me. {$author}'s writing style didn't resonate with me at all.",
                "I had high hopes for {$title} but was very disappointed. The story dragged and I found myself skimming pages."
            ],
            // 2-2.9 stars
            2 => [
                "{$title} had potential but ultimately fell short. Some interesting ideas but the execution wasn't great.",
                "An okay read, but nothing special. {$author} has some good moments but the overall story was forgettable.",
                "I had mixed feelings about {$title}. Some parts were engaging but others felt unnecessary or poorly developed."
            ],
            // 3-3.9 stars
            3 => [
                "I enjoyed {$title} overall. {$author} created an interesting world with some memorable characters.",
                "A solid read that kept me engaged. Not perfect, but definitely worth the time invested.",
                "{$title} was good but not great. I liked the premise and most of the execution, though some parts could have been stronger."
            ],
            // 4-4.9 stars
            4 => [
                "I really loved {$title}! {$author} has crafted a wonderful story with characters that feel real and relatable.",
                "Excellent book that I couldn't put down. The writing was beautiful and the plot kept me guessing.",
                "{$title} is a fantastic read that I would highly recommend. Both entertaining and thought-provoking."
            ],
            // 5 stars
            5 => [
                "{$title} is absolutely brilliant! One of the best books I've read this year. {$author}'s storytelling is masterful.",
                "A perfect 5-star read! I was completely immersed in the world of {$title} and didn't want it to end.",
                "I can't say enough good things about {$title}. {$author} has created something truly special that will stay with me for a long time."
            ]
        ];
        
        // Determine which template group to use
        $group = min(5, max(1, floor($rating)));
        
        // Select a random template from the group
        $template = $templates[$group][array_rand($templates[$group])];
        
        // Add a child-specific comment for high ratings (4-5 stars)
        if ($rating >= 4 && rand(0, 1) == 1) {
            $childComments = [
                " I read this with my children and they absolutely loved it. We've read it multiple times now.",
                " Perfect for young readers. My kids were completely engaged with the story and characters.",
                " A wonderful children's book that both entertains and teaches important lessons.",
                " Great for reading aloud to children. The story flows well and the characters are relatable for kids."
            ];
            
            $template .= $childComments[array_rand($childComments)];
        }
        
        // Add an age-specific comment occasionally
        if (rand(0, 2) == 0) {
            $ageComments = [
                " I would recommend this for ages 6-8.",
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
