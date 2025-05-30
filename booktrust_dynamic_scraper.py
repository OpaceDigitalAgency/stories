import requests
from bs4 import BeautifulSoup
import csv
import time
import re
import hashlib
from urllib.parse import urljoin

class BookTrustDynamicScraper:
    def __init__(self):
        self.base_url = "https://www.booktrust.org.uk"
        self.session = requests.Session()
        self.session.headers.update({
            'User-Agent': 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Language': 'en-US,en;q=0.5',
            'Accept-Encoding': 'gzip, deflate, br',
            'Connection': 'keep-alive',
            'Upgrade-Insecure-Requests': '1'
        })
        self.books = []
        self.seen_books = set()
        
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
            
        # Clean the text
        text = str(text).replace('-', '').replace(' ', '')
        
        # Look for ISBN-13 (13 digits)
        isbn13_pattern = r'(?:ISBN[:\s]*)?(?:978|979)\d{10}'
        match = re.search(isbn13_pattern, text)
        if match:
            isbn = re.sub(r'[^\d]', '', match.group())
            if len(isbn) == 13:
                return isbn
                
        # Look for ISBN-10 (10 digits)
        isbn10_pattern = r'(?:ISBN[:\s]*)?(?:\d{9}[\dX])'
        match = re.search(isbn10_pattern, text)
        if match:
            isbn = re.sub(r'[^\dX]', '', match.group())
            if len(isbn) == 10:
                return isbn
                
        return None
        
    def scrape_book_detail_page(self, url):
        """Scrape individual book detail page for more information"""
        try:
            print(f"  Scraping detail page: {url}")
            response = self.get_page(url)
            soup = BeautifulSoup(response.content, 'html.parser')
            
            book_data = {
                'title': None,
                'author': None,
                'isbn': None,
                'publisher': None,
                'description': None,
                'age_range': None
            }
            
            # Extract title - try multiple selectors
            title_selectors = ['h1', '.book-title', '.title', '[class*="title"]']
            for selector in title_selectors:
                title_elem = soup.select_one(selector)
                if title_elem and title_elem.text.strip():
                    book_data['title'] = title_elem.text.strip()
                    break
                    
            # Extract author - look for various patterns
            author_patterns = [
                '.author', '.by-author', '.book-author', '[class*="author"]',
                'p:contains("by")', 'span:contains("by")', 'div:contains("by")'
            ]
            for pattern in author_patterns:
                try:
                    if ':contains' in pattern:
                        # BeautifulSoup doesn't support :contains, so we search differently
                        elements = soup.find_all(pattern.split(':')[0])
                        for elem in elements:
                            if 'by' in elem.text.lower():
                                author_text = elem.text.strip()
                                author_text = re.sub(r'^by\s+', '', author_text, flags=re.I)
                                if author_text and len(author_text) < 100:
                                    book_data['author'] = author_text
                                    break
                    else:
                        author_elem = soup.select_one(pattern)
                        if author_elem:
                            author_text = author_elem.text.strip()
                            author_text = re.sub(r'^by\s+', '', author_text, flags=re.I)
                            if author_text and len(author_text) < 100:
                                book_data['author'] = author_text
                                break
                except:
                    continue
                    
            # Extract ISBN - search entire page
            page_text = soup.get_text()
            book_data['isbn'] = self.extract_isbn_from_text(page_text)
            
            # Also check meta tags and structured data
            if not book_data['isbn']:
                # Check meta tags
                isbn_meta = soup.find('meta', {'property': 'books:isbn'}) or \
                           soup.find('meta', {'name': 'isbn'})
                if isbn_meta and isbn_meta.get('content'):
                    book_data['isbn'] = self.extract_isbn_from_text(isbn_meta['content'])
                    
            # Extract publisher
            publisher_selectors = ['.publisher', '[class*="publisher"]', 'span:contains("Publisher")', 'p:contains("Publisher")']
            for selector in publisher_selectors:
                try:
                    if ':contains' in selector:
                        elements = soup.find_all(selector.split(':')[0])
                        for elem in elements:
                            if 'publisher' in elem.text.lower():
                                pub_text = elem.text.strip()
                                pub_text = re.sub(r'Publisher[:\s]*', '', pub_text, flags=re.I)
                                if pub_text and len(pub_text) < 100:
                                    book_data['publisher'] = pub_text
                                    break
                    else:
                        pub_elem = soup.select_one(selector)
                        if pub_elem:
                            book_data['publisher'] = pub_elem.text.strip()
                            break
                except:
                    continue
                    
            # Extract description
            desc_selectors = ['.description', '.summary', '.blurb', '[class*="description"]', '.book-description']
            for selector in desc_selectors:
                desc_elem = soup.select_one(selector)
                if desc_elem:
                    desc_text = desc_elem.text.strip()
                    if desc_text and len(desc_text) > 20:
                        book_data['description'] = desc_text[:500]
                        break
                        
            # Extract age range
            age_selectors = ['.age-range', '.age', '[class*="age"]', 'span:contains("Age")', 'p:contains("Age")']
            for selector in age_selectors:
                try:
                    if ':contains' in selector:
                        elements = soup.find_all(selector.split(':')[0])
                        for elem in elements:
                            if 'age' in elem.text.lower():
                                age_text = elem.text.strip()
                                if age_text and len(age_text) < 50:
                                    book_data['age_range'] = age_text
                                    break
                    else:
                        age_elem = soup.select_one(selector)
                        if age_elem:
                            book_data['age_range'] = age_elem.text.strip()
                            break
                except:
                    continue
                    
            return book_data
            
        except Exception as e:
            print(f"    Error scraping detail page: {e}")
            return None
            
    def scrape_book_list_page(self, url, default_age_range):
        """Scrape a book list page and extract all books"""
        print(f"\nScraping list page: {url}")
        response = self.get_page(url)
        soup = BeautifulSoup(response.content, 'html.parser')
        
        # Find all links that might be book detail pages
        book_links = []
        
        # Look for links with book-related patterns
        link_patterns = [
            'a[href*="/books/"]',
            'a[href*="/book/"]',
            'a[href*="/book-"]',
            '.book-card a',
            '.book-item a',
            'article a',
            'h3 a',
            'h4 a'
        ]
        
        for pattern in link_patterns:
            links = soup.select(pattern)
            for link in links:
                href = link.get('href')
                if href and '/book' in href:
                    full_url = urljoin(self.base_url, href)
                    if full_url not in book_links:
                        book_links.append(full_url)
                        
        print(f"Found {len(book_links)} potential book detail links")
        
        # Also try to extract books directly from the list page
        book_containers = soup.select('article, .book-card, .book-item, [class*="book-card"], [class*="book-item"]')
        
        print(f"Found {len(book_containers)} book containers on the page")
        
        # Extract from containers first
        for container in book_containers:
            book_data = {
                'title': None,
                'author': None,
                'age_range': default_age_range,
                'isbn': None,
                'publisher': None,
                'description': None
            }
            
            # Extract title
            title_elem = container.find(['h2', 'h3', 'h4', 'a'])
            if title_elem:
                title_text = title_elem.text.strip()
                if title_text and not title_text.startswith('http'):
                    book_data['title'] = title_text
                    
            # Extract author
            author_elem = container.find(string=re.compile(r'\bby\s+', re.I))
            if author_elem:
                author_text = str(author_elem).strip()
                author_text = re.sub(r'^.*\bby\s+', '', author_text, flags=re.I)
                author_text = author_text.split('\n')[0].strip()
                if author_text and len(author_text) < 100:
                    book_data['author'] = author_text
            else:
                # Try class-based search
                for class_name in ['author', 'by-author', 'book-author']:
                    author_elem = container.find(class_=re.compile(class_name, re.I))
                    if author_elem:
                        book_data['author'] = author_elem.text.strip()
                        break
                        
            # Extract description
            desc_elem = container.find(['p', 'div'], class_=re.compile(r'desc|summary|blurb', re.I))
            if desc_elem:
                book_data['description'] = desc_elem.text.strip()[:500]
                
            # Extract ISBN from container text
            container_text = container.get_text()
            book_data['isbn'] = self.extract_isbn_from_text(container_text)
            
            # Add book if we have at least a title
            if book_data['title']:
                book_id = self.create_book_id(book_data)
                if book_id not in self.seen_books:
                    self.seen_books.add(book_id)
                    self.books.append(book_data)
                    print(f"Added from list: {book_data['title']} by {book_data['author'] or 'Unknown'}")
                    
        # Now scrape detail pages for more complete information
        for i, book_url in enumerate(book_links[:20]):  # Limit to 20 to avoid too many requests
            try:
                detail_data = self.scrape_book_detail_page(book_url)
                if detail_data and detail_data['title']:
                    # Set default age range if not found
                    if not detail_data['age_range']:
                        detail_data['age_range'] = default_age_range
                        
                    book_id = self.create_book_id(detail_data)
                    if book_id not in self.seen_books:
                        self.seen_books.add(book_id)
                        self.books.append(detail_data)
                        print(f"  Added from detail: {detail_data['title']} by {detail_data['author'] or 'Unknown'}")
                        if detail_data['isbn']:
                            print(f"    ISBN: {detail_data['isbn']}")
                            
                time.sleep(1)  # Be respectful to the server
                
            except Exception as e:
                print(f"  Error processing book link {book_url}: {e}")
                continue
                
    def create_book_id(self, book_data):
        """Create a unique identifier for a book"""
        title = (book_data['title'] or '').lower().strip()
        author = (book_data['author'] or 'unknown').lower().strip()
        id_string = f"{title}_{author}"
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
                    if book[key] and isinstance(book[key], str):
                        book[key] = book[key].strip()
                writer.writerow(book)
                
        print(f"\nSaved {len(self.books)} unique books to {filename}")
        
    def run(self):
        """Main execution method"""
        print("Starting BookTrust dynamic scraper...")
        print("This will dynamically scrape all books from each URL without any hardcoded data.\n")
        
        self.scrape_all_lists()
        self.save_to_csv()
        
        print("\nScraping complete!")
        
        # Print summary
        print(f"\nSummary:")
        print(f"Total unique books: {len(self.books)}")
        print(f"Books with ISBNs: {sum(1 for b in self.books if b['isbn'])}")
        print(f"Books with authors: {sum(1 for b in self.books if b['author'])}")
        print(f"Books with publishers: {sum(1 for b in self.books if b['publisher'])}")
        print(f"Books with descriptions: {sum(1 for b in self.books if b['description'])}")

if __name__ == "__main__":
    scraper = BookTrustDynamicScraper()
    scraper.run()