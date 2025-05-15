<?php
/**
 * Review Fetcher Factory
 *
 * This class creates and manages review fetcher instances.
 */

namespace Services\ReviewFetcher;

use PDO;

class ReviewFetcherFactory {
    /**
     * @var PDO Database connection
     */
    private $db;

    /**
     * @var array Review sources from the database
     */
    private $sources = [];

    /**
     * @var array Fetcher instances
     */
    private $fetchers = [];

    /**
     * Constructor
     *
     * @param PDO $db Database connection
     */
    public function __construct(PDO $db) {
        $this->db = $db;
        $this->loadSources();
    }

    /**
     * Load review sources from the database
     */
    private function loadSources(): void {
        try {
            $stmt = $this->db->query("SELECT id, name, url, is_third_party FROM review_sources ORDER BY id");
            $this->sources = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Error loading review sources: " . $e->getMessage());
            $this->sources = [];
        }
    }

    /**
     * Get all review sources
     *
     * @return array Array of review sources
     */
    public function getSources(): array {
        return $this->sources;
    }

    /**
     * Get a review fetcher by source ID
     *
     * @param int $sourceId The source ID
     * @return ReviewFetcherInterface|null The review fetcher or null if not found
     */
    public function getFetcher(int $sourceId): ?ReviewFetcherInterface {
        // Return cached fetcher if available
        if (isset($this->fetchers[$sourceId])) {
            return $this->fetchers[$sourceId];
        }

        // Find the source
        $source = null;
        foreach ($this->sources as $s) {
            if ($s['id'] == $sourceId) {
                $source = $s;
                break;
            }
        }

        if (!$source) {
            return null;
        }

        // Create the appropriate fetcher based on the source name
        $fetcher = null;

        switch (strtolower($source['name'])) {
            case 'google books':
                $fetcher = new GoogleBooksReviewFetcher($this->db, $sourceId);
                break;

            case 'open library':
                $fetcher = new OpenLibraryReviewFetcher($this->db, $sourceId);
                break;

            case 'goodreads':
                $fetcher = new GoodreadsReviewFetcher($this->db, $sourceId);
                break;

            case 'amazon':
                $fetcher = new AmazonReviewFetcher($this->db, $sourceId);
                break;

            default:
                // Unknown source
                return null;
        }

        // Cache the fetcher
        $this->fetchers[$sourceId] = $fetcher;

        return $fetcher;
    }

    /**
     * Get all available fetchers
     *
     * @return array Array of review fetchers
     */
    public function getAllFetchers(): array {
        $fetchers = [];

        foreach ($this->sources as $source) {
            // Skip internal sources
            if (!$source['is_third_party']) {
                continue;
            }

            $fetcher = $this->getFetcher($source['id']);
            if ($fetcher && $fetcher->isConfigured()) {
                $fetchers[] = $fetcher;
            }
        }

        return $fetchers;
    }

    /**
     * Fetch reviews for a book from all available sources
     *
     * @param string $isbn The ISBN of the book
     * @param array $sourceIds Optional array of source IDs to use (default: all)
     * @param int $limit Maximum number of reviews per source
     * @param bool $logErrors Whether to log errors (default: true)
     * @return array Array of reviews from all sources and any errors encountered
     */
    public function fetchReviewsFromAllSources(string $isbn, array $sourceIds = [], int $limit = 5, bool $logErrors = true): array {
        $result = [
            'reviews' => [],
            'errors' => [],
            'sources_attempted' => 0,
            'sources_successful' => 0
        ];

        // Get fetchers to use
        $fetchers = [];
        if (empty($sourceIds)) {
            // Use all available fetchers
            $fetchers = $this->getAllFetchers();
        } else {
            // Use only specified fetchers
            foreach ($sourceIds as $sourceId) {
                $fetcher = $this->getFetcher($sourceId);
                if ($fetcher && $fetcher->isConfigured()) {
                    $fetchers[] = $fetcher;
                }
            }
        }

        // Fetch reviews from each source
        foreach ($fetchers as $fetcher) {
            $result['sources_attempted']++;
            $sourceName = $fetcher->getSourceName();

            try {
                $reviews = $fetcher->fetchReviewsByISBN($isbn, $limit);

                if (!empty($reviews)) {
                    $result['reviews'] = array_merge($result['reviews'], $reviews);
                    $result['sources_successful']++;
                } else {
                    $errorMessage = $fetcher->getLastError() ?: "No reviews found";
                    $result['errors'][$sourceName] = $errorMessage;

                    if ($logErrors) {
                        error_log("No reviews found from {$sourceName} for ISBN {$isbn}: {$errorMessage}");
                    }
                }
            } catch (\Exception $e) {
                $errorMessage = $e->getMessage();
                $result['errors'][$sourceName] = $errorMessage;

                if ($logErrors) {
                    error_log("Error fetching reviews from {$sourceName} for ISBN {$isbn}: {$errorMessage}");
                }
            }
        }

        return $result;
    }

    /**
     * Fetch reviews for a book from all available sources (legacy method)
     *
     * @param string $isbn The ISBN of the book
     * @param array $sourceIds Optional array of source IDs to use (default: all)
     * @param int $limit Maximum number of reviews per source
     * @return array Array of reviews from all sources
     * @deprecated Use fetchReviewsFromAllSources() instead
     */
    public function fetchReviews(string $isbn, array $sourceIds = [], int $limit = 5): array {
        $result = $this->fetchReviewsFromAllSources($isbn, $sourceIds, $limit);
        return $result['reviews'];
    }
}
