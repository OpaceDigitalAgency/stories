<?php
namespace StoriesAPI\Endpoints;

use StoriesAPI\Core\BaseController;
use StoriesAPI\Utils\Response;
use StoriesAPI\Utils\Validator;

class GamesController extends BaseController {
    public function index() {
        try {
            // Get pagination parameters
            $pagination = $this->getPaginationParams();
            $page = $pagination['page'];
            $pageSize = $pagination['pageSize'];
            $offset = ($page - 1) * $pageSize;
            
            // Get sort parameters
            $allowedSortFields = ['title', 'created_at', 'rating'];
            $sort = parent::getSortParams($allowedSortFields);
            $sortField = $sort['field'] ?? 'created_at';
            
            // Add table alias to sort field
            $sortField = 'g.' . $sortField;
            
            $sortDirection = $sort['direction'] ?? 'DESC';
            $sortClause = "ORDER BY $sortField $sortDirection";
            
            // Get filter parameters
            $allowedFilterFields = ['title', 'slug', 'genre', 'platform'];
            $filters = $this->getFilterParams($allowedFilterFields);
            
            // Build WHERE clause
            $where = [];
            $params = [];
            
            foreach ($filters as $field => $value) {
                if ($value === '') {
                    continue;
                }
                $where[] = "g.$field LIKE ?";
                $params[] = "%$value%";
            }
            
            $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
            
            // Count total records
            $countQuery = "SELECT COUNT(*) as total FROM games g $whereClause";
            $stmt = $this->db->query($countQuery, $params);
            $total = $stmt->fetch()['total'];
            
            // Get games with pagination
            $query = "SELECT
                g.id, g.title, g.description, g.slug,
                g.genre, g.platform, g.developer, g.publisher,
                g.release_date, g.rating, g.price,
                g.created_at AS createdAt, g.updated_at AS updatedAt
                FROM games g
                $whereClause
                $sortClause
                LIMIT $offset, $pageSize";
            
            $stmt = $this->db->query($query, $params);
            $games = $stmt->fetchAll();
            
            // Format games
            $formattedGames = [];
            foreach ($games as $game) {
                $formattedGames[] = [
                    'id' => $game['id'],
                    'attributes' => [
                        'title' => $game['title'],
                        'description' => $game['description'],
                        'slug' => $game['slug'],
                        'genre' => $game['genre'],
                        'platform' => $game['platform'],
                        'developer' => $game['developer'],
                        'publisher' => $game['publisher'],
                        'releaseDate' => $game['release_date'],
                        'rating' => (float)$game['rating'],
                        'price' => (float)$game['price'],
                        'createdAt' => $game['createdAt'],
                        'updatedAt' => $game['updatedAt']
                    ]
                ];
            }
            
            Response::sendPaginated($formattedGames, $page, $pageSize, $total);
        } catch (\Exception $e) {
            $this->serverError('Failed to fetch games: ' . $e->getMessage());
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
                id, title, description, slug,
                genre, platform, developer, publisher,
                release_date, rating, price,
                created_at AS createdAt, updated_at AS updatedAt
                FROM games
                WHERE $column = ?
                LIMIT 1";
            
            $stmt = $this->db->query($query, [$value]);
            $game = $stmt->fetch();
            
            if (!$game) {
                Response::sendError('Game not found', 404);
                return;
            }
            
            $formattedGame = [
                'id' => $game['id'],
                'attributes' => [
                    'title' => $game['title'],
                    'description' => $game['description'],
                    'slug' => $game['slug'],
                    'genre' => $game['genre'],
                    'platform' => $game['platform'],
                    'developer' => $game['developer'],
                    'publisher' => $game['publisher'],
                    'releaseDate' => $game['release_date'],
                    'rating' => (float)$game['rating'],
                    'price' => (float)$game['price'],
                    'createdAt' => $game['createdAt'],
                    'updatedAt' => $game['updatedAt']
                ]
            ];
            
            Response::sendSuccess($formattedGame);
        } catch (\Exception $e) {
            $this->serverError('Failed to fetch game: ' . $e->getMessage());
        }
    }
    
    public function create() {
        if (!Validator::required($this->request, ['title'])) {
            $this->badRequest('Title is required');
            return;
        }
        
        try {
            $title = Validator::sanitizeString($this->request['title']);
            $description = $this->request['description'] ?? '';
            $slug = isset($this->request['slug']) ? Validator::sanitizeString($this->request['slug']) : $this->generateSlug($title);
            $genre = $this->request['genre'] ?? '';
            $platform = $this->request['platform'] ?? '';
            $developer = $this->request['developer'] ?? '';
            $publisher = $this->request['publisher'] ?? '';
            $releaseDate = $this->request['release_date'] ?? null;
            $rating = isset($this->request['rating']) ? (float)$this->request['rating'] : 0;
            $price = isset($this->request['price']) ? (float)$this->request['price'] : 0;
            
            // Check if slug exists
            $stmt = $this->db->query("SELECT id FROM games WHERE slug = ?", [$slug]);
            if ($stmt->rowCount() > 0) {
                $slug = $this->generateUniqueSlug($slug);
            }
            
            $query = "INSERT INTO games (
                title, description, slug, genre, platform,
                developer, publisher, release_date, rating, price,
                created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
            
            $this->db->query($query, [
                $title,
                $description,
                $slug,
                $genre,
                $platform,
                $developer,
                $publisher,
                $releaseDate,
                $rating,
                $price
            ]);
            
            $gameId = $this->db->lastInsertId();
            
            $this->params['id'] = $gameId;
            $this->show();
        } catch (\Exception $e) {
            $this->serverError('Failed to create game: ' . $e->getMessage());
        }
    }
    
    public function update() {
        $gameId = isset($this->params['id']) ? (int)$this->params['id'] : null;
        if (!$gameId) {
            $this->badRequest('Game ID is required');
            return;
        }
        
        try {
            // Check if game exists
            $stmt = $this->db->query("SELECT id FROM games WHERE id = ?", [$gameId]);
            if ($stmt->rowCount() === 0) {
                $this->notFound('Game not found');
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
                    $stmt = $this->db->query("SELECT id FROM games WHERE slug = ? AND id != ?", [$slug, $gameId]);
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
                $stmt = $this->db->query("SELECT id FROM games WHERE slug = ? AND id != ?", [$slug, $gameId]);
                if ($stmt->rowCount() > 0) {
                    $slug = $this->generateUniqueSlug($slug);
                }
                $updates[] = "slug = ?";
                $params[] = $slug;
            }
            
            if (isset($this->request['genre'])) {
                $updates[] = "genre = ?";
                $params[] = $this->request['genre'];
            }
            
            if (isset($this->request['platform'])) {
                $updates[] = "platform = ?";
                $params[] = $this->request['platform'];
            }
            
            if (isset($this->request['developer'])) {
                $updates[] = "developer = ?";
                $params[] = $this->request['developer'];
            }
            
            if (isset($this->request['publisher'])) {
                $updates[] = "publisher = ?";
                $params[] = $this->request['publisher'];
            }
            
            if (isset($this->request['release_date'])) {
                $updates[] = "release_date = ?";
                $params[] = $this->request['release_date'];
            }
            
            if (isset($this->request['rating'])) {
                $updates[] = "rating = ?";
                $params[] = (float)$this->request['rating'];
            }
            
            if (isset($this->request['price'])) {
                $updates[] = "price = ?";
                $params[] = (float)$this->request['price'];
            }
            
            if (empty($updates)) {
                $this->params['id'] = $gameId;
                $this->show();
                return;
            }
            
            $updates[] = "updated_at = NOW()";
            $params[] = $gameId;
            
            $query = "UPDATE games SET " . implode(", ", $updates) . " WHERE id = ?";
            $this->db->query($query, $params);
            
            $this->params['id'] = $gameId;
            $this->show();
        } catch (\Exception $e) {
            $this->serverError('Failed to update game: ' . $e->getMessage());
        }
    }
    
    public function delete() {
        $gameId = isset($this->params['id']) ? (int)$this->params['id'] : null;
        if (!$gameId) {
            $this->badRequest('Game ID is required');
            return;
        }
        
        try {
            // Check if game exists
            $stmt = $this->db->query("SELECT id FROM games WHERE id = ?", [$gameId]);
            if ($stmt->rowCount() === 0) {
                $this->notFound('Game not found');
                return;
            }
            
            $this->db->query("DELETE FROM games WHERE id = ?", [$gameId]);
            Response::sendSuccess(['id' => $gameId, 'deleted' => true]);
        } catch (\Exception $e) {
            $this->serverError('Failed to delete game: ' . $e->getMessage());
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
            $stmt = $this->db->query("SELECT id FROM games WHERE slug = ?", [$slug]);
            if ($stmt->rowCount() === 0) {
                break;
            }
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }
}