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
        $allowedSortFields = ['title', 'created_at', 'rating'];
        $sort = $this->getSortParams($allowedSortFields);
        $sortField = $sort['field'] ?? 'created_at';
        $sortDirection = $sort['direction'] ?? 'DESC';
        $sortClause = "ORDER BY $sortField $sortDirection";
        
        // Get filter parameters
        $allowedFilterFields = ['title', 'slug', 'featured', 'is_published', 'category_id', 'pricing_type'];
        $filters = $this->getFilterParams($allowedFilterFields);
        
        try {
            // Build the WHERE clause
            $whereData = $this->buildWhereClause($filters);
            $whereClause = $whereData['clause'];
            $params = $whereData['params'];
            
            // Count total records
            $countQuery = "SELECT COUNT(*) as total FROM ai_tools $whereClause";
            $stmt = $this->db->query($countQuery, $params);
            $total = $stmt->fetch()['total'];
            
            // Get AI tools with pagination
            $query = "SELECT
                t.id, t.title, t.description, t.slug, t.featured, t.is_published,
                t.category_id, t.tool_url, t.pricing_type, t.price_info, t.features, t.rating,
                t.published_at AS publishedAt, t.created_at AS createdAt, t.updated_at AS updatedAt,
                c.name AS category_name, c.slug AS category_slug
                FROM ai_tools t
                LEFT JOIN ai_tool_categories c ON t.category_id = c.id
                $whereClause
                $sortClause
                LIMIT $offset, $pageSize";
            
            $stmt = $this->db->query($query, $params);
            $tools = $stmt->fetchAll();
            
            // Format AI tools with the expected structure
            $formattedTools = [];
            foreach ($tools as $tool) {
                $category = null;
                if ($tool['category_id']) {
                    $category = [
                        'id' => $tool['category_id'],
                        'name' => $tool['category_name'],
                        'slug' => $tool['category_slug']
                    ];
                }
                
                // Parse features if it's a string
                $features = $tool['features'];
                if (is_string($features) && !empty($features)) {
                    $features = explode("\n", $features);
                } elseif (empty($features)) {
                    $features = [];
                }
                
                $formattedTools[] = [
                    'id' => $tool['id'],
                    'attributes' => [
                        'title' => $tool['title'],
                        'description' => $tool['description'],
                        'slug' => $tool['slug'],
                        'toolUrl' => $tool['tool_url'],
                        'pricingType' => $tool['pricing_type'],
                        'priceInfo' => $tool['price_info'],
                        'features' => $features,
                        'rating' => (float)$tool['rating'],
                        'featured' => (bool)$tool['featured'],
                        'isPublished' => (bool)$tool['is_published'],
                        'publishedAt' => $tool['publishedAt'],
                        'createdAt' => $tool['createdAt'],
                        'updatedAt' => $tool['updatedAt'],
                        'category' => $category
                    ]
                ];
            }
            
            // Send paginated response
            Response::sendPaginated($formattedTools, $page, $pageSize, $total);
        } catch (\Exception $e) {
            $this->serverError('Failed to fetch AI tools: ' . $e->getMessage());
        }
    }
    
    /**
     * Get a single AI tool by ID or slug
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
            $column = 't.id';
            $value = (int)$identifier;
        } else {
            $column = 't.slug';
            $value = Validator::sanitizeString($identifier);
        }
        
        try {
            $query = "SELECT
                t.id, t.title, t.description, t.slug, t.featured, t.is_published,
                t.category_id, t.tool_url, t.pricing_type, t.price_info, t.features, t.rating,
                t.published_at AS publishedAt, t.created_at AS createdAt, t.updated_at AS updatedAt,
                c.name AS category_name, c.slug AS category_slug
                FROM ai_tools t
                LEFT JOIN ai_tool_categories c ON t.category_id = c.id
                WHERE $column = ?
                LIMIT 1";
            $stmt = $this->db->query($query, [$value]);
            $tool = $stmt->fetch();
            
            if (!$tool) {
                Response::sendError('AI tool not found', 404);
                return;
            }
            
            // Format AI tool with the expected structure
            $category = null;
            if ($tool['category_id']) {
                $category = [
                    'id' => $tool['category_id'],
                    'name' => $tool['category_name'],
                    'slug' => $tool['category_slug']
                ];
            }
            
            // Parse features if it's a string
            $features = $tool['features'];
            if (is_string($features) && !empty($features)) {
                $features = explode("\n", $features);
            } elseif (empty($features)) {
                $features = [];
            }
            
            $formattedTool = [
                'id' => $tool['id'],
                'attributes' => [
                    'title' => $tool['title'],
                    'description' => $tool['description'],
                    'slug' => $tool['slug'],
                    'toolUrl' => $tool['tool_url'],
                    'pricingType' => $tool['pricing_type'],
                    'priceInfo' => $tool['price_info'],
                    'features' => $features,
                    'rating' => (float)$tool['rating'],
                    'featured' => (bool)$tool['featured'],
                    'isPublished' => (bool)$tool['is_published'],
                    'publishedAt' => $tool['publishedAt'],
                    'createdAt' => $tool['createdAt'],
                    'updatedAt' => $tool['updatedAt'],
                    'category' => $category
                ]
            ];
            
            Response::sendSuccess($formattedTool);
        } catch (\Exception $e) {
            $this->serverError('Failed to fetch AI tool: ' . $e->getMessage());
        }
    }
    
    /**
     * Create a new AI tool
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
        $toolUrl = $this->request['tool_url'] ?? '';
        $pricingType = $this->request['pricing_type'] ?? 'free';
        $priceInfo = $this->request['price_info'] ?? '';
        $features = $this->request['features'] ?? '';
        
        // If features is an array, convert it to a string
        if (is_array($features)) {
            $features = implode("\n", $features);
        }
        
        $rating = isset($this->request['rating']) ? (float)$this->request['rating'] : 0;
        $featured = isset($this->request['featured']) ? (bool)$this->request['featured'] : false;
        $isPublished = isset($this->request['is_published']) ? (bool)$this->request['is_published'] : false;
        $publishedAt = isset($this->request['published_at']) ? $this->request['published_at'] : null;
        
        try {
            // Check if slug already exists
            $query = "SELECT id FROM ai_tools WHERE slug = ? LIMIT 1";
            $stmt = $this->db->query($query, [$slug]);
            
            if ($stmt->rowCount() > 0) {
                // Generate a unique slug
                $slug = $this->generateUniqueSlug($slug);
            }
            
            // Check if category exists if provided
            if ($categoryId) {
                $query = "SELECT id FROM ai_tool_categories WHERE id = ? LIMIT 1";
                $stmt = $this->db->query($query, [$categoryId]);
                
                if ($stmt->rowCount() === 0) {
                    $this->badRequest('Category not found');
                    return;
                }
            }
            
            // Insert AI tool
            $query = "INSERT INTO ai_tools (
                title, description, slug, category_id, tool_url, pricing_type, price_info, features,
                rating, featured, is_published, published_at, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
            
            $this->db->query($query, [
                $title,
                $description,
                $slug,
                $categoryId,
                $toolUrl,
                $pricingType,
                $priceInfo,
                $features,
                $rating,
                $featured ? 1 : 0,
                $isPublished ? 1 : 0,
                $publishedAt
            ]);
            
            $toolId = $this->db->lastInsertId();
            
            // Return the created AI tool
            $this->params['id'] = $toolId;
            $this->show();
        } catch (\Exception $e) {
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
                    $query = "SELECT id FROM ai_tools WHERE slug = ? AND id != ? LIMIT 1";
                    $stmt = $this->db->query($query, [$slug, $toolId]);
                    
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
                $query = "SELECT id FROM ai_tools WHERE slug = ? AND id != ? LIMIT 1";
                $stmt = $this->db->query($query, [$slug, $toolId]);
                
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
                    $query = "SELECT id FROM ai_tool_categories WHERE id = ? LIMIT 1";
                    $stmt = $this->db->query($query, [$categoryId]);
                    
                    if ($stmt->rowCount() === 0) {
                        $this->badRequest('Category not found');
                        return;
                    }
                }
                
                $updates[] = "category_id = ?";
                $params[] = $categoryId;
            }
            
            if (isset($this->request['tool_url'])) {
                $updates[] = "tool_url = ?";
                $params[] = $this->request['tool_url'];
            }
            
            if (isset($this->request['pricing_type'])) {
                $updates[] = "pricing_type = ?";
                $params[] = $this->request['pricing_type'];
            }
            
            if (isset($this->request['price_info'])) {
                $updates[] = "price_info = ?";
                $params[] = $this->request['price_info'];
            }
            
            if (isset($this->request['features'])) {
                $features = $this->request['features'];
                
                // If features is an array, convert it to a string
                if (is_array($features)) {
                    $features = implode("\n", $features);
                }
                
                $updates[] = "features = ?";
                $params[] = $features;
            }
            
            if (isset($this->request['rating'])) {
                $updates[] = "rating = ?";
                $params[] = (float)$this->request['rating'];
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
            
            // If no updates, return the AI tool
            if (empty($updates)) {
                $this->params['id'] = $toolId;
                $this->show();
                return;
            }
            
            // Add AI tool ID to params
            $params[] = $toolId;
            
            // Update AI tool
            $query = "UPDATE ai_tools SET " . implode(", ", $updates) . " WHERE id = ?";
            $this->db->query($query, $params);
            
            // Return the updated AI tool
            $this->params['id'] = $toolId;
            $this->show();
        } catch (\Exception $e) {
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
            $query = "SELECT id FROM ai_tools WHERE id = ? LIMIT 1";
            $stmt = $this->db->query($query, [$toolId]);
            
            if ($stmt->rowCount() === 0) {
                $this->notFound('AI tool not found');
                return;
            }
            
            // Delete AI tool
            $query = "DELETE FROM ai_tools WHERE id = ?";
            $this->db->query($query, [$toolId]);
            
            // Return success
            Response::sendSuccess(['id' => $toolId, 'deleted' => true]);
        } catch (\Exception $e) {
            $this->serverError('Failed to delete AI tool: ' . $e->getMessage());
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
            $query = "SELECT id FROM ai_tools WHERE slug = ? LIMIT 1";
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