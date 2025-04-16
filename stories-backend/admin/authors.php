<?php
/**
 * Authors Admin Page
 * 
 * This page handles CRUD operations for authors.
 * 
 * @package Stories Admin
 * @version 1.0.0
 */

// Include required files
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/ApiClient.php';
require_once __DIR__ . '/includes/Validator.php';
require_once __DIR__ . '/includes/FileUpload.php';
require_once __DIR__ . '/includes/AdminPage.php';
require_once __DIR__ . '/includes/CrudPage.php';

/**
 * Authors Page Class
 */
class AuthorsPage extends CrudPage {
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        
        // Set page title
        $this->pageTitle = 'Authors';
        
        // Set active menu
        $this->activeMenu = 'authors';
        
        // Set entity name
        $this->entityName = 'Author';
        $this->entityNamePlural = 'Authors';
        
        // Set API endpoint
        $this->endpoint = 'authors';
        
        // Set fields
        $this->fields = [
            [
                'name' => 'name',
                'label' => 'Name',
                'type' => 'text',
                'main' => true,
                'list' => true,
                'form' => true,
                'view' => true,
                'default' => ''
            ],
            [
                'name' => 'slug',
                'label' => 'Slug',
                'type' => 'text',
                'list' => true,
                'form' => true,
                'view' => true,
                'default' => '',
                'help' => 'URL-friendly version of the name. Leave blank to generate automatically.'
            ],
            [
                'name' => 'bio',
                'label' => 'Biography',
                'type' => 'textarea',
                'list' => false,
                'form' => true,
                'view' => true,
                'default' => ''
            ],
            [
                'name' => 'featured',
                'label' => 'Featured',
                'type' => 'boolean',
                'list' => true,
                'form' => true,
                'view' => true,
                'default' => false,
                'checkboxLabel' => 'Mark as featured author'
            ],
            [
                'name' => 'twitter',
                'label' => 'Twitter',
                'type' => 'text',
                'list' => false,
                'form' => true,
                'view' => true,
                'default' => '',
                'help' => 'Twitter username without @'
            ],
            [
                'name' => 'instagram',
                'label' => 'Instagram',
                'type' => 'text',
                'list' => false,
                'form' => true,
                'view' => true,
                'default' => '',
                'help' => 'Instagram username without @'
            ],
            [
                'name' => 'website',
                'label' => 'Website',
                'type' => 'text',
                'list' => false,
                'form' => true,
                'view' => true,
                'default' => '',
                'help' => 'Full URL including https://'
            ],
            [
                'name' => 'avatar',
                'label' => 'Avatar',
                'type' => 'image',
                'list' => false,
                'form' => true,
                'view' => true,
                'default' => null
            ]
        ];
        
        // Set required fields
        $this->requiredFields = ['name'];
        
        // Set searchable fields
        $this->searchableFields = ['name', 'bio'];
        
        // Set sortable fields
        $this->sortableFields = ['id', 'name', 'featured'];
        
        // Set default sort
        $this->defaultSortField = 'name';
        $this->defaultSortDirection = 'asc';
    }
    
    /**
     * Handle create
     */
    protected function handleCreate() {
        // Handle file upload
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            // Create file upload instance
            $fileUpload = new FileUpload($this->config['media']);
            
            // Upload file
            $file = $fileUpload->upload($_FILES['avatar'], 'author', 0, 'avatar');
            
            if ($file) {
                $_POST['avatar'] = $file;
            } else {
                $this->errors = array_merge($this->errors, $fileUpload->getErrors());
                return;
            }
        }
        
        // Generate slug if not provided
        if (empty($_POST['slug']) && isset($_POST['name'])) {
            $_POST['slug'] = Validator::generateSlug($_POST['name']);
        }
        
        // Call parent method
        parent::handleCreate();
    }
    
    /**
     * Handle edit
     */
    protected function handleEdit() {
        // Handle file upload
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            // Create file upload instance
            $fileUpload = new FileUpload($this->config['media']);
            
            // Get item ID
            $id = $this->getParam('id');
            
            // Upload file
            $file = $fileUpload->upload($_FILES['avatar'], 'author', $id, 'avatar');
            
            if ($file) {
                $_POST['avatar'] = $file;
            } else {
                $this->errors = array_merge($this->errors, $fileUpload->getErrors());
                return;
            }
        }
        
        // Generate slug if not provided
        if (empty($_POST['slug']) && isset($_POST['name'])) {
            $_POST['slug'] = Validator::generateSlug($_POST['name']);
        }
        
        // Call parent method
        parent::handleEdit();
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
            case 'create':
                return 'generic/form';
            case 'edit':
                return 'generic/form';
            case 'view':
                return 'generic/view';
            case 'delete':
                return 'generic/delete';
            default:
                return 'generic/list';
        }
    }
}

// Create and process the page
$page = new AuthorsPage();
$page->process();