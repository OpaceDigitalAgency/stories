<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Dashboard</h1>
    </div>

    <!-- Content Statistics -->
    <div class="row">
        <div class="col-md-3 col-sm-6 mb-4">
            <div class="card stats-card">
                <div class="card-body">
                    <div class="stats-icon text-primary">
                        <i class="fas fa-book"></i>
                    </div>
                    <div class="stats-number"><?php echo $stats['stories']; ?></div>
                    <div class="stats-title">Stories</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-4">
            <div class="card stats-card">
                <div class="card-body">
                    <div class="stats-icon text-success">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stats-number"><?php echo $stats['authors']; ?></div>
                    <div class="stats-title">Authors</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-4">
            <div class="card stats-card">
                <div class="card-body">
                    <div class="stats-icon text-info">
                        <i class="fas fa-newspaper"></i>
                    </div>
                    <div class="stats-number"><?php echo $stats['blog_posts']; ?></div>
                    <div class="stats-title">Blog Posts</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-4">
            <div class="card stats-card">
                <div class="card-body">
                    <div class="stats-icon text-warning">
                        <i class="fas fa-tags"></i>
                    </div>
                    <div class="stats-number"><?php echo $stats['tags']; ?></div>
                    <div class="stats-title">Tags</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-3 col-sm-6 mb-4">
            <div class="card stats-card">
                <div class="card-body">
                    <div class="stats-icon text-danger">
                        <i class="fas fa-folder"></i>
                    </div>
                    <div class="stats-number"><?php echo $stats['directory_items']; ?></div>
                    <div class="stats-title">Directory Items</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-4">
            <div class="card stats-card">
                <div class="card-body">
                    <div class="stats-icon text-secondary">
                        <i class="fas fa-gamepad"></i>
                    </div>
                    <div class="stats-number"><?php echo $stats['games']; ?></div>
                    <div class="stats-title">Games</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-4">
            <div class="card stats-card">
                <div class="card-body">
                    <div class="stats-icon text-dark">
                        <i class="fas fa-robot"></i>
                    </div>
                    <div class="stats-number"><?php echo $stats['ai_tools']; ?></div>
                    <div class="stats-title">AI Tools</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-4">
            <div class="card stats-card">
                <div class="card-body">
                    <div class="stats-icon text-primary">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="stats-number"><?php echo $stats['featured_stories']; ?></div>
                    <div class="stats-title">Featured Stories</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Content -->
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Stories</h5>
                    <a href="<?php echo ADMIN_URL; ?>/stories.php" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body">
                    <?php if (empty($recentStories)): ?>
                        <p class="text-muted">No stories found.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-sm">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Author</th>
                                        <th>Published</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentStories as $story): ?>
                                        <tr>
                                            <td>
                                                <a href="<?php echo ADMIN_URL; ?>/stories-edit.php?id=<?php echo $story['id']; ?>">
                                                    <?php echo htmlspecialchars($story['attributes']['title']); ?>
                                                </a>
                                            </td>
                                            <td>
                                                <?php 
                                                    if (isset($story['attributes']['author']['data'][0])) {
                                                        echo htmlspecialchars($story['attributes']['author']['data'][0]['attributes']['name']);
                                                    } else {
                                                        echo 'Unknown';
                                                    }
                                                ?>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($story['attributes']['publishedAt'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Blog Posts</h5>
                    <a href="<?php echo ADMIN_URL; ?>/blog-posts.php" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body">
                    <?php if (empty($recentBlogPosts)): ?>
                        <p class="text-muted">No blog posts found.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-sm">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Author</th>
                                        <th>Published</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentBlogPosts as $post): ?>
                                        <tr>
                                            <td>
                                                <a href="<?php echo ADMIN_URL; ?>/blog-posts-edit.php?id=<?php echo $post['id']; ?>">
                                                    <?php echo htmlspecialchars($post['attributes']['title']); ?>
                                                </a>
                                            </td>
                                            <td>
                                                <?php 
                                                    if (isset($post['attributes']['author']['data'][0])) {
                                                        echo htmlspecialchars($post['attributes']['author']['data'][0]['attributes']['name']);
                                                    } else {
                                                        echo 'Unknown';
                                                    }
                                                ?>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($post['attributes']['publishedAt'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Moderation Queue -->
    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Moderation Queue</h5>
                    <span class="badge bg-danger"><?php echo $stats['moderation_stories']; ?> Pending</span>
                </div>
                <div class="card-body">
                    <?php if (empty($moderationStories)): ?>
                        <p class="text-muted">No stories pending moderation.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-sm">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Author</th>
                                        <th>Submitted</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($moderationStories as $story): ?>
                                        <tr>
                                            <td>
                                                <a href="<?php echo ADMIN_URL; ?>/stories-edit.php?id=<?php echo $story['id']; ?>">
                                                    <?php echo htmlspecialchars($story['attributes']['title']); ?>
                                                </a>
                                            </td>
                                            <td>
                                                <?php 
                                                    if (isset($story['attributes']['author']['data'][0])) {
                                                        echo htmlspecialchars($story['attributes']['author']['data'][0]['attributes']['name']);
                                                    } else {
                                                        echo 'Unknown';
                                                    }
                                                ?>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($story['attributes']['createdAt'])); ?></td>
                                            <td>
                                                <a href="<?php echo ADMIN_URL; ?>/stories-edit.php?id=<?php echo $story['id']; ?>" class="btn btn-sm btn-primary">Review</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>