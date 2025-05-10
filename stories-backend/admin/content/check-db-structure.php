<?php
// Include auth check
require_once '../includes/auth-check.php';

// Include database connection
require_once '../includes/db-connect.php';

// Page variables
$pageTitle = 'Check Database Structure';
$currentPage = 'check-db-structure';

// Include header
require_once '../includes/header.php';

// Get the authors table structure
$stmt = $db->query("DESCRIBE authors");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get the first few authors
$authorsStmt = $db->query("SELECT id, name, avatar_url FROM authors ORDER BY id DESC LIMIT 5");
$authors = $authorsStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="content-section mb-4">
    <div class="section-header">
        <h2 class="section-title">Authors Table Structure</h2>
    </div>
    <div class="section-body">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Field</th>
                    <th>Type</th>
                    <th>Null</th>
                    <th>Key</th>
                    <th>Default</th>
                    <th>Extra</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($columns as $column): ?>
                <tr>
                    <td><?php echo htmlspecialchars($column['Field']); ?></td>
                    <td><?php echo htmlspecialchars($column['Type']); ?></td>
                    <td><?php echo htmlspecialchars($column['Null']); ?></td>
                    <td><?php echo htmlspecialchars($column['Key']); ?></td>
                    <td><?php echo htmlspecialchars($column['Default'] ?? 'NULL'); ?></td>
                    <td><?php echo htmlspecialchars($column['Extra']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="content-section mb-4">
    <div class="section-header">
        <h2 class="section-title">Sample Authors Data</h2>
    </div>
    <div class="section-body">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Avatar URL</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($authors as $author): ?>
                <tr>
                    <td><?php echo $author['id']; ?></td>
                    <td><?php echo htmlspecialchars($author['name']); ?></td>
                    <td>
                        <?php if ($author['avatar_url'] === null): ?>
                            <span class="text-danger">NULL</span>
                        <?php elseif (empty($author['avatar_url'])): ?>
                            <span class="text-warning">Empty string</span>
                        <?php else: ?>
                            <code><?php echo htmlspecialchars($author['avatar_url']); ?></code>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="content-section mb-4">
    <div class="section-header">
        <h2 class="section-title">Test Direct SQL Update</h2>
    </div>
    <div class="section-body">
        <form action="direct-sql-update.php" method="get" class="mb-3">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="id">Author ID</label>
                        <input type="number" name="id" id="id" class="form-control" value="2048">
                    </div>
                </div>
                <div class="col-md-7">
                    <div class="form-group">
                        <label for="url">Image URL</label>
                        <input type="text" name="url" id="url" class="form-control" value="https://api.storiesfromtheweb.org/uploads/optimized/test-image.jpg">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary form-control">Update</button>
                    </div>
                </div>
            </div>
        </form>
        
        <div class="alert alert-info">
            <p>This form will execute a direct SQL update to set the avatar_url for the specified author.</p>
            <p>After updating, check the <a href="view-author-avatars.php">View Author Avatars</a> page to see if the update was successful.</p>
        </div>
    </div>
</div>

<?php
// Include footer
require_once '../includes/footer.php';
?>
