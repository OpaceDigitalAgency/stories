# BOOK_SCRAPER_DATA_STORAGE

This document outlines the data storage architecture, enrichment logic, field mappings, and external API integration used in our book scraper and enrichment pipeline. It complements existing documentation like `REVIEW_SYSTEM_README.md` and `VPS_REVIEW_SCRAPER_IMPLEMENTATION.md`.

---

## 📘 Overview

The system enriches book data using multiple data sources with a **two-phase loading strategy**:

### Phase 1: Fast API Calls (Synchronous)
- **Google Books API**: `https://www.googleapis.com/books/v1/volumes?q=isbn:{ISBN}`
- **Open Library API**: `https://openlibrary.org/search.json?q={ISBN}&fields=*,availability&limit=1`

### Phase 2: Scraping Services (Asynchronous)
- **Amazon UK Scraping**: Real-time price and format data from `https://www.amazon.co.uk/dp/{ISBN}`
- **Goodreads Scraping**: Reviews, ratings, and metadata from `https://www.goodreads.com/book/isbn/{ISBN}`

This approach ensures **fast initial loading** with API data, followed by **enhanced data** from scraping services without blocking the user interface.

Each book record is imported or enriched by matching ISBNs and retrieving relevant metadata from all available sources.

---

## 🗂️ Database Table and API Field Mapping

```markdown
+--------------------------+---------------------------+-------------------------------------------+-------------------------------------------+-------------------------------------------+
| Table                   | Column                    | Google Books API                          | Open Library API                          | Amazon UK Scraping                       |
+==========================+===========================+===========================================+===========================================+===========================================+
| directory_items          | id                        | (internal ID)                             | (internal ID)                             | (internal ID)                             |
| directory_items          | title                     | volumeInfo.title                          | title                                     | (not available)                           |
| directory_items          | isbn                      | industryIdentifiers[type=ISBN_10]         | isbn[] (10-digit)                         | (used for lookup)                         |
| directory_items          | isbn13                    | industryIdentifiers[type=ISBN_13]         | isbn[] (13-digit)                         | (converted to ISBN-10 for lookup)        |
| directory_items          | author                    | volumeInfo.authors[0]                     | author_name[0]                            | (not available)                           |
| directory_items          | publisher                 | volumeInfo.publisher                      | publisher[0]                              | (not available)                           |
| directory_items          | publication_date          | volumeInfo.publishedDate                  | first_publish_year / publish_date[0]      | (not available)                           |
| directory_items          | page_count                | volumeInfo.pageCount                      | number_of_pages_median                    | (not available)                           |
| directory_items          | price_range               | saleInfo.listPrice.amount (if for sale)   | (not available)                           | calculated from selected_price            |
| directory_items          | age_range                 | maturityRating → inferred                 | subject_facet → e.g. "Ages 9-12"          | (not available)                           |
| directory_items          | reading_level             | (not available)                           | lexile[]                                  | (not available)                           |
| directory_items          | language                  | volumeInfo.language                       | language[]                                | (not available)                           |
| directory_items          | format                    | printType                                 | format[]                                  | selected_format (Hardcover/Kindle/etc)   |
| directory_items          | cover_url                 | imageLinks.thumbnail                      | covers.openlibrary.org via cover_i        | (not available)                           |
| directory_items          | purchase_links            | constructed from ISBN (Amazon, etc)       | constructed from ISBN                     | JSON: buying_options with URLs & prices  |
| directory_items          | preview_link              | volumeInfo.previewLink                    | availability.is_previewable + work link   | (not available)                           |
| directory_items          | metadata                  | entire Google response                    | entire Open Library response              | buying_options + selected data            |
| directory_items          | series                    | (not available)                           | (not available)                           | (not available)                           |
| directory_items          | publisher_id              | (internal mapping, not in API)            | (not in API)                              | (not available)                           |
| directory_items          | internet_archive_id       | (not available)                           | lending_identifier_s or ia[0]             | (not available)                           |
| directory_items          | awards                    | (not available)                           | subject_facet → award:*                   | (not available)                           |
| directory_items          | characters                | (not available)                           | person[]                                  | (not available)                           |
| directory_items          | settings                  | (not available)                           | place[]                                   | (not available)                           |
| directory_items          | last_validated            | (timestamp at import)                     | (timestamp at import)                     | (timestamp at scraping)                   |
| directory_items          | validation_status         | derived from match logic                  | derived from match logic                  | derived from scraping success            |
+--------------------------+---------------------------+-------------------------------------------+-------------------------------------------+

| directory_item_tags      | tag_id                    | categories[] (mapped)                     | subject[], subject_key[], subject_facet[] |
+--------------------------+---------------------------+-------------------------------------------+-------------------------------------------+

| item_tags                | tag_id                    | categories[] / keywords                   | subject_key[]                             |
+--------------------------+---------------------------+-------------------------------------------+-------------------------------------------+

| book_authors             | author_id                 | authors[].name (no ID)                    | author_key[] + author_name[]              |
| book_authors             | directory_item_id         | matched by ISBN/title                     | matched by ISBN/title                     |
+--------------------------+---------------------------+-------------------------------------------+-------------------------------------------+

| authors                  | id                        | (not available)                           | author_key[]                              |
| authors                  | name                      | authors[0]                                | author_name[0]                            |
| authors                  | slug                      | (not available)                           | (not available)                           |
+--------------------------+---------------------------+-------------------------------------------+-------------------------------------------+

| age_ranges               | range_name                | maturityRating → inferred                 | subject_facet[] (children, tweens, YA)    |
+--------------------------+---------------------------+-------------------------------------------+-------------------------------------------+
```

---

## 🧠 Metadata Field Logic

- `metadata`: stores full JSON response from each API under:
  ```json
  {
    "google_books": { ... },
    "open_library": { ... }
  }
  ```

---

## 🛒 Amazon UK Scraping Integration

### Real-time Amazon Data Extraction

The system scrapes live Amazon UK data for:
- **Format availability**: Hardcover, Paperback, Kindle, Audio CD
- **Current pricing**: Real-time prices in GBP
- **Purchase URLs**: Direct links with proper /ref= parameters
- **Selected format detection**: Identifies default format (has `selected` class + `javascript:void(0)` href)

### Amazon Endpoints Used

- **Desktop**: `https://www.amazon.co.uk/dp/{ISBN-10}`
- **Mobile**: `https://www.amazon.co.uk/gp/aw/d/{ISBN-10}`

**Note**: Amazon scraping requires ISBN-10 format. ISBN-13 values are automatically converted using `convertISBN13ToISBN10()` function.

### Amazon HTML Structure Targeting

The scraper targets the following HTML elements:
```html
<div id="tmm-grid-swatch-HARDCOVER" class="...selected...">
  <a href="javascript:void(0)" role="radio" aria-checked="true">
    <span aria-label="£13.70">£13.70</span>
  </a>
</div>
<div id="tmm-grid-swatch-KINDLE" class="...unselected...">
  <a href="/Coraline-Neil-Gaiman-ebook/dp/B0037B6Q66/ref=tmm_kin_swatch_0">
    <span aria-label="£4.53">£4.53</span>
  </a>
</div>
```

### Regex Patterns Used

```php
// Individual format extraction
$patterns = [
    'Hardcover' => '/id="tmm-grid-swatch-HARDCOVER".*?<a href="([^"]*)".*?aria-label="£(\d+\.\d{2})"/is',
    'Paperback' => '/id="tmm-grid-swatch-PAPERBACK".*?<a href="([^"]*)".*?aria-label="£(\d+\.\d{2})"/is',
    'Kindle' => '/id="tmm-grid-swatch-KINDLE".*?<a href="([^"]*)".*?aria-label="£(\d+\.\d{2})"/is',
    'Audio CD' => '/id="tmm-grid-swatch-AUDIOBOOK".*?<a href="([^"]*)".*?aria-label="£(\d+\.\d{2})"/is',
];

// Selected format detection (checks each format individually)
foreach ($formatChecks as $formatKey => $formatName) {
    $pattern = '/id="tmm-grid-swatch-' . $formatKey . '"[^>]*class="[^"]*selected[^"]*".*?href="javascript:void\(0\)".*?aria-label="£(\d+\.\d{2})"/is';
}
```

### Purchase Link Generation

Dynamic links are created using:
- **Amazon UK**: `https://www.amazon.co.uk/gp/product/{isbn-10}/ref=tmm_{format}_swatch_0`
- **Goodreads**: `https://www.goodreads.com/book/isbn/{isbn13}`
- **Google Books**: `https://books.google.com/books?isbn={isbn13}`

### Selected Format Logic

The system identifies the **selected format** by finding the format that has:
1. **CSS class containing "selected"**
2. **href="javascript:void(0)"** (indicates current selection)
3. **Valid price in aria-label**

Unselected formats have real URLs and "unselected" class.

### No Caching Policy

Amazon data is fetched fresh on each request since:
- Prices change frequently throughout the day
- Format availability can vary
- Real-time accuracy is prioritized over performance

### Amazon Data Structure

```json
{
  "buying_options": {
    "Hardcover": {
      "price": "£13.70",
      "url": "https://www.amazon.co.uk/gp/product/0380977788/ref=tmm_hardcover_swatch_0",
      "is_selected": true
    },
    "Paperback": {
      "price": "£7.89",
      "url": "https://www.amazon.co.uk/Coraline-Neil-Gaiman/dp/0747562105/ref=tmm_pap_swatch_0",
      "is_selected": false
    },
    "Kindle": {
      "price": "£4.53",
      "url": "https://www.amazon.co.uk/Coraline-Neil-Gaiman-ebook/dp/B0037B6Q66/ref=tmm_kin_swatch_0",
      "is_selected": false
    },
    "Audio CD": {
      "price": "£18.91",
      "url": "https://www.amazon.co.uk/Coraline-Neil-Gaiman/dp/006051048X/ref=tmm_abk_swatch_0",
      "is_selected": false
    }
  },
  "selected_format": "Hardcover",
  "selected_price": "£13.70"
}
```

---

## 📚 Goodreads Scraping Integration

### Real-time Goodreads Data Extraction

The system scrapes live Goodreads data for:
- **Ratings**: Average rating and rating count
- **Reviews**: Review count and sample reviews
- **Enhanced metadata**: Awards, characters, settings
- **Genre information**: Detailed genre classifications

### Goodreads Endpoints Used

- **Book Page**: `https://www.goodreads.com/book/isbn/{ISBN-13}`
- **Alternative**: `https://www.goodreads.com/book/show/{book_id}`

### Goodreads VPS Implementation

The Goodreads scraping uses a **Node.js + Puppeteer** service running on the VPS:
- **Server**: `stories-backend/goodreads/server.js`
- **Port**: 3000
- **Headless browser**: Handles JavaScript-heavy Goodreads pages
- **Caching**: Implements intelligent caching to avoid rate limits

### Goodreads Data Structure

```json
{
  "rating": "4.13",
  "review_count": "41954",
  "rating_count": "753463",
  "awards": ["Hugo Award Nominee", "Nebula Award Winner"],
  "characters": ["Coraline Jones", "The Other Mother"],
  "settings": ["England", "Parallel Universe"],
  "genres": ["Fantasy", "Children's Literature", "Horror"],
  "format": "Hardcover",
  "pages": "162",
  "published": "2002",
  "language": "English"
}
```

### Goodreads AJAX Integration

```javascript
// Check if ISBN exists on Goodreads
$.post('ajax/data-enrichment-ajax.php', {
    action: 'check_goodreads_isbn',
    isbn: currentBookISBN
}, function(res) {
    if (res.success && res.exists) {
        // ISBN found on Goodreads, can scrape additional data
        loadGoodreadsEnhancedData(currentBookISBN);
    }
});
```

---

## 🧾 Example URLs

- Google Books: `https://www.googleapis.com/books/v1/volumes?q=isbn:9780380977789`
- Open Library: `https://openlibrary.org/search.json?q=9780380977789&fields=*,availability&limit=1`
- Amazon: `https://www.amazon.com/dp/9780380977789/`
- Goodreads: `https://www.goodreads.com/book/isbn/9780380977789`

---

## 🚦 Status Flags

- `validation_status`: enum('pending', 'valid', 'invalid', 'partial')
- `last_validated`: set during enrichment run

---

## 📋 Field Fallback Priorities

| Field              | Priority Order                            |
|-------------------|--------------------------------------------|
| Title             | Google → Open Library                      |
| Author            | Google → Open Library                      |
| ISBN/ISBN-13      | Google → Open Library                      |
| Publisher         | Google → Open Library                      |
| Description       | Google → Open Library                      |
| Page Count        | Google → Open Library                      |
| Publication Date  | Google → Open Library                      |
| Format            | Open Library → Google                      |
| Cover URL         | Google → Open Library                      |
| Maturity / Age    | Google → Open Library (tags)               |
| Tags & Genres     | Open Library + Google categories           |

---

## 🔄 AJAX Implementation & Asynchronous Loading

### Two-Phase Data Enrichment Modal Flow

#### Phase 1: Fast API Loading (Synchronous)
1. **Initial Load**: Modal opens with "Checking..." indicators
2. **API Requests**: Fast calls to Google Books and OpenLibrary APIs
3. **Initial Display**: Show available data immediately (typically < 2 seconds)

#### Phase 2: Enhanced Scraping (Asynchronous)
4. **Background Requests**: Separate AJAX calls for scraping services
   - Amazon data (price, format, purchase links)
   - Goodreads data (reviews, ratings, enhanced metadata)
5. **Progressive Updates**: Fields update as scraping data becomes available
6. **User Selection**: Interactive checkboxes for field updates

This approach ensures users see **immediate results** from APIs while **enhanced data loads in background**.

### AJAX Endpoints

#### Main Enrichment Endpoint
**URL**: `book-import-validate/ajax/data-enrichment-ajax.php`
**Action**: `get_enrichment_data`
**Parameters**: `title`, `author`, `current_isbn`, `book_id`

#### Amazon Data Endpoint
**URL**: `book-import-validate/ajax/data-enrichment-ajax.php`
**Action**: `get_amazon_data`
**Parameters**: `isbn` (ISBN-10 or ISBN-13)

**Amazon Response Format**:
```json
{
  "success": true,
  "data": {
    "purchase_links": {
      "new_data": {
        "value": "{\"Hardcover\":{\"price\":\"£13.70\",\"url\":\"...\"},\"Kindle\":{\"price\":\"£4.53\",\"url\":\"...\"}}",
        "source": "amazon_derived",
        "confidence": 90,
        "status": "ready"
      }
    },
    "format": {
      "new_data": {
        "value": "Hardcover",
        "source": "amazon_derived",
        "confidence": 95,
        "status": "ready"
      }
    },
    "price_range": {
      "new_data": {
        "value": "£10-£15",
        "source": "amazon_derived",
        "confidence": 90,
        "status": "ready"
      }
    }
  },
  "debug": {
    "isbn_original": "9780380977789",
    "isbn_used": "0380977788",
    "options_count": 4,
    "selected_format": "Hardcover",
    "selected_price": "£13.70",
    "raw_amazon_payload": { ... }
  }
}
```

#### Goodreads Data Endpoint
**URL**: `book-import-validate/ajax/data-enrichment-ajax.php`
**Action**: `check_goodreads_isbn`
**Parameters**: `isbn`

### JavaScript Integration

```javascript
// Phase 1: Load API data immediately
loadEnrichmentData(bookId, title, author, isbn);

// Phase 2: Load Amazon data asynchronously
$.post('ajax/data-enrichment-ajax.php', {
    action: 'get_amazon_data',
    isbn: currentBookISBN
}, function(res) {
    if (res.success && res.data) {
        updateEnrichmentDataWithAmazon(res.data);
        displayEnrichmentFields(currentEnrichmentData.fields);
    }
});
```

### Error Handling

- **Network failures**: Graceful degradation with error messages
- **Empty responses**: Clear "No data available" indicators
- **Debug logging**: Comprehensive error_log() statements for troubleshooting
- **Timeout handling**: Scraping requests have reasonable timeouts
- **Fallback behavior**: System works even if scraping services fail

## 🧩 Enhancement Suggestions

- ✅ **Real-time Amazon scraping**: Implemented with live price data
- ✅ **AJAX-based enrichment**: Non-blocking modal updates
- Use affiliate tagging for monetised Amazon links
- Expand metadata to store enrichment logs (source, confidence, etc.)


### 1. **Fallbacks and Defaults**
- If `maturityRating = "NOT_MATURE"` (Google Books), use `"All Ages"` as age range.
- If `maturityRating = "MATURE"` (Google Books), use `"18+"` or `"Adult"`.

### 2. **Age Range Logic**
- Use Open Library `subject` or `facet` like "Children's Books/Ages 9-12 Fiction".
- Match Goodreads genres (e.g. `Middle Grade`, `Young Adult`) to age ranges:
  - "Middle Grade" = `9–12 years`
  - "Young Adult" = `12+ years` or `Teen`

### 3. **Metadata (`metadata` JSON field)**

Currently stores extra matched fields such as:
```json
{
  "awards": "...",
  "characters": "...",
  "settings": "...",
  "rating": "4.13",
  "review_count": "41954",
  "rating_count": "753463"
}
```

From Goodreads `https://www.goodreads.com/book/show/474073.Coraline`:

- `aggregateRating.ratingValue` → rating
- `aggregateRating.reviewCount` → review_count
- `aggregateRating.ratingCount` → rating_count
- `.BookPageMetadataSection__genreButton > span` → genres (loop)
- `.DescListItem > dt:contains("Setting") + dd a` → settings
- `.DescListItem > dt:contains("Characters") + dd a` → characters
- `.DescListItem > dt:contains("Literary awards") + dd a` → awards


---

## 🔍 SELECTORS / MATCH STRATEGY (GOODREADS)

| Field         | Selector / JSON Key                                 |
|---------------|------------------------------------------------------|
| Title         | `meta[property='og:title']` or `name` JSON-LD        |
| Authors       | `meta[property='books:author']` / `.author a`        |
| Awards        | `data-testid="Award"` inside `.DescListItem`         |
| Characters    | `.DescListItem:contains("Characters") a`             |
| Settings      | `.DescListItem:contains("Setting") a`                |
| Format        | `.DescListItem:contains("Format") div.content`       |
| Rating        | `script[type='application/ld+json'] → ratingValue`   |
| Genres        | `.BookPageMetadataSection__genreButton span`         |
| Pages         | `data-testid="pagesFormat"`                          |
| Published     | `data-testid="publicationInfo"`                      |
| Language      | `script[type='application/ld+json'] → inLanguage`    |

---

## 🧪 Test Scripts & Development Tools

### Amazon Scraping Test Scripts

#### Main Amazon Scraping Test
**URL**: `https://api.storiesfromtheweb.org/test-amazon-scraping.php`
**Purpose**: Comprehensive testing of Amazon scraping functionality
**Features**:
- Tests Amazon buying options scraping with debugging output
- Validates ISBN conversion (ISBN-13 ↔ ISBN-10)
- Tests purchase links generation
- Tests format and price range extraction
- Shows full Amazon enrichment data structure

#### Regex Pattern Testing
**URL**: `https://api.storiesfromtheweb.org/test-regex-patterns.php`
**Purpose**: Tests regex patterns against actual Amazon HTML
**Features**:
- Tests selected format detection logic
- Validates individual format pattern matching
- Shows raw pattern matches for debugging
- Uses real Amazon HTML structure for accurate testing

### Key Implementation Files

#### Core Functions
- `stories-backend/admin/content/book-import-validate/functions/data-enrichment-functions.php`
  - `getAmazonEnrichmentData()` - Main Amazon data fetching
  - `scrapeAmazonBuyingOptions()` - HTML parsing and regex matching
  - `convertISBN13ToISBN10()` - ISBN format conversion
  - `extractFieldValue()` - Field extraction for enrichment modal

#### AJAX Endpoints
- `stories-backend/admin/content/book-import-validate/ajax/data-enrichment-ajax.php`
  - `get_amazon_data` action - Amazon scraping endpoint
  - `get_enrichment_data` action - Main enrichment endpoint
  - `check_goodreads_isbn` action - Goodreads validation

#### Modal Implementation
- `stories-backend/admin/content/book-import-validate/modals/data-enrichment-modal.php`
  - `updateEnrichmentDataWithAmazon()` - Amazon data integration
  - `displayEnrichmentFields()` - Field rendering with Amazon badges
  - Asynchronous loading logic for scraping services

#### Database Integration
- `stories-backend/admin/content/book-import-validate/ajax/data-enrichment-ajax.php`
  - `handleApplyEnrichment()` - Saves enriched data to database
  - `filterRelevantFields()` - Maps API data to database fields
  - Field validation and processing logic

### Development URLs

#### API Testing
- **Google Books**: `https://www.googleapis.com/books/v1/volumes?q=isbn:9780380977789`
- **Open Library**: `https://openlibrary.org/search.json?q=9780380977789&fields=*,availability&limit=1`
- **Amazon UK**: `https://www.amazon.co.uk/dp/0380977788`
- **Goodreads**: `https://www.goodreads.com/book/isbn/9780380977789`

#### Live Testing
- **Book Validation**: `https://api.storiesfromtheweb.org/admin/content/book-import-validate/`
- **Data Enrichment Modal**: Available from book validation page
- **Amazon Test Script**: `https://api.storiesfromtheweb.org/test-amazon-scraping.php`
- **Regex Test Script**: `https://api.storiesfromtheweb.org/test-regex-patterns.php`

## ✅ EXAMPLE RESOURCES

- [Google Books API Example](https://books.google.com/books?isbn=9780380977789)
- [Open Library API](https://openlibrary.org/isbn/9780380977789.json)
- [Goodreads Book Page](https://www.goodreads.com/book/show/474073.Coraline)
- [Amazon UK Product Page](https://www.amazon.co.uk/dp/0380977788)

---

## 🛠️ Useful TODOs

- Add logic to query Google search results for pricing where available.
- Prioritise data sources in this order: `Google Books` → `Open Library` → `Goodreads`.
- Include HTML escaping + cleanup when storing tags or long fields like awards.

