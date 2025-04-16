<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Dashboard</h1>
    </div>

    <!-- Content Statistics -->
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
                    <a class="small text-white stretched-link" href="<?php echo ADMIN_URL; ?>/stories.php">View Details</a>
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
                    <a class="small text-white stretched-link" href="<?php echo ADMIN_URL; ?>/authors.php">View Details</a>
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
                    <a class="small text-white stretched-link" href="<?php echo ADMIN_URL; ?>/blog-posts.php">View Details</a>
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
                    <a class="small text-white stretched-link" href="<?php echo ADMIN_URL; ?>/tags.php">View Details</a>
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
                    <a class="small text-white stretched-link" href="<?php echo ADMIN_URL; ?>/directory-items.php">View Details</a>
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
                    <a class="small text-white stretched-link" href="<?php echo ADMIN_URL; ?>/games.php">View Details</a>
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
                    <a class="small text-white stretched-link" href="<?php echo ADMIN_URL; ?>/ai-tools.php">View Details</a>
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
                    <a class="small text-white stretched-link" href="<?php echo ADMIN_URL; ?>/media.php">View Details</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Content -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-table me-1"></i>
            Recent Stories
        </div>
        <div class="card-body">
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
                                    <td><?php echo htmlspecialchars($story['attributes']['title']); ?></td>
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
                                        <a href="<?php echo ADMIN_URL; ?>/stories.php?action=view&id=<?php echo $story['id']; ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="<?php echo ADMIN_URL; ?>/stories.php?action=edit&id=<?php echo $story['id']; ?>" class="btn btn-sm btn-primary">
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
</div>