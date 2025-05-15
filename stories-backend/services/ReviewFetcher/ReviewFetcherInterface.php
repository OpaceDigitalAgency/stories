<?php
/**
 * Review Fetcher Interface
 * 
 * This interface defines the contract for all review fetcher implementations.
 * Each review fetcher is responsible for fetching reviews from a specific source.
 */

namespace Services\ReviewFetcher;

interface ReviewFetcherInterface {
    /**
     * Fetch reviews for a book by ISBN
     * 
     * @param string $isbn The ISBN of the book (can be ISBN-10 or ISBN-13)
     * @param int $limit Maximum number of reviews to fetch
     * @return array Array of review data
     */
    public function fetchReviewsByISBN(string $isbn, int $limit = 10): array;
    
    /**
     * Get the source ID for this fetcher
     * 
     * @return int The ID of the review source in the database
     */
    public function getSourceId(): int;
    
    /**
     * Get the name of the review source
     * 
     * @return string The name of the review source
     */
    public function getSourceName(): string;
    
    /**
     * Check if the fetcher is configured correctly
     * 
     * @return bool True if the fetcher is configured correctly, false otherwise
     */
    public function isConfigured(): bool;
    
    /**
     * Get the last error message
     * 
     * @return string|null The last error message or null if no error occurred
     */
    public function getLastError(): ?string;
}
