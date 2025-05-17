# Review Fetcher System

This system fetches book reviews from various sources and normalizes them for display in the Stories from the Web platform.

## Current Status and Challenges

### Anti-Scraping Challenges

We've encountered significant challenges with review scraping due to increasingly sophisticated anti-bot measures:

#### Amazon Challenges
- **Forced Login Walls**: Amazon now redirects to login pages for most review access attempts
- **CAPTCHA Detection**: Sophisticated bot detection triggers CAPTCHAs frequently
- **Dynamic HTML Structure**: Frequent changes to page structure break regex patterns
- **IP Blocking**: Repeated requests from the same IP get blocked

#### Goodreads Challenges
- **JavaScript Pagination**: "Show more reviews" buttons require JavaScript execution
- **Limited Initial Load**: Only 10-30 reviews load in the initial HTML
- **Dynamic Content**: Modern React-based interface makes scraping difficult
- **Inconsistent HTML Structure**: Multiple page layouts require different parsing strategies

### Evolution of Our Approach

We've tried multiple approaches to overcome these challenges:

1. **Direct PHP Scraping** (Limited Success)
   - Simple HTTP requests with randomized user agents
   - Basic regex pattern matching for HTML parsing
   - Worked initially but quickly encountered limitations

2. **Enhanced PHP Scraping with Anti-Detection** (Partial Success)
   - Sophisticated user agent rotation
   - Cookie persistence
   - Delayed requests with randomization
   - Multiple fallback strategies

3. **Netlify Serverless Functions with Puppeteer** (Mixed Results)
   - Serverless functions on Netlify using Puppeteer
   - Browser automation to handle JavaScript-rendered content
   - Limited by 250MB size limit and execution time constraints

4. **Third-Party API Services (Outscraper)** (Limited Success)
   - Used Outscraper's API for Goodreads
   - Limited results (typically only 30 reviews per book)
   - No official Amazon endpoint

### Planned VPS Solution

After evaluating all options, we're planning to implement a dedicated VPS running Puppeteer/Playwright:

- **Full Browser Automation**: Complete browser environment with JavaScript execution
- **Persistent Sessions**: Maintain cookies and sessions between requests
- **No Size/Time Limits**: Overcome serverless function constraints
- **Cost-Effective**: ~$10/month vs. expensive commercial scraping services

See `docs/VPS_REVIEW_SCRAPER_IMPLEMENTATION.md` for the detailed implementation plan.

## Current Implementation

### Review Source Prioritization

The `ReviewFetcherFactory` prioritizes sources in the following order:

1. Goodreads (most reliable with current implementation)
2. Other sources (Google Books, Open Library, etc.)
3. Amazon (least reliable, only used if no other sources return reviews)

### Enhanced Goodreads Fetcher

The Goodreads fetcher uses a two-step approach to find books:

1. **OpenLibrary Bridge**: First tries to find the Goodreads ID via OpenLibrary's API
   - Uses `https://openlibrary.org/isbn/{ISBN}.json` to get book data
   - Extracts Goodreads ID from the `identifiers.goodreads` field

2. **Direct Search Fallback**: If OpenLibrary doesn't have the Goodreads ID, falls back to search
   - Uses `https://www.goodreads.com/search?q={ISBN}` to search for the book
   - Applies multiple regex patterns to find the book URL

## Debugging

Enhanced logging has been added to help debug review fetching issues:

- All Goodreads fetcher logs are saved to `services/ReviewFetcher/debug/goodreads-log.txt`
- OpenLibrary responses are saved to `services/ReviewFetcher/debug/openlibrary_{ISBN}_response.json`
- Goodreads search results are saved to `services/ReviewFetcher/debug/goodreads_search_debug.html`
- Amazon CAPTCHA pages are saved to `services/ReviewFetcher/debug/amazon-captcha-{timestamp}.html`

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

Our planned improvements to the review system:

1. **VPS-Based Scraping Solution**:
   - Implement dedicated VPS with Puppeteer/Playwright
   - Create API endpoints for PHP backend integration
   - Add robust caching to minimize scraping frequency
   - Implement session management to avoid login walls

2. **Enhanced Error Handling**:
   - Implement more sophisticated retry mechanisms
   - Add detailed logging for all scraping attempts
   - Create alerting for persistent failures
   - Develop fallback strategies for each source

3. **Performance Optimizations**:
   - Add caching to reduce API calls and improve performance
   - Implement request batching for multiple books
   - Optimize HTML parsing with more efficient patterns
   - Add background processing for large scraping jobs

4. **Data Quality Improvements**:
   - Improve review normalization and deduplication
   - Enhance reviewer name standardization
   - Add sentiment analysis for reviews without explicit ratings
   - Implement better content filtering for inappropriate reviews

See `docs/VPS_REVIEW_SCRAPER_IMPLEMENTATION.md` for the detailed implementation plan for the VPS-based solution.
