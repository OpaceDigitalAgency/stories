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
        $allowedSortFields = ['name', 'category'];
        $sort = $this->getSortParams($allowedSortFields);
        $sortClause = $sort ? "ORDER BY {$sort['field']} {$sort['direction']}" : "ORDER BY name ASC";
        
        // Get filter parameters
        $allowedFilterFields = ['name', 'category'];
        $filters = $this->getFilterParams($allowedFilterFields);
        
        try {
            // Build the WHERE clause
            $whereData = $this->buildWhereClause($filters);
            $whereClause = $whereData['clause'];
            $params = $whereData['params'];
            
            // Count total records
            $countQuery = "SELECT COUNT(*) as total FROM directory_items di $whereClause";
            $stmt = $this->db->query($countQuery, $params);
            $total = $stmt->fetch()['total'];
            
            // Get directory items with pagination
            $query = "SELECT 
                di.id, di.name, di.description, di.url, di.category,
                di.created_at as createdAt, di.updated_at as updatedAt
                FROM directory_items di
                $whereClause
                $sortClause
                LIMIT $offset, $pageSize";
            
            $stmt = $this->db->query($query, $params);
            $directoryItems = $stmt->fetchAll();
            
            // Format directory items to match Strapi response format
            $formattedDirectoryItems = [];
            
            foreach ($directoryItems as $item) {
                $itemId = $item['id'];
                
                // Get item logo
                $logoQuery = "SELECT id, url, width, height, alt_text FROM media WHERE entity_type = 'directory_item' AND entity_id = ? AND type = 'logo' LIMIT 1";
                $logoStmt = $this->db->query($logoQuery, [$itemId]);
                $logo = $logoStmt->fetch();
                
                // Format logo
                $formattedLogo = null;
                if ($logo) {
                    $formattedLogo = [
                        'data' => [
                            'id' => $logo['id'],
                            'attributes' => [
                                'url' => $logo['url'],
                                'width' => $logo['width'],
                                'height' => $logo['height'],
                                'alternativeText' => $logo['alt_text']
                            ]
                        ]
                    ];
                }
                
                // Build the formatted directory item
                $formattedDirectoryItems[] = [
                    'id' => $itemId,
                    'attributes' => [
                        'name' => $item['name'],
                        'description' => $item['description'],
                        'url' => $item['url'],
                        'category' => $item['category'],
                        'createdAt' => $item['createdAt'],
                        'updatedAt' => $item['updatedAt'],
                        'logo' => $formattedLogo
                    ]
                ];
            }
            
            // Send paginated response
            Response::sendPaginated($formattedDirectoryItems, $page, $pageSize, $total);
        } catch (\Exception $e) {
            $this->serverError('Failed to fetch directory items: ' . $e->getMessage());
        }
    }
    
    /**
     * Get a single directory item by ID
     */
    public function show() {
        // Validate ID
        $itemId = isset($this->params['id']) ? (int)$this->params['id'] : null;
        
        if (!$itemId) {
            $this->badRequest('Directory item ID is required');
            return;
        }
        
        try {
            // Get directory item by ID
            $query = "SELECT 
                di.id, di.name, di.description, di.url, di.category,
                di.created_at as createdAt, di.updated_at as updatedAt
                FROM directory_items di 
                WHERE di.id = ? LIMIT 1";
            
            $stmt = $this->db->query($query, [$itemId]);
            
            if ($stmt->rowCount() === 0) {
                $this->notFound('Directory item not found');
                return;
            }
            
            $item = $stmt->fetch();
            
            // Get item logo
            $logoQuery = "SELECT id, url, width, height, alt_text FROM media WHERE entity_type = 'directory_item' AND entity_id = ? AND type = 'logo' LIMIT 1";
            $logoStmt = $this->db->query($logoQuery, [$itemId]);
            $logo = $logoStmt->fetch();
            
            // Format logo
            $formattedLogo = null;
            if ($logo) {
                $formattedLogo = [
                    'data' => [
                        'id' => $logo['id'],
                        'attributes' => [
                            'url' => $logo['url'],
                            'width' => $logo['width'],
                            'height' => $logo['height'],
                            'alternativeText' => $logo['alt_text']
                        ]
                    ]
                ];
            }
            
            // Build the formatted directory item
            $formattedDirectoryItem = [
                'id' => $itemId,
                'attributes' => [
                    'name' => $item['name'],
                    'description' => $item['description'],
                    'url' => $item['url'],
                    'category' => $item['category'],
                    'createdAt' => $item['createdAt'],
                    'updatedAt' => $item['updatedAt'],
                    'logo' => $formattedLogo
                ]
            ];
            
            // Send response
            Response::sendSuccess(['data' => $formattedDirectoryItem]);
        } catch (\Exception $e) {
            $this->serverError('Failed to fetch directory item: ' . $e->getMessage());
        }
    }
    
    /**
     * Create a new directory item
     */
    public function create() {
        // Validate required fields
        if (!Validator::required($this->request, ['name', 'url'])) {
            $this->badRequest('Name and URL are required', Validator::getErrors());
            return;
        }
        
        // Validate name length
        if (!Validator::length($this->request['name'], 'name', 2, 100)) {
            $this->badRequest('Name must be between 2 and 100 characters', Validator::getErrors());
            return;
        }
        
        // Validate URL
        if (!Validator::url($this->request['url'], 'url')) {
            $this->badRequest('URL must be a valid URL', Validator::getErrors());
            return;
        }
        
        // Sanitize input
        $name = Validator::sanitizeString($this->request['name']);
        $url = Validator::sanitizeString($this->request['url']);
        $description = isset($this->request['description']) ? Validator::sanitizeString($this->request['description']) : '';
        $category = isset($this->request['category']) ? Validator::sanitizeString($this->request['category']) : 'General';
        
        try {
            // Start transaction
            $this->db->beginTransaction();
            
            // Insert directory item
            $query = "INSERT INTO directory_items (
                name, description, url, category, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?)";
            
            $this->db->query($query, [
                $name,
                $description,
                $url,
                $category,
                date('Y-m-d H:i:s'),
                date('Y-m-d H:i:s')
            ]);
            
            $itemId = $this->db->lastInsertId();
            
            // Handle logo if provided
            if (isset($this->request['logo']) && !empty($this->request['logo'])) {
                $logoUrl = Validator::sanitizeString($this->request['logo']);
                $query = "INSERT INTO media (
                    entity_type, entity_id, type, url, width, height, alt_text, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                
                $this->db->query($query, [
                    'directory_item',
                    $itemId,
                    'logo',
                    $logoUrl,
                    isset($this->request['logoWidth']) ? (int)$this->request['logoWidth'] : 200,
                    isset($this->request['logoHeight']) ? (int)$this->request['logoHeight'] : 200,
                    isset($this->request['logoAlt']) ? Validator::sanitizeString($this->request['logoAlt']) : $name,
                    date('Y-m-d H:i:s')
                ]);
            }
            
            // Commit transaction
            $this->db->commit();
            
            // Return the created directory item
            $this->params['id'] = $itemId;
            $this->show();
        } catch (\Exception $e) {
            // Rollback transaction
            $this->db->rollback();
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
            
            $item = $stmt->fetch();
            
            // Build update query
            $updates = [];
            $params = [];
            
            // Update fields if provided
            if (isset($this->request['name'])) {
                if (!Validator::length($this->request['name'], 'name', 2, 100)) {
                    $this->badRequest('Name must be between 2 and 100 characters', Validator::getErrors());
                    return;
                }
                
                $updates[] = "name = ?";
                $params[] = Validator::sanitizeString($this->request['name']);
            }
            
            if (isset($this->request['url'])) {
                if (!Validator::url($this->request['url'], 'url')) {
                    $this->badRequest('URL must be a valid URL', Validator::getErrors());
                    return;
                }
                
                $updates[] = "url = ?";
                $params[] = Validator::sanitizeString($this->request['url']);
            }
            
            if (isset($this->request['description'])) {
                $updates[] = "description = ?";
                $params[] = Validator::sanitizeString($this->request['description']);
            }
            
            if (isset($this->request['category'])) {
                $updates[] = "category = ?";
                $params[] = Validator::sanitizeString($this->request['category']);
            }
            
            // Add updated_at
            $updates[] = "updated_at = ?";
            $params[] = date('Y-m-d H:i:s');
            
            // Add item ID to params
            $params[] = $itemId;
            
            // Start transaction
            $this->db->beginTransaction();
            
            // Update directory item
            if (!empty($updates)) {
                $query = "UPDATE directory_items SET " . implode(', ', $updates) . " WHERE id = ?";
                $this->db->query($query, $params);
            }
            
            // Handle logo if provided
            if (isset($this->request['logo']) && !empty($this->request['logo'])) {
                $logoUrl = Validator::sanitizeString($this->request['logo']);
                
                // Check if logo already exists
                $query = "SELECT id FROM media WHERE entity_type = 'directory_item' AND entity_id = ? AND type = 'logo' LIMIT 1";
                $stmt = $this->db->query($query, [$itemId]);
                
                if ($stmt->rowCount() > 0) {
                    // Update existing logo
                    $logoId = $stmt->fetch()['id'];
                    $query = "UPDATE media SET 
                        url = ?, 
                        width = ?, 
                        height = ?, 
                        alt_text = ? 
                        WHERE id = ?";
                    
                    $this->db->query($query, [
                        $logoUrl,
                        isset($this->request['logoWidth']) ? (int)$this->request['logoWidth'] : 200,
                        isset($this->request['logoHeight']) ? (int)$this->request['logoHeight'] : 200,
                        isset($this->request['logoAlt']) ? Validator::sanitizeString($this->request['logoAlt']) : $item['name'],
                        $logoId
                    ]);
                } else {
                    // Insert new logo
                    $query = "INSERT INTO media (
                        entity_type, entity_id, type, url, width, height, alt_text, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                    
                    $this->db->query($query, [
                        'directory_item',
                        $itemId,
                        'logo',
                        $logoUrl,
                        isset($this->request['logoWidth']) ? (int)$this->request['logoWidth'] : 200,
                        isset($this->request['logoHeight']) ? (int)$this->request['logoHeight'] : 200,
                        isset($this->request['logoAlt']) ? Validator::sanitizeString($this->request['logoAlt']) : $item['name'],
                        date('Y-m-d H:i:s')
                    ]);
                }
            }
            
            // Commit transaction
            $this->db->commit();
            
            // Return the updated directory item
            $this->params['id'] = $itemId;
            $this->show();
        } catch (\Exception $e) {
            // Rollback transaction
            $this->db->rollback();
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
            $query = "SELECT * FROM directory_items WHERE id = ? LIMIT 1";
            $stmt = $this->db->query($query, [$itemId]);
            
            if ($stmt->rowCount() === 0) {
                $this->notFound('Directory item not found');
                return;
            }
            
            // Start transaction
            $this->db->beginTransaction();
            
            // Delete directory item media
            $query = "DELETE FROM media WHERE entity_type = 'directory_item' AND entity_id = ?";
            $this->db->query($query, [$itemId]);
            
            // Delete directory item
            $query = "DELETE FROM directory_items WHERE id = ?";
            $this->db->query($query, [$itemId]);
            
            // Commit transaction
            $this->db->commit();
            
            // Send success response
            Response::sendSuccess(['message' => 'Directory item deleted successfully'], [], 200);
        } catch (\Exception $e) {
            // Rollback transaction
            $this->db->rollback();
            $this->serverError('Failed to delete directory item: ' . $e->getMessage());
        }
    }
}