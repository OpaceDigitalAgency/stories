/**
 * Goodreads scraper main module
 */
const browser = require('../../utils/browser');
const logger = require('../../utils/logger');
const { extractBookMetadata } = require('./selectors');
const { makeGraphQLRequest } = require('./graphql');
const { checkCache, saveToCache } = require('./cache');

/**
 * Extract work ID from HTML content
 * @param {string} html - HTML content
 * @returns {string|null} Work ID
 */
function extractWorkId(html) {
  const workIdMatch = html.match(/kca:\/\/work\/amzn1\.gr\.work\.v1\.[a-zA-Z0-9]+/);
  if (workIdMatch) {
    const workId = workIdMatch[0].replace('kca://', '');
    logger.info(`Extracted work ID: ${workId}`);
    return workId;
  }
  logger.warn('Could not extract work ID from HTML');
  return null;
}

/**
 * Extract book ID from Goodreads URL
 * @param {string} url - Goodreads book URL
 * @returns {string|null} Book ID
 */
function extractBookId(url) {
  // Try ISBN URL format
  const isbnMatch = url.match(/\/book\/isbn\/(\d+)/);
  if (isbnMatch?.[1]) {
    logger.info(`Extracted ISBN ${isbnMatch[1]} from URL`);
    return isbnMatch[1];
  }

  // Try show URL format
  const showMatch = url.match(/\/book\/show\/(\d+)(?:[.-]|$)/);
  if (showMatch?.[1]) {
    logger.info(`Extracted book ID ${showMatch[1]} from URL`);
    return showMatch[1];
  }

  logger.warn(`Could not extract book ID from URL: ${url}`);
  return null;
}

/**
 * Scrape reviews and metadata from Goodreads
 * @param {string} goodreadsUrl - URL of the book page
 * @param {number} limit - Maximum number of reviews to fetch
 * @param {Object} options - Scraping options
 */
async function scrapeGoodreadsReviews(goodreadsUrl, limit = 50, options = {}) {
  const {
    maxPages = 100,
    continueFromLast = false,
    force = false
  } = options;

  logger.info(`Starting Goodreads scrape:
    - URL: ${goodreadsUrl}
    - Limit: ${limit}
    - Max pages: ${maxPages}
    - Continue from last: ${continueFromLast}
    - Force refresh: ${force}
  `);

  // Extract book ID for caching
  const bookId = extractBookId(goodreadsUrl);
  if (!bookId) {
    throw new Error(`Invalid Goodreads URL: ${goodreadsUrl}`);
  }

  // Check cache unless force refresh requested
  const cachedData = await checkCache(bookId, { force, continueFromLast, limit });
  if (cachedData && !force && !continueFromLast) {
    logger.info(`Using cached data for book ${bookId}`);
    return { ...cachedData, source: 'cache' };
  }

  // Initialize browser and navigate to page
  const page = await browser.getNewPage();
  try {
    logger.info(`Navigating to ${goodreadsUrl}`);
    await page.goto(goodreadsUrl, { waitUntil: 'networkidle2', timeout: 30000 });

    // Take screenshot for debugging
    await browser.takeScreenshot(page, `goodreads-initial-${bookId}`);

    // Extract metadata from HTML
    const metadata = await page.evaluate(extractBookMetadata);
    logger.info('Extracted initial metadata:', metadata);

    // Extract work ID for GraphQL queries
    const html = await page.content();
    const workId = extractWorkId(html);
    if (!workId) {
      throw new Error('Could not extract work ID');
    }

    // Initialize reviews array with cached reviews if continuing
    let reviews = cachedData?.reviews || [];
    let nextCursor = cachedData?.graphql_state?.next_token;
    let hasMore = true;
    let pageCount = cachedData?.graphql_state?.current_page || 0;

    // Fetch reviews via GraphQL
    while (hasMore && pageCount < maxPages && (continueFromLast || reviews.length < limit)) {
      pageCount++;
      logger.info(`Fetching GraphQL page ${pageCount}/${maxPages}`);

      const result = await makeGraphQLRequest(page, workId, nextCursor);
      if (!result) {
        logger.warn('GraphQL request failed');
        break;
      }

      // Merge metadata
      Object.assign(metadata, result.metadata);

      // Add new reviews
      const newReviews = result.reviews.filter(review => {
        // Skip duplicates when continuing from cache
        if (continueFromLast) {
          return !reviews.some(existing => 
            existing.reviewer_name === review.reviewer_name &&
            existing.review_text.substring(0, 50) === review.review_text.substring(0, 50)
          );
        }
        return true;
      });

      reviews = [...reviews, ...newReviews];
      logger.info(`Added ${newReviews.length} reviews, total: ${reviews.length}`);

      hasMore = result.hasMore;
      nextCursor = result.nextCursor;

      // Add small delay between requests
      await page.waitForTimeout(1000);
    }

    // Prepare final result
    const result = {
      source: 'scrape',
      ...metadata,
      reviews: reviews.slice(0, limit),
      total: reviews.length,
      hasMoreReviews: hasMore || reviews.length > limit,
      graphql_state: {
        current_page: pageCount,
        next_token: nextCursor,
        total_fetched: reviews.length
      }
    };

    // Save to cache
    await saveToCache(bookId, result);

    return result;

  } catch (error) {
    logger.error(`Error scraping Goodreads: ${error.message}`);
    throw error;
  } finally {
    await page.close();
  }
}

module.exports = {
  scrapeGoodreadsReviews
};