<?php
/**
 * Directory Items Controller
 * 
 * This controller handles CRUD operations for directory items.
 * 
 * @package Stories API
 * @version 1.0.0
 */

namespace StoriesAPI\Endpoints;

use StoriesAPI\Core\BaseController;
use StoriesAPI\Utils\Response;
use StoriesAPI\Utils\Validator;

class DirectoryItemsController extends BaseController {
    /**
     * Get all directory items with pagination, filtering, and sorting
     */
    public function index() {
        // Get pagination parameters
        $pagination = $this->getPaginationParams();
        $page = $pagination['page'];
        $pageSize = $pagination['pageSize'];
        $offset = ($page - 1) * $pageSize;
        
        // Get sort parameters
        $allowedSortFields = ['title', 'created_at'];
        $sort = $this->getSortParams($allowedSortFields);
        $sortField = $sort['field'] ?? 'created_at';
        $sortDirection = $sort['direction'] ?? 'DESC';
        $sortClause = "ORDER BY $sortField $sortDirection";
        
        // Get filter parameters
        $allowedFilterFields = ['title', 'slug', 'featured', 'is_published', 'category_id'];
        $filters = $this->getFilterParams($allowedFilterFields);
        
        try {
            // Build the WHERE clause
            $whereData = $this->buildWhereClause($filters);
            $whereClause = $whereData['clause'];
            $params = $whereData['params'];
            
            // Count total records
            $countQuery = "SELECT COUNT(*) as total FROM directory_items $whereClause";
            $stmt = $this->db->query($countQuery, $params);
            $total = $stmt->fetch()['total'];
            
            // Get directory items with pagination
            $query = "SELECT
                d.id, d.title, d.description, d.slug, d.featured, d.is_published,
                d.category_id, d.website_url, d.contact_email, d.contact_phone, d.address,
                d.published_at AS publishedAt, d.created_at AS createdAt, d.updated_at AS updatedAt,
                c.name AS category_name, c.slug AS category_slug
                FROM directory_items d
                LEFT JOIN directory_categories c ON d.category_id = c.id
                $whereClause
                $sortClause
                LIMIT $offset, $pageSize";
            
            $stmt = $this->db->query($query, $params);
            $items = $stmt->fetchAll();
            
            // Format directory items with the expected structure
            $formattedItems = [];
            foreach ($items as $item) {
                $category = null;
                if ($item['category_id']) {
                    $category = [
                        'id' => $item['category_id'],
                        'name' => $item['category_name'],
                        'slug' => $item['category_slug']
                    ];
                }
                
                $formattedItems[] = [
                    'id' => $item['id'],
                    'attributes' => [
                        'title' => $item['title'],
                        'description' => $item['description'],
                        'slug' => $item['slug'],
                        'websiteUrl' => $item['website_url'],
                        'contactEmail' => $item['contact_email'],
                        'contactPhone' => $item['contact_phone'],
                        'address' => $item['address'],
                        'featured' => (bool)$item['featured'],
                        'isPublished' => (bool)$item['is_published'],
                        'publishedAt' => $item['publishedAt'],
                        'createdAt' => $item['createdAt'],
                        'updatedAt' => $item['updatedAt'],
                        'category' => $category
                    ]
                ];
            }
            
            // Send paginated response
            Response::sendPaginated($formattedItems, $page, $pageSize, $total);
        } catch (\Exception $e) {
            $this->serverError('Failed to fetch directory items: ' . $e->getMessage());
        }
    }
    
    /**
     * Get a single directory item by ID or slug
     */
    public function show() {
        // Check for both 'id' and 'slug' parameters
        $identifier = $this->params['id'] ?? $this->params['slug'] ?? null;
        if (!$identifier) {
            Response::sendError('No identifier provided', 400);
            return;
        }
        
        // Decide whether this is an ID or a slug
        if (ctype_digit($identifier)) {
            $column = 'd.id';
            $value = (int)$identifier;
        } else {
            $column = 'd.slug';
            $value = Validator::sanitizeString($identifier);
        }
        
        try {
            $query = "SELECT
                d.id, d.title, d.description, d.slug, d.featured, d.is_published,
                d.category_id, d.website_url, d.contact_email, d.contact_phone, d.address,
                d.published_at AS publishedAt, d.created_at AS createdAt, d.updated_at AS updatedAt,
                c.name AS category_name, c.slug AS category_slug
                FROM directory_items d
                LEFT JOIN directory_categories c ON d.category_id = c.id
                WHERE $column = ?
                LIMIT 1";
            $stmt = $this->db->query($query, [$value]);
            $item = $stmt->fetch();
            
            if (!$item) {
                Response::sendError('Directory item not found', 404);
                return;
            }
            
            // Format directory item with the expected structure
            $category = null;
            if ($item['category_id']) {
                $category = [
                    'id' => $item['category_id'],
                    'name' => $item['category_name'],
                    'slug' => $item['category_slug']
                ];
            }
            
            $formattedItem = [
                'id' => $item['id'],
                'attributes' => [
                    'title' => $item['title'],
                    'description' => $item['description'],
                    'slug' => $item['slug'],
                    'websiteUrl' => $item['website_url'],
                    'contactEmail' => $item['contact_email'],
                    'contactPhone' => $item['contact_phone'],
                    'address' => $item['address'],
                    'featured' => (bool)$item['featured'],
                    'isPublished' => (bool)$item['is_published'],
                    'publishedAt' => $item['publishedAt'],
                    'createdAt' => $item['createdAt'],
                    'updatedAt' => $item['updatedAt'],
                    'category' => $category
                ]
            ];
            
            Response::sendSuccess($formattedItem);
        } catch (\Exception $e) {
            $this->serverError('Failed to fetch directory item: ' . $e->getMessage());
        }
    }
    
    /**
     * Create a new directory item
     */
    public function create() {
        // Validate required fields
        if (!Validator::required($this->request, ['title'])) {
            $this->badRequest('Title is required');
            return;
        }
        
        // Sanitize input
        $title = Validator::sanitizeString($this->request['title']);
        $description = $this->request['description'] ?? '';
        $slug = isset($this->request['slug']) ? Validator::sanitizeString($this->request['slug']) : $this->generateSlug($title);
        $categoryId = !empty($this->request['category_id']) ? (int)$this->request['category_id'] : null;
        $websiteUrl = $this->request['website_url'] ?? '';
        $contactEmail = $this->request['contact_email'] ?? '';
        $contactPhone = $this->request['contact_phone'] ?? '';
        $address = $this->request['address'] ?? '';
        $featured = isset($this->request['featured']) ? (bool)$this->request['featured'] : false;
        $isPublished = isset($this->request['is_published']) ? (bool)$this->request['is_published'] : false;
        $publishedAt = isset($this->request['published_at']) ? $this->request['published_at'] : null;
        
        try {
            // Check if slug already exists
            $query = "SELECT id FROM directory_items WHERE slug = ? LIMIT 1";
            $stmt = $this->db->query($query, [$slug]);
            
            if ($stmt->rowCount() > 0) {
                // Generate a unique slug
                $slug = $this->generateUniqueSlug($slug);
            }
            
            // Check if category exists if provided
            if ($categoryId) {
                $query = "SELECT id FROM directory_categories WHERE id = ? LIMIT 1";
                $stmt = $this->db->query($query, [$categoryId]);
                
                if ($stmt->rowCount() === 0) {
                    $this->badRequest('Category not found');
                    return;
                }
            }
            
            // Insert directory item
            $query = "INSERT INTO directory_items (
                title, description, slug, category_id, website_url, contact_email, contact_phone, address,
                featured, is_published, published_at, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
            
            $this->db->query($query, [
                $title,
                $description,
                $slug,
                $categoryId,
                $websiteUrl,
                $contactEmail,
                $contactPhone,
                $address,
                $featured ? 1 : 0,
                $isPublished ? 1 : 0,
                $publishedAt
            ]);
            
            $itemId = $this->db->lastInsertId();
            
            // Return the created directory item
            $this->params['id'] = $itemId;
            $this->show();
        } catch (\Exception $e) {
            $this->serverError('Failed to create directory item: ' . $e->getMessage());
        }
    }
    
    /**
     * Update a directory item
     */
    public function update() {
        // Validate directory item ID
        $itemId = isset($this->params['id']) ? (int)$this->params['id'] : null;
        
        if (!$itemId) {
            $this->badRequest('Directory item ID is required');
            return;
        }
        
        try {
            // Check if directory item exists
            $query = "SELECT * FROM directory_items WHERE id = ? LIMIT 1";
            $stmt = $this->db->query($query, [$itemId]);
            
            if ($stmt->rowCount() === 0) {
                $this->notFound('Directory item not found');
                return;
            }
            
            // Build update query
            $updates = [];
            $params = [];
            
            // Update fields if provided
            if (isset($this->request['title'])) {
                $title = Validator::sanitizeString($this->request['title']);
                $updates[] = "title = ?";
                $params[] = $title;
                
                // Update slug if title is changed and slug is not provided
                if (!isset($this->request['slug'])) {
                    $slug = $this->generateSlug($title);
                    
                    // Check if slug already exists
                    $query = "SELECT id FROM directory_items WHERE slug = ? AND id != ? LIMIT 1";
                    $stmt = $this->db->query($query, [$slug, $itemId]);
                    
                    if ($stmt->rowCount() > 0) {
                        // Generate a unique slug
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
                
                // Check if slug already exists
                $query = "SELECT id FROM directory_items WHERE slug = ? AND id != ? LIMIT 1";
                $stmt = $this->db->query($query, [$slug, $itemId]);
                
                if ($stmt->rowCount() > 0) {
                    // Generate a unique slug
                    $slug = $this->generateUniqueSlug($slug);
                }
                
                $updates[] = "slug = ?";
                $params[] = $slug;
            }
            
            if (isset($this->request['category_id'])) {
                $categoryId = !empty($this->request['category_id']) ? (int)$this->request['category_id'] : null;
                
                // Check if category exists if provided
                if ($categoryId) {
                    $query = "SELECT id FROM directory_categories WHERE id = ? LIMIT 1";
                    $stmt = $this->db->query($query, [$categoryId]);
                    
                    if ($stmt->rowCount() === 0) {
                        $this->badRequest('Category not found');
                        return;
                    }
                }
                
                $updates[] = "category_id = ?";
                $params[] = $categoryId;
            }
            
            if (isset($this->request['website_url'])) {
                $updates[] = "website_url = ?";
                $params[] = $this->request['website_url'];
            }
            
            if (isset($this->request['contact_email'])) {
                $updates[] = "contact_email = ?";
                $params[] = $this->request['contact_email'];
            }
            
            if (isset($this->request['contact_phone'])) {
                $updates[] = "contact_phone = ?";
                $params[] = $this->request['contact_phone'];
            }
            
            if (isset($this->request['address'])) {
                $updates[] = "address = ?";
                $params[] = $this->request['address'];
            }
            
            if (isset($this->request['featured'])) {
                $updates[] = "featured = ?";
                $params[] = (bool)$this->request['featured'] ? 1 : 0;
            }
            
            if (isset($this->request['is_published'])) {
                $updates[] = "is_published = ?";
                $params[] = (bool)$this->request['is_published'] ? 1 : 0;
            }
            
            if (isset($this->request['published_at'])) {
                $updates[] = "published_at = ?";
                $params[] = $this->request['published_at'];
            }
            
            // Add updated_at
            $updates[] = "updated_at = NOW()";
            
            // If no updates, return the directory item
            if (empty($updates)) {
                $this->params['id'] = $itemId;
                $this->show();
                return;
            }
            
            // Add directory item ID to params
            $params[] = $itemId;
            
            // Update directory item
            $query = "UPDATE directory_items SET " . implode(", ", $updates) . " WHERE id = ?";
            $this->db->query($query, $params);
            
            // Return the updated directory item
            $this->params['id'] = $itemId;
            $this->show();
        } catch (\Exception $e) {
            $this->serverError('Failed to update directory item: ' . $e->getMessage());
        }
    }
    
    /**
     * Delete a directory item
     */
    public function delete() {
        // Validate directory item ID
        $itemId = isset($this->params['id']) ? (int)$this->params['id'] : null;
        
        if (!$itemId) {
            $this->badRequest('Directory item ID is required');
            return;
        }
        
        try {
            // Check if directory item exists
            $query = "SELECT id FROM directory_items WHERE id = ? LIMIT 1";
            $stmt = $this->db->query($query, [$itemId]);
            
            if ($stmt->rowCount() === 0) {
                $this->notFound('Directory item not found');
                return;
            }
            
            // Delete directory item
            $query = "DELETE FROM directory_items WHERE id = ?";
            $this->db->query($query, [$itemId]);
            
            // Return success
            Response::sendSuccess(['id' => $itemId, 'deleted' => true]);
        } catch (\Exception $e) {
            $this->serverError('Failed to delete directory item: ' . $e->getMessage());
        }
    }
    
    /**
     * Generate a slug from a title
     */
    private function generateSlug($title) {
        // Convert to lowercase
        $slug = strtolower($title);
        // Replace non-alphanumeric characters with hyphens
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        // Remove leading and trailing hyphens
        $slug = trim($slug, '-');
        
        return $slug;
    }
    
    /**
     * Generate a unique slug
     */
    private function generateUniqueSlug($slug) {
        $originalSlug = $slug;
        $counter = 1;
        
        while (true) {
            $query = "SELECT id FROM directory_items WHERE slug = ? LIMIT 1";
            $stmt = $this->db->query($query, [$slug]);
            
            if ($stmt->rowCount() === 0) {
                break;
            }
            
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }
}