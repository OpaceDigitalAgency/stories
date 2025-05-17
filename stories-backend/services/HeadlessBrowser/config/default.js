/**
 * Default configuration for the review scraper
 */
module.exports = {
  server: {
    port: 3000,
    host: 'localhost',
    apiKey: 'your-secret-api-key-here' // Change this in production
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
      'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
      'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.1.1 Safari/605.1.15',
      'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:89.0) Gecko/20100101 Firefox/89.0',
      'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.159 Safari/537.36',
      'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.159 Safari/537.36',
      'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.159 Safari/537.36',
      'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:91.0) Gecko/20100101 Firefox/91.0'
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
