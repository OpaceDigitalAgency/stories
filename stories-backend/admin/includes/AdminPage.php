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
     * @var string Page description
     */
    protected $pageDescription = '';
    
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
     * @var array Breadcrumbs
     */
    protected $breadcrumbs = [];
    
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
        
        // Ensure config is available globally
        if (!isset($GLOBALS['config'])) {
            $GLOBALS['config'] = $this->config;
        }
        
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
        
        // Ensure token consistency between session and cookie
        $this->ensureTokenConsistency();
    }
    
    /**
     * Ensure token consistency between session and cookie
     */
    protected function ensureTokenConsistency() {
        // Check if we have a token in session
        $sessionToken = isset($_SESSION['token']) ? $_SESSION['token'] : null;
        
        // Check if we have a token in cookie
        $cookieToken = isset($_COOKIE['auth_token']) ? $_COOKIE['auth_token'] : null;
        
        // If we have a token in session but not in cookie, set the cookie
        if ($sessionToken && !$cookieToken) {
            setcookie('auth_token', $sessionToken, time() + $this->config['security']['token_expiry'], '/', '', false, true);
            error_log("AdminPage: Set cookie token from session token");
        }
        
        // If we have a token in cookie but not in session, set the session
        if ($cookieToken && !$sessionToken) {
            $_SESSION['token'] = $cookieToken;
            error_log("AdminPage: Set session token from cookie token");
        }
        
        // If we have both tokens but they don't match, refresh the token
        if ($sessionToken && $cookieToken && $sessionToken !== $cookieToken) {
            error_log("AdminPage: Token mismatch between session and cookie, refreshing token");
            Auth::refreshToken($_SESSION['user'], true);
        }
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
        
        // Set page description
        $this->data['pageDescription'] = $this->pageDescription;
        
        // Set active menu
        $this->data['activeMenu'] = $this->activeMenu;
        
        // Set errors
        $this->data['errors'] = $this->errors;
        
        // Set success messages
        $this->data['success'] = $this->success;
        
        // Set breadcrumbs
        $this->data['breadcrumbs'] = $this->breadcrumbs;
        
        // Extract data to variables
        extract($this->data);
        
        // Include header
        include __DIR__ . '/../views/layouts/header.php';
        
        // Include content template
        $contentTemplate = $this->getContentTemplate();
        if (file_exists(__DIR__ . '/../views/' . $contentTemplate . '.php')) {
            include __DIR__ . '/../views/' . $contentTemplate . '.php';
        } else {
            echo '<div class="container-fluid"><div class="alert alert-danger">Template not found: ' . $contentTemplate . '</div></div>';
        }
        
        // Include footer
        include __DIR__ . '/../views/layouts/footer.php';
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
    
    /**
     * Set page description
     *
     * @param string $description Page description
     */
    protected function setPageDescription($description) {
        $this->pageDescription = $description;
    }
    
    /**
     * Add a breadcrumb
     *
     * @param string $label Breadcrumb label
     * @param string|null $url Breadcrumb URL (null for current page)
     */
    protected function addBreadcrumb($label, $url = null) {
        $this->breadcrumbs[$label] = $url;
    }
    
    /**
     * Show a tooltip with help information
     *
     * @param string $text Tooltip text
     * @param string $placement Tooltip placement (top, bottom, left, right)
     * @return string HTML for tooltip
     */
    protected function helpTooltip($text, $placement = 'top') {
        return '<i class="fas fa-question-circle text-muted ms-1" data-bs-toggle="tooltip" data-bs-placement="' . $placement . '" title="' . htmlspecialchars($text) . '"></i>';
    }
    
    /**
     * Format a form field with label, input, and optional help text
     *
     * @param string $type Input type (text, textarea, select, etc.)
     * @param string $name Field name
     * @param string $label Field label
     * @param mixed $value Field value
     * @param array $options Additional options (required, help, placeholder, etc.)
     * @return string HTML for form field
     */
    protected function formField($type, $name, $label, $value = '', $options = []) {
        $required = isset($options['required']) && $options['required'] ? true : false;
        $help = $options['help'] ?? '';
        $placeholder = $options['placeholder'] ?? '';
        $classes = $options['classes'] ?? '';
        $attributes = $options['attributes'] ?? [];
        
        $html = '<div class="mb-3">';
        $html .= '<label for="' . $name . '" class="form-label' . ($required ? ' required' : '') . '">' . $label;
        
        if (!empty($help)) {
            $html .= $this->helpTooltip($help);
        }
        
        $html .= '</label>';
        
        $attributesStr = '';
        foreach ($attributes as $attr => $attrValue) {
            $attributesStr .= ' ' . $attr . '="' . htmlspecialchars($attrValue) . '"';
        }
        
        switch ($type) {
            case 'textarea':
                $html .= '<textarea class="form-control ' . $classes . '" id="' . $name . '" name="' . $name . '" placeholder="' . $placeholder . '"' . ($required ? ' required' : '') . $attributesStr . '>' . htmlspecialchars($value) . '</textarea>';
                break;
                
            case 'select':
                $html .= '<select class="form-select ' . $classes . '" id="' . $name . '" name="' . $name . '"' . ($required ? ' required' : '') . $attributesStr . '>';
                if (!empty($placeholder)) {
                    $html .= '<option value="">' . $placeholder . '</option>';
                }
                if (isset($options['options']) && is_array($options['options'])) {
                    foreach ($options['options'] as $optValue => $optLabel) {
                        $selected = $value == $optValue ? ' selected' : '';
                        $html .= '<option value="' . htmlspecialchars($optValue) . '"' . $selected . '>' . htmlspecialchars($optLabel) . '</option>';
                    }
                }
                $html .= '</select>';
                break;
                
            case 'checkbox':
                $html = '<div class="mb-3 form-check">';
                $html .= '<input type="checkbox" class="form-check-input ' . $classes . '" id="' . $name . '" name="' . $name . '" value="1"' . ($value ? ' checked' : '') . ($required ? ' required' : '') . $attributesStr . '>';
                $html .= '<label class="form-check-label" for="' . $name . '">' . $label;
                if (!empty($help)) {
                    $html .= $this->helpTooltip($help);
                }
                $html .= '</label>';
                break;
                
            default: // text, email, password, etc.
                $html .= '<input type="' . $type . '" class="form-control ' . $classes . '" id="' . $name . '" name="' . $name . '" value="' . htmlspecialchars($value) . '" placeholder="' . $placeholder . '"' . ($required ? ' required' : '') . $attributesStr . '>';
                break;
        }
        
        if (isset($options['feedback'])) {
            $html .= '<div class="form-text text-muted">' . $options['feedback'] . '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
}