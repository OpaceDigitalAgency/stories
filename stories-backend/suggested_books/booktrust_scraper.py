import requests
from bs4 import BeautifulSoup
import csv
import time
from urllib.parse import urljoin
import re
import hashlib

class BookTrustScraper:
    def __init__(self):
        self.base_url = "https://www.booktrust.org.uk"
        self.session = requests.Session()
        self.session.headers.update({
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
        })
        self.books = []
        self.seen_books = set()  # For deduplication
        
    def get_page(self, url):
        """Fetch a page with retry logic"""
        for attempt in range(3):
            try:
                response = self.session.get(url, timeout=30)
                response.raise_for_status()
                return response
            except Exception as e:
                print(f"Attempt {attempt + 1} failed for {url}: {e}")
                if attempt < 2:
                    time.sleep(2)
                else:
                    raise
                    
    def extract_isbn_from_text(self, text):
        """Extract ISBN from text using regex"""
        if not text:
            return None
            
        # Look for ISBN-13 (13 digits)
        isbn13_pattern = r'(?:ISBN[:\s-]*)?(?:978|979)[\s-]?\d{1,5}[\s-]?\d{1,7}[\s-]?\d{1,6}[\s-]?\d'
        match = re.search(isbn13_pattern, text.replace('-', ''))
        if match:
            isbn = re.sub(r'[^\d]', '', match.group())
            if len(isbn) == 13:
                return isbn
                
        # Look for ISBN-10 (10 digits)
        isbn10_pattern = r'(?:ISBN[:\s-]*)?(?:\d{1,5}[\s-]?\d{1,7}[\s-]?\d{1,6}[\s-]?[\dX])'
        match = re.search(isbn10_pattern, text.replace('-', ''))
        if match:
            isbn = re.sub(r'[^\dX]', '', match.group())
            if len(isbn) == 10:
                return isbn
                
        return None
        
    def scrape_book_list_page(self, url, age_range):
        """Scrape a book list page"""
        print(f"\nScraping: {url}")
        response = self.get_page(url)
        soup = BeautifulSoup(response.content, 'html.parser')
        
        # Find all book entries
        book_entries = soup.find_all('article', class_='book-card') or \
                      soup.find_all('div', class_='book-item') or \
                      soup.find_all('li', class_='book-listing')
        
        # If no specific book entries found, look for general article/div patterns
        if not book_entries:
            book_entries = soup.find_all(['article', 'div'], class_=re.compile(r'book|item|card'))
            
        print(f"Found {len(book_entries)} potential book entries")
        
        for entry in book_entries:
            book_data = self.extract_book_info(entry, age_range)
            if book_data and book_data['title']:  # Only add if we have at least a title
                # Create a unique identifier for deduplication
                book_id = self.create_book_id(book_data)
                if book_id not in self.seen_books:
                    self.seen_books.add(book_id)
                    self.books.append(book_data)
                    print(f"Added: {book_data['title']} by {book_data['author'] or 'Unknown'}")
                    
    def extract_book_info(self, element, default_age_range):
        """Extract book information from an element"""
        book_data = {
            'title': None,
            'author': None,
            'age_range': default_age_range,
            'isbn': None,
            'publisher': None,
            'description': None
        }
        
        # Try to find title
        title_elem = element.find(['h2', 'h3', 'h4'], class_=re.compile(r'title|heading|name'))
        if not title_elem:
            title_elem = element.find('a', class_=re.compile(r'title|book'))
        if not title_elem:
            title_elem = element.find(['h2', 'h3', 'h4'])
        if title_elem:
            book_data['title'] = title_elem.get_text(strip=True)
            
        # Try to find author
        author_elem = element.find(class_=re.compile(r'author|by'))
        if not author_elem:
            author_elem = element.find(text=re.compile(r'^by\s+', re.I))
        if author_elem:
            author_text = author_elem.get_text(strip=True) if hasattr(author_elem, 'get_text') else str(author_elem)
            book_data['author'] = re.sub(r'^by\s+', '', author_text, flags=re.I).strip()
            
        # Try to find ISBN
        isbn_elem = element.find(text=re.compile(r'ISBN'))
        if isbn_elem:
            book_data['isbn'] = self.extract_isbn_from_text(str(isbn_elem))
        else:
            # Check in various attributes
            for attr in ['data-isbn', 'isbn']:
                if element.get(attr):
                    book_data['isbn'] = element.get(attr)
                    break
                    
        # Try to find publisher
        publisher_elem = element.find(class_=re.compile(r'publisher'))
        if publisher_elem:
            book_data['publisher'] = publisher_elem.get_text(strip=True)
            
        # Try to find description
        desc_elem = element.find(class_=re.compile(r'description|summary|blurb'))
        if desc_elem:
            book_data['description'] = desc_elem.get_text(strip=True)[:500]  # Limit length
            
        return book_data
        
    def create_book_id(self, book_data):
        """Create a unique identifier for a book"""
        # Use title and author for deduplication
        id_string = f"{book_data['title'].lower()}_{book_data['author'].lower() if book_data['author'] else 'unknown'}"
        return hashlib.md5(id_string.encode()).hexdigest()
        
    def scrape_all_lists(self):
        """Scrape all the book lists"""
        lists = [
            {
                'url': 'https://www.booktrust.org.uk/book-recommendations/booklists/great-books-guide-2024-25-for-4-to-5-year-olds/',
                'age_range': '4-5 years'
            },
            {
                'url': 'https://www.booktrust.org.uk/book-recommendations/booklists/great-books-guide-2024-25-for-6-to-7-year-olds/',
                'age_range': '6-7 years'
            },
            {
                'url': 'https://www.booktrust.org.uk/book-recommendations/booklists/great-books-guide-2024-25-for-8-to-9-year-olds/',
                'age_range': '8-9 years'
            },
            {
                'url': 'https://www.booktrust.org.uk/book-recommendations/booklists/great-books-guide-2024-25-for-10-to-11-year-olds/',
                'age_range': '10-11 years'
            },
            {
                'url': 'https://www.booktrust.org.uk/book-recommendations/booklists/great-books-guide-2024-25-for-11/',
                'age_range': '11+ years'
            }
        ]
        
        for list_info in lists:
            try:
                self.scrape_book_list_page(list_info['url'], list_info['age_range'])
                time.sleep(2)  # Be respectful to the server
            except Exception as e:
                print(f"Error scraping {list_info['url']}: {e}")
                
    def save_to_csv(self, filename='booktrust_books.csv'):
        """Save the scraped books to a CSV file"""
        if not self.books:
            print("No books to save!")
            return
            
        fieldnames = ['title', 'author', 'age_range', 'isbn', 'publisher', 'description']
        
        with open(filename, 'w', newline='', encoding='utf-8') as csvfile:
            writer = csv.DictWriter(csvfile, fieldnames=fieldnames)
            writer.writeheader()
            
            # Sort books by title for better organization
            sorted_books = sorted(self.books, key=lambda x: x['title'] or '')
            
            for book in sorted_books:
                writer.writerow(book)
                
        print(f"\nSaved {len(self.books)} unique books to {filename}")
        
    def run(self):
        """Main execution method"""
        print("Starting BookTrust scraper...")
        self.scrape_all_lists()
        self.save_to_csv()
        print("\nScraping complete!")
        
        # Print summary
        print(f"\nSummary:")
        print(f"Total unique books: {len(self.books)}")
        print(f"Books with ISBNs: {sum(1 for b in self.books if b['isbn'])}")
        print(f"Books with authors: {sum(1 for b in self.books if b['author'])}")

if __name__ == "__main__":
    scraper = BookTrustScraper()
    scraper.run()