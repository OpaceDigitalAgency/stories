<?php
// Include auth check
require_once '../includes/auth-check.php';

// This is a processing script, no UI needed

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: authors.php");
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

    // Start transaction
    $db->beginTransaction();

    // Get form data
    $id = $_POST['id'] ?? null;
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $avatar_url = trim($_POST['avatar_url'] ?? '');
    $author_type = trim($_POST['author_type'] ?? 'retail');
    $age = ($author_type === 'child') ? (int)($_POST['age'] ?? null) : null;
    $location = trim($_POST['location'] ?? '');

    // Fix for empty avatar_url that should be NULL in database
    if (empty($avatar_url)) {
        $avatar_url = null;
    }

    // Log form data for debugging
    error_log("Save author form data: " . print_r($_POST, true));
    error_log("Avatar URL from form: " . ($avatar_url ?? 'NULL'));
    error_log("Avatar URL type: " . gettype($avatar_url));
    error_log("Avatar URL empty check: " . (empty($avatar_url) ? 'EMPTY' : 'NOT EMPTY'));
    error_log("Avatar URL null check: " . ($avatar_url === null ? 'NULL' : 'NOT NULL'));
    error_log("Avatar URL string check: " . (is_string($avatar_url) ? 'STRING' : 'NOT STRING'));
    error_log("Image updated flag: " . (isset($_POST['image_updated']) ? $_POST['image_updated'] : 'not set'));

    // Check if image_updated flag is set
    $image_updated = isset($_POST['image_updated']) && $_POST['image_updated'] === '1';
    error_log("Image updated flag: " . ($image_updated ? 'YES' : 'NO'));

    // If we have an ID and the image_updated flag is not set, check if there's an existing avatar_url in the database
    // This is to handle cases where the image was not updated
    if ($id && !$image_updated) {
        try {
            $checkStmt = $db->prepare("SELECT avatar_url FROM authors WHERE id = ?");
            $checkStmt->execute([$id]);
            $existingData = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if ($existingData && !empty($existingData['avatar_url']) && empty($avatar_url)) {
                // If we have an existing avatar_url in the database but not in the form,
                // use the one from the database
                $avatar_url = $existingData['avatar_url'];
                error_log("Using existing avatar_url from database: " . $avatar_url);
            }
        } catch (Exception $e) {
            error_log("Error checking existing avatar_url: " . $e->getMessage());
        }
    } else if ($image_updated) {
        // If image_updated flag is set, use the avatar_url from the form
        // If it's empty, set it to NULL to remove the image
        if (empty($avatar_url)) {
            $avatar_url = null;
            error_log("Removing avatar image (setting to NULL)");
        } else {
            error_log("Updating avatar image to: " . $avatar_url);
        }
    }

    // Always log the final avatar_url value that will be used
    error_log("Final avatar_url value to be saved: " . ($avatar_url ?? 'NULL'));

    // Validate required fields
    if (empty($name)) {
        throw new Exception("Please fill in the name field");
    }

    // Get all columns from authors table
    $columns = [];
    $stmt = $db->query("DESCRIBE authors");
    while ($row = $stmt->fetch()) {
        $columns[] = $row['Field'];
    }

    // Check if email column exists
    $hasEmailColumn = in_array('email', $columns);

    // Check if slug column exists
    $hasSlugColumn = in_array('slug', $columns);

    // Check if bio column exists
    $hasBioColumn = in_array('bio', $columns);

    // Check if avatar_url column exists
    $hasAvatarColumn = in_array('avatar_url', $columns);

    // Check if author_type column exists
    $hasAuthorTypeColumn = in_array('author_type', $columns);

    // Check if age column exists
    $hasAgeColumn = in_array('age', $columns);

    // Check if location column exists
    $hasLocationColumn = in_array('location', $columns);

    // Generate slug from name if not provided and slug column exists
    if ($hasSlugColumn) {
        // If no slug provided, generate from name
        if (empty($slug)) {
            // Convert to lowercase
            $slug = strtolower($name);
            // Replace accented characters
            $slug = iconv('UTF-8', 'ASCII//TRANSLIT', $slug);
            // Replace anything that's not alphanumeric with hyphens
            $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
            // Remove leading/trailing hyphens
            $slug = trim($slug, '-');
        }

        // Ensure unique slug
        $stmt = $db->prepare("SELECT COUNT(*) FROM authors WHERE slug = ? AND id != ?");
        $stmt->execute([$slug, $id ?? 0]);
        $count = $stmt->fetchColumn();

        if ($count > 0) {
            $originalSlug = $slug;
            $counter = 1;
            do {
                $slug = $originalSlug . '-' . $counter;
                $stmt->execute([$slug, $id ?? 0]);
                $count = $stmt->fetchColumn();
                $counter++;
            } while ($count > 0);
        }
    }

    // Validate email format if email column exists and email is provided
    if ($hasEmailColumn && !empty($email)) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }

        // Check if email is already in use by another author
        $stmt = $db->prepare("SELECT id FROM authors WHERE email = ? AND id != ?");
        $stmt->execute([$email, $id ?? 0]);
        if ($stmt->fetch()) {
            throw new Exception("Email is already in use by another author");
        }
    }

    // Validate slug format if slug column exists
    if ($hasSlugColumn) {
        if (empty($slug)) {
            throw new Exception("Please fill in the slug field");
        }

        if (!preg_match('/^[a-z0-9-]+$/', $slug)) {
            throw new Exception("Slug can only contain lowercase letters, numbers, and hyphens");
        }

        // Check if slug is already in use by another author
        $stmt = $db->prepare("SELECT id FROM authors WHERE slug = ? AND id != ?");
        $stmt->execute([$slug, $id ?? 0]);
        if ($stmt->fetch()) {
            throw new Exception("Slug is already in use by another author");
        }
    }

    if ($id) {
        // Verify author exists
        $stmt = $db->prepare("SELECT id FROM authors WHERE id = ?");
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            throw new Exception("Author not found");
        }

        // Update existing author
        $setClause = ["name = ?"];
        $params = [$name];

        if ($hasSlugColumn) {
            $setClause[] = "slug = ?";
            $params[] = $slug;
        }

        if ($hasEmailColumn) {
            $setClause[] = "email = ?";
            $params[] = $email;
        }

        if ($hasBioColumn) {
            $setClause[] = "bio = ?";
            $params[] = $bio;
        }

        if ($hasAvatarColumn) {
            // Handle NULL values properly in SQL
            if ($avatar_url === null) {
                $setClause[] = "avatar_url = NULL";
            } else {
                $setClause[] = "avatar_url = ?";
                $params[] = $avatar_url;
            }
        }

        if ($hasAuthorTypeColumn) {
            $setClause[] = "author_type = ?";
            $params[] = $author_type;
        }

        if ($hasAgeColumn) {
            $setClause[] = "age = ?";
            $params[] = $age;
        }

        if ($hasLocationColumn) {
            $setClause[] = "location = ?";
            $params[] = $location;
        }

        $setClause[] = "updated_at = NOW()";
        $params[] = $id; // Add ID for WHERE clause

        $sql = "UPDATE authors SET " . implode(', ', $setClause) . " WHERE id = ?";
        error_log("UPDATE SQL: " . $sql);
        error_log("UPDATE params: " . print_r($params, true));

        $stmt = $db->prepare($sql);
        $result = $stmt->execute($params);
        error_log("UPDATE result: " . ($result ? 'SUCCESS' : 'FAILED') . ", affected rows: " . $stmt->rowCount());

        // Verify the update
        $verifyStmt = $db->prepare("SELECT id, name, avatar_url FROM authors WHERE id = ?");
        $verifyStmt->execute([$id]);
        $verifiedAuthor = $verifyStmt->fetch(PDO::FETCH_ASSOC);
        error_log("Verified author after update: " . print_r($verifiedAuthor, true));

        $message = "Author updated successfully";
    } else {
        // Create new author
        $columns = ["name"];
        $placeholders = ["?"];
        $params = [$name];

        if ($hasSlugColumn) {
            $columns[] = "slug";
            $placeholders[] = "?";
            $params[] = $slug;
        }

        if ($hasEmailColumn) {
            $columns[] = "email";
            $placeholders[] = "?";
            $params[] = $email;
        }

        if ($hasBioColumn) {
            $columns[] = "bio";
            $placeholders[] = "?";
            $params[] = $bio;
        }

        if ($hasAvatarColumn) {
            $columns[] = "avatar_url";
            if ($avatar_url === null) {
                $placeholders[] = "NULL";
            } else {
                $placeholders[] = "?";
                $params[] = $avatar_url;
            }
        }

        if ($hasAuthorTypeColumn) {
            $columns[] = "author_type";
            $placeholders[] = "?";
            $params[] = $author_type;
        }

        if ($hasAgeColumn) {
            $columns[] = "age";
            $placeholders[] = "?";
            $params[] = $age;
        }

        if ($hasLocationColumn) {
            $columns[] = "location";
            $placeholders[] = "?";
            $params[] = $location;
        }

        // Don't add created_at and updated_at columns - they have DEFAULT values in the database
        // The error occurs because we're trying to set these values explicitly
        // Let MySQL handle the timestamps with its DEFAULT values

        $sql = "INSERT INTO authors (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
        error_log("INSERT SQL: " . $sql);
        error_log("INSERT params: " . print_r($params, true));

        $stmt = $db->prepare($sql);
        $result = $stmt->execute($params);
        error_log("INSERT result: " . ($result ? 'SUCCESS' : 'FAILED') . ", last insert ID: " . $db->lastInsertId());

        // Verify the insert
        $newId = $db->lastInsertId();
        $verifyStmt = $db->prepare("SELECT id, name, avatar_url FROM authors WHERE id = ?");
        $verifyStmt->execute([$newId]);
        $verifiedAuthor = $verifyStmt->fetch(PDO::FETCH_ASSOC);
        error_log("Verified author after insert: " . print_r($verifiedAuthor, true));

        $message = "Author created successfully";
    }

    // Commit transaction
    $db->commit();

    // Store success message and redirect
    $_SESSION['success'] = $message;

    header("Location: authors.php");
    exit;

} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($db)) {
        $db->rollBack();
    }

    error_log("Save author error: " . $e->getMessage());

    // Store error in session and redirect back to form
    $_SESSION['error'] = $e->getMessage();

    $redirect = $id ? "author-form.php?id=$id" : "author-form.php";
    header("Location: $redirect");
    exit;
}

// No footer needed for processing script
