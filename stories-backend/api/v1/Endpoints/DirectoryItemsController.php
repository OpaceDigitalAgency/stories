<?php
namespace StoriesAPI\Endpoints;

use StoriesAPI\Core\BaseController;
use StoriesAPI\Utils\Response;
use StoriesAPI\Utils\Validator;

class DirectoryItemsController extends BaseController {
    public function index() {
        try {
            // Get pagination parameters
            $pagination = $this->getPaginationParams();
            $page = $pagination['page'];
            $pageSize = $pagination['pageSize'];
            $offset = ($page - 1) * $pageSize;
            
            // Get sort parameters
            $allowedSortFields = ['title', 'created_at'];
            $sort = parent::getSortParams($allowedSortFields);
            $sortField = $sort['field'] ?? 'created_at';
            
            // Add table alias to sort field
            $sortField = 'd.' . $sortField;
            
            $sortDirection = $sort['direction'] ?? 'DESC';
            $sortClause = "ORDER BY $sortField $sortDirection";
            
            // Get filter parameters
            $allowedFilterFields = ['title', 'slug', 'category'];
            $filters = $this->getFilterParams($allowedFilterFields);
            
            // Build WHERE clause
            $where = [];
            $params = [];
            
            foreach ($filters as $field => $value) {
                if ($value === '') {
                    continue;
                }
                $where[] = "d.$field LIKE ?";
                $params[] = "%$value%";
            }
            
            $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
            
            // Count total records
            $countQuery = "SELECT COUNT(*) as total FROM directory_items d $whereClause";
            $stmt = $this->db->query($countQuery, $params);
            $total = $stmt->fetch()['total'];
            
            // Get directory items with pagination
            $query = "SELECT
                d.id, d.title, d.description, d.slug, d.url,
                d.category, d.rating, d.price_range,
                d.created_at AS createdAt, d.updated_at AS updatedAt
                FROM directory_items d
                $whereClause
                $sortClause
                LIMIT $offset, $pageSize";
            
            $stmt = $this->db->query($query, $params);
            $items = $stmt->fetchAll();
            
            // Format directory items
            $formattedItems = [];
            foreach ($items as $item) {
                $formattedItems[] = [
                    'id' => $item['id'],
                    'attributes' => [
                        'title' => $item['title'],
                        'description' => $item['description'],
                        'slug' => $item['slug'],
                        'url' => $item['url'],
                        'category' => $item['category'],
                        'rating' => (float)$item['rating'],
                        'priceRange' => $item['price_range'],
                        'createdAt' => $item['createdAt'],
                        'updatedAt' => $item['updatedAt']
                    ]
                ];
            }
            
            Response::sendPaginated($formattedItems, $page, $pageSize, $total);
        } catch (\Exception $e) {
            $this->serverError('Failed to fetch directory items: ' . $e->getMessage());
        }
    }
    
    public function show() {
        $identifier = $this->params['id'] ?? $this->params['slug'] ?? null;
        if (!$identifier) {
            Response::sendError('No identifier provided', 400);
            return;
        }
        
        try {
            // Determine if this is an ID or slug
            if (ctype_digit($identifier)) {
                $column = 'id';
                $value = (int)$identifier;
            } else {
                $column = 'slug';
                $value = Validator::sanitizeString($identifier);
            }
            
            $query = "SELECT
                id, title, description, slug, url,
                category, rating, price_range,
                created_at AS createdAt, updated_at AS updatedAt
                FROM directory_items
                WHERE $column = ?
                LIMIT 1";
            
            $stmt = $this->db->query($query, [$value]);
            $item = $stmt->fetch();
            
            if (!$item) {
                Response::sendError('Directory item not found', 404);
                return;
            }
            
            $formattedItem = [
                'id' => $item['id'],
                'attributes' => [
                    'title' => $item['title'],
                    'description' => $item['description'],
                    'slug' => $item['slug'],
                    'url' => $item['url'],
                    'category' => $item['category'],
                    'rating' => (float)$item['rating'],
                    'priceRange' => $item['price_range'],
                    'createdAt' => $item['createdAt'],
                    'updatedAt' => $item['updatedAt']
                ]
            ];
            
            Response::sendSuccess($formattedItem);
        } catch (\Exception $e) {
            $this->serverError('Failed to fetch directory item: ' . $e->getMessage());
        }
    }
    
    public function create() {
        if (!Validator::required($this->request, ['title', 'url'])) {
            $this->badRequest('Title and URL are required');
            return;
        }
        
        try {
            $title = Validator::sanitizeString($this->request['title']);
            $description = $this->request['description'] ?? '';
            $slug = isset($this->request['slug']) ? Validator::sanitizeString($this->request['slug']) : $this->generateSlug($title);
            $url = $this->request['url'];
            $category = $this->request['category'] ?? '';
            $rating = isset($this->request['rating']) ? (float)$this->request['rating'] : 0;
            $priceRange = $this->request['price_range'] ?? '';
            
            // Check if slug exists
            $stmt = $this->db->query("SELECT id FROM directory_items WHERE slug = ?", [$slug]);
            if ($stmt->rowCount() > 0) {
                $slug = $this->generateUniqueSlug($slug);
            }
            
            $query = "INSERT INTO directory_items (
                title, description, slug, url, category,
                rating, price_range, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
            
            $this->db->query($query, [
                $title,
                $description,
                $slug,
                $url,
                $category,
                $rating,
                $priceRange
            ]);
            
            $itemId = $this->db->lastInsertId();
            
            $this->params['id'] = $itemId;
            $this->show();
        } catch (\Exception $e) {
            $this->serverError('Failed to create directory item: ' . $e->getMessage());
        }
    }
    
    public function update() {
        $itemId = isset($this->params['id']) ? (int)$this->params['id'] : null;
        if (!$itemId) {
            $this->badRequest('Directory item ID is required');
            return;
        }
        
        try {
            // Check if directory item exists
            $stmt = $this->db->query("SELECT id FROM directory_items WHERE id = ?", [$itemId]);
            if ($stmt->rowCount() === 0) {
                $this->notFound('Directory item not found');
                return;
            }
            
            $updates = [];
            $params = [];
            
            if (isset($this->request['title'])) {
                $title = Validator::sanitizeString($this->request['title']);
                $updates[] = "title = ?";
                $params[] = $title;
                
                // Update slug if title changed and slug not provided
                if (!isset($this->request['slug'])) {
                    $slug = $this->generateSlug($title);
                    $stmt = $this->db->query("SELECT id FROM directory_items WHERE slug = ? AND id != ?", [$slug, $itemId]);
                    if ($stmt->rowCount() > 0) {
                        $slug = $this->generateUniqueSlug($slug);
                    }
                    $updates[] = "slug = ?";
                    $params[] = $slug;
                }
            }
            
            if (isset($this->request['description'])) {
                $updates[] = "description = ?";
                $params[] = $this->request['description'];
            }
            
            if (isset($this->request['slug'])) {
                $slug = Validator::sanitizeString($this->request['slug']);
                $stmt = $this->db->query("SELECT id FROM directory_items WHERE slug = ? AND id != ?", [$slug, $itemId]);
                if ($stmt->rowCount() > 0) {
                    $slug = $this->generateUniqueSlug($slug);
                }
                $updates[] = "slug = ?";
                $params[] = $slug;
            }
            
            if (isset($this->request['url'])) {
                $updates[] = "url = ?";
                $params[] = $this->request['url'];
            }
            
            if (isset($this->request['category'])) {
                $updates[] = "category = ?";
                $params[] = $this->request['category'];
            }
            
            if (isset($this->request['rating'])) {
                $updates[] = "rating = ?";
                $params[] = (float)$this->request['rating'];
            }
            
            if (isset($this->request['price_range'])) {
                $updates[] = "price_range = ?";
                $params[] = $this->request['price_range'];
            }
            
            if (empty($updates)) {
                $this->params['id'] = $itemId;
                $this->show();
                return;
            }
            
            $updates[] = "updated_at = NOW()";
            $params[] = $itemId;
            
            $query = "UPDATE directory_items SET " . implode(", ", $updates) . " WHERE id = ?";
            $this->db->query($query, $params);
            
            $this->params['id'] = $itemId;
            $this->show();
        } catch (\Exception $e) {
            $this->serverError('Failed to update directory item: ' . $e->getMessage());
        }
    }
    
    public function delete() {
        $itemId = isset($this->params['id']) ? (int)$this->params['id'] : null;
        if (!$itemId) {
            $this->badRequest('Directory item ID is required');
            return;
        }
        
        try {
            // Check if directory item exists
            $stmt = $this->db->query("SELECT id FROM directory_items WHERE id = ?", [$itemId]);
            if ($stmt->rowCount() === 0) {
                $this->notFound('Directory item not found');
                return;
            }
            
            $this->db->query("DELETE FROM directory_items WHERE id = ?", [$itemId]);
            Response::sendSuccess(['id' => $itemId, 'deleted' => true]);
        } catch (\Exception $e) {
            $this->serverError('Failed to delete directory item: ' . $e->getMessage());
        }
    }
    
    private function generateSlug($title) {
        $slug = strtolower($title);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');
        return $slug;
    }
    
    private function generateUniqueSlug($slug) {
        $originalSlug = $slug;
        $counter = 1;
        
        while (true) {
            $stmt = $this->db->query("SELECT id FROM directory_items WHERE slug = ?", [$slug]);
            if ($stmt->rowCount() === 0) {
                break;
            }
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }
}