#!/usr/bin/env python3
"""
Script to identify CSS selectors for book information on Goodreads.
This script fetches a Goodreads book page and identifies the CSS selectors
for key book information like title, author, ISBN, etc.
"""

import requests
from bs4 import BeautifulSoup
import re
import json
import os

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

def save_html(html, filename):
    """Save HTML content to a file."""
    with open(filename, "w", encoding="utf-8") as f:
        f.write(html)
    print(f"Saved raw HTML to {filename}")

def extract_json_data(html):
    """Extract JSON data from script tags in the HTML."""
    soup = BeautifulSoup(html, 'html.parser')
    json_data = None

    # Look for script tags that might contain book data
    script_tags = soup.find_all('script', type='application/ld+json')
    for script in script_tags:
        try:
            data = json.loads(script.string)
            if isinstance(data, dict) and data.get('@type') == 'Book':
                json_data = data
                # Print the JSON data for inspection
                print("\nJSON Data found in script tag:")
                print(json.dumps(data, indent=2)[:1000] + "..." if len(json.dumps(data, indent=2)) > 1000 else json.dumps(data, indent=2))
                break
        except (json.JSONDecodeError, AttributeError):
            continue

    return json_data

def find_selectors(html):
    """Find and print CSS selectors for key book information."""
    if not html:
        print("No HTML content to analyze")
        return

    soup = BeautifulSoup(html, 'html.parser')

    # Dictionary to store selectors and found values
    selectors = {}

    # Try to extract structured data first
    json_data = extract_json_data(html)
    if json_data:
        print("Found structured JSON data for the book!")

    # Title
    title_elem = soup.select_one("h1.Text__title1")
    if title_elem:
        selectors["title"] = {
            "selector": "h1.Text__title1",
            "value": title_elem.text.strip()
        }

    # Author
    author_elem = soup.select_one("span.ContributorLink__name")
    if author_elem:
        selectors["author"] = {
            "selector": "span.ContributorLink__name",
            "value": author_elem.text.strip()
        }

    # Look for book details in different formats
    # Method 1: Look for feature items
    feature_items = soup.select("div.FeatureItems div.FeatureItem")
    if feature_items:
        print(f"Found {len(feature_items)} feature items")
        for item in feature_items:
            label = item.select_one("div.FeatureItem__label")
            value = item.select_one("div.FeatureItem__value")

            if label and value:
                label_text = label.text.strip().lower()
                value_text = value.text.strip()

                if "isbn" in label_text:
                    selectors["isbn"] = {
                        "selector": "div.FeatureItem:contains('ISBN') div.FeatureItem__value",
                        "value": value_text
                    }
                elif "pages" in label_text:
                    selectors["pages"] = {
                        "selector": "div.FeatureItem:contains('pages') div.FeatureItem__value",
                        "value": value_text
                    }
                elif "published" in label_text:
                    selectors["published_date"] = {
                        "selector": "div.FeatureItem:contains('Published') div.FeatureItem__value",
                        "value": value_text
                    }

    # Method 2: Look for book details in a different format
    book_details = soup.select("div.BookDetails div.BookDetails__list span.BookDetails__label")
    if book_details:
        print(f"Found {len(book_details)} book details")
        for label_elem in book_details:
            label_text = label_elem.text.strip().lower()
            value_elem = label_elem.find_next_sibling("span", class_="BookDetails__value")

            if value_elem:
                value_text = value_elem.text.strip()

                if "isbn" in label_text:
                    selectors["isbn"] = {
                        "selector": "div.BookDetails__list span.BookDetails__label:contains('ISBN') + span.BookDetails__value",
                        "value": value_text
                    }
                elif "pages" in label_text or "length" in label_text:
                    selectors["pages"] = {
                        "selector": "div.BookDetails__list span.BookDetails__label:contains('pages') + span.BookDetails__value",
                        "value": value_text
                    }
                elif "published" in label_text:
                    selectors["published_date"] = {
                        "selector": "div.BookDetails__list span.BookDetails__label:contains('Published') + span.BookDetails__value",
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
        if isbn_match and 'isbn' not in selectors:
            selectors["isbn"] = {
                "selector": f"Pattern match: {pattern}",
                "value": isbn_match.group(1)
            }

    # Rating
    rating_elem = soup.select_one("div.RatingStatistics__rating")
    if rating_elem:
        selectors["rating"] = {
            "selector": "div.RatingStatistics__rating",
            "value": rating_elem.text.strip()
        }

    # Number of ratings
    ratings_count_elem = soup.select_one("span.RatingStatistics__meta")
    if ratings_count_elem:
        selectors["ratings_count"] = {
            "selector": "span.RatingStatistics__meta",
            "value": ratings_count_elem.text.strip()
        }

    # Description
    description_elem = soup.select_one("div.DetailsLayoutRightParagraph__widthConstrained")
    if description_elem:
        selectors["description"] = {
            "selector": "div.DetailsLayoutRightParagraph__widthConstrained",
            "value": description_elem.text.strip()[:100] + "..." if len(description_elem.text.strip()) > 100 else description_elem.text.strip()
        }

    # Cover image
    cover_img = soup.select_one("img.ResponsiveImage")
    if cover_img and cover_img.get('src'):
        selectors["cover_image"] = {
            "selector": "img.ResponsiveImage",
            "value": cover_img.get('src')
        }

    # Series information
    series_elem = soup.select_one("h3.Text__title3")
    if series_elem and 'Series' in series_elem.text:
        series_container = series_elem.find_parent("div")
        if series_container:
            series_link = series_container.select_one("a")
            if series_link:
                selectors["series"] = {
                    "selector": "h3.Text__title3:contains('Series') + div a",
                    "value": series_link.text.strip()
                }

    # Genres/Categories
    genre_elements = soup.select("span.BookPageMetadataSection__genreButton")
    if genre_elements:
        selectors["genres"] = {
            "selector": "span.BookPageMetadataSection__genreButton",
            "value": [genre.text.strip() for genre in genre_elements]
        }

    # If we found JSON data, use it to fill in any missing information
    if json_data:
        # Extract all available information from JSON data
        if 'isbn' not in selectors and json_data.get('isbn'):
            selectors["isbn"] = {
                "selector": "JSON data: @type=Book, isbn property",
                "value": json_data.get('isbn')
            }

        if 'pages' not in selectors and json_data.get('numberOfPages'):
            selectors["pages"] = {
                "selector": "JSON data: @type=Book, numberOfPages property",
                "value": str(json_data.get('numberOfPages'))
            }

        if 'published_date' not in selectors and json_data.get('datePublished'):
            selectors["published_date"] = {
                "selector": "JSON data: @type=Book, datePublished property",
                "value": json_data.get('datePublished')
            }

        # Add book format
        if 'format' not in selectors and json_data.get('bookFormat'):
            selectors["format"] = {
                "selector": "JSON data: @type=Book, bookFormat property",
                "value": json_data.get('bookFormat')
            }

        # Add language
        if 'language' not in selectors and json_data.get('inLanguage'):
            selectors["language"] = {
                "selector": "JSON data: @type=Book, inLanguage property",
                "value": json_data.get('inLanguage')
            }

        # Add awards
        if 'awards' not in selectors and json_data.get('awards'):
            selectors["awards"] = {
                "selector": "JSON data: @type=Book, awards property",
                "value": json_data.get('awards')
            }

        # Add series information from the title if available
        if 'series' not in selectors and '(' in json_data.get('name', ''):
            title = json_data.get('name', '')
            series_match = re.search(r'\((.*?),\s*#(\d+)\)', title)
            if series_match:
                series_name = series_match.group(1)
                series_number = series_match.group(2)
                selectors["series"] = {
                    "selector": "JSON data: extracted from name property",
                    "value": f"{series_name} (Book {series_number})"
                }

        # Add rating count and review count
        if 'aggregateRating' in json_data:
            rating_data = json_data['aggregateRating']

            if 'rating_count' not in selectors and rating_data.get('ratingCount'):
                selectors["rating_count"] = {
                    "selector": "JSON data: @type=Book, aggregateRating.ratingCount property",
                    "value": str(rating_data.get('ratingCount'))
                }

            if 'review_count' not in selectors and rating_data.get('reviewCount'):
                selectors["review_count"] = {
                    "selector": "JSON data: @type=Book, aggregateRating.reviewCount property",
                    "value": str(rating_data.get('reviewCount'))
                }

    return selectors

def main():
    url = "https://www.goodreads.com/book/show/72193.Harry_Potter_and_the_Philosopher_s_Stone"
    html = fetch_goodreads_page(url)

    if html:
        # Save the raw HTML
        save_html(html, "hp1.html")

        # Extract JSON data first
        json_data = extract_json_data(html)
        if json_data:
            with open("goodreads_book_data.json", "w", encoding="utf-8") as f:
                json.dump(json_data, f, indent=2)
            print(f"Saved raw book data to goodreads_book_data.json")

        # Find and print selectors (this will use the JSON data)
        selectors = find_selectors(html)

        if selectors:
            print("\nFound CSS Selectors for Goodreads Book Information:")
            print("=" * 60)

            for key, data in selectors.items():
                print(f"{key.upper()}:")
                print(f"  Selector: {data['selector']}")
                print(f"  Example Value: {data['value']}")
                print("-" * 60)

            # Save selectors to JSON file
            with open("goodreads_selectors.json", "w", encoding="utf-8") as f:
                json.dump(selectors, f, indent=2)
            print(f"Saved selectors to goodreads_selectors.json")

            # Also save a more comprehensive version with all details
            with open("goodreads_selectors_full.json", "w", encoding="utf-8") as f:
                json.dump(selectors, f, indent=2)
            print(f"Saved full selectors to goodreads_selectors_full.json")
        else:
            print("No selectors found. The page structure might have changed.")
    else:
        print("Failed to fetch the Goodreads page.")

if __name__ == "__main__":
    main()
