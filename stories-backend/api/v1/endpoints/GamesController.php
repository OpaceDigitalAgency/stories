<?php
/**
 * Games Controller
 * 
 * This controller handles CRUD operations for games.
 * 
 * @package Stories API
 * @version 1.0.0
 */

namespace StoriesAPI\Endpoints;

use StoriesAPI\Core\BaseController;
use StoriesAPI\Utils\Response;
use StoriesAPI\Utils\Validator;

class GamesController extends BaseController {
    /**
     * Get all games with pagination, filtering, and sorting
     */
    public function index() {
        // Get pagination parameters
        $pagination = $this->getPaginationParams();
        $page = $pagination['page'];
        $pageSize = $pagination['pageSize'];
        $offset = ($page - 1) * $pageSize;
        
        // Get sort parameters
        $allowedSortFields = ['title', 'category'];
        $sort = $this->getSortParams($allowedSortFields);
        $sortClause = $sort ? "ORDER BY {$sort['field']} {$sort['direction']}" : "ORDER BY title ASC";
        
        // Get filter parameters
        $allowedFilterFields = ['title', 'category'];
        $filters = $this->getFilterParams($allowedFilterFields);
        
        try {
            // Build the WHERE clause
            $whereData = $this->buildWhereClause($filters);
            $whereClause = $whereData['clause'];
            $params = $whereData['params'];
            
            // Count total records
            $countQuery = "SELECT COUNT(*) as total FROM games g $whereClause";
            $stmt = $this->db->query($countQuery, $params);
            $total = $stmt->fetch()['total'];
            
            // Get games with pagination
            $query = "SELECT 
                g.id, g.title, g.description, g.url, g.category,
                g.created_at as createdAt, g.updated_at as updatedAt
                FROM games g
                $whereClause
                $sortClause
                LIMIT $offset, $pageSize";
            
            $stmt = $this->db->query($query, $params);
            $games = $stmt->fetchAll();
            
            // Format games to match Strapi response format
            $formattedGames = [];
            
            foreach ($games as $game) {
                $gameId = $game['id'];
                
                // Get game thumbnail
                $thumbnailQuery = "SELECT id, url, width, height, alt_text FROM media WHERE entity_type = 'game' AND entity_id = ? AND type = 'thumbnail' LIMIT 1";
                $thumbnailStmt = $this->db->query($thumbnailQuery, [$gameId]);
                $thumbnail = $thumbnailStmt->fetch();
                
                // Format thumbnail
                $formattedThumbnail = null;
                if ($thumbnail) {
                    $formattedThumbnail = [
                        'data' => [
                            'id' => $thumbnail['id'],
                            'attributes' => [
                                'url' => $thumbnail['url'],
                                'width' => $thumbnail['width'],
                                'height' => $thumbnail['height'],
                                'alternativeText' => $thumbnail['alt_text']
                            ]
                        ]
                    ];
                }
                
                // Build the formatted game
                $formattedGames[] = [
                    'id' => $gameId,
                    'attributes' => [
                        'title' => $game['title'],
                        'description' => $game['description'],
                        'url' => $game['url'],
                        'category' => $game['category'],
                        'createdAt' => $game['createdAt'],
                        'updatedAt' => $game['updatedAt'],
                        'thumbnail' => $formattedThumbnail
                    ]
                ];
            }
            
            // Send paginated response
            Response::sendPaginated($formattedGames, $page, $pageSize, $total);
        } catch (\Exception $e) {
            $this->serverError('Failed to fetch games: ' . $e->getMessage());
        }
    }
    
    /**
     * Get a single Game by slug or numeric ID
     */
    public function show() {
        $identifier = $this->params['slug'] ?? null;
        if (!$identifier) {
            Response::sendError('No identifier provided', 400);
            return;
        }

        // Determine whether $identifier is an ID or a slug
        if (ctype_digit($identifier)) {
            $column = 'g.id';
            $value  = (int)$identifier;
        } else {
            $column = 'g.title'; // Use title as the string identifier since there's no slug column
            $value  = Validator::sanitizeString($identifier);
        }

        try {
            // Get Game by identifier
            $query = "
                SELECT
                    g.id, g.title, g.description, g.url, g.category,
                    g.created_at as createdAt, g.updated_at as updatedAt
                FROM games g
                WHERE $column = ?
                LIMIT 1
            ";
            $stmt  = $this->db->query($query, [$value]);
            $game = $stmt->fetch();

            if (!$game) {
                Response::sendError('Game not found', 404);
                return;
            }

            // Format the Game
            $formatted = $this->formatSingleGame($game);
            Response::sendSuccess($formatted);

        } catch (\Exception $e) {
            error_log("GamesController::show() - Exception: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            $this->serverError('Failed to fetch Game: ' . $e->getMessage());
        }
    }

    /**
     * Helper to format a single Game
     */
    private function formatSingleGame(array $game): array {
        $gameId = $game['id'];

        // Get game thumbnail
        $thumbnailQuery = "SELECT id, url, width, height, alt_text FROM media WHERE entity_type = 'game' AND entity_id = ? AND type = 'thumbnail' LIMIT 1";
        $thumbnailStmt = $this->db->query($thumbnailQuery, [$gameId]);
        $thumbnail = $thumbnailStmt->fetch();

        // Format thumbnail
        $formattedThumbnail = null;
        if ($thumbnail) {
            $formattedThumbnail = [
                'data' => [
                    'id' => $thumbnail['id'],
                    'attributes' => [
                        'url' => $thumbnail['url'],
                        'width' => $thumbnail['width'],
                        'height' => $thumbnail['height'],
                        'alternativeText' => $thumbnail['alt_text']
                    ]
                ]
            ];
        }

        // Build the formatted Game
        return [
            'id' => $gameId,
            'attributes' => [
                'title' => $game['title'],
                'slug' => $game['title'], // Use title as slug since there's no slug column
                'description' => $game['description'],
                'url' => $game['url'],
                'category' => $game['category'],
                'createdAt' => $game['createdAt'],
                'updatedAt' => $game['updatedAt'],
                'thumbnail' => $formattedThumbnail
            ]
        ];
    }
    
    /**
     * Create a new game
     */
    public function create() {
        // Validate required fields
        if (!Validator::required($this->request, ['title', 'url'])) {
            $this->badRequest('Title and URL are required', Validator::getErrors());
            return;
        }
        
        // Validate title length
        if (!Validator::length($this->request['title'], 'title', 2, 100)) {
            $this->badRequest('Title must be between 2 and 100 characters', Validator::getErrors());
            return;
        }
        
        // Validate URL
        if (!Validator::url($this->request['url'], 'url')) {
            $this->badRequest('URL must be a valid URL', Validator::getErrors());
            return;
        }
        
        // Sanitize input
        $title = Validator::sanitizeString($this->request['title']);
        $url = Validator::sanitizeString($this->request['url']);
        $description = isset($this->request['description']) ? Validator::sanitizeString($this->request['description']) : '';
        $category = isset($this->request['category']) ? Validator::sanitizeString($this->request['category']) : 'Educational';
        
        try {
            // Start transaction
            $this->db->beginTransaction();
            
            // Insert game
            $query = "INSERT INTO games (
                title, description, url, category, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?)";
            
            $this->db->query($query, [
                $title,
                $description,
                $url,
                $category,
                date('Y-m-d H:i:s'),
                date('Y-m-d H:i:s')
            ]);
            
            $gameId = $this->db->lastInsertId();
            
            // Handle thumbnail if provided
            if (isset($this->request['thumbnail']) && !empty($this->request['thumbnail'])) {
                $thumbnailUrl = Validator::sanitizeString($this->request['thumbnail']);
                $query = "INSERT INTO media (
                    entity_type, entity_id, type, url, width, height, alt_text, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                
                $this->db->query($query, [
                    'game',
                    $gameId,
                    'thumbnail',
                    $thumbnailUrl,
                    isset($this->request['thumbnailWidth']) ? (int)$this->request['thumbnailWidth'] : 300,
                    isset($this->request['thumbnailHeight']) ? (int)$this->request['thumbnailHeight'] : 200,
                    isset($this->request['thumbnailAlt']) ? Validator::sanitizeString($this->request['thumbnailAlt']) : $title,
                    date('Y-m-d H:i:s')
                ]);
            }
            
            // Commit transaction
            $this->db->commit();
            
            // Return the created game
            $this->params['id'] = $gameId;
            $this->show();
        } catch (\Exception $e) {
            // Rollback transaction
            $this->db->rollback();
            $this->serverError('Failed to create game: ' . $e->getMessage());
        }
    }
    
    /**
     * Update a game
     */
    public function update() {
        // Validate game ID
        $gameId = isset($this->params['id']) ? (int)$this->params['id'] : null;
        
        if (!$gameId) {
            $this->badRequest('Game ID is required');
            return;
        }
        
        try {
            // Check if game exists
            $query = "SELECT * FROM games WHERE id = ? LIMIT 1";
            $stmt = $this->db->query($query, [$gameId]);
            
            if ($stmt->rowCount() === 0) {
                $this->notFound('Game not found');
                return;
            }
            
            $game = $stmt->fetch();
            
            // Build update query
            $updates = [];
            $params = [];
            
            // Update fields if provided
            if (isset($this->request['title'])) {
                if (!Validator::length($this->request['title'], 'title', 2, 100)) {
                    $this->badRequest('Title must be between 2 and 100 characters', Validator::getErrors());
                    return;
                }
                
                $updates[] = "title = ?";
                $params[] = Validator::sanitizeString($this->request['title']);
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
            
            // Add game ID to params
            $params[] = $gameId;
            
            // Start transaction
            $this->db->beginTransaction();
            
            // Update game
            if (!empty($updates)) {
                $query = "UPDATE games SET " . implode(', ', $updates) . " WHERE id = ?";
                $this->db->query($query, $params);
            }
            
            // Handle thumbnail if provided
            if (isset($this->request['thumbnail']) && !empty($this->request['thumbnail'])) {
                $thumbnailUrl = Validator::sanitizeString($this->request['thumbnail']);
                
                // Check if thumbnail already exists
                $query = "SELECT id FROM media WHERE entity_type = 'game' AND entity_id = ? AND type = 'thumbnail' LIMIT 1";
                $stmt = $this->db->query($query, [$gameId]);
                
                if ($stmt->rowCount() > 0) {
                    // Update existing thumbnail
                    $thumbnailId = $stmt->fetch()['id'];
                    $query = "UPDATE media SET 
                        url = ?, 
                        width = ?, 
                        height = ?, 
                        alt_text = ? 
                        WHERE id = ?";
                    
                    $this->db->query($query, [
                        $thumbnailUrl,
                        isset($this->request['thumbnailWidth']) ? (int)$this->request['thumbnailWidth'] : 300,
                        isset($this->request['thumbnailHeight']) ? (int)$this->request['thumbnailHeight'] : 200,
                        isset($this->request['thumbnailAlt']) ? Validator::sanitizeString($this->request['thumbnailAlt']) : $game['title'],
                        $thumbnailId
                    ]);
                } else {
                    // Insert new thumbnail
                    $query = "INSERT INTO media (
                        entity_type, entity_id, type, url, width, height, alt_text, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                    
                    $this->db->query($query, [
                        'game',
                        $gameId,
                        'thumbnail',
                        $thumbnailUrl,
                        isset($this->request['thumbnailWidth']) ? (int)$this->request['thumbnailWidth'] : 300,
                        isset($this->request['thumbnailHeight']) ? (int)$this->request['thumbnailHeight'] : 200,
                        isset($this->request['thumbnailAlt']) ? Validator::sanitizeString($this->request['thumbnailAlt']) : $game['title'],
                        date('Y-m-d H:i:s')
                    ]);
                }
            }
            
            // Commit transaction
            $this->db->commit();
            
            // Return the updated game
            $this->params['id'] = $gameId;
            $this->show();
        } catch (\Exception $e) {
            // Rollback transaction
            $this->db->rollback();
            $this->serverError('Failed to update game: ' . $e->getMessage());
        }
    }
    
    /**
     * Delete a game
     */
    public function delete() {
        // Validate game ID
        $gameId = isset($this->params['id']) ? (int)$this->params['id'] : null;
        
        if (!$gameId) {
            $this->badRequest('Game ID is required');
            return;
        }
        
        try {
            // Check if game exists
            $query = "SELECT * FROM games WHERE id = ? LIMIT 1";
            $stmt = $this->db->query($query, [$gameId]);
            
            if ($stmt->rowCount() === 0) {
                $this->notFound('Game not found');
                return;
            }
            
            // Start transaction
            $this->db->beginTransaction();
            
            // Delete game media
            $query = "DELETE FROM media WHERE entity_type = 'game' AND entity_id = ?";
            $this->db->query($query, [$gameId]);
            
            // Delete game
            $query = "DELETE FROM games WHERE id = ?";
            $this->db->query($query, [$gameId]);
            
            // Commit transaction
            $this->db->commit();
            
            // Send success response
            Response::sendSuccess(['message' => 'Game deleted successfully'], [], 200);
        } catch (\Exception $e) {
            // Rollback transaction
            $this->db->rollback();
            $this->serverError('Failed to delete game: ' . $e->getMessage());
        }
    }
}