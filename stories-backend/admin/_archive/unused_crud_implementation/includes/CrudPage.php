<?php
/**
 * CrudPage  –  base class used by every admin list / create / edit page
 *
 * • builds the API query (page / pageSize / sort / search)
 * • retries with simpler query if the endpoint chokes
 * • accepts either Strapi-style  {"data":[…],"meta":{…}}
 *   or a flat array-style        [ … ]
 * • maps API-field names → admin-field names
 *
 *  @package Stories Admin
 */

class CrudPage extends AdminPage
{
    /* ----------------------------------------------------------------
       configurable per sub-class
       ---------------------------------------------------------------- */
    protected string $endpoint           = '';
    protected string $entityName         = '';
    protected string $entityNamePlural   = '';
    protected array  $fields             = [];
    protected array  $requiredFields     = [];
    protected array  $searchableFields   = [];
    protected array  $sortableFields     = [];
    protected string $defaultSortField   = 'id';
    protected string $defaultSortDirection = 'desc';
    protected int    $itemsPerPage       = 25;

    /* ---------------------------------------------------------------- */
    protected ApiClient $apiClient;

    /* ----------------------------------------------------------------
       constructor
       ---------------------------------------------------------------- */
    public function __construct ()
    {
        parent::__construct();

        /* grab the JWT (session → cookie → null) */
        $token = $_SESSION['token'] ?? $_COOKIE['auth_token'] ?? null;
        if ($token) $_SESSION['token'] = $token;   // keep it alive

        $this->apiClient = new ApiClient(API_URL, $token);

        /* surface flash messages to the view layer */
        $this->getSessionErrors();
        $this->getSessionSuccess();
        $this->data['slug'] = $this->activeMenu;
    }

    /* ================================================================
       LIST VIEW   (the bit that broke on flat arrays)
       ================================================================ */
    protected function getListData (): void
    {
        


            // — Temporary DEBUG OUTPUT — 
    // (will print at top of your admin list page)
    $debugParams = json_encode($params ?? []);
    echo '<pre style="background:#fee;border:1px solid #f00;padding:10px;">';
    echo "CRUDPAGE DEBUG — endpoint={$this->endpoint}\n";
    echo "Params: $debugParams\n\n";

    $response = $this->apiClient->get($this->endpoint, $params);

    if ($response === false) {
        // Show the error message from ApiClient
        $fmtErr = $this->apiClient->getFormattedError();
        echo "Response Error: $fmtErr\n";
        echo '</pre>';
        // Stop here so you can see it
        exit;
    }

    // If OK, dump the raw response and then halt
    echo "Raw Response:\n" . htmlspecialchars(json_encode($response, JSON_PRETTY_PRINT)) . "\n";
    echo '</pre>';
    exit;




        /* ---------- build query string ---------- */
        $page          = max(1,  (int) $this->getParam('page',     1));
        $pageSize      = max(1,  (int) $this->getParam('pageSize', $this->itemsPerPage));
        $sortField     = $this->getParam('sort',      $this->defaultSortField);
        $sortDirection = $this->getParam('direction', $this->defaultSortDirection);
        $search        = $this->getParam('search',    '');

        if (!in_array($sortField, $this->sortableFields))        $sortField     = $this->defaultSortField;
        if (!in_array(strtolower($sortDirection), ['asc','desc'])) $sortDirection = $this->defaultSortDirection;

        $params = [
            'page'     => $page,
            'pageSize' => $pageSize,
            'sort'     => ($sortDirection === 'desc' ? '-' : '') . $sortField,
        ];
        if ($search) foreach ($this->searchableFields as $f)
            $params[$f] = ['like' => $search];

        /* ---------- primary request ---------- */
        $response = $this->apiClient->get($this->endpoint, $params);

        /* ---------- fallback (retry w/out page/pageSize/sort) ---------- */
        if ($response === false) {
            error_log("CrudPage fallback → retrying {$this->endpoint} with minimal params");
            // always retry without any query params (flat response)
            $response = $this->apiClient->get($this->endpoint, []);
        }
        if ($response === false) {                 // still dead
            $this->setError(
                'Failed to fetch ' . $this->entityNamePlural .
                ($this->apiClient->getFormattedError() ? ': ' . $this->apiClient->getFormattedError() : '')
            );
            return;
        }

        /* ---------- accept both response shapes ---------- */
        $items = isset($response['data']) ? $response['data'] : $response;

        /* ---------- synthesize a pagination block if absent ---------- */
        $pagination = $response['meta']['pagination'] ?? [
            'page'      => 1,
            'pageSize'  => count($items),
            'pageCount' => 1,
            'total'     => count($items),
        ];

        /* ---------- normalise to $item['attributes'] ---------- */
        foreach ($items as &$it) {

            /* flat → attributes wrapper */
            if (!isset($it['attributes'])) {
                $attr = $it; unset($attr['id']);
                $it = ['id' => $it['id'] ?? null, 'attributes' => $attr];
            }

            /* api_field → admin field mapping */
            foreach ($this->fields as $f) {
                if (empty($f['api_field'])) continue;
                $api = $f['api_field']; $adm = $f['name'];
                if     (isset($it[$api]))               $it['attributes'][$adm] = $it[$api];
                elseif (isset($it['attributes'][$api])) $it['attributes'][$adm] = $it['attributes'][$api];
            }
        }

        /* ---------- expose to the view ---------- */
        $this->data += [
            'items'            => $items,
            'pagination'       => $pagination,
            'sort'             => ['field'=>$sortField, 'direction'=>$sortDirection],
            'search'           => $search,
            'fields'           => $this->fields,
            'entityName'       => $this->entityName,
            'entityNamePlural' => $this->entityNamePlural,
        ];
    }

    /* ================================================================
       master dispatcher
       ================================================================ */
    protected function getData (): void
    {
        match ($this->getParam('action','list')) {
            'create' => $this->getCreateData(),
            'edit'   => $this->getEditData(),
            'view'   => $this->getViewData(),
            'delete' => $this->getDeleteData(),
            default  => $this->getListData(),
        };
    }

    /* ================================================================
       create / edit / view / delete  (identical to the old version)
       ================================================================ */
    protected function getCreateData(): void
    {
        $this->data['fields']         = $this->fields;
        $this->data['requiredFields'] = $this->requiredFields;
        $this->data['item']           = array_column($this->fields,'default','name');
    }
    protected function getEditData()  { $this->fetchSingle('edit');  }
    protected function getViewData()  { $this->fetchSingle('view');  }
    protected function getDeleteData(){ $this->fetchSingle('delete');}

    private function fetchSingle(string $mode): void
    {
        $id = $this->getParam('id');
        if (!$id) { $this->setError('Invalid ID'); return; }

        $resp = $this->apiClient->get("{$this->endpoint}/$id");
        if ($resp === false) {
            $this->setError('Failed to fetch '.$this->entityName.': '.$this->apiClient->getFormattedError());
            return;
        }
        $this->data['fields'] = $this->fields;
        $this->data['item']   = $resp['data'] ?? $resp;
    }

    /* ================================================================
       helpers
       ================================================================ */
    protected function prepareData(array $post): array
    {
        $attr = [];
        foreach ($this->fields as $f)
            if (isset($post[$f['name']])) $attr[$f['name']] = $post[$f['name']];
        return ['data'=>['attributes'=>$attr]];
    }
}
