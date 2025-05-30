<?php
/**
 * BookTrust Scraper
 * 
 * Scrapes book data from BookTrust website using PHP
 */

class BookTrustScraper {
    private $base_url = 'https://www.booktrust.org.uk';
    
    /**
     * Check if this scraper can handle the given URL
     */
    public function canHandle($url) {
        $canHandle = strpos($url, 'booktrust.org.uk') !== false;
        error_log("BookTrustScraper: canHandle('{$url}') = " . ($canHandle ? 'true' : 'false'));
        return $canHandle;
    }
    
    /**
     * Scrape books from the given URL
     */
    public function scrape($url) {
        $books = [];
        
        error_log("BookTrustScraper: Starting scrape of {$url}");
        
        // Get HTML content
        $html = $this->fetchPage($url);
        if (!$html) {
            error_log("BookTrustScraper: Failed to fetch page: {$url}");
            return $books;
        }
        
        error_log("BookTrustScraper: Successfully fetched HTML, length: " . strlen($html));
        
        // Parse with DOMDocument
        $dom = new DOMDocument();
        libxml_use_internal_errors(true); // Suppress HTML parsing warnings
        $loadResult = @$dom->loadHTML($html);
        libxml_clear_errors();
        
        if (!$loadResult) {
            error_log("BookTrustScraper: Failed to parse HTML with DOMDocument");
            return $books;
        }
        
        error_log("BookTrustScraper: Successfully parsed HTML with DOMDocument");
        
        $xpath = new DOMXPath($dom);
        
        // Extract age range from URL or page title
        $defaultAgeRange = $this->extractAgeRange($url, $xpath);
        error_log("BookTrustScraper: Default age range: " . $defaultAgeRange);
        
        // Find all book items (using contains() to match partial class)
        $bookItems = $xpath->query('//li[contains(@class, "reading-width")]');
        error_log("BookTrustScraper: Found " . $bookItems->length . " book items with selector //li[contains(@class, \"reading-width\")]");
        
        // If no items found, try alternative selectors for debugging
        if ($bookItems->length === 0) {
            error_log("BookTrustScraper: No items found with reading-width class, trying alternatives...");
            
            // Try finding any li elements
            $allLi = $xpath->query('//li');
            error_log("BookTrustScraper: Found " . $allLi->length . " total li elements");
            
            // Try finding elements with book-related classes using contains()
            $bookClasses = ['book-item', 'book', 'item', 'card', 'reading-width'];
            foreach ($bookClasses as $class) {
                $items = $xpath->query("//li[contains(@class, '{$class}')]");
                if ($items->length > 0) {
                    error_log("BookTrustScraper: Found " . $items->length . " items containing class '{$class}'");
                }
            }
            
            // Try finding the book list container
            $bookList = $xpath->query('//ul[@class="grid grid-cols-books gap-x-lg"]');
            error_log("BookTrustScraper: Found " . $bookList->length . " book list containers");
            
            if ($bookList->length > 0) {
                $listItems = $xpath->query('.//li', $bookList->item(0));
                error_log("BookTrustScraper: Found " . $listItems->length . " li elements inside book list container");
                
                // Check classes of first few items
                for ($i = 0; $i < min(3, $listItems->length); $i++) {
                    $item = $listItems->item($i);
                    $class = $item->getAttribute('class');
                    error_log("BookTrustScraper: Li item {$i} has class: '{$class}'");
                }
            }
        }
        
        foreach ($bookItems as $index => $item) {
            error_log("BookTrustScraper: Processing book item " . ($index + 1));
            $book = $this->parseBookItem($item, $xpath);
            
            error_log("BookTrustScraper: Parsed book - Title: '{$book['title']}', Author: '{$book['author']}'");
            
            // Add default age range if not found
            if (empty($book['age_range']) && !empty($defaultAgeRange)) {
                $book['age_range'] = $defaultAgeRange;
            }
            
            if (!empty($book['title'])) {
                // Get additional details from book page if available
                if (!empty($book['detail_url'])) {
                    $details = $this->scrapeBookDetails($book['detail_url']);
                    $book = array_merge($book, $details);
                }
                
                $books[] = $book;
                error_log("BookTrustScraper: Successfully added book: " . $book['title']);
            } else {
                error_log("BookTrustScraper: Skipping book with empty title");
            }
        }
        
        error_log("BookTrustScraper: Completed scraping, found " . count($books) . " books");
        return $books;
    }
    
    /**
     * Parse a single book item
     */
    private function parseBookItem($item, $xpath) {
        $book = [
            'title' => '',
            'author' => '',
            'age_range' => '',
            'year' => '',
            'tags' => [],
            'description' => '',
            'detail_url' => '',
            'source' => 'booktrust',
            'isbn' => '',
            'isbn13' => ''
        ];
        
        // Title and URL (updated to use contains() for class matching)
        $titleNode = $xpath->query('.//h3[contains(@class, "heading-s")]//a', $item)->item(0);
        error_log("BookTrustScraper: parseBookItem - Looking for h3[contains(@class, \"heading-s\")]//a, found: " . ($titleNode ? 'YES' : 'NO'));
        
        if ($titleNode) {
            $book['title'] = $this->cleanText($titleNode->textContent);
            $href = $titleNode->getAttribute('href');
            error_log("BookTrustScraper: parseBookItem - Title: '{$book['title']}', href: '{$href}'");
            if ($href) {
                // Check if href is already a full URL
                if (strpos($href, 'http') === 0) {
                    $book['detail_url'] = $href;
                } else {
                    $book['detail_url'] = $this->base_url . $href;
                }
            }
        } else {
            // Try alternative selectors for debugging
            $allH3 = $xpath->query('.//h3', $item);
            error_log("BookTrustScraper: parseBookItem - No h3.heading-s//a found, trying any h3: found " . $allH3->length);
            
            if ($allH3->length > 0) {
                for ($i = 0; $i < min(3, $allH3->length); $i++) {
                    $h3 = $allH3->item($i);
                    $class = $h3->getAttribute('class');
                    $text = trim($h3->textContent);
                    error_log("BookTrustScraper: parseBookItem - h3 #{$i} class: '{$class}', text: '" . substr($text, 0, 50) . "'");
                }
            }
            
            // Try finding any links
            $allLinks = $xpath->query('.//a', $item);
            error_log("BookTrustScraper: parseBookItem - Found " . $allLinks->length . " total links in item");
        }
        
        // Author - remove "by " prefix (updated to use contains())
        $authorNode = $xpath->query('.//p[contains(@class, "body-xs")]', $item)->item(0);
        if ($authorNode) {
            $authorText = $this->cleanText($authorNode->textContent);
            $book['author'] = preg_replace('/^by\s+/i', '', $authorText);
        }
        
        // Metadata (year and age range) (updated to use contains())
        $metaNode = $xpath->query('.//p[contains(@class, "body-xxs")]', $item)->item(0);
        if ($metaNode) {
            $metaText = $this->cleanText($metaNode->textContent);
            
            // Extract year (4 digits starting with 20)
            if (preg_match('/^(20\d{2})/', $metaText, $matches)) {
                $book['year'] = $matches[1];
                // Get age range by removing the year from the start
                $ageRange = trim(substr($metaText, 4));
                if ($ageRange) {
                    $book['age_range'] = $ageRange;
                }
            }
        }
        
        // Tags (updated to use contains())
        $tagNodes = $xpath->query('.//ul[contains(@class, "bt-tags")]//li[contains(@class, "tag")]', $item);
        foreach ($tagNodes as $tag) {
            $tagText = $this->cleanText($tag->textContent);
            if ($tagText) {
                $book['tags'][] = $tagText;
            }
        }
        
        // Description (updated to use contains())
        $descNode = $xpath->query('.//div[contains(@class, "short-synopsis")]//p', $item)->item(0);
        if ($descNode) {
            $book['description'] = $this->cleanText($descNode->textContent);
        }
        
        return $book;
    }
    
    /**
     * Scrape additional details from book detail page
     */
    private function scrapeBookDetails($url) {
        $details = [];
        
        // Fetch the detail page
        $html = $this->fetchPage($url);
        if (!$html) {
            return $details;
        }
        
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        @$dom->loadHTML($html);
        libxml_clear_errors();
        
        $xpath = new DOMXPath($dom);
        
        // Look for ISBN in various places
        $pageText = $dom->textContent;
        
        // ISBN patterns
        $patterns = [
            '/ISBN[:\s-]*(\d{10}|\d{13})/i',
            '/ISBN-10[:\s-]*(\d{10})/i',
            '/ISBN-13[:\s-]*(\d{13})/i'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $pageText, $matches)) {
                $isbn = $matches[1];
                if (strlen($isbn) == 10) {
                    $details['isbn'] = $isbn;
                } elseif (strlen($isbn) == 13) {
                    $details['isbn13'] = $isbn;
                }
            }
        }
        
        // Look for additional metadata
        $metaNodes = $xpath->query('//meta[@property or @name]');
        foreach ($metaNodes as $meta) {
            $property = $meta->getAttribute('property') ?: $meta->getAttribute('name');
            $content = $meta->getAttribute('content');
            
            if ($property == 'og:description' && empty($details['description'])) {
                $details['description'] = $content;
            }
        }
        
        return $details;
    }
    
    /**
     * Extract age range from URL or page
     */
    private function extractAgeRange($url, $xpath) {
        // Try to extract from URL
        if (preg_match('/(\d+)-to-(\d+)-year/', $url, $matches)) {
            return $matches[1] . ' to ' . $matches[2] . ' years';
        }
        
        // Try to extract from page title
        $titleNode = $xpath->query('//h1')->item(0);
        if ($titleNode) {
            $title = $titleNode->textContent;
            if (preg_match('/(\d+)\s*[-to]+\s*(\d+)\s*year/i', $title, $matches)) {
                return $matches[1] . ' to ' . $matches[2] . ' years';
            }
        }
        
        return '';
    }
    
    /**
     * Fetch a page using cURL
     */
    private function fetchPage($url) {
        error_log("BookTrustScraper: fetchPage() called with URL: {$url}");
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        error_log("BookTrustScraper: Executing cURL request...");
        $html = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $effectiveUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        curl_close($ch);
        
        error_log("BookTrustScraper: cURL completed - HTTP Code: {$httpCode}, Effective URL: {$effectiveUrl}");
        
        if ($error) {
            error_log("BookTrustScraper: cURL error fetching {$url}: {$error}");
            return false;
        }
        
        if ($httpCode !== 200) {
            error_log("BookTrustScraper: HTTP {$httpCode} fetching {$url}");
            return false;
        }
        
        if ($html === false) {
            error_log("BookTrustScraper: cURL returned false for {$url}");
            return false;
        }
        
        error_log("BookTrustScraper: Successfully fetched HTML, length: " . strlen($html));
        return $html;
    }
    
    /**
     * Clean text by removing special characters and fixing encoding
     */
    private function cleanText($text) {
        if (empty($text)) {
            return '';
        }
        
        // Decode HTML entities
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // Fix common encoding issues
        $replacements = [
            "\u{2018}" => "'", // Left single quote
            "\u{2019}" => "'", // Right single quote
            "\u{201C}" => '"', // Left double quote
            "\u{201D}" => '"', // Right double quote
            "\u{2013}" => '-', // En dash
            "\u{2014}" => '-', // Em dash
            "\u{2026}" => '...', // Ellipsis
            "\u{200B}" => '', // Zero-width space
            "\u{00A0}" => ' ', // Non-breaking space
        ];
        
        $text = str_replace(array_keys($replacements), array_values($replacements), $text);
        
        // Trim and normalize whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text);
    }
}