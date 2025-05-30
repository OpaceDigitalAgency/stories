#!/usr/bin/env python3
"""
Comprehensive BookTrust Great Books Guide 2024-25 Scraper
Extracts all books from all age group pages
"""

import requests
from bs4 import BeautifulSoup
import csv
import time
import re
from urllib.parse import urljoin
import hashlib

class BookTrustComprehensiveScraper:
    def __init__(self):
        self.session = requests.Session()
        self.session.headers.update({
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
        })
        self.base_url = 'https://www.booktrust.org.uk'
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
        return None
    
    def extract_isbn_from_text(self, text):
        """Extract ISBN from text using regex"""
        if not text:
            return None
            
        # Look for ISBN-13 (13 digits)
        isbn13_pattern = r'(?:ISBN[:\s-]*)?(?:978|979)[\s-]?(?:\d[\s-]?){9}\d'
        match = re.search(isbn13_pattern, text, re.IGNORECASE)
        if match:
            # Clean up the ISBN
            isbn = re.sub(r'[^\d]', '', match.group())
            if len(isbn) == 13:
                return isbn
                
        # Look for ISBN-10 (10 digits)
        isbn10_pattern = r'(?:ISBN[:\s-]*)?(?:\d[\s-]?){9}[\dXx]'
        match = re.search(isbn10_pattern, text, re.IGNORECASE)
        if match:
            # Clean up the ISBN
            isbn = re.sub(r'[^\dXx]', '', match.group())
            if len(isbn) == 10:
                return isbn
                
        return None
    
    def scrape_book_detail_page(self, url):
        """Scrape ISBN from book detail page"""
        print(f"  Checking detail page: {url}")
        response = self.get_page(url)
        if not response:
            return None
            
        soup = BeautifulSoup(response.content, 'html.parser')
        
        # Look for ISBN in various places
        # 1. Check for ISBN in metadata or specific elements
        isbn_elements = soup.find_all(text=re.compile(r'ISBN', re.IGNORECASE))
        for elem in isbn_elements:
            if elem.parent:
                text = elem.parent.get_text()
                isbn = self.extract_isbn_from_text(text)
                if isbn:
                    return isbn
        
        # 2. Check in book details section
        details_section = soup.find('div', class_='book-details') or soup.find('section', class_='book-info')
        if details_section:
            text = details_section.get_text()
            isbn = self.extract_isbn_from_text(text)
            if isbn:
                return isbn
        
        # 3. Check in any dl/dt/dd structure
        for dl in soup.find_all('dl'):
            text = dl.get_text()
            isbn = self.extract_isbn_from_text(text)
            if isbn:
                return isbn
                
        return None
    
    def parse_book_from_li(self, li_element, default_age_range):
        """Parse book information from an li element"""
        book = {
            'title': '',
            'author': '',
            'age_range': default_age_range,
            'isbn': '',
            'year': '',
            'tags': [],
            'description': '',
            'detail_url': ''
        }
        
        # Extract title and detail URL
        title_elem = li_element.find('h3', class_='heading-s')
        if title_elem:
            link = title_elem.find('a')
            if link:
                book['title'] = link.get_text(strip=True)
                book['detail_url'] = urljoin(self.base_url, link.get('href', ''))
        
        # Extract author
        author_elem = li_element.find('p', class_='body-xs')
        if author_elem:
            author_text = author_elem.get_text(strip=True)
            # Clean up author text
            author_text = author_text.replace('by ', '').strip()
            book['author'] = author_text
        
        # Extract year and age range from the metadata line
        metadata_elem = li_element.find('p', class_='body-xxs')
        if metadata_elem:
            metadata_text = metadata_elem.get_text(strip=True)
            
            # The metadata format appears to be "YYYYX to Y years" with no separator
            # Extract year first (4 digits starting with 20)
            year_match = re.search(r'^(20\d{2})', metadata_text)
            if year_match:
                book['year'] = year_match.group(1)
                # Get the age range by removing the year from the start
                age_range = metadata_text[4:].strip()  # Skip the 4-digit year
                book['age_range'] = age_range
            else:
                # No year found, assume the whole text is the age range
                book['age_range'] = metadata_text
        
        # Extract tags
        tags_container = li_element.find('ul', class_='bt-tags')
        if tags_container:
            tags = tags_container.find_all('li', class_='tag')
            book['tags'] = [tag.get_text(strip=True) for tag in tags]
        
        # Extract description
        synopsis_div = li_element.find('div', class_='short-synopsis')
        if synopsis_div:
            desc_elem = synopsis_div.find('p')
            if desc_elem:
                book['description'] = desc_elem.get_text(strip=True)
        
        return book
    
    def scrape_age_group_page(self, url, age_range):
        """Scrape all books from an age group page"""
        print(f"\nScraping {age_range} page: {url}")
        response = self.get_page(url)
        if not response:
            print(f"Failed to fetch {url}")
            return
        
        soup = BeautifulSoup(response.content, 'html.parser')
        
        # Find the book list
        book_list = soup.find('ul', class_='grid grid-cols-books gap-x-lg')
        if not book_list:
            print("Could not find book list on page")
            return
        
        # Find all book items
        book_items = book_list.find_all('li', class_='reading-width')
        print(f"Found {len(book_items)} books on {age_range} page")
        
        for li in book_items:
            book = self.parse_book_from_li(li, age_range)
            
            if book['title']:
                # Create a unique identifier for deduplication
                book_id = f"{book['title'].lower()}_{book['author'].lower()}"
                book_hash = hashlib.md5(book_id.encode()).hexdigest()
                
                if book_hash not in self.seen_books:
                    self.seen_books.add(book_hash)
                    
                    # Try to get ISBN from detail page
                    if book['detail_url'] and not book['isbn']:
                        isbn = self.scrape_book_detail_page(book['detail_url'])
                        if isbn:
                            book['isbn'] = isbn
                            print(f"  Found ISBN: {isbn}")
                    
                    self.books.append(book)
                    print(f"  Added: {book['title']} by {book['author']}")
                else:
                    print(f"  Skipped duplicate: {book['title']}")
    
    def scrape_all_pages(self):
        """Scrape all age group pages"""
        pages = [
            ('https://www.booktrust.org.uk/book-recommendations/booklists/great-books-guide-2024-25-for-4-to-5-year-olds/', '4 to 5 years'),
            ('https://www.booktrust.org.uk/book-recommendations/booklists/great-books-guide-2024-25-for-6-to-7-year-olds/', '6 to 7 years'),
            ('https://www.booktrust.org.uk/book-recommendations/booklists/great-books-guide-2024-25-for-8-to-9-year-olds/', '8 to 9 years'),
            ('https://www.booktrust.org.uk/book-recommendations/booklists/great-books-guide-2024-25-for-10-to-11-year-olds/', '10 to 11 years'),
            ('https://www.booktrust.org.uk/book-recommendations/booklists/great-books-guide-2024-25-for-11-plus-year-olds/', '11+ years')
        ]
        
        for url, age_range in pages:
            self.scrape_age_group_page(url, age_range)
            time.sleep(1)  # Be polite to the server
    
    def save_to_csv(self, filename='booktrust_books_comprehensive.csv'):
        """Save books to CSV file"""
        if not self.books:
            print("No books to save")
            return
        
        # Sort books by age range and title
        self.books.sort(key=lambda x: (x['age_range'], x['title']))
        
        with open(filename, 'w', newline='', encoding='utf-8') as csvfile:
            fieldnames = ['title', 'author', 'age_range', 'isbn', 'year', 'tags', 'description', 'detail_url']
            writer = csv.DictWriter(csvfile, fieldnames=fieldnames)
            
            writer.writeheader()
            for book in self.books:
                # Convert tags list to string
                book_copy = book.copy()
                book_copy['tags'] = ', '.join(book['tags'])
                writer.writerow(book_copy)
        
        print(f"\nSaved {len(self.books)} unique books to {filename}")

def main():
    scraper = BookTrustComprehensiveScraper()
    
    print("Starting BookTrust Great Books Guide 2024-25 scraper...")
    print("This will scrape all 5 age group pages")
    
    # Scrape all pages
    scraper.scrape_all_pages()
    
    # Save results
    scraper.save_to_csv()
    
    # Print summary
    print("\nSummary by age range:")
    age_counts = {}
    for book in scraper.books:
        age = book['age_range']
        age_counts[age] = age_counts.get(age, 0) + 1
    
    for age, count in sorted(age_counts.items()):
        print(f"  {age}: {count} books")
    
    print(f"\nTotal unique books: {len(scraper.books)}")
    
    # Count books with ISBNs
    books_with_isbn = sum(1 for book in scraper.books if book['isbn'])
    print(f"Books with ISBNs: {books_with_isbn}")

if __name__ == "__main__":
    main()