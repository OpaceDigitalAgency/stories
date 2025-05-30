<?php
/**
 * Generic Book Scraper
 * 
 * Attempts to scrape book data from unknown websites using common patterns
 */

class GenericBookScraper {
    
    /**
     * This scraper can handle any URL as a fallback
     */
    public function canHandle($url) {
        return true;
    }
    
    /**
     * Scrape books from an unknown website
     */
    public function scrape($url) {
        $books = [];
        
        // Fetch the page
        $html = $this->fetchPage($url);
        if (!$html) {
            error_log("Failed to fetch page: {$url}");
            return $books;
        }
        
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        @$dom->loadHTML($html);
        libxml_clear_errors();
        
        $xpath = new DOMXPath($dom);
        
        // Strategy 1: Look for structured data (JSON-LD)
        $jsonLdBooks = $this->extractFromJsonLd($xpath);
        if (!empty($jsonLdBooks)) {
            return $jsonLdBooks;
        }
        
        // Strategy 2: Look for common book list patterns
        $books = $this->extractFromCommonPatterns($xpath, $url);
        if (!empty($books)) {
            return $books;
        }
        
        // Strategy 3: Look for ISBN patterns and build books around them
        $isbnBooks = $this->extractFromISBNs($html, $xpath);
        if (!empty($isbnBooks)) {
            return $isbnBooks;
        }
        
        // Strategy 4: Look for microdata
        $microdataBooks = $this->extractFromMicrodata($xpath);
        if (!empty($microdataBooks)) {
            return $microdataBooks;
        }
        
        return $books;
    }
    
    /**
     * Extract books from JSON-LD structured data
     */
    private function extractFromJsonLd($xpath) {
        $books = [];
        
        $scripts = $xpath->query('//script[@type="application/ld+json"]');
        foreach ($scripts as $script) {
            $json = $script->textContent;
            $data = json_decode($json, true);
            
            if (!$data) continue;
            
            // Handle single book
            if (isset($data['@type']) && $data['@type'] === 'Book') {
                $books[] = $this->parseJsonLdBook($data);
            }
            
            // Handle list of books
            if (isset($data['@graph'])) {
                foreach ($data['@graph'] as $item) {
                    if (isset($item['@type']) && $item['@type'] === 'Book') {
                        $books[] = $this->parseJsonLdBook($item);
                    }
                }
            }
            
            // Handle ItemList of books
            if (isset($data['@type']) && $data['@type'] === 'ItemList' && isset($data['itemListElement'])) {
                foreach ($data['itemListElement'] as $element) {
                    if (isset($element['item']['@type']) && $element['item']['@type'] === 'Book') {
                        $books[] = $this->parseJsonLdBook($element['item']);
                    }
                }
            }
        }
        
        return $books;
    }
    
    /**
     * Parse a JSON-LD book object
     */
    private function parseJsonLdBook($data) {
        $book = [
            'title' => $data['name'] ?? '',
            'author' => '',
            'isbn' => $data['isbn'] ?? '',
            'description' => $data['description'] ?? '',
            'publisher' => $data['publisher']['name'] ?? $data['publisher'] ?? '',
            'publication_date' => $data['datePublished'] ?? '',
            'page_count' => $data['numberOfPages'] ?? '',
            'language' => $data['inLanguage'] ?? '',
            'tags' => $data['genre'] ?? [],
            'source' => 'generic'
        ];
        
        // Handle author
        if (isset($data['author'])) {
            if (is_string($data['author'])) {
                $book['author'] = $data['author'];
            } elseif (isset($data['author']['name'])) {
                $book['author'] = $data['author']['name'];
            } elseif (is_array($data['author']) && isset($data['author'][0]['name'])) {
                $book['author'] = $data['author'][0]['name'];
            }
        }
        
        return $book;
    }
    
    /**
     * Extract books using common HTML patterns
     */
    private function extractFromCommonPatterns($xpath, $url) {
        $books = [];
        
        // Common patterns for book containers
        $patterns = [
            '//article[contains(@class, "book")]',
            '//div[contains(@class, "book-item")]',
            '//div[contains(@class, "book-card")]',
            '//li[contains(@class, "book")]',
            '//div[@itemtype="http://schema.org/Book"]',
            '//div[@itemtype="https://schema.org/Book"]',
            '//div[contains(@class, "product") and contains(@class, "book")]',
            '//div[contains(@class, "item") and .//a[contains(@href, "isbn")]]'
        ];
        
        foreach ($patterns as $pattern) {
            $nodes = $xpath->query($pattern);
            if ($nodes->length > 0) {
                error_log("Found {$nodes->length} books using pattern: {$pattern}");
                
                foreach ($nodes as $node) {
                    $book = $this->extractBookFromNode($node, $xpath);
                    if (!empty($book['title'])) {
                        $book['source_url'] = $url;
                        $books[] = $book;
                    }
                }
                
                if (!empty($books)) {
                    break; // Use first successful pattern
                }
            }
        }
        
        return $books;
    }
    
    /**
     * Extract book data from a DOM node
     */
    private function extractBookFromNode($node, $xpath) {
        $book = [
            'title' => '',
            'author' => '',
            'isbn' => '',
            'description' => '',
            'tags' => [],
            'source' => 'generic'
        ];
        
        // Title selectors
        $titleSelectors = [
            './/*[@itemprop="name"]',
            './/h1', './/h2', './/h3', './/h4',
            './/*[contains(@class, "title") and not(contains(@class, "subtitle"))]',
            './/*[contains(@class, "book-title")]',
            './/*[contains(@class, "product-title")]',
            './/a[contains(@class, "title")]'
        ];
        
        foreach ($titleSelectors as $selector) {
            $titleNode = $xpath->query($selector, $node)->item(0);
            if ($titleNode && !empty(trim($titleNode->textContent))) {
                $book['title'] = $this->cleanText($titleNode->textContent);
                break;
            }
        }
        
        // Author selectors
        $authorSelectors = [
            './/*[@itemprop="author"]',
            './/*[contains(@class, "author")]',
            './/*[contains(@class, "by-line")]',
            './/*[contains(@class, "contributor")]',
            './/span[contains(text(), "by ")]/following-sibling::*',
            './/a[contains(@href, "/author/")]'
        ];
        
        foreach ($authorSelectors as $selector) {
            $authorNode = $xpath->query($selector, $node)->item(0);
            if ($authorNode && !empty(trim($authorNode->textContent))) {
                $author = $this->cleanText($authorNode->textContent);
                $author = preg_replace('/^by\s+/i', '', $author);
                if (!empty($author)) {
                    $book['author'] = $author;
                    break;
                }
            }
        }
        
        // ISBN extraction
        $nodeText = $node->textContent;
        if (preg_match('/(?:ISBN[:\s-]*)?(\d{10}|\d{13})/i', $nodeText, $matches)) {
            $book['isbn'] = $matches[1];
        }
        
        // Description selectors
        $descSelectors = [
            './/*[@itemprop="description"]',
            './/*[contains(@class, "description")]',
            './/*[contains(@class, "synopsis")]',
            './/*[contains(@class, "summary")]',
            './/p[position()=1 and string-length(.) > 50]'
        ];
        
        foreach ($descSelectors as $selector) {
            $descNode = $xpath->query($selector, $node)->item(0);
            if ($descNode && !empty(trim($descNode->textContent))) {
                $book['description'] = $this->cleanText($descNode->textContent);
                break;
            }
        }
        
        // Extract tags/genres
        $tagSelectors = [
            './/*[@itemprop="genre"]',
            './/*[contains(@class, "tag")]',
            './/*[contains(@class, "category")]',
            './/*[contains(@class, "genre")]'
        ];
        
        foreach ($tagSelectors as $selector) {
            $tagNodes = $xpath->query($selector, $node);
            foreach ($tagNodes as $tagNode) {
                $tag = $this->cleanText($tagNode->textContent);
                if (!empty($tag) && strlen($tag) < 50) {
                    $book['tags'][] = $tag;
                }
            }
        }
        
        return $book;
    }
    
    /**
     * Extract books by finding ISBNs first
     */
    private function extractFromISBNs($html, $xpath) {
        $books = [];
        $isbnMatches = [];
        
        // Find all ISBNs in the page
        preg_match_all('/(?:ISBN[:\s-]*)?(\d{10}|\d{13})(?![0-9])/i', $html, $isbnMatches);
        
        if (empty($isbnMatches[1])) {
            return $books;
        }
        
        $uniqueISBNs = array_unique($isbnMatches[1]);
        
        foreach ($uniqueISBNs as $isbn) {
            // Try to find context around each ISBN
            $book = [
                'isbn' => $isbn,
                'title' => '',
                'author' => '',
                'source' => 'generic'
            ];
            
            // Search for the ISBN in text nodes
            $textNodes = $xpath->query("//text()[contains(., '{$isbn}')]");
            
            foreach ($textNodes as $textNode) {
                $parent = $textNode->parentNode;
                $grandParent = $parent->parentNode;
                
                // Look for title and author in surrounding elements
                if ($grandParent) {
                    $context = $grandParent;
                    
                    // Look for title
                    $possibleTitles = $xpath->query('.//h1 | .//h2 | .//h3 | .//h4 | .//a[string-length(.) > 10]', $context);
                    foreach ($possibleTitles as $titleNode) {
                        $title = $this->cleanText($titleNode->textContent);
                        if (!empty($title) && !preg_match('/\d{10,13}/', $title)) {
                            $book['title'] = $title;
                            break;
                        }
                    }
                    
                    // Look for author
                    if (preg_match('/by\s+([^,\n]+)/i', $context->textContent, $authorMatch)) {
                        $book['author'] = $this->cleanText($authorMatch[1]);
                    }
                }
                
                if (!empty($book['title'])) {
                    break;
                }
            }
            
            if (!empty($book['title'])) {
                $books[] = $book;
            }
        }
        
        return $books;
    }
    
    /**
     * Extract books from microdata
     */
    private function extractFromMicrodata($xpath) {
        $books = [];
        
        $bookNodes = $xpath->query('//*[@itemtype="http://schema.org/Book" or @itemtype="https://schema.org/Book"]');
        
        foreach ($bookNodes as $node) {
            $book = [
                'title' => '',
                'author' => '',
                'isbn' => '',
                'description' => '',
                'source' => 'generic'
            ];
            
            // Extract microdata properties
            $props = [
                'name' => 'title',
                'author' => 'author',
                'isbn' => 'isbn',
                'description' => 'description',
                'publisher' => 'publisher',
                'datePublished' => 'publication_date'
            ];
            
            foreach ($props as $itemprop => $bookField) {
                $propNode = $xpath->query('.//*[@itemprop="' . $itemprop . '"]', $node)->item(0);
                if ($propNode) {
                    $value = $this->cleanText($propNode->textContent);
                    if ($bookField === 'author' && strpos($value, 'by ') === 0) {
                        $value = substr($value, 3);
                    }
                    $book[$bookField] = $value;
                }
            }
            
            if (!empty($book['title'])) {
                $books[] = $book;
            }
        }
        
        return $books;
    }
    
    /**
     * Fetch a page using cURL
     */
    private function fetchPage($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        $html = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            error_log("HTTP {$httpCode} fetching {$url}");
            return false;
        }
        
        return $html;
    }
    
    /**
     * Clean text
     */
    private function cleanText($text) {
        if (empty($text)) {
            return '';
        }
        
        // Decode HTML entities
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // Remove extra whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        
        return trim($text);
    }
}