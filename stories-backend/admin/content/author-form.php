<?php
require_once '../../simple_auth.php';

// Database configuration
$config = [
    'host' => 'localhost',
    'name' => 'stories_db',
    'user' => 'stories_user',
    'password' => '$tw1cac3*sOt',
    'charset' => 'utf8mb4',
    'port' => 3306
];

// Initialize SimpleAuth
SimpleAuth::initDB($config);

// Check if user is logged in
if (!$user = SimpleAuth::check()) {
    header("Location: ../login.php");
    exit;
}

try {
    // Connect to database
    $db = new PDO(
        "mysql:host={$config['host']};dbname={$config['name']};charset={$config['charset']}",
        $config['user'],
        $config['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );

    // Get author if editing
    $author = null;
    if (isset($_GET['id'])) {
        $stmt = $db->prepare("SELECT * FROM authors WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $author = $stmt->fetch();
        
        if (!$author) {
            header("Location: authors.php");
            exit;
        }
    }

    // Get all columns from authors table
    $columns = [];
    $stmt = $db->query("DESCRIBE authors");
    while ($row = $stmt->fetch()) {
        $columns[] = $row['Field'];
    }

    // Check if slug column exists
    $hasSlugColumn = in_array('slug', $columns);
    
    // Check if email column exists
    $hasEmailColumn = in_array('email', $columns);

} catch (PDOException $e) {
    error_log("Author form error: " . $e->getMessage());
    $error = "Error loading form data. Please try again.";
}

// Check for error messages
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $author ? 'Edit' : 'Add'; ?> Author - Admin</title>
    <link rel="stylesheet" href="../assets/css/modern-admin.css">
</head>
<body>
    <header class="admin-header">
        <div class="header-container">
            <div class="logo-container">
                <div class="logo">S</div>
                <div class="logo-text">Stories Admin</div>
            </div>
            <div class="user-info">
                <span class="user-name">Welcome, <?php echo htmlspecialchars($user['name']); ?></span>
                <form method="POST" action="../logout.php" style="display: inline;">
                    <button type="submit" class="btn btn-danger btn-sm">Logout</button>
                </form>
            </div>
        </div>
    </header>

    <div class="container">
        <nav class="nav-menu">
            <form method="GET" style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                <button type="submit" formaction="../dashboard.php" class="nav-link">Dashboard</button>
                <button type="submit" formaction="stories.php" class="nav-link">Stories</button>
                <button type="submit" formaction="blog-posts.php" class="nav-link">Blog Posts</button>
                <button type="submit" formaction="authors.php" class="nav-link active">Authors</button>
                <button type="submit" formaction="tags.php" class="nav-link">Tags</button>
                <button type="submit" formaction="games.php" class="nav-link">Games</button>
                <button type="submit" formaction="directory-items.php" class="nav-link">Directory</button>
                <button type="submit" formaction="ai-tools.php" class="nav-link">AI Tools</button>
                <button type="submit" formaction="media.php" class="nav-link">Media</button>
            </form>
        </nav>

        <div class="page-header d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="page-title"><?php echo $author ? 'Edit' : 'Add'; ?> Author</h1>
                <p class="page-description">
                    <a href="authors.php" class="text-primary">← Back to Authors</a>
                </p>
            </div>
        </div>

        <?php if (isset($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="content-section mb-4">
            <div class="section-body">
                <form method="POST" action="save-author.php" class="content-form">
                    <?php if ($author): ?>
                        <input type="hidden" name="id" value="<?php echo $author['id']; ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label class="form-label" for="name">Name</label>
                        <input type="text" id="name" name="name" class="form-control" required
                               value="<?php echo htmlspecialchars($author['name'] ?? ''); ?>">
                    </div>

                    <?php if ($hasSlugColumn): ?>
                    <div class="form-group">
                        <label class="form-label" for="slug">Slug</label>
                        <input type="text" id="slug" name="slug" class="form-control" required
                               value="<?php echo htmlspecialchars($author['slug'] ?? ''); ?>"
                               placeholder="author-name-in-lowercase">
                        <small class="form-text text-muted">Use lowercase letters, numbers, and hyphens only. No spaces.</small>
                    </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label class="form-label" for="email">Email</label>
                        <input type="email" id="email" name="email" class="form-control" required
                               value="<?php echo htmlspecialchars($author['email'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="bio">Bio</label>
                        <textarea id="bio" name="bio" class="form-control" rows="5"><?php 
                            echo htmlspecialchars($author['bio'] ?? ''); 
                        ?></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="avatar_url">Avatar URL</label>
                        <input type="text" id="avatar_url" name="avatar_url" class="form-control"
                               value="<?php echo htmlspecialchars($author['avatar_url'] ?? ''); ?>"
                               placeholder="https://example.com/avatar.jpg">
                        <small class="form-text text-muted">Enter a URL to the author's avatar image. Leave empty to use the default avatar.</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="author_type">Author Type</label>
                        <select id="author_type" name="author_type" class="form-control">
                            <option value="retail" <?php echo (isset($author['author_type']) && $author['author_type'] === 'retail') ? 'selected' : ''; ?>>Retail (Book Author)</option>
                            <option value="parent" <?php echo (isset($author['author_type']) && $author['author_type'] === 'parent') ? 'selected' : ''; ?>>Parent</option>
                            <option value="child" <?php echo (isset($author['author_type']) && $author['author_type'] === 'child') ? 'selected' : ''; ?>>Child</option>
                            <option value="educator" <?php echo (isset($author['author_type']) && $author['author_type'] === 'educator') ? 'selected' : ''; ?>>Educator</option>
                        </select>
                    </div>

                    <div id="age-field" class="form-group" style="display: <?php echo (isset($author['author_type']) && $author['author_type'] === 'child') ? 'block' : 'none'; ?>;">
                        <label class="form-label" for="age">Age</label>
                        <select id="age" name="age" class="form-control">
                            <?php for ($i = 1; $i <= 21; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php echo (isset($author['age']) && $author['age'] == $i) ? 'selected' : ''; ?>><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="location">Location</label>
                        <input type="text" id="location" name="location" class="form-control"
                               value="<?php echo htmlspecialchars($author['location'] ?? ''); ?>"
                               list="uk-locations" autocomplete="off">
                        <datalist id="uk-locations">
                            <!-- Countries -->
                            <option value="England">England</option>
                            <option value="Scotland">Scotland</option>
                            <option value="Wales">Wales</option>
                            <option value="Northern Ireland">Northern Ireland</option>
                            
                            <!-- Major Cities -->
                            <option value="London">London</option>
                            <option value="Birmingham">Birmingham</option>
                            <option value="Manchester">Manchester</option>
                            <option value="Glasgow">Glasgow</option>
                            <option value="Edinburgh">Edinburgh</option>
                            <option value="Liverpool">Liverpool</option>
                            <option value="Bristol">Bristol</option>
                            <option value="Leeds">Leeds</option>
                            <option value="Sheffield">Sheffield</option>
                            <option value="Newcastle">Newcastle</option>
                            <option value="Belfast">Belfast</option>
                            <option value="Cardiff">Cardiff</option>
                            <option value="Nottingham">Nottingham</option>
                            <option value="Leicester">Leicester</option>
                            <option value="Coventry">Coventry</option>
                            <option value="Bradford">Bradford</option>
                            <option value="Stoke-on-Trent">Stoke-on-Trent</option>
                            <option value="Wolverhampton">Wolverhampton</option>
                            <option value="Plymouth">Plymouth</option>
                            <option value="Derby">Derby</option>
                            <option value="Southampton">Southampton</option>
                            <option value="Brighton">Brighton</option>
                            <option value="Hull">Hull</option>
                            <option value="Reading">Reading</option>
                            <option value="Preston">Preston</option>
                            <option value="York">York</option>
                            <option value="Oxford">Oxford</option>
                            <option value="Cambridge">Cambridge</option>
                            <option value="Aberdeen">Aberdeen</option>
                            <option value="Dundee">Dundee</option>
                            <option value="Swansea">Swansea</option>
                            <option value="Sunderland">Sunderland</option>
                            <option value="Norwich">Norwich</option>
                            <option value="Exeter">Exeter</option>
                            
                            <!-- Counties -->
                            <option value="Bedfordshire">Bedfordshire</option>
                            <option value="Berkshire">Berkshire</option>
                            <option value="Buckinghamshire">Buckinghamshire</option>
                            <option value="Cambridgeshire">Cambridgeshire</option>
                            <option value="Cheshire">Cheshire</option>
                            <option value="Cornwall">Cornwall</option>
                            <option value="Cumbria">Cumbria</option>
                            <option value="Derbyshire">Derbyshire</option>
                            <option value="Devon">Devon</option>
                            <option value="Dorset">Dorset</option>
                            <option value="Durham">Durham</option>
                            <option value="Essex">Essex</option>
                            <option value="Gloucestershire">Gloucestershire</option>
                            <option value="Hampshire">Hampshire</option>
                            <option value="Herefordshire">Herefordshire</option>
                            <option value="Hertfordshire">Hertfordshire</option>
                            <option value="Kent">Kent</option>
                            <option value="Lancashire">Lancashire</option>
                            <option value="Leicestershire">Leicestershire</option>
                            <option value="Lincolnshire">Lincolnshire</option>
                            <option value="Norfolk">Norfolk</option>
                            <option value="Northamptonshire">Northamptonshire</option>
                            <option value="Northumberland">Northumberland</option>
                            <option value="Nottinghamshire">Nottinghamshire</option>
                            <option value="Oxfordshire">Oxfordshire</option>
                            <option value="Rutland">Rutland</option>
                            <option value="Shropshire">Shropshire</option>
                            <option value="Somerset">Somerset</option>
                            <option value="Staffordshire">Staffordshire</option>
                            <option value="Suffolk">Suffolk</option>
                            <option value="Surrey">Surrey</option>
                            <option value="Sussex">Sussex</option>
                            <option value="Warwickshire">Warwickshire</option>
                            <option value="Wiltshire">Wiltshire</option>
                            <option value="Worcestershire">Worcestershire</option>
                            <option value="Yorkshire">Yorkshire</option>
                        </datalist>
                        <small class="form-text text-muted">Start typing to search for UK locations. If your location isn't listed, you can enter it manually.</small>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Save Author</button>
                        <a href="authors.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // Auto-generate slug from name
        document.addEventListener('DOMContentLoaded', function() {
            const nameInput = document.getElementById('name');
            const slugInput = document.getElementById('slug');
            const authorTypeSelect = document.getElementById('author_type');
            const ageField = document.getElementById('age-field');
            
            if (nameInput && slugInput) {
                nameInput.addEventListener('input', function() {
                    // Only auto-generate if slug is empty or hasn't been manually edited
                    if (!slugInput.value || slugInput._autoGenerated) {
                        const slug = nameInput.value
                            .toLowerCase()
                            .replace(/[^\w\s-]/g, '') // Remove special characters
                            .replace(/\s+/g, '-')     // Replace spaces with hyphens
                            .replace(/-+/g, '-');     // Replace multiple hyphens with single hyphen
                        
                        slugInput.value = slug;
                        slugInput._autoGenerated = true;
                    }
                });
                
                // Mark when user manually edits the slug
                slugInput.addEventListener('input', function() {
                    slugInput._autoGenerated = false;
                });
            }
            
            // Show/hide age field based on author type
            if (authorTypeSelect && ageField) {
                authorTypeSelect.addEventListener('change', function() {
                    if (this.value === 'child') {
                        ageField.style.display = 'block';
                    } else {
                        ageField.style.display = 'none';
                    }
                });
            }
        });
    </script>
</body>
</html>