<?php
/**
 * Games Admin Page
 * 
 * This page handles CRUD operations for games.
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
 * Games Page Class
 */
class GamesPage extends CrudPage {
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        
        // Set page title
        $this->pageTitle = 'Games';
        
        // Set active menu
        $this->activeMenu = 'games';
        
        // Set entity name
        $this->entityName = 'Game';
        $this->entityNamePlural = 'Games';
        
        // Set API endpoint
        $this->endpoint = 'games';
        
        // Set fields
        $this->fields = [
            [
                'name' => 'title',
                'label' => 'Title',
                'type' => 'text',
                'main' => true,
                'list' => true,
                'form' => true,
                'view' => true,
                'default' => ''
            ],
            [
                'name' => 'description',
                'label' => 'Description',
                'type' => 'textarea',
                'list' => false,
                'form' => true,
                'view' => true,
                'default' => ''
            ],
            [
                'name' => 'url',
                'label' => 'URL',
                'type' => 'text',
                'list' => true,
                'form' => true,
                'view' => true,
                'default' => '',
                'help' => 'Full URL including https:// or relative path if hosted on this site'
            ],
            [
                'name' => 'category',
                'label' => 'Category',
                'type' => 'select',
                'list' => true,
                'form' => true,
                'view' => true,
                'default' => '',
                'options' => [
                    ['value' => 'Educational', 'label' => 'Educational'],
                    ['value' => 'Puzzle', 'label' => 'Puzzle'],
                    ['value' => 'Adventure', 'label' => 'Adventure'],
                    ['value' => 'Word', 'label' => 'Word'],
                    ['value' => 'Quiz', 'label' => 'Quiz'],
                    ['value' => 'Memory', 'label' => 'Memory'],
                    ['value' => 'Other', 'label' => 'Other']
                ]
            ],
            [
                'name' => 'ageGroup',
                'label' => 'Age Group',
                'type' => 'text',
                'list' => true,
                'form' => true,
                'view' => true,
                'default' => '',
                'help' => 'e.g., "5-8", "9-12", "13+"'
            ],
            [
                'name' => 'thumbnail',
                'label' => 'Thumbnail',
                'type' => 'image',
                'list' => false,
                'form' => true,
                'view' => true,
                'default' => null
            ]
        ];
        
        // Set required fields
        $this->requiredFields = ['title', 'url', 'category'];
        
        // Set searchable fields
        $this->searchableFields = ['title', 'description', 'url'];
        
        // Set sortable fields
        $this->sortableFields = ['id', 'title', 'category'];
        
        // Set default sort
        $this->defaultSortField = 'title';
        $this->defaultSortDirection = 'asc';
    }
    
    /**
     * Handle create
     */
    protected function handleCreate() {
        // Handle file upload
        if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
            // Create file upload instance
            $fileUpload = new FileUpload($this->config['media']);
            
            // Upload file
            $file = $fileUpload->upload($_FILES['thumbnail'], 'game', 0, 'thumbnail');
            
            if ($file) {
                $_POST['thumbnail'] = $file;
            } else {
                $this->errors = array_merge($this->errors, $fileUpload->getErrors());
                return;
            }
        }
        
        // Call parent method
        parent::handleCreate();
    }
    
    /**
     * Handle edit
     */
    protected function handleEdit() {
        // Handle file upload
        if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
            // Create file upload instance
            $fileUpload = new FileUpload($this->config['media']);
            
            // Get item ID
            $id = $this->getParam('id');
            
            // Upload file
            $file = $fileUpload->upload($_FILES['thumbnail'], 'game', $id, 'thumbnail');
            
            if ($file) {
                $_POST['thumbnail'] = $file;
            } else {
                $this->errors = array_merge($this->errors, $fileUpload->getErrors());
                return;
            }
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
$page = new GamesPage();
$page->process();