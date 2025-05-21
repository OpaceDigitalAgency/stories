/**
 * Goodreads cache management
 */
const cache = require('../../utils/cache');
const logger = require('../../utils/logger');
const sqlite3 = require('sqlite3').verbose();
const path = require('path');
const config = require('../../config/default');

/**
 * Check cache for existing data
 * @param {string} bookId - Goodreads book ID
 * @param {Object} options - Cache options
 * @returns {Object|null} Cached data or null
 */
async function checkCache(bookId, options = {}) {
  const {
    force = false,
    continueFromLast = false,
    limit = 50
  } = options;

  // Check environment variables for force refresh
  const envForce = process.env.VPS_BYPASS_CACHE === 'true' ||
                   process.env.FORCE_FRESH_DATA === 'true' ||
                   process.env.SKIP_CACHE === 'true';

  // Combine force flags
  const shouldForceRefresh = force || envForce;

  // Log cache check parameters with detailed force information
  logger.info(`Cache check for book ${bookId}:
    - Force refresh: ${shouldForceRefresh}
      * Options force: ${force}
      * VPS_BYPASS_CACHE: ${process.env.VPS_BYPASS_CACHE}
      * FORCE_FRESH_DATA: ${process.env.FORCE_FRESH_DATA}
      * SKIP_CACHE: ${process.env.SKIP_CACHE}
    - Continue from last: ${continueFromLast}
    - Required reviews: ${limit}
  `);

  // If force refresh is requested from any source, clear the cache first
  if (shouldForceRefresh) {
    logger.info(`Force refresh requested for book ${bookId} (triggered by: ${
      force ? 'force parameter' :
      process.env.VPS_BYPASS_CACHE === 'true' ? 'VPS_BYPASS_CACHE' :
      process.env.FORCE_FRESH_DATA === 'true' ? 'FORCE_FRESH_DATA' :
      'SKIP_CACHE'
    })`);
    await cache.clear('goodreads', bookId);
  }

  // Try to get cached data with combined force option
  let cachedData = await cache.get('goodreads', bookId, { force: shouldForceRefresh });

  if (cachedData) {
    logger.info(`Cache hit for book ${bookId}:
      - Reviews: ${cachedData.reviews?.length || 0}
      - Last scraped: ${cachedData.lastScrapedAt}
      - Has more: ${cachedData.hasMoreReviews}
    `);

    // If continuing from last scrape, return cache regardless
    if (continueFromLast) {
      logger.info(`Returning cached data for continuation (${cachedData.reviews?.length} reviews)`);
      return cachedData;
    }

    // Otherwise, only return if we have enough reviews
    const hasEnoughReviews = cachedData.reviews?.length >= limit;
    if (hasEnoughReviews) {
      logger.info(`Using cached data (${cachedData.reviews.length} reviews >= limit ${limit})`);
      return cachedData;
    }

    logger.info(`Cache has insufficient reviews (${cachedData.reviews?.length} < ${limit})`);
  } else {
    logger.info(`No cached data found for book ${bookId}`);
  }

  return null;
}

/**
 * Clear cache for a specific book
 * @param {string} bookId - Goodreads book ID
 */
// Function removed as we now use cache.clear() directly

/**
 * Save data to cache
 * @param {string} bookId - Goodreads book ID
 * @param {Object} data - Data to cache
 */
async function saveToCache(bookId, data) {
  logger.info(`Saving data to cache for book ${bookId}:
    - Reviews: ${data.reviews?.length || 0}
    - Has more: ${data.hasMoreReviews}
  `);

  const cacheData = {
    ...data,
    lastScrapedAt: new Date().toISOString()
  };

  try {
    await cache.set('goodreads', bookId, cacheData);
    logger.info(`Successfully cached data for book ${bookId}`);

    // Verify the cache was set
    const verifyCachedData = await cache.get('goodreads', bookId);
    if (verifyCachedData) {
      logger.info(`Cache verification successful. Cached ${verifyCachedData.reviews?.length} reviews.`);
    } else {
      logger.warn(`Cache verification failed. Could not retrieve cached data.`);
    }
  } catch (error) {
    logger.error(`Error saving to cache for ${bookId}: ${error.message}`);
  }
}

module.exports = {
  checkCache,
  clearCache,
  saveToCache
};