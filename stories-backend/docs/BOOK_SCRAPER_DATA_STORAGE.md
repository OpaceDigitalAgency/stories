# BOOK_SCRAPER_DATA_STORAGE

This document outlines the data storage architecture, enrichment logic, field mappings, and external API integration used in our book scraper and enrichment pipeline. It complements existing documentation like `REVIEW_SYSTEM_README.md` and `VPS_REVIEW_SCRAPER_IMPLEMENTATION.md`.

---

## 📘 Overview

The system enriches book data using:
- **Google Books API**: `https://www.googleapis.com/books/v1/volumes?q=isbn:{ISBN}`
- **Open Library API**: `https://openlibrary.org/search.json?q={ISBN}&fields=*,availability&limit=1`
- **Amazon UK Scraping**: Real-time price and format data from `https://www.amazon.co.uk/dp/{ISBN}`

Each book record is imported or enriched by matching ISBNs and retrieving relevant metadata.

---

## 🗂️ Database Table and API Field Mapping

```markdown
+--------------------------+---------------------------+-------------------------------------------+-------------------------------------------+
| Table                   | Column                    | Google Books API                          | Open Library API                          |
+==========================+===========================+===========================================+===========================================+
| directory_items          | id                        | (internal ID)                             | (internal ID)                             |
| directory_items          | title                     | volumeInfo.title                          | title                                     |
| directory_items          | isbn                      | industryIdentifiers[type=ISBN_10]         | isbn[] (10-digit)                         |
| directory_items          | isbn13                    | industryIdentifiers[type=ISBN_13]         | isbn[] (13-digit)                         |
| directory_items          | author                    | volumeInfo.authors[0]                     | author_name[0]                            |
| directory_items          | publisher                 | volumeInfo.publisher                      | publisher[0]                              |
| directory_items          | publication_date          | volumeInfo.publishedDate                  | first_publish_year / publish_date[0]      |
| directory_items          | page_count                | volumeInfo.pageCount                      | number_of_pages_median                    |
| directory_items          | price_range               | saleInfo.listPrice.amount (if for sale)   | (not available)                           |
| directory_items          | age_range                 | maturityRating → inferred                 | subject_facet → e.g. "Ages 9-12"          |
| directory_items          | reading_level             | (not available)                           | lexile[]                                  |
| directory_items          | language                  | volumeInfo.language                       | language[]                                |
| directory_items          | format                    | printType                                 | format[]                                  |
| directory_items          | cover_url                 | imageLinks.thumbnail                      | covers.openlibrary.org via cover_i        |
| directory_items          | purchase_links            | constructed from ISBN (Amazon, etc)       | constructed from ISBN                     |
| directory_items          | preview_link              | volumeInfo.previewLink                    | availability.is_previewable + work link   |
| directory_items          | metadata                  | entire Google response                    | entire Open Library response              |
| directory_items          | series                    | (not available)                           | (not available)                           |
| directory_items          | publisher_id              | (internal mapping, not in API)            | (not in API)                              |
| directory_items          | internet_archive_id       | (not available)                           | lending_identifier_s or ia[0]             |
| directory_items          | awards                    | (not available)                           | subject_facet → award:*                   |
| directory_items          | characters                | (not available)                           | person[]                                  |
| directory_items          | settings                  | (not available)                           | place[]                                   |
| directory_items          | last_validated            | (timestamp at import)                     | (timestamp at import)                     |
| directory_items          | validation_status         | derived from match logic                  | derived from match logic                  |
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

## 🛒 Amazon Scraping & Purchase Links

### Real-time Amazon Data Extraction

The system scrapes live Amazon UK data for:
- **Format availability**: Hardcover, Paperback, Kindle, Audio CD
- **Current pricing**: Real-time prices in GBP
- **Purchase URLs**: Direct links with proper /ref= parameters

### Amazon HTML Structure Targeting

The scraper targets the following HTML elements:
```html
<div id="tmm-grid-swatch-HARDCOVER">
  <a href="/path/to/book/ref=tmm_hardcover_swatch_0">
    <span aria-label="£13.70">£13.70</span>
  </a>
</div>
```

### Regex Patterns Used

```php
$patterns = [
    'Hardcover' => '/id="tmm-grid-swatch-HARDCOVER".*?href="([^"]+)".*?aria-label="£(\d+\.\d{2})"/is',
    'Paperback' => '/id="tmm-grid-swatch-PAPERBACK".*?href="([^"]+)".*?aria-label="£(\d+\.\d{2})"/is',
    'Kindle' => '/id="tmm-grid-swatch-KINDLE".*?href="([^"]+)".*?aria-label="£(\d+\.\d{2})"/is',
    'Audio CD' => '/id="tmm-grid-swatch-AUDIOBOOK".*?href="([^"]+)".*?aria-label="£(\d+\.\d{2})"/is',
];
```

### Purchase Link Generation

Dynamic links are created using:
- **Amazon UK**: `https://www.amazon.co.uk/gp/product/{isbn}/ref=tmm_{format}_swatch_0`
- **Goodreads**: `https://www.goodreads.com/book/isbn/{isbn13}`
- **Google Books**: `https://books.google.com/books?isbn={isbn13}`

### No Caching Policy

Amazon data is fetched fresh on each request since:
- Prices change frequently throughout the day
- Format availability can vary
- Real-time accuracy is prioritized over performance

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

## 🔄 AJAX Implementation

### Data Enrichment Modal Flow

1. **Initial Load**: Modal opens with "Checking..." indicators
2. **Parallel Requests**:
   - Main enrichment data (Google Books + OpenLibrary)
   - Amazon data (separate AJAX call)
3. **Real-time Updates**: Status badges update as data loads
4. **User Selection**: Interactive checkboxes for field updates

### Amazon AJAX Endpoint

**URL**: `book-import-validate/ajax/data-enrichment-ajax.php`
**Action**: `get_amazon_data`
**Parameters**: `isbn` (ISBN-10 or ISBN-13)

**Response Format**:
```json
{
  "success": true,
  "data": {
    "buying_options": {
      "Hardcover": {"price": "£13.70", "url": "https://amazon.co.uk/..."},
      "Paperback": {"price": "£7.89", "url": "https://amazon.co.uk/..."},
      "Kindle": {"price": "£4.53", "url": "https://amazon.co.uk/..."},
      "Audio CD": {"price": "£18.91", "url": "https://amazon.co.uk/..."}
    },
    "selected_format": "Hardcover",
    "selected_price": "£13.70"
  },
  "debug": {
    "isbn_used": "9780380977789",
    "options_count": 4,
    "selected_format": "Hardcover"
  }
}
```

### Error Handling

- **Network failures**: Graceful degradation with error messages
- **Empty responses**: Clear "No data available" indicators
- **Debug logging**: Comprehensive error_log() statements for troubleshooting

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

## ✅ EXAMPLE RESOURCES

- [Google Books API Example](https://books.google.com/books?isbn=9780380977789)
- [Open Library API](https://openlibrary.org/isbn/9780380977789.json)
- [Goodreads Book Page](https://www.goodreads.com/book/show/474073.Coraline)

---

## 🛠️ Useful TODOs

- Add logic to query Google search results for pricing where available.
- Prioritise data sources in this order: `Google Books` → `Open Library` → `Goodreads`.
- Include HTML escaping + cleanup when storing tags or long fields like awards.

