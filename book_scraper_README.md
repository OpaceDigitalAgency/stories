# Book Scraper and Data Enrichment Tools

This repository contains tools for scraping book data from websites and enriching it with ISBNs and metadata from various APIs.

## Overview

1. **booktrust_comprehensive_scraper.py** - Scrapes book data from BookTrust's Great Books Guide 2024-25
2. **flexible_book_scraper.py** - A flexible framework for creating book scrapers for different websites
3. **book_data_enricher.py** - Enriches book data with ISBNs and metadata from Google Books and Open Library APIs

## Features

### BookTrust Scraper
- Scrapes all age groups (4-5, 6-7, 8-9, 10-11 years)
- Handles JavaScript-rendered content
- Deduplicates books automatically
- Cleans special characters and encoding issues
- Extracts: title, author, age range, year, tags, description, detail URL

### Flexible Book Scraper Framework
- Abstract base class for creating custom scrapers
- Built-in text cleaning and deduplication
- Supports both CSV and JSON output
- Includes example implementation for BookTrust
- Template for creating scrapers for other websites

### Book Data Enricher
- Searches Google Books API for ISBN and metadata
- Falls back to Open Library API if needed
- Adds: ISBN-10, ISBN-13, publisher, publication date, page count, cover images
- Maintains all original data while adding enrichments
- Outputs both CSV and JSON formats

## Installation

```bash
pip install requests beautifulsoup4
```

## Usage

### 1. Scrape BookTrust Books

```bash
python3 booktrust_comprehensive_scraper.py
```

This creates `booktrust_books_comprehensive.csv` with 101 books.

### 2. Enrich with ISBNs and Metadata

```bash
python3 book_data_enricher.py
```

This reads `booktrust_books_comprehensive.csv` and creates:
- `booktrust_books_enriched.csv` - Enhanced CSV with ISBNs and metadata
- `booktrust_books_enriched.json` - Same data in JSON format

### 3. Create Custom Scrapers

Use the `flexible_book_scraper.py` framework:

```python
from flexible_book_scraper import BookScraperBase

class MyBookScraper(BookScraperBase):
    def __init__(self):
        super().__init__()
        self.base_url = 'https://example.com'
    
    def parse_book_from_element(self, element):
        # Implement your parsing logic
        book = {
            'title': element.find('h2').get_text(),
            'author': element.find('.author').get_text(),
            # ... more fields
        }
        return book
    
    def scrape_list_page(self, url):
        # Implement page scraping
        response = self.get_page(url)
        soup = BeautifulSoup(response.content, 'html.parser')
        # ... find and parse books
```

## Data Fields

### Scraped Fields
- `title` - Book title
- `author` - Author name(s)
- `age_range` - Target age group
- `year` - Publication year
- `tags` - Categories/genres
- `description` - Book synopsis
- `detail_url` - Link to book detail page

### Enriched Fields (added by enricher)
- `isbn` - ISBN-13 or ISBN-10
- `isbn10` - ISBN-10 specifically
- `isbn13` - ISBN-13 specifically
- `google_books_id` - Google Books identifier
- `open_library_key` - Open Library identifier
- `publisher` - Publisher name
- `published_date` - Publication date
- `page_count` - Number of pages
- `language` - Language code
- `thumbnail_url` - Cover image URL
- `categories` - Google Books categories
- `subjects` - Open Library subjects

## Database Integration

The enriched data can be imported into your database using the schema documented in `stories-backend/docs/BOOK_SCRAPER_DATA_STORAGE.md`. The enriched CSV contains all necessary fields for:

- Basic book information (title, author, description)
- ISBNs for further API lookups
- Publisher and publication details
- Cover images
- Age ranges and categories

## Rate Limiting

The scripts include polite delays between requests:
- 1 second between scraping pages
- 0.5-1 second between API calls

This ensures compliance with website and API terms of service.

## Troubleshooting

### No ISBNs Found
BookTrust doesn't display ISBNs on their website. The enricher searches for them using:
1. Google Books API (title + author search)
2. Open Library API (fallback)

Success rate is typically 60-80% depending on book availability in these databases.

### Special Characters
The scraper automatically cleans:
- Smart quotes (' ' " ")
- Em/en dashes (— –)
- Ellipsis (…)
- HTML entities (&amp; &quot; etc.)

### Missing Books
Some age groups may not exist (e.g., 11+ page returns 404). The scraper handles this gracefully and continues.

## Future Enhancements

1. Add Amazon scraping for additional ISBNs
2. Support for more book websites
3. Batch processing with progress bars
4. API key support for higher rate limits
5. Export to different formats (Excel, XML)
6. Automatic database import