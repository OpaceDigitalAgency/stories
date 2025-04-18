<?php
/**
 * Admin Dashboard
 * 
 * This is the main dashboard page for the admin UI.
 * 
 * @package Stories Admin
 * @version 1.0.0
 */

// Include required files
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/ApiClient.php';
require_once __DIR__ . '/includes/AdminPage.php';

/**
 * Dashboard Page Class
 */
class DashboardPage extends AdminPage {
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        
        // Set page title
        $this->pageTitle = 'Dashboard';
        
        // Set active menu
        $this->activeMenu = 'dashboard';
    }
    
    /**
     * Get page data
     */
    protected function getData() {
        // Initialize API client
        $apiClient = new ApiClient(API_URL, isset($_COOKIE['auth_token']) ? $_COOKIE['auth_token'] : null);
        
        // Get content statistics
        $stats = [
            'stories' => 0,
            'authors' => 0,
            'blog_posts' => 0,
            'tags' => 0,
            'directory_items' => 0,
            'games' => 0,
            'ai_tools' => 0,
            'media' => 0
        ];
        
        // Debug information
        $apiErrors = [];
        
        // Function to safely get count from API response
        $getCountFromApi = function($endpoint, $params = ['pageSize' => 1]) use ($apiClient, &$apiErrors) {
            try {
                $response = $apiClient->get($endpoint, $params);
                
                if ($response) {
                    // Check for different response formats
                    if (isset($response['meta']['pagination']['total'])) {
                        return $response['meta']['pagination']['total'];
                    } elseif (isset($response['meta']['total'])) {
                        return $response['meta']['total'];
                    } elseif (isset($response['total'])) {
                        return $response['total'];
                    } elseif (isset($response['data']) && is_array($response['data'])) {
                        // If we have data but no count, return the count of data items
                        return count($response['data']);
                    }
                }
                
                // Store error information for debugging
                $error = $apiClient->getLastError();
                if ($error) {
                    $apiErrors[$endpoint] = $apiClient->getFormattedError();
                } else {
                    $apiErrors[$endpoint] = "Unknown error or unexpected response format";
                }
            } catch (Exception $apiEx) {
                $apiErrors[$endpoint] = "API Exception: " . $apiEx->getMessage();
            }
            
            // Fallback to direct database count if API fails
            try {
                $db = Database::getInstance($this->config['db']);
                $tableName = str_replace('-', '_', $endpoint);
                // Handle special cases for table names
                if ($tableName == 'blog_posts') {
                    $tableName = 'blog';
                } elseif ($tableName == 'ai_tools') {
                    $tableName = 'ai_tool';
                } elseif ($tableName == 'directory_items') {
                    $tableName = 'directory';
                }
                
                $query = "SELECT COUNT(*) as count FROM {$tableName}";
                $stmt = $db->query($query);
                $result = $stmt->fetch();
                return $result['count'];
            } catch (Exception $e) {
                // Log the database error
                error_log("Database count error for {$endpoint}: " . $e->getMessage());
                // If both API and direct DB fail, return 0
                return 0;
            }
        };
        
        // Get counts for all content types
        $stats['stories'] = $getCountFromApi('stories');
        $stats['authors'] = $getCountFromApi('authors');
        $stats['blog_posts'] = $getCountFromApi('blog-posts');
        $stats['tags'] = $getCountFromApi('tags');
        $stats['directory_items'] = $getCountFromApi('directory-items');
        $stats['games'] = $getCountFromApi('games');
        $stats['ai_tools'] = $getCountFromApi('ai-tools');
        
        // Get media count directly from database
        try {
            $db = Database::getInstance($this->config['db']);
            $query = "SELECT COUNT(*) as count FROM media";
            $stmt = $db->query($query);
            $result = $stmt->fetch();
            $stats['media'] = $result['count'];
        } catch (Exception $e) {
            // Log the error but continue
            error_log("Error getting media count: " . $e->getMessage());
        }
        
        // Store API errors for debugging
        $this->data['apiErrors'] = $apiErrors;
        
        // Set statistics
        $this->data['stats'] = $stats;
        
        // Function to get recent items for a content type
        $getRecentItems = function($endpoint, $pageSize = 5) use ($apiClient) {
            $response = $apiClient->get($endpoint, [
                'pageSize' => $pageSize,
                'sort' => '-publishedAt'
            ]);
            
            if ($response && isset($response['data'])) {
                // Process each item to handle nested attributes structure
                $items = $response['data'];
                foreach ($items as &$item) {
                    // Handle nested attributes structure (attributes.attributes)
                    if (isset($item['attributes']['attributes']) && is_array($item['attributes']['attributes'])) {
                        // Move nested attributes up one level
                        $item['attributes'] = $item['attributes']['attributes'];
                    }
                    
                    // Handle author data structure for stories
                    if ($endpoint === 'stories' && isset($item['attributes']['author'])) {
                        // If author is already in the correct format, keep it
                        if (isset($item['attributes']['author']['data']['attributes']['name'])) {
                            // Already in correct format
                        }
                        // If author is a simple ID, fetch the author data
                        elseif (is_numeric($item['attributes']['author'])) {
                            $authorId = $item['attributes']['author'];
                            $authorResponse = $apiClient->get("authors/$authorId");
                            if ($authorResponse && isset($authorResponse['data'])) {
                                $item['attributes']['author'] = [
                                    'data' => $authorResponse['data']
                                ];
                            }
                        }
                    }
                    
                    // Handle category data structure for directory items
                    if ($endpoint === 'directory-items' && isset($item['attributes']['category']) && is_numeric($item['attributes']['category'])) {
                        $categoryId = $item['attributes']['category'];
                        $categoryResponse = $apiClient->get("categories/$categoryId");
                        if ($categoryResponse && isset($categoryResponse['data'])) {
                            $item['attributes']['category'] = [
                                'data' => $categoryResponse['data']
                            ];
                        }
                    }
                }
                return $items;
            }
            
            // Fallback to database if API fails
            try {
                $db = Database::getInstance($this->config['db']);
                $tableName = str_replace('-', '_', $endpoint);
                // Handle special cases for table names
                if ($tableName == 'blog_posts') {
                    $tableName = 'blog';
                } elseif ($tableName == 'ai_tools') {
                    $tableName = 'ai_tool';
                } elseif ($tableName == 'directory_items') {
                    $tableName = 'directory';
                }
                
                $query = "SELECT * FROM {$tableName} ORDER BY created_at DESC LIMIT {$pageSize}";
                $stmt = $db->query($query);
                $results = $stmt->fetchAll();
                
                // Format results to match API response format
                $formattedResults = [];
                foreach ($results as $result) {
                    $formattedResults[] = [
                        'id' => $result['id'],
                        'attributes' => $result
                    ];
                }
                
                return $formattedResults;
            } catch (Exception $e) {
                error_log("Database fallback error for {$endpoint}: " . $e->getMessage());
                return [];
            }
        };
        
        // Get recent items for all content types
        $this->data['recentStories'] = $getRecentItems('stories');
        $this->data['recentAuthors'] = $getRecentItems('authors');
        $this->data['recentBlogPosts'] = $getRecentItems('blog-posts');
        $this->data['recentDirectoryItems'] = $getRecentItems('directory-items');
        $this->data['recentGames'] = $getRecentItems('games');
        $this->data['recentAiTools'] = $getRecentItems('ai-tools');
        $this->data['recentTags'] = $getRecentItems('tags');
        
        // Get items that need attention (for example, items pending moderation)
        // This is a placeholder - you would need to implement the actual logic based on your requirements
        $this->data['needsAttention'] = [
            'stories' => [], // Items would be populated based on your business logic
            'blog_posts' => [],
            'directory_items' => [],
            // Add other content types as needed
        ];
    }
    
    /**
     * Get content template name
     * 
     * @return string Template name
     */
    protected function getContentTemplate() {
        return 'dashboard/dashboard';
    }
}

// Create and process the page
$page = new DashboardPage();
$page->process();