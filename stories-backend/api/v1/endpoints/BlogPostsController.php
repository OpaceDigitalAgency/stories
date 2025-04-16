<?php
/**
 * Blog Posts Controller
 * 
 * This controller handles CRUD operations for blog posts.
 * 
 * @package Stories API
 * @version 1.0.0
 */

namespace StoriesAPI\Endpoints;

use StoriesAPI\Core\BaseController;
use StoriesAPI\Utils\Response;
use StoriesAPI\Utils\Validator;

class BlogPostsController extends BaseController {
    /**
     * Get all blog posts with pagination, filtering, and sorting
     */
    public function index() {
        // Get pagination parameters
        $pagination = $this->getPaginationParams();
        $page = $pagination['page'];
        $pageSize = $pagination['pageSize'];
        $offset = ($page - 1) * $pageSize;
        
        // Get sort parameters
        $allowedSortFields = ['title', 'publishedAt'];
        $sort = $this->getSortParams($allowedSortFields);
        $sortClause = $sort ? "ORDER BY {$sort['field']} {$sort['direction']}" : "ORDER BY published_at DESC";
        
        // Get filter parameters
        $allowedFilterFields = ['title', 'slug', 'author_id'];
        $filters = $this->getFilterParams($allowedFilterFields);
        
        try {
            // Build the base query
            $baseQuery = "FROM blog_posts bp 
                LEFT JOIN blog_post_authors bpa ON bp.id = bpa.blog_post_id 
                LEFT JOIN authors a ON bpa.author_id = a.id";
            
            // Build the WHERE clause
            $whereData = $this->buildWhereClause($filters);
            $whereClause = $whereData['clause'];
            $params = $whereData['params'];
            
            // Count total records
            $countQuery = "SELECT COUNT(DISTINCT bp.id) as total $baseQuery $whereClause";
            $stmt = $this->db->query($countQuery, $params);
            $total = $stmt->fetch()['total'];
            
            // Get blog posts with pagination
            $query = "SELECT DISTINCT 
                bp.id, bp.title, bp.slug, bp.excerpt, bp.content, bp.published_at as publishedAt,
                bp.created_at as createdAt, bp.updated_at as updatedAt
                $baseQuery
                $whereClause
                $sortClause
                LIMIT $offset, $pageSize";
            
            $stmt = $this->db->query($query, $params);
            $blogPosts = $stmt->fetchAll();
            
            // Format blog posts to match Strapi response format
            $formattedBlogPosts = [];
            
            foreach ($blogPosts as $blogPost) {
                $blogPostId = $blogPost['id'];
                
                // Get blog post cover image
                $coverQuery = "SELECT id, url, width, height, alt_text FROM media WHERE entity_type = 'blog_post' AND entity_id = ? AND type = 'cover' LIMIT 1";
                $coverStmt = $this->db->query($coverQuery, [$blogPostId]);
                $cover = $coverStmt->fetch();
                
                // Get blog post author
                $authorQuery = "SELECT a.id, a.name, a.slug, a.bio FROM authors a 
                    JOIN blog_post_authors bpa ON a.id = bpa.author_id 
                    WHERE bpa.blog_post_id = ? LIMIT 1";
                $authorStmt = $this->db->query($authorQuery, [$blogPostId]);
                $author = $authorStmt->fetch();
                
                // Get author avatar
                if ($author) {
                    $avatarQuery = "SELECT id, url, width, height, alt_text FROM media WHERE entity_type = 'author' AND entity_id = ? AND type = 'avatar' LIMIT 1";
                    $avatarStmt = $this->db->query($avatarQuery, [$author['id']]);
                    $avatar = $avatarStmt->fetch();
                    
                    if ($avatar) {
                        $author['avatar'] = [
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
                }
                
                // Format cover image
                $formattedCover = null;
                if ($cover) {
                    $formattedCover = [
                        'data' => [
                            'id' => $cover['id'],
                            'attributes' => [
                                'url' => $cover['url'],
                                'width' => $cover['width'],
                                'height' => $cover['height'],
                                'alternativeText' => $cover['alt_text']
                            ]
                        ]
                    ];
                }
                
                // Format author
                $formattedAuthor = null;
                if ($author) {
                    $formattedAuthor = [
                        'data' => [
                            [
                                'id' => $author['id'],
                                'attributes' => [
                                    'name' => $author['name'],
                                    'slug' => $author['slug'],
                                    'bio' => $author['bio'],
                                    'avatar' => isset($author['avatar']) ? $author['avatar'] : null
                                ]
                            ]
                        ],
                        'meta' => [
                            'pagination' => [
                                'page' => 1,
                                'pageSize' => 1,
                                'pageCount' => 1,
                                'total' => 1
                            ]
                        ]
                    ];
                }
                
                // Build the formatted blog post
                $formattedBlogPost = [
                    'id' => $blogPostId,
                    'attributes' => [
                        'title' => $blogPost['title'],
                        'slug' => $blogPost['slug'],
                        'excerpt' => $blogPost['excerpt'],
                        'content' => $blogPost['content'],
                        'publishedAt' => $blogPost['publishedAt'],
                        'createdAt' => $blogPost['createdAt'],
                        'updatedAt' => $blogPost['updatedAt'],
                        'cover' => $formattedCover,
                        'author' => $formattedAuthor
                    ]
                ];
                
                $formattedBlogPosts[] = $formattedBlogPost;
            }
            
            // Send paginated response
            Response::sendPaginated($formattedBlogPosts, $page, $pageSize, $total);
        } catch (\Exception $e) {
            $this->serverError('Failed to fetch blog posts: ' . $e->getMessage());
        }
    }
    
    /**
     * Get a single blog post by slug
     */
    public function show() {
        // Validate slug
        $slug = isset($this->params['slug']) ? Validator::sanitizeString($this->params['slug']) : null;
        
        if (!$slug) {
            $this->badRequest('Blog post slug is required');
            return;
        }
        
        try {
            // Get blog post by slug
            $query = "SELECT 
                bp.id, bp.title, bp.slug, bp.excerpt, bp.content, bp.published_at as publishedAt,
                bp.created_at as createdAt, bp.updated_at as updatedAt
                FROM blog_posts bp 
                WHERE bp.slug = ? LIMIT 1";
            
            $stmt = $this->db->query($query, [$slug]);
            
            if ($stmt->rowCount() === 0) {
                $this->notFound('Blog post not found');
                return;
            }
            
            $blogPost = $stmt->fetch();
            $blogPostId = $blogPost['id'];
            
            // Get blog post cover image
            $coverQuery = "SELECT id, url, width, height, alt_text FROM media WHERE entity_type = 'blog_post' AND entity_id = ? AND type = 'cover' LIMIT 1";
            $coverStmt = $this->db->query($coverQuery, [$blogPostId]);
            $cover = $coverStmt->fetch();
            
            // Get blog post author
            $authorQuery = "SELECT a.id, a.name, a.slug, a.bio FROM authors a 
                JOIN blog_post_authors bpa ON a.id = bpa.author_id 
                WHERE bpa.blog_post_id = ? LIMIT 1";
            $authorStmt = $this->db->query($authorQuery, [$blogPostId]);
            $author = $authorStmt->fetch();
            
            // Get author avatar
            if ($author) {
                $avatarQuery = "SELECT id, url, width, height, alt_text FROM media WHERE entity_type = 'author' AND entity_id = ? AND type = 'avatar' LIMIT 1";
                $avatarStmt = $this->db->query($avatarQuery, [$author['id']]);
                $avatar = $avatarStmt->fetch();
                
                if ($avatar) {
                    $author['avatar'] = [
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
            }
            
            // Format cover image
            $formattedCover = null;
            if ($cover) {
                $formattedCover = [
                    'data' => [
                        'id' => $cover['id'],
                        'attributes' => [
                            'url' => $cover['url'],
                            'width' => $cover['width'],
                            'height' => $cover['height'],
                            'alternativeText' => $cover['alt_text']
                        ]
                    ]
                ];
            }
            
            // Format author
            $formattedAuthor = null;
            if ($author) {
                $formattedAuthor = [
                    'data' => [
                        [
                            'id' => $author['id'],
                            'attributes' => [
                                'name' => $author['name'],
                                'slug' => $author['slug'],
                                'bio' => $author['bio'],
                                'avatar' => isset($author['avatar']) ? $author['avatar'] : null
                            ]
                        ]
                    ],
                    'meta' => [
                        'pagination' => [
                            'page' => 1,
                            'pageSize' => 1,
                            'pageCount' => 1,
                            'total' => 1
                        ]
                    ]
                ];
            }
            
            // Build the formatted blog post
            $formattedBlogPost = [
                'id' => $blogPostId,
                'attributes' => [
                    'title' => $blogPost['title'],
                    'slug' => $blogPost['slug'],
                    'excerpt' => $blogPost['excerpt'],
                    'content' => $blogPost['content'],
                    'publishedAt' => $blogPost['publishedAt'],
                    'createdAt' => $blogPost['createdAt'],
                    'updatedAt' => $blogPost['updatedAt'],
                    'cover' => $formattedCover,
                    'author' => $formattedAuthor
                ]
            ];
            
            // Send response
            Response::sendSuccess(['data' => $formattedBlogPost]);
        } catch (\Exception $e) {
            $this->serverError('Failed to fetch blog post: ' . $e->getMessage());
        }
    }
    
    /**
     * Create a new blog post
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
        
        try {
            // Start transaction
            $this->db->beginTransaction();
            
            // Check if slug already exists
            $query = "SELECT id FROM blog_posts WHERE slug = ? LIMIT 1";
            $stmt = $this->db->query($query, [$slug]);
            
            if ($stmt->rowCount() > 0) {
                // Generate a unique slug
                $slug = $this->generateUniqueSlug($slug);
            }
            
            // Insert blog post
            $query = "INSERT INTO blog_posts (
                title, slug, excerpt, content, published_at, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $this->db->query($query, [
                $title,
                $slug,
                $excerpt,
                $content,
                date('Y-m-d H:i:s'), // publishedAt
                date('Y-m-d H:i:s'), // createdAt
                date('Y-m-d H:i:s')  // updatedAt
            ]);
            
            $blogPostId = $this->db->lastInsertId();
            
            // Associate with author (current user)
            $query = "INSERT INTO blog_post_authors (blog_post_id, author_id) VALUES (?, ?)";
            $this->db->query($query, [$blogPostId, $this->user['id']]);
            
            // Handle cover image if provided
            if (isset($this->request['cover']) && !empty($this->request['cover'])) {
                $coverUrl = Validator::sanitizeString($this->request['cover']);
                $query = "INSERT INTO media (
                    entity_type, entity_id, type, url, width, height, alt_text, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                
                $this->db->query($query, [
                    'blog_post',
                    $blogPostId,
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
            
            // Return the created blog post
            $this->params['slug'] = $slug;
            $this->show();
        } catch (\Exception $e) {
            // Rollback transaction
            $this->db->rollback();
            $this->serverError('Failed to create blog post: ' . $e->getMessage());
        }
    }
    
    /**
     * Update a blog post
     */
    public function update() {
        // Validate blog post ID
        $blogPostId = isset($this->params['id']) ? (int)$this->params['id'] : null;
        
        if (!$blogPostId) {
            $this->badRequest('Blog post ID is required');
            return;
        }
        
        try {
            // Check if blog post exists and user has permission to update it
            $query = "SELECT bp.* FROM blog_posts bp 
                JOIN blog_post_authors bpa ON bp.id = bpa.blog_post_id 
                WHERE bp.id = ? AND bpa.author_id = ? LIMIT 1";
            
            $stmt = $this->db->query($query, [$blogPostId, $this->user['id']]);
            
            if ($stmt->rowCount() === 0) {
                // Check if user is admin or editor
                if (!isset($this->user['role']) || !in_array($this->user['role'], ['admin', 'editor'])) {
                    $this->notFound('Blog post not found or you do not have permission to update it');
                    return;
                }
                
                // Admin or editor can update any blog post
                $query = "SELECT * FROM blog_posts WHERE id = ? LIMIT 1";
                $stmt = $this->db->query($query, [$blogPostId]);
                
                if ($stmt->rowCount() === 0) {
                    $this->notFound('Blog post not found');
                    return;
                }
            }
            
            $blogPost = $stmt->fetch();
            
            // Build update query
            $updates = [];
            $params = [];
            
            // Update fields if provided
            if (isset($this->request['title'])) {
                if (!Validator::length($this->request['title'], 'title', 3, 255)) {
                    $this->badRequest('Title must be between 3 and 255 characters', Validator::getErrors());
                    return;
                }
                
                $updates[] = "title = ?";
                $params[] = Validator::sanitizeString($this->request['title']);
            }
            
            if (isset($this->request['slug'])) {
                $slug = Validator::sanitizeString($this->request['slug']);
                
                // Check if slug already exists
                $query = "SELECT id FROM blog_posts WHERE slug = ? AND id != ? LIMIT 1";
                $stmt = $this->db->query($query, [$slug, $blogPostId]);
                
                if ($stmt->rowCount() > 0) {
                    // Generate a unique slug
                    $slug = $this->generateUniqueSlug($slug);
                }
                
                $updates[] = "slug = ?";
                $params[] = $slug;
            }
            
            if (isset($this->request['content'])) {
                if (!Validator::length($this->request['content'], 'content', 10)) {
                    $this->badRequest('Content must be at least 10 characters', Validator::getErrors());
                    return;
                }
                
                $updates[] = "content = ?";
                $params[] = $this->request['content'];
            }
            
            if (isset($this->request['excerpt'])) {
                $updates[] = "excerpt = ?";
                $params[] = Validator::sanitizeString($this->request['excerpt']);
            }
            
            // Add updated_at
            $updates[] = "updated_at = ?";
            $params[] = date('Y-m-d H:i:s');
            
            // Add blog post ID to params
            $params[] = $blogPostId;
            
            // Start transaction
            $this->db->beginTransaction();
            
            // Update blog post
            if (!empty($updates)) {
                $query = "UPDATE blog_posts SET " . implode(', ', $updates) . " WHERE id = ?";
                $this->db->query($query, $params);
            }
            
            // Handle cover image if provided
            if (isset($this->request['cover']) && !empty($this->request['cover'])) {
                $coverUrl = Validator::sanitizeString($this->request['cover']);
                
                // Check if cover already exists
                $query = "SELECT id FROM media WHERE entity_type = 'blog_post' AND entity_id = ? AND type = 'cover' LIMIT 1";
                $stmt = $this->db->query($query, [$blogPostId]);
                
                if ($stmt->rowCount() > 0) {
                    // Update existing cover
                    $coverId = $stmt->fetch()['id'];
                    $query = "UPDATE media SET 
                        url = ?, 
                        width = ?, 
                        height = ?, 
                        alt_text = ? 
                        WHERE id = ?";
                    
                    $this->db->query($query, [
                        $coverUrl,
                        isset($this->request['coverWidth']) ? (int)$this->request['coverWidth'] : 1200,
                        isset($this->request['coverHeight']) ? (int)$this->request['coverHeight'] : 800,
                        isset($this->request['coverAlt']) ? Validator::sanitizeString($this->request['coverAlt']) : $blogPost['title'],
                        $coverId
                    ]);
                } else {
                    // Insert new cover
                    $query = "INSERT INTO media (
                        entity_type, entity_id, type, url, width, height, alt_text, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                    
                    $this->db->query($query, [
                        'blog_post',
                        $blogPostId,
                        'cover',
                        $coverUrl,
                        isset($this->request['coverWidth']) ? (int)$this->request['coverWidth'] : 1200,
                        isset($this->request['coverHeight']) ? (int)$this->request['coverHeight'] : 800,
                        isset($this->request['coverAlt']) ? Validator::sanitizeString($this->request['coverAlt']) : $blogPost['title'],
                        date('Y-m-d H:i:s')
                    ]);
                }
            }
            
            // Commit transaction
            $this->db->commit();
            
            // Return the updated blog post
            $query = "SELECT slug FROM blog_posts WHERE id = ? LIMIT 1";
            $stmt = $this->db->query($query, [$blogPostId]);
            $updatedBlogPost = $stmt->fetch();
            
            $this->params['slug'] = $updatedBlogPost['slug'];
            $this->show();
        } catch (\Exception $e) {
            // Rollback transaction
            $this->db->rollback();
            $this->serverError('Failed to update blog post: ' . $e->getMessage());
        }
    }
    
    /**
     * Delete a blog post
     */
    public function delete() {
        // Validate blog post ID
        $blogPostId = isset($this->params['id']) ? (int)$this->params['id'] : null;
        
        if (!$blogPostId) {
            $this->badRequest('Blog post ID is required');
            return;
        }
        
        try {
            // Check if blog post exists and user has permission to delete it
            $query = "SELECT bp.* FROM blog_posts bp 
                JOIN blog_post_authors bpa ON bp.id = bpa.blog_post_id 
                WHERE bp.id = ? AND bpa.author_id = ? LIMIT 1";
            
            $stmt = $this->db->query($query, [$blogPostId, $this->user['id']]);
            
            if ($stmt->rowCount() === 0) {
                // Check if user is admin or editor
                if (!isset($this->user['role']) || !in_array($this->user['role'], ['admin', 'editor'])) {
                    $this->notFound('Blog post not found or you do not have permission to delete it');
                    return;
                }
                
                // Admin or editor can delete any blog post
                $query = "SELECT * FROM blog_posts WHERE id = ? LIMIT 1";
                $stmt = $this->db->query($query, [$blogPostId]);
                
                if ($stmt->rowCount() === 0) {
                    $this->notFound('Blog post not found');
                    return;
                }
            }
            
            // Start transaction
            $this->db->beginTransaction();
            
            // Delete blog post authors
            $query = "DELETE FROM blog_post_authors WHERE blog_post_id = ?";
            $this->db->query($query, [$blogPostId]);
            
            // Delete blog post media
            $query = "DELETE FROM media WHERE entity_type = 'blog_post' AND entity_id = ?";
            $this->db->query($query, [$blogPostId]);
            
            // Delete blog post
            $query = "DELETE FROM blog_posts WHERE id = ?";
            $this->db->query($query, [$blogPostId]);
            
            // Commit transaction
            $this->db->commit();
            
            // Send success response
            Response::sendSuccess(['message' => 'Blog post deleted successfully'], [], 200);
        } catch (\Exception $e) {
            // Rollback transaction
            $this->db->rollback();
            $this->serverError('Failed to delete blog post: ' . $e->getMessage());
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
            $query = "SELECT id FROM blog_posts WHERE slug = ? LIMIT 1";
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