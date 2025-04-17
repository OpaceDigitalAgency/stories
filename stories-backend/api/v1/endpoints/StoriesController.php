<?php
/**
 * Stories Controller
 * 
 * This controller handles CRUD operations for stories.
 * 
 * @package Stories API
 * @version 1.0.0
 */

namespace StoriesAPI\Endpoints;

use StoriesAPI\Core\BaseController;
use StoriesAPI\Utils\Response;
use StoriesAPI\Utils\Validator;

class StoriesController extends BaseController {
    /**
     * Get all stories with pagination, filtering, and sorting
     */
    public function index() {
        // Get pagination parameters - cast to int to avoid notices
        $pagination = $this->getPaginationParams();
        $page = (int)($pagination['page'] ?? 1);
        $pageSize = (int)($pagination['pageSize'] ?? 10);
        $offset = ($page - 1) * $pageSize;
        
        // Get sort parameters - use null coalescing to avoid undefined index notices
        $sortParam = $this->query['sort'] ?? '';
        $sort = $sortParam === ''
            ? null
            : $this->getSortParams(['title', 'publishedAt', 'averageRating', 'featured']);
        $sortClause = $sort ? "ORDER BY {$sort['field']} {$sort['direction']}" : "ORDER BY published_at DESC";
        
        // Get filter parameters
        $allowedFilterFields = ['title', 'slug', 'featured', 'author_id', 'tag_id'];
        $filters = $this->getFilterParams($allowedFilterFields);
        
        try {
            // Build the base query
            $baseQuery = "FROM stories s 
                LEFT JOIN story_authors sa ON s.id = sa.story_id 
                LEFT JOIN authors a ON sa.author_id = a.id 
                LEFT JOIN story_tags st ON s.id = st.story_id";
            
            // Build the WHERE clause
            $whereData = $this->buildWhereClause($filters);
            $whereClause = $whereData['clause'];
            $params = $whereData['params'];
            
            // Count total records
            $countQuery = "SELECT COUNT(DISTINCT s.id) as total $baseQuery $whereClause";
            $stmt = $this->db->query($countQuery, $params);
            $total = $stmt->fetch()['total'];
            
            // Get stories with pagination
            $query = "SELECT DISTINCT 
                s.id, s.title, s.slug, s.excerpt, s.content, s.published_at as publishedAt, 
                s.featured, s.average_rating as averageRating, s.review_count as reviewCount,
                s.estimated_reading_time as estimatedReadingTime, s.is_sponsored as isSponsored,
                s.age_group as ageGroup, s.needs_moderation as needsModeration,
                s.is_self_published as isSelfPublished, s.is_ai_enhanced as isAIEnhanced,
                s.created_at as createdAt, s.updated_at as updatedAt
                $baseQuery
                $whereClause
                $sortClause
                LIMIT $offset, $pageSize";
            
            $stmt = $this->db->query($query, $params);
            $stories = $stmt->fetchAll();
            
            // Format stories with a simplified structure to avoid JSON encoding issues
            $formattedStories = [];
            
            foreach ($stories as $story) {
                $storyId = $story['id'];
                
                // Get basic story author info
                $authorQuery = "SELECT a.id, a.name, a.slug FROM authors a
                    JOIN story_authors sa ON a.id = sa.author_id
                    WHERE sa.story_id = ? LIMIT 1";
                $authorStmt = $this->db->query($authorQuery, [$storyId]);
                $author = $authorStmt->fetch();
                
                // Get basic tag info
                $tagsQuery = "SELECT t.id, t.name FROM tags t
                    JOIN story_tags st ON t.id = st.tag_id
                    WHERE st.story_id = ?";
                $tagsStmt = $this->db->query($tagsQuery, [$storyId]);
                $tags = $tagsStmt->fetchAll();
                
                // Simplify tag structure
                $simpleTags = [];
                foreach ($tags as $tag) {
                    $simpleTags[] = [
                        'id' => $tag['id'],
                        'name' => $tag['name']
                    ];
                }
                
                // Build a simplified story structure
                $formattedStory = [
                    'id' => $storyId,
                    'title' => $story['title'],
                    'slug' => $story['slug'],
                    'excerpt' => $story['excerpt'],
                    'publishedAt' => $story['publishedAt'],
                    'featured' => (bool)$story['featured'],
                    'averageRating' => (float)$story['averageRating'],
                    'reviewCount' => (int)$story['reviewCount'],
                    'createdAt' => $story['createdAt'],
                    'updatedAt' => $story['updatedAt'],
                    'author' => $author ? [
                        'id' => $author['id'],
                        'name' => $author['name'],
                        'slug' => $author['slug']
                    ] : null,
                    'tags' => $simpleTags
                ];
                
                $formattedStories[] = $formattedStory;
            }
            
            // Send paginated response
            Response::sendPaginated($formattedStories, $page, $pageSize, $total);
        } catch (\Exception $e) {
            $this->serverError('Failed to fetch stories: ' . $e->getMessage());
        }
    }
    
    /**
     * Get a single story by slug or numeric ID
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
            $column = 's.id';
            $value  = (int)$identifier;
        } else {
            $column = 's.slug';
            // sanitize as before
            $value  = Validator::sanitizeString($identifier);
        }

        try {
            // Get story by identifier
            $query = "SELECT
                s.id, s.title, s.slug, s.excerpt, s.content, s.published_at as publishedAt,
                s.featured, s.average_rating as averageRating, s.review_count as reviewCount,
                s.estimated_reading_time as estimatedReadingTime, s.is_sponsored as isSponsored,
                s.age_group as ageGroup, s.needs_moderation as needsModeration,
                s.is_self_published as isSelfPublished, s.is_ai_enhanced as isAIEnhanced,
                s.created_at as createdAt, s.updated_at as updatedAt,
                a.id as authorId, a.name as authorName, a.slug as authorSlug
                FROM stories s
                LEFT JOIN story_authors sa ON s.id = sa.story_id
                LEFT JOIN authors a ON sa.author_id = a.id
                WHERE $column = ? LIMIT 1";
            
            $stmt = $this->db->query($query, [$value]);
            
            if ($stmt->rowCount() === 0) {
                $this->notFound('Story not found');
                return;
            }
            
            $story = $stmt->fetch();
            $storyId = $story['id'];
            
            // Get basic story author info
            $authorQuery = "SELECT a.id, a.name, a.slug FROM authors a
                JOIN story_authors sa ON a.id = sa.author_id
                WHERE sa.story_id = ? LIMIT 1";
            $authorStmt = $this->db->query($authorQuery, [$storyId]);
            $author = $authorStmt->fetch();
            
            // Get basic tag info
            $tagsQuery = "SELECT t.id, t.name FROM tags t
                JOIN story_tags st ON t.id = st.tag_id
                WHERE st.story_id = ?";
            $tagsStmt = $this->db->query($tagsQuery, [$storyId]);
            $tags = $tagsStmt->fetchAll();
            
            // Simplify tag structure
            $simpleTags = [];
            foreach ($tags as $tag) {
                $simpleTags[] = [
                    'id' => $tag['id'],
                    'name' => $tag['name']
                ];
            }
            
            // Get cover URL if exists
            $coverUrl = null;
            $coverQuery = "SELECT url FROM media WHERE entity_type = 'story' AND entity_id = ? AND type = 'cover' LIMIT 1";
            $coverStmt = $this->db->query($coverQuery, [$storyId]);
            $cover = $coverStmt->fetch();
            if ($cover) {
                $coverUrl = $cover['url'];
            }
            
            // Build a simplified story structure
            $formattedStory = [
                'id' => $storyId,
                'title' => $story['title'],
                'slug' => $story['slug'],
                'excerpt' => $story['excerpt'],
                'content' => $story['content'],
                'publishedAt' => $story['publishedAt'],
                'featured' => (bool)$story['featured'],
                'averageRating' => (float)$story['averageRating'],
                'reviewCount' => (int)$story['reviewCount'],
                'estimatedReadingTime' => $story['estimatedReadingTime'],
                'isSponsored' => (bool)$story['isSponsored'],
                'ageGroup' => $story['ageGroup'],
                'needsModeration' => (bool)$story['needsModeration'],
                'isSelfPublished' => (bool)$story['isSelfPublished'],
                'isAIEnhanced' => (bool)$story['isAIEnhanced'],
                'createdAt' => $story['createdAt'],
                'updatedAt' => $story['updatedAt'],
                'coverUrl' => $coverUrl,
                'author' => $author ? [
                    'id' => $author['id'],
                    'name' => $author['name'],
                    'slug' => $author['slug']
                ] : null,
                'tags' => $simpleTags
            ];
            
            // Send response
            Response::sendSuccess(['data' => $formattedStory]);
        } catch (\Exception $e) {
            $this->serverError('Failed to fetch story: ' . $e->getMessage());
        }
    }
    
    /**
     * Create a new story
     */
    public function create() {
        // Validate required fields
        if (!Validator::required($this->request, ['title', 'content'])) {
            $this->badRequest('Title and content are required', Validator::getErrors());
            return;
        }
        
        // Validate title length
        if (!Validator::length($this->request['title'], 'title', 3, 255)) {
            $this->badRequest('Title must be between 3 and 255 characters', Validator::getErrors());
            return;
        }
        
        // Validate content length
        if (!Validator::length($this->request['content'], 'content', 10)) {
            $this->badRequest('Content must be at least 10 characters', Validator::getErrors());
            return;
        }
        
        // Sanitize input
        $title = Validator::sanitizeString($this->request['title']);
        $content = $this->request['content']; // Don't sanitize content as it may contain HTML
        $excerpt = isset($this->request['excerpt']) ? Validator::sanitizeString($this->request['excerpt']) : substr(strip_tags($content), 0, 200) . '...';
        $slug = isset($this->request['slug']) ? Validator::sanitizeString($this->request['slug']) : $this->generateSlug($title);
        $featured = isset($this->request['featured']) ? (bool)$this->request['featured'] : false;
        $isSponsored = isset($this->request['isSponsored']) ? (bool)$this->request['isSponsored'] : false;
        $ageGroup = isset($this->request['ageGroup']) ? Validator::sanitizeString($this->request['ageGroup']) : null;
        $isSelfPublished = isset($this->request['isSelfPublished']) ? (bool)$this->request['isSelfPublished'] : true;
        $isAIEnhanced = isset($this->request['isAIEnhanced']) ? (bool)$this->request['isAIEnhanced'] : false;
        $needsModeration = true; // All new stories need moderation
        
        try {
            // Start transaction
            $this->db->beginTransaction();
            
            // Check if slug already exists
            $query = "SELECT id FROM stories WHERE slug = ? LIMIT 1";
            $stmt = $this->db->query($query, [$slug]);
            
            if ($stmt->rowCount() > 0) {
                // Generate a unique slug
                $slug = $this->generateUniqueSlug($slug);
            }
            
            // Insert story
            $query = "INSERT INTO stories (
                title, slug, excerpt, content, published_at, featured, 
                is_sponsored, age_group, needs_moderation, is_self_published, 
                is_ai_enhanced, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $this->db->query($query, [
                $title,
                $slug,
                $excerpt,
                $content,
                date('Y-m-d H:i:s'), // publishedAt
                $featured ? 1 : 0,
                $isSponsored ? 1 : 0,
                $ageGroup,
                $needsModeration ? 1 : 0,
                $isSelfPublished ? 1 : 0,
                $isAIEnhanced ? 1 : 0,
                date('Y-m-d H:i:s'), // createdAt
                date('Y-m-d H:i:s')  // updatedAt
            ]);
            
            $storyId = $this->db->lastInsertId();
            
            // Associate with author (current user)
            $query = "INSERT INTO story_authors (story_id, author_id) VALUES (?, ?)";
            $this->db->query($query, [$storyId, $this->user['id']]);
            
            // Associate with tags if provided
            if (isset($this->request['tags']) && is_array($this->request['tags'])) {
                foreach ($this->request['tags'] as $tagId) {
                    $query = "INSERT INTO story_tags (story_id, tag_id) VALUES (?, ?)";
                    $this->db->query($query, [$storyId, $tagId]);
                }
            }
            
            // Handle cover image if provided
            if (isset($this->request['cover']) && !empty($this->request['cover'])) {
                $coverUrl = Validator::sanitizeString($this->request['cover']);
                $query = "INSERT INTO media (
                    entity_type, entity_id, type, url, width, height, alt_text, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                
                $this->db->query($query, [
                    'story',
                    $storyId,
                    'cover',
                    $coverUrl,
                    isset($this->request['coverWidth']) ? (int)$this->request['coverWidth'] : 1200,
                    isset($this->request['coverHeight']) ? (int)$this->request['coverHeight'] : 800,
                    isset($this->request['coverAlt']) ? Validator::sanitizeString($this->request['coverAlt']) : $title,
                    date('Y-m-d H:i:s')
                ]);
            }
            
            // Commit transaction
            $this->db->commit();
            
            // Return the created story
            $this->params['slug'] = $slug;
            $this->show();
        } catch (\Exception $e) {
            // Rollback transaction
            $this->db->rollback();
            $this->serverError('Failed to create story: ' . $e->getMessage());
        }
    }
    
    /**
     * Update a story
     */
    public function update() {
        // Validate story ID
        $storyId = isset($this->params['id']) ? (int)$this->params['id'] : null;
        
        if (!$storyId) {
            $this->badRequest('Story ID is required');
            return;
        }
        
        try {
            // Check if story exists and user has permission to update it
            $query = "SELECT s.* FROM stories s 
                JOIN story_authors sa ON s.id = sa.story_id 
                WHERE s.id = ? AND sa.author_id = ? LIMIT 1";
            
            $stmt = $this->db->query($query, [$storyId, $this->user['id']]);
            
            if ($stmt->rowCount() === 0) {
                // Check if user is admin or editor
                if (!isset($this->user['role']) || !in_array($this->user['role'], ['admin', 'editor'])) {
                    $this->notFound('Story not found or you do not have permission to update it');
                    return;
                }
                
                // Admin or editor can update any story
                $query = "SELECT * FROM stories WHERE id = ? LIMIT 1";
                $stmt = $this->db->query($query, [$storyId]);
                
                if ($stmt->rowCount() === 0) {
                    $this->notFound('Story not found');
                    return;
                }
            }
            
            $story = $stmt->fetch();
            
            // Build update query
            $updates = [];
            $params = [];
            
            // Update fields if provided
            if (isset($this->request['title'])) {
                $updates[] = "title = ?";
                $params[] = Validator::sanitizeString($this->request['title']);
            }
            
            if (isset($this->request['slug'])) {
                $updates[] = "slug = ?";
                $params[] = Validator::sanitizeString($this->request['slug']);
            }
            
            if (isset($this->request['content'])) {
                $updates[] = "content = ?";
                $params[] = $this->request['content'];
            }
            
            if (isset($this->request['excerpt'])) {
                $updates[] = "excerpt = ?";
                $params[] = Validator::sanitizeString($this->request['excerpt']);
            }
            
            if (isset($this->request['featured'])) {
                $updates[] = "featured = ?";
                $params[] = (bool)$this->request['featured'] ? 1 : 0;
            }
            
            // Add updated_at
            $updates[] = "updated_at = ?";
            $params[] = date('Y-m-d H:i:s');
            
            // Add story ID to params
            $params[] = $storyId;
            
            // Update story
            if (!empty($updates)) {
                $query = "UPDATE stories SET " . implode(', ', $updates) . " WHERE id = ?";
                $this->db->query($query, $params);
            }
            
            // Return the updated story
            $query = "SELECT slug FROM stories WHERE id = ? LIMIT 1";
            $stmt = $this->db->query($query, [$storyId]);
            $updatedStory = $stmt->fetch();
            
            $this->params['slug'] = $updatedStory['slug'];
            $this->show();
        } catch (\Exception $e) {
            $this->serverError('Failed to update story: ' . $e->getMessage());
        }
    }
    
    /**
     * Delete a story
     */
    public function delete() {
        // Validate story ID
        $storyId = isset($this->params['id']) ? (int)$this->params['id'] : null;
        
        if (!$storyId) {
            $this->badRequest('Story ID is required');
            return;
        }
        
        try {
            // Check if story exists and user has permission to delete it
            $query = "SELECT s.* FROM stories s 
                JOIN story_authors sa ON s.id = sa.story_id 
                WHERE s.id = ? AND sa.author_id = ? LIMIT 1";
            
            $stmt = $this->db->query($query, [$storyId, $this->user['id']]);
            
            if ($stmt->rowCount() === 0) {
                // Check if user is admin or editor
                if (!isset($this->user['role']) || !in_array($this->user['role'], ['admin', 'editor'])) {
                    $this->notFound('Story not found or you do not have permission to delete it');
                    return;
                }
                
                // Admin or editor can delete any story
                $query = "SELECT * FROM stories WHERE id = ? LIMIT 1";
                $stmt = $this->db->query($query, [$storyId]);
                
                if ($stmt->rowCount() === 0) {
                    $this->notFound('Story not found');
                    return;
                }
            }
            
            // Start transaction
            $this->db->beginTransaction();
            
            // Delete story tags
            $query = "DELETE FROM story_tags WHERE story_id = ?";
            $this->db->query($query, [$storyId]);
            
            // Delete story authors
            $query = "DELETE FROM story_authors WHERE story_id = ?";
            $this->db->query($query, [$storyId]);
            
            // Delete story media
            $query = "DELETE FROM media WHERE entity_type = 'story' AND entity_id = ?";
            $this->db->query($query, [$storyId]);
            
            // Delete story
            $query = "DELETE FROM stories WHERE id = ?";
            $this->db->query($query, [$storyId]);
            
            // Commit transaction
            $this->db->commit();
            
            // Send success response
            Response::sendSuccess(['message' => 'Story deleted successfully'], [], 200);
        } catch (\Exception $e) {
            // Rollback transaction
            $this->db->rollback();
            $this->serverError('Failed to delete story: ' . $e->getMessage());
        }
    }
    
    /**
     * Generate a slug from a title
     * 
     * @param string $title The title to generate a slug from
     * @return string The generated slug
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
     * 
     * @param string $slug The base slug
     * @return string A unique slug
     */
    private function generateUniqueSlug($slug) {
        $originalSlug = $slug;
        $counter = 1;
        
        while (true) {
            // Check if slug exists
            $query = "SELECT id FROM stories WHERE slug = ? LIMIT 1";
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
