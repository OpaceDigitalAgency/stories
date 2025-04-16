<?php
/**
 * Tags Admin Page
 * 
 * This page handles CRUD operations for tags.
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
require_once __DIR__ . '/includes/AdminPage.php';
require_once __DIR__ . '/includes/CrudPage.php';

/**
 * Tags Page Class
 */
class TagsPage extends CrudPage {
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        
        // Set page title
        $this->pageTitle = 'Tags';
        
        // Set active menu
        $this->activeMenu = 'tags';
        
        // Set entity name
        $this->entityName = 'Tag';
        $this->entityNamePlural = 'Tags';
        
        // Set API endpoint
        $this->endpoint = 'tags';
        
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
            ]
        ];
        
        // Set required fields
        $this->requiredFields = ['name'];
        
        // Set searchable fields
        $this->searchableFields = ['name', 'slug'];
        
        // Set sortable fields
        $this->sortableFields = ['id', 'name', 'slug'];
        
        // Set default sort
        $this->defaultSortField = 'name';
        $this->defaultSortDirection = 'asc';
    }
    
    /**
     * Handle create
     */
    protected function handleCreate() {
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
$page = new TagsPage();
$page->process();