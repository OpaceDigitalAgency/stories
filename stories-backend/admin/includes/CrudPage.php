<?php
/**
 * CRUD Page Base Class
 * 
 * This class serves as the base for all CRUD pages, providing
 * common functionality for handling CRUD operations.
 * 
 * @package Stories Admin
 * @version 1.0.0
 */

class CrudPage extends AdminPage {
    /**
     * @var string API endpoint
     */
    protected $endpoint;
    
    /**
     * @var string Entity name (singular)
     */
    protected $entityName;
    
    /**
     * @var string Entity name (plural)
     */
    protected $entityNamePlural;
    
    /**
     * @var array Entity fields
     */
    protected $fields = [];
    
    /**
     * @var array Required fields
     */
    protected $requiredFields = [];
    
    /**
     * @var array Searchable fields
     */
    protected $searchableFields = [];
    
    /**
     * @var array Sortable fields
     */
    protected $sortableFields = [];
    
    /**
     * @var string Default sort field
     */
    protected $defaultSortField = 'id';
    
    /**
     * @var string Default sort direction
     */
    protected $defaultSortDirection = 'desc';
    
    /**
     * @var int Items per page
     */
    protected $itemsPerPage = 10;
    
    /**
     * @var ApiClient API client
     */
    protected $apiClient;
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        
        // Initialize API client
        $this->apiClient = new ApiClient(API_URL, isset($_COOKIE['auth_token']) ? $_COOKIE['auth_token'] : null);
        
        // Get session messages
        $this->getSessionErrors();
        $this->getSessionSuccess();
        
        // Expose slug to all views
        $this->data['slug'] = $this->activeMenu;
    }
    
    /**
     * Get page data
     */
    protected function getData() {
        // Get current action
        $action = $this->getParam('action', 'list');
        
        // Call appropriate method based on action
        switch ($action) {
            case 'create':
                $this->getCreateData();
                break;
            case 'edit':
                $this->getEditData();
                break;
            case 'view':
                $this->getViewData();
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
        
        // Check if this is an AJAX request
        $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                  strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        
        // Call appropriate method based on action
        $result = null;
        switch ($action) {
            case 'create':
                $result = $this->handleCreate();
                break;
            case 'edit':
                $result = $this->handleEdit();
                break;
            case 'delete':
                $result = $this->handleDelete();
                break;
            default:
                // No action needed for list and view
                break;
        }
        
        // If this is an AJAX request, return JSON response
        if ($isAjax) {
            header('Content-Type: application/json');
            
            if (!empty($this->errors)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'errors' => $this->errors
                ]);
            } else {
                echo json_encode([
                    'success' => true,
                    'message' => $this->success,
                    'data' => $result
                ]);
            }
            exit;
        }
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
                return strtolower($this->entityName) . '/create';
            case 'edit':
                return strtolower($this->entityName) . '/edit';
            case 'view':
                return strtolower($this->entityName) . '/view';
            case 'delete':
                return strtolower($this->entityName) . '/delete';
            default:
                return strtolower($this->entityName) . '/list';
        }
    }
    
    /**
     * Get list data
     */
    protected function getListData() {
        // Get pagination parameters
        $page = $this->getParam('page', 1);
        $pageSize = $this->getParam('pageSize', $this->itemsPerPage);
        
        // Get sort parameters
        $sortField = $this->getParam('sort', $this->defaultSortField);
        $sortDirection = $this->getParam('direction', $this->defaultSortDirection);
        
        // Validate sort field
        if (!in_array($sortField, $this->sortableFields)) {
            $sortField = $this->defaultSortField;
        }
        
        // Validate sort direction
        if (!in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = $this->defaultSortDirection;
        }
        
        // Build sort parameter
        $sort = ($sortDirection === 'desc' ? '-' : '') . $sortField;
        
        // Get search parameter
        $search = $this->getParam('search', '');
        
        // Build query parameters
        $params = [
            'page' => $page,
            'pageSize' => $pageSize,
            'sort' => $sort
        ];
        
        // Add search parameter if provided
        if ($search) {
            // Add search to each searchable field
            foreach ($this->searchableFields as $field) {
                $params[$field] = ['like' => $search];
            }
        }
        
        // Get items from API
        $response = $this->apiClient->get($this->endpoint, $params);
        
        if (!$response) {
            // Get API error details
            $error = $this->apiClient->getFormattedError();
            $this->setError('Failed to fetch ' . $this->entityNamePlural . ($error ? ': ' . $error : ''));
            
            // Log detailed error for debugging
            error_log('API list error: ' . json_encode($this->apiClient->getLastError()));
        }
        
        // Set data
        $items = $response && isset($response['data']) ? $response['data'] : [];
        
        // Process each item to ensure consistent data structure
        foreach ($items as &$item) {
            // Ensure attributes array exists
            if ((!isset($item['attributes']) || empty($item['attributes'])) && !empty($item)) {
                // If no attributes array but we have data, create an attributes array
                $item['attributes'] = [];
                
                // Move any non-special fields to attributes
                foreach ($item as $key => $value) {
                    if (!in_array($key, ['id', 'type', 'links', 'meta', 'relationships'])) {
                        $item['attributes'][$key] = $value;
                    }
                }
            }
            
            // Process relation fields
            foreach ($this->fields as $field) {
                if ($field['type'] === 'relation' && !isset($item['attributes'][$field['name']])) {
                    // Check if the relation exists in a different format
                    if (isset($item[$field['name']])) {
                        $item['attributes'][$field['name']] = $item[$field['name']];
                    }
                }
            }
        }
        
        $this->data['items'] = $items;
        $this->data['pagination'] = $response ? ($response['meta']['pagination'] ?? [
            'page' => $page,
            'pageSize' => $pageSize,
            'pageCount' => 0,
            'total' => 0
        ]) : [
            'page' => $page,
            'pageSize' => $pageSize,
            'pageCount' => 0,
            'total' => 0
        ];
        $this->data['sort'] = [
            'field' => $sortField,
            'direction' => $sortDirection
        ];
        $this->data['search'] = $search;
        $this->data['fields'] = $this->fields;
        $this->data['entityName'] = $this->entityName;
        $this->data['entityNamePlural'] = $this->entityNamePlural;
    }
    
    /**
     * Get create data
     */
    protected function getCreateData() {
        $this->data['fields'] = $this->fields;
        $this->data['requiredFields'] = $this->requiredFields;
        $this->data['entityName'] = $this->entityName;
        $this->data['entityNamePlural'] = $this->entityNamePlural;
        $this->data['item'] = [];
        
        // Set default values
        foreach ($this->fields as $field) {
            $this->data['item'][$field['name']] = $field['default'] ?? '';
        }
    }
    
    /**
     * Get edit data
     */
    protected function getEditData() {
        // Get item ID
        $id = $this->getParam('id');
        
        if (!$id) {
            $this->setError('Invalid ID');
            $this->redirect($this->entityNamePlural . '.php');
            return;
        }
        
        // Get item from API
        $response = $this->apiClient->get($this->endpoint . '/' . $id);
        
        if (!$response) {
            // Get API error details
            $error = $this->apiClient->getFormattedError();
            $this->setError('Failed to fetch ' . $this->entityName . ($error ? ': ' . $error : ''));
            $this->redirect($this->activeMenu . '.php');
            return;
        }
        
        // Debug response structure
        error_log("CrudPage::getEditData - Response for {$this->endpoint}/{$id}: " . json_encode($response, JSON_PRETTY_PRINT));
        
        $this->data['fields'] = $this->fields;
        $this->data['requiredFields'] = $this->requiredFields;
        $this->data['entityName'] = $this->entityName;
        $this->data['entityNamePlural'] = $this->entityNamePlural;
        
        // Process the item data to ensure all expected fields are present
        $item = isset($response['data']) ? $response['data'] : [];
        
        // Ensure attributes array exists
        if ((!isset($item['attributes']) || empty($item['attributes'])) && !empty($item)) {
            // If no attributes array but we have data, create an attributes array
            $item['attributes'] = [];
            
            // Move any non-special fields to attributes
            foreach ($item as $key => $value) {
                if (!in_array($key, ['id', 'type', 'links', 'meta', 'relationships'])) {
                    $item['attributes'][$key] = $value;
                }
            }
        }
        
        // Process relation fields
        foreach ($this->fields as $field) {
            if ($field['type'] === 'relation' && !isset($item['attributes'][$field['name']])) {
                // Check if the relation exists in a different format
                if (isset($item[$field['name']])) {
                    $item['attributes'][$field['name']] = $item[$field['name']];
                }
            }
        }
        
        $this->data['item'] = $item;
    }
    
    /**
     * Get view data
     */
    protected function getViewData() {
        // Get item ID
        $id = $this->getParam('id');
        
        if (!$id) {
            $this->setError('Invalid ID');
            $this->redirect($this->entityNamePlural . '.php');
            return;
        }
        
        // Get item from API
        $response = $this->apiClient->get($this->endpoint . '/' . $id);
        
        if (!$response) {
            // Get API error details
            $error = $this->apiClient->getFormattedError();
            $this->setError('Failed to fetch ' . $this->entityName . ($error ? ': ' . $error : ''));
            // Already correct, no change needed
            $this->redirect($this->activeMenu . '.php');
            return;
        }
        
        // Debug response structure
        error_log("CrudPage::getViewData - Response for {$this->endpoint}/{$id}: " . json_encode($response, JSON_PRETTY_PRINT));
        
        $this->data['fields'] = $this->fields;
        $this->data['entityName'] = $this->entityName;
        $this->data['entityNamePlural'] = $this->entityNamePlural;
        
        // Process the item data to ensure all expected fields are present
        $item = isset($response['data']) ? $response['data'] : [];
        
        // Ensure attributes array exists
        if ((!isset($item['attributes']) || empty($item['attributes'])) && !empty($item)) {
            // If no attributes array but we have data, create an attributes array
            $item['attributes'] = [];
            
            // Move any non-special fields to attributes
            foreach ($item as $key => $value) {
                if (!in_array($key, ['id', 'type', 'links', 'meta', 'relationships'])) {
                    $item['attributes'][$key] = $value;
                }
            }
        }
        
        // Process relation fields
        foreach ($this->fields as $field) {
            if ($field['type'] === 'relation' && !isset($item['attributes'][$field['name']])) {
                // Check if the relation exists in a different format
                if (isset($item[$field['name']])) {
                    $item['attributes'][$field['name']] = $item[$field['name']];
                }
            }
        }
        
        $this->data['item'] = $item;
    }
    
    /**
     * Get delete data
     */
    protected function getDeleteData() {
        // Get item ID
        $id = $this->getParam('id');
        
        if (!$id) {
            $this->setError('Invalid ID');
            $this->redirect($this->entityNamePlural . '.php');
            return;
        }
        
        // Get item from API
        $response = $this->apiClient->get($this->endpoint . '/' . $id);
        
        if (!$response) {
            // Get API error details
            $error = $this->apiClient->getFormattedError();
            $this->setError('Failed to fetch ' . $this->entityName . ($error ? ': ' . $error : ''));
            $this->redirect($this->activeMenu . '.php');
            return;
        }
        
        $this->data['entityName'] = $this->entityName;
        $this->data['entityNamePlural'] = $this->entityNamePlural;
        
        // Process the item data to ensure all expected fields are present
        $item = isset($response['data']) ? $response['data'] : [];
        
        // Ensure attributes array exists
        if ((!isset($item['attributes']) || empty($item['attributes'])) && !empty($item)) {
            // If no attributes array but we have data, create an attributes array
            $item['attributes'] = [];
            
            // Move any non-special fields to attributes
            foreach ($item as $key => $value) {
                if (!in_array($key, ['id', 'type', 'links', 'meta', 'relationships'])) {
                    $item['attributes'][$key] = $value;
                }
            }
        }
        
        // Process relation fields
        foreach ($this->fields as $field) {
            if ($field['type'] === 'relation' && !isset($item['attributes'][$field['name']])) {
                // Check if the relation exists in a different format
                if (isset($item[$field['name']])) {
                    $item['attributes'][$field['name']] = $item[$field['name']];
                }
            }
        }
        
        $this->data['item'] = $item;
    }
    
    /**
     * Handle create
     *
     * @return array|null Response data
     */
    protected function handleCreate() {
        // Validate required fields
        if (!Validator::required($_POST, $this->requiredFields)) {
            $this->errors = Validator::getErrors();
            return null;
        }
        
        // Check if we have file uploads
        $hasFileUploads = false;
        foreach ($_FILES as $field => $file) {
            if (!empty($file['name'])) {
                $hasFileUploads = true;
                break;
            }
        }
        
        if ($hasFileUploads) {
            // For file uploads, we need to handle the data differently
            $data = $_POST;
            
            // Add files to the data
            foreach ($_FILES as $field => $file) {
                if (!empty($file['name'])) {
                    $data[$field] = $file;
                }
            }
            
            error_log('Create with file upload: ' . json_encode($data));
        } else {
            // For regular form data, prepare it as usual
            $data = $this->prepareData($_POST);
            error_log('Create with regular data: ' . json_encode($data));
        }
        
        // Create item
        $response = $this->apiClient->post($this->endpoint, $data);
        
        if ($response) {
            $this->setSuccess($this->entityName . ' created successfully');
            
            // Check if this is an AJAX request
            $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                      strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
            
            if (!$isAjax) {
                $this->redirect($this->entityName . '.php');
            }
            
            return $response;
        } else {
            // Get API error details
            $error = $this->apiClient->getFormattedError();
            $this->setError('Failed to create ' . $this->entityName . ($error ? ': ' . $error : ''));
            
            // Log detailed error for debugging
            error_log('API create error: ' . json_encode($this->apiClient->getLastError()));
            
            return null;
        }
    }
    
    /**
     * Handle edit
     *
     * @return array|null Response data
     */
    protected function handleEdit() {
        // Get item ID
        $id = $this->getParam('id');
        
        if (!$id) {
            $this->setError('Invalid ID');
            
            // Check if this is an AJAX request
            $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                      strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
            
            if (!$isAjax) {
                $this->redirect($this->entityName . '.php');
            }
            
            return null;
        }
        
        // Validate required fields
        if (!Validator::required($_POST, $this->requiredFields)) {
            $this->errors = Validator::getErrors();
            return null;
        }
        
        // Check if we have file uploads
        $hasFileUploads = false;
        foreach ($_FILES as $field => $file) {
            if (!empty($file['name'])) {
                $hasFileUploads = true;
                break;
            }
        }
        
        if ($hasFileUploads) {
            // For file uploads, we need to handle the data differently
            $data = $_POST;
            
            // Add files to the data
            foreach ($_FILES as $field => $file) {
                if (!empty($file['name'])) {
                    $data[$field] = $file;
                }
            }
            
            error_log('Edit with file upload: ' . json_encode($data));
        } else {
            // For regular form data, prepare it as usual
            $data = $this->prepareData($_POST);
            error_log('Edit with regular data: ' . json_encode($data));
        }
        
        // Update item
        $response = $this->apiClient->put($this->endpoint . '/' . $id, $data);
        
        if ($response) {
            $this->setSuccess($this->entityName . ' updated successfully');
            
            // Check if this is an AJAX request
            $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                      strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
            
            if (!$isAjax) {
                $this->redirect($this->entityName . '.php');
            }
            
            return $response;
        } else {
            // Get API error details
            $error = $this->apiClient->getFormattedError();
            $this->setError('Failed to update ' . $this->entityName . ($error ? ': ' . $error : ''));
            
            // Log detailed error for debugging
            error_log('API update error: ' . json_encode($this->apiClient->getLastError()));
            
            return null;
        }
    }
    
    /**
     * Handle delete
     *
     * @return array|null Response data
     */
    protected function handleDelete() {
        // Get item ID
        $id = $this->getParam('id');
        
        if (!$id) {
            $this->setError('Invalid ID');
            
            // Check if this is an AJAX request
            $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                      strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
            
            if (!$isAjax) {
                $this->redirect($this->entityName . '.php');
            }
            
            return null;
        }
        
        // Log the delete request
        error_log('Deleting ' . $this->entityName . ' with ID: ' . $id);
        
        // Delete item
        $response = $this->apiClient->delete($this->endpoint . '/' . $id);
        
        if ($response) {
            $this->setSuccess($this->entityName . ' deleted successfully');
            error_log('Delete successful for ' . $this->entityName . ' with ID: ' . $id);
        } else {
            // Get API error details
            $error = $this->apiClient->getFormattedError();
            $errorMessage = 'Failed to delete ' . $this->entityName . ($error ? ': ' . $error : '');
            $this->setError($errorMessage);
            
            // Log detailed error for debugging
            error_log('API delete error: ' . json_encode($this->apiClient->getLastError()));
            error_log('Delete failed for ' . $this->entityName . ' with ID: ' . $id . ' - ' . $errorMessage);
        }
        
        // Check if this is an AJAX request
        $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                  strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        
        if (!$isAjax) {
            $this->redirect($this->entityName . '.php');
        }
        
        return $response;
    }
    
    /**
     * Prepare data for API request
     * 
     * @param array $data Form data
     * @return array Prepared data
     */
    protected function prepareData($data) {
        $prepared = [];
        
        // Process each field
        foreach ($this->fields as $field) {
            $name = $field['name'];
            
            // Skip if field is not in the form data
            if (!isset($data[$name])) {
                continue;
            }
            
            // Get field value
            $value = $data[$name];
            
            // Process based on field type
            switch ($field['type']) {
                case 'boolean':
                    $prepared[$name] = (bool)$value;
                    break;
                case 'number':
                    $prepared[$name] = (float)$value;
                    break;
                case 'integer':
                    $prepared[$name] = (int)$value;
                    break;
                case 'date':
                case 'datetime':
                    $prepared[$name] = $value;
                    break;
                case 'array':
                    $prepared[$name] = is_array($value) ? $value : explode(',', $value);
                    break;
                default:
                    $prepared[$name] = $value;
                    break;
            }
        }
        
        return $prepared;
    }
}