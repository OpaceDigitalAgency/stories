<?php

// Page variables
$pageTitle = 'Author Form';
$currentPage = 'author-form';

// Include auth check
require_once '../includes/auth-check.php';

// Include database connection
require_once '../includes/db-connect.php';

// Include header
require_once '../includes/header.php';

try {
    // Ensure we have a database connection
    if (!isset($db) || !$db) {
        // Try to connect to the database directly
        try {
            $db = new PDO(
                'mysql:host=localhost;dbname=stories_db;charset=utf8mb4',
                'stories_user',
                '$tw1cac3*sOt',
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
        } catch (PDOException $e) {
            $errorMessage = "Database connection error: " . $e->getMessage();
            error_log("Database connection error in author-form.php: " . $e->getMessage());
        }
    }

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

<div class="content-wrapper">
    <div class="container-fluid">
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
                        <label class="form-label" for="author_type">Author Type <span class="text-danger">*</span></label>
                        <select id="author_type" name="author_type" class="form-control" required onchange="handleAuthorTypeChange()">
                            <option value="retail" <?php echo (isset($author['author_type']) && $author['author_type'] === 'retail') ? 'selected' : ''; ?>>Retail (Book Author)</option>
                            <option value="parent" <?php echo (isset($author['author_type']) && $author['author_type'] === 'parent') ? 'selected' : ''; ?>>Parent</option>
                            <option value="child" <?php echo (isset($author['author_type']) && $author['author_type'] === 'child') ? 'selected' : ''; ?>>Child</option>
                            <option value="educator" <?php echo (isset($author['author_type']) && $author['author_type'] === 'educator') ? 'selected' : ''; ?>>Educator</option>
                        </select>
                    </div>

                    <div id="age-field" class="form-group" style="display: <?php echo (isset($author['author_type']) && $author['author_type'] === 'child') ? 'block' : 'none'; ?>;">
                        <label class="form-label" for="age">Age <span class="text-danger">*</span></label>
                        <input type="number" id="age" name="age" class="form-control" min="1" max="21"
                               value="<?php echo htmlspecialchars($author['age'] ?? ''); ?>"
                               <?php echo (isset($author['author_type']) && $author['author_type'] === 'child') ? 'required' : ''; ?>>
                        <small class="form-text text-muted">Age must be between 1 and 21 years old</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="location">Location <span class="text-danger">*</span></label>
                        <input type="text" id="location" name="location" class="form-control" required
                               value="<?php echo htmlspecialchars($author['location'] ?? ''); ?>"
                               list="uk-locations" autocomplete="off"
                               maxlength="100"
                               pattern="[A-Za-z\s,.-]{2,100}"
                               title="Location must be between 2 and 100 characters, and can only contain letters, spaces, commas, periods, and hyphens">
                        <small class="form-text text-muted">Enter city, county, or country (max 100 characters)</small>
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
                            <option value="Leeds">Leeds</option>
                            <option value="Liverpool">Liverpool</option>
                            <option value="Newcastle">Newcastle</option>
                            <option value="Sheffield">Sheffield</option>
                            <option value="Bristol">Bristol</option>
                            <option value="Edinburgh">Edinburgh</option>
                            <option value="Glasgow">Glasgow</option>
                            <option value="Cardiff">Cardiff</option>
                            <option value="Belfast">Belfast</option>
                        </datalist>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Save Author</button>
                        <a href="authors.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
        // Handle author type change
        function handleAuthorTypeChange() {
            const authorType = document.getElementById('author_type').value;
            const ageField = document.getElementById('age-field');
            const ageInput = document.getElementById('age');
            
            if (authorType === 'child') {
                ageField.style.display = 'block';
                ageInput.required = true;
                if (!ageInput.value) {
                    ageInput.value = '7'; // Default age for child authors
                }
            } else {
                ageField.style.display = 'none';
                ageInput.required = false;
                ageInput.value = '';
            }
        }

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const authorType = document.getElementById('author_type').value;
            const age = document.getElementById('age').value;
            const location = document.getElementById('location').value;

            // Validate age for child authors
            if (authorType === 'child') {
                if (!age || age < 1 || age > 21) {
                    e.preventDefault();
                    alert('Age must be between 1 and 21 for child authors');
                    return;
                }
            }

            // Validate location
            if (!location || location.length < 2 || location.length > 100) {
                e.preventDefault();
                alert('Location must be between 2 and 100 characters');
                return;
            }

            // Validate location characters
            if (!/^[A-Za-z\s,.-]+$/.test(location)) {
                e.preventDefault();
                alert('Location can only contain letters, spaces, commas, periods, and hyphens');
                return;
            }
        });

        // Initialize form state
        handleAuthorTypeChange();
    </script>

<?php require_once '../includes/footer.php'; ?>
