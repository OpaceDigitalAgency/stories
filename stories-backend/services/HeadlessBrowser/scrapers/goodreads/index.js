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
  // Parse force parameter from query string if present
  const urlObj = new URL(goodreadsUrl);
  const forceParam = urlObj.searchParams.get('force') === '1';
  const envForce = process.env.VPS_BYPASS_CACHE === 'true' || process.env.FORCE_FRESH_DATA === 'true';
  const maxPages = options.maxPages ?? 100;
  const continueFromLast = options.continueFromLast ?? false;
  const force = typeof options.force === 'boolean' ? options.force : (forceParam || envForce);

  // Log force parameter sources for debugging
  logger.info(`Force refresh sources:
    - URL parameter: ${forceParam}
    - Environment variables: ${envForce}
    - VPS_BYPASS_CACHE: ${process.env.VPS_BYPASS_CACHE}
    - FORCE_FRESH_DATA: ${process.env.FORCE_FRESH_DATA} 
    - Final force value: ${force}
  `);

  // Initialize steps array for detailed logging
  const steps = [];
  const addStep = (name, status, message, details = {}) => {
    steps.push({
      name,
      status,
      message,
      fetch_url: details.url,
      response: details.response_length,
      details
    });
    logger.info(`[${name}] ${status}: ${message}`);
  };

  addStep('scrape_start', 'info', 'Starting Goodreads scrape', {
    url: goodreadsUrl,
    limit,
    maxPages,
    continueFromLast,
    force
  });

  // Extract book ID for caching
  const bookId = extractBookId(goodreadsUrl);
  if (!bookId) {
    throw new Error(`Invalid Goodreads URL: ${goodreadsUrl}`);
  }

  // Check cache with detailed logging
  logger.info(`Checking cache for book ${bookId}`);
  logger.info(`Force refresh: ${force}, Force param from URL: ${forceParam}`);
  const cachedData = await checkCache(bookId, { force, continueFromLast, limit });

  // Handle cache result
  if (cachedData) {
    logger.info(`Found cached data for book ${bookId}:
      - Reviews: ${cachedData.reviews?.length || 0}
      - Last scraped: ${cachedData.lastScrapedAt}
      - Has more: ${cachedData.hasMoreReviews}
      - Force refresh: ${force}
    `);

    if (!force) {
      // If continuing from last scrape, return cache
      if (continueFromLast) {
        logger.info(`Using cached data for continuation - ${cachedData.reviews?.length} reviews available`);
        return { ...cachedData, source: 'cache' };
      }
      
      // If we have enough reviews, return cache
      const hasEnoughReviews = cachedData.reviews?.length >= limit;
      if (hasEnoughReviews) {
        logger.info(`Using cached data - ${cachedData.reviews.length} reviews >= requested limit ${limit}`);
        return { ...cachedData, source: 'cache' };
      }
      
      logger.info(`Cache has insufficient reviews (${cachedData.reviews?.length} < ${limit}) - proceeding with scrape`);
    } else {
      logger.info(`Force refresh requested - ignoring cache and proceeding with scrape`);
    }
  } else {
    logger.info(`No cached data found for book ${bookId} - proceeding with scrape`);
  }

  // Initialize browser and navigate to page
  const page = await browser.getNewPage();
  try {
    logger.info(`Navigating to ${goodreadsUrl}`);
    await page.goto(goodreadsUrl, { waitUntil: 'networkidle2', timeout: 30000 });

    // Take screenshot for debugging
    await browser.takeScreenshot(page, `goodreads-initial-${bookId}`);

    // Extract metadata from HTML with detailed logging
    logger.info('Extracting metadata from page HTML...');
    // Extract metadata using the proper function from selectors.js
    const metadata = await page.evaluate(({ SELECTORS, extractBookMetadata }) => {
      // Make SELECTORS available in page context
      window.SELECTORS = SELECTORS;
      return extractBookMetadata();
    }, { SELECTORS, extractBookMetadata });
    if (!metadata) {
      addStep('metadata_extraction', 'error', 'Failed to extract metadata from page');
      throw new Error('Failed to extract metadata from page');
    }

    // Log extracted metadata with raw values for debugging
    addStep('metadata_extraction', 'success', 'Successfully extracted metadata', {
      metadata: {
        ...metadata,
        _raw: metadata._raw
      }
    });

    // Validate required fields
    const requiredFields = ['title', 'author'];
    const missingFields = requiredFields.filter(field => !metadata[field]);
    if (missingFields.length > 0) {
      addStep('metadata_validation', 'error', `Missing required fields: ${missingFields.join(', ')}`);
      throw new Error(`Missing required metadata fields: ${missingFields.join(', ')}`);
    }

    // Extract work ID for GraphQL queries
    addStep('work_id_extraction', 'in_progress', 'Extracting work ID for GraphQL queries');
    const html = await page.content();
    const workId = extractWorkId(html);
    if (!workId) {
      addStep('work_id_extraction', 'error', 'Failed to extract work ID');
      throw new Error('Could not extract work ID');
    }
    addStep('work_id_extraction', 'success', 'Successfully extracted work ID', { workId });

    // Initialize reviews array with cached reviews if continuing
    let reviews = cachedData?.reviews || [];
    let nextCursor = cachedData?.graphql_state?.next_token;
    let hasMore = true;
    let pageCount = cachedData?.graphql_state?.current_page || 0;

    // Fetch reviews via GraphQL
    while (hasMore && pageCount < maxPages && (continueFromLast || reviews.length < limit)) {
      pageCount++;
      addStep('graphql_request', 'in_progress', `Fetching GraphQL page ${pageCount}/${maxPages}`, {
        workId,
        cursor: nextCursor
      });

      const result = await makeGraphQLRequest(page, workId, nextCursor);
      if (!result) {
        addStep('graphql_request', 'error', 'GraphQL request failed');
        break;
      }
      
      addStep('graphql_request', 'success', 'Successfully fetched GraphQL data', {
        new_reviews: result.reviews.length,
        has_more: result.hasMore,
        metadata_updated: Object.keys(result.metadata || {}).length > 0
      });

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
    addStep('cache_save', 'in_progress', 'Saving results to cache');
    await saveToCache(bookId, result);
    addStep('cache_save', 'success', 'Successfully saved to cache', {
      reviews_count: result.reviews.length,
      metadata_fields: Object.keys(result).filter(k => k !== 'reviews').length
    });

    // Add steps to result
    result.steps = steps;
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