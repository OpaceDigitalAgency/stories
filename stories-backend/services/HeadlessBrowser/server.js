/**
 * Review Scraper Server
 *
 * This server provides API endpoints for scraping book reviews from various sources.
 */
const express = require('express');
const cors = require('cors');
const helmet = require('helmet');
const { RateLimiterMemory } = require('rate-limiter-flexible');
const config = require('./config/default');
const logger = require('./utils/logger');
const browser = require('./utils/browser');
const cache = require('./utils/cache');

// Import scrapers
const goodreads = require('./scrapers/goodreads');
const amazon = require('./scrapers/amazon');

// Initialize Express app
const app = express();

// Middleware
app.use(helmet());
app.use(cors());
app.use(express.json());

// Rate limiter
const rateLimiter = new RateLimiterMemory({
  points: 10, // Number of requests
  duration: 60, // Per minute
});

// API key authentication middleware
const authenticateApiKey = (req, res, next) => {
  const apiKey = req.headers['x-api-key'];

  if (!apiKey || apiKey !== config.server.apiKey) {
    return res.status(401).json({ error: 'Unauthorized: Invalid API key' });
  }

  next();
};

// Rate limiting middleware
const rateLimiterMiddleware = async (req, res, next) => {
  try {
    await rateLimiter.consume(req.ip);
    next();
  } catch (err) {
    res.status(429).json({ error: 'Too many requests' });
  }
};

// Health check endpoint
app.get('/health', (req, res) => {
  res.status(200).json({ status: 'ok' });
});

// Goodreads scraper endpoint
app.get('/scrape/goodreads', authenticateApiKey, rateLimiterMiddleware, async (req, res) => {
  try {
    // Extract parameters from query string, ensuring proper type conversion
    const url = req.query.url;
    const limit = parseInt(req.query.limit || '50', 10);
    const maxPages = parseInt(req.query.maxPages || '20', 10);
    const continueFromLast = req.query.continueFromLast === '1' || req.query.continueFromLast === 'true';
    //const force = req.query.force === '1' || req.query.force === 'true';
    const force = true;
    
    // Log all parameters for debugging
    logger.info(`Goodreads scraper parameters:
      - url: ${url}
      - limit: ${limit}
      - maxPages: ${maxPages}
      - continueFromLast: ${continueFromLast}
      - force: ${force}
    `);

    if (!url) {
      return res.status(400).json({ error: 'Missing URL parameter' });
    }

    logger.info(`Scraping Goodreads reviews for URL: ${url}, force=${force}, maxPages=${maxPages}, continueFromLast=${continueFromLast}`);

    // Extract book ID for caching - handle both /book/show/ and /book/isbn/ formats
    const bookIdMatch = url.match(/\/book\/(?:show|isbn)\/(\d+)/);
    const bookId = bookIdMatch ? bookIdMatch[1] : null;

    logger.info(`Extracted book ID: ${bookId} from URL: ${url}`);

    // Check if we should bypass cache
    if (force === 'true' || force === '1' || force === true) {
      logger.info(`Force parameter set to ${force}, bypassing cache`);

      // If we have a book ID, clear its cache entry
      if (bookId) {
        // Use the db directly to delete the cache entry
        const sqlite3 = require('sqlite3').verbose();
        const path = require('path');
        const dbPath = path.resolve(config.cache.dbPath);
        const db = new sqlite3.Database(dbPath);

        // Clear all cache entries for this book ID (with any cache key format)
        db.run('DELETE FROM reviews_cache WHERE source = ? AND identifier LIKE ?',
               ['goodreads', `${bookId}%`], function(err) {
          if (err) {
            logger.error(`Error clearing cache for ${bookId}: ${err.message}`);
          } else {
            logger.info(`Cleared ${this.changes} cache entries for book ID ${bookId}`);
          }
          db.close();
        });
      }
    } else if ((!force || force === 'false' || force === '0') && bookId) {
      // Check if we have cached data
      const cachedData = await cache.get('goodreads', bookId);
      if (cachedData) {
        const cachedCount = (cachedData.reviews || []).length;
        // Only return cache if NOT continuing from last AND we have at least the requested limit
        if (!continueFromLast && cachedCount >= limit) {
          logger.info(`Cache hit for ${bookId} - returning ${cachedCount} reviews`);
          return res.json(cachedData);
        }
        // Otherwise always continue scraping to fetch next batch
        logger.info(`Cache hit but continuing scrape (continueFromLast=${continueFromLast}, cachedCount=${cachedCount}, limit=${limit})`);
      }
    }


    const reviews = await goodreads.scrapeGoodreadsReviews(url, parseInt(limit), {
      maxPages: parseInt(maxPages),
      continueFromLast: continueFromLast === 'true' || continueFromLast === '1',
      force: force === 'true' || force === '1' || force === true
    });

    res.status(200).json(reviews);
  } catch (error) {
    logger.error(`Error scraping Goodreads reviews: ${error.message}`);
    res.status(500).json({ error: error.message });
  }
});

// Amazon scraper endpoint
app.get('/scrape/amazon', authenticateApiKey, rateLimiterMiddleware, async (req, res) => {
  try {
    // Extract parameters from query string, ensuring proper type conversion
    const asin = req.query.asin;
    const limit = parseInt(req.query.limit || '50', 10);
    const force = req.query.force === '1' || req.query.force === 'true';
    const continueFromLast = req.query.continueFromLast === '1' || req.query.continueFromLast === 'true';
    const maxPages = parseInt(req.query.maxPages || '20', 10);

    // Log all parameters for debugging
    logger.info(`Amazon scraper parameters:
      - asin: ${asin}
      - limit: ${limit}
      - force: ${force}
      - continueFromLast: ${continueFromLast}
      - maxPages: ${maxPages}
    `);

    if (!asin) {
      return res.status(400).json({ error: 'Missing ASIN parameter' });
    }

    logger.info(`Scraping Amazon reviews for ASIN: ${asin}, force=${force}, continueFromLast=${continueFromLast}`);

    // Check if we should bypass cache
    if (force === 'true' || force === '1' || force === true) {
      logger.info(`Force parameter set to ${force}, bypassing cache`);

      // Clear cache for this ASIN
      const sqlite3 = require('sqlite3').verbose();
      const path = require('path');
      const dbPath = path.resolve(config.cache.dbPath);
      const db = new sqlite3.Database(dbPath);

      // Clear all cache entries for this ASIN (with any cache key format)
      db.run('DELETE FROM reviews_cache WHERE source = ? AND identifier LIKE ?',
             ['amazon', `${asin}%`], function(err) {
        if (err) {
          logger.error(`Error clearing cache for ASIN ${asin}: ${err.message}`);
        } else {
          logger.info(`Cleared ${this.changes} cache entries for ASIN ${asin}`);
        }
        db.close();
      });
    }

    const reviews = await amazon.scrapeAmazonReviews(asin, parseInt(limit), {
      continueFromLast,
      maxPages
    });

    res.status(200).json(reviews);
  } catch (error) {
    logger.error(`Error scraping Amazon reviews: ${error.message}`);
    res.status(500).json({ error: error.message });
  }
});

// Clear cache endpoint
app.post('/cache/clear', authenticateApiKey, async (req, res) => {
  try {
    const cleared = await cache.clearExpired();
    res.status(200).json({ message: `Cleared ${cleared} expired cache entries` });
  } catch (error) {
    logger.error(`Error clearing cache: ${error.message}`);
    res.status(500).json({ error: error.message });
  }
});

// Clear specific cache entry endpoint
app.post('/cache/clear/:source/:identifier', authenticateApiKey, async (req, res) => {
  try {
    const { source, identifier } = req.params;

    if (!source || !identifier) {
      return res.status(400).json({ error: 'Missing source or identifier parameter' });
    }

    logger.info(`Clearing cache for ${source}:${identifier}`);

    // Use the db directly to delete the cache entry
    const sqlite3 = require('sqlite3').verbose();
    const path = require('path');
    const dbPath = path.resolve(config.cache.dbPath);
    const db = new sqlite3.Database(dbPath);

    db.run('DELETE FROM reviews_cache WHERE source = ? AND identifier = ?', [source, identifier], function(err) {
      if (err) {
        logger.error(`Error clearing cache for ${source}:${identifier}: ${err.message}`);
        db.close();
        return res.status(500).json({ error: err.message });
      }

      const rowsDeleted = this.changes;
      logger.info(`Cleared ${rowsDeleted} cache entries for ${source}:${identifier}`);
      db.close();

      res.status(200).json({
        success: true,
        message: `Cleared ${rowsDeleted} cache entries for ${source}:${identifier}`
      });
    });
  } catch (error) {
    logger.error(`Error clearing specific cache: ${error.message}`);
    res.status(500).json({ error: error.message });
  }
});

// Logs endpoint
app.get('/logs', authenticateApiKey, async (req, res) => {
  try {
    const fs = require('fs');
    const path = require('path');

    // Get the most recent log files
    const logsDir = path.join(__dirname, 'logs');
    const combinedLogPath = path.join(logsDir, 'combined.log');
    const errorLogPath = path.join(logsDir, 'error.log');

    let logs = [];

    // Read combined log if it exists
    if (fs.existsSync(combinedLogPath)) {
      const combinedLog = fs.readFileSync(combinedLogPath, 'utf8');
      const combinedLines = combinedLog.split('\n').filter(line => line.trim() !== '');

      // Get the last 100 lines
      const lastLines = combinedLines.slice(-100);

      // Parse each line as JSON
      for (const line of lastLines) {
        try {
          const logEntry = JSON.parse(line);
          logs.push({
            timestamp: logEntry.timestamp,
            level: logEntry.level,
            message: logEntry.message
          });
        } catch (err) {
          // If line isn't valid JSON, add it as a raw message
          logs.push({
            timestamp: new Date().toISOString(),
            level: 'info',
            message: line
          });
        }
      }
    }

    // Sort logs by timestamp (newest first)
    logs.sort((a, b) => new Date(b.timestamp) - new Date(a.timestamp));

    res.status(200).json({ logs });
  } catch (error) {
    logger.error(`Error retrieving logs: ${error.message}`);
    res.status(500).json({ error: error.message });
  }
});

// Restart endpoint
app.get('/restart', authenticateApiKey, async (req, res) => {
  try {
    logger.info('Restart requested via API');

    // Send success response before restarting
    res.status(200).json({
      success: true,
      message: 'Restart initiated. The server will restart in 2 seconds.'
    });

    // Wait 2 seconds to allow the response to be sent
    setTimeout(() => {
      process.exit(0); // PM2 will restart the process
    }, 2000);
  } catch (error) {
    logger.error(`Error during restart: ${error.message}`);
    res.status(500).json({ error: error.message });
  }
});

// Error handling middleware
app.use((err, req, res, next) => {
  logger.error(`Unhandled error: ${err.message}`);
  res.status(500).json({ error: 'Internal server error' });
});

// Start the server
const PORT = process.env.PORT || config.server.port;
const HOST = config.server.host || '0.0.0.0';

const server = app.listen(PORT, HOST, () => {
  logger.info(`Server running on http://${HOST}:${PORT}`);
  logger.info(`API Key: ${config.server.apiKey}`);
});

// Graceful shutdown
process.on('SIGTERM', gracefulShutdown);
process.on('SIGINT', gracefulShutdown);

async function gracefulShutdown() {
  logger.info('Received shutdown signal, closing server and resources');

  // Close the server
  server.close(() => {
    logger.info('HTTP server closed');
  });

  // Close the browser
  try {
    await browser.closeBrowser();
  } catch (error) {
    logger.error(`Error closing browser: ${error.message}`);
  }

  // Close the cache
  try {
    cache.close();
  } catch (error) {
    logger.error(`Error closing cache: ${error.message}`);
  }

  // Exit process
  process.exit(0);
}

module.exports = app; // For testing
