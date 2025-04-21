<?php
namespace StoriesAPI\Endpoints;

use StoriesAPI\Core\BaseController;
use StoriesAPI\Utils\Response;
use StoriesAPI\Utils\Validator;

class TagsController extends BaseController {
    public function index() {
        try {
            // Get pagination parameters
            $pagination = $this->getPaginationParams();
            $page = $pagination['page'];
            $pageSize = $pagination['pageSize'];
            $offset = ($page - 1) * $pageSize;
            
            // Get sort parameters
            $allowedSortFields = ['name', 'created_at'];
            $sort = parent::getSortParams($allowedSortFields);
            $sortField = $sort['field'] ?? 'created_at';
            
            // Add table alias to sort field
            $sortField = 't.' . $sortField;
            
            $sortDirection = $sort['direction'] ?? 'DESC';
            $sortClause = "ORDER BY $sortField $sortDirection";
            
            // Get filter parameters
            $allowedFilterFields = ['name', 'slug'];
            $filters = $this->getFilterParams($allowedFilterFields);
            
            // Build WHERE clause
            $where = [];
            $params = [];
            
            foreach ($filters as $field => $value) {
                if ($value === '') {
                    continue;
                }
                $where[] = "t.$field LIKE ?";
                $params[] = "%$value%";
            }
            
            $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
            
            // Count total records
            $countQuery = "SELECT COUNT(*) as total FROM tags t $whereClause";
            $stmt = $this->db->query($countQuery, $params);
            $total = $stmt->fetch()['total'];
            
            // Get tags with pagination
            $query = "SELECT
                t.id, t.name, t.slug, t.description,
                t.created_at AS createdAt, t.updated_at AS updatedAt
                FROM tags t
                $whereClause
                $sortClause
                LIMIT $offset, $pageSize";
            
            $stmt = $this->db->query($query, $params);
            $tags = $stmt->fetchAll();
            
            // Format tags
            $formattedTags = [];
            foreach ($tags as $tag) {
                $formattedTags[] = [
                    'id' => $tag['id'],
                    'attributes' => [
                        'name' => $tag['name'],
                        'slug' => $tag['slug'],
                        'description' => $tag['description'],
                        'createdAt' => $tag['createdAt'],
                        'updatedAt' => $tag['updatedAt']
                    ]
                ];
            }
            
            Response::sendPaginated($formattedTags, $page, $pageSize, $total);
        } catch (\Exception $e) {
            $this->serverError('Failed to fetch tags: ' . $e->getMessage());
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
                id, name, slug, description,
                created_at AS createdAt, updated_at AS updatedAt
                FROM tags
                WHERE $column = ?
                LIMIT 1";
            
            $stmt = $this->db->query($query, [$value]);
            $tag = $stmt->fetch();
            
            if (!$tag) {
                Response::sendError('Tag not found', 404);
                return;
            }
            
            $formattedTag = [
                'id' => $tag['id'],
                'attributes' => [
                    'name' => $tag['name'],
                    'slug' => $tag['slug'],
                    'description' => $tag['description'],
                    'createdAt' => $tag['createdAt'],
                    'updatedAt' => $tag['updatedAt']
                ]
            ];
            
            Response::sendSuccess($formattedTag);
        } catch (\Exception $e) {
            $this->serverError('Failed to fetch tag: ' . $e->getMessage());
        }
    }
    
    public function create() {
        if (!Validator::required($this->request, ['name'])) {
            $this->badRequest('Name is required');
            return;
        }
        
        try {
            $name = Validator::sanitizeString($this->request['name']);
            $description = $this->request['description'] ?? '';
            $slug = isset($this->request['slug']) ? Validator::sanitizeString($this->request['slug']) : $this->generateSlug($name);
            
            // Check if slug exists
            $stmt = $this->db->query("SELECT id FROM tags WHERE slug = ?", [$slug]);
            if ($stmt->rowCount() > 0) {
                $slug = $this->generateUniqueSlug($slug);
            }
            
            $query = "INSERT INTO tags (name, slug, description, created_at, updated_at)
                VALUES (?, ?, ?, NOW(), NOW())";
            
            $this->db->query($query, [$name, $slug, $description]);
            $tagId = $this->db->lastInsertId();
            
            $this->params['id'] = $tagId;
            $this->show();
        } catch (\Exception $e) {
            $this->serverError('Failed to create tag: ' . $e->getMessage());
        }
    }
    
    public function update() {
        $tagId = isset($this->params['id']) ? (int)$this->params['id'] : null;
        if (!$tagId) {
            $this->badRequest('Tag ID is required');
            return;
        }
        
        try {
            // Check if tag exists
            $stmt = $this->db->query("SELECT id FROM tags WHERE id = ?", [$tagId]);
            if ($stmt->rowCount() === 0) {
                $this->notFound('Tag not found');
                return;
            }
            
            $updates = [];
            $params = [];
            
            if (isset($this->request['name'])) {
                $name = Validator::sanitizeString($this->request['name']);
                $updates[] = "name = ?";
                $params[] = $name;
                
                // Update slug if name changed and slug not provided
                if (!isset($this->request['slug'])) {
                    $slug = $this->generateSlug($name);
                    $stmt = $this->db->query("SELECT id FROM tags WHERE slug = ? AND id != ?", [$slug, $tagId]);
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
                $stmt = $this->db->query("SELECT id FROM tags WHERE slug = ? AND id != ?", [$slug, $tagId]);
                if ($stmt->rowCount() > 0) {
                    $slug = $this->generateUniqueSlug($slug);
                }
                $updates[] = "slug = ?";
                $params[] = $slug;
            }
            
            if (empty($updates)) {
                $this->params['id'] = $tagId;
                $this->show();
                return;
            }
            
            $updates[] = "updated_at = NOW()";
            $params[] = $tagId;
            
            $query = "UPDATE tags SET " . implode(", ", $updates) . " WHERE id = ?";
            $this->db->query($query, $params);
            
            $this->params['id'] = $tagId;
            $this->show();
        } catch (\Exception $e) {
            $this->serverError('Failed to update tag: ' . $e->getMessage());
        }
    }
    
    public function delete() {
        $tagId = isset($this->params['id']) ? (int)$this->params['id'] : null;
        if (!$tagId) {
            $this->badRequest('Tag ID is required');
            return;
        }
        
        try {
            // Check if tag exists
            $stmt = $this->db->query("SELECT id FROM tags WHERE id = ?", [$tagId]);
            if ($stmt->rowCount() === 0) {
                $this->notFound('Tag not found');
                return;
            }
            
            $this->db->query("DELETE FROM tags WHERE id = ?", [$tagId]);
            Response::sendSuccess(['id' => $tagId, 'deleted' => true]);
        } catch (\Exception $e) {
            $this->serverError('Failed to delete tag: ' . $e->getMessage());
        }
    }
    
    private function generateSlug($name) {
        $slug = strtolower($name);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');
        return $slug;
    }
    
    private function generateUniqueSlug($slug) {
        $originalSlug = $slug;
        $counter = 1;
        
        while (true) {
            $stmt = $this->db->query("SELECT id FROM tags WHERE slug = ?", [$slug]);
            if ($stmt->rowCount() === 0) {
                break;
            }
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }
}