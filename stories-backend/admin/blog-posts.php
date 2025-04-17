<?php
/**
 * Blog Posts Admin Page
 * 
 * This page handles CRUD operations for blog posts.
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
 * Blog Posts Page Class
 */
class BlogPostsPage extends CrudPage {
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        
        // Set page title
        $this->pageTitle = 'Blog Posts';
        
        // Set active menu
        $this->activeMenu = 'blog-posts';
        
        // Set entity name
        $this->entityName = 'Blog Post';
        $this->entityNamePlural = 'Blog Posts';
        
        // Set API endpoint
        $this->endpoint = 'blog-posts';
        
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
                'name' => 'slug',
                'label' => 'Slug',
                'type' => 'text',
                'list' => true,
                'form' => true,
                'view' => true,
                'default' => '',
                'help' => 'URL-friendly version of the title. Leave blank to generate automatically.'
            ],
            [
                'name' => 'excerpt',
                'label' => 'Excerpt',
                'type' => 'textarea',
                'list' => false,
                'form' => true,
                'view' => true,
                'default' => '',
                'help' => 'A short summary of the blog post.'
            ],
            [
                'name' => 'content',
                'label' => 'Content',
                'type' => 'richtext',
                'list' => false,
                'form' => true,
                'view' => true,
                'default' => ''
            ],
            [
                'name' => 'publishedAt',
                'label' => 'Published Date',
                'type' => 'datetime',
                'list' => true,
                'form' => true,
                'view' => true,
                'default' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'author',
                'label' => 'Author',
                'type' => 'relation',
                'list' => true,
                'form' => true,
                'view' => true,
                'default' => null,
                'options' => []
            ],
            [
                'name' => 'cover',
                'label' => 'Cover Image',
                'type' => 'image',
                'list' => false,
                'form' => true,
                'view' => true,
                'default' => null
            ]
        ];
        
        // Set required fields
        $this->requiredFields = ['title', 'content', 'publishedAt'];
        
        // Set searchable fields
        $this->searchableFields = ['title', 'excerpt', 'content'];
        
        // Set sortable fields
        $this->sortableFields = ['id', 'title', 'publishedAt'];
        
        // Set default sort
        $this->defaultSortField = 'publishedAt';
        $this->defaultSortDirection = 'desc';
    }
    
    /**
     * Get create data
     */
    protected function getCreateData() {
        parent::getCreateData();
        
        // Get authors for dropdown
        $authors = $this->apiClient->get('authors', ['pageSize' => 100]);
        $authorOptions = [];
        
        if ($authors && isset($authors['data'])) {
            if (is_array($authors['data'])) {
                foreach ($authors['data'] as $author) {
                    if (isset($author['id'])) {
                        $authorOptions[] = [
                            'id' => $author['id'],
                            'name' => isset($author['attributes']) && isset($author['attributes']['name'])
                                ? $author['attributes']['name']
                                : ($author['name'] ?? 'Unknown')
                        ];
                    }
                }
            }
        }
        
        // Update author field options
        foreach ($this->fields as &$field) {
            if ($field['name'] === 'author') {
                $field['options'] = $authorOptions;
                break;
            }
        }
    }
    
    /**
     * Get edit data
     */
    protected function getEditData() {
        parent::getEditData();
        
        // Get authors for dropdown
        $authors = $this->apiClient->get('authors', ['pageSize' => 100]);
        $authorOptions = [];
        
        if ($authors && isset($authors['data'])) {
            if (is_array($authors['data'])) {
                foreach ($authors['data'] as $author) {
                    if (isset($author['id'])) {
                        $authorOptions[] = [
                            'id' => $author['id'],
                            'name' => isset($author['attributes']) && isset($author['attributes']['name'])
                                ? $author['attributes']['name']
                                : ($author['name'] ?? 'Unknown')
                        ];
                    }
                }
            }
        }
        
        // Update author field options
        foreach ($this->fields as &$field) {
            if ($field['name'] === 'author') {
                $field['options'] = $authorOptions;
                break;
            }
        }
    }
    
    /**
     * Handle create
     */
    protected function handleCreate() {
        // Handle file upload
        if (isset($_FILES['cover']) && $_FILES['cover']['error'] === UPLOAD_ERR_OK) {
            // Create file upload instance
            $fileUpload = new FileUpload($this->config['media']);
            
            // Upload file
            $file = $fileUpload->upload($_FILES['cover'], 'blog_post', 0, 'cover');
            
            if ($file) {
                $_POST['cover'] = $file;
            } else {
                $this->errors = array_merge($this->errors, $fileUpload->getErrors());
                return;
            }
        }
        
        // Generate slug if not provided
        if (empty($_POST['slug']) && isset($_POST['title'])) {
            $_POST['slug'] = Validator::generateSlug($_POST['title']);
        }
        
        // Call parent method
        parent::handleCreate();
    }
    
    /**
     * Handle edit
     */
    protected function handleEdit() {
        // Handle file upload
        if (isset($_FILES['cover']) && $_FILES['cover']['error'] === UPLOAD_ERR_OK) {
            // Create file upload instance
            $fileUpload = new FileUpload($this->config['media']);
            
            // Get item ID
            $id = $this->getParam('id');
            
            // Upload file
            $file = $fileUpload->upload($_FILES['cover'], 'blog_post', $id, 'cover');
            
            if ($file) {
                $_POST['cover'] = $file;
            } else {
                $this->errors = array_merge($this->errors, $fileUpload->getErrors());
                return;
            }
        }
        
        // Generate slug if not provided
        if (empty($_POST['slug']) && isset($_POST['title'])) {
            $_POST['slug'] = Validator::generateSlug($_POST['title']);
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
$page = new BlogPostsPage();
$page->process();