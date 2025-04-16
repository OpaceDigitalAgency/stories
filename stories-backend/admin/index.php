<?php
/**
 * Dashboard Page
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
     * @var ApiClient API client
     */
    private $apiClient;
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        
        // Set page title
        $this->pageTitle = 'Dashboard';
        
        // Set active menu
        $this->activeMenu = 'dashboard';
        
        // Initialize API client
        $this->apiClient = new ApiClient(API_URL, isset($_COOKIE['auth_token']) ? $_COOKIE['auth_token'] : null);
        
        // Get session messages
        $this->getSessionErrors();
        $this->getSessionSuccess();
    }
    
    /**
     * Get page data
     */
    protected function getData() {
        // Get statistics
        $stats = $this->apiClient->getStatistics();
        
        if (!$stats) {
            $stats = [
                'stories' => 0,
                'authors' => 0,
                'blog_posts' => 0,
                'directory_items' => 0,
                'games' => 0,
                'ai_tools' => 0,
                'tags' => 0,
                'featured_stories' => 0,
                'moderation_stories' => 0
            ];
        }
        
        $this->data['stats'] = $stats;
        
        // Get recent stories
        $recentStories = $this->apiClient->get('stories', [
            'sort' => '-publishedAt',
            'pageSize' => 5
        ]);
        
        $this->data['recentStories'] = $recentStories ? $recentStories['data'] : [];
        
        // Get recent blog posts
        $recentBlogPosts = $this->apiClient->get('blog-posts', [
            'sort' => '-publishedAt',
            'pageSize' => 5
        ]);
        
        $this->data['recentBlogPosts'] = $recentBlogPosts ? $recentBlogPosts['data'] : [];
        
        // Get stories needing moderation
        $moderationStories = $this->apiClient->get('stories', [
            'needs_moderation' => 1,
            'sort' => '-createdAt',
            'pageSize' => 10
        ]);
        
        $this->data['moderationStories'] = $moderationStories ? $moderationStories['data'] : [];
    }
}

// Create and process the page
$page = new DashboardPage();
$page->process();