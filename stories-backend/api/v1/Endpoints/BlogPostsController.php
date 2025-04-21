<?php
namespace StoriesAPI\Endpoints;

use StoriesAPI\Core\BaseController;
use StoriesAPI\Utils\Response;
use StoriesAPI\Utils\Validator;

class BlogPostsController extends BaseController {
    public function index() {
        try {
            // Get pagination parameters
            $pagination = $this->getPaginationParams();
            $page = $pagination['page'];
            $pageSize = $pagination['pageSize'];
            $offset = ($page - 1) * $pageSize;
            
            // Get sort parameters
            $allowedSortFields = ['title', 'created_at', 'published_at'];
            $sort = parent::getSortParams($allowedSortFields);
            $sortField = $sort['field'] ?? 'created_at';
            
            // Add table alias to sort field
            $sortField = 'p.' . $sortField;
            
            $sortDirection = $sort['direction'] ?? 'DESC';
            $sortClause = "ORDER BY $sortField $sortDirection";
            
            // Get filter parameters
            $allowedFilterFields = ['title', 'slug', 'is_published', 'author_id'];
            $filters = $this->getFilterParams($allowedFilterFields);
            
            // Build WHERE clause
            $where = [];
            $params = [];
            
            foreach ($filters as $field => $value) {
                if ($value === '') {
                    continue;
                }
                
                if ($field === 'is_published') {
                    $where[] = "p.$field = ?";
                    $params[] = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
                } else {
                    $where[] = "p.$field LIKE ?";
                    $params[] = "%$value%";
                }
            }
            
            $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
            
            // Count total records
            $countQuery = "SELECT COUNT(*) as total FROM blog_posts p $whereClause";
            $stmt = $this->db->query($countQuery, $params);
            $total = $stmt->fetch()['total'];
            
            // Get blog posts with pagination
            $query = "SELECT
                p.id, p.title, p.content, p.slug, p.excerpt,
                p.featured_image, p.is_published, p.author_id,
                p.published_at AS publishedAt,
                p.created_at AS createdAt,
                p.updated_at AS updatedAt,
                a.name AS author_name,
                a.slug AS author_slug,
                GROUP_CONCAT(t.id) AS tag_ids,
                GROUP_CONCAT(t.name) AS tag_names,
                GROUP_CONCAT(t.slug) AS tag_slugs
                FROM blog_posts p
                LEFT JOIN authors a ON p.author_id = a.id
                LEFT JOIN blog_post_tags pt ON p.id = pt.post_id
                LEFT JOIN tags t ON pt.tag_id = t.id
                $whereClause
                GROUP BY p.id
                $sortClause
                LIMIT $offset, $pageSize";
            
            $stmt = $this->db->query($query, $params);
            $posts = $stmt->fetchAll();
            
            // Format blog posts
            $formattedPosts = [];
            foreach ($posts as $post) {
                // Format tags
                $tags = [];
                if ($post['tag_ids']) {
                    $tagIds = explode(',', $post['tag_ids']);
                    $tagNames = explode(',', $post['tag_names']);
                    $tagSlugs = explode(',', $post['tag_slugs']);
                    
                    foreach ($tagIds as $i => $tagId) {
                        $tags[] = [
                            'id' => $tagId,
                            'name' => $tagNames[$i],
                            'slug' => $tagSlugs[$i]
                        ];
                    }
                }
                
                // Format author
                $author = null;
                if ($post['author_id']) {
                    $author = [
                        'id' => $post['author_id'],
                        'name' => $post['author_name'],
                        'slug' => $post['author_slug']
                    ];
                }
                
                $formattedPosts[] = [
                    'id' => $post['id'],
                    'attributes' => [
                        'title' => $post['title'],
                        'content' => $post['content'],
                        'slug' => $post['slug'],
                        'excerpt' => $post['excerpt'],
                        'featuredImage' => $post['featured_image'],
                        'isPublished' => (bool)$post['is_published'],
                        'publishedAt' => $post['publishedAt'],
                        'createdAt' => $post['createdAt'],
                        'updatedAt' => $post['updatedAt'],
                        'author' => $author,
                        'tags' => $tags
                    ]
                ];
            }
            
            Response::sendPaginated($formattedPosts, $page, $pageSize, $total);
        } catch (\Exception $e) {
            $this->serverError('Failed to fetch blog posts: ' . $e->getMessage());
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
                $column = 'p.id';
                $value = (int)$identifier;
            } else {
                $column = 'p.slug';
                $value = Validator::sanitizeString($identifier);
            }
            
            $query = "SELECT
                p.id, p.title, p.content, p.slug, p.excerpt,
                p.featured_image, p.is_published, p.author_id,
                p.published_at AS publishedAt,
                p.created_at AS createdAt,
                p.updated_at AS updatedAt,
                a.name AS author_name,
                a.slug AS author_slug,
                GROUP_CONCAT(t.id) AS tag_ids,
                GROUP_CONCAT(t.name) AS tag_names,
                GROUP_CONCAT(t.slug) AS tag_slugs
                FROM blog_posts p
                LEFT JOIN authors a ON p.author_id = a.id
                LEFT JOIN blog_post_tags pt ON p.id = pt.post_id
                LEFT JOIN tags t ON pt.tag_id = t.id
                WHERE $column = ?
                GROUP BY p.id
                LIMIT 1";
            
            $stmt = $this->db->query($query, [$value]);
            $post = $stmt->fetch();
            
            if (!$post) {
                Response::sendError('Blog post not found', 404);
                return;
            }
            
            // Format tags
            $tags = [];
            if ($post['tag_ids']) {
                $tagIds = explode(',', $post['tag_ids']);
                $tagNames = explode(',', $post['tag_names']);
                $tagSlugs = explode(',', $post['tag_slugs']);
                
                foreach ($tagIds as $i => $tagId) {
                    $tags[] = [
                        'id' => $tagId,
                        'name' => $tagNames[$i],
                        'slug' => $tagSlugs[$i]
                    ];
                }
            }
            
            // Format author
            $author = null;
            if ($post['author_id']) {
                $author = [
                    'id' => $post['author_id'],
                    'name' => $post['author_name'],
                    'slug' => $post['author_slug']
                ];
            }
            
            $formattedPost = [
                'id' => $post['id'],
                'attributes' => [
                    'title' => $post['title'],
                    'content' => $post['content'],
                    'slug' => $post['slug'],
                    'excerpt' => $post['excerpt'],
                    'featuredImage' => $post['featured_image'],
                    'isPublished' => (bool)$post['is_published'],
                    'publishedAt' => $post['publishedAt'],
                    'createdAt' => $post['createdAt'],
                    'updatedAt' => $post['updatedAt'],
                    'author' => $author,
                    'tags' => $tags
                ]
            ];
            
            Response::sendSuccess($formattedPost);
        } catch (\Exception $e) {
            $this->serverError('Failed to fetch blog post: ' . $e->getMessage());
        }
    }
    
    public function create() {
        if (!Validator::required($this->request, ['title', 'content'])) {
            $this->badRequest('Title and content are required');
            return;
        }
        
        try {
            $title = Validator::sanitizeString($this->request['title']);
            $content = $this->request['content'];
            $excerpt = $this->request['excerpt'] ?? '';
            $slug = isset($this->request['slug']) ? Validator::sanitizeString($this->request['slug']) : $this->generateSlug($title);
            $featuredImage = $this->request['featured_image'] ?? null;
            $authorId = !empty($this->request['author_id']) ? (int)$this->request['author_id'] : null;
            $isPublished = isset($this->request['is_published']) ? (bool)$this->request['is_published'] : false;
            $publishedAt = isset($this->request['published_at']) ? $this->request['published_at'] : null;
            $tags = isset($this->request['tags']) ? array_map('intval', $this->request['tags']) : [];
            
            // Check if slug exists
            $stmt = $this->db->query("SELECT id FROM blog_posts WHERE slug = ?", [$slug]);
            if ($stmt->rowCount() > 0) {
                $slug = $this->generateUniqueSlug($slug);
            }
            
            // Check if author exists
            if ($authorId) {
                $stmt = $this->db->query("SELECT id FROM authors WHERE id = ?", [$authorId]);
                if ($stmt->rowCount() === 0) {
                    $this->badRequest('Author not found');
                    return;
                }
            }
            
            // Start transaction
            $this->db->beginTransaction();
            
            // Insert blog post
            $query = "INSERT INTO blog_posts (
                title, content, slug, excerpt, featured_image,
                author_id, is_published, published_at,
                created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
            
            $this->db->query($query, [
                $title,
                $content,
                $slug,
                $excerpt,
                $featuredImage,
                $authorId,
                $isPublished ? 1 : 0,
                $publishedAt
            ]);
            
            $postId = $this->db->lastInsertId();
            
            // Add tags
            if (!empty($tags)) {
                $values = array_fill(0, count($tags), "($postId, ?)");
                $query = "INSERT INTO blog_post_tags (post_id, tag_id) VALUES " . implode(', ', $values);
                $this->db->query($query, $tags);
            }
            
            $this->db->commit();
            
            // Return the created blog post
            $this->params['id'] = $postId;
            $this->show();
        } catch (\Exception $e) {
            $this->db->rollBack();
            $this->serverError('Failed to create blog post: ' . $e->getMessage());
        }
    }
    
    public function update() {
        $postId = isset($this->params['id']) ? (int)$this->params['id'] : null;
        if (!$postId) {
            $this->badRequest('Blog post ID is required');
            return;
        }
        
        try {
            // Check if blog post exists
            $stmt = $this->db->query("SELECT id FROM blog_posts WHERE id = ?", [$postId]);
            if ($stmt->rowCount() === 0) {
                $this->notFound('Blog post not found');
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
                    $stmt = $this->db->query("SELECT id FROM blog_posts WHERE slug = ? AND id != ?", [$slug, $postId]);
                    if ($stmt->rowCount() > 0) {
                        $slug = $this->generateUniqueSlug($slug);
                    }
                    $updates[] = "slug = ?";
                    $params[] = $slug;
                }
            }
            
            if (isset($this->request['content'])) {
                $updates[] = "content = ?";
                $params[] = $this->request['content'];
            }
            
            if (isset($this->request['excerpt'])) {
                $updates[] = "excerpt = ?";
                $params[] = $this->request['excerpt'];
            }
            
            if (isset($this->request['slug'])) {
                $slug = Validator::sanitizeString($this->request['slug']);
                $stmt = $this->db->query("SELECT id FROM blog_posts WHERE slug = ? AND id != ?", [$slug, $postId]);
                if ($stmt->rowCount() > 0) {
                    $slug = $this->generateUniqueSlug($slug);
                }
                $updates[] = "slug = ?";
                $params[] = $slug;
            }
            
            if (isset($this->request['featured_image'])) {
                $updates[] = "featured_image = ?";
                $params[] = $this->request['featured_image'];
            }
            
            if (isset($this->request['author_id'])) {
                $authorId = !empty($this->request['author_id']) ? (int)$this->request['author_id'] : null;
                if ($authorId) {
                    $stmt = $this->db->query("SELECT id FROM authors WHERE id = ?", [$authorId]);
                    if ($stmt->rowCount() === 0) {
                        $this->badRequest('Author not found');
                        return;
                    }
                }
                $updates[] = "author_id = ?";
                $params[] = $authorId;
            }
            
            if (isset($this->request['is_published'])) {
                $updates[] = "is_published = ?";
                $params[] = (bool)$this->request['is_published'] ? 1 : 0;
            }
            
            if (isset($this->request['published_at'])) {
                $updates[] = "published_at = ?";
                $params[] = $this->request['published_at'];
            }
            
            // Start transaction
            $this->db->beginTransaction();
            
            // Update blog post if there are changes
            if (!empty($updates)) {
                $updates[] = "updated_at = NOW()";
                $params[] = $postId;
                
                $query = "UPDATE blog_posts SET " . implode(", ", $updates) . " WHERE id = ?";
                $this->db->query($query, $params);
            }
            
            // Update tags if provided
            if (isset($this->request['tags'])) {
                $tags = array_map('intval', $this->request['tags']);
                
                // Remove existing tags
                $this->db->query("DELETE FROM blog_post_tags WHERE post_id = ?", [$postId]);
                
                // Add new tags
                if (!empty($tags)) {
                    $values = array_fill(0, count($tags), "($postId, ?)");
                    $query = "INSERT INTO blog_post_tags (post_id, tag_id) VALUES " . implode(', ', $values);
                    $this->db->query($query, $tags);
                }
            }
            
            $this->db->commit();
            
            // Return the updated blog post
            $this->params['id'] = $postId;
            $this->show();
        } catch (\Exception $e) {
            $this->db->rollBack();
            $this->serverError('Failed to update blog post: ' . $e->getMessage());
        }
    }
    
    public function delete() {
        $postId = isset($this->params['id']) ? (int)$this->params['id'] : null;
        if (!$postId) {
            $this->badRequest('Blog post ID is required');
            return;
        }
        
        try {
            // Check if blog post exists
            $stmt = $this->db->query("SELECT id FROM blog_posts WHERE id = ?", [$postId]);
            if ($stmt->rowCount() === 0) {
                $this->notFound('Blog post not found');
                return;
            }
            
            // Start transaction
            $this->db->beginTransaction();
            
            // Delete tags
            $this->db->query("DELETE FROM blog_post_tags WHERE post_id = ?", [$postId]);
            
            // Delete blog post
            $this->db->query("DELETE FROM blog_posts WHERE id = ?", [$postId]);
            
            $this->db->commit();
            
            Response::sendSuccess(['id' => $postId, 'deleted' => true]);
        } catch (\Exception $e) {
            $this->db->rollBack();
            $this->serverError('Failed to delete blog post: ' . $e->getMessage());
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
            $stmt = $this->db->query("SELECT id FROM blog_posts WHERE slug = ?", [$slug]);
            if ($stmt->rowCount() === 0) {
                break;
            }
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }
}