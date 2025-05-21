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
  scrapeGoodreadsReviews
};
