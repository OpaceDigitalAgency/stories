<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - Stories Admin' : 'Stories Admin'; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="<?php echo ADMIN_URL; ?>/assets/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
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
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?php echo ADMIN_URL; ?>/index.php">
                Stories Admin
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
                    <li class="nav-item">
                        <a class="nav-link <?php echo $activeMenu === 'stories' ? 'active' : ''; ?>" href="<?php echo ADMIN_URL; ?>/stories.php">
                            <i class="fas fa-book"></i> Stories
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $activeMenu === 'authors' ? 'active' : ''; ?>" href="<?php echo ADMIN_URL; ?>/authors.php">
                            <i class="fas fa-users"></i> Authors
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $activeMenu === 'blog-posts' ? 'active' : ''; ?>" href="<?php echo ADMIN_URL; ?>/blog-posts.php">
                            <i class="fas fa-newspaper"></i> Blog Posts
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $activeMenu === 'directory-items' ? 'active' : ''; ?>" href="<?php echo ADMIN_URL; ?>/directory-items.php">
                            <i class="fas fa-folder"></i> Directory Items
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $activeMenu === 'games' ? 'active' : ''; ?>" href="<?php echo ADMIN_URL; ?>/games.php">
                            <i class="fas fa-gamepad"></i> Games
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $activeMenu === 'ai-tools' ? 'active' : ''; ?>" href="<?php echo ADMIN_URL; ?>/ai-tools.php">
                            <i class="fas fa-robot"></i> AI Tools
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $activeMenu === 'tags' ? 'active' : ''; ?>" href="<?php echo ADMIN_URL; ?>/tags.php">
                            <i class="fas fa-tags"></i> Tags
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $activeMenu === 'media' ? 'active' : ''; ?>" href="<?php echo ADMIN_URL; ?>/media.php">
                            <i class="fas fa-images"></i> Media
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $activeMenu === 'test-tools' ? 'active' : ''; ?>" href="<?php echo ADMIN_URL; ?>/test_tools.php">
                            <i class="fas fa-tools"></i> Test Tools
                        </a>
                    </li>
                </ul>
                <?php if (isset($user)): ?>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($user['name']); ?>
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
                <!-- Display error messages -->
                <?php if (!empty($errors)): ?>
                    <?php foreach ($errors as $error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <!-- Display success messages -->
                <?php if (!empty($success)): ?>
                    <?php foreach ($success as $message): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>