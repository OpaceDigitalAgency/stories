#!/usr/bin/env python3
"""
Example of how to create a custom book scraper for a different website
using the flexible_book_scraper framework
"""

from flexible_book_scraper import BookScraperBase
from bs4 import BeautifulSoup
import re
from urllib.parse import urljoin

class WaterstonesBookScraper(BookScraperBase):
    """Example scraper for Waterstones website (UK bookstore)"""
    
    def __init__(self):
        super().__init__()
        self.base_url = 'https://www.waterstones.com'
    
    def scrape_list_page(self, url, category=''):
        """Scrape a Waterstones category or search results page"""
        response = self.get_page(url)
        if not response:
            return []
        
        soup = BeautifulSoup(response.content, 'html.parser')
        
        # Find book items - adjust selector based on actual HTML structure
        book_items = soup.find_all('div', class_='book-item')
        
        books = []
        for item in book_items:
            book = self.parse_book_from_element(item)
            if category:
                book['category'] = category
            
            if book.get('title') and self.deduplicate_book(book):
                # Try to get more details from detail page
                if book.get('detail_url'):
                    details = self.scrape_detail_page(book['detail_url'])
                    book.update(details)
                
                self.books.append(book)
                books.append(book)
                print(f"  Added: {book['title']} by {book.get('author', 'Unknown')}")
        
        return books
    
    def parse_book_from_element(self, element):
        """Parse book data from Waterstones book element"""
        book = {
            'title': '',
            'author': '',
            'price': '',
            'isbn': '',
            'format': '',
            'description': '',
            'detail_url': ''
        }
        
        # Example parsing - adjust based on actual HTML structure
        # Title and URL
        title_elem = element.find('h3', class_='title') or element.find('a', class_='title')
        if title_elem:
            book['title'] = self.clean_text(title_elem.get_text())
            if title_elem.name == 'a':
                book['detail_url'] = urljoin(self.base_url, title_elem.get('href', ''))
        
        # Author
        author_elem = element.find('span', class_='author') or element.find('div', class_='author')
        if author_elem:
            book['author'] = self.clean_text(author_elem.get_text())
        
        # Price
        price_elem = element.find('span', class_='price')
        if price_elem:
            book['price'] = self.clean_text(price_elem.get_text())
        
        # Format (hardback, paperback, etc.)
        format_elem = element.find('span', class_='format')
        if format_elem:
            book['format'] = self.clean_text(format_elem.get_text())
        
        return book
    
    def scrape_detail_page(self, url):
        """Scrape additional details from book detail page"""
        print(f"  Checking detail page: {url}")
        response = self.get_page(url)
        if not response:
            return {}
        
        soup = BeautifulSoup(response.content, 'html.parser')
        details = {}
        
        # ISBN - often in product details section
        isbn_patterns = [
            r'ISBN[:\s-]*(\d{10}|\d{13})',
            r'ISBN-10[:\s-]*(\d{10})',
            r'ISBN-13[:\s-]*(\d{13})'
        ]
        
        # Look for ISBN in various places
        details_section = soup.find('div', class_='product-details') or soup.find('section', class_='details')
        if details_section:
            details_text = details_section.get_text()
            for pattern in isbn_patterns:
                match = re.search(pattern, details_text, re.IGNORECASE)
                if match:
                    isbn = match.group(1)
                    if len(isbn) == 10:
                        details['isbn10'] = isbn
                        details['isbn'] = isbn
                    elif len(isbn) == 13:
                        details['isbn13'] = isbn
                        details['isbn'] = isbn
                    break
        
        # Description
        desc_elem = soup.find('div', class_='description') or soup.find('section', class_='synopsis')
        if desc_elem:
            details['description'] = self.clean_text(desc_elem.get_text())
        
        # Publisher
        publisher_elem = soup.find('span', itemprop='publisher')
        if publisher_elem:
            details['publisher'] = self.clean_text(publisher_elem.get_text())
        
        # Publication date
        pub_date_elem = soup.find('span', itemprop='datePublished')
        if pub_date_elem:
            details['publication_date'] = self.clean_text(pub_date_elem.get_text())
        
        return details


class GoodreadsListScraper(BookScraperBase):
    """Example scraper for Goodreads lists"""
    
    def __init__(self):
        super().__init__()
        self.base_url = 'https://www.goodreads.com'
    
    def scrape_list_page(self, url):
        """Scrape a Goodreads list page"""
        response = self.get_page(url)
        if not response:
            return []
        
        soup = BeautifulSoup(response.content, 'html.parser')
        
        # Find book items in list
        book_items = soup.find_all('tr', itemtype='http://schema.org/Book')
        
        books = []
        for item in book_items:
            book = self.parse_book_from_element(item)
            
            if book.get('title') and self.deduplicate_book(book):
                self.books.append(book)
                books.append(book)
                print(f"  Added: {book['title']} by {book.get('author', 'Unknown')}")
        
        return books
    
    def parse_book_from_element(self, element):
        """Parse book data from Goodreads list element"""
        book = {
            'title': '',
            'author': '',
            'rating': '',
            'isbn': '',
            'description': '',
            'detail_url': ''
        }
        
        # Title
        title_elem = element.find('a', class_='bookTitle')
        if title_elem:
            book['title'] = self.clean_text(title_elem.get_text())
            book['detail_url'] = urljoin(self.base_url, title_elem.get('href', ''))
        
        # Author
        author_elem = element.find('a', class_='authorName')
        if author_elem:
            book['author'] = self.clean_text(author_elem.get_text())
        
        # Rating
        rating_elem = element.find('span', class_='minirating')
        if rating_elem:
            book['rating'] = self.clean_text(rating_elem.get_text())
        
        # ISBN (if available in data attributes)
        isbn_attr = element.get('data-isbn')
        if isbn_attr:
            book['isbn'] = isbn_attr
        
        return book
    
    def scrape_detail_page(self, url):
        """Goodreads detail pages require login for full info"""
        # For Goodreads, you might need to use their API or handle login
        return {}


# Usage examples
if __name__ == "__main__":
    # Example 1: Scrape Waterstones (hypothetical - adjust selectors for real site)
    print("Example 1: Waterstones Scraper")
    print("-" * 50)
    waterstones_scraper = WaterstonesBookScraper()
    
    # This is a hypothetical URL - replace with actual category URL
    # waterstones_scraper.scrape_list_page('https://www.waterstones.com/category/childrens-books', 'Children')
    # waterstones_scraper.save_to_csv('waterstones_books.csv')
    
    print("\nNote: The Waterstones scraper is a template. You need to:")
    print("1. Inspect the actual HTML structure of Waterstones")
    print("2. Update the CSS selectors in parse_book_from_element()")
    print("3. Test with a real URL")
    
    # Example 2: Scrape Goodreads list
    print("\n\nExample 2: Goodreads List Scraper")
    print("-" * 50)
    goodreads_scraper = GoodreadsListScraper()
    
    # Example: Best Books of 2024 list (hypothetical URL)
    # goodreads_scraper.scrape_list_page('https://www.goodreads.com/list/show/12345.Best_Books_of_2024')
    # goodreads_scraper.save_to_csv('goodreads_best_2024.csv')
    
    print("\nNote: The Goodreads scraper is a template. You need to:")
    print("1. Handle Goodreads' anti-scraping measures")
    print("2. Consider using their API instead")
    print("3. Update selectors based on current HTML")
    
    # Example 3: Create your own scraper
    print("\n\nExample 3: Creating Your Own Scraper")
    print("-" * 50)
    print("""
To create a scraper for a new website:

1. Inspect the website's HTML structure
2. Create a class inheriting from BookScraperBase
3. Implement parse_book_from_element() with the right selectors
4. Optionally implement scrape_detail_page() for more data
5. Test with a real URL

Key methods to override:
- parse_book_from_element(): Extract data from list items
- scrape_detail_page(): Get additional data from book pages
- scrape_list_page(): Customize the list scraping logic

The base class provides:
- HTTP session management
- Retry logic
- Text cleaning
- Deduplication
- CSV/JSON export
""")