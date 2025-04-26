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

    protected function getListData() {
        $page = $this->getParam('page', 1);
        $pageSize = $this->getParam('pageSize', $this->itemsPerPage);
        $sortField = $this->getParam('sort', $this->defaultSortField);
        $sortDirection = $this->getParam('direction', $this->defaultSortDirection);

        if (!in_array($sortField, $this->sortableFields)) $sortField = $this->defaultSortField;
        if (!in_array($sortDirection, ['asc', 'desc'])) $sortDirection = $this->defaultSortDirection;

        $params = ['page' => $page, 'pageSize' => $pageSize, 'sort' => ($sortDirection === 'desc' ? '-' : '') . $sortField];
        if ($search = $this->getParam('search', '')) {
            foreach ($this->searchableFields as $field) {
                $params[$field] = ['like' => $search];
            }
        }

        $response = $this->apiClient->get($this->endpoint, $params);

        if (!$response) {
            $this->setError('Failed to fetch ' . $this->entityNamePlural);
            return;
        }

        $items = [];

        if (isset($response['data'])) {
            $items = $response['data'];
        } elseif (is_array($response)) {
            $items = $response;
        }

        foreach ($items as &$item) {
            if (isset($item['attributes']['attributes'])) {
                $item['attributes'] = $item['attributes']['attributes'];
            } elseif (isset($item['attributes'])) {
                $item['attributes'] = $item['attributes'];
            } else {
                // If flat structure, wrap fields inside 'attributes'
                $attributes = [];
                foreach ($item as $k => $v) {
                    if (!in_array($k, ['id', 'type', 'links', 'meta', 'relationships'])) {
                        $attributes[$k] = $v;
                    }
                }
                $item['attributes'] = $attributes;
            }
        }

        $this->data['items'] = $items;
        $this->data['pagination'] = $response['meta']['pagination'] ?? [
            'page' => $page,
            'pageSize' => $pageSize,
            'pageCount' => 1,
            'total' => count($items)
        ];
        $this->data['sort'] = ['field' => $sortField, 'direction' => $sortDirection];
        $this->data['search'] = $search;
        $this->data['fields'] = $this->fields;
        $this->data['entityName'] = $this->entityName;
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