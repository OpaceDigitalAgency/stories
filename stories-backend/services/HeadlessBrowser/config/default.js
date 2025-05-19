/**
 * Default configuration for the review scraper
 */
module.exports = {
  server: {
    port: 3000,
    host: '0.0.0.0', // Listen on all interfaces to allow external connections
    apiKey: 'stories-scraper-api-key-2023' // API key used by the PHP code
  },
  browser: {
    headless: true,
    userDataDir: './data/browser-data',
    defaultViewport: { width: 1920, height: 1080 }
  },
  cache: {
    enabled: true,
    ttl: 7 * 24 * 60 * 60 * 1000, // 7 days in milliseconds
    dbPath: './data/cache.db'
  },
  scraping: {
    maxConcurrent: 2,
    retries: 3,
    timeout: 60000,
    userAgents: [
      // Desktop browsers
      'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
      'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
      'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:121.0) Gecko/20100101 Firefox/121.0',
      'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Safari/605.1.15',
      'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36 Edg/120.0.0.0',
      // Mobile browsers
      'Mozilla/5.0 (iPhone; CPU iPhone OS 17_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Mobile/15E148 Safari/604.1',
      'Mozilla/5.0 (iPad; CPU OS 17_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Mobile/15E148 Safari/604.1',
      'Mozilla/5.0 (Linux; Android 10; SM-G981B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36',
      'Mozilla/5.0 (Linux; Android 13; Pixel 7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36'
    ]
  },
  sources: {
    goodreads: {
      baseUrl: 'https://www.goodreads.com',
      maxReviews: 200,
      maxPages: 20
    },
    amazon: {
      baseUrl: 'https://www.amazon.co.uk',
      maxReviews: 200,
      maxPages: 20,
      affiliateTag: 'storiesfro0f0-20'
    }
  }
};
