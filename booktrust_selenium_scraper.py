from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.chrome.options import Options
from selenium.common.exceptions import TimeoutException, NoSuchElementException
from bs4 import BeautifulSoup
import csv
import time
import re
import hashlib

class BookTrustSeleniumScraper:
    def __init__(self, headless=True):
        self.books = []
        self.seen_books = set()
        
        # Setup Chrome options
        chrome_options = Options()
        if headless:
            chrome_options.add_argument("--headless")
        chrome_options.add_argument("--no-sandbox")
        chrome_options.add_argument("--disable-dev-shm-usage")
        chrome_options.add_argument("--disable-gpu")
        chrome_options.add_argument("--window-size=1920,1080")
        
        try:
            self.driver = webdriver.Chrome(options=chrome_options)
        except:
            print("Chrome driver not found. Trying Firefox...")
            from selenium.webdriver.firefox.options import Options as FirefoxOptions
            firefox_options = FirefoxOptions()
            if headless:
                firefox_options.add_argument("--headless")
            self.driver = webdriver.Firefox(options=firefox_options)
            
        self.wait = WebDriverWait(self.driver, 10)
        
    def extract_isbn_from_text(self, text):
        """Extract ISBN from text using regex"""
        if not text:
            return None
            
        # Clean the text
        text = text.replace('-', '').replace(' ', '')
        
        # Look for ISBN-13
        isbn13_pattern = r'(?:ISBN)?(?:978|979)\d{10}'
        match = re.search(isbn13_pattern, text)
        if match:
            return match.group().replace('ISBN', '')
            
        # Look for ISBN-10
        isbn10_pattern = r'(?:ISBN)?\d{9}[\dX]'
        match = re.search(isbn10_pattern, text)
        if match:
            return match.group().replace('ISBN', '')
            
        return None
        
    def scrape_book_detail_page(self, url):
        """Scrape individual book detail page for more information"""
        try:
            self.driver.get(url)
            time.sleep(2)
            
            book_data = {
                'title': None,
                'author': None,
                'isbn': None,
                'publisher': None,
                'description': None,
                'age_range': None
            }
            
            # Try to find title
            try:
                title_elem = self.driver.find_element(By.CSS_SELECTOR, 'h1, .book-title, .title')
                book_data['title'] = title_elem.text.strip()
            except:
                pass
                
            # Try to find author
            try:
                author_elem = self.driver.find_element(By.CSS_SELECTOR, '.author, .by-author, [class*="author"]')
                author_text = author_elem.text.strip()
                book_data['author'] = re.sub(r'^by\s+', '', author_text, flags=re.I)
            except:
                pass
                
            # Try to find ISBN
            try:
                # Look for ISBN in various places
                page_text = self.driver.find_element(By.TAG_NAME, 'body').text
                book_data['isbn'] = self.extract_isbn_from_text(page_text)
            except:
                pass
                
            # Try to find publisher
            try:
                publisher_elem = self.driver.find_element(By.CSS_SELECTOR, '.publisher, [class*="publisher"]')
                book_data['publisher'] = publisher_elem.text.strip()
            except:
                pass
                
            # Try to find description
            try:
                desc_elem = self.driver.find_element(By.CSS_SELECTOR, '.description, .summary, .blurb, [class*="description"]')
                book_data['description'] = desc_elem.text.strip()[:500]
            except:
                pass
                
            # Try to find age range
            try:
                age_elem = self.driver.find_element(By.CSS_SELECTOR, '.age-range, .age, [class*="age"]')
                book_data['age_range'] = age_elem.text.strip()
            except:
                pass
                
            return book_data
            
        except Exception as e:
            print(f"Error scraping book detail page {url}: {e}")
            return None
            
    def scrape_book_list_page(self, url, default_age_range):
        """Scrape a book list page"""
        print(f"\nScraping: {url}")
        self.driver.get(url)
        
        # Wait for page to load
        try:
            self.wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
        except TimeoutException:
            print(f"Timeout loading {url}")
            return
            
        time.sleep(3)  # Additional wait for dynamic content
        
        # Get page source and parse with BeautifulSoup
        soup = BeautifulSoup(self.driver.page_source, 'html.parser')
        
        # Find book links - BookTrust typically has book cards with links
        book_links = []
        
        # Try different selectors
        selectors = [
            'a.book-link',
            'article a',
            '.book-card a',
            '.book-item a',
            'h3 a',
            'h4 a',
            '.title a'
        ]
        
        for selector in selectors:
            links = soup.select(selector)
            if links:
                book_links.extend(links)
                break
                
        # Also try finding by href pattern
        if not book_links:
            book_links = soup.find_all('a', href=re.compile(r'/books?/|/book-'))
            
        print(f"Found {len(book_links)} book links")
        
        # Extract basic info from the list page first
        book_entries = soup.find_all(['article', 'div', 'li'], class_=re.compile(r'book|item|card'))
        
        for entry in book_entries:
            book_data = {
                'title': None,
                'author': None,
                'age_range': default_age_range,
                'isbn': None,
                'publisher': None,
                'description': None
            }
            
            # Extract title
            title_elem = entry.find(['h2', 'h3', 'h4', 'a'], class_=re.compile(r'title|heading|name'))
            if title_elem:
                book_data['title'] = title_elem.get_text(strip=True)
                
            # Extract author
            author_elem = entry.find(class_=re.compile(r'author|by'))
            if author_elem:
                author_text = author_elem.get_text(strip=True)
                book_data['author'] = re.sub(r'^by\s+', '', author_text, flags=re.I)
                
            # Extract description
            desc_elem = entry.find(class_=re.compile(r'description|summary|blurb'))
            if desc_elem:
                book_data['description'] = desc_elem.get_text(strip=True)[:500]
                
            # Add book if we have at least a title
            if book_data['title']:
                book_id = self.create_book_id(book_data)
                if book_id not in self.seen_books:
                    self.seen_books.add(book_id)
                    self.books.append(book_data)
                    print(f"Added: {book_data['title']} by {book_data['author'] or 'Unknown'}")
                    
        # If we have book detail links, scrape them for more info
        if len(book_links) < 20:  # Limit to avoid too many requests
            for link in book_links[:10]:  # Further limit for demo
                href = link.get('href')
                if href and not href.startswith('http'):
                    href = f"https://www.booktrust.org.uk{href}"
                    
                if href and '/book/' in href:
                    print(f"Scraping detail page: {href}")
                    detail_data = self.scrape_book_detail_page(href)
                    if detail_data and detail_data['title']:
                        detail_data['age_range'] = default_age_range
                        book_id = self.create_book_id(detail_data)
                        if book_id not in self.seen_books:
                            self.seen_books.add(book_id)
                            self.books.append(detail_data)
                            print(f"Added from detail: {detail_data['title']}")
                    time.sleep(1)  # Be respectful
                    
    def create_book_id(self, book_data):
        """Create a unique identifier for a book"""
        id_string = f"{(book_data['title'] or '').lower()}_{(book_data['author'] or 'unknown').lower()}"
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
                time.sleep(2)
            except Exception as e:
                print(f"Error scraping {list_info['url']}: {e}")
                
    def enrich_with_known_isbns(self):
        """Enrich book data with known ISBNs for popular titles"""
        # This is a small sample - in production, you'd have a larger database
        known_isbns = {
            "the gruffalo": "9781509804757",
            "where the wild things are": "9780370007724",
            "the very hungry caterpillar": "9780241003008",
            "room on the broom": "9781509804771",
            "the tiger who came to tea": "9780007215997",
            "we're going on a bear hunt": "9781406363395",
            "the cat in the hat": "9780007158447",
            "green eggs and ham": "9780007158461",
            "matilda": "9780141365466",
            "charlie and the chocolate factory": "9780141365374"
        }
        
        for book in self.books:
            if not book['isbn'] and book['title']:
                title_lower = book['title'].lower()
                for known_title, isbn in known_isbns.items():
                    if known_title in title_lower:
                        book['isbn'] = isbn
                        print(f"Added known ISBN for: {book['title']}")
                        break
                        
    def save_to_csv(self, filename='booktrust_books_complete.csv'):
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
                # Clean up the data
                for key in book:
                    if book[key]:
                        book[key] = book[key].strip()
                writer.writerow(book)
                
        print(f"\nSaved {len(self.books)} unique books to {filename}")
        
    def close(self):
        """Close the browser"""
        if self.driver:
            self.driver.quit()
            
    def run(self):
        """Main execution method"""
        try:
            print("Starting BookTrust Selenium scraper...")
            self.scrape_all_lists()
            self.enrich_with_known_isbns()
            self.save_to_csv()
            print("\nScraping complete!")
            
            # Print summary
            print(f"\nSummary:")
            print(f"Total unique books: {len(self.books)}")
            print(f"Books with ISBNs: {sum(1 for b in self.books if b['isbn'])}")
            print(f"Books with authors: {sum(1 for b in self.books if b['author'])}")
            print(f"Books with publishers: {sum(1 for b in self.books if b['publisher'])}")
            print(f"Books with descriptions: {sum(1 for b in self.books if b['description'])}")
            
        finally:
            self.close()

if __name__ == "__main__":
    # Run with headless=False to see the browser in action
    scraper = BookTrustSeleniumScraper(headless=True)
    scraper.run()