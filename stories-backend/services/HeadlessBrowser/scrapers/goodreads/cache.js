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
    continueFromLast = false
  } = options;

  // Log cache check parameters
  logger.info(`Checking cache for book ${bookId}:
    - Force refresh: ${force}
    - Continue from last: ${continueFromLast}
  `);

  // If force refresh is requested, clear the cache first
  if (force) {
    await clearCache(bookId);
    logger.info(`Cache cleared for book ${bookId} (force refresh requested)`);
    return null;
  }

  // Try to get cached data
  const cachedData = await cache.get('goodreads', bookId);

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
    const hasEnoughReviews = cachedData.reviews?.length >= (options.limit || 50);
    if (hasEnoughReviews) {
      logger.info(`Using cached data (${cachedData.reviews.length} reviews >= limit ${options.limit})`);
      return cachedData;
    }

    logger.info(`Cache has insufficient reviews (${cachedData.reviews?.length} < ${options.limit})`);
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
    // Clear all cache entries for this book ID (with any cache key format)
    await new Promise((resolve, reject) => {
      db.run('DELETE FROM reviews_cache WHERE source = ? AND identifier LIKE ?',
        ['goodreads', `${bookId}%`],
        function(err) {
          if (err) {
            reject(err);
          } else {
            logger.info(`Cleared ${this.changes} cache entries for book ${bookId}`);
            resolve(this.changes);
          }
        }
      );
    });
  } catch (error) {
    logger.error(`Error clearing cache for ${bookId}: ${error.message}`);
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