<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - Stories Admin' : 'Stories Admin'; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="<?php echo ADMIN_URL; ?>/assets/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome (Local) -->
    <link href="<?php echo ADMIN_URL; ?>/assets/css/all.min.css" rel="stylesheet">
    
    <!-- Flatpickr CSS -->
    <link href="<?php echo ADMIN_URL; ?>/assets/css/flatpickr.min.css" rel="stylesheet">
    
    <!-- Bootstrap Tags Input CSS -->
    <link href="<?php echo ADMIN_URL; ?>/assets/css/bootstrap-tagsinput.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="<?php echo ADMIN_URL; ?>/assets/css/admin.css" rel="stylesheet">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="<?php echo ADMIN_URL; ?>/index.php">
                <div class="logo-icon me-2 d-flex align-items-center justify-content-center">
                    <i class="fas fa-book"></i>
                </div>
                <span>Stories Admin</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $activeMenu === 'dashboard' ? 'active' : ''; ?>" href="<?php echo ADMIN_URL; ?>/index.php">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    </li>
                    
                    <!-- Content Management Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?php echo in_array($activeMenu, ['stories', 'authors', 'blog-posts']) ? 'active' : ''; ?>" href="#" id="contentDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-file-alt"></i> Content
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="contentDropdown">
                            <li>
                                <a class="dropdown-item <?php echo $activeMenu === 'stories' ? 'active' : ''; ?>" href="<?php echo ADMIN_URL; ?>/stories.php">
                                    <i class="fas fa-book"></i> Stories
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item <?php echo $activeMenu === 'authors' ? 'active' : ''; ?>" href="<?php echo ADMIN_URL; ?>/authors.php">
                                    <i class="fas fa-users"></i> Authors
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item <?php echo $activeMenu === 'blog-posts' ? 'active' : ''; ?>" href="<?php echo ADMIN_URL; ?>/blog-posts.php">
                                    <i class="fas fa-newspaper"></i> Blog Posts
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item <?php echo $activeMenu === 'tags' ? 'active' : ''; ?>" href="<?php echo ADMIN_URL; ?>/tags.php">
                                    <i class="fas fa-tags"></i> Tags
                                </a>
                            </li>
                        </ul>
                    </li>
                    
                    <!-- Features Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?php echo in_array($activeMenu, ['directory-items', 'games', 'ai-tools']) ? 'active' : ''; ?>" href="#" id="featuresDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-puzzle-piece"></i> Features
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="featuresDropdown">
                            <li>
                                <a class="dropdown-item <?php echo $activeMenu === 'directory-items' ? 'active' : ''; ?>" href="<?php echo ADMIN_URL; ?>/directory-items.php">
                                    <i class="fas fa-folder"></i> Directory Items
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item <?php echo $activeMenu === 'games' ? 'active' : ''; ?>" href="<?php echo ADMIN_URL; ?>/games.php">
                                    <i class="fas fa-gamepad"></i> Games
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item <?php echo $activeMenu === 'ai-tools' ? 'active' : ''; ?>" href="<?php echo ADMIN_URL; ?>/ai-tools.php">
                                    <i class="fas fa-robot"></i> AI Tools
                                </a>
                            </li>
                        </ul>
                    </li>
                    
                    <!-- Media -->
                    <li class="nav-item">
                        <a class="nav-link <?php echo $activeMenu === 'media' ? 'active' : ''; ?>" href="<?php echo ADMIN_URL; ?>/media.php">
                            <i class="fas fa-images"></i> Media
                        </a>
                    </li>
                    
                    <!-- Tools -->
                    <li class="nav-item">
                        <a class="nav-link <?php echo $activeMenu === 'test-tools' ? 'active' : ''; ?>" href="<?php echo ADMIN_URL; ?>/test_tools.php">
                            <i class="fas fa-tools"></i> Tools
                        </a>
                    </li>
                </ul>
                
                <!-- Help Button -->
                <ul class="navbar-nav me-2">
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#helpModal">
                            <i class="fas fa-question-circle"></i> Help
                        </a>
                    </li>
                </ul>
                
                <!-- User Menu -->
                <?php if (isset($user)): ?>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="avatar-circle me-2">
                                <span class="avatar-initials"><?php echo substr($user['name'], 0, 1); ?></span>
                            </div>
                            <span><?php echo htmlspecialchars($user['name']); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="<?php echo ADMIN_URL; ?>/profile.php"><i class="fas fa-user-cog"></i> Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo ADMIN_URL; ?>/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container-fluid">
        <div class="row">
            <!-- Content Wrapper -->
            <main class="col-md-12 ms-sm-auto px-md-4 content-wrapper">
                <!-- Breadcrumbs -->
                <?php if (isset($breadcrumbs) && !empty($breadcrumbs)): ?>
                <nav aria-label="breadcrumb" class="mt-3">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?php echo ADMIN_URL; ?>/index.php"><i class="fas fa-home"></i> Home</a></li>
                        <?php foreach ($breadcrumbs as $label => $url): ?>
                            <?php if ($url): ?>
                                <li class="breadcrumb-item"><a href="<?php echo $url; ?>"><?php echo htmlspecialchars($label); ?></a></li>
                            <?php else: ?>
                                <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($label); ?></li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ol>
                </nav>
                <?php endif; ?>

                <!-- Page Header -->
                <?php if (isset($pageTitle) && isset($pageDescription)): ?>
                <div class="page-header mb-4">
                    <h1 class="page-title"><?php echo htmlspecialchars($pageTitle); ?></h1>
                    <?php if (isset($pageDescription)): ?>
                        <p class="page-description text-muted"><?php echo htmlspecialchars($pageDescription); ?></p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- Display error messages -->
                <?php if (!empty($errors)): ?>
                    <?php foreach ($errors as $error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <div><?php echo htmlspecialchars($error); ?></div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <!-- Display success messages -->
                <?php if (!empty($success)): ?>
                    <?php foreach ($success as $message): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-check-circle me-2"></i>
                                <div><?php echo htmlspecialchars($message); ?></div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>