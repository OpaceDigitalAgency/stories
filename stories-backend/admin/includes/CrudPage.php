<?php
/**
 * CRUD Page – base class used by every admin list/create/edit page
 *
 * Handles:
 *   • building the API query (page / pageSize / sort / search)
 *   • retrying with simpler queries if the endpoint chokes
 *   • accepting *either* Strapi-style  {"data":[…],"meta":{…}}
 *             or   flat-array-style  [ … ]
 *   • mapping API field-names → admin field-names
 */

class CrudPage extends AdminPage
{
    /* ------------- configurable per-sub-class ------------- */
    protected string $endpoint              = '';
    protected string $entityName            = '';
    protected string $entityNamePlural      = '';
    protected array  $fields                = [];
    protected array  $requiredFields        = [];
    protected array  $searchableFields      = [];
    protected array  $sortableFields        = [];
    protected string $defaultSortField      = 'id';
    protected string $defaultSortDirection  = 'desc';
    protected int    $itemsPerPage          = 10;
    /* ------------------------------------------------------ */

    protected ApiClient $apiClient;

    /* ------------------------------------------------------ */
    /* constructor                                            */
    /* ------------------------------------------------------ */
    public function __construct()
    {
        parent::__construct();

        /** grab the JWT (session -> cookie -> null) */
        $token = $_SESSION['token'] ?? $_COOKIE['auth_token'] ?? null;
        if ($token) {
            $_SESSION['token'] = $token;        // keep it alive
        }

        $this->apiClient = new ApiClient(API_URL, $token);

        /* surface flash messages to the view layer */
        $this->getSessionErrors();
        $this->getSessionSuccess();
        $this->data['slug'] = $this->activeMenu;
    }

    /* ------------------------------------------------------ */
    /* master dispatcher                                      */
    /* ------------------------------------------------------ */
    protected function getData(): void
    {
        $action = $this->getParam('action', 'list');
        match ($action) {
            'create' => $this->getCreateData(),
            'edit'   => $this->getEditData(),
            'view'   => $this->getViewData(),
            'delete' => $this->getDeleteData(),
            default  => $this->getListData(),
        };
    }

    /* ------------------------------------------------------ */
    /* LIST – with 3-level fallback for picky endpoints       */
    /* ------------------------------------------------------ */
    protected function getListData(): void
    {
        /* ---------- build the “full” Strapi-style query ---- */
        $page          = $this->getParam('page', 1);
        $pageSize      = $this->getParam('pageSize', $this->itemsPerPage);
        $sortField     = $this->getParam('sort', $this->defaultSortField);
        $sortDirection = $this->getParam('direction', $this->defaultSortDirection);
        $search        = $this->getParam('search', '');

        // sanitise
        if (!in_array($sortField, $this->sortableFields))   $sortField     = $this->defaultSortField;
        if (!in_array($sortDirection, ['asc', 'desc']))     $sortDirection = $this->defaultSortDirection;

        $fullQuery = [
            'page'     => $page,
            'pageSize' => $pageSize,
            'sort'     => ($sortDirection === 'desc' ? '-' : '') . $sortField,
        ];
        if ($search !== '') {
            foreach ($this->searchableFields as $f) {
                $fullQuery[$f] = ['like' => $search];
            }
        }

        /* ---------- 1st attempt – full Strapi query -------- */
        error_log("CrudPage::getListData – query (1) " . json_encode($fullQuery));
        $response = $this->apiClient->get($this->endpoint, $fullQuery);

        /* ---------- 2nd attempt – drop sort/​page/​pageSize-- */
        if ($response === false) {
            $simpler = $search ? [$this->searchableFields[0] => ['like' => $search]] : [];
            error_log("CrudPage::getListData – query (2) " . json_encode($simpler));
            $response = $this->apiClient->get($this->endpoint, $simpler);
        }

        /* ---------- 3rd attempt – literally ‘/endpoint’ ---- */
        if ($response === false) {
            error_log("CrudPage::getListData – query (3) – no params");
            $response = $this->apiClient->get($this->endpoint, []);
        }

        /* ---------- still no joy → give up ----------------- */
        if ($response === false) {
            $this->setError(
                'Failed to fetch ' . $this->entityNamePlural .
                ': ' . $this->apiClient->getFormattedError()
            );
            return;
        }

        /* ---------- clear prior errors & continue ---------- */
        $this->errors = [];   // prevent previous banner showing

        /* ---------- normalise response --------------------- */
        $items = isset($response['data']) ? $response['data'] : $response;

        $pagination = $response['meta']['pagination'] ?? [
            'page'      => 1,
            'pageSize'  => count($items),
            'pageCount' => 1,
            'total'     => count($items),
        ];

        foreach ($items as &$item) {
            /* wrap flat → attributes */
            if (!isset($item['attributes'])) {
                $attr = $item;          // copy all keys
                unset($attr['id']);     // …except id
                $item = [
                    'id'         => $item['id'] ?? null,
                    'attributes' => $attr,
                ];
            }

            /* field-name mapping */
            foreach ($this->fields as $f) {
                if (empty($f['api_field'])) continue;
                $api   = $f['api_field'];
                $admin = $f['name'];

                if (isset($item['attributes'][$api])) {
                    $item['attributes'][$admin] = $item['attributes'][$api];
                }
            }
        }

        /* ---------- hand data to the Twig/​PHP template ----- */
        $this->data += [
            'items'              => $items,
            'pagination'         => $pagination,
            'sort'               => ['field' => $sortField, 'direction' => $sortDirection],
            'search'             => $search,
            'fields'             => $this->fields,
            'entityName'         => $this->entityName,
            'entityNamePlural'   => $this->entityNamePlural,
        ];
    }

    /* (the create / edit / view / delete helpers are unchanged) */
    /* …                                                          */
    /* keep the simple prepareData helper as you already had it   */
    /* --------------------------------------------------------- */

    /* Only *prepareData* shown because that’s the one pages call */
    protected function prepareData(array $post): array
    {
        $attributes = [];
        foreach ($this->fields as $f) {
            if (isset($post[$f['name']])) {
                $attributes[$f['name']] = $post[$f['name']];
            }
        }
        return ['data' => ['attributes' => $attributes]];
    }
}
