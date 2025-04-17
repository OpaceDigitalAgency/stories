<div class="container-fluid">
    <!-- Welcome Section -->
    <div class="card mb-4 bg-light">
        <div class="card-body">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center">
                <div>
                    <h1 class="h2 mb-2">Welcome to Stories Admin</h1>
                    <p class="text-muted">Manage your content, authors, and more from this dashboard.</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?php echo ADMIN_URL; ?>/stories.php?action=new" class="btn btn-primary">
                        <i class="fas fa-plus-circle me-1"></i> Add New Story
                    </a>
                    <a href="<?php echo ADMIN_URL; ?>/media.php?action=upload" class="btn btn-success">
                        <i class="fas fa-upload me-1"></i> Upload Media
                    </a>
                    <div class="dropdown">
                        <button class="btn btn-secondary dropdown-toggle" type="button" id="quickActionsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-bolt me-1"></i> Quick Actions
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="quickActionsDropdown">
                            <li><a class="dropdown-item" href="<?php echo ADMIN_URL; ?>/blog-posts.php?action=new"><i class="fas fa-plus-circle me-1"></i> Add Blog Post</a></li>
                            <li><a class="dropdown-item" href="<?php echo ADMIN_URL; ?>/authors.php?action=new"><i class="fas fa-plus-circle me-1"></i> Add Author</a></li>
                            <li><a class="dropdown-item" href="<?php echo ADMIN_URL; ?>/tags.php?action=new"><i class="fas fa-plus-circle me-1"></i> Add Tag</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo ADMIN_URL; ?>/directory-items.php?action=new"><i class="fas fa-plus-circle me-1"></i> Add Directory Item</a></li>
                            <li><a class="dropdown-item" href="<?php echo ADMIN_URL; ?>/games.php?action=new"><i class="fas fa-plus-circle me-1"></i> Add Game</a></li>
                            <li><a class="dropdown-item" href="<?php echo ADMIN_URL; ?>/ai-tools.php?action=new"><i class="fas fa-plus-circle me-1"></i> Add AI Tool</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Items Needing Attention -->
    <?php if (isset($needsAttention) && !empty(array_filter($needsAttention))): ?>
    <div class="card mb-4 border-warning">
        <div class="card-header bg-warning text-white">
            <i class="fas fa-exclamation-circle me-1"></i>
            Items Needing Attention
        </div>
        <div class="card-body">
            <div class="row">
                <?php if (!empty($needsAttention['stories'])): ?>
                <div class="col-md-4 mb-3">
                    <div class="card h-100 border-warning">
                        <div class="card-header bg-warning bg-opacity-25">
                            <i class="fas fa-book me-1"></i> Stories
                            <span class="badge bg-warning text-dark ms-2"><?php echo count($needsAttention['stories']); ?></span>
                        </div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush">
                                <?php foreach ($needsAttention['stories'] as $item): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <?php echo htmlspecialchars($item['attributes']['title'] ?? 'Untitled'); ?>
                                    <a href="<?php echo ADMIN_URL; ?>/stories.php?action=edit&id=<?php echo $item['id']; ?>" class="btn btn-sm btn-warning">
                                        <i class="fas fa-exclamation-triangle me-1"></i> Review
                                    </a>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($needsAttention['blog_posts'])): ?>
                <div class="col-md-4 mb-3">
                    <div class="card h-100 border-warning">
                        <div class="card-header bg-warning bg-opacity-25">
                            <i class="fas fa-newspaper me-1"></i> Blog Posts
                            <span class="badge bg-warning text-dark ms-2"><?php echo count($needsAttention['blog_posts']); ?></span>
                        </div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush">
                                <?php foreach ($needsAttention['blog_posts'] as $item): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <?php echo htmlspecialchars($item['attributes']['title'] ?? 'Untitled'); ?>
                                    <a href="<?php echo ADMIN_URL; ?>/blog-posts.php?action=edit&id=<?php echo $item['id']; ?>" class="btn btn-sm btn-warning">
                                        <i class="fas fa-exclamation-triangle me-1"></i> Review
                                    </a>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($needsAttention['directory_items'])): ?>
                <div class="col-md-4 mb-3">
                    <div class="card h-100 border-warning">
                        <div class="card-header bg-warning bg-opacity-25">
                            <i class="fas fa-list me-1"></i> Directory Items
                            <span class="badge bg-warning text-dark ms-2"><?php echo count($needsAttention['directory_items']); ?></span>
                        </div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush">
                                <?php foreach ($needsAttention['directory_items'] as $item): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <?php echo htmlspecialchars($item['attributes']['title'] ?? 'Untitled'); ?>
                                    <a href="<?php echo ADMIN_URL; ?>/directory-items.php?action=edit&id=<?php echo $item['id']; ?>" class="btn btn-sm btn-warning">
                                        <i class="fas fa-exclamation-triangle me-1"></i> Review
                                    </a>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Content Statistics -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-chart-bar me-1"></i>
            Content Statistics
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 mb-4">
                    <div class="card bg-primary text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="me-3">
                                    <div class="text-white-75 small">Stories</div>
                                    <div class="text-lg fw-bold">
                                        <?php echo $stats['stories'] ?? 0; ?>
                                    </div>
                                </div>
                                <i class="fas fa-book fa-2x text-white-50"></i>
                            </div>
                        </div>
                        <div class="card-footer d-flex align-items-center justify-content-between">
                            <a class="small text-white stretched-link" href="<?php echo ADMIN_URL; ?>/stories.php">
                                <i class="fas fa-list me-1"></i> Manage Stories
                            </a>
                            <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-4">
                    <div class="card bg-success text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="me-3">
                                    <div class="text-white-75 small">Authors</div>
                                    <div class="text-lg fw-bold">
                                        <?php echo $stats['authors'] ?? 0; ?>
                                    </div>
                                </div>
                                <i class="fas fa-user-edit fa-2x text-white-50"></i>
                            </div>
                        </div>
                        <div class="card-footer d-flex align-items-center justify-content-between">
                            <a class="small text-white stretched-link" href="<?php echo ADMIN_URL; ?>/authors.php">
                                <i class="fas fa-list me-1"></i> Manage Authors
                            </a>
                            <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-4">
                    <div class="card bg-warning text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="me-3">
                                    <div class="text-white-75 small">Blog Posts</div>
                                    <div class="text-lg fw-bold">
                                        <?php echo $stats['blog_posts'] ?? 0; ?>
                                    </div>
                                </div>
                                <i class="fas fa-newspaper fa-2x text-white-50"></i>
                            </div>
                        </div>
                        <div class="card-footer d-flex align-items-center justify-content-between">
                            <a class="small text-white stretched-link" href="<?php echo ADMIN_URL; ?>/blog-posts.php">
                                <i class="fas fa-list me-1"></i> Manage Blog Posts
                            </a>
                            <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-4">
                    <div class="card bg-danger text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="me-3">
                                    <div class="text-white-75 small">Tags</div>
                                    <div class="text-lg fw-bold">
                                        <?php echo $stats['tags'] ?? 0; ?>
                                    </div>
                                </div>
                                <i class="fas fa-tags fa-2x text-white-50"></i>
                            </div>
                        </div>
                        <div class="card-footer d-flex align-items-center justify-content-between">
                            <a class="small text-white stretched-link" href="<?php echo ADMIN_URL; ?>/tags.php">
                                <i class="fas fa-list me-1"></i> Manage Tags
                            </a>
                            <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-3 mb-4">
                    <div class="card bg-info text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="me-3">
                                    <div class="text-white-75 small">Directory Items</div>
                                    <div class="text-lg fw-bold">
                                        <?php echo $stats['directory_items'] ?? 0; ?>
                                    </div>
                                </div>
                                <i class="fas fa-list fa-2x text-white-50"></i>
                            </div>
                        </div>
                        <div class="card-footer d-flex align-items-center justify-content-between">
                            <a class="small text-white stretched-link" href="<?php echo ADMIN_URL; ?>/directory-items.php">
                                <i class="fas fa-list me-1"></i> Manage Directory
                            </a>
                            <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-4">
                    <div class="card bg-secondary text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="me-3">
                                    <div class="text-white-75 small">Games</div>
                                    <div class="text-lg fw-bold">
                                        <?php echo $stats['games'] ?? 0; ?>
                                    </div>
                                </div>
                                <i class="fas fa-gamepad fa-2x text-white-50"></i>
                            </div>
                        </div>
                        <div class="card-footer d-flex align-items-center justify-content-between">
                            <a class="small text-white stretched-link" href="<?php echo ADMIN_URL; ?>/games.php">
                                <i class="fas fa-list me-1"></i> Manage Games
                            </a>
                            <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-4">
                    <div class="card bg-dark text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="me-3">
                                    <div class="text-white-75 small">AI Tools</div>
                                    <div class="text-lg fw-bold">
                                        <?php echo $stats['ai_tools'] ?? 0; ?>
                                    </div>
                                </div>
                                <i class="fas fa-robot fa-2x text-white-50"></i>
                            </div>
                        </div>
                        <div class="card-footer d-flex align-items-center justify-content-between">
                            <a class="small text-white stretched-link" href="<?php echo ADMIN_URL; ?>/ai-tools.php">
                                <i class="fas fa-list me-1"></i> Manage AI Tools
                            </a>
                            <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-4">
                    <div class="card bg-primary text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="me-3">
                                    <div class="text-white-75 small">Media Files</div>
                                    <div class="text-lg fw-bold">
                                        <?php echo $stats['media'] ?? 0; ?>
                                    </div>
                                </div>
                                <i class="fas fa-images fa-2x text-white-50"></i>
                            </div>
                        </div>
                        <div class="card-footer d-flex align-items-center justify-content-between">
                            <a class="small text-white stretched-link" href="<?php echo ADMIN_URL; ?>/media.php">
                                <i class="fas fa-photo-video me-1"></i> Manage Media
                            </a>
                            <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Content Tabs -->
    <div class="card mb-4">
        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs" id="recentContentTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="stories-tab" data-bs-toggle="tab" data-bs-target="#stories" type="button" role="tab" aria-controls="stories" aria-selected="true">
                        <i class="fas fa-book me-1"></i> Stories
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="authors-tab" data-bs-toggle="tab" data-bs-target="#authors" type="button" role="tab" aria-controls="authors" aria-selected="false">
                        <i class="fas fa-user-edit me-1"></i> Authors
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="blog-posts-tab" data-bs-toggle="tab" data-bs-target="#blog-posts" type="button" role="tab" aria-controls="blog-posts" aria-selected="false">
                        <i class="fas fa-newspaper me-1"></i> Blog Posts
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="directory-tab" data-bs-toggle="tab" data-bs-target="#directory" type="button" role="tab" aria-controls="directory" aria-selected="false">
                        <i class="fas fa-list me-1"></i> Directory
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="games-tab" data-bs-toggle="tab" data-bs-target="#games" type="button" role="tab" aria-controls="games" aria-selected="false">
                        <i class="fas fa-gamepad me-1"></i> Games
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="ai-tools-tab" data-bs-toggle="tab" data-bs-target="#ai-tools" type="button" role="tab" aria-controls="ai-tools" aria-selected="false">
                        <i class="fas fa-robot me-1"></i> AI Tools
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tags-tab" data-bs-toggle="tab" data-bs-target="#tags" type="button" role="tab" aria-controls="tags" aria-selected="false">
                        <i class="fas fa-tags me-1"></i> Tags
                    </button>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content" id="recentContentTabsContent">
                <!-- Stories Tab -->
                <div class="tab-pane fade show active" id="stories" role="tabpanel" aria-labelledby="stories-tab">
                    <div class="d-flex justify-content-between mb-3">
                        <h5 class="card-title">Recent Stories</h5>
                        <div>
                            <a href="<?php echo ADMIN_URL; ?>/stories.php?action=new" class="btn btn-sm btn-primary">
                                <i class="fas fa-plus-circle me-1"></i> Add New Story
                            </a>
                            <a href="<?php echo ADMIN_URL; ?>/stories.php" class="btn btn-sm btn-secondary">
                                <i class="fas fa-list me-1"></i> View All
                            </a>
                        </div>
                    </div>
                    
                    <?php if (isset($recentStories) && !empty($recentStories)): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Author</th>
                                        <th>Published</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentStories as $story): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($story['attributes']['title'] ?? 'Untitled'); ?></td>
                                            <td>
                                                <?php 
                                                    if (isset($story['attributes']['author']['data']['attributes']['name'])) {
                                                        echo htmlspecialchars($story['attributes']['author']['data']['attributes']['name']);
                                                    } else {
                                                        echo '<em>No author</em>';
                                                    }
                                                ?>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($story['attributes']['publishedAt'])); ?></td>
                                            <td>
                                                <a href="<?php echo ADMIN_URL; ?>/stories.php?action=view&id=<?php echo $story['id']; ?>" class="btn btn-sm btn-info" title="View Story">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="<?php echo ADMIN_URL; ?>/stories.php?action=edit&id=<?php echo $story['id']; ?>" class="btn btn-sm btn-primary" title="Edit Story">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">No recent stories found.</div>
                    <?php endif; ?>
                </div>
                
                <!-- Authors Tab -->
                <div class="tab-pane fade" id="authors" role="tabpanel" aria-labelledby="authors-tab">
                    <div class="d-flex justify-content-between mb-3">
                        <h5 class="card-title">Recent Authors</h5>
                        <div>
                            <a href="<?php echo ADMIN_URL; ?>/authors.php?action=new" class="btn btn-sm btn-primary">
                                <i class="fas fa-plus-circle me-1"></i> Add New Author
                            </a>
                            <a href="<?php echo ADMIN_URL; ?>/authors.php" class="btn btn-sm btn-secondary">
                                <i class="fas fa-list me-1"></i> View All
                            </a>
                        </div>
                    </div>
                    
                    <?php if (isset($recentAuthors) && !empty($recentAuthors)): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Stories</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentAuthors as $author): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($author['attributes']['name'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($author['attributes']['email'] ?? ''); ?></td>
                                            <td>
                                                <?php 
                                                    if (isset($author['attributes']['stories']['data'])) {
                                                        echo count($author['attributes']['stories']['data']);
                                                    } else {
                                                        echo '0';
                                                    }
                                                ?>
                                            </td>
                                            <td>
                                                <a href="<?php echo ADMIN_URL; ?>/authors.php?action=view&id=<?php echo $author['id']; ?>" class="btn btn-sm btn-info" title="View Author">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="<?php echo ADMIN_URL; ?>/authors.php?action=edit&id=<?php echo $author['id']; ?>" class="btn btn-sm btn-primary" title="Edit Author">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">No recent authors found.</div>
                    <?php endif; ?>
                </div>
                
                <!-- Blog Posts Tab -->
                <div class="tab-pane fade" id="blog-posts" role="tabpanel" aria-labelledby="blog-posts-tab">
                    <div class="d-flex justify-content-between mb-3">
                        <h5 class="card-title">Recent Blog Posts</h5>
                        <div>
                            <a href="<?php echo ADMIN_URL; ?>/blog-posts.php?action=new" class="btn btn-sm btn-primary">
                                <i class="fas fa-plus-circle me-1"></i> Add New Blog Post
                            </a>
                            <a href="<?php echo ADMIN_URL; ?>/blog-posts.php" class="btn btn-sm btn-secondary">
                                <i class="fas fa-list me-1"></i> View All
                            </a>
                        </div>
                    </div>
                    
                    <?php if (isset($recentBlogPosts) && !empty($recentBlogPosts)): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Author</th>
                                        <th>Published</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentBlogPosts as $post): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($post['attributes']['title'] ?? ''); ?></td>
                                            <td>
                                                <?php 
                                                    if (isset($post['attributes']['author']['data']['attributes']['name'])) {
                                                        echo htmlspecialchars($post['attributes']['author']['data']['attributes']['name']);
                                                    } else {
                                                        echo '<em>No author</em>';
                                                    }
                                                ?>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($post['attributes']['publishedAt'] ?? 'now')); ?></td>
                                            <td>
                                                <a href="<?php echo ADMIN_URL; ?>/blog-posts.php?action=view&id=<?php echo $post['id']; ?>" class="btn btn-sm btn-info" title="View Blog Post">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="<?php echo ADMIN_URL; ?>/blog-posts.php?action=edit&id=<?php echo $post['id']; ?>" class="btn btn-sm btn-primary" title="Edit Blog Post">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">No recent blog posts found.</div>
                    <?php endif; ?>
                </div>
                
                <!-- Directory Tab -->
                <div class="tab-pane fade" id="directory" role="tabpanel" aria-labelledby="directory-tab">
                    <div class="d-flex justify-content-between mb-3">
                        <h5 class="card-title">Recent Directory Items</h5>
                        <div>
                            <a href="<?php echo ADMIN_URL; ?>/directory-items.php?action=new" class="btn btn-sm btn-primary">
                                <i class="fas fa-plus-circle me-1"></i> Add New Directory Item
                            </a>
                            <a href="<?php echo ADMIN_URL; ?>/directory-items.php" class="btn btn-sm btn-secondary">
                                <i class="fas fa-list me-1"></i> View All
                            </a>
                        </div>
                    </div>
                    
                    <?php if (isset($recentDirectoryItems) && !empty($recentDirectoryItems)): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Category</th>
                                        <th>Added</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentDirectoryItems as $item): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item['attributes']['name'] ?? $item['attributes']['title'] ?? ''); ?></td>
                                            <td>
                                                <?php 
                                                    if (isset($item['attributes']['category'])) {
                                                        echo htmlspecialchars($item['attributes']['category']['data']['attributes']['name'] ?? '');
                                                    } else {
                                                        echo '<em>Uncategorized</em>';
                                                    }
                                                ?>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($item['attributes']['createdAt'] ?? 'now')); ?></td>
                                            <td>
                                                <a href="<?php echo ADMIN_URL; ?>/directory-items.php?action=view&id=<?php echo $item['id']; ?>" class="btn btn-sm btn-info" title="View Directory Item">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="<?php echo ADMIN_URL; ?>/directory-items.php?action=edit&id=<?php echo $item['id']; ?>" class="btn btn-sm btn-primary" title="Edit Directory Item">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">No recent directory items found.</div>
                    <?php endif; ?>
                </div>
                
                <!-- Games Tab -->
                <div class="tab-pane fade" id="games" role="tabpanel" aria-labelledby="games-tab">
                    <div class="d-flex justify-content-between mb-3">
                        <h5 class="card-title">Recent Games</h5>
                        <div>
                            <a href="<?php echo ADMIN_URL; ?>/games.php?action=new" class="btn btn-sm btn-primary">
                                <i class="fas fa-plus-circle me-1"></i> Add New Game
                            </a>
                            <a href="<?php echo ADMIN_URL; ?>/games.php" class="btn btn-sm btn-secondary">
                                <i class="fas fa-list me-1"></i> View All
                            </a>
                        </div>
                    </div>
                    
                    <?php if (isset($recentGames) && !empty($recentGames)): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Developer</th>
                                        <th>Added</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentGames as $game): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($game['attributes']['title'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($game['attributes']['developer'] ?? ''); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($game['attributes']['createdAt'] ?? 'now')); ?></td>
                                            <td>
                                                <a href="<?php echo ADMIN_URL; ?>/games.php?action=view&id=<?php echo $game['id']; ?>" class="btn btn-sm btn-info" title="View Game">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="<?php echo ADMIN_URL; ?>/games.php?action=edit&id=<?php echo $game['id']; ?>" class="btn btn-sm btn-primary" title="Edit Game">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">No recent games found.</div>
                    <?php endif; ?>
                </div>
                
                <!-- AI Tools Tab -->
                <div class="tab-pane fade" id="ai-tools" role="tabpanel" aria-labelledby="ai-tools-tab">
                    <div class="d-flex justify-content-between mb-3">
                        <h5 class="card-title">Recent AI Tools</h5>
                        <div>
                            <a href="<?php echo ADMIN_URL; ?>/ai-tools.php?action=new" class="btn btn-sm btn-primary">
                                <i class="fas fa-plus-circle me-1"></i> Add New AI Tool
                            </a>
                            <a href="<?php echo ADMIN_URL; ?>/ai-tools.php" class="btn btn-sm btn-secondary">
                                <i class="fas fa-list me-1"></i> View All
                            </a>
                        </div>
                    </div>
                    
                    <?php if (isset($recentAiTools) && !empty($recentAiTools)): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Provider</th>
                                        <th>Added</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentAiTools as $tool): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($tool['attributes']['name'] ?? $tool['attributes']['title'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($tool['attributes']['provider'] ?? ''); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($tool['attributes']['createdAt'] ?? 'now')); ?></td>
                                            <td>
                                                <a href="<?php echo ADMIN_URL; ?>/ai-tools.php?action=view&id=<?php echo $tool['id']; ?>" class="btn btn-sm btn-info" title="View AI Tool">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="<?php echo ADMIN_URL; ?>/ai-tools.php?action=edit&id=<?php echo $tool['id']; ?>" class="btn btn-sm btn-primary" title="Edit AI Tool">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">No recent AI tools found.</div>
                    <?php endif; ?>
                </div>
                
                <!-- Tags Tab -->
                <div class="tab-pane fade" id="tags" role="tabpanel" aria-labelledby="tags-tab">
                    <div class="d-flex justify-content-between mb-3">
                        <h5 class="card-title">Recent Tags</h5>
                        <div>
                            <a href="<?php echo ADMIN_URL; ?>/tags.php?action=new" class="btn btn-sm btn-primary">
                                <i class="fas fa-plus-circle me-1"></i> Add New Tag
                            </a>
                            <a href="<?php echo ADMIN_URL; ?>/tags.php" class="btn btn-sm btn-secondary">
                                <i class="fas fa-list me-1"></i> View All
                            </a>
                        </div>
                    </div>
                    
                    <?php if (isset($recentTags) && !empty($recentTags)): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Slug</th>
                                        <th>Items</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentTags as $tag): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($tag['attributes']['name'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($tag['attributes']['slug'] ?? ''); ?></td>
                                            <td>
                                                <?php
                                                    $itemCount = 0;
                                                    if (isset($tag['attributes']['stories']['data'])) {
                                                        $itemCount += count($tag['attributes']['stories']['data']);
                                                    }
                                                    if (isset($tag['attributes']['blog_posts']['data'])) {
                                                        $itemCount += count($tag['attributes']['blog_posts']['data']);
                                                    }
                                                    echo $itemCount;
                                                ?>
                                            </td>
                                            <td>
                                                <a href="<?php echo ADMIN_URL; ?>/tags.php?action=view&id=<?php echo $tag['id']; ?>" class="btn btn-sm btn-info" title="View Tag">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="<?php echo ADMIN_URL; ?>/tags.php?action=edit&id=<?php echo $tag['id']; ?>" class="btn btn-sm btn-primary" title="Edit Tag">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">No recent tags found.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php if (isset($apiErrors) && !empty($apiErrors)): ?>
    <!-- API Error Information (Only visible to admins for debugging) -->
    <div class="card mb-4">
        <div class="card-header bg-warning text-white">
            <i class="fas fa-exclamation-triangle me-1"></i>
            API Debugging Information
        </div>
        <div class="card-body">
            <div class="alert alert-warning">
                <p><strong>Note:</strong> The dashboard is showing some content counts from fallback sources because the API returned errors.</p>
                <p>This section is only visible to administrators and helps diagnose API issues.</p>
            </div>
            
            <h5>API Errors:</h5>
            <ul class="list-group">
                <?php foreach ($apiErrors as $endpoint => $error): ?>
                <li class="list-group-item list-group-item-warning">
                    <strong>Endpoint:</strong> <?php echo htmlspecialchars($endpoint); ?><br>
                    <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <?php endif; ?>
</div>
