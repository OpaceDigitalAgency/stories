<?php
/**
 * Media Admin Page
 * 
 * This page handles media uploads and management.
 * 
 * @package Stories Admin
 * @version 1.0.0
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include required files
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/ApiClient.php';
require_once __DIR__ . '/includes/Validator.php';
require_once __DIR__ . '/includes/FileUpload.php';
require_once __DIR__ . '/includes/AdminPage.php';

/**
 * Media Page Class
 */
class MediaPage extends AdminPage {
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
        $this->pageTitle = 'Media';
        
        // Set active menu
        $this->activeMenu = 'media';
        
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
        // Get current action
        $action = $this->getParam('action', 'list');
        
        // Call appropriate method based on action
        switch ($action) {
            case 'upload':
                $this->getUploadData();
                break;
            case 'delete':
                $this->getDeleteData();
                break;
            default:
                $this->getListData();
                break;
        }
    }
    
    /**
     * Handle POST request
     */
    protected function handlePost() {
        // Get current action
        $action = $this->getParam('action', 'list');
        
        // Call appropriate method based on action
        switch ($action) {
            case 'upload':
                $this->handleUpload();
                break;
            case 'delete':
                $this->handleDelete();
                break;
            default:
                // No action needed for list
                break;
        }
    }
    
    /**
     * Get list data
     */
    protected function getListData() {
        // Get pagination parameters
        $page = $this->getParam('page', 1);
        $pageSize = $this->getParam('pageSize', 20);
        
        // Get filter parameters
        $entityType = $this->getParam('entity_type', '');
        $type = $this->getParam('type', '');
        
        // Build query parameters
        $params = [
            'page' => $page,
            'pageSize' => $pageSize,
            'sort' => '-created_at'
        ];
        
        // Add filter parameters if provided
        if ($entityType) {
            $params['entity_type'] = $entityType;
        }
        
        if ($type) {
            $params['type'] = $type;
        }
        
        // Get media from database
        global $config;
        $db = Database::getInstance($config['db']);
        
        try {
            // Build query
            $query = "SELECT * FROM media";
            $whereClause = [];
            $queryParams = [];
            
            if ($entityType) {
                $whereClause[] = "entity_type = ?";
                $queryParams[] = $entityType;
            }
            
            if ($type) {
                $whereClause[] = "type = ?";
                $queryParams[] = $type;
            }
            
            if (!empty($whereClause)) {
                $query .= " WHERE " . implode(' AND ', $whereClause);
            }
            
            $query .= " ORDER BY created_at DESC";
            
            // Add pagination
            $offset = ($page - 1) * $pageSize;
            $query .= " LIMIT ?, ?";
            $queryParams[] = $offset;
            $queryParams[] = $pageSize;
            
            // Execute query
            $stmt = $db->query($query, $queryParams);
            $media = $stmt->fetchAll();
            
            // Get total count
            $countQuery = "SELECT COUNT(*) as total FROM media";
            if (!empty($whereClause)) {
                $countQuery .= " WHERE " . implode(' AND ', $whereClause);
            }
            
            $countStmt = $db->query($countQuery, array_slice($queryParams, 0, count($queryParams) - 2));
            $total = $countStmt->fetch()['total'];
            
            // Set data
            $this->data['media'] = $media;
            $this->data['pagination'] = [
                'page' => $page,
                'pageSize' => $pageSize,
                'pageCount' => ceil($total / $pageSize),
                'total' => $total
            ];
            $this->data['filters'] = [
                'entity_type' => $entityType,
                'type' => $type
            ];
            
            // Get entity types and media types for filters
            $entityTypesQuery = "SELECT DISTINCT entity_type FROM media ORDER BY entity_type";
            $entityTypesStmt = $db->query($entityTypesQuery);
            $this->data['entityTypes'] = $entityTypesStmt->fetchAll(\PDO::FETCH_COLUMN);
            
            $typesQuery = "SELECT DISTINCT type FROM media ORDER BY type";
            $typesStmt = $db->query($typesQuery);
            $this->data['types'] = $typesStmt->fetchAll(\PDO::FETCH_COLUMN);
        } catch (\Exception $e) {
            $this->setError('Failed to fetch media: ' . $e->getMessage());
        }
    }
    
    /**
     * Get upload data
     */
    protected function getUploadData() {
        // Set data
        $this->data['entityTypes'] = [
            'story' => 'Story',
            'author' => 'Author',
            'blog_post' => 'Blog Post',
            'directory_item' => 'Directory Item',
            'game' => 'Game',
            'ai_tool' => 'AI Tool'
        ];
        
        $this->data['mediaTypes'] = [
            'cover' => 'Cover Image',
            'avatar' => 'Avatar',
            'logo' => 'Logo',
            'thumbnail' => 'Thumbnail'
        ];
    }
    
    /**
     * Get delete data
     */
    protected function getDeleteData() {
        // Get media ID
        $id = $this->getParam('id');
        
        if (!$id) {
            $this->setError('Invalid ID');
            $this->redirect('media.php');
            return;
        }
        
        // Get media from database
        $db = Database::getInstance();
        
        try {
            $query = "SELECT * FROM media WHERE id = ? LIMIT 1";
            $stmt = $db->query($query, [$id]);
            
            if ($stmt->rowCount() === 0) {
                $this->setError('Media not found');
                $this->redirect('media.php');
                return;
            }
            
            $this->data['media'] = $stmt->fetch();
        } catch (\Exception $e) {
            $this->setError('Failed to fetch media: ' . $e->getMessage());
            $this->redirect('media.php');
        }
    }
    
    /**
     * Handle upload
     */
    protected function handleUpload() {
        // Validate required fields
        if (!Validator::required($_POST, ['entity_type', 'entity_id', 'type'])) {
            $this->errors = Validator::getErrors();
            return;
        }
        
        // Validate file
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $this->setError('Please select a file to upload');
            return;
        }
        
        // Check if media configuration exists
        if (!isset($GLOBALS['config']['media'])) {
            $this->setError("Media configuration not found in global config");
            return;
        }
        
        try {
            // Create file upload instance
            $fileUpload = new FileUpload($GLOBALS['config']['media']);
        } catch (Exception $e) {
            $this->setError("Error initializing file upload: " . $e->getMessage());
            return;
        }
        
        // Upload file
        $file = $fileUpload->upload(
            $_FILES['file'],
            $_POST['entity_type'],
            $_POST['entity_id'],
            $_POST['type']
        );
        
        if ($file) {
            $this->setSuccess('File uploaded successfully');
            $this->redirect('media.php');
        } else {
            $this->errors = array_merge($this->errors, $fileUpload->getErrors());
        }
    }
    
    /**
     * Handle delete
     */
    protected function handleDelete() {
        // Get media ID
        $id = $this->getParam('id');
        
        if (!$id) {
            $this->setError('Invalid ID');
            $this->redirect('media.php');
            return;
        }
        
        // Check if media configuration exists
        if (!isset($GLOBALS['config']['media'])) {
            $this->setError("Media configuration not found in global config");
            $this->redirect('media.php');
            return;
        }
        
        try {
            // Create file upload instance
            $fileUpload = new FileUpload($GLOBALS['config']['media']);
        } catch (Exception $e) {
            $this->setError("Error initializing file upload: " . $e->getMessage());
            $this->redirect('media.php');
            return;
        }
        
        // Delete file
        if ($fileUpload->delete($id)) {
            $this->setSuccess('File deleted successfully');
        } else {
            $this->setError('Failed to delete file');
        }
        
        $this->redirect('media.php');
    }
    
    /**
     * Get content template name
     * 
     * @return string Template name
     */
    protected function getContentTemplate() {
        // Get current action
        $action = $this->getParam('action', 'list');
        
        // Get template name based on action
        switch ($action) {
            case 'upload':
                return 'media/upload';
            case 'delete':
                return 'media/delete';
            default:
                return 'media/list';
        }
    }
}

// Create and process the page
$page = new MediaPage();
$page->process();