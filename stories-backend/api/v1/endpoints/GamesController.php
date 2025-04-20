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
        $allowedSortFields = ['title', 'created_at'];
        $sort = $this->getSortParams($allowedSortFields);
        $sortField = $sort['field'] ?? 'created_at';
        $sortDirection = $sort['direction'] ?? 'DESC';
        $sortClause = "ORDER BY $sortField $sortDirection";
        
        // Get filter parameters
        $allowedFilterFields = ['title', 'slug', 'featured', 'is_published'];
        $filters = $this->getFilterParams($allowedFilterFields);
        
        try {
            echo "<p>Building WHERE clause...</p>";
            // Build the WHERE clause
            $whereData = $this->buildWhereClause($filters);
            $whereClause = $whereData['clause'];
            $params = $whereData['params'];
            echo "<p>WHERE clause built: $whereClause with params: " . json_encode($params) . "</p>";
            
            // Count total records
            $countQuery = "SELECT COUNT(*) as total FROM games $whereClause";
            echo "<p>Executing count query: $countQuery with params: " . json_encode($params) . "</p>";
            $stmt = $this->db->query($countQuery, $params);
            $total = $stmt->fetch()['total'];
            echo "<p>Total records: $total</p>";
            
            // Get games with pagination
            $query = "SELECT
                id, title, description, slug, featured, is_published,
                published_at AS publishedAt, created_at AS createdAt, updated_at AS updatedAt
                FROM games
                $whereClause
                $sortClause
                LIMIT $offset, $pageSize";
            
            echo "<p>Executing data query: $query with params: " . json_encode($params) . "</p>";
            $stmt = $this->db->query($query, $params);
            $games = $stmt->fetchAll();
            echo "<p>Fetched games: " . json_encode($games) . "</p>";
            
            // Format games with the expected structure
            $formattedGames = Response::formatData($games);
            echo "<p>Formatted games: " . json_encode($formattedGames) . "</p>";
            
            // Send paginated response
            Response::sendPaginated($formattedGames, $page, $pageSize, $total);
        } catch (\Exception $e) {
            echo "<p>Error fetching games: " . $e->getMessage() . "</p>";
            $this->serverError('Failed to fetch games: ' . $e->getMessage());
        }
    }
    
    /**
     * Get a single game by ID or slug
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
            $column = 'id';
            $value = (int)$identifier;
        } else {
            $column = 'slug';
            $value = Validator::sanitizeString($identifier);
        }
        
        try {
            $query = "SELECT
                id, title, description, slug, featured, is_published,
                published_at AS publishedAt, created_at AS createdAt, updated_at AS updatedAt
                FROM games
                WHERE $column = ?
                LIMIT 1";
            $stmt = $this->db->query($query, [$value]);
            $game = $stmt->fetch();
            
            if (!$game) {
                Response::sendError('Game not found', 404);
                return;
            }
            
            // Format game with the expected structure
            $formattedGame = [
                'id' => $game['id'],
                'attributes' => [
                    'title' => $game['title'],
                    'description' => $game['description'],
                    'slug' => $game['slug'],
                    'featured' => (bool)$game['featured'],
                    'isPublished' => (bool)$game['is_published'],
                    'publishedAt' => $game['publishedAt'],
                    'createdAt' => $game['createdAt'],
                    'updatedAt' => $game['updatedAt']
                ]
            ];
            
            Response::sendSuccess($formattedGame);
        } catch (\Exception $e) {
            $this->serverError('Failed to fetch game: ' . $e->getMessage());
        }
    }
    
    /**
     * Create a new game
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
        $featured = isset($this->request['featured']) ? (bool)$this->request['featured'] : false;
        $isPublished = isset($this->request['is_published']) ? (bool)$this->request['is_published'] : false;
        $publishedAt = isset($this->request['published_at']) ? $this->request['published_at'] : null;
        
        try {
            // Check if slug already exists
            $query = "SELECT id FROM games WHERE slug = ? LIMIT 1";
            $stmt = $this->db->query($query, [$slug]);
            
            if ($stmt->rowCount() > 0) {
                // Generate a unique slug
                $slug = $this->generateUniqueSlug($slug);
            }
            
            // Insert game
            $query = "INSERT INTO games (
                title, description, slug, featured, is_published,
                published_at, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())";
            
            $this->db->query($query, [
                $title,
                $description,
                $slug,
                $featured ? 1 : 0,
                $isPublished ? 1 : 0,
                $publishedAt
            ]);
            
            $gameId = $this->db->lastInsertId();
            
            // Return the created game
            $this->params['id'] = $gameId;
            $this->show();
        } catch (\Exception $e) {
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
                    $query = "SELECT id FROM games WHERE slug = ? AND id != ? LIMIT 1";
                    $stmt = $this->db->query($query, [$slug, $gameId]);
                    
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
                $query = "SELECT id FROM games WHERE slug = ? AND id != ? LIMIT 1";
                $stmt = $this->db->query($query, [$slug, $gameId]);
                
                if ($stmt->rowCount() > 0) {
                    // Generate a unique slug
                    $slug = $this->generateUniqueSlug($slug);
                }
                
                $updates[] = "slug = ?";
                $params[] = $slug;
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
            
            // If no updates, return the game
            if (empty($updates)) {
                $this->params['id'] = $gameId;
                $this->show();
                return;
            }
            
            // Add game ID to params
            $params[] = $gameId;
            
            // Update game
            $query = "UPDATE games SET " . implode(", ", $updates) . " WHERE id = ?";
            $this->db->query($query, $params);
            
            // Return the updated game
            $this->params['id'] = $gameId;
            $this->show();
        } catch (\Exception $e) {
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
            $query = "SELECT id FROM games WHERE id = ? LIMIT 1";
            $stmt = $this->db->query($query, [$gameId]);
            
            if ($stmt->rowCount() === 0) {
                $this->notFound('Game not found');
                return;
            }
            
            // Delete game
            $query = "DELETE FROM games WHERE id = ?";
            $this->db->query($query, [$gameId]);
            
            // Return success
            Response::sendSuccess(['id' => $gameId, 'deleted' => true]);
        } catch (\Exception $e) {
            $this->serverError('Failed to delete game: ' . $e->getMessage());
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
            $query = "SELECT id FROM games WHERE slug = ? LIMIT 1";
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
