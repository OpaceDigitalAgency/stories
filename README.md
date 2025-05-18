# Stories From The Web

A platform for discovering and sharing children's books and stories.

## VPS Server Configuration

The VPS server (37.27.31.107) is configured to:

1. Run the review scraper service using PM2
2. Listen on all interfaces (0.0.0.0) instead of just localhost
3. Auto-deploy changes from GitHub using Git-Auto-Deploy

## Testing Auto-Deployment

This line was added to test the auto-deployment process. When this change is pushed to GitHub, it should trigger the following actions on the VPS:

1. Pull the latest changes from GitHub
2. Run `npm install` in the HeadlessBrowser directory
3. Restart the review-scraper service using PM2

## Review Scraper

The review scraper is a Node.js application that uses Puppeteer to scrape reviews from Goodreads and Amazon. It's configured to:

1. Listen on port 3000
2. Accept requests from any IP address (0.0.0.0)
3. Require an API key for authentication

## Troubleshooting

If the scraper is not returning more than 30 reviews, check:

1. If the server is listening on all interfaces (`0.0.0.0`) instead of just localhost
2. If the API key is correct in both the server and client
3. If the server is reachable from the client (check firewall rules)
4. If the PM2 process is running (`pm2 status`)

## Deployment

Changes are automatically deployed when pushed to the main branch of the GitHub repository. The deployment process:

1. Pulls the latest changes from GitHub
2. Installs dependencies
3. Restarts the PM2 process

## API Endpoints

- `/health` - Returns a 200 OK response if the server is running
- `/scrape/goodreads` - Scrapes reviews from Goodreads
- `/scrape/amazon` - Scrapes reviews from Amazon
Auto-deploy test Sun May 18 08:57:54 BST 2025
Deploy test: Sun May 18 09:01:43 BST 2025
