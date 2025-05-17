# Amazon Reviews Puppeteer Service

This Netlify serverless function provides a remote Puppeteer service for scraping Amazon reviews. It's designed to work around the login walls that Amazon has implemented to block traditional scraping methods.

## How It Works

1. The function uses Puppeteer and Chromium in a serverless environment to navigate to Amazon review pages
2. It handles anti-bot measures by using proper headers and browser settings
3. It extracts reviews using regex patterns similar to the PHP scraper
4. It returns the reviews in a format compatible with the existing AmazonReviewFetcher class

## API Usage

### Endpoint

```
POST /.netlify/functions/amazon-reviews
```

### Request Format

```json
{
  "asin": "1234567890",
  "limit": 10,
  "domain": "www.amazon.co.uk"
}
```

### Response Format

```json
{
  "reviews": [
    {
      "source_id": 1,
      "reviewer_name": "John Doe",
      "review_date": "2023-01-15",
      "original_rating": "4.0/5",
      "rating_value": 4.0,
      "rating_scale": 5,
      "rating_normalised": 0.8,
      "review_text": "This is a great book...",
      "review_title": "Great read!",
      "metadata": "{\"asin\":\"1234567890\",\"review_url\":\"https://www.amazon.co.uk/product-reviews/1234567890\",\"affiliate_url\":\"https://www.amazon.co.uk/dp/1234567890?tag=storiesfro0f0-20\"}"
    }
  ],
  "total": 1
}
```

## Integration with PHP Backend

The PHP backend has been updated to call this function first when fetching Amazon reviews. If this function fails or returns no reviews, the backend will fall back to other methods:

1. Try Remote Puppeteer API (this function)
2. If that fails, try Outscraper API
3. If that fails, try direct scraping
4. If that fails, use aggregate rating

## Deployment

This function is automatically deployed when changes are pushed to the repository. The function uses the following environment:

- Node.js runtime
- Chromium browser (via @sparticuz/chromium)
- Puppeteer Core

## Troubleshooting

If the function fails to scrape reviews, check the following:

1. Look at the debug logs in the Netlify function logs
2. Check if Amazon has changed their page structure
3. Verify that the ASIN is valid
4. Try a different domain (e.g., amazon.com instead of amazon.co.uk)

## Limitations

- The function has a 60-second timeout
- It may still be blocked by Amazon's anti-bot measures in some cases
- It can only fetch a limited number of reviews per request
