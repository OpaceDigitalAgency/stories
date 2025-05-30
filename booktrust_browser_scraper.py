import time
import csv
import re
import hashlib
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.chrome.options import Options
from selenium.common.exceptions import TimeoutException, NoSuchElementException
from bs4 import BeautifulSoup

class BookTrustBrowserScraper:
    def __init__(self):
        self.books = []
        self.seen_books = set()
        self.known_isbns = self.load_known_isbns()
        
    def load_known_isbns(self):
        """Load a database of known ISBNs for popular children's books"""
        return {
            # Picture Books (4-5 years)
            "the gruffalo": "9781509804757",
            "room on the broom": "9781509804771",
            "the tiger who came to tea": "9780007215997",
            "we're going on a bear hunt": "9781406363395",
            "the very hungry caterpillar": "9780241003008",
            "where the wild things are": "9780370007724",
            "the cat in the hat": "9780007158447",
            "green eggs and ham": "9780007158461",
            "stick man": "9781407170718",
            "the snail and the whale": "9781509812523",
            
            # Early Readers (6-7 years)
            "dog man": "9781407186672",
            "captain underpants": "9781407138312",
            "diary of a wimpy kid": "9780141324906",
            "the bad guys": "9781407188157",
            "isadora moon": "9780192744296",
            
            # Middle Grade (8-11 years)
            "harry potter and the philosopher's stone": "9781408855652",
            "matilda": "9780141365466",
            "charlie and the chocolate factory": "9780141365374",
            "the boy at the back of the class": "9781510105010",
            "wonder": "9780552565974",
            "the girl who stole an elephant": "9781788004312",
            "the nowhere emporium": "9781782503828",
            
            # Additional popular titles
            "the wonky donkey": "9781407195575",
            "the boy, the mole, the fox and the horse": "9781529105100",
            "the world's worst children": "9780008197032",
            "gangsta granny": "9780007371464",
            "the midnight gang": "9780008164621",
            "the beast of buckingham palace": "9780008262174",
            "the ice monster": "9780008164706",
        }
        
    def setup_driver(self, headless=True):
        """Setup Chrome driver with options"""
        chrome_options = Options()
        if headless:
            chrome_options.add_argument("--headless")
        chrome_options.add_argument("--no-sandbox")
        chrome_options.add_argument("--disable-dev-shm-usage")
        chrome_options.add_argument("--disable-gpu")
        chrome_options.add_argument("--window-size=1920,1080")
        chrome_options.add_argument("--user-agent=Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36")
        
        try:
            return webdriver.Chrome(options=chrome_options)
        except:
            print("Chrome driver not found. Please install ChromeDriver.")
            return None
            
    def extract_isbn_from_text(self, text):
        """Extract ISBN from text"""
        if not text:
            return None
            
        text = text.replace('-', '').replace(' ', '')
        
        # ISBN-13 pattern
        isbn13_match = re.search(r'(?:ISBN)?(?:978|979)\d{10}', text)
        if isbn13_match:
            return isbn13_match.group().replace('ISBN', '')
            
        # ISBN-10 pattern
        isbn10_match = re.search(r'(?:ISBN)?\d{9}[\dX]', text)
        if isbn10_match:
            return isbn10_match.group().replace('ISBN', '')
            
        return None
        
    def find_isbn_by_title(self, title, author=None):
        """Try to find ISBN from our known database"""
        if not title:
            return None
            
        title_lower = title.lower()
        
        # Direct match
        for known_title, isbn in self.known_isbns.items():
            if known_title in title_lower:
                return isbn
                
        # Try author + partial title match
        if author:
            author_lower = author.lower()
            if "david walliams" in author_lower:
                for book in ["gangsta granny", "the midnight gang", "the beast of buckingham palace", "the ice monster"]:
                    if book in title_lower:
                        return self.known_isbns.get(book)
            elif "julia donaldson" in author_lower:
                for book in ["the gruffalo", "room on the broom", "stick man", "the snail and the whale"]:
                    if book in title_lower:
                        return self.known_isbns.get(book)
            elif "roald dahl" in author_lower:
                for book in ["matilda", "charlie and the chocolate factory"]:
                    if book in title_lower:
                        return self.known_isbns.get(book)
                        
        return None
        
    def scrape_book_from_card(self, card_element, default_age_range):
        """Extract book information from a card element"""
        book_data = {
            'title': None,
            'author': None,
            'age_range': default_age_range,
            'isbn': None,
            'publisher': None,
            'description': None
        }
        
        try:
            # Try to find title
            title_selectors = ['h3', 'h4', '.title', '.book-title', 'a']
            for selector in title_selectors:
                try:
                    title_elem = card_element.find_element(By.CSS_SELECTOR, selector)
                    if title_elem and title_elem.text.strip():
                        book_data['title'] = title_elem.text.strip()
                        break
                except:
                    continue
                    
            # Try to find author
            author_selectors = ['.author', '.by-author', '.book-author', 'p']
            for selector in author_selectors:
                try:
                    author_elem = card_element.find_element(By.CSS_SELECTOR, selector)
                    text = author_elem.text.strip()
                    if text and ('by' in text.lower() or 'author' in text.lower() or ',' in text):
                        # Clean up author text
                        author_text = re.sub(r'^by\s+', '', text, flags=re.I)
                        author_text = re.sub(r'^Author:\s*', '', author_text, flags=re.I)
                        if author_text and len(author_text) < 100:  # Avoid descriptions
                            book_data['author'] = author_text
                            break
                except:
                    continue
                    
            # Try to find description
            try:
                desc_elem = card_element.find_element(By.CSS_SELECTOR, '.description, .summary, p:last-child')
                if desc_elem:
                    desc_text = desc_elem.text.strip()
                    if desc_text and len(desc_text) > 20:
                        book_data['description'] = desc_text[:500]
            except:
                pass
                
            # Try to find ISBN from known database
            if book_data['title']:
                book_data['isbn'] = self.find_isbn_by_title(book_data['title'], book_data['author'])
                
        except Exception as e:
            print(f"Error extracting book data: {e}")
            
        return book_data
        
    def scrape_list_page(self, driver, url, age_range):
        """Scrape a book list page"""
        print(f"\nScraping: {url}")
        driver.get(url)
        
        # Wait for page to load
        try:
            WebDriverWait(driver, 10).until(
                EC.presence_of_element_located((By.TAG_NAME, "body"))
            )
        except TimeoutException:
            print(f"Timeout loading {url}")
            return
            
        time.sleep(3)  # Additional wait for dynamic content
        
        # Try to find book cards/items
        book_selectors = [
            'article.book-card',
            'div.book-item',
            'li.book-listing',
            'article[class*="book"]',
            'div[class*="book-card"]',
            'div.grid > div',  # Grid items
            'ul.book-list > li',
            'div.books-grid > div'
        ]
        
        book_elements = []
        for selector in book_selectors:
            elements = driver.find_elements(By.CSS_SELECTOR, selector)
            if elements:
                book_elements = elements
                print(f"Found {len(elements)} book elements using selector: {selector}")
                break
                
        if not book_elements:
            # Try a more general approach
            all_articles = driver.find_elements(By.TAG_NAME, 'article')
            all_divs = driver.find_elements(By.CSS_SELECTOR, 'div[class*="col"], div[class*="item"]')
            book_elements = all_articles + all_divs
            print(f"Using general approach, found {len(book_elements)} potential elements")
            
        books_found = 0
        for element in book_elements:
            try:
                # Check if this looks like a book entry
                text = element.text.strip()
                if not text or len(text) < 10:
                    continue
                    
                book_data = self.scrape_book_from_card(element, age_range)
                
                if book_data['title']:
                    # Create unique ID for deduplication
                    book_id = self.create_book_id(book_data)
                    if book_id not in self.seen_books:
                        self.seen_books.add(book_id)
                        self.books.append(book_data)
                        books_found += 1
                        print(f"Added: {book_data['title']} by {book_data['author'] or 'Unknown'}")
                        if book_data['isbn']:
                            print(f"  ISBN: {book_data['isbn']}")
                            
            except Exception as e:
                continue
                
        print(f"Found {books_found} unique books on this page")
        
    def create_book_id(self, book_data):
        """Create a unique identifier for a book"""
        title = (book_data['title'] or '').lower().strip()
        author = (book_data['author'] or 'unknown').lower().strip()
        id_string = f"{title}_{author}"
        return hashlib.md5(id_string.encode()).hexdigest()
        
    def save_to_csv(self, filename='booktrust_books_complete.csv'):
        """Save books to CSV file"""
        if not self.books:
            print("No books to save!")
            return
            
        fieldnames = ['title', 'author', 'age_range', 'isbn', 'publisher', 'description']
        
        with open(filename, 'w', newline='', encoding='utf-8') as csvfile:
            writer = csv.DictWriter(csvfile, fieldnames=fieldnames)
            writer.writeheader()
            
            # Sort by title
            sorted_books = sorted(self.books, key=lambda x: x['title'] or '')
            
            for book in sorted_books:
                # Clean up data
                for key in book:
                    if book[key] and isinstance(book[key], str):
                        book[key] = book[key].strip()
                writer.writerow(book)
                
        print(f"\nSaved {len(self.books)} unique books to {filename}")
        
    def run(self):
        """Main execution"""
        driver = self.setup_driver(headless=False)  # Set to False to see browser
        if not driver:
            print("Failed to setup driver")
            return
            
        try:
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
            
            print("Starting BookTrust browser scraper...")
            
            for list_info in lists:
                try:
                    self.scrape_list_page(driver, list_info['url'], list_info['age_range'])
                    time.sleep(2)  # Be respectful
                except Exception as e:
                    print(f"Error scraping {list_info['url']}: {e}")
                    
            self.save_to_csv()
            
            # Print summary
            print(f"\nSummary:")
            print(f"Total unique books: {len(self.books)}")
            print(f"Books with ISBNs: {sum(1 for b in self.books if b['isbn'])}")
            print(f"Books with authors: {sum(1 for b in self.books if b['author'])}")
            print(f"Books with descriptions: {sum(1 for b in self.books if b['description'])}")
            
        finally:
            driver.quit()

if __name__ == "__main__":
    scraper = BookTrustBrowserScraper()
    scraper.run()