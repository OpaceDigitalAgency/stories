#!/usr/bin/env python3
"""
BookTrust Great Books Guide 2024-25 Scraper with Selenium
Scrapes book information from all age-specific pages
"""

import csv
import time
import re
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.common.exceptions import TimeoutException, NoSuchElementException
from bs4 import BeautifulSoup
import hashlib

class BookTrustSeleniumScraper:
    def __init__(self):
        self.books = []
        self.seen_hashes = set()
        
    def setup_driver(self):
        """Setup Chrome driver with options"""
        options = webdriver.ChromeOptions()
        options.add_argument('--headless')  # Run in background
        options.add_argument('--no-sandbox')
        options.add_argument('--disable-dev-shm-usage')
        options.add_argument('--disable-gpu')
        options.add_argument('--window-size=1920,1080')
        
        try:
            driver = webdriver.Chrome(options=options)
            return driver
        except Exception as e:
            print(f"Error setting up Chrome driver: {e}")
            print("Please ensure ChromeDriver is installed and in PATH")
            return None
    
    def extract_isbn(self, text):
        """Extract ISBN from text using regex"""
        if not text:
            return None
            
        # Look for ISBN-13 (13 digits)
        isbn13_pattern = r'(?:ISBN[:\s-]*)?(?:978|979)[\s-]?\d{1,5}[\s-]?\d{1,7}[\s-]?\d{1,6}[\s-]?\d'
        match = re.search(isbn13_pattern, text, re.IGNORECASE)
        if match:
            # Clean up the ISBN
            isbn = re.sub(r'[^\d]', '', match.group())
            if len(isbn) == 13:
                return isbn
                
        # Look for ISBN-10 (10 digits)
        isbn10_pattern = r'(?:ISBN[:\s-]*)?(?:\d{1,5}[\s-]?\d{1,7}[\s-]?\d{1,6}[\s-]?[\dX])'
        match = re.search(isbn10_pattern, text, re.IGNORECASE)
        if match:
            # Clean up the ISBN
            isbn = re.sub(r'[^\dX]', '', match.group().upper())
            if len(isbn) == 10:
                return isbn
                
        return None
    
    def scrape_book_detail_page(self, driver, url):
        """Scrape detailed information from a book's detail page"""
        try:
            driver.get(url)
            time.sleep(2)  # Wait for page to load
            
            # Wait for main content to load
            WebDriverWait(driver, 10).until(
                EC.presence_of_element_located((By.TAG_NAME, "main"))
            )
            
            soup = BeautifulSoup(driver.page_source, 'html.parser')
            
            # Extract ISBN from various possible locations
            isbn = None
            
            # Check for ISBN in metadata or specific elements
            isbn_elements = soup.find_all(text=re.compile(r'ISBN', re.IGNORECASE))
            for elem in isbn_elements:
                parent = elem.parent
                if parent:
                    isbn = self.extract_isbn(parent.get_text())
                    if isbn:
                        break
            
            # Check in book details section
            if not isbn:
                details_section = soup.find(['div', 'section'], class_=re.compile(r'detail|info|meta', re.IGNORECASE))
                if details_section:
                    isbn = self.extract_isbn(details_section.get_text())
            
            return isbn
            
        except Exception as e:
            print(f"Error scraping book detail page {url}: {e}")
            return None
    
    def scrape_age_group_page(self, driver, url, age_range):
        """Scrape all books from an age group page"""
        print(f"\nScraping {age_range} page: {url}")
        
        try:
            driver.get(url)
            time.sleep(3)  # Wait for initial page load
            
            # Accept cookies if present
            try:
                cookie_button = WebDriverWait(driver, 5).until(
                    EC.element_to_be_clickable((By.XPATH, "//button[contains(text(), 'Accept') or contains(text(), 'OK')]"))
                )
                cookie_button.click()
                time.sleep(1)
            except:
                pass
            
            # Click "Explore the guide in full" if present
            try:
                explore_button = driver.find_element(By.XPATH, "//a[contains(text(), 'Explore the guide in full')]")
                driver.execute_script("arguments[0].click();", explore_button)
                time.sleep(3)
            except:
                pass
            
            # Scroll to load all content
            last_height = driver.execute_script("return document.body.scrollHeight")
            while True:
                driver.execute_script("window.scrollTo(0, document.body.scrollHeight);")
                time.sleep(2)
                new_height = driver.execute_script("return document.body.scrollHeight")
                if new_height == last_height:
                    break
                last_height = new_height
            
            # Parse the page
            soup = BeautifulSoup(driver.page_source, 'html.parser')
            
            # Find book elements - try multiple selectors
            book_selectors = [
                'article.book',
                'div.book-item',
                'div.book-card',
                'article[class*="book"]',
                'div[class*="book-item"]',
                'div[class*="book-card"]',
                'a[href*="/books/"]',
                'div.grid article',
                'div.grid > div > article'
            ]
            
            books_found = []
            for selector in book_selectors:
                elements = soup.select(selector)
                if elements:
                    print(f"Found {len(elements)} potential book elements with selector: {selector}")
                    books_found = elements
                    break
            
            if not books_found:
                print("No book elements found, trying alternative approach...")
                # Look for any article or div that contains book-like content
                all_articles = soup.find_all(['article', 'div'], class_=True)
                for article in all_articles:
                    text = article.get_text().lower()
                    if 'author' in text or 'isbn' in text or 'publisher' in text:
                        books_found.append(article)
            
            print(f"Processing {len(books_found)} book elements...")
            
            for book_elem in books_found:
                try:
                    # Extract title
                    title = None
                    title_elem = book_elem.find(['h2', 'h3', 'h4', 'h5'], class_=True)
                    if title_elem:
                        title = title_elem.get_text(strip=True)
                    else:
                        # Try finding a link with book title
                        link = book_elem.find('a', href=re.compile(r'/books?/'))
                        if link:
                            title = link.get_text(strip=True)
                    
                    if not title:
                        continue
                    
                    # Extract author
                    author = None
                    author_patterns = [
                        r'by\s+([^,\n]+)',
                        r'author:\s*([^,\n]+)',
                        r'written by\s+([^,\n]+)'
                    ]
                    
                    book_text = book_elem.get_text()
                    for pattern in author_patterns:
                        match = re.search(pattern, book_text, re.IGNORECASE)
                        if match:
                            author = match.group(1).strip()
                            break
                    
                    # Try to find author in specific elements
                    if not author:
                        author_elem = book_elem.find(['p', 'span', 'div'], class_=re.compile(r'author|by', re.IGNORECASE))
                        if author_elem:
                            author = author_elem.get_text(strip=True)
                            author = re.sub(r'^(by|author:)\s*', '', author, flags=re.IGNORECASE).strip()
                    
                    # Extract ISBN from book element
                    isbn = self.extract_isbn(book_text)
                    
                    # If no ISBN found, try to get it from detail page
                    if not isbn:
                        detail_link = book_elem.find('a', href=re.compile(r'/books?/'))
                        if detail_link and detail_link.get('href'):
                            detail_url = detail_link['href']
                            if not detail_url.startswith('http'):
                                detail_url = f"https://www.booktrust.org.uk{detail_url}"
                            print(f"  Checking detail page for {title}: {detail_url}")
                            isbn = self.scrape_book_detail_page(driver, detail_url)
                    
                    # Create book entry
                    book = {
                        'title': title,
                        'author': author or 'Unknown',
                        'age_range': age_range,
                        'isbn': isbn or 'Not found'
                    }
                    
                    # Create hash for deduplication
                    book_str = f"{book['title']}|{book['author']}|{book['isbn']}"
                    book_hash = hashlib.md5(book_str.encode()).hexdigest()
                    
                    if book_hash not in self.seen_hashes:
                        self.seen_hashes.add(book_hash)
                        self.books.append(book)
                        print(f"  Added: {book['title']} by {book['author']} (ISBN: {book['isbn']})")
                    
                except Exception as e:
                    print(f"  Error processing book element: {e}")
                    continue
            
            print(f"Total books found on this page: {len(books_found)}")
            
        except Exception as e:
            print(f"Error scraping age group page: {e}")
    
    def scrape_all_pages(self):
        """Scrape all age group pages"""
        urls = [
            ("https://www.booktrust.org.uk/book-recommendations/booklists/great-books-guide-2024-25-for-4-to-5-year-olds/", "4-5 years"),
            ("https://www.booktrust.org.uk/book-recommendations/booklists/great-books-guide-2024-25-for-6-to-7-year-olds/", "6-7 years"),
            ("https://www.booktrust.org.uk/book-recommendations/booklists/great-books-guide-2024-25-for-8-to-9-year-olds/", "8-9 years"),
            ("https://www.booktrust.org.uk/book-recommendations/booklists/great-books-guide-2024-25-for-10-to-11-year-olds/", "10-11 years"),
            ("https://www.booktrust.org.uk/book-recommendations/booklists/great-books-guide-2024-25-for-11-plus/", "11+ years")
        ]
        
        driver = self.setup_driver()
        if not driver:
            return
        
        try:
            for url, age_range in urls:
                self.scrape_age_group_page(driver, url, age_range)
                time.sleep(2)  # Be polite between requests
        finally:
            driver.quit()
    
    def save_to_csv(self, filename='booktrust_books_selenium.csv'):
        """Save scraped books to CSV file"""
        if not self.books:
            print("No books to save!")
            return
        
        with open(filename, 'w', newline='', encoding='utf-8') as csvfile:
            fieldnames = ['title', 'author', 'age_range', 'isbn']
            writer = csv.DictWriter(csvfile, fieldnames=fieldnames)
            
            writer.writeheader()
            for book in self.books:
                writer.writerow(book)
        
        print(f"\nSaved {len(self.books)} unique books to {filename}")

def main():
    print("BookTrust Great Books Guide 2024-25 Scraper (Selenium Version)")
    print("=" * 60)
    
    scraper = BookTrustSeleniumScraper()
    scraper.scrape_all_pages()
    scraper.save_to_csv()
    
    # Print summary
    print("\nSummary by age range:")
    age_counts = {}
    for book in scraper.books:
        age = book['age_range']
        age_counts[age] = age_counts.get(age, 0) + 1
    
    for age, count in sorted(age_counts.items()):
        print(f"  {age}: {count} books")
    
    # Count books with ISBNs
    isbn_count = sum(1 for book in scraper.books if book['isbn'] != 'Not found')
    print(f"\nBooks with ISBNs: {isbn_count}/{len(scraper.books)}")

if __name__ == "__main__":
    main()