<?php
/**
 * AI Tools Admin Page
 * 
 * This page handles CRUD operations for AI tools.
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
 * AI Tools Page Class
 */
class AiToolsPage extends CrudPage {
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        
        // Set page title
        $this->pageTitle = 'AI Tools';
        
        // Set active menu
        $this->activeMenu = 'ai-tools';
        
        // Set entity name
        $this->entityName = 'AI Tool';
        $this->entityNamePlural = 'AI Tools';
        
        // Set API endpoint
        $this->endpoint = 'ai-tools';
        
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
                    ['value' => 'Writing', 'label' => 'Writing'],
                    ['value' => 'Editing', 'label' => 'Editing'],
                    ['value' => 'Illustration', 'label' => 'Illustration'],
                    ['value' => 'Translation', 'label' => 'Translation'],
                    ['value' => 'Summarization', 'label' => 'Summarization'],
                    ['value' => 'Learning', 'label' => 'Learning'],
                    ['value' => 'Other', 'label' => 'Other']
                ]
            ],
            [
                'name' => 'free',
                'label' => 'Free',
                'type' => 'boolean',
                'list' => true,
                'form' => true,
                'view' => true,
                'default' => true,
                'checkboxLabel' => 'Free to use'
            ],
            [
                'name' => 'logo',
                'label' => 'Logo',
                'type' => 'image',
                'list' => false,
                'form' => true,
                'view' => true,
                'default' => null
            ]
        ];
        
        // Set required fields
        $this->requiredFields = ['name', 'url', 'category'];
        
        // Set searchable fields
        $this->searchableFields = ['name', 'description', 'url'];
        
        // Set sortable fields
        $this->sortableFields = ['id', 'name', 'category'];
        
        // Set default sort
        $this->defaultSortField = 'name';
        $this->defaultSortDirection = 'asc';
    }
    
    /**
     * Handle create
     */
    protected function handleCreate() {
        // Handle file upload
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            // Create file upload instance
            $fileUpload = new FileUpload($this->config['media']);
            
            // Upload file
            $file = $fileUpload->upload($_FILES['logo'], 'ai_tool', 0, 'logo');
            
            if ($file) {
                $_POST['logo'] = $file;
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
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            // Create file upload instance
            $fileUpload = new FileUpload($this->config['media']);
            
            // Get item ID
            $id = $this->getParam('id');
            
            // Upload file
            $file = $fileUpload->upload($_FILES['logo'], 'ai_tool', $id, 'logo');
            
            if ($file) {
                $_POST['logo'] = $file;
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
$page = new AiToolsPage();
$page->process();