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
        // Get pagination parameters
        $pagination = $this->getPaginationParams();
        $page = $pagination['page'];
        $pageSize = $pagination['pageSize'];
        $offset = ($page - 1) * $pageSize;
        
        // Get sort parameters
        $allowedSortFields = ['title', 'publishedAt', 'averageRating'];
        $sort = $this->getSortParams($allowedSortFields);
        // Map API sort fields to DB columns
        $sortFieldMap = [
            'title' => 'title',
            'publishedAt' => 'published_at',
            'averageRating' => 'average_rating'
        ];
        $sortField = $sort['field'] ?? 'publishedAt';
        if (isset($sortFieldMap[$sortField])) {
            $dbSortField = $sortFieldMap[$sortField];
        } else {
            $dbSortField = 'published_at';
        }
        $sortDirection = $sort['direction'] ?? 'DESC';
        $sortClause = "ORDER BY $dbSortField $sortDirection";
        
        // Get filter parameters
        $allowedFilterFields = ['title', 'slug', 'featured', 'ageGroup', 'isSponsored'];
        $filters = $this->getFilterParams($allowedFilterFields);
        
        try {
            // Build the WHERE clause
            $whereData = $this->buildWhereClause($filters);
            $whereClause = $whereData['clause'];
            $params = $whereData['params'];
            
            // Count total records
            $countQuery = "SELECT COUNT(*) as total FROM stories s $whereClause";
            $stmt = $this->db->query($countQuery, $params);
            $total = $stmt->fetch()['total'];
            
            // Get stories with pagination
            $query = "SELECT 
                s.id, s.title, s.slug, s.excerpt, s.published_at AS publishedAt, 
                s.featured, s.average_rating AS averageRating, s.review_count AS reviewCount,
                s.estimated_reading_time AS estimatedReadingTime, s.is_sponsored AS isSponsored,
                s.age_group AS ageGroup, s.needs_moderation AS needsModeration,
                s.is_self_published AS isSelfPublished, s.is_ai_enhanced AS isAIEnhanced,
                s.cover_url AS coverUrl, s.created_at AS createdAt, s.updated_at AS updatedAt
                FROM stories s
                $whereClause
                $sortClause
                LIMIT $offset, $pageSize";
            
            $stmt = $this->db->query($query, $params);
            try {
                $stories = $stmt->fetchAll();
            } catch (\Exception $e) {
                error_log('Main stories query error: ' . $e->getMessage());
                $this->serverError('Failed to fetch stories (main query): ' . $e->getMessage() . "\n" . $e->getTraceAsString());
                return;
            }
            
            // Format stories with a simplified structure to avoid JSON encoding issues
            $formattedStories = [];
            
            foreach ($stories as $story) {
                $storyId = $story['id'];
                
                // Get author
                $authorQuery = "SELECT a.id, a.name, a.slug FROM authors a
                    LEFT JOIN story_authors sa ON a.id = sa.author_id
                    WHERE sa.story_id = ? LIMIT 1";
                try {
                    $authorStmt = $this->db->query($authorQuery, [$storyId]);
                    $author = $authorStmt->fetch();
                } catch (\Exception $e) {
                    error_log('Author subquery error for story ' . $storyId . ': ' . $e->getMessage());
                    $author = null;
                }
                
                // Get tags
                $tagsQuery = "SELECT t.id, t.name FROM tags t
                    LEFT JOIN story_tags st ON t.id = st.tag_id
                    WHERE st.story_id = ?";
                try {
                    $tagsStmt = $this->db->query($tagsQuery, [$storyId]);
                    $tags = $tagsStmt->fetchAll();
                } catch (\Exception $e) {
                    error_log('Tags subquery error for story ' . $storyId . ': ' . $e->getMessage());
                    $tags = [];
                }
                
                // Format tags
                $formattedTags = [];
                foreach ($tags as $tag) {
                    $formattedTags[] = [
                        'id' => $tag['id'],
                        'name' => $tag['name']
                    ];
                }
                
                // Build the formatted story
                $formattedStories[] = [
                    'id' => $storyId,
                    'attributes' => [
                        'title' => $story['title'],
                        'slug' => $story['slug'],
                        'excerpt' => $story['excerpt'],
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
                        'coverUrl' => $story['coverUrl'],
                        'createdAt' => $story['createdAt'],
                        'updatedAt' => $story['updatedAt'],
                        'author' => $author ? [
                            'id' => $author['id'],
                            'name' => $author['name'],
                            'slug' => $author['slug']
                        ] : null,
                        'tags' => $formattedTags
                    ]
                ];
            }
            
            // Send paginated response
            Response::sendPaginated($formattedStories, $page, $pageSize, $total);
        } catch (\Exception $e) {
            error_log('StoriesController index() error: ' . $e->getMessage());
            $this->serverError('Failed to fetch stories: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
        }
    }
    
    /**
     * Get a single story by slug or numeric ID
     */
    public function show() {
        // Grab the placeholder (named "slug" by the router)
        $identifier = $this->params['slug'] ?? null;
        if (!$identifier) {
            Response::sendError('No identifier provided', 400);
            return;
        }

        try {
            // Decide whether this is an ID or a slug
            if (ctype_digit($identifier)) {
                $query = "SELECT * FROM stories WHERE id = ? LIMIT 1";
                $value = (int)$identifier;
            } else {
                $query = "SELECT * FROM stories WHERE slug = ? LIMIT 1";
                $value = Validator::sanitizeString($identifier);
            }
            
            $stmt = $this->db->query($query, [$value]);
            $story = $stmt->fetch();
            
            if (!$story) {
                Response::sendError('Story not found', 404);
                return;
            }
            
            // Format the story with attributes wrapper
            $formattedStory = [
                'id' => $story['id'],
                'attributes' => [
                    'title' => $story['title'],
                    'slug' => $story['slug'],
                    'excerpt' => $story['excerpt'],
                    'content' => $story['content'],
                    'publishedAt' => $story['published_at'],
                    'featured' => (bool)$story['featured'],
                    'averageRating' => (float)$story['average_rating'],
                    'reviewCount' => (int)$story['review_count'],
                    'estimatedReadingTime' => $story['estimated_reading_time'],
                    'isSponsored' => (bool)$story['is_sponsored'],
                    'ageGroup' => $story['age_group'],
                    'needsModeration' => (bool)$story['needs_moderation'],
                    'isSelfPublished' => (bool)$story['is_self_published'],
                    'isAIEnhanced' => (bool)$story['is_ai_enhanced'],
                    'coverUrl' => $story['cover_url'],
                    'createdAt' => $story['created_at'],
                    'updatedAt' => $story['updated_at']
                ]
            ];
            
            // Get author
            $authorQuery = "SELECT a.id, a.name, a.slug FROM authors a
                JOIN story_authors sa ON a.id = sa.author_id
                WHERE sa.story_id = ? LIMIT 1";
            $authorStmt = $this->db->query($authorQuery, [$story['id']]);
            $author = $authorStmt->fetch();
            
            if ($author) {
                $formattedStory['attributes']['author'] = [
                    'id' => $author['id'],
                    'name' => $author['name'],
                    'slug' => $author['slug']
                ];
            }
            
            // Get tags
            $tagsQuery = "SELECT t.id, t.name FROM tags t
                JOIN story_tags st ON t.id = st.tag_id
                WHERE st.story_id = ?";
            $tagsStmt = $this->db->query($tagsQuery, [$story['id']]);
            $tags = $tagsStmt->fetchAll();
            
            $formattedTags = [];
            foreach ($tags as $tag) {
                $formattedTags[] = [
                    'id' => $tag['id'],
                    'name' => $tag['name']
                ];
            }
            
            $formattedStory['attributes']['tags'] = $formattedTags;
            
            Response::sendSuccess($formattedStory);
        } catch (\Exception $e) {
            $this->serverError('Failed to fetch Story: ' . $e->getMessage());
        }
    }
    
    /**
     * Create a new story
     */
    public function create() {
        // Validate required fields
        if (!Validator::required($this->request, ['title', 'content', 'authorId'])) {
            $this->badRequest('Title, content, and author ID are required', Validator::getErrors());
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
        $authorId = (int)$this->request['authorId'];
        
        // Optional fields
        $featured = isset($this->request['featured']) ? (bool)$this->request['featured'] : false;
        $isSponsored = isset($this->request['isSponsored']) ? (bool)$this->request['isSponsored'] : false;
        $ageGroup = isset($this->request['ageGroup']) ? Validator::sanitizeString($this->request['ageGroup']) : null;
        $needsModeration = isset($this->request['needsModeration']) ? (bool)$this->request['needsModeration'] : true;
        $isSelfPublished = isset($this->request['isSelfPublished']) ? (bool)$this->request['isSelfPublished'] : false;
        $isAIEnhanced = isset($this->request['isAIEnhanced']) ? (bool)$this->request['isAIEnhanced'] : false;
        $coverUrl = isset($this->request['coverUrl']) ? Validator::sanitizeString($this->request['coverUrl']) : null;
        $estimatedReadingTime = isset($this->request['estimatedReadingTime']) ? Validator::sanitizeString($this->request['estimatedReadingTime']) : $this->calculateReadingTime($content);
        
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
            
            // Check if author exists
            $query = "SELECT id FROM authors WHERE id = ? LIMIT 1";
            $stmt = $this->db->query($query, [$authorId]);
            
            if ($stmt->rowCount() === 0) {
                $this->badRequest('Author not found');
                return;
            }
            
            // Insert story
            $query = "INSERT INTO stories (
                title, slug, excerpt, content, published_at, featured,
                average_rating, review_count, estimated_reading_time,
                is_sponsored, age_group, needs_moderation,
                is_self_published, is_ai_enhanced, cover_url,
                created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $this->db->query($query, [
                $title,
                $slug,
                $excerpt,
                $content,
                date('Y-m-d H:i:s'), // publishedAt
                $featured ? 1 : 0,
                0, // averageRating
                0, // reviewCount
                $estimatedReadingTime,
                $isSponsored ? 1 : 0,
                $ageGroup,
                $needsModeration ? 1 : 0,
                $isSelfPublished ? 1 : 0,
                $isAIEnhanced ? 1 : 0,
                $coverUrl,
                date('Y-m-d H:i:s'), // createdAt
                date('Y-m-d H:i:s')  // updatedAt
            ]);
            
            $storyId = $this->db->lastInsertId();
            
            // Associate with author
            $query = "INSERT INTO story_authors (story_id, author_id) VALUES (?, ?)";
            $this->db->query($query, [$storyId, $authorId]);
            
            // Associate with tags if provided
            if (isset($this->request['tags']) && is_array($this->request['tags'])) {
                foreach ($this->request['tags'] as $tagId) {
                    $query = "INSERT INTO story_tags (story_id, tag_id) VALUES (?, ?)";
                    $this->db->query($query, [$storyId, (int)$tagId]);
                }
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
            // Check if story exists
            $query = "SELECT * FROM stories WHERE id = ? LIMIT 1";
            $stmt = $this->db->query($query, [$storyId]);
            
            if ($stmt->rowCount() === 0) {
                $this->notFound('Story not found');
                return;
            }
            
            $story = $stmt->fetch();
            
            // Build update query
            $updates = [];
            $params = [];
            
            // Update fields if provided
            if (isset($this->request['title'])) {
                if (!Validator::length($this->request['title'], 'title', 3, 255)) {
                    $this->badRequest('Title must be between 3 and 255 characters', Validator::getErrors());
                    return;
                }
                
                $title = Validator::sanitizeString($this->request['title']);
                $updates[] = "title = ?";
                $params[] = $title;
                
                // Update slug if title is changed and slug is not provided
                if (!isset($this->request['slug'])) {
                    $slug = $this->generateSlug($title);
                    
                    // Check if slug already exists
                    $query = "SELECT id FROM stories WHERE slug = ? AND id != ? LIMIT 1";
                    $stmt = $this->db->query($query, [$slug, $storyId]);
                    
                    if ($stmt->rowCount() > 0) {
                        // Generate a unique slug
                        $slug = $this->generateUniqueSlug($slug);
                    }
                    
                    $updates[] = "slug = ?";
                    $params[] = $slug;
                }
            }
            
            if (isset($this->request['slug'])) {
                $slug = Validator::sanitizeString($this->request['slug']);
                
                // Check if slug already exists
                $query = "SELECT id FROM stories WHERE slug = ? AND id != ? LIMIT 1";
                $stmt = $this->db->query($query, [$slug, $storyId]);
                
                if ($stmt->rowCount() > 0) {
                    // Generate a unique slug
                    $slug = $this->generateUniqueSlug($slug);
                }
                
                $updates[] = "slug = ?";
                $params[] = $slug;
            }
            
            if (isset($this->request['excerpt'])) {
                $updates[] = "excerpt = ?";
                $params[] = Validator::sanitizeString($this->request['excerpt']);
            }
            
            if (isset($this->request['content'])) {
                if (!Validator::length($this->request['content'], 'content', 10)) {
                    $this->badRequest('Content must be at least 10 characters', Validator::getErrors());
                    return;
                }
                
                $content = $this->request['content'];
                $updates[] = "content = ?";
                $params[] = $content;
                
                // Update excerpt if content is changed and excerpt is not provided
                if (!isset($this->request['excerpt'])) {
                    $excerpt = substr(strip_tags($content), 0, 200) . '...';
                    $updates[] = "excerpt = ?";
                    $params[] = $excerpt;
                }
                
                // Update estimated reading time if content is changed and reading time is not provided
                if (!isset($this->request['estimatedReadingTime'])) {
                    $estimatedReadingTime = $this->calculateReadingTime($content);
                    $updates[] = "estimated_reading_time = ?";
                    $params[] = $estimatedReadingTime;
                }
            }
            
            // Update optional fields if provided
            if (isset($this->request['featured'])) {
                $updates[] = "featured = ?";
                $params[] = (bool)$this->request['featured'] ? 1 : 0;
            }
            
            if (isset($this->request['isSponsored'])) {
                $updates[] = "is_sponsored = ?";
                $params[] = (bool)$this->request['isSponsored'] ? 1 : 0;
            }
            
            if (isset($this->request['ageGroup'])) {
                $updates[] = "age_group = ?";
                $params[] = Validator::sanitizeString($this->request['ageGroup']);
            }
            
            if (isset($this->request['needsModeration'])) {
                $updates[] = "needs_moderation = ?";
                $params[] = (bool)$this->request['needsModeration'] ? 1 : 0;
            }
            
            if (isset($this->request['isSelfPublished'])) {
                $updates[] = "is_self_published = ?";
                $params[] = (bool)$this->request['isSelfPublished'] ? 1 : 0;
            }
            
            if (isset($this->request['isAIEnhanced'])) {
                $updates[] = "is_ai_enhanced = ?";
                $params[] = (bool)$this->request['isAIEnhanced'] ? 1 : 0;
            }
            
            if (isset($this->request['coverUrl'])) {
                $updates[] = "cover_url = ?";
                $params[] = Validator::sanitizeString($this->request['coverUrl']);
            }
            
            if (isset($this->request['estimatedReadingTime'])) {
                $updates[] = "estimated_reading_time = ?";
                $params[] = Validator::sanitizeString($this->request['estimatedReadingTime']);
            }
            
            // Start transaction
            $this->db->beginTransaction();
            
            // Update story
            if (!empty($updates)) {
                // Add updated_at
                $updates[] = "updated_at = ?";
                $params[] = date('Y-m-d H:i:s');
                
                // Add story ID to params
                $params[] = $storyId;
                
                $query = "UPDATE stories SET " . implode(', ', $updates) . " WHERE id = ?";
                $this->db->query($query, $params);
            }
            
            // Update author if provided
            if (isset($this->request['authorId'])) {
                $authorId = (int)$this->request['authorId'];
                
                // Check if author exists
                $query = "SELECT id FROM authors WHERE id = ? LIMIT 1";
                $stmt = $this->db->query($query, [$authorId]);
                
                if ($stmt->rowCount() === 0) {
                    $this->badRequest('Author not found');
                    return;
                }
                
                // Delete existing author association
                $query = "DELETE FROM story_authors WHERE story_id = ?";
                $this->db->query($query, [$storyId]);
                
                // Insert new author association
                $query = "INSERT INTO story_authors (story_id, author_id) VALUES (?, ?)";
                $this->db->query($query, [$storyId, $authorId]);
            }
            
            // Update tags if provided
            if (isset($this->request['tags']) && is_array($this->request['tags'])) {
                // Delete existing tag associations
                $query = "DELETE FROM story_tags WHERE story_id = ?";
                $this->db->query($query, [$storyId]);
                
                // Insert new tag associations
                foreach ($this->request['tags'] as $tagId) {
                    $query = "INSERT INTO story_tags (story_id, tag_id) VALUES (?, ?)";
                    $this->db->query($query, [$storyId, (int)$tagId]);
                }
            }
            
            // Commit transaction
            $this->db->commit();
            
            // Return the updated story
            $query = "SELECT slug FROM stories WHERE id = ? LIMIT 1";
            $stmt = $this->db->query($query, [$storyId]);
            $updatedStory = $stmt->fetch();
            
            $this->params['slug'] = $updatedStory['slug'];
            $this->show();
        } catch (\Exception $e) {
            // Rollback transaction
            $this->db->rollback();
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
            // Check if story exists
            $query = "SELECT * FROM stories WHERE id = ? LIMIT 1";
            $stmt = $this->db->query($query, [$storyId]);
            
            if ($stmt->rowCount() === 0) {
                $this->notFound('Story not found');
                return;
            }
            
            // Start transaction
            $this->db->beginTransaction();
            
            // Delete story associations
            $query = "DELETE FROM story_authors WHERE story_id = ?";
            $this->db->query($query, [$storyId]);
            
            $query = "DELETE FROM story_tags WHERE story_id = ?";
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
     * Calculate estimated reading time for a story
     * 
     * @param string $content The story content
     * @return string The estimated reading time
     */
    private function calculateReadingTime($content) {
        // Average reading speed: 200 words per minute
        $wordCount = str_word_count(strip_tags($content));
        $minutes = ceil($wordCount / 200);
        
        if ($minutes < 1) {
            return 'Less than a minute';
        } elseif ($minutes === 1) {
            return '1 minute';
        } else {
            return $minutes . ' minutes';
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
