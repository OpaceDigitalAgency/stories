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

  // Log cache check parameters
  logger.info(`Cache check for book ${bookId}:
    - Force refresh: ${force}
    - Continue from last: ${continueFromLast}
    - Required reviews: ${limit}
  `);

  // If force refresh is requested, clear the cache and return null
  if (force) {
    logger.info(`Force refresh requested for book ${bookId}`);
    await clearCache(bookId);
    return null;
  }

  // Try to get cached data
  let cachedData = await cache.get('goodreads', bookId);

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
async function clearCache(bookId) {
  logger.info(`Clearing cache for book ${bookId}`);

  const dbPath = path.resolve(config.cache.dbPath);
  const db = new sqlite3.Database(dbPath);

  try {
    // Delete all cache entries for this book using pattern matching
    const deletePromises = [
      `goodreads:${bookId}`,
      `goodreads:${bookId}:%`,
      `${bookId}`,
      `${bookId}:%`
    ].map(pattern => {
      return new Promise((resolve, reject) => {
        db.run(
          'DELETE FROM reviews_cache WHERE source = ? AND identifier LIKE ?',
          ['goodreads', pattern],
          function(err) {
            if (err) {
              reject(err);
            } else {
              logger.info(`Cleared ${this.changes} entries matching ${pattern}`);
              resolve(this.changes);
            }
          }
        );
      });
    });

    const results = await Promise.all(deletePromises);
    const totalCleared = results.reduce((sum, count) => sum + count, 0);
    logger.info(`Successfully cleared ${totalCleared} total cache entries for book ${bookId}`);

  } catch (error) {
    logger.error(`Error clearing cache for ${bookId}: ${error.message}`);
    throw error;
  } finally {
    db.close();
  }
}

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