<?php
/**
 * Authors Controller
 * 
 * This controller handles CRUD operations for authors.
 * 
 * @package Stories API
 * @version 1.0.0
 */

namespace StoriesAPI\Endpoints;

use StoriesAPI\Core\BaseController;
use StoriesAPI\Utils\Response;
use StoriesAPI\Utils\Validator;

class AuthorsController extends BaseController {
    /**
     * Get all authors with pagination, filtering, and sorting
     */
    public function index() {
        // Get pagination parameters
        $pagination = $this->getPaginationParams();
        $page = $pagination['page'];
        $pageSize = $pagination['pageSize'];
        $offset = ($page - 1) * $pageSize;
        
        // Get sort parameters
        $allowedSortFields = ['name', 'storyCount'];
        $sort = $this->getSortParams($allowedSortFields);
        $sortClause = $sort ? "ORDER BY {$sort['field']} {$sort['direction']}" : "ORDER BY name ASC";
        
        // Get filter parameters
        $allowedFilterFields = ['name', 'slug'];
        $filters = $this->getFilterParams($allowedFilterFields);
        
        try {
            // Build the WHERE clause
            $whereData = $this->buildWhereClause($filters);
            $whereClause = $whereData['clause'];
            $params = $whereData['params'];
            
            // Count total records
            $countQuery = "SELECT COUNT(*) as total FROM authors a $whereClause";
            $stmt = $this->db->query($countQuery, $params);
            $total = $stmt->fetch()['total'];
            
            // Get authors with pagination
            $query = "SELECT 
                a.id, a.name, a.slug, a.bio,
                (SELECT COUNT(*) FROM story_authors sa WHERE sa.author_id = a.id) as storyCount
                FROM authors a
                $whereClause
                $sortClause
                LIMIT $offset, $pageSize";
            
            $stmt = $this->db->query($query, $params);
            $authors = $stmt->fetchAll();
            
            // Format authors with a simplified structure to avoid JSON encoding issues
            $formattedAuthors = [];
            
            foreach ($authors as $author) {
                // Get author avatar
                $avatarQuery = "SELECT id, url, width, height, alt_text FROM media WHERE entity_type = 'author' AND entity_id = ? AND type = 'avatar' LIMIT 1";
                $avatarStmt = $this->db->query($avatarQuery, [$author['id']]);
                $avatar = $avatarStmt->fetch();
                
                $formattedAvatar = null;
                if ($avatar) {
                    $formattedAvatar = [
                        'data' => [
                            'id' => $avatar['id'],
                            'attributes' => [
                                'url' => $avatar['url'],
                                'width' => $avatar['width'],
                                'height' => $avatar['height'],
                                'alternativeText' => $avatar['alt_text']
                            ]
                        ]
                    ];
                }
                
                $formattedAuthors[] = [
                    'id' => $author['id'],
                    'attributes' => [
                        'name' => $author['name'],
                        'slug' => $author['slug'],
                        'bio' => $author['bio'],
                        'storyCount' => (int)$author['storyCount'],
                        'avatar' => $formattedAvatar
                    ]
                ];
            }
            
            // Send paginated response
            Response::sendPaginated($formattedAuthors, $page, $pageSize, $total);
        } catch (\Exception $e) {
            $this->serverError('Failed to fetch authors: ' . $e->getMessage());
        }
    }
    
    /**
     * Get a single author by slug or numeric ID
     */
    public function show() {
        // Grab the placeholder (named "id" by the router)
        $identifier = $this->params['id'] ?? null;
        if (!$identifier) {
            $this->badRequest('No identifier provided');
            return;
        }
        
        // Log the identifier for debugging
        error_log("AuthorsController::show - Identifier: " . $identifier);
        error_log("AuthorsController::show - Params: " . json_encode($this->params));

        // Decide whether this is an ID or a slug
        if (ctype_digit($identifier)) {
            $column = 'a.id';
            $value  = (int)$identifier;
        } else {
            $column = 'a.slug';
            // sanitize as before
            $value  = Validator::sanitizeString($identifier);
        }
        
        try {
            // Get author by identifier
            $query = "SELECT
                a.id, a.name, a.slug, a.bio, a.created_at as createdAt, a.updated_at as updatedAt
                FROM authors a
                WHERE $column = ? LIMIT 1";
            
            $stmt = $this->db->query($query, [$value]);
            
            if ($stmt->rowCount() === 0) {
                $this->notFound('Author not found');
                return;
            }
            
            $author = $stmt->fetch();
            $authorId = $author['id'];
            
            // Get author avatar
            $avatarQuery = "SELECT id, url, width, height, alt_text FROM media WHERE entity_type = 'author' AND entity_id = ? AND type = 'avatar' LIMIT 1";
            $avatarStmt = $this->db->query($avatarQuery, [$authorId]);
            $avatar = $avatarStmt->fetch();
            
            $formattedAvatar = null;
            if ($avatar) {
                $formattedAvatar = [
                    'data' => [
                        'id' => $avatar['id'],
                        'attributes' => [
                            'url' => $avatar['url'],
                            'width' => $avatar['width'],
                            'height' => $avatar['height'],
                            'alternativeText' => $avatar['alt_text']
                        ]
                    ]
                ];
            }
            
            // Get stories by this author
            $storiesQuery = "SELECT 
                s.id, s.title, s.slug, s.excerpt, s.published_at as publishedAt,
                s.featured, s.average_rating as averageRating
                FROM stories s
                JOIN story_authors sa ON s.id = sa.story_id
                WHERE sa.author_id = ?
                ORDER BY s.published_at DESC";
            
            $storiesStmt = $this->db->query($storiesQuery, [$authorId]);
            $stories = $storiesStmt->fetchAll();
            
            // Format stories with simplified structure
            $simpleStories = [];
            foreach ($stories as $story) {
                $storyId = $story['id'];
                
                // Get cover URL if exists
                $coverUrl = null;
                $coverQuery = "SELECT url FROM media WHERE entity_type = 'story' AND entity_id = ? AND type = 'cover' LIMIT 1";
                $coverStmt = $this->db->query($coverQuery, [$storyId]);
                $cover = $coverStmt->fetch();
                if ($cover) {
                    $coverUrl = $cover['url'];
                }
                
                // Format story with simplified structure
                $simpleStories[] = [
                    'id' => $storyId,
                    'title' => $story['title'],
                    'slug' => $story['slug'],
                    'excerpt' => $story['excerpt'],
                    'publishedAt' => $story['publishedAt'],
                    'featured' => (bool)$story['featured'],
                    'averageRating' => (float)$story['averageRating'],
                    'coverUrl' => $coverUrl
                ];
            }
            
            // Count stories
            $storyCount = count($simpleStories);
            
            // Build the formatted author with proper structure
            $formattedAuthor = [
                'id' => $authorId,
                'attributes' => [
                    'name' => $author['name'],
                    'slug' => $author['slug'],
                    'bio' => $author['bio'],
                    'createdAt' => $author['createdAt'],
                    'updatedAt' => $author['updatedAt'],
                    'storyCount' => $storyCount,
                    'avatar' => $formattedAvatar,
                    'stories' => $simpleStories
                ]
            ];
            
            // Send response
            Response::sendSuccess($formattedAuthor);
        } catch (\Exception $e) {
            $this->serverError('Failed to fetch author: ' . $e->getMessage());
        }
    }
    
    /**
     * Create a new author
     */
    public function create() {
        // Validate required fields
        if (!Validator::required($this->request, ['name'])) {
            $this->badRequest('Author name is required', Validator::getErrors());
            return;
        }
        
        // Validate name length
        if (!Validator::length($this->request['name'], 'name', 2, 100)) {
            $this->badRequest('Author name must be between 2 and 100 characters', Validator::getErrors());
            return;
        }
        
        // Sanitize input
        $name = Validator::sanitizeString($this->request['name']);
        $slug = isset($this->request['slug']) ? Validator::sanitizeString($this->request['slug']) : $this->generateSlug($name);
        $bio = isset($this->request['bio']) ? $this->request['bio'] : null;
        
        try {
            // Check if slug already exists
            $query = "SELECT id FROM authors WHERE slug = ? LIMIT 1";
            $stmt = $this->db->query($query, [$slug]);
            
            if ($stmt->rowCount() > 0) {
                // Generate a unique slug
                $slug = $this->generateUniqueSlug($slug);
            }
            
            // Insert author
            $query = "INSERT INTO authors (name, slug, bio, created_at, updated_at) VALUES (?, ?, ?, ?, ?)";
            $this->db->query($query, [
                $name,
                $slug,
                $bio,
                date('Y-m-d H:i:s'),
                date('Y-m-d H:i:s')
            ]);
            
            $authorId = $this->db->lastInsertId();
            
            // Handle avatar if provided
            $avatarUrl = null;
            if (isset($this->request['avatar']) && !empty($this->request['avatar'])) {
                $avatarUrl = Validator::sanitizeString($this->request['avatar']);
                $query = "INSERT INTO media (
                    entity_type, entity_id, type, url, width, height, alt_text, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                
                $this->db->query($query, [
                    'author',
                    $authorId,
                    'avatar',
                    $avatarUrl,
                    isset($this->request['avatarWidth']) ? (int)$this->request['avatarWidth'] : 300,
                    isset($this->request['avatarHeight']) ? (int)$this->request['avatarHeight'] : 300,
                    isset($this->request['avatarAlt']) ? Validator::sanitizeString($this->request['avatarAlt']) : $name,
                    date('Y-m-d H:i:s')
                ]);
            }
            
            // Return the created author with proper structure
            $formattedAuthor = [
                'id' => $authorId,
                'attributes' => [
                    'name' => $name,
                    'slug' => $slug,
                    'bio' => $bio,
                    'storyCount' => 0,
                    'avatar' => $avatarUrl ? [
                        'data' => [
                            'id' => $this->db->lastInsertId(),
                            'attributes' => [
                                'url' => $avatarUrl,
                                'width' => isset($this->request['avatarWidth']) ? (int)$this->request['avatarWidth'] : 300,
                                'height' => isset($this->request['avatarHeight']) ? (int)$this->request['avatarHeight'] : 300,
                                'alternativeText' => isset($this->request['avatarAlt']) ? Validator::sanitizeString($this->request['avatarAlt']) : $name
                            ]
                        ]
                    ] : null
                ]
            ];
            
            Response::sendSuccess($formattedAuthor, [], 201);
        } catch (\Exception $e) {
            $this->serverError('Failed to create author: ' . $e->getMessage());
        }
    }
    
    /**
     * Update an author
     */
    public function update() {
        // Validate author ID
        $authorId = isset($this->params['id']) ? (int)$this->params['id'] : null;
        
        if (!$authorId) {
            $this->badRequest('Author ID is required');
            return;
        }
        
        try {
            // Check if author exists
            $query = "SELECT * FROM authors WHERE id = ? LIMIT 1";
            $stmt = $this->db->query($query, [$authorId]);
            
            if ($stmt->rowCount() === 0) {
                $this->notFound('Author not found');
                return;
            }
            
            $author = $stmt->fetch();
            
            // Build update query
            $updates = [];
            $params = [];
            
            // Update name if provided
            if (isset($this->request['name'])) {
                if (!Validator::length($this->request['name'], 'name', 2, 100)) {
                    $this->badRequest('Author name must be between 2 and 100 characters', Validator::getErrors());
                    return;
                }
                
                $name = Validator::sanitizeString($this->request['name']);
                $updates[] = "name = ?";
                $params[] = $name;
                
                // Update slug if name is changed and slug is not provided
                if (!isset($this->request['slug'])) {
                    $slug = $this->generateSlug($name);
                    
                    // Check if slug already exists
                    $query = "SELECT id FROM authors WHERE slug = ? AND id != ? LIMIT 1";
                    $stmt = $this->db->query($query, [$slug, $authorId]);
                    
                    if ($stmt->rowCount() > 0) {
                        // Generate a unique slug
                        $slug = $this->generateUniqueSlug($slug);
                    }
                    
                    $updates[] = "slug = ?";
                    $params[] = $slug;
                }
            }
            
            // Update slug if provided
            if (isset($this->request['slug'])) {
                $slug = Validator::sanitizeString($this->request['slug']);
                
                // Check if slug already exists
                $query = "SELECT id FROM authors WHERE slug = ? AND id != ? LIMIT 1";
                $stmt = $this->db->query($query, [$slug, $authorId]);
                
                if ($stmt->rowCount() > 0) {
                    // Generate a unique slug
                    $slug = $this->generateUniqueSlug($slug);
                }
                
                $updates[] = "slug = ?";
                $params[] = $slug;
            }
            
            // Update bio if provided
            if (isset($this->request['bio'])) {
                $updates[] = "bio = ?";
                $params[] = $this->request['bio'];
            }
            
            // If no updates, check if avatar is provided
            if (empty($updates) && !isset($this->request['avatar'])) {
                $this->params['slug'] = $author['slug'];
                $this->show();
                return;
            }
            
            // Add updated_at
            $updates[] = "updated_at = ?";
            $params[] = date('Y-m-d H:i:s');
            
            // Add author ID to params
            $params[] = $authorId;
            
            // Update author
            if (!empty($updates)) {
                $query = "UPDATE authors SET " . implode(', ', $updates) . " WHERE id = ?";
                $this->db->query($query, $params);
            }
            
            // Handle avatar if provided
            if (isset($this->request['avatar']) && !empty($this->request['avatar'])) {
                $avatarUrl = Validator::sanitizeString($this->request['avatar']);
                
                // Check if avatar already exists
                $query = "SELECT id FROM media WHERE entity_type = 'author' AND entity_id = ? AND type = 'avatar' LIMIT 1";
                $stmt = $this->db->query($query, [$authorId]);
                
                if ($stmt->rowCount() > 0) {
                    // Update existing avatar
                    $avatarId = $stmt->fetch()['id'];
                    $query = "UPDATE media SET 
                        url = ?, 
                        width = ?, 
                        height = ?, 
                        alt_text = ? 
                        WHERE id = ?";
                    
                    $this->db->query($query, [
                        $avatarUrl,
                        isset($this->request['avatarWidth']) ? (int)$this->request['avatarWidth'] : 300,
                        isset($this->request['avatarHeight']) ? (int)$this->request['avatarHeight'] : 300,
                        isset($this->request['avatarAlt']) ? Validator::sanitizeString($this->request['avatarAlt']) : $author['name'],
                        $avatarId
                    ]);
                } else {
                    // Insert new avatar
                    $query = "INSERT INTO media (
                        entity_type, entity_id, type, url, width, height, alt_text, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                    
                    $this->db->query($query, [
                        'author',
                        $authorId,
                        'avatar',
                        $avatarUrl,
                        isset($this->request['avatarWidth']) ? (int)$this->request['avatarWidth'] : 300,
                        isset($this->request['avatarHeight']) ? (int)$this->request['avatarHeight'] : 300,
                        isset($this->request['avatarAlt']) ? Validator::sanitizeString($this->request['avatarAlt']) : $author['name'],
                        date('Y-m-d H:i:s')
                    ]);
                }
            }
            
            // Return the updated author
            $this->params['slug'] = isset($slug) ? $slug : $author['slug'];
            $this->show();
        } catch (\Exception $e) {
            $this->serverError('Failed to update author: ' . $e->getMessage());
        }
    }
    
    /**
     * Delete an author
     */
    public function delete() {
        // Validate author ID
        $authorId = isset($this->params['id']) ? (int)$this->params['id'] : null;
        
        if (!$authorId) {
            $this->badRequest('Author ID is required');
            return;
        }
        
        try {
            // Check if author exists
            $query = "SELECT * FROM authors WHERE id = ? LIMIT 1";
            $stmt = $this->db->query($query, [$authorId]);
            
            if ($stmt->rowCount() === 0) {
                $this->notFound('Author not found');
                return;
            }
            
            // Start transaction
            $this->db->beginTransaction();
            
            // Delete author associations
            $query = "DELETE FROM story_authors WHERE author_id = ?";
            $this->db->query($query, [$authorId]);
            
            // Delete author media
            $query = "DELETE FROM media WHERE entity_type = 'author' AND entity_id = ?";
            $this->db->query($query, [$authorId]);
            
            // Delete author
            $query = "DELETE FROM authors WHERE id = ?";
            $this->db->query($query, [$authorId]);
            
            // Commit transaction
            $this->db->commit();
            
            // Send success response
            Response::sendSuccess(['message' => 'Author deleted successfully'], [], 200);
        } catch (\Exception $e) {
            // Rollback transaction
            $this->db->rollback();
            $this->serverError('Failed to delete author: ' . $e->getMessage());
        }
    }
    
    /**
     * Generate a slug from a name
     * 
     * @param string $name The name to generate a slug from
     * @return string The generated slug
     */
    private function generateSlug($name) {
        // Convert to lowercase
        $slug = strtolower($name);
        
        // Replace non-alphanumeric characters with hyphens
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        
        // Remove leading and trailing hyphens
        $slug = trim($slug, '-');
        
        return $slug;
    }
    
    /**
     * Generate a unique slug
     * 
     * @param string $slug The base slug
     * @return string A unique slug
     */
    private function generateUniqueSlug($slug) {
        $originalSlug = $slug;
        $counter = 1;
        
        while (true) {
            // Check if slug exists
            $query = "SELECT id FROM authors WHERE slug = ? LIMIT 1";
            $stmt = $this->db->query($query, [$slug]);
            
            if ($stmt->rowCount() === 0) {
                break;
            }
            
            // Append counter to slug
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }
}
