/**
 * Cache utility for storing and retrieving scraped reviews
 */
const sqlite3 = require('sqlite3').verbose();
const path = require('path');
const fs = require('fs');
const config = require('../config/default');
const logger = require('./logger');

// Ensure data directory exists
const dataDir = path.dirname(config.cache.dbPath);
if (!fs.existsSync(dataDir)) {
  fs.mkdirSync(dataDir, { recursive: true });
}

// Initialize database
const dbPath = path.resolve(config.cache.dbPath);
const db = new sqlite3.Database(dbPath, (err) => {
  if (err) {
    logger.error(`Error opening cache database: ${err.message}`);
  } else {
    logger.info(`Connected to cache database at ${dbPath}`);

    // Create tables if they don't exist
    db.run(`
      CREATE TABLE IF NOT EXISTS reviews_cache (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        source TEXT NOT NULL,
        identifier TEXT NOT NULL,
        data TEXT NOT NULL,
        created_at INTEGER NOT NULL,
        expires_at INTEGER NOT NULL,
        UNIQUE(source, identifier)
      )
    `);
  }
});

const cache = {
  /**
   * Get cached reviews for a source and identifier
   * @param {string} source - The source (e.g., 'goodreads', 'amazon')
   * @param {string} identifier - The identifier (e.g., URL, ASIN)
   * @param {Object} options - Additional options
   * @returns {Promise<Object|null>} - Cached data or null if not found/expired
   */
  get: (source, identifier, options = {}) => {
    return new Promise((resolve, reject) => {
      // Normalize force parameter - accept boolean true, string 'true', or '1'
      const force = options.force === true || options.force === 'true' || options.force === '1';

      // Always return null if cache is disabled or force refresh is requested
      if (!config.cache.enabled || force) {
        logger.info(`Cache disabled or force refresh requested for ${source}:${identifier} (force=${JSON.stringify(options.force)})`);
        return resolve(null);
      }

      const now = Date.now();

      db.get(
        'SELECT data, expires_at FROM reviews_cache WHERE source = ? AND identifier = ? AND expires_at > ?',
        [source, identifier, now],
        (err, row) => {
          if (err) {
            logger.error(`Cache get error: ${err.message}`);
            return resolve(null);
          }

          if (row) {
            logger.info(`Cache hit for ${source}:${identifier}`);
            try {
              const data = JSON.parse(row.data);
              return resolve(data);
            } catch (e) {
              logger.error(`Error parsing cached data: ${e.message}`);
              return resolve(null);
            }
          } else {
            logger.info(`Cache miss for ${source}:${identifier}`);
            return resolve(null);
          }
        }
      );
    });
  },

  /**
   * Store data in the cache
   * @param {string} source - The source (e.g., 'goodreads', 'amazon')
   * @param {string} identifier - The identifier (e.g., URL, ASIN)
   * @param {Object} data - The data to cache
   * @param {number} ttl - Time to live in milliseconds (optional, defaults to config)
   * @returns {Promise<boolean>} - Whether the operation was successful
   */
  set: (source, identifier, data, ttl = null) => {
    return new Promise((resolve, reject) => {
      if (!config.cache.enabled) {
        return resolve(false);
      }

      const now = Date.now();
      const expiresAt = now + (ttl || config.cache.ttl);

      try {
        const jsonData = JSON.stringify(data);

        db.run(
          `INSERT OR REPLACE INTO reviews_cache
           (source, identifier, data, created_at, expires_at)
           VALUES (?, ?, ?, ?, ?)`,
          [source, identifier, jsonData, now, expiresAt],
          function(err) {
            if (err) {
              logger.error(`Cache set error: ${err.message}`);
              return resolve(false);
            }

            logger.info(`Cached ${source}:${identifier} (expires ${new Date(expiresAt).toISOString()})`);
            return resolve(true);
          }
        );
      } catch (e) {
        logger.error(`Error stringifying data for cache: ${e.message}`);
        return resolve(false);
      }
    });
  },

  /**
   * Clear a specific cache entry
   * @param {string} source - The source (e.g., 'goodreads', 'amazon')
   * @param {string} identifier - The identifier (e.g., URL, ASIN)
   * @returns {Promise<boolean>} - Whether the operation was successful
   */
  clear: (source, identifier) => {
    return new Promise((resolve, reject) => {
      db.run(
        'DELETE FROM reviews_cache WHERE source = ? AND identifier = ?',
        [source, identifier],
        function(err) {
          if (err) {
            logger.error(`Cache clear error for ${source}:${identifier}: ${err.message}`);
            return resolve(false);
          }

          logger.info(`Cleared cache entry for ${source}:${identifier}`);
          return resolve(true);
        }
      );
    });
  },

  /**
   * Clear expired cache entries
   * @returns {Promise<number>} - Number of entries cleared
   */
  clearExpired: () => {
    return new Promise((resolve, reject) => {
      const now = Date.now();

      db.run(
        'DELETE FROM reviews_cache WHERE expires_at <= ?',
        [now],
        function(err) {
          if (err) {
            logger.error(`Cache clear error: ${err.message}`);
            return resolve(0);
          }

          logger.info(`Cleared ${this.changes} expired cache entries`);
          return resolve(this.changes);
        }
      );
    });
  },

  /**
   * Close the database connection
   */
  close: () => {
    db.close((err) => {
      if (err) {
        logger.error(`Error closing cache database: ${err.message}`);
      } else {
        logger.info('Cache database connection closed');
      }
    });
  }
};

// Periodically clear expired cache entries
setInterval(() => {
  cache.clearExpired().catch(err => {
    logger.error(`Error in cache cleanup: ${err.message}`);
  });
}, 24 * 60 * 60 * 1000); // Once a day

module.exports = cache;
