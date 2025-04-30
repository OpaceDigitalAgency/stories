<?php
/**
 * AI Tools Admin Page
 *
 * @package Stories Admin
 * @version 1.0.0
 */

// 1) Include the common admin framework
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/ApiClient.php';
require_once __DIR__ . '/../includes/Validator.php';
require_once __DIR__ . '/../includes/FileUpload.php';
require_once __DIR__ . '/../includes/AdminPage.php';
require_once __DIR__ . '/../includes/CrudPage.php';

/**
 * AI Tools Page Class
 */
class AiToolsPage extends CrudPage {
    public function __construct() {
        parent::__construct();

        // Menu & Titles
        $this->pageTitle        = 'AI Tools';
        $this->activeMenu       = 'ai-tools';
        $this->entityName       = 'AI Tool';
        $this->entityNamePlural = 'AI Tools';
        $this->endpoint         = 'ai-tools';

        // 2) Define the fields and their API mappings
        $this->fields = [
            [
                'name'      => 'title',
                'label'     => 'Name',
                'type'      => 'text',
                'main'      => true,
                'list'      => true,
                'form'      => true,
                'view'      => true,
                'default'   => '',
                'api_field'=> 'title',
            ],
            [
                'name'      => 'description',
                'label'     => 'Description',
                'type'      => 'textarea',
                'list'      => false,
                'form'      => true,
                'view'      => true,
                'default'   => '',
                'api_field'=> 'description',
            ],
            [
                'name'      => 'website_url',
                'label'     => 'URL',
                'type'      => 'text',
                'help'      => 'Full URL including https://',
                'list'      => true,
                'form'      => true,
                'view'      => true,
                'default'   => '',
                'api_field'=> 'website_url',
            ],
            [
                'name'      => 'category',
                'label'     => 'Category',
                'type'      => 'select',
                'list'      => true,
                'form'      => true,
                'view'      => true,
                'default'   => '',
                'options'   => [
                    ['value'=>'Writing','label'=>'Writing'],
                    ['value'=>'Editing','label'=>'Editing'],
                    ['value'=>'Illustration','label'=>'Illustration'],
                    ['value'=>'Translation','label'=>'Translation'],
                    ['value'=>'Summarization','label'=>'Summarization'],
                    ['value'=>'Learning','label'=>'Learning'],
                    ['value'=>'Other','label'=>'Other'],
                ],
                'api_field'=> 'category',
            ],
            [
                'name'      => 'pricing_type',
                'label'     => 'Pricing Type',
                'type'      => 'select',
                'list'      => true,
                'form'      => true,
                'view'      => true,
                'default'   => 'free',
                'options'   => [
                    ['value'=>'free','label'=>'Free'],
                    ['value'=>'freemium','label'=>'Freemium'],
                    ['value'=>'paid','label'=>'Paid'],
                ],
                'api_field'=> 'pricing_type',
            ],
            [
                'name'      => 'cover_url',
                'label'     => 'Logo',
                'type'      => 'image',
                'list'      => false,
                'form'      => true,
                'view'      => true,
                'default'   => null,
                'api_field'=> 'cover_url',
            ],
            [
                'name'      => 'featured',
                'label'     => 'Featured',
                'type'      => 'boolean',
                'list'      => true,
                'form'      => true,
                'view'      => true,
                'default'   => false,
                'checkboxLabel'=> 'Featured tool',
                'api_field'=> 'featured',
            ],
            [
                'name'      => 'is_published',
                'label'     => 'Published',
                'type'      => 'boolean',
                'list'      => true,
                'form'      => true,
                'view'      => true,
                'default'   => true,
                'checkboxLabel'=> 'Published',
                'api_field'=> 'is_published',
            ],
            [
                'name'      => 'rating',
                'label'     => 'Rating',
                'type'      => 'number',
                'list'      => true,
                'form'      => true,
                'view'      => true,
                'default'   => 0,
                'api_field'=> 'rating',
            ],
        ];

        // 3) Who’s required, searchable & sortable?
        $this->requiredFields   = ['title','website_url','category'];
        $this->searchableFields = ['title','description','website_url'];
        $this->sortableFields   = ['id','title','category','pricing_type'];
        $this->defaultSortField = 'title';
        $this->defaultSortDirection = 'asc';
    }

    /**
     * File uploads (if you allow image/logo uploads)
     * Uncomment & adjust if you need to handle `$_FILES['cover_url']`
     */
/*
    protected function handleCreate() {
        if (isset($_FILES['cover_url']) && $_FILES['cover_url']['error']===UPLOAD_ERR_OK) {
            $u = new FileUpload($this->config['media']);
            $file = $u->upload($_FILES['cover_url'],'ai_tool',0,'cover_url');
            if ($file) {
                $_POST['cover_url'] = $file;
            } else {
                $this->errors = array_merge($this->errors,$u->getErrors());
                return;
            }
        }
        parent::handleCreate();
    }

    protected function handleEdit() {
        $id = $this->getParam('id');
        if (isset($_FILES['cover_url']) && $_FILES['cover_url']['error']===UPLOAD_ERR_OK) {
            $u = new FileUpload($this->config['media']);
            $file = $u->upload($_FILES['cover_url'],'ai_tool',$id,'cover_url');
            if ($file) {
                $_POST['cover_url'] = $file;
            } else {
                $this->errors = array_merge($this->errors,$u->getErrors());
                return;
            }
        }
        parent::handleEdit();
    }
*/

}  // end class

// 4) Instantiate and run
$page = new AiToolsPage();
$page->process();
