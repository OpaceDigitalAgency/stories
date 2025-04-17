<?php
/**
 * Authors Controller
 * 
 * This controller handles operations for authors.
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
        $allowedSortFields = ['name', 'storyCount', 'featured'];
        $sort = $this->getSortParams($allowedSortFields);
        $sortClause = $sort ? "ORDER BY {$sort['field']} {$sort['direction']}" : "ORDER BY name ASC";
        
        // Get filter parameters
        $allowedFilterFields = ['name', 'slug', 'featured'];
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
                a.id, a.name, a.slug, a.bio, a.featured, a.twitter, a.instagram, a.website,
                a.created_at as createdAt, a.updated_at as updatedAt,
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
                $authorId = $author['id'];
                
                // Get avatar URL if exists
                $avatarUrl = null;
                $avatarQuery = "SELECT url FROM media WHERE entity_type = 'author' AND entity_id = ? AND type = 'avatar' LIMIT 1";
                $avatarStmt = $this->db->query($avatarQuery, [$authorId]);
                $avatar = $avatarStmt->fetch();
                if ($avatar) {
                    $avatarUrl = $avatar['url'];
                }
                
                // Build a simplified author structure
                $formattedAuthor = [
                    'id' => $authorId,
                    'name' => $author['name'],
                    'slug' => $author['slug'],
                    'bio' => $author['bio'],
                    'featured' => (bool)$author['featured'],
                    'twitter' => $author['twitter'],
                    'instagram' => $author['instagram'],
                    'website' => $author['website'],
                    'storyCount' => (int)$author['storyCount'],
                    'createdAt' => $author['createdAt'],
                    'updatedAt' => $author['updatedAt'],
                    'avatarUrl' => $avatarUrl
                ];
                
                $formattedAuthors[] = $formattedAuthor;
            }
            
            // Send paginated response
            Response::sendPaginated($formattedAuthors, $page, $pageSize, $total);
        } catch (\Exception $e) {
            $this->serverError('Failed to fetch authors: ' . $e->getMessage());
        }
    }
    
    /**
     * Get a single author by slug
     */
    public function show() {
        // Validate slug
        $slug = isset($this->params['slug']) ? Validator::sanitizeString($this->params['slug']) : null;
        
        if (!$slug) {
            $this->badRequest('Author slug is required');
            return;
        }
        
        try {
            // Get author by slug
            $query = "SELECT 
                a.id, a.name, a.slug, a.bio, a.featured, a.twitter, a.instagram, a.website,
                a.created_at as createdAt, a.updated_at as updatedAt
                FROM authors a 
                WHERE a.slug = ? LIMIT 1";
            
            $stmt = $this->db->query($query, [$slug]);
            
            if ($stmt->rowCount() === 0) {
                $this->notFound('Author not found');
                return;
            }
            
            $author = $stmt->fetch();
            $authorId = $author['id'];
            
            // Get avatar URL if exists
            $avatarUrl = null;
            $avatarQuery = "SELECT url FROM media WHERE entity_type = 'author' AND entity_id = ? AND type = 'avatar' LIMIT 1";
            $avatarStmt = $this->db->query($avatarQuery, [$authorId]);
            $avatar = $avatarStmt->fetch();
            if ($avatar) {
                $avatarUrl = $avatar['url'];
            }
            
            // Get author's stories with simplified structure
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
            
            // Build the formatted author with simplified structure
            $formattedAuthor = [
                'id' => $authorId,
                'name' => $author['name'],
                'slug' => $author['slug'],
                'bio' => $author['bio'],
                'featured' => (bool)$author['featured'],
                'twitter' => $author['twitter'],
                'instagram' => $author['instagram'],
                'website' => $author['website'],
                'storyCount' => $storyCount,
                'createdAt' => $author['createdAt'],
                'updatedAt' => $author['updatedAt'],
                'avatarUrl' => $avatarUrl,
                'stories' => $simpleStories
            ];
            
            // Send response
            Response::sendSuccess(['data' => $formattedAuthor]);
        } catch (\Exception $e) {
            $this->serverError('Failed to fetch author: ' . $e->getMessage());
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
            // Check if author exists and user has permission to update it
            $query = "SELECT * FROM authors WHERE id = ? LIMIT 1";
            $stmt = $this->db->query($query, [$authorId]);
            
            if ($stmt->rowCount() === 0) {
                $this->notFound('Author not found');
                return;
            }
            
            $author = $stmt->fetch();
            
            // Check if user is the author or has admin/editor role
            $isOwnProfile = $this->user['id'] == $authorId;
            $isAdminOrEditor = isset($this->user['role']) && in_array($this->user['role'], ['admin', 'editor']);
            
            if (!$isOwnProfile && !$isAdminOrEditor) {
                $this->forbidden('You do not have permission to update this author');
                return;
            }
            
            // Build update query
            $updates = [];
            $params = [];
            
            // Update fields if provided
            if (isset($this->request['name'])) {
                $updates[] = "name = ?";
                $params[] = Validator::sanitizeString($this->request['name']);
            }
            
            if (isset($this->request['bio'])) {
                $updates[] = "bio = ?";
                $params[] = Validator::sanitizeString($this->request['bio']);
            }
            
            if (isset($this->request['twitter'])) {
                $updates[] = "twitter = ?";
                $params[] = Validator::sanitizeString($this->request['twitter']);
            }
            
            if (isset($this->request['instagram'])) {
                $updates[] = "instagram = ?";
                $params[] = Validator::sanitizeString($this->request['instagram']);
            }
            
            if (isset($this->request['website'])) {
                $updates[] = "website = ?";
                $params[] = Validator::sanitizeString($this->request['website']);
            }
            
            // Only admin/editor can update featured status
            if (isset($this->request['featured']) && $isAdminOrEditor) {
                $updates[] = "featured = ?";
                $params[] = (bool)$this->request['featured'] ? 1 : 0;
            }
            
            // Add updated_at
            $updates[] = "updated_at = ?";
            $params[] = date('Y-m-d H:i:s');
            
            // Add author ID to params
            $params[] = $authorId;
            
            // Start transaction
            $this->db->beginTransaction();
            
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
            
            // Commit transaction
            $this->db->commit();
            
            // Return the updated author
            $query = "SELECT slug FROM authors WHERE id = ? LIMIT 1";
            $stmt = $this->db->query($query, [$authorId]);
            $updatedAuthor = $stmt->fetch();
            
            $this->params['slug'] = $updatedAuthor['slug'];
            $this->show();
        } catch (\Exception $e) {
            // Rollback transaction
            $this->db->rollback();
            $this->serverError('Failed to update author: ' . $e->getMessage());
        }
    }
}