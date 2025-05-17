# Review Fetcher System

This system fetches book reviews from various sources and normalizes them for display in the Stories from the Web platform.

## Recent Changes

### Amazon to Goodreads Migration

Due to Amazon's increasingly aggressive anti-scraping measures (login screens, CAPTCHAs), we've enhanced the Goodreads fetcher and prioritized it over Amazon. This provides a more reliable source of reviews without requiring complex scraping solutions.

### Enhanced Goodreads Fetcher

The Goodreads fetcher now uses a two-step approach to find books:

1. **OpenLibrary Bridge**: First tries to find the Goodreads ID via OpenLibrary's API
   - Uses `https://openlibrary.org/isbn/{ISBN}.json` to get book data
   - Extracts Goodreads ID from the `identifiers.goodreads` field
   - If not found in the book data, tries to find it in the associated work data

2. **Direct Search Fallback**: If OpenLibrary doesn't have the Goodreads ID, falls back to the original search method
   - Uses `https://www.goodreads.com/search?q={ISBN}` to search for the book
   - Applies multiple regex patterns to find the book URL in the search results

### Review Source Prioritization

The `ReviewFetcherFactory` now prioritizes sources in the following order:

1. Goodreads (most reliable)
2. Other sources (Google Books, Open Library, etc.)
3. Amazon (least reliable, only used if no other sources return reviews)

This ensures we get the most reliable reviews first and only fall back to problematic sources when necessary.

## Debugging

Enhanced logging has been added to help debug review fetching issues:

- All Goodreads fetcher logs are saved to `services/ReviewFetcher/debug/goodreads-log.txt`
- OpenLibrary responses are saved to `services/ReviewFetcher/debug/openlibrary_{ISBN}_response.json`
- Goodreads search results are saved to `services/ReviewFetcher/debug/goodreads_search_debug.html`

## Usage

The review fetching system can be used as follows:

```php
// Get the factory
$factory = new \Services\ReviewFetcher\ReviewFetcherFactory($db);

// Fetch reviews from all sources
$result = $factory->fetchReviewsFromAllSources('9781234567890');

// Or fetch from specific sources
$result = $factory->fetchReviewsFromAllSources('9781234567890', [1, 2, 3]);

// Access the reviews
$reviews = $result['reviews'];

// Check for errors
$errors = $result['errors'];
```

## Review Sources

The system supports the following review sources:

1. **Goodreads** - Book reviews from Goodreads users
2. **Google Books** - Reviews from Google Books
3. **Open Library** - Book information and reviews from Open Library/Internet Archive
4. **Amazon** - Reviews from Amazon (now deprioritized due to scraping issues)
5. **Kirkus Reviews** - Professional reviews from Kirkus
6. **School Library Journal** - Professional reviews from SLJ
7. **Stories from the Web** - Internal reviews from our platform

## Future Improvements

Potential future improvements to the review system:

1. Add caching to reduce API calls and improve performance
2. Implement more robust error handling and retry mechanisms
3. Add support for additional review sources
4. Improve review normalization and deduplication
