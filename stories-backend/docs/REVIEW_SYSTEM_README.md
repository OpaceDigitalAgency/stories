# Book Reviews System - Developer Guide

This document provides a practical guide for developers working with the book reviews system. It covers how to use the system, common tasks, and troubleshooting tips.

## Table of Contents
1. [System Overview](#system-overview)
2. [Admin Interface](#admin-interface)
3. [Review Scraping](#review-scraping)
4. [AI Analysis](#ai-analysis)
5. [Database Structure](#database-structure)
6. [Adding New Review Sources](#adding-new-review-sources)
7. [Troubleshooting](#troubleshooting)

## System Overview

The book reviews system collects, normalizes, and displays reviews from various sources. It includes:

- **Admin Interface**: Manage reviews through the Book Import Tool
- **Review Scraping**: Fetch reviews from external sources like Amazon, Goodreads, etc.
- **AI Analysis**: Analyze reviews for age-appropriateness and content flags
- **Database Structure**: Store reviews and aggregate metrics
- **Frontend Components**: Display reviews with filtering options

For a comprehensive architectural overview, see [reviews_system_architecture.md](reviews_system_architecture.md).

## Admin Interface

### Accessing the Reviews Management

1. Navigate to `Admin > Content > Book Import Tool`
2. Click on the "Reviews" tab

### Key Features

- **View Reviews**: Browse all reviews with pagination
- **Filter Reviews**: Filter by book, source, rating, or search text
- **Edit Reviews**: Click the edit icon to modify a review
- **Delete Reviews**: Remove individual reviews or use bulk actions
- **Analyze Reviews**: Run AI analysis on reviews to detect age-related content

### Bulk Actions

1. Select reviews using the checkboxes
2. Choose an action from the "Bulk Actions" dropdown
3. Click "Apply"

Available bulk actions:
- **Delete**: Remove selected reviews
- **Analyze**: Run AI analysis on selected reviews

## Review Scraping

### Scraping Reviews for a Book

1. Navigate to `Admin > Content > Book Import Tool`
2. In the "Existing Books" tab, find the book you want to scrape reviews for
3. Click the "Scrape Reviews" button
4. Select the sources you want to scrape from
5. Click "Start Scraping"

### Available Sources

- **Amazon**: Scrapes reviews from Amazon product pages
- **Goodreads**: Fetches reviews from Goodreads
- **Google Books**: Gets reviews from Google Books API
- **Open Library**: Retrieves reviews from Open Library

### Adding New Sources

To add a new review source:

1. Run the `setup_review_sources.sql` script in phpMyAdmin
2. Or manually add a new entry to the `review_sources` table
3. Implement a new fetcher class in `services/ReviewFetcher/`

## AI Analysis

The system uses OpenAI to analyze reviews for age-appropriateness and content flags.

### Running AI Analysis

1. Edit a review and check the "Run AI analysis" checkbox
2. Or use the bulk action "Analyze" on multiple reviews

### What the AI Analyzes

- **Age-Related Content**: Mentions of age-appropriateness
- **Content Flags**: Violence, scary content, mature themes, etc.
- **Suitability Score**: A normalized score for age-appropriateness

### Configuration

The AI analysis uses the OpenAI API key set in the environment variable `OPENAI_API_KEY`.

## Database Structure

### Main Tables

- **`reviews`**: Individual book reviews
- **`review_sources`**: Sources of reviews
- **`directory_items`**: Books with aggregate review metrics

### Key Fields in `reviews` Table

- `book_id`: Foreign key to directory_items
- `source_id`: Foreign key to review_sources
- `reviewer_name`: Name of the reviewer
- `rating_normalised`: Normalized rating (0-1 scale)
- `review_text`: The review content
- `ai_analysis`: JSON data from AI analysis

### Aggregate Fields in `directory_items` Table

- `review_count`: Number of reviews
- `average_rating`: Average normalized rating (0-1)
- `highest_rating`: Highest normalized rating (0-1)
- `lowest_rating`: Lowest normalized rating (0-1)

## Adding New Review Sources

### Step 1: Add to Database

Run the `setup_review_sources.sql` script or manually add to the `review_sources` table:

```sql
INSERT INTO review_sources (name, url, is_third_party)
VALUES ('New Source Name', 'https://example.com', 1);
```

### Step 2: Create Fetcher Class

Create a new class in `services/ReviewFetcher/` that implements the `ReviewFetcherInterface`:

```php
<?php
namespace Services\ReviewFetcher;

class NewSourceReviewFetcher extends AbstractReviewFetcher {
    public function __construct(PDO $db, int $sourceId) {
        parent::__construct($db, $sourceId, 'New Source Name');
    }

    public function isConfigured(): bool {
        return true;
    }

    public function fetchReviewsByISBN(string $isbn, int $limit = 10): array {
        // Implementation here
    }
}
```

### Step 3: Update Factory

Add your new fetcher to the `ReviewFetcherFactory` class:

```php
// In ReviewFetcherFactory.php
switch (strtolower($source['name'])) {
    // Existing cases...

    case 'new source name':
        $fetcher = new NewSourceReviewFetcher($this->db, $sourceId);
        break;
}
```

## Troubleshooting

### Common Issues

#### No Reviews Found When Scraping

- Check that the ISBN is correct and exists in the source
- Verify that the source is properly configured
- Check for API rate limiting or blocking
- Try using the "Force Fresh Data" button to bypass cache

#### Force Fresh Data Button Not Working

If the "Force Fresh Data" button isn't bypassing the cache:

- Ensure the force parameter is properly passed from PHP to Node.js
- Check that `options['force'] = true` is set in GoodreadsReviewFetcher.php
- Verify that server.js properly normalizes the force parameter
- Check the logs for force parameter values

#### AI Analysis Not Working

- Ensure the `OPENAI_API_KEY` environment variable is set
- Check for API quota limitations
- Verify that the review text is not empty

#### Duplicate Reviews

The system checks for duplicates based on:
- Same book_id
- Same source_id
- Same reviewer_name (case-insensitive, trimmed)

If you're seeing duplicates, check if there are slight variations in these fields.

### Debugging

- Check the PHP error log for detailed error messages
- Use the logging in the scraping and bulk action pages
- For API issues, check the network tab in browser dev tools

## Further Reading

- [Full System Architecture](reviews_system_architecture.md)
- [Database Schema](database-schema.md)
- [API Documentation](api-documentation.md)

## Contributing

When making changes to the review system:

1. Update the documentation in `reviews_system_architecture.md`
2. Add your changes to `PROGRESS.md`
3. Run tests to ensure everything works correctly
4. Commit with a descriptive message
