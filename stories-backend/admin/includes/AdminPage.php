<?php
/**
 * Admin Page Base Class
 * 
 * This class serves as the base class for all admin pages.
 * 
 * @package Stories Admin
 * @version 1.0.0
 */

class AdminPage {
    /**
     * @var string Page title
     */
    protected $pageTitle = 'Admin';
    
    /**
     * @var string Active menu item
     */
    protected $activeMenu = '';
    
    /**
     * @var array Page data
     */
    protected $data = [];
    
    /**
     * @var array Error messages
     */
    protected $errors = [];
    
    /**
     * @var array Success messages
     */
    protected $success = [];
    
    /**
     * @var array Config
     */
    protected $config;
    
    /**
     * Constructor
     */
    public function __construct() {
        // Start session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Load configuration
        $this->config = require __DIR__ . '/config.php';
        
        // Check authentication
        $this->checkAuth();
    }
    
    /**
     * Process the page
     */
    public function process() {
        // Handle POST request
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handlePost();
        }
        
        // Get page data
        $this->getData();
        
        // Render the page
        $this->render();
    }
    
    /**
     * Check if user is authenticated
     */
    protected function checkAuth() {
        // Include Auth class
        require_once __DIR__ . '/Auth.php';
        
        // Initialize Auth
        Auth::init($this->config['security']);
        
        // Check if user is authenticated
        $user = Auth::checkAuth();
        
        if (!$user) {
            // Redirect to login page
            $this->redirect('login.php');
            exit;
        }
        
        // Store user in data
        $this->data['user'] = $user;
    }
    
    /**
     * Get page data
     */
    protected function getData() {
        // To be implemented by child classes
    }
    
    /**
     * Handle POST request
     */
    protected function handlePost() {
        // To be implemented by child classes
    }
    
    /**
     * Render the page
     */
    protected function render() {
        // Set page title
        $this->data['pageTitle'] = $this->pageTitle;
        
        // Set active menu
        $this->data['activeMenu'] = $this->activeMenu;
        
        // Set errors
        $this->data['errors'] = $this->errors;
        
        // Set success messages
        $this->data['success'] = $this->success;
        
        // Extract data to variables
        extract($this->data);
        
        // Include header
        include __DIR__ . '/../views/header.php';
        
        // Include content template
        $contentTemplate = $this->getContentTemplate();
        if (file_exists(__DIR__ . '/../views/' . $contentTemplate . '.php')) {
            include __DIR__ . '/../views/' . $contentTemplate . '.php';
        } else {
            echo '<div class="container-fluid"><div class="alert alert-danger">Template not found: ' . $contentTemplate . '</div></div>';
        }
        
        // Include footer
        include __DIR__ . '/../views/footer.php';
    }
    
    /**
     * Get content template name
     * 
     * @return string Template name
     */
    protected function getContentTemplate() {
        return 'dashboard/dashboard';
    }
    
    /**
     * Redirect to a URL
     * 
     * @param string $url URL to redirect to
     */
    protected function redirect($url) {
        header('Location: ' . ADMIN_URL . '/' . $url);
        exit;
    }
    
    /**
     * Get a request parameter
     * 
     * @param string $name Parameter name
     * @param mixed $default Default value
     * @return mixed Parameter value
     */
    protected function getParam($name, $default = null) {
        return $_GET[$name] ?? $default;
    }
    
    /**
     * Set an error message
     * 
     * @param string $message Error message
     */
    protected function setError($message) {
        $this->errors[] = $message;
        $_SESSION['errors'] = $this->errors;
    }
    
    /**
     * Set a success message
     * 
     * @param string $message Success message
     */
    protected function setSuccess($message) {
        $this->success[] = $message;
        $_SESSION['success'] = $this->success;
    }
    
    /**
     * Get session errors
     */
    protected function getSessionErrors() {
        if (isset($_SESSION['errors'])) {
            $this->errors = $_SESSION['errors'];
            unset($_SESSION['errors']);
        }
    }
    
    /**
     * Get session success messages
     */
    protected function getSessionSuccess() {
        if (isset($_SESSION['success'])) {
            $this->success = $_SESSION['success'];
            unset($_SESSION['success']);
        }
    }
}