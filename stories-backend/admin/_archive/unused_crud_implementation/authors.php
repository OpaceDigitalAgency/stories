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
            ],
            [
                'name' => 'author_type',
                'label' => 'Author Type',
                'type' => 'select',
                'list' => true,
                'form' => true,
                'view' => true,
                'default' => 'retail',
                'options' => [
                    ['value' => 'retail', 'label' => 'Retail (Book Author)'],
                    ['value' => 'parent', 'label' => 'Parent'],
                    ['value' => 'child', 'label' => 'Child'],
                    ['value' => 'educator', 'label' => 'Educator']
                ]
            ],
            [
                'name' => 'age',
                'label' => 'Age',
                'type' => 'number',
                'list' => false,
                'form' => true,
                'view' => true,
                'default' => null,
                'help' => 'Age of child author (1-21)'
            ],
            [
                'name' => 'location',
                'label' => 'Location',
                'type' => 'text',
                'list' => true,
                'form' => true,
                'view' => true,
                'default' => '',
                'help' => 'Author\'s location (city, county, or country)'
            ]
        ];
        
        // Set required fields
        $this->requiredFields = ['name', 'author_type'];
        
        // Set conditional required fields
        $this->conditionalRequiredFields = [
            'age' => function($data) {
                return isset($data['author_type']) && $data['author_type'] === 'child';
            }
        ];
        
        // Set field validation rules
        $this->validationRules = [
            'age' => function($value, $data) {
                if ($data['author_type'] === 'child') {
                    if (!is_numeric($value) || $value < 1 || $value > 21) {
                        return 'Age must be between 1 and 21 for child authors';
                    }
                }
                return true;
            },
            'location' => function($value, $data) {
                if (empty($value)) {
                    return 'Location is required';
                }
                if (strlen($value) > 100) {
                    return 'Location must be less than 100 characters';
                }
                return true;
            }
        ];
        
        // Set searchable fields
        $this->searchableFields = ['name', 'bio', 'location'];
        
        // Set sortable fields
        $this->sortableFields = ['id', 'name', 'featured', 'location', 'author_type'];
        
        // Set default sort
        $this->defaultSortField = 'name';
        $this->defaultSortDirection = 'asc';
    }

    /**
     * Handle delete
     */
    protected function handleDelete() {
        if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
            $this->errors[] = 'Invalid author ID';
            return;
        }

        $action = $_POST['action'] ?? 'cancel';
        $newAuthorId = $_POST['new_author_id'] ?? null;

        if ($action === 'cancel') {
            $this->redirect = 'authors.php';
            return;
        }

        try {
            // Start transaction
            $this->db->beginTransaction();

            $authorId = $_POST['id'];

            // Get story count
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM story_authors WHERE author_id = ?");
            $stmt->execute([$authorId]);
            $storyCount = $stmt->fetchColumn();

            if ($action === 'delete_all') {
                // Get all stories by this author
                $stmt = $this->db->prepare("SELECT story_id FROM story_authors WHERE author_id = ?");
                $stmt->execute([$authorId]);
                $storyIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                // Delete story tags
                if (!empty($storyIds)) {
                    $placeholders = str_repeat('?,', count($storyIds) - 1) . '?';
                    $stmt = $this->db->prepare("DELETE FROM story_tags WHERE story_id IN ($placeholders)");
                    $stmt->execute($storyIds);
                }
                
                // Delete story authors
                $stmt = $this->db->prepare("DELETE FROM story_authors WHERE author_id = ?");
                $stmt->execute([$authorId]);
                
                // Delete stories
                if (!empty($storyIds)) {
                    $placeholders = str_repeat('?,', count($storyIds) - 1) . '?';
                    $stmt = $this->db->prepare("DELETE FROM stories WHERE id IN ($placeholders)");
                    $stmt->execute($storyIds);
                }
            }
            elseif ($action === 'reassign' && $newAuthorId) {
                // Verify new author exists
                $stmt = $this->db->prepare("SELECT id FROM authors WHERE id = ?");
                $stmt->execute([$newAuthorId]);
                if (!$stmt->fetch()) {
                    throw new Exception("New author not found");
                }
                
                // Update story_authors table
                $stmt = $this->db->prepare("UPDATE story_authors SET author_id = ? WHERE author_id = ?");
                $stmt->execute([$newAuthorId, $authorId]);
            }
            else {
                throw new Exception("Invalid action");
            }

            // Finally delete the author
            $stmt = $this->db->prepare("DELETE FROM authors WHERE id = ?");
            $stmt->execute([$authorId]);

            $this->db->commit();
            $this->message = 'Author deleted successfully';
            $this->redirect = 'authors.php';

        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->errors[] = $e->getMessage();
        }
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
                return 'authors/delete';
            default:
                return 'authors/list';
        }
    }
}

// Create and process the page
$page = new AuthorsPage();
$page->process();