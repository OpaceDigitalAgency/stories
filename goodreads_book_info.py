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

    # Try to extract structured data first (most reliable)
    json_data = extract_json_data(html)

    # Extract information from HTML elements
    # Title
    title_elem = soup.select_one("h1.Text__title1")
    if title_elem:
        book_info["title"] = title_elem.text.strip()
        selectors_info["title"] = {"selector": "h1.Text__title1", "value": book_info["title"]}

    # Author
    author_elem = soup.select_one("span.ContributorLink__name")
    if author_elem:
        book_info["author"] = author_elem.text.strip()
        selectors_info["author"] = {"selector": "span.ContributorLink__name", "value": book_info["author"]}

    # Rating
    rating_elem = soup.select_one("div.RatingStatistics__rating")
    if rating_elem:
        book_info["rating"] = rating_elem.text.strip()
        selectors_info["rating"] = {"selector": "div.RatingStatistics__rating", "value": book_info["rating"]}

    # Description
    description_elem = soup.select_one("div.DetailsLayoutRightParagraph__widthConstrained")
    if description_elem:
        book_info["description"] = description_elem.text.strip()
        selectors_info["description"] = {
            "selector": "div.DetailsLayoutRightParagraph__widthConstrained",
            "value": book_info["description"][:100] + "..." if len(book_info["description"]) > 100 else book_info["description"]
        }

    # Cover image
    cover_img = soup.select_one("img.ResponsiveImage")
    if cover_img and cover_img.get('src'):
        book_info["cover_image"] = cover_img.get('src')
        selectors_info["cover_image"] = {"selector": "img.ResponsiveImage", "value": book_info["cover_image"]}

    # Genres/Categories
    genre_elements = soup.select("span.BookPageMetadataSection__genreButton")
    if genre_elements:
        book_info["genres"] = [genre.text.strip() for genre in genre_elements]
        selectors_info["genres"] = {"selector": "span.BookPageMetadataSection__genreButton", "value": book_info["genres"]}

    # Look for book details in different formats
    # Method 1: Look for feature items (common in newer Goodreads design)
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
                    selectors_info["isbn"] = {
                        "selector": "div.FeatureItem:contains('ISBN') div.FeatureItem__value",
                        "value": value_text
                    }
                elif "pages" in label_text:
                    book_info["pages"] = value_text
                    selectors_info["pages"] = {
                        "selector": "div.FeatureItem:contains('pages') div.FeatureItem__value",
                        "value": value_text
                    }
                elif "published" in label_text:
                    book_info["published_date"] = value_text
                    selectors_info["published_date"] = {
                        "selector": "div.FeatureItem:contains('Published') div.FeatureItem__value",
                        "value": value_text
                    }
                elif "publisher" in label_text:
                    book_info["publisher"] = value_text
                    selectors_info["publisher"] = {
                        "selector": "div.FeatureItem:contains('Publisher') div.FeatureItem__value",
                        "value": value_text
                    }
                elif "language" in label_text:
                    book_info["language"] = value_text
                    selectors_info["language"] = {
                        "selector": "div.FeatureItem:contains('Language') div.FeatureItem__value",
                        "value": value_text
                    }

    # Method 2: Look for book details in a different format (common in older Goodreads design)
    book_details = soup.select("div.BookDetails div.BookDetails__list span.BookDetails__label")
    if book_details:
        for label_elem in book_details:
            label_text = label_elem.text.strip().lower()
            value_elem = label_elem.find_next_sibling("span", class_="BookDetails__value")

            if value_elem:
                value_text = value_elem.text.strip()

                if "isbn" in label_text:
                    book_info["isbn"] = value_text
                    selectors_info["isbn"] = {
                        "selector": "div.BookDetails__list span.BookDetails__label:contains('ISBN') + span.BookDetails__value",
                        "value": value_text
                    }
                elif "pages" in label_text or "length" in label_text:
                    book_info["pages"] = value_text
                    selectors_info["pages"] = {
                        "selector": "div.BookDetails__list span.BookDetails__label:contains('pages') + span.BookDetails__value",
                        "value": value_text
                    }
                elif "published" in label_text:
                    book_info["published_date"] = value_text
                    selectors_info["published_date"] = {
                        "selector": "div.BookDetails__list span.BookDetails__label:contains('Published') + span.BookDetails__value",
                        "value": value_text
                    }
                elif "publisher" in label_text:
                    book_info["publisher"] = value_text
                    selectors_info["publisher"] = {
                        "selector": "div.BookDetails__list span.BookDetails__label:contains('Publisher') + span.BookDetails__value",
                        "value": value_text
                    }
                elif "language" in label_text:
                    book_info["language"] = value_text
                    selectors_info["language"] = {
                        "selector": "div.BookDetails__list span.BookDetails__label:contains('Language') + span.BookDetails__value",
                        "value": value_text
                    }

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
    # Check if URL is provided as command line argument
    if len(sys.argv) > 1:
        url = sys.argv[1]
    else:
        # Default URL for testing
        url = "https://www.goodreads.com/book/show/72193.Harry_Potter_and_the_Philosopher_s_Stone"

    print(f"Fetching book information from: {url}")
    html = fetch_goodreads_page(url)

    if html:
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
        raw_html_file = f"{raw_filename_base}_raw.html"
        with open(raw_html_file, "w", encoding="utf-8") as f:
            f.write(html)
        print(f"Saved raw HTML to {raw_html_file}")

        # Extract book information
        book_info = extract_book_info(html)

        if book_info:
            # Create a filename based on the book title
            if 'title' in book_info:
                # Clean the title to make it suitable for a filename
                clean_title = re.sub(r'[^\w\s-]', '', book_info['title'])
                clean_title = re.sub(r'\s+', '_', clean_title)
                base_filename = clean_title
            else:
                # Fallback to the raw filename base
                base_filename = raw_filename_base

            # Extract selectors info before printing
            selectors_info = book_info.pop("_selectors", {})

            # Print book information
            print("\nBook Information:")
            print("=" * 60)

            for key, value in book_info.items():
                if isinstance(value, list):
                    print(f"{key.upper()}: {', '.join(str(item) for item in value)}")
                else:
                    print(f"{key.upper()}: {value}")

            # Save book info to JSON file
            book_info_file = f"{base_filename}.json"
            with open(book_info_file, "w", encoding="utf-8") as f:
                json.dump(book_info, f, indent=2)
            print(f"\nSaved book information to {book_info_file}")

            # Save selectors info to a separate file
            selectors_file = f"{base_filename}_selectors.json"
            with open(selectors_file, "w", encoding="utf-8") as f:
                json.dump(selectors_info, f, indent=2)
            print(f"Saved selectors information to {selectors_file}")

            # Create a combined reference file with both data and selectors
            reference_file = f"{base_filename}_reference.json"
            reference_data = {}
            for key, selector_info in selectors_info.items():
                reference_data[key] = {
                    "selector": selector_info["selector"],
                    "example_value": selector_info["value"],
                    "actual_value": book_info.get(key, "Not found in book data")
                }

            with open(reference_file, "w", encoding="utf-8") as f:
                json.dump(reference_data, f, indent=2)
            print(f"Saved reference information to {reference_file}")

            # Save raw JSON data for inspection
            if 'isbn' in book_info:
                print(f"\nISBN found in book_info: {book_info['isbn']}")
                print("Searching raw HTML for this ISBN...")
                isbn = book_info['isbn']
                if isbn in html:
                    print(f"ISBN {isbn} found in raw HTML!")
                    # Find the context around the ISBN
                    isbn_index = html.find(isbn)
                    start = max(0, isbn_index - 100)
                    end = min(len(html), isbn_index + 100)
                    print(f"Context: ...{html[start:end]}...")
                else:
                    print(f"ISBN {isbn} NOT found in raw HTML as a direct string!")
        else:
            print("Failed to extract book information.")
    else:
        print("Failed to fetch the Goodreads page.")

if __name__ == "__main__":
    main()
