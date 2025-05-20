#!/usr/bin/env python3
"""
Script to extract book information from Goodreads.
This script fetches a Goodreads book page and extracts key information
like title, author, ISBN, etc.
"""

import requests
from bs4 import BeautifulSoup
import re
import json
import sys

def clean_text(text):
    """Clean up text by removing HTML tags, URLs, and extra whitespace."""
    # Remove HTML tags
    text = re.sub(r'<[^>]+>', '', text)
    # Remove URLs
    text = re.sub(r'https?://\S+', '', text)
    # Remove href attributes
    text = re.sub(r'href="[^"]+"', '', text)
    # Clean up whitespace
    text = re.sub(r'\s+', ' ', text).strip()
    return text

def fetch_goodreads_page(url):
    """Fetch a Goodreads book page with proper headers."""
    headers = {
        "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) "
                      "AppleWebKit/537.36 (KHTML, like Gecko) "
                      "Chrome/113.0.0.0 Safari/537.36",
        "Accept-Language": "en-US,en;q=0.9",
        "Accept": "text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8",
        "Referer": "https://www.goodreads.com/",
    }

    try:
        resp = requests.get(url, headers=headers)
        resp.raise_for_status()
        return resp.text
    except requests.exceptions.RequestException as e:
        print(f"Error fetching page: {e}")
        return None

def extract_book_info(html):
    """Extract book information from HTML."""
    if not html:
        print("No HTML content to analyze")
        return None

    soup = BeautifulSoup(html, 'html.parser')
    book_info = {}
    selectors_info = {}  # Store selector information for reference

    # Check if we're on a search results page
    search_results = []
    
    # Try multiple selectors for search results
    selectors = [
        "table.tableList tr.bookalike",  # Classic format
        "div.BookSearchResult",          # Modern format
        "div[data-testid='searchResults'] > div",  # Newest format
    ]
    
    for selector in selectors:
        results = soup.select(selector)
        if results:
            search_results = results
            print(f"Found {len(results)} search results using selector: {selector}")
            break
    
    if search_results:
        print(f"Analyzing {len(search_results)} search results to find the best match")
        best_match = None
        best_match_score = 0
        
        # Get search parameters from URL
        url_params = {}
        if "?" in url:
            query = url.split("?")[1]
            url_params = dict(param.split("=") for param in query.split("&"))
            search_query = url_params.get("q", "").replace("+", " ")
            print(f"Search query: {search_query}")
        
        # Analyze each result
        for result in search_results:
            match_score = 0
            result_data = {}
            
            # Extract book info using multiple selectors
            for title_selector in ["a.bookTitle", "a[data-testid='bookTitle']", "a[href*='/book/show/']"]:
                title_elem = result.select_one(title_selector)
                if title_elem:
                    result_data["title"] = title_elem.text.strip()
                    result_data["url"] = title_elem["href"]
                    break
            
            for author_selector in ["a.authorName", "a[data-testid='authorLink']", "a[href*='/author/show/']"]:
                author_elem = result.select_one(author_selector)
                if author_elem:
                    result_data["author"] = author_elem.text.strip()
                    break
            
            # Look for ISBN in result
            isbn_elem = result.find(string=re.compile(r'ISBN.*\d'))
            if isbn_elem:
                isbn_match = re.search(r'ISBN.*?(\d+[X\d]+)', isbn_elem)
                if isbn_match:
                    result_data["isbn"] = isbn_match.group(1)
            
            # Score the match
            if search_query:
                # Direct ISBN match
                if "isbn" in result_data and search_query.replace("-", "") == result_data["isbn"].replace("-", ""):
                    match_score = 100
                    print(f"Found exact ISBN match: {result_data['isbn']}")
                else:
                    # Title and author match
                    if "title" in result_data and result_data["title"].lower() in search_query.lower():
                        match_score += 40
                    if "author" in result_data and result_data["author"].lower() in search_query.lower():
                        match_score += 40
            
            print(f"Result score: {match_score} for {result_data.get('title', 'Unknown')} by {result_data.get('author', 'Unknown')}")
            
            if match_score > best_match_score:
                best_match = result_data
                best_match_score = match_score
        
        if best_match and best_match.get("url"):
            book_url = best_match["url"]
            if not book_url.startswith("http"):
                book_url = "https://www.goodreads.com" + book_url
            
            print(f"Selected best match (score: {best_match_score}): {book_url}")
            
            # Fetch the actual book page
            html = fetch_goodreads_page(book_url)
            if html:
                # Re-parse with the new HTML
                soup = BeautifulSoup(html, "html.parser")
                # Save the book page HTML for debugging
                with open("book_page.html", "w", encoding="utf-8") as f:
                    f.write(html)
                print("Saved book page HTML to book_page.html")
            else:
                print("Failed to fetch the book page from search results")
                return None
        else:
            print("No suitable match found in search results")
            # Dump the HTML for debugging
            with open("search_results_debug.html", "w", encoding="utf-8") as f:
                f.write(html)
            print("Saved search results HTML to search_results_debug.html")
            return None

    # Try to extract structured data first (most reliable)
    json_data = extract_json_data(html)

    # Extract information from HTML elements
    # Title - try multiple selectors
    title_selectors = [
        "h1.Text__title1",
        "h1.BookPageTitleSection__title",
        "h1[data-testid='bookTitle']",
        "h1.Text__title3"
    ]
    
    for selector in title_selectors:
        title_elem = soup.select_one(selector)
        if title_elem:
            book_info["title"] = title_elem.text.strip()
            selectors_info["title"] = {"selector": selector, "value": book_info["title"]}
            break

    # Author - try multiple selectors
    author_selectors = [
        "span.ContributorLink__name",
        "span.BookPageTitleSection__authorName",
        "a[data-testid='authorLink']",
        "a[href*='/author/show/']"
    ]
    
    for selector in author_selectors:
        author_elem = soup.select_one(selector)
        if author_elem:
            book_info["author"] = author_elem.text.strip()
            selectors_info["author"] = {"selector": selector, "value": book_info["author"]}
            break

    # Rating
    rating_selectors = [
        "div.RatingStatistics__rating",
        "span[data-testid='averageRating']"
    ]
    
    for selector in rating_selectors:
        rating_elem = soup.select_one(selector)
        if rating_elem:
            book_info["rating"] = rating_elem.text.strip()
            selectors_info["rating"] = {"selector": selector, "value": book_info["rating"]}
            break

    # Description
    description_selectors = [
        "div.DetailsLayoutRightParagraph__widthConstrained",
        "div[data-testid='description']",
        "div.TruncatedContent div[data-testid='contentContainer']"
    ]
    
    for selector in description_selectors:
        description_elem = soup.select_one(selector)
        if description_elem:
            book_info["description"] = description_elem.text.strip()
            selectors_info["description"] = {
                "selector": selector,
                "value": book_info["description"][:100] + "..." if len(book_info["description"]) > 100 else book_info["description"]
            }
            break

    # Cover image
    cover_selectors = [
        "img.ResponsiveImage",
        "img[data-testid='coverImage']"
    ]
    
    for selector in cover_selectors:
        cover_img = soup.select_one(selector)
        if cover_img and cover_img.get('src'):
            book_info["cover_image"] = cover_img.get('src')
            selectors_info["cover_image"] = {"selector": selector, "value": book_info["cover_image"]}
            break

    # Genres/Categories
    genre_selectors = [
        "span.BookPageMetadataSection__genreButton",
        "a[href*='/genres/']",
        "div[data-testid='genresList'] a"
    ]
    
    for selector in genre_selectors:
        genre_elements = soup.select(selector)
        if genre_elements:
            book_info["genres"] = [genre.text.strip() for genre in genre_elements]
            selectors_info["genres"] = {"selector": selector, "value": book_info["genres"]}
            break

    # Book Details - try both new and old formats
    details_found = False
    
    # Method 1: New Goodreads design with CollapsableList
    book_details = soup.select("div.BookDetails div.CollapsableList div.DescListItem")
    if book_details:
        details_found = True
        for item in book_details:
            # Find the label (dt) and value (dd)
            label = item.find("dt")
            value = item.find("dd")
            
            if label and value:
                label_text = label.text.strip().lower()
                # Look for the actual content in TruncatedContent
                content_container = value.select_one("div[data-testid='contentContainer']")
                value_text = content_container.text.strip() if content_container else value.text.strip()
                
                # Extract various fields
                if "isbn" in label_text:
                    # Handle both ISBN-10 and ISBN-13
                    isbn_match = re.search(r'(\d{10}|\d{13})', value_text)
                    if isbn_match:
                        book_info["isbn"] = isbn_match.group(1)
                        selectors_info["isbn"] = {"selector": "BookDetails CollapsableList ISBN", "value": book_info["isbn"]}
                elif "format" in label_text:
                    book_info["format"] = value_text
                    selectors_info["format"] = {"selector": "BookDetails CollapsableList Format", "value": value_text}
                elif "published" in label_text:
                    # Extract date and publisher
                    pub_match = re.match(r'(.*?)\s+by\s+(.*?)(?:\s*$|\s*\()', value_text)
                    if pub_match:
                        book_info["published_date"] = pub_match.group(1).strip()
                        book_info["publisher"] = pub_match.group(2).strip()
                        selectors_info["published_date"] = {"selector": "BookDetails CollapsableList Published", "value": book_info["published_date"]}
                        selectors_info["publisher"] = {"selector": "BookDetails CollapsableList Publisher", "value": book_info["publisher"]}
                elif "pages" in label_text:
                    pages_match = re.search(r'(\d+)\s*pages', value_text)
                    if pages_match:
                        book_info["pages"] = pages_match.group(1)
                        selectors_info["pages"] = {"selector": "BookDetails CollapsableList Pages", "value": book_info["pages"]}
                elif "language" in label_text:
                    book_info["language"] = value_text
                    selectors_info["language"] = {"selector": "BookDetails CollapsableList Language", "value": value_text}

    # Method 2: Feature items (if Method 1 didn't find anything)
    if not details_found:
        feature_items = soup.select("div.FeatureItems div.FeatureItem")
        if feature_items:
            for item in feature_items:
                label = item.select_one("div.FeatureItem__label")
                value = item.select_one("div.FeatureItem__value")

                if label and value:
                    label_text = label.text.strip().lower()
                    value_text = value.text.strip()

                    if "isbn" in label_text:
                        book_info["isbn"] = value_text
                        selectors_info["isbn"] = {"selector": "FeatureItem ISBN", "value": value_text}
                    elif "pages" in label_text:
                        book_info["pages"] = value_text
                        selectors_info["pages"] = {"selector": "FeatureItem Pages", "value": value_text}
                    elif "published" in label_text:
                        book_info["published_date"] = value_text
                        selectors_info["published_date"] = {"selector": "FeatureItem Published", "value": value_text}
                    elif "publisher" in label_text:
                        book_info["publisher"] = clean_text(value_text)
                        selectors_info["publisher"] = {"selector": "FeatureItem Publisher", "value": book_info["publisher"]}
                    elif "language" in label_text:
                        book_info["language"] = value_text
                        selectors_info["language"] = {"selector": "FeatureItem Language", "value": value_text}

    # Method 3: Look for data attributes or other patterns
    # Try to find ISBN in any format
    isbn_patterns = [
        r'ISBN(?:\s+|:)(\d+[X\d]+)',
        r'ISBN-13(?:\s+|:)(\d+[X\d]+)',
        r'ISBN-10(?:\s+|:)(\d+[X\d]+)'
    ]

    for pattern in isbn_patterns:
        isbn_match = re.search(pattern, html, re.IGNORECASE)
        if isbn_match and 'isbn' not in book_info:
            book_info["isbn"] = isbn_match.group(1)
            selectors_info["isbn"] = {
                "selector": f"Pattern match: {pattern}",
                "value": book_info["isbn"]
            }

    # Look for series information
    series_elem = soup.select_one("h3.Text__title3")
    if series_elem and 'Series' in series_elem.text:
        series_container = series_elem.find_parent("div")
        if series_container:
            series_link = series_container.select_one("a")
            if series_link:
                book_info["series"] = series_link.text.strip()
                selectors_info["series"] = {
                    "selector": "h3.Text__title3:contains('Series') + div a",
                    "value": book_info["series"]
                }

                # Try to extract series number
                series_text = series_container.text
                series_num_match = re.search(r'#(\d+)', series_text)
                if series_num_match:
                    book_info["series_number"] = int(series_num_match.group(1))
                    selectors_info["series_number"] = {
                        "selector": "Series container with #number pattern",
                        "value": book_info["series_number"]
                    }

    # Look for characters
    characters_section = soup.find(lambda tag: tag.name == "h3" and "Characters" in tag.text)
    if characters_section:
        character_container = characters_section.find_parent("div")
        if character_container:
            character_links = character_container.select("a")
            if character_links:
                book_info["characters"] = [char.text.strip() for char in character_links if char.text.strip()]
                selectors_info["characters"] = {
                    "selector": "h3:contains('Characters') parent div a",
                    "value": book_info["characters"]
                }

    # Look for setting
    setting_section = soup.find(lambda tag: tag.name == "h3" and "Setting" in tag.text)
    if setting_section:
        setting_container = setting_section.find_parent("div")
        if setting_container:
            setting_links = setting_container.select("a")
            if setting_links:
                book_info["settings"] = [setting.text.strip() for setting in setting_links if setting.text.strip()]
                selectors_info["settings"] = {
                    "selector": "h3:contains('Setting') parent div a",
                    "value": book_info["settings"]
                }

    # Direct publisher extraction - try to find publisher information directly
    if "publisher" not in book_info:
        # Try to find publisher in the details section
        publisher_patterns = [
            r'Published\s+(?:.*?)\s+by\s+(.*?)(?:\.|,|\(|$)',
            r'Publisher\s*:\s*(.*?)(?:\.|,|\(|$)',
            r'Published\s+by\s+(.*?)(?:\.|,|\(|$)'
        ]

        for pattern in publisher_patterns:
            publisher_match = re.search(pattern, html, re.IGNORECASE)
            if publisher_match:
                publisher_text = publisher_match.group(1).strip()
                # Clean up publisher text
                publisher_text = re.sub(r'<[^>]+>', '', publisher_text)
                publisher_text = re.sub(r'https?://\S+', '', publisher_text)
                publisher_text = re.sub(r'href="[^"]+"', '', publisher_text)
                publisher_text = re.sub(r'\s+', ' ', publisher_text).strip()

                book_info["publisher"] = publisher_text
                selectors_info["publisher"] = {
                    "selector": f"Regex pattern: {pattern}",
                    "value": publisher_text
                }
                break

    # If we found JSON data, use it to fill in any missing information
    # or add additional information
    if json_data:
        # Basic book information
        if "title" not in book_info and json_data.get("name"):
            book_info["title"] = json_data.get("name")
            selectors_info["title"] = {
                "selector": "JSON data: @type=Book, name property",
                "value": book_info["title"]
            }

        if "isbn" not in book_info and json_data.get("isbn"):
            book_info["isbn"] = json_data.get("isbn")
            selectors_info["isbn"] = {
                "selector": "JSON data: @type=Book, isbn property",
                "value": book_info["isbn"]
            }

        if "pages" not in book_info and json_data.get("numberOfPages"):
            book_info["pages"] = json_data.get("numberOfPages")
            selectors_info["pages"] = {
                "selector": "JSON data: @type=Book, numberOfPages property",
                "value": book_info["pages"]
            }

        if "format" not in book_info and json_data.get("bookFormat"):
            book_info["format"] = json_data.get("bookFormat")
            selectors_info["format"] = {
                "selector": "JSON data: @type=Book, bookFormat property",
                "value": book_info["format"]
            }

        if "language" not in book_info and json_data.get("inLanguage"):
            book_info["language"] = json_data.get("inLanguage")
            selectors_info["language"] = {
                "selector": "JSON data: @type=Book, inLanguage property",
                "value": book_info["language"]
            }

        if "published_date" not in book_info and json_data.get("datePublished"):
            book_info["published_date"] = json_data.get("datePublished")
            selectors_info["published_date"] = {
                "selector": "JSON data: @type=Book, datePublished property",
                "value": book_info["published_date"]
            }

        if "publisher" not in book_info and json_data.get("publisher"):
            if isinstance(json_data["publisher"], dict):
                book_info["publisher"] = json_data["publisher"].get("name", "")
            else:
                book_info["publisher"] = json_data["publisher"]

            # Clean up publisher field - remove any HTML or URL fragments
            if book_info["publisher"]:
                # Remove HTML tags
                book_info["publisher"] = re.sub(r'<[^>]+>', '', book_info["publisher"])
                # Remove href attributes
                book_info["publisher"] = re.sub(r'href="[^"]+"', '', book_info["publisher"])
                # Remove any remaining URL fragments
                book_info["publisher"] = re.sub(r'https?://\S+', '', book_info["publisher"])
                # Clean up whitespace
                book_info["publisher"] = re.sub(r'\s+', ' ', book_info["publisher"]).strip()

            selectors_info["publisher"] = {
                "selector": "JSON data: @type=Book, publisher property",
                "value": book_info["publisher"]
            }

        # Extract series information from the title if available
        if "series" not in book_info and "(" in json_data.get("name", ""):
            title = json_data.get("name", "")
            series_match = re.search(r'\((.*?),\s*#(\d+)\)', title)
            if series_match:
                series_name = series_match.group(1)
                series_number = series_match.group(2)
                book_info["series"] = f"{series_name}"
                book_info["series_number"] = int(series_number)

                selectors_info["series"] = {
                    "selector": "JSON data: extracted from name property",
                    "value": book_info["series"]
                }
                selectors_info["series_number"] = {
                    "selector": "JSON data: extracted from name property",
                    "value": book_info["series_number"]
                }

        # Rating information
        if "aggregateRating" in json_data:
            rating_data = json_data["aggregateRating"]

            if "rating" not in book_info and rating_data.get("ratingValue"):
                book_info["rating"] = rating_data.get("ratingValue")
                selectors_info["rating"] = {
                    "selector": "JSON data: @type=Book, aggregateRating.ratingValue property",
                    "value": book_info["rating"]
                }

            if "rating_count" not in book_info and rating_data.get("ratingCount"):
                book_info["rating_count"] = rating_data.get("ratingCount")
                selectors_info["rating_count"] = {
                    "selector": "JSON data: @type=Book, aggregateRating.ratingCount property",
                    "value": book_info["rating_count"]
                }

            if "review_count" not in book_info and rating_data.get("reviewCount"):
                book_info["review_count"] = rating_data.get("reviewCount")
                selectors_info["review_count"] = {
                    "selector": "JSON data: @type=Book, aggregateRating.reviewCount property",
                    "value": book_info["review_count"]
                }

        # Awards
        if "awards" in json_data:
            book_info["awards"] = json_data["awards"]
            selectors_info["awards"] = {
                "selector": "JSON data: @type=Book, awards property",
                "value": book_info["awards"]
            }

    # Store the selectors information in the book_info
    book_info["_selectors"] = selectors_info

    return book_info

def extract_json_data(html):
    """Extract JSON data from script tags in the HTML."""
    soup = BeautifulSoup(html, 'html.parser')
    json_data = None

    # Look for script tags that might contain book data
    script_tags = soup.find_all('script', type='application/ld+json')
    print(f"Found {len(script_tags)} script tags with application/ld+json type")

    for i, script in enumerate(script_tags):
        try:
            data = json.loads(script.string)
            print(f"\nScript tag #{i+1} content type: {type(data)}")

            if isinstance(data, dict):
                print(f"Script tag #{i+1} keys: {list(data.keys())}")
                if data.get('@type') == 'Book':
                    print(f"Found Book data in script tag #{i+1}")
                    if 'isbn' in data:
                        print(f"ISBN found in JSON-LD data: {data['isbn']}")
                    json_data = data
                    break
        except (json.JSONDecodeError, AttributeError) as e:
            print(f"Error parsing script tag #{i+1}: {e}")
            continue

    # If we didn't find ISBN in JSON-LD, let's look for it in other script tags
    if not json_data or 'isbn' not in json_data:
        print("\nLooking for ISBN in other script tags...")
        all_scripts = soup.find_all('script')
        for i, script in enumerate(all_scripts):
            if script.string and 'isbn' in script.string.lower():
                print(f"Found 'isbn' mention in script tag #{i+1}")
                # Extract a snippet around the ISBN mention
                script_text = script.string.lower()
                isbn_index = script_text.find('isbn')
                start = max(0, isbn_index - 50)
                end = min(len(script_text), isbn_index + 100)
                print(f"Context: ...{script_text[start:end]}...")

    # Also look for ISBN in meta tags
    meta_tags = soup.find_all('meta')
    for meta in meta_tags:
        if meta.get('property') in ['books:isbn', 'book:isbn'] or meta.get('name') == 'isbn':
            print(f"Found ISBN in meta tag: {meta.get('content')}")

    return json_data

def main():
    # Initialize status object for structured output
    status = {
        "status": "initializing",
        "message": "Starting Goodreads data extraction",
        "steps": [],
        "data": {}
    }

    # Check if URL is provided as command line argument
    if len(sys.argv) > 1:
        url = sys.argv[1]
    else:
        # Default URL for testing
        url = "https://www.goodreads.com/book/show/72193.Harry_Potter_and_the_Philosopher_s_Stone"

    # Add initialization step
    status["steps"].append({
        "name": "initialization",
        "status": "success",
        "message": f"URL: {url}"
    })

    # Add environment info
    status["environment"] = {
        "python_version": sys.version,
        "beautifulsoup_version": BeautifulSoup.__version__,
        "requests_version": requests.__version__
    }

    # Fetch the page
    status["status"] = "fetching"
    status["message"] = f"Fetching page: {url}"

    html = fetch_goodreads_page(url)

    if not html:
        status["status"] = "error"
        status["message"] = "Failed to fetch page"
        status["steps"].append({
            "name": "fetch_page",
            "status": "error",
            "message": "Failed to fetch page content"
        })
        # Print status as JSON for PHP to parse
        print(f"STATUS_JSON: {json.dumps(status)}")
        return

    # Successfully fetched the page
    status["steps"].append({
        "name": "fetch_page",
        "status": "success",
        "message": "Successfully fetched page content"
    })

    # Extract book ID or title from URL for filename
    match = re.search(r'/show/(\d+)(?:-(.+))?', url)
    if match:
        book_id = match.group(1)
        book_slug = match.group(2) if match.group(2) else "book"
        raw_filename_base = f"{book_id}_{book_slug}"
    else:
        import time
        raw_filename_base = f"book_{int(time.time())}"

    # Save raw HTML for inspection
    try:
        raw_html_file = f"{raw_filename_base}_raw.html"
        with open(raw_html_file, "w", encoding="utf-8") as f:
            f.write(html)

        status["steps"].append({
            "name": "save_html",
            "status": "success",
            "message": f"Saved raw HTML to {raw_html_file}"
        })
    except Exception as e:
        status["steps"].append({
            "name": "save_html",
            "status": "warning",
            "message": f"Failed to save HTML: {str(e)}"
        })

    # Extract book information
    status["status"] = "extracting"
    status["message"] = "Extracting book information"

    book_info = extract_book_info(html)

    if not book_info:
        status["status"] = "error"
        status["message"] = "Failed to extract book information"
        status["steps"].append({
            "name": "extract_info",
            "status": "error",
            "message": "No book information could be extracted"
        })
        # Print status as JSON for PHP to parse
        print(f"STATUS_JSON: {json.dumps(status)}")
        return

    # Successfully extracted book info
    status["steps"].append({
        "name": "extract_info",
        "status": "success",
        "message": "Successfully extracted book information"
    })

    # Create a filename based on the book title
    if 'title' in book_info:
        # Clean the title to make it suitable for a filename
        clean_title = re.sub(r'[^\w\s-]', '', book_info['title'])
        clean_title = re.sub(r'\s+', '_', clean_title)
        base_filename = clean_title
    else:
        # Fallback to the raw filename base
        base_filename = raw_filename_base

    # Extract selectors info before saving
    selectors_info = book_info.pop("_selectors", {})

    # Save book info to JSON file
    try:
        book_info_file = f"{base_filename}.json"
        with open(book_info_file, "w", encoding="utf-8") as f:
            json.dump(book_info, f, indent=2)

        status["steps"].append({
            "name": "save_json",
            "status": "success",
            "message": f"Saved book information to {book_info_file}"
        })

        # Add the book data to the status object
        status["data"] = book_info

        # Check for key fields to determine completeness
        required_fields = ["title", "author", "publisher", "isbn"]
        missing_fields = [field for field in required_fields if not book_info.get(field)]

        if missing_fields:
            status["status"] = "partial"
            status["message"] = f"Extracted partial book information (missing: {', '.join(missing_fields)})"
        else:
            status["status"] = "success"
            status["message"] = "Successfully extracted complete book information"

    except Exception as e:
        status["steps"].append({
            "name": "save_json",
            "status": "error",
            "message": f"Failed to save JSON: {str(e)}"
        })
        status["status"] = "error"
        status["message"] = f"Error saving book data: {str(e)}"

    # Print status as JSON for PHP to parse
    print(f"STATUS_JSON: {json.dumps(status)}")

    # Also print the book info in a readable format for debugging
    print("\nBook Information:")
    print("=" * 60)

    for key, value in book_info.items():
        if isinstance(value, list):
            print(f"{key.upper()}: {', '.join(str(item) for item in value)}")
        else:
            print(f"{key.upper()}: {value}")

    print(f"\nSaved book information to {book_info_file}")

if __name__ == "__main__":
    main()
