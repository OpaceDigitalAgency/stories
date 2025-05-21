/**
 * Goodreads scraper entry point
 * 
 * This file has been refactored into modular components:
 * - selectors.js: HTML selectors and extraction
 * - graphql.js: GraphQL queries and processing
 * - cache.js: Cache management
 * - index.js: Main scraping logic
 */

const { scrapeGoodreadsReviews } = require('./goodreads/index');

// Export the main scraping function
module.exports = {
  async scrapeGoodreadsReviews(req, res) {
    const url = req.query.url;
    const limit = parseInt(req.query.limit, 10) || 10;
    const maxPages = parseInt(req.query.maxPages, 10) || 5;
    const continueFromLast = req.query.continueFromLast === '1' || req.query.continueFromLast === 'true';
    const force = req.query.force === '1' || req.query.force === 'true';

    const reviews = await scrapeGoodreadsReviews(url, limit, {
      maxPages,
      continueFromLast,
      skipDbCheck: true,
      force
    });

    res.json(reviews);
  }
};
