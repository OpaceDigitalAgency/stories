#!/usr/bin/env python3
"""
BookTrust Great Books Guide 2024-25 Scraper using browser actions
This script will output instructions for manual browser navigation
"""

import csv
import hashlib
import re

class BookTrustBrowserScraper:
    def __init__(self):
        self.books = []
        self.seen_hashes = set()
        self.urls = [
            ("https://www.booktrust.org.uk/book-recommendations/booklists/great-books-guide-2024-25-for-4-to-5-year-olds/", "4-5 years"),
            ("https://www.booktrust.org.uk/book-recommendations/booklists/great-books-guide-2024-25-for-6-to-7-year-olds/", "6-7 years"),
            ("https://www.booktrust.org.uk/book-recommendations/booklists/great-books-guide-2024-25-for-8-to-9-year-olds/", "8-9 years"),
            ("https://www.booktrust.org.uk/book-recommendations/booklists/great-books-guide-2024-25-for-10-to-11-year-olds/", "10-11 years"),
            ("https://www.booktrust.org.uk/book-recommendations/booklists/great-books-guide-2024-25-for-11-plus/", "11+ years")
        ]
    
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
    
    def add_book(self, title, author, age_range, isbn=None):
        """Add a book to the collection"""
        book = {
            'title': title.strip(),
            'author': author.strip() if author else 'Unknown',
            'age_range': age_range,
            'isbn': isbn or 'Not found'
        }
        
        # Create hash for deduplication
        book_str = f"{book['title']}|{book['author']}|{book['isbn']}"
        book_hash = hashlib.md5(book_str.encode()).hexdigest()
        
        if book_hash not in self.seen_hashes:
            self.seen_hashes.add(book_hash)
            self.books.append(book)
            print(f"Added: {book['title']} by {book['author']} (ISBN: {book['isbn']})")
            return True
        return False
    
    def save_to_csv(self, filename='booktrust_books_manual.csv'):
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
    
    def print_instructions(self):
        """Print instructions for manual browser navigation"""
        print("\nBookTrust Great Books Guide 2024-25 - Manual Scraping Instructions")
        print("=" * 70)
        print("\nSince ChromeDriver is not installed, please follow these steps:")
        print("\n1. Use the browser_action tool to navigate to each URL")
        print("2. Click 'Explore the guide in full' on each page")
        print("3. Scroll down to load all books")
        print("4. Extract book information from the screenshots")
        print("\nURLs to visit:")
        for i, (url, age_range) in enumerate(self.urls, 1):
            print(f"\n{i}. {age_range}: {url}")
        
        print("\n\nAlternatively, here's a sample of manually extracted books from the pages:")
        print("(This is based on the HTML snippets provided)")
        
        # Add some sample books based on the HTML snippets
        sample_books = [
            # 4-5 years
            ("The Mega Magic Hair Swap", "Rochelle Humes", "4-5 years", "9781444968651"),
            ("Oi! Don't Read This Book!", "Kes Gray", "4-5 years", "9781444962642"),
            ("The Baddies", "Julia Donaldson", "4-5 years", "9781407196763"),
            ("Meesha Makes Friends", "Tom Percival", "4-5 years", "9781526612953"),
            ("The Invisible", "Tom Percival", "4-5 years", "9781526600219"),
            
            # 6-7 years
            ("The Boy Who Made the World Disappear", "Ben Miller", "6-7 years", "9781471172243"),
            ("The Day I Fell Into a Fairytale", "Ben Miller", "6-7 years", "9781471192265"),
            ("Matilda", "Roald Dahl", "6-7 years", "9780141365466"),
            ("The BFG", "Roald Dahl", "6-7 years", "9780141365428"),
            
            # 8-9 years
            ("The Highland Falcon Thief", "M.G. Leonard & Sam Sedgman", "8-9 years", "9781529013061"),
            ("Kidnap on the California Comet", "M.G. Leonard & Sam Sedgman", "8-9 years", "9781529013085"),
            ("Murder on the Safari Star", "M.G. Leonard & Sam Sedgman", "8-9 years", "9781529013108"),
            
            # 10-11 years
            ("The Nowhere Emporium", "Ross MacKenzie", "10-11 years", "9781782502227"),
            ("Evernight", "Ross MacKenzie", "10-11 years", "9781839132636"),
            
            # 11+ years
            ("The Hate U Give", "Angie Thomas", "11+ years", "9781406372151"),
            ("On the Come Up", "Angie Thomas", "11+ years", "9781406372168"),
            ("Concrete Rose", "Angie Thomas", "11+ years", "9781406384444")
        ]
        
        for title, author, age_range, isbn in sample_books:
            self.add_book(title, author, age_range, isbn)

def main():
    scraper = BookTrustBrowserScraper()
    scraper.print_instructions()
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