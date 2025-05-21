const { scrapeGoodreadsReviews: performScrape } = require('./goodreads/index.js');

// Export the main scraping function
module.exports = {
  async scrapeGoodreadsReviews(req, res) {
    try {
      const url = req.query.url;
      const limit = parseInt(req.query.limit, 10) || 10;
      const maxPages = parseInt(req.query.maxPages, 10) || 5;
      const continueFromLast = req.query.continueFromLast === '1' || req.query.continueFromLast === 'true';
      const force = req.query.force === '1' || req.query.force === 'true';

      console.log(`[Scraper API] Incoming scrape request:`, {
        url,
        limit,
        maxPages,
        continueFromLast,
        force
      });

      const reviews = await performScrape(url, limit, {
        maxPages,
        continueFromLast,
        skipDbCheck: true,
        force
      });

      

      res.json(reviews);
    } catch (err) {
      console.error(`[Scraper API] Error in scrapeGoodreadsReviews handler: ${err.message}`);
      res.status(500).json({ error: err.message });
    }
  }
};