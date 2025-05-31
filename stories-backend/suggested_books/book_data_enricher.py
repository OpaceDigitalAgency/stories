import csv
import json
import time
import requests
from typing import Dict, List, Optional
import re
from urllib.parse import quote

class BookDataEnricher:
    """Enrich book data with ISBNs and metadata from various APIs"""
    
    def __init__(self):
        self.session = requests.Session()
        self.session.headers.update({
            'User-Agent': 'BookDataEnricher/1.0'
        })
        
    def clean_isbn(self, isbn: str) -> str:
        """Clean and validate ISBN"""
        if not isbn:
            return ''
        # Remove all non-digits
        isbn = re.sub(r'[^\d]', '', isbn)
        # Check if valid ISBN-10 or ISBN-13
        if len(isbn) == 10 or len(isbn) == 13:
            return isbn
        return ''
    
    def search_google_books(self, title: str, author: str = '') -> Dict:
        """Search Google Books API for book information"""
        try:
            # Build search query
            query_parts = []
            if title:
                query_parts.append(f'intitle:{title}')
            if author:
                query_parts.append(f'inauthor:{author}')
            
            if not query_parts:
                return {}
            
            query = '+'.join(query_parts)
            url = f'https://www.googleapis.com/books/v1/volumes?q={quote(query)}&maxResults=5'
            
            response = self.session.get(url, timeout=10)
            response.raise_for_status()
            data = response.json()
            
            if 'items' not in data or not data['items']:
                return {}
            
            # Find best match
            for item in data['items']:
                volume_info = item.get('volumeInfo', {})
                
                # Check if title matches reasonably well
                api_title = volume_info.get('title', '').lower()
                search_title = title.lower()
                
                if search_title in api_title or api_title in search_title:
                    result = {
                        'google_books_id': item.get('id'),
                        'title': volume_info.get('title'),
                        'subtitle': volume_info.get('subtitle'),
                        'authors': volume_info.get('authors', []),
                        'publisher': volume_info.get('publisher'),
                        'published_date': volume_info.get('publishedDate'),
                        'description': volume_info.get('description'),
                        'page_count': volume_info.get('pageCount'),
                        'categories': volume_info.get('categories', []),
                        'language': volume_info.get('language'),
                        'preview_link': volume_info.get('previewLink'),
                        'info_link': volume_info.get('infoLink')
                    }
                    
                    # Extract ISBNs
                    identifiers = volume_info.get('industryIdentifiers', [])
                    for identifier in identifiers:
                        if identifier['type'] == 'ISBN_10':
                            result['isbn10'] = identifier['identifier']
                        elif identifier['type'] == 'ISBN_13':
                            result['isbn13'] = identifier['identifier']
                    
                    # Extract image links
                    image_links = volume_info.get('imageLinks', {})
                    if image_links:
                        result['thumbnail'] = image_links.get('thumbnail', '').replace('http:', 'https:')
                        result['small_thumbnail'] = image_links.get('smallThumbnail', '').replace('http:', 'https:')
                    
                    return result
            
            return {}
            
        except Exception as e:
            print(f"Error searching Google Books for '{title}': {e}")
            return {}
    
    def search_open_library(self, title: str, author: str = '') -> Dict:
        """Search Open Library API for book information"""
        try:
            # Build search query
            query_parts = []
            if title:
                query_parts.append(f'title:{title}')
            if author:
                query_parts.append(f'author:{author}')
            
            if not query_parts:
                return {}
            
            query = ' '.join(query_parts)
            url = f'https://openlibrary.org/search.json?q={quote(query)}&limit=5'
            
            response = self.session.get(url, timeout=10)
            response.raise_for_status()
            data = response.json()
            
            if 'docs' not in data or not data['docs']:
                return {}
            
            # Find best match
            for doc in data['docs']:
                # Check if title matches reasonably well
                api_title = doc.get('title', '').lower()
                search_title = title.lower()
                
                if search_title in api_title or api_title in search_title:
                    result = {
                        'open_library_key': doc.get('key'),
                        'title': doc.get('title'),
                        'authors': doc.get('author_name', []),
                        'first_publish_year': doc.get('first_publish_year'),
                        'publisher': doc.get('publisher', []),
                        'language': doc.get('language', []),
                        'subjects': doc.get('subject', []),
                        'number_of_pages': doc.get('number_of_pages_median')
                    }
                    
                    # Extract ISBNs
                    isbns = doc.get('isbn', [])
                    if isbns:
                        for isbn in isbns:
                            clean = self.clean_isbn(isbn)
                            if len(clean) == 10 and 'isbn10' not in result:
                                result['isbn10'] = clean
                            elif len(clean) == 13 and 'isbn13' not in result:
                                result['isbn13'] = clean
                    
                    # Get cover image
                    cover_i = doc.get('cover_i')
                    if cover_i:
                        result['cover_url'] = f'https://covers.openlibrary.org/b/id/{cover_i}-L.jpg'
                        result['cover_url_medium'] = f'https://covers.openlibrary.org/b/id/{cover_i}-M.jpg'
                        result['cover_url_small'] = f'https://covers.openlibrary.org/b/id/{cover_i}-S.jpg'
                    
                    return result
            
            return {}
            
        except Exception as e:
            print(f"Error searching Open Library for '{title}': {e}")
            return {}
    
    def enrich_book(self, book: Dict) -> Dict:
        """Enrich a single book with data from multiple sources"""
        enriched = book.copy()
        
        title = book.get('title', '')
        author = book.get('author', '')
        
        if not title:
            return enriched
        
        print(f"Enriching: {title} by {author}")
        
        # Try Google Books first
        google_data = self.search_google_books(title, author)
        if google_data:
            print(f"  Found on Google Books")
            # Add Google Books data
            enriched['google_books_id'] = google_data.get('google_books_id')
            enriched['isbn10'] = enriched.get('isbn10') or google_data.get('isbn10', '')
            enriched['isbn13'] = enriched.get('isbn13') or google_data.get('isbn13', '')
            enriched['publisher'] = enriched.get('publisher') or google_data.get('publisher', '')
            enriched['published_date'] = enriched.get('published_date') or google_data.get('published_date', '')
            enriched['page_count'] = enriched.get('page_count') or google_data.get('page_count', '')
            enriched['language'] = enriched.get('language') or google_data.get('language', '')
            enriched['thumbnail_url'] = google_data.get('thumbnail', '')
            enriched['categories'] = google_data.get('categories', [])
            
            # Use Google description if we don't have one
            if not enriched.get('description') and google_data.get('description'):
                enriched['description'] = google_data['description']
        
        # Try Open Library if we still need ISBN or other data
        if not enriched.get('isbn10') and not enriched.get('isbn13'):
            time.sleep(0.5)  # Be polite to APIs
            open_lib_data = self.search_open_library(title, author)
            if open_lib_data:
                print(f"  Found on Open Library")
                enriched['open_library_key'] = open_lib_data.get('open_library_key')
                enriched['isbn10'] = enriched.get('isbn10') or open_lib_data.get('isbn10', '')
                enriched['isbn13'] = enriched.get('isbn13') or open_lib_data.get('isbn13', '')
                enriched['subjects'] = open_lib_data.get('subjects', [])
                
                # Use Open Library cover if we don't have one
                if not enriched.get('thumbnail_url') and open_lib_data.get('cover_url'):
                    enriched['thumbnail_url'] = open_lib_data['cover_url']
        
        # Create a combined ISBN field
        if enriched.get('isbn13'):
            enriched['isbn'] = enriched['isbn13']
        elif enriched.get('isbn10'):
            enriched['isbn'] = enriched['isbn10']
        
        return enriched
    
    def enrich_csv(self, input_file: str, output_file: str):
        """Enrich all books in a CSV file"""
        books = []
        
        # Read input CSV
        with open(input_file, 'r', encoding='utf-8') as f:
            reader = csv.DictReader(f)
            books = list(reader)
        
        print(f"Loaded {len(books)} books from {input_file}")
        
        # Enrich each book
        enriched_books = []
        for i, book in enumerate(books):
            print(f"\nProcessing book {i+1}/{len(books)}")
            enriched = self.enrich_book(book)
            enriched_books.append(enriched)
            
            # Rate limiting
            if i < len(books) - 1:
                time.sleep(1)  # Be polite to APIs
        
        # Get all unique keys
        all_keys = set()
        for book in enriched_books:
            all_keys.update(book.keys())
        
        # Sort keys for consistent output
        fieldnames = sorted(list(all_keys))
        
        # Write enriched CSV
        with open(output_file, 'w', newline='', encoding='utf-8') as f:
            writer = csv.DictWriter(f, fieldnames=fieldnames)
            writer.writeheader()
            
            for book in enriched_books:
                # Convert lists to strings for CSV
                book_copy = book.copy()
                for key, value in book_copy.items():
                    if isinstance(value, list):
                        book_copy[key] = ', '.join(str(v) for v in value)
                writer.writerow(book_copy)
        
        print(f"\nEnriched data saved to {output_file}")
        
        # Also save as JSON for easier processing
        json_file = output_file.replace('.csv', '.json')
        with open(json_file, 'w', encoding='utf-8') as f:
            json.dump(enriched_books, f, indent=2, ensure_ascii=False)
        print(f"JSON version saved to {json_file}")
        
        # Print summary
        isbn_count = sum(1 for book in enriched_books if book.get('isbn'))
        print(f"\nSummary:")
        print(f"  Total books: {len(enriched_books)}")
        print(f"  Books with ISBN: {isbn_count}")
        print(f"  Success rate: {isbn_count/len(enriched_books)*100:.1f}%")


# Example usage
if __name__ == "__main__":
    enricher = BookDataEnricher()
    
    # Enrich the BookTrust CSV file
    enricher.enrich_csv('booktrust_books_comprehensive.csv', 'booktrust_books_enriched.csv')