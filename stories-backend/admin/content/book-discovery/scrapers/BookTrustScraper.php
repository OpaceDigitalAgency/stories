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
        return strpos($url, 'booktrust.org.uk') !== false;
    }
    
    /**
     * Scrape books from the given URL
     */
    public function scrape($url) {
        $books = [];
        
        // Get HTML content
        $html = $this->fetchPage($url);
        if (!$html) {
            error_log("Failed to fetch page: {$url}");
            return $books;
        }
        
        // Parse with DOMDocument
        $dom = new DOMDocument();
        libxml_use_internal_errors(true); // Suppress HTML parsing warnings
        @$dom->loadHTML($html);
        libxml_clear_errors();
        
        $xpath = new DOMXPath($dom);
        
        // Extract age range from URL or page title
        $defaultAgeRange = $this->extractAgeRange($url, $xpath);
        
        // Find all book items (using the correct selector from Python script)
        $bookItems = $xpath->query('//li[@class="reading-width"]');
        error_log("Found " . $bookItems->length . " book items on {$url}");
        
        foreach ($bookItems as $item) {
            $book = $this->parseBookItem($item, $xpath);
            
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
                error_log("Scraped book: " . $book['title']);
            }
        }
        
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
        
        // Title and URL
        $titleNode = $xpath->query('.//h3[@class="heading-s"]//a', $item)->item(0);
        if ($titleNode) {
            $book['title'] = $this->cleanText($titleNode->textContent);
            $href = $titleNode->getAttribute('href');
            if ($href) {
                $book['detail_url'] = $this->base_url . $href;
            }
        }
        
        // Author - remove "by " prefix
        $authorNode = $xpath->query('.//p[@class="body-xs"]', $item)->item(0);
        if ($authorNode) {
            $authorText = $this->cleanText($authorNode->textContent);
            $book['author'] = preg_replace('/^by\s+/i', '', $authorText);
        }
        
        // Metadata (year and age range)
        $metaNode = $xpath->query('.//p[@class="body-xxs"]', $item)->item(0);
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
        
        // Tags
        $tagNodes = $xpath->query('.//ul[@class="bt-tags"]//li[@class="tag"]', $item);
        foreach ($tagNodes as $tag) {
            $tagText = $this->cleanText($tag->textContent);
            if ($tagText) {
                $book['tags'][] = $tagText;
            }
        }
        
        // Description
        $descNode = $xpath->query('.//div[@class="short-synopsis"]//p', $item)->item(0);
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
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        $html = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            error_log("cURL error fetching {$url}: {$error}");
            return false;
        }
        
        if ($httpCode !== 200) {
            error_log("HTTP {$httpCode} fetching {$url}");
            return false;
        }
        
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