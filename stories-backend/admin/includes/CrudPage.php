<?php
/**
 * CRUD Page Base Class
 * 
 * @package Stories Admin
 * @version 1.0.0
 */

class CrudPage extends AdminPage {
    protected $endpoint;
    protected $entityName;
    protected $entityNamePlural;
    protected $fields = [];
    protected $requiredFields = [];
    protected $searchableFields = [];
    protected $sortableFields = [];
    protected $defaultSortField = 'id';
    protected $defaultSortDirection = 'desc';
    protected $itemsPerPage = 10;
    protected $apiClient;

    public function __construct() {
        parent::__construct();

        $token = $_SESSION['token'] ?? $_COOKIE['auth_token'] ?? null;
        if ($token) {
            $_SESSION['token'] = $token;
        }

        $this->apiClient = new ApiClient(API_URL, $token);

        $this->getSessionErrors();
        $this->getSessionSuccess();
        $this->data['slug'] = $this->activeMenu;
    }

    protected function getData() {
        $action = $this->getParam('action', 'list');
        switch ($action) {
            case 'create': $this->getCreateData(); break;
            case 'edit': $this->getEditData(); break;
            case 'view': $this->getViewData(); break;
            case 'delete': $this->getDeleteData(); break;
            default: $this->getListData(); break;
        }
    }

        /**
     * Get list data
     */
    protected function getListData()
    {
        // ---------- build query ----------
        $page          = $this->getParam('page', 1);
        $pageSize      = $this->getParam('pageSize', $this->itemsPerPage);
        $sortField     = $this->getParam('sort', $this->defaultSortField);
        $sortDirection = $this->getParam('direction', $this->defaultSortDirection);
        $search        = $this->getParam('search', '');

        if (!in_array($sortField, $this->sortableFields))    $sortField     = $this->defaultSortField;
        if (!in_array($sortDirection, ['asc', 'desc']))      $sortDirection = $this->defaultSortDirection;

        $params = [
            'page'     => $page,
            'pageSize' => $pageSize,
            'sort'     => ($sortDirection === 'desc' ? '-' : '') . $sortField,
        ];
        if ($search) {
            foreach ($this->searchableFields as $field) {
                $params[$field] = ['like' => $search];
            }
        }

        // ---------- first attempt ----------
        error_log("CrudPage::getListData – primary request to {$this->endpoint} with " . json_encode($params));
        $response = $this->apiClient->get($this->endpoint, $params);

        // ---------- fallback (no page / pageSize / sort) ----------
        if ($response === false) {
            error_log("CrudPage::getListData – primary request failed, retrying without pagination/sort");
            $response = $this->apiClient->get($this->endpoint, $search ? [$search] : []);
        }

        // ---------- bail out if still no data ----------
        if ($response === false) {
            $error = $this->apiClient->getFormattedError();
            $this->setError('Failed to fetch ' . $this->entityNamePlural . ($error ? ': ' . $error : ''));
            return;
        }

        /* ---------- normal processing from here down ---------- */
        // Accept either Strapi-style {"data":[…],"meta":{…}} or flat [ … ]
        $items = isset($response['data']) ? $response['data'] : $response;

        // synthesize minimal pagination if API didn’t provide one
        $pagination = $response['meta']['pagination'] ?? [
            'page'      => 1,
            'pageSize'  => count($items),
            'pageCount' => 1,
            'total'     => count($items),
        ];

        // map attributes so the templates can use a consistent structure
        foreach ($items as &$item) {
            if (!isset($item['attributes'])) {
                $attr = $item;
                unset($attr['id']);
                $item = ['id' => $item['id'] ?? null, 'attributes' => $attr];
            }
            foreach ($this->fields as $f) {
                if (!isset($f['api_field'])) continue;
                $api   = $f['api_field'];
                $admin = $f['name'];
                if (isset($item[$api]))                       $item['attributes'][$admin] = $item[$api];
                elseif (isset($item['attributes'][$api]))    $item['attributes'][$admin] = $item['attributes'][$api];
            }
        }

        // push to the view
        $this->data['items']        = $items;
        $this->data['pagination']   = $pagination;
        $this->data['sort']         = ['field' => $sortField, 'direction' => $sortDirection];
        $this->data['search']       = $search;
        $this->data['fields']       = $this->fields;
        $this->data['entityName']   = $this->entityName;
        $this->data['entityNamePlural'] = $this->entityNamePlural;
    }


    protected function getCreateData() {
        $this->data['fields'] = $this->fields;
        $this->data['requiredFields'] = $this->requiredFields;
        $this->data['item'] = array_column($this->fields, 'default', 'name');
    }

    protected function getEditData() {
        $id = $this->getParam('id');
        if (!$id) { $this->setError('Invalid ID'); return; }
        $response = $this->apiClient->get("{$this->endpoint}/$id");
        $item = $response['data'] ?? $response;
        $this->data['item'] = $item;
    }

    protected function getViewData() { $this->getEditData(); }
    protected function getDeleteData() { $this->getEditData(); }

    protected function prepareData($data) {
        $attributes = [];
        foreach ($this->fields as $field) {
            if (isset($data[$field['name']])) {
                $attributes[$field['name']] = $data[$field['name']];
            }
        }
        return ['data' => ['attributes' => $attributes]];
    }
}