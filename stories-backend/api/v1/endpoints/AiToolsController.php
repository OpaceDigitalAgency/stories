<?php
/**
 * AI Tools Controller
 * 
 * This controller handles CRUD operations for AI tools.
 * 
 * @package Stories API
 * @version 1.0.0
 */

namespace StoriesAPI\Endpoints;

use StoriesAPI\Core\BaseController;
use StoriesAPI\Utils\Response;
use StoriesAPI\Utils\Validator;

class AiToolsController extends BaseController {
    /**
     * Get all AI tools with pagination, filtering, and sorting
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
            $countQuery = "SELECT COUNT(*) as total FROM ai_tools at $whereClause";
            $stmt = $this->db->query($countQuery, $params);
            $total = $stmt->fetch()['total'];
            
            // Get AI tools with pagination
            $query = "SELECT 
                at.id, at.name, at.description, at.url, at.category,
                at.created_at as createdAt, at.updated_at as updatedAt
                FROM ai_tools at
                $whereClause
                $sortClause
                LIMIT $offset, $pageSize";
            
            $stmt = $this->db->query($query, $params);
            $aiTools = $stmt->fetchAll();
            
            // Format AI tools to match Strapi response format
            $formattedAiTools = [];
            
            foreach ($aiTools as $tool) {
                $toolId = $tool['id'];
                
                // Get tool logo
                $logoQuery = "SELECT id, url, width, height, alt_text FROM media WHERE entity_type = 'ai_tool' AND entity_id = ? AND type = 'logo' LIMIT 1";
                $logoStmt = $this->db->query($logoQuery, [$toolId]);
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
                
                // Build the formatted AI tool
                $formattedAiTools[] = [
                    'id' => $toolId,
                    'attributes' => [
                        'name' => $tool['name'],
                        'description' => $tool['description'],
                        'url' => $tool['url'],
                        'category' => $tool['category'],
                        'createdAt' => $tool['createdAt'],
                        'updatedAt' => $tool['updatedAt'],
                        'logo' => $formattedLogo
                    ]
                ];
            }
            
            // Send paginated response
            Response::sendPaginated($formattedAiTools, $page, $pageSize, $total);
        } catch (\Exception $e) {
            $this->serverError('Failed to fetch AI tools: ' . $e->getMessage());
        }
    }
    
    /**
     * Get a single AI tool by ID
     */
    public function show() {
        // Validate ID
        $toolId = isset($this->params['id']) ? (int)$this->params['id'] : null;
        
        if (!$toolId) {
            $this->badRequest('AI tool ID is required');
            return;
        }
        
        try {
            // Get AI tool by ID
            $query = "SELECT 
                at.id, at.name, at.description, at.url, at.category,
                at.created_at as createdAt, at.updated_at as updatedAt
                FROM ai_tools at 
                WHERE at.id = ? LIMIT 1";
            
            $stmt = $this->db->query($query, [$toolId]);
            
            if ($stmt->rowCount() === 0) {
                $this->notFound('AI tool not found');
                return;
            }
            
            $tool = $stmt->fetch();
            
            // Get tool logo
            $logoQuery = "SELECT id, url, width, height, alt_text FROM media WHERE entity_type = 'ai_tool' AND entity_id = ? AND type = 'logo' LIMIT 1";
            $logoStmt = $this->db->query($logoQuery, [$toolId]);
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
            
            // Build the formatted AI tool
            $formattedAiTool = [
                'id' => $toolId,
                'attributes' => [
                    'name' => $tool['name'],
                    'description' => $tool['description'],
                    'url' => $tool['url'],
                    'category' => $tool['category'],
                    'createdAt' => $tool['createdAt'],
                    'updatedAt' => $tool['updatedAt'],
                    'logo' => $formattedLogo
                ]
            ];
            
            // Send response
            Response::sendSuccess(['data' => $formattedAiTool]);
        } catch (\Exception $e) {
            $this->serverError('Failed to fetch AI tool: ' . $e->getMessage());
        }
    }
    
    /**
     * Create a new AI tool
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
        $category = isset($this->request['category']) ? Validator::sanitizeString($this->request['category']) : 'Writing';
        
        try {
            // Start transaction
            $this->db->beginTransaction();
            
            // Insert AI tool
            $query = "INSERT INTO ai_tools (
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
            
            $toolId = $this->db->lastInsertId();
            
            // Handle logo if provided
            if (isset($this->request['logo']) && !empty($this->request['logo'])) {
                $logoUrl = Validator::sanitizeString($this->request['logo']);
                $query = "INSERT INTO media (
                    entity_type, entity_id, type, url, width, height, alt_text, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                
                $this->db->query($query, [
                    'ai_tool',
                    $toolId,
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
            
            // Return the created AI tool
            $this->params['id'] = $toolId;
            $this->show();
        } catch (\Exception $e) {
            // Rollback transaction
            $this->db->rollback();
            $this->serverError('Failed to create AI tool: ' . $e->getMessage());
        }
    }
    
    /**
     * Update an AI tool
     */
    public function update() {
        // Validate AI tool ID
        $toolId = isset($this->params['id']) ? (int)$this->params['id'] : null;
        
        if (!$toolId) {
            $this->badRequest('AI tool ID is required');
            return;
        }
        
        try {
            // Check if AI tool exists
            $query = "SELECT * FROM ai_tools WHERE id = ? LIMIT 1";
            $stmt = $this->db->query($query, [$toolId]);
            
            if ($stmt->rowCount() === 0) {
                $this->notFound('AI tool not found');
                return;
            }
            
            $tool = $stmt->fetch();
            
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
            
            // Add tool ID to params
            $params[] = $toolId;
            
            // Start transaction
            $this->db->beginTransaction();
            
            // Update AI tool
            if (!empty($updates)) {
                $query = "UPDATE ai_tools SET " . implode(', ', $updates) . " WHERE id = ?";
                $this->db->query($query, $params);
            }
            
            // Handle logo if provided
            if (isset($this->request['logo']) && !empty($this->request['logo'])) {
                $logoUrl = Validator::sanitizeString($this->request['logo']);
                
                // Check if logo already exists
                $query = "SELECT id FROM media WHERE entity_type = 'ai_tool' AND entity_id = ? AND type = 'logo' LIMIT 1";
                $stmt = $this->db->query($query, [$toolId]);
                
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
                        isset($this->request['logoAlt']) ? Validator::sanitizeString($this->request['logoAlt']) : $tool['name'],
                        $logoId
                    ]);
                } else {
                    // Insert new logo
                    $query = "INSERT INTO media (
                        entity_type, entity_id, type, url, width, height, alt_text, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                    
                    $this->db->query($query, [
                        'ai_tool',
                        $toolId,
                        'logo',
                        $logoUrl,
                        isset($this->request['logoWidth']) ? (int)$this->request['logoWidth'] : 200,
                        isset($this->request['logoHeight']) ? (int)$this->request['logoHeight'] : 200,
                        isset($this->request['logoAlt']) ? Validator::sanitizeString($this->request['logoAlt']) : $tool['name'],
                        date('Y-m-d H:i:s')
                    ]);
                }
            }
            
            // Commit transaction
            $this->db->commit();
            
            // Return the updated AI tool
            $this->params['id'] = $toolId;
            $this->show();
        } catch (\Exception $e) {
            // Rollback transaction
            $this->db->rollback();
            $this->serverError('Failed to update AI tool: ' . $e->getMessage());
        }
    }
    
    /**
     * Delete an AI tool
     */
    public function delete() {
        // Validate AI tool ID
        $toolId = isset($this->params['id']) ? (int)$this->params['id'] : null;
        
        if (!$toolId) {
            $this->badRequest('AI tool ID is required');
            return;
        }
        
        try {
            // Check if AI tool exists
            $query = "SELECT * FROM ai_tools WHERE id = ? LIMIT 1";
            $stmt = $this->db->query($query, [$toolId]);
            
            if ($stmt->rowCount() === 0) {
                $this->notFound('AI tool not found');
                return;
            }
            
            // Start transaction
            $this->db->beginTransaction();
            
            // Delete AI tool media
            $query = "DELETE FROM media WHERE entity_type = 'ai_tool' AND entity_id = ?";
            $this->db->query($query, [$toolId]);
            
            // Delete AI tool
            $query = "DELETE FROM ai_tools WHERE id = ?";
            $this->db->query($query, [$toolId]);
            
            // Commit transaction
            $this->db->commit();
            
            // Send success response
            Response::sendSuccess(['message' => 'AI tool deleted successfully'], [], 200);
        } catch (\Exception $e) {
            // Rollback transaction
            $this->db->rollback();
            $this->serverError('Failed to delete AI tool: ' . $e->getMessage());
        }
    }
}