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
        
        // Get stories count
        $storiesResponse = $apiClient->get('stories', ['pageSize' => 1]);
        if ($storiesResponse && isset($storiesResponse['meta']['pagination']['total'])) {
            $stats['stories'] = $storiesResponse['meta']['pagination']['total'];
        }
        
        // Get authors count
        $authorsResponse = $apiClient->get('authors', ['pageSize' => 1]);
        if ($authorsResponse && isset($authorsResponse['meta']['pagination']['total'])) {
            $stats['authors'] = $authorsResponse['meta']['pagination']['total'];
        }
        
        // Get blog posts count
        $blogPostsResponse = $apiClient->get('blog-posts', ['pageSize' => 1]);
        if ($blogPostsResponse && isset($blogPostsResponse['meta']['pagination']['total'])) {
            $stats['blog_posts'] = $blogPostsResponse['meta']['pagination']['total'];
        }
        
        // Get tags count
        $tagsResponse = $apiClient->get('tags', ['pageSize' => 1]);
        if ($tagsResponse && isset($tagsResponse['meta']['pagination']['total'])) {
            $stats['tags'] = $tagsResponse['meta']['pagination']['total'];
        }
        
        // Get directory items count
        $directoryItemsResponse = $apiClient->get('directory-items', ['pageSize' => 1]);
        if ($directoryItemsResponse && isset($directoryItemsResponse['meta']['pagination']['total'])) {
            $stats['directory_items'] = $directoryItemsResponse['meta']['pagination']['total'];
        }
        
        // Get games count
        $gamesResponse = $apiClient->get('games', ['pageSize' => 1]);
        if ($gamesResponse && isset($gamesResponse['meta']['pagination']['total'])) {
            $stats['games'] = $gamesResponse['meta']['pagination']['total'];
        }
        
        // Get AI tools count
        $aiToolsResponse = $apiClient->get('ai-tools', ['pageSize' => 1]);
        if ($aiToolsResponse && isset($aiToolsResponse['meta']['pagination']['total'])) {
            $stats['ai_tools'] = $aiToolsResponse['meta']['pagination']['total'];
        }
        
        // Get media count
        try {
            $db = Database::getInstance($this->config['db']);
            $query = "SELECT COUNT(*) as count FROM media";
            $stmt = $db->query($query);
            $result = $stmt->fetch();
            $stats['media'] = $result['count'];
        } catch (Exception $e) {
            // Ignore errors
        }
        
        // Set statistics
        $this->data['stats'] = $stats;
        
        // Get recent stories
        $recentStoriesResponse = $apiClient->get('stories', [
            'pageSize' => 5,
            'sort' => '-publishedAt'
        ]);
        
        if ($recentStoriesResponse && isset($recentStoriesResponse['data'])) {
            $this->data['recentStories'] = $recentStoriesResponse['data'];
        } else {
            $this->data['recentStories'] = [];
        }
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