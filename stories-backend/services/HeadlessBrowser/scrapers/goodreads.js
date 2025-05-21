let scrapeGoodreadsReviews;
setImmediate(() => {
  scrapeGoodreadsReviews = require('./goodreads').scrapeGoodreadsReviews;
});

// Export the main scraping function
module.exports = {
  async scrapeGoodreadsReviews(req, res) {
    if (!scrapeGoodreadsReviews) {
      return res.status(500).json({ error: 'scrapeGoodreadsReviews is not ready due to circular dependency' });
    }

    try {
      const url = req.query.url;
      const limit = parseInt(req.query.limit, 10) || 10;
      const maxPages = parseInt(req.query.maxPages, 10) || 5;
      const continueFromLast = req.query.continueFromLast === '1' || req.query.continueFromLast === 'true';
      const forceRaw = req.query.force;
      const force = forceRaw === '1' || forceRaw === 'true';

      console.log(`[Scraper API] Incoming scrape request:`, {
        url,
        limit,
        maxPages,
        continueFromLast,
        forceRaw,
        parsedForce: force
      });

      const reviews = await scrapeGoodreadsReviews(url, limit, {
        maxPages,
        continueFromLast,
        skipDbCheck: true,
        force: force
      });

      res.json(reviews);
    } catch (err) {
      console.error(`[Scraper API] Error in scrapeGoodreadsReviews handler: ${err.message}`);
      res.status(500).json({ error: err.message });
    }
  }
};