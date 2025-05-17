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
    const { url, limit = 50 } = req.query;
    
    if (!url) {
      return res.status(400).json({ error: 'Missing URL parameter' });
    }
    
    logger.info(`Scraping Goodreads reviews for URL: ${url}`);
    const reviews = await goodreads.scrapeGoodreadsReviews(url, parseInt(limit));
    
    res.status(200).json(reviews);
  } catch (error) {
    logger.error(`Error scraping Goodreads reviews: ${error.message}`);
    res.status(500).json({ error: error.message });
  }
});

// Amazon scraper endpoint
app.get('/scrape/amazon', authenticateApiKey, rateLimiterMiddleware, async (req, res) => {
  try {
    const { asin, limit = 50 } = req.query;
    
    if (!asin) {
      return res.status(400).json({ error: 'Missing ASIN parameter' });
    }
    
    logger.info(`Scraping Amazon reviews for ASIN: ${asin}`);
    const reviews = await amazon.scrapeAmazonReviews(asin, parseInt(limit));
    
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

// Error handling middleware
app.use((err, req, res, next) => {
  logger.error(`Unhandled error: ${err.message}`);
  res.status(500).json({ error: 'Internal server error' });
});

// Start the server
const PORT = process.env.PORT || config.server.port;
const HOST = process.env.HOST || config.server.host;

const server = app.listen(PORT, HOST, () => {
  logger.info(`Server running on http://${HOST}:${PORT}`);
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
