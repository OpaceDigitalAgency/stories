<?php
/**
 * Pagination Utility Class
 * 
 * This class provides methods for generating pagination links.
 * 
 * @package Stories Admin
 * @version 1.0.0
 */

class Pagination {
    /**
     * @var int Current page
     */
    private $currentPage;
    
    /**
     * @var int Total items
     */
    private $totalItems;
    
    /**
     * @var int Items per page
     */
    private $itemsPerPage;
    
    /**
     * @var int Total pages
     */
    private $totalPages;
    
    /**
     * @var int Number of page links to show
     */
    private $numLinks;
    
    /**
     * Constructor
     * 
     * @param int $currentPage Current page
     * @param int $totalItems Total items
     * @param int $itemsPerPage Items per page
     * @param int $numLinks Number of page links to show
     */
    public function __construct($currentPage, $totalItems, $itemsPerPage, $numLinks = 5) {
        $this->currentPage = (int)$currentPage;
        $this->totalItems = (int)$totalItems;
        $this->itemsPerPage = (int)$itemsPerPage;
        $this->numLinks = (int)$numLinks;
        
        // Calculate total pages
        $this->totalPages = ceil($this->totalItems / $this->itemsPerPage);
        
        // Ensure current page is valid
        if ($this->currentPage < 1) {
            $this->currentPage = 1;
        } elseif ($this->currentPage > $this->totalPages && $this->totalPages > 0) {
            $this->currentPage = $this->totalPages;
        }
    }
    
    /**
     * Get pagination data
     * 
     * @return array Pagination data
     */
    public function getData() {
        return [
            'current_page' => $this->currentPage,
            'total_items' => $this->totalItems,
            'items_per_page' => $this->itemsPerPage,
            'total_pages' => $this->totalPages
        ];
    }
    
    /**
     * Get offset for SQL LIMIT clause
     * 
     * @return int Offset
     */
    public function getOffset() {
        return ($this->currentPage - 1) * $this->itemsPerPage;
    }
    
    /**
     * Get limit for SQL LIMIT clause
     * 
     * @return int Limit
     */
    public function getLimit() {
        return $this->itemsPerPage;
    }
    
    /**
     * Generate pagination links
     * 
     * @param string $url Base URL for pagination links
     * @param array $queryParams Additional query parameters
     * @return string HTML pagination links
     */
    public function createLinks($url, $queryParams = []) {
        if ($this->totalPages <= 1) {
            return '';
        }
        
        $html = '<nav aria-label="Page navigation"><ul class="pagination">';
        
        // Previous link
        if ($this->currentPage > 1) {
            $html .= $this->createPageLink($url, $this->currentPage - 1, '&laquo; Previous', $queryParams);
        } else {
            $html .= '<li class="page-item disabled"><span class="page-link">&laquo; Previous</span></li>';
        }
        
        // Calculate start and end page
        $startPage = max(1, $this->currentPage - floor($this->numLinks / 2));
        $endPage = min($this->totalPages, $startPage + $this->numLinks - 1);
        
        // Adjust start page if needed
        if ($endPage - $startPage + 1 < $this->numLinks) {
            $startPage = max(1, $endPage - $this->numLinks + 1);
        }
        
        // First page link
        if ($startPage > 1) {
            $html .= $this->createPageLink($url, 1, '1', $queryParams);
            if ($startPage > 2) {
                $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
        }
        
        // Page links
        for ($i = $startPage; $i <= $endPage; $i++) {
            if ($i == $this->currentPage) {
                $html .= '<li class="page-item active"><span class="page-link">' . $i . '</span></li>';
            } else {
                $html .= $this->createPageLink($url, $i, $i, $queryParams);
            }
        }
        
        // Last page link
        if ($endPage < $this->totalPages) {
            if ($endPage < $this->totalPages - 1) {
                $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
            $html .= $this->createPageLink($url, $this->totalPages, $this->totalPages, $queryParams);
        }
        
        // Next link
        if ($this->currentPage < $this->totalPages) {
            $html .= $this->createPageLink($url, $this->currentPage + 1, 'Next &raquo;', $queryParams);
        } else {
            $html .= '<li class="page-item disabled"><span class="page-link">Next &raquo;</span></li>';
        }
        
        $html .= '</ul></nav>';
        
        return $html;
    }
    
    /**
     * Create a page link
     * 
     * @param string $url Base URL
     * @param int $page Page number
     * @param string $text Link text
     * @param array $queryParams Additional query parameters
     * @return string HTML page link
     */
    private function createPageLink($url, $page, $text, $queryParams = []) {
        $params = array_merge(['page' => $page], $queryParams);
        $href = $url . '?' . http_build_query($params);
        
        return '<li class="page-item"><a class="page-link" href="' . htmlspecialchars($href) . '">' . $text . '</a></li>';
    }
}