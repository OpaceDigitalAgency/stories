<?php
namespace StoriesAPI\Endpoints;

use StoriesAPI\Core\BaseController;
use StoriesAPI\Utils\Response;
use StoriesAPI\Utils\Validator;

class AuthorsController extends BaseController {
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
            $sortField = 'a.' . $sortField;
            
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
                $where[] = "a.$field LIKE ?";
                $params[] = "%$value%";
            }
            
            $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
            
            // Count total records
            $countQuery = "SELECT COUNT(*) as total FROM authors a $whereClause";
            $stmt = $this->db->query($countQuery, $params);
            $total = $stmt->fetch()['total'];
            
            // Get authors with pagination
            $query = "SELECT
                a.id, a.name, a.slug, a.bio, a.avatar_url,
                a.created_at AS createdAt, a.updated_at AS updatedAt
                FROM authors a
                $whereClause
                $sortClause
                LIMIT $offset, $pageSize";
            
            $stmt = $this->db->query($query, $params);
            $authors = $stmt->fetchAll();
            
            // Format authors
            $formattedAuthors = [];
            foreach ($authors as $author) {
                $formattedAuthors[] = [
                    'id' => $author['id'],
                    'attributes' => [
                        'name' => $author['name'],
                        'slug' => $author['slug'],
                        'bio' => $author['bio'],
                        'avatarUrl' => $author['avatar_url'],
                        'createdAt' => $author['createdAt'],
                        'updatedAt' => $author['updatedAt']
                    ]
                ];
            }
            
            Response::sendPaginated($formattedAuthors, $page, $pageSize, $total);
        } catch (\Exception $e) {
            $this->serverError('Failed to fetch authors: ' . $e->getMessage());
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
                id, name, slug, bio, avatar_url,
                created_at AS createdAt, updated_at AS updatedAt
                FROM authors
                WHERE $column = ?
                LIMIT 1";
            
            $stmt = $this->db->query($query, [$value]);
            $author = $stmt->fetch();
            
            if (!$author) {
                Response::sendError('Author not found', 404);
                return;
            }
            
            $formattedAuthor = [
                'id' => $author['id'],
                'attributes' => [
                    'name' => $author['name'],
                    'slug' => $author['slug'],
                    'bio' => $author['bio'],
                    'avatarUrl' => $author['avatar_url'],
                    'createdAt' => $author['createdAt'],
                    'updatedAt' => $author['updatedAt']
                ]
            ];
            
            Response::sendSuccess($formattedAuthor);
        } catch (\Exception $e) {
            $this->serverError('Failed to fetch author: ' . $e->getMessage());
        }
    }
    
    public function create() {
        if (!Validator::required($this->request, ['name'])) {
            $this->badRequest('Name is required');
            return;
        }
        
        try {
            $name = Validator::sanitizeString($this->request['name']);
            $bio = $this->request['bio'] ?? '';
            $slug = isset($this->request['slug']) ? Validator::sanitizeString($this->request['slug']) : $this->generateSlug($name);
            $avatarUrl = $this->request['avatar_url'] ?? null;
            
            // Check if slug exists
            $stmt = $this->db->query("SELECT id FROM authors WHERE slug = ?", [$slug]);
            if ($stmt->rowCount() > 0) {
                $slug = $this->generateUniqueSlug($slug);
            }
            
            $query = "INSERT INTO authors (name, slug, bio, avatar_url, created_at, updated_at)
                VALUES (?, ?, ?, ?, NOW(), NOW())";
            
            $this->db->query($query, [$name, $slug, $bio, $avatarUrl]);
            $authorId = $this->db->lastInsertId();
            
            $this->params['id'] = $authorId;
            $this->show();
        } catch (\Exception $e) {
            $this->serverError('Failed to create author: ' . $e->getMessage());
        }
    }
    
    public function update() {
        $authorId = isset($this->params['id']) ? (int)$this->params['id'] : null;
        if (!$authorId) {
            $this->badRequest('Author ID is required');
            return;
        }
        
        try {
            // Check if author exists
            $stmt = $this->db->query("SELECT id FROM authors WHERE id = ?", [$authorId]);
            if ($stmt->rowCount() === 0) {
                $this->notFound('Author not found');
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
                    $stmt = $this->db->query("SELECT id FROM authors WHERE slug = ? AND id != ?", [$slug, $authorId]);
                    if ($stmt->rowCount() > 0) {
                        $slug = $this->generateUniqueSlug($slug);
                    }
                    $updates[] = "slug = ?";
                    $params[] = $slug;
                }
            }
            
            if (isset($this->request['bio'])) {
                $updates[] = "bio = ?";
                $params[] = $this->request['bio'];
            }
            
            if (isset($this->request['slug'])) {
                $slug = Validator::sanitizeString($this->request['slug']);
                $stmt = $this->db->query("SELECT id FROM authors WHERE slug = ? AND id != ?", [$slug, $authorId]);
                if ($stmt->rowCount() > 0) {
                    $slug = $this->generateUniqueSlug($slug);
                }
                $updates[] = "slug = ?";
                $params[] = $slug;
            }
            
            if (isset($this->request['avatar_url'])) {
                $updates[] = "avatar_url = ?";
                $params[] = $this->request['avatar_url'];
            }
            
            if (empty($updates)) {
                $this->params['id'] = $authorId;
                $this->show();
                return;
            }
            
            $updates[] = "updated_at = NOW()";
            $params[] = $authorId;
            
            $query = "UPDATE authors SET " . implode(", ", $updates) . " WHERE id = ?";
            $this->db->query($query, $params);
            
            $this->params['id'] = $authorId;
            $this->show();
        } catch (\Exception $e) {
            $this->serverError('Failed to update author: ' . $e->getMessage());
        }
    }
    
    public function delete() {
        $authorId = isset($this->params['id']) ? (int)$this->params['id'] : null;
        if (!$authorId) {
            $this->badRequest('Author ID is required');
            return;
        }
        
        try {
            // Check if author exists
            $stmt = $this->db->query("SELECT id FROM authors WHERE id = ?", [$authorId]);
            if ($stmt->rowCount() === 0) {
                $this->notFound('Author not found');
                return;
            }
            
            $this->db->query("DELETE FROM authors WHERE id = ?", [$authorId]);
            Response::sendSuccess(['id' => $authorId, 'deleted' => true]);
        } catch (\Exception $e) {
            $this->serverError('Failed to delete author: ' . $e->getMessage());
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
            $stmt = $this->db->query("SELECT id FROM authors WHERE slug = ?", [$slug]);
            if ($stmt->rowCount() === 0) {
                break;
            }
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }
}