<?php
/**
 * Tags Controller
 * 
 * This controller handles CRUD operations for tags.
 * 
 * @package Stories API
 * @version 1.0.0
 */

namespace StoriesAPI\Endpoints;

use StoriesAPI\Core\BaseController;
use StoriesAPI\Utils\Response;
use StoriesAPI\Utils\Validator;

class TagsController extends BaseController {
    /**
     * Get all tags with pagination, filtering, and sorting
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
            $countQuery = "SELECT COUNT(*) as total FROM tags t $whereClause";
            $stmt = $this->db->query($countQuery, $params);
            $total = $stmt->fetch()['total'];
            
            // Get tags with pagination
            $query = "SELECT 
                t.id, t.name, t.slug,
                (SELECT COUNT(*) FROM story_tags st WHERE st.tag_id = t.id) as storyCount
                FROM tags t
                $whereClause
                $sortClause
                LIMIT $offset, $pageSize";
            
            $stmt = $this->db->query($query, $params);
            $tags = $stmt->fetchAll();
            
            // Format tags with a simplified structure to avoid JSON encoding issues
            $formattedTags = [];
            
            foreach ($tags as $tag) {
                $formattedTags[] = [
                    'id' => $tag['id'],
                    'attributes' => [
                        'name' => $tag['name'],
                        'slug' => $tag['slug'],
                        'storyCount' => (int)$tag['storyCount']
                    ]
                ];
            }
            
            // Send paginated response
            Response::sendPaginated($formattedTags, $page, $pageSize, $total);
        } catch (\Exception $e) {
            $this->serverError('Failed to fetch tags: ' . $e->getMessage());
        }
    }
    
    /**
     * Get a single tag by slug or numeric ID
     */
    public function show() {
        // Grab the placeholder (named "slug" by the router)
        $identifier = $this->params['slug'] ?? null;
        if (!$identifier) {
            $this->serverError('No identifier provided');
            return;
        }

        // Decide whether this is an ID or a slug
        if (ctype_digit($identifier)) {
            $column = 't.id';
            $value  = (int)$identifier;
        } else {
            $column = 't.slug';
            // sanitize as before
            $value  = Validator::sanitizeString($identifier);
        }
        
        try {
            // Get tag by identifier
            $query = "SELECT
                t.id, t.name, t.slug
                FROM tags t
                WHERE $column = ? LIMIT 1";
            
            $stmt = $this->db->query($query, [$value]);
            
            if ($stmt->rowCount() === 0) {
                $this->notFound('Tag not found');
                return;
            }
            
            $tag = $stmt->fetch();
            $tagId = $tag['id'];
            
            // Get stories with this tag
            $storiesQuery = "SELECT 
                s.id, s.title, s.slug, s.excerpt, s.published_at as publishedAt,
                s.featured, s.average_rating as averageRating
                FROM stories s
                JOIN story_tags st ON s.id = st.story_id
                WHERE st.tag_id = ?
                ORDER BY s.published_at DESC";
            
            $storiesStmt = $this->db->query($storiesQuery, [$tagId]);
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
                
                // Get author basic info
                $authorName = null;
                $authorSlug = null;
                $authorId = null;
                $authorQuery = "SELECT a.id, a.name, a.slug FROM authors a
                    JOIN story_authors sa ON a.id = sa.author_id
                    WHERE sa.story_id = ? LIMIT 1";
                $authorStmt = $this->db->query($authorQuery, [$storyId]);
                $author = $authorStmt->fetch();
                if ($author) {
                    $authorId = $author['id'];
                    $authorName = $author['name'];
                    $authorSlug = $author['slug'];
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
                    'coverUrl' => $coverUrl,
                    'author' => $author ? [
                        'id' => $authorId,
                        'name' => $authorName,
                        'slug' => $authorSlug
                    ] : null
                ];
            }
            
            // Count stories
            $storyCount = count($simpleStories);
            
            // Build the formatted tag with proper structure
            $formattedTag = [
                'id' => $tagId,
                'attributes' => [
                    'name' => $tag['name'],
                    'slug' => $tag['slug'],
                    'storyCount' => $storyCount,
                    'stories' => $simpleStories
                ]
            ];
            
            // Send response
            Response::sendSuccess($formattedTag);
        } catch (\Exception $e) {
            $this->serverError('Failed to fetch tag: ' . $e->getMessage());
        }
    }
    
    /**
     * Create a new tag
     */
    public function create() {
        // Validate required fields
        if (!Validator::required($this->request, ['name'])) {
            $this->badRequest('Tag name is required', Validator::getErrors());
            return;
        }
        
        // Validate name length
        if (!Validator::length($this->request['name'], 'name', 2, 50)) {
            $this->badRequest('Tag name must be between 2 and 50 characters', Validator::getErrors());
            return;
        }
        
        // Sanitize input
        $name = Validator::sanitizeString($this->request['name']);
        $slug = isset($this->request['slug']) ? Validator::sanitizeString($this->request['slug']) : $this->generateSlug($name);
        
        try {
            // Check if slug already exists
            $query = "SELECT id FROM tags WHERE slug = ? LIMIT 1";
            $stmt = $this->db->query($query, [$slug]);
            
            if ($stmt->rowCount() > 0) {
                // Generate a unique slug
                $slug = $this->generateUniqueSlug($slug);
            }
            
            // Insert tag
            $query = "INSERT INTO tags (name, slug, created_at, updated_at) VALUES (?, ?, ?, ?)";
            $this->db->query($query, [
                $name,
                $slug,
                date('Y-m-d H:i:s'),
                date('Y-m-d H:i:s')
            ]);
            
            $tagId = $this->db->lastInsertId();
            
            // Return the created tag with proper structure
            $formattedTag = [
                'id' => $tagId,
                'attributes' => [
                    'name' => $name,
                    'slug' => $slug,
                    'storyCount' => 0
                ]
            ];
            
            Response::sendSuccess($formattedTag, [], 201);
        } catch (\Exception $e) {
            $this->serverError('Failed to create tag: ' . $e->getMessage());
        }
    }
    
    /**
     * Update a tag
     */
    public function update() {
        // Validate tag ID
        $tagId = isset($this->params['id']) ? (int)$this->params['id'] : null;
        
        if (!$tagId) {
            $this->badRequest('Tag ID is required');
            return;
        }
        
        try {
            // Check if tag exists
            $query = "SELECT * FROM tags WHERE id = ? LIMIT 1";
            $stmt = $this->db->query($query, [$tagId]);
            
            if ($stmt->rowCount() === 0) {
                $this->notFound('Tag not found');
                return;
            }
            
            $tag = $stmt->fetch();
            
            // Build update query
            $updates = [];
            $params = [];
            
            // Update name if provided
            if (isset($this->request['name'])) {
                if (!Validator::length($this->request['name'], 'name', 2, 50)) {
                    $this->badRequest('Tag name must be between 2 and 50 characters', Validator::getErrors());
                    return;
                }
                
                $name = Validator::sanitizeString($this->request['name']);
                $updates[] = "name = ?";
                $params[] = $name;
                
                // Update slug if name is changed and slug is not provided
                if (!isset($this->request['slug'])) {
                    $slug = $this->generateSlug($name);
                    
                    // Check if slug already exists
                    $query = "SELECT id FROM tags WHERE slug = ? AND id != ? LIMIT 1";
                    $stmt = $this->db->query($query, [$slug, $tagId]);
                    
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
                $query = "SELECT id FROM tags WHERE slug = ? AND id != ? LIMIT 1";
                $stmt = $this->db->query($query, [$slug, $tagId]);
                
                if ($stmt->rowCount() > 0) {
                    // Generate a unique slug
                    $slug = $this->generateUniqueSlug($slug);
                }
                
                $updates[] = "slug = ?";
                $params[] = $slug;
            }
            
            // If no updates, return current tag
            if (empty($updates)) {
                $this->params['slug'] = $tag['slug'];
                $this->show();
                return;
            }
            
            // Add updated_at
            $updates[] = "updated_at = ?";
            $params[] = date('Y-m-d H:i:s');
            
            // Add tag ID to params
            $params[] = $tagId;
            
            // Update tag
            $query = "UPDATE tags SET " . implode(', ', $updates) . " WHERE id = ?";
            $this->db->query($query, $params);
            
            // Return the updated tag
            $query = "SELECT * FROM tags WHERE id = ? LIMIT 1";
            $stmt = $this->db->query($query, [$tagId]);
            $updatedTag = $stmt->fetch();
            
            $formattedTag = [
                'id' => $tagId,
                'attributes' => [
                    'name' => $updatedTag['name'],
                    'slug' => $updatedTag['slug'],
                    'storyCount' => $this->getStoryCount($tagId)
                ]
            ];
            
            Response::sendSuccess($formattedTag);
        } catch (\Exception $e) {
            $this->serverError('Failed to update tag: ' . $e->getMessage());
        }
    }
    
    /**
     * Delete a tag
     */
    public function delete() {
        // Validate tag ID
        $tagId = isset($this->params['id']) ? (int)$this->params['id'] : null;
        
        if (!$tagId) {
            $this->badRequest('Tag ID is required');
            return;
        }
        
        try {
            // Check if tag exists
            $query = "SELECT * FROM tags WHERE id = ? LIMIT 1";
            $stmt = $this->db->query($query, [$tagId]);
            
            if ($stmt->rowCount() === 0) {
                $this->notFound('Tag not found');
                return;
            }
            
            // Start transaction
            $this->db->beginTransaction();
            
            // Delete tag associations
            $query = "DELETE FROM story_tags WHERE tag_id = ?";
            $this->db->query($query, [$tagId]);
            
            // Delete tag
            $query = "DELETE FROM tags WHERE id = ?";
            $this->db->query($query, [$tagId]);
            
            // Commit transaction
            $this->db->commit();
            
            // Send success response
            Response::sendSuccess(['message' => 'Tag deleted successfully'], [], 200);
        } catch (\Exception $e) {
            // Rollback transaction
            $this->db->rollback();
            $this->serverError('Failed to delete tag: ' . $e->getMessage());
        }
    }
    
    /**
     * Get the number of stories associated with a tag
     * 
     * @param int $tagId The tag ID
     * @return int The number of stories
     */
    private function getStoryCount($tagId) {
        $query = "SELECT COUNT(*) as count FROM story_tags WHERE tag_id = ?";
        $stmt = $this->db->query($query, [$tagId]);
        return (int)$stmt->fetch()['count'];
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
            $query = "SELECT id FROM tags WHERE slug = ? LIMIT 1";
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
