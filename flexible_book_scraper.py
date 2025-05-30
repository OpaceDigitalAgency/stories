import requests
from bs4 import BeautifulSoup
import csv
import time
import re
from urllib.parse import urljoin
import hashlib
import html
from abc import ABC, abstractmethod
import json

class BookScraperBase(ABC):
    """Base class for book scrapers - extend this for different websites"""
    
    def __init__(self):
        self.session = requests.Session()
        self.session.headers.update({
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        })
        self.books = []
        self.seen_books = set()
    
    def clean_text(self, text):
        """Clean text by removing special characters and fixing encoding issues"""
        if not text:
            return text
        # Decode HTML entities
        text = html.unescape(text)
        # Fix common encoding issues
        replacements = {
            ''': "'",
            ''': "'",
            '"': '"',
            '"': '"',
            '–': '-',
            '—': '-',
            '…': '...',
            '\u200b': '',  # Zero-width space
            '\xa0': ' ',   # Non-breaking space
        }
        for old, new in replacements.items():
            text = text.replace(old, new)
        return text.strip()
    
    def get_page(self, url, retries=3):
        """Get page with retry logic"""
        for attempt in range(retries):
            try:
                response = self.session.get(url, timeout=30)
                response.raise_for_status()
                return response
            except Exception as e:
                print(f"Attempt {attempt + 1} failed for {url}: {e}")
                if attempt < retries - 1:
                    time.sleep(2)
        return None
    
    def deduplicate_book(self, book):
        """Check if book is duplicate based on title and author"""
        book_id = f"{book.get('title', '').lower()}_{book.get('author', '').lower()}"
        book_hash = hashlib.md5(book_id.encode()).hexdigest()
        
        if book_hash not in self.seen_books:
            self.seen_books.add(book_hash)
            return True
        return False
    
    @abstractmethod
    def scrape_list_page(self, url):
        """Override this method to scrape a list page"""
        pass
    
    @abstractmethod
    def scrape_detail_page(self, url):
        """Override this method to scrape book details"""
        pass
    
    @abstractmethod
    def parse_book_from_element(self, element):
        """Override this method to parse book data from HTML element"""
        pass
    
    def save_to_csv(self, filename):
        """Save books to CSV file"""
        if not self.books:
            print("No books to save")
            return
        
        # Get all unique keys from all books
        all_keys = set()
        for book in self.books:
            all_keys.update(book.keys())
        
        fieldnames = sorted(list(all_keys))
        
        with open(filename, 'w', newline='', encoding='utf-8') as csvfile:
            writer = csv.DictWriter(csvfile, fieldnames=fieldnames)
            writer.writeheader()
            
            for book in self.books:
                # Convert lists to strings
                book_copy = book.copy()
                for key, value in book_copy.items():
                    if isinstance(value, list):
                        book_copy[key] = ', '.join(value)
                writer.writerow(book_copy)
        
        print(f"Saved {len(self.books)} books to {filename}")
    
    def save_to_json(self, filename):
        """Save books to JSON file"""
        with open(filename, 'w', encoding='utf-8') as f:
            json.dump(self.books, f, indent=2, ensure_ascii=False)
        print(f"Saved {len(self.books)} books to {filename}")


class BookTrustScraper(BookScraperBase):
    """Scraper specifically for BookTrust website"""
    
    def __init__(self):
        super().__init__()
        self.base_url = 'https://www.booktrust.org.uk'
    
    def scrape_list_page(self, url, default_age_range=''):
        """Scrape a BookTrust list page"""
        response = self.get_page(url)
        if not response:
            return []
        
        soup = BeautifulSoup(response.content, 'html.parser')
        book_items = soup.find_all('li', class_='book-item')
        
        books = []
        for item in book_items:
            book = self.parse_book_from_element(item)
            if not book.get('age_range') and default_age_range:
                book['age_range'] = default_age_range
            
            if book.get('title') and self.deduplicate_book(book):
                # Try to get more details from detail page
                if book.get('detail_url'):
                    details = self.scrape_detail_page(book['detail_url'])
                    book.update(details)
                
                self.books.append(book)
                books.append(book)
                print(f"  Added: {book['title']} by {book['author']}")
        
        return books
    
    def parse_book_from_element(self, element):
        """Parse book data from BookTrust li element"""
        book = {
            'title': '',
            'author': '',
            'age_range': '',
            'isbn': '',
            'year': '',
            'tags': [],
            'description': '',
            'detail_url': ''
        }
        
        # Extract title and URL
        title_elem = element.find('h3', class_='heading-s')
        if title_elem:
            link = title_elem.find('a')
            if link:
                book['title'] = self.clean_text(link.get_text(strip=True))
                book['detail_url'] = urljoin(self.base_url, link.get('href', ''))
        
        # Extract author
        author_elem = element.find('p', class_='body-xs')
        if author_elem:
            author_text = author_elem.get_text(strip=True)
            author_text = author_text.replace('by ', '').strip()
            book['author'] = self.clean_text(author_text)
        
        # Extract metadata (year and age range)
        metadata_elem = element.find('p', class_='body-xxs')
        if metadata_elem:
            metadata_text = metadata_elem.get_text(strip=True)
            # Extract year first (4 digits starting with 20)
            year_match = re.search(r'^(20\d{2})', metadata_text)
            if year_match:
                book['year'] = year_match.group(1)
                # Get the age range by removing the year from the start
                age_range = metadata_text[4:].strip()
                book['age_range'] = age_range
        
        # Extract tags
        tags_container = element.find('ul', class_='bt-tags')
        if tags_container:
            tags = tags_container.find_all('li', class_='tag')
            book['tags'] = [self.clean_text(tag.get_text(strip=True)) for tag in tags]
        
        # Extract description
        synopsis_div = element.find('div', class_='short-synopsis')
        if synopsis_div:
            desc_elem = synopsis_div.find('p')
            if desc_elem:
                book['description'] = self.clean_text(desc_elem.get_text(strip=True))
        
        return book
    
    def scrape_detail_page(self, url):
        """Scrape additional details from book detail page"""
        print(f"  Checking detail page: {url}")
        response = self.get_page(url)
        if not response:
            return {}
        
        soup = BeautifulSoup(response.content, 'html.parser')
        details = {}
        
        # Try to find ISBN
        isbn_patterns = [
            r'ISBN[:\s-]*(\d{10}|\d{13})',
            r'ISBN-10[:\s-]*(\d{10})',
            r'ISBN-13[:\s-]*(\d{13})'
        ]
        
        page_text = soup.get_text()
        for pattern in isbn_patterns:
            match = re.search(pattern, page_text, re.IGNORECASE)
            if match:
                isbn = match.group(1)
                if len(isbn) == 10:
                    details['isbn'] = isbn
                elif len(isbn) == 13:
                    details['isbn13'] = isbn
                break
        
        return details


# Example scraper for another website (template)
class GenericBookScraper(BookScraperBase):
    """Template for creating scrapers for other book websites"""
    
    def __init__(self, base_url):
        super().__init__()
        self.base_url = base_url
    
    def scrape_list_page(self, url):
        """Override with site-specific implementation"""
        response = self.get_page(url)
        if not response:
            return []
        
        soup = BeautifulSoup(response.content, 'html.parser')
        
        # TODO: Find book elements on the page
        # book_elements = soup.find_all('div', class_='book-item')
        
        books = []
        # for element in book_elements:
        #     book = self.parse_book_from_element(element)
        #     if book and self.deduplicate_book(book):
        #         self.books.append(book)
        #         books.append(book)
        
        return books
    
    def parse_book_from_element(self, element):
        """Override with site-specific parsing logic"""
        book = {
            'title': '',
            'author': '',
            'isbn': '',
            'description': '',
            'detail_url': ''
        }
        
        # TODO: Extract book data from element
        # Example:
        # title_elem = element.find('h2', class_='title')
        # if title_elem:
        #     book['title'] = self.clean_text(title_elem.get_text())
        
        return book
    
    def scrape_detail_page(self, url):
        """Override to scrape additional details"""
        return {}


# Usage example
if __name__ == "__main__":
    # Example: Scrape BookTrust
    scraper = BookTrustScraper()
    
    # Define pages to scrape
    pages = [
        ('https://www.booktrust.org.uk/book-recommendations/booklists/great-books-guide-2024-25-for-4-to-5-year-olds/', '4 to 5 years'),
        ('https://www.booktrust.org.uk/book-recommendations/booklists/great-books-guide-2024-25-for-6-to-7-year-olds/', '6 to 7 years'),
    ]
    
    # Scrape all pages
    for url, age_range in pages:
        print(f"\nScraping {age_range} page: {url}")
        scraper.scrape_list_page(url, age_range)
        time.sleep(1)  # Be polite
    
    # Save results
    scraper.save_to_csv('books_output.csv')
    scraper.save_to_json('books_output.json')