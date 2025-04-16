<?php
/**
 * Admin Page Base Class
 * 
 * This class serves as the base for all admin pages, providing
 * common functionality for handling authentication, layout, and error handling.
 * 
 * @package Stories Admin
 * @version 1.0.0
 */

class AdminPage {
    /**
     * @var array Configuration
     */
    protected $config;
    
    /**
     * @var Database Database instance
     */
    protected $db;
    
    /**
     * @var array Current authenticated user
     */
    protected $user;
    
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
     * @var string Page title
     */
    protected $pageTitle = 'Admin';
    
    /**
     * @var string Active menu item
     */
    protected $activeMenu = '';
    
    /**
     * @var bool Whether the page requires authentication
     */
    protected $requireAuth = true;
    
    /**
     * @var array|string Required roles for the page
     */
    protected $requiredRoles = ['admin', 'editor'];
    
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
        
        // Initialize database
        $this->db = Database::getInstance($this->config['db']);
        
        // Initialize Auth
        Auth::init($this->config['security']);
        
        // Check authentication
        if ($this->requireAuth) {
            $this->checkAuth();
        }
    }
    
    /**
     * Check if user is authenticated
     */
    protected function checkAuth() {
        $this->user = Auth::checkAuth();
        
        if (!$this->user) {
            // Redirect to login page
            $this->redirect('login.php');
        }
        
        // Check if user has required role
        if ($this->requiredRoles && !Auth::hasRole($this->user, $this->requiredRoles)) {
            // Set error message
            $this->setError('You do not have permission to access this page.');
            
            // Redirect to dashboard
            $this->redirect('index.php');
        }
    }
    
    /**
     * Process the page request
     */
    public function process() {
        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handlePost();
        }
        
        // Get page data
        $this->getData();
        
        // Render the page
        $this->render();
    }
    
    /**
     * Handle POST request
     */
    protected function handlePost() {
        // To be implemented by child classes
    }
    
    /**
     * Get page data
     */
    protected function getData() {
        // To be implemented by child classes
    }
    
    /**
     * Render the page
     */
    protected function render() {
        // Include header
        $this->includeTemplate('header', [
            'pageTitle' => $this->pageTitle,
            'activeMenu' => $this->activeMenu,
            'user' => $this->user
        ]);
        
        // Include page content
        $this->includeTemplate($this->getContentTemplate(), array_merge(
            $this->data,
            [
                'errors' => $this->errors,
                'success' => $this->success
            ]
        ));
        
        // Include footer
        $this->includeTemplate('footer');
    }
    
    /**
     * Get content template name
     * 
     * @return string Template name
     */
    protected function getContentTemplate() {
        // Default to class name without 'Page' suffix
        $className = get_class($this);
        $templateName = str_replace('Page', '', $className);
        
        return strtolower($templateName);
    }
    
    /**
     * Include a template file
     * 
     * @param string $template Template name
     * @param array $data Data to pass to the template
     */
    protected function includeTemplate($template, $data = []) {
        // Extract data to make variables available in the template
        extract($data);
        
        // Include the template file
        $templateFile = __DIR__ . '/../views/' . $template . '.php';
        
        if (file_exists($templateFile)) {
            include $templateFile;
        } else {
            echo "Template not found: $template";
        }
    }
    
    /**
     * Set an error message
     * 
     * @param string $message Error message
     */
    protected function setError($message) {
        $this->errors[] = $message;
        
        // Store in session for redirects
        if (!isset($_SESSION['errors'])) {
            $_SESSION['errors'] = [];
        }
        
        $_SESSION['errors'][] = $message;
    }
    
    /**
     * Set a success message
     * 
     * @param string $message Success message
     */
    protected function setSuccess($message) {
        $this->success[] = $message;
        
        // Store in session for redirects
        if (!isset($_SESSION['success'])) {
            $_SESSION['success'] = [];
        }
        
        $_SESSION['success'][] = $message;
    }
    
    /**
     * Get error messages from session
     */
    protected function getSessionErrors() {
        if (isset($_SESSION['errors'])) {
            $this->errors = array_merge($this->errors, $_SESSION['errors']);
            unset($_SESSION['errors']);
        }
    }
    
    /**
     * Get success messages from session
     */
    protected function getSessionSuccess() {
        if (isset($_SESSION['success'])) {
            $this->success = array_merge($this->success, $_SESSION['success']);
            unset($_SESSION['success']);
        }
    }
    
    /**
     * Redirect to another page
     * 
     * @param string $url URL to redirect to
     */
    protected function redirect($url) {
        header('Location: ' . $url);
        exit;
    }
    
    /**
     * Get a GET parameter
     * 
     * @param string $name Parameter name
     * @param mixed $default Default value
     * @return mixed Parameter value
     */
    protected function getParam($name, $default = null) {
        return isset($_GET[$name]) ? $_GET[$name] : $default;
    }
    
    /**
     * Get a POST parameter
     * 
     * @param string $name Parameter name
     * @param mixed $default Default value
     * @return mixed Parameter value
     */
    protected function getPostParam($name, $default = null) {
        return isset($_POST[$name]) ? $_POST[$name] : $default;
    }
    
    /**
     * Check if a POST parameter exists
     * 
     * @param string $name Parameter name
     * @return bool True if parameter exists
     */
    protected function hasPostParam($name) {
        return isset($_POST[$name]);
    }
    
    /**
     * Get current page URL
     * 
     * @return string Current page URL
     */
    protected function getCurrentUrl() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $uri = $_SERVER['REQUEST_URI'];
        
        return "$protocol://$host$uri";
    }
}