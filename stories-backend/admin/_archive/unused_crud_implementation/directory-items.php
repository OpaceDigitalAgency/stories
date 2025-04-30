<?php
/**
 * Directory Items Admin Page
 * 
 * @package Stories Admin
 * @version 1.0.0
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/ApiClient.php';
require_once __DIR__ . '/includes/Validator.php';
require_once __DIR__ . '/includes/FileUpload.php';
require_once __DIR__ . '/includes/AdminPage.php';
require_once __DIR__ . '/includes/CrudPage.php';

class DirectoryItemsPage extends CrudPage {
    public function __construct() {
        parent::__construct();
        
        $this->pageTitle = 'Directory Items';
        $this->activeMenu = 'directory-items';
        $this->entityName = 'Directory Item';
        $this->entityNamePlural = 'Directory Items';
        $this->endpoint = 'directory-items';
        
        $this->fields = [
            [
                'name' => 'name',
                'label' => 'Name',
                'type' => 'text',
                'main' => true,
                'list' => true,
                'form' => true,
                'view' => true,
                'default' => '',
                'api_field' => 'name'
            ],
            [
                'name' => 'description',
                'label' => 'Description',
                'type' => 'textarea',
                'list' => false,
                'form' => true,
                'view' => true,
                'default' => '',
                'api_field' => 'description'
            ],
            [
                'name' => 'url',
                'label' => 'URL',
                'type' => 'text',
                'list' => true,
                'form' => true,
                'view' => true,
                'default' => '',
                'help' => 'Full URL including https://',
                'api_field' => 'url'
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
                    ['value' => 'Category1', 'label' => 'Category1'],
                    ['value' => 'Category2', 'label' => 'Category2']
                ],
                'api_field' => 'category'
            ],
            [
                'name' => 'logo',
                'label' => 'Logo',
                'type' => 'image',
                'list' => false,
                'form' => true,
                'view' => true,
                'default' => null,
                'api_field' => 'logo'
            ],
            [
                'name' => 'rating',
                'label' => 'Rating',
                'type' => 'number',
                'list' => true,
                'form' => true,
                'view' => true,
                'default' => 0,
                'api_field' => 'rating'
            ],
            [
                'name' => 'priceRange',
                'label' => 'Price Range',
                'type' => 'text',
                'list' => true,
                'form' => true,
                'view' => true,
                'default' => '',
                'api_field' => 'priceRange'
            ],
            [
                'name' => 'isPublished',
                'label' => 'Published',
                'type' => 'boolean',
                'list' => true,
                'form' => true,
                'view' => true,
                'default' => true,
                'checkboxLabel' => 'Published',
                'api_field' => 'isPublished'
            ]
        ];
        
        $this->requiredFields = ['name', 'url', 'category'];
        $this->searchableFields = ['name', 'description', 'url'];
        $this->sortableFields = ['id', 'name', 'category'];
        $this->defaultSortField = 'name';
        $this->defaultSortDirection = 'asc';
    }

    protected function getContentTemplate() {
        $action = $this->getParam('action', 'list');
        switch ($action) {
            case 'create':
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

$page = new DirectoryItemsPage();
$page->process();

