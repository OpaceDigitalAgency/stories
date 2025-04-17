<?php
/**
 * Login Page
 *
 * This page handles user authentication for the admin UI.
 *
 * @package Stories Admin
 * @version 1.0.0
 */

// Prevent any output before headers are sent
ob_start();

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors to browser
ini_set('log_errors', 1);
ini_set('error_log', '/home/stories/api.storiesfromtheweb.org/logs/api-error.log');

// Include required files
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/Validator.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize database
$db = Database::getInstance($config['db']);

// Initialize Auth
Auth::init($config['security']);

// Check if user is already logged in
$user = Auth::checkAuth();
if ($user) {
    // Redirect to dashboard
    header('Location: ' . ADMIN_URL . '/index.php');
    exit;
}

// Initialize variables
$errors = [];
$success = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate required fields
    if (!Validator::required($_POST, ['email', 'password'])) {
        $errors = Validator::getErrors();
    } else {
        // Validate email format
        if (!Validator::email($_POST['email'])) {
            $errors = Validator::getErrors();
        } else {
            // Sanitize input
            $email = Validator::sanitizeString($_POST['email']);
            $password = $_POST['password']; // Don't sanitize password before verification
            $remember = isset($_POST['remember']) ? (bool)$_POST['remember'] : false;
            
            // Authenticate user
            $user = Auth::login($email, $password, $remember);
            
            if ($user) {
                // Redirect to dashboard
                header('Location: ' . ADMIN_URL . '/index.php');
                exit;
            } else {
                $errors[] = 'Invalid email or password';
            }
        }
    }
}

// Get error messages from session
if (isset($_SESSION['errors'])) {
    $errors = array_merge($errors, $_SESSION['errors']);
    unset($_SESSION['errors']);
}

// Get success messages from session
if (isset($_SESSION['success'])) {
    $success = array_merge($success, $_SESSION['success']);
    unset($_SESSION['success']);
}

// Set page title
$pageTitle = 'Login';

// Include header without navbar
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Stories Admin</title>
    
    <!-- Bootstrap CSS -->
    <link href="<?php echo ADMIN_URL; ?>/assets/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="<?php echo ADMIN_URL; ?>/assets/css/all.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="<?php echo ADMIN_URL; ?>/assets/css/admin.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <!-- Display error messages -->
        <?php if (!empty($errors)): ?>
            <?php foreach ($errors as $error): ?>
                <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Display success messages -->
        <?php if (!empty($success)): ?>
            <?php foreach ($success as $message): ?>
                <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php include __DIR__ . '/views/auth/login.php'; ?>

    <!-- jQuery -->
    <script src="<?php echo ADMIN_URL; ?>/assets/js/jquery.min.js"></script>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="<?php echo ADMIN_URL; ?>/assets/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="<?php echo ADMIN_URL; ?>/assets/js/admin.js"></script>
</body>
</html>

// Initialize database
$db = Database::getInstance($config['db']);

// Initialize Auth
Auth::init($config['security']);

// Check if user is already logged in
$user = Auth::checkAuth();
if ($user) {
    // Redirect to dashboard
    header('Location: ' . ADMIN_URL . '/index.php');
    exit;
}

// Initialize variables
$errors = [];
$success = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate required fields
    if (!Validator::required($_POST, ['email', 'password'])) {
        $errors = Validator::getErrors();
    } else {
        // Validate email format
        if (!Validator::email($_POST['email'])) {
            $errors = Validator::getErrors();
        } else {
            // Sanitize input
            $email = Validator::sanitizeString($_POST['email']);
            $password = $_POST['password']; // Don't sanitize password before verification
            $remember = isset($_POST['remember']) ? (bool)$_POST['remember'] : false;
            
            // Authenticate user
            $user = Auth::login($email, $password, $remember);
            
            if ($user) {
                // Redirect to dashboard
                header('Location: ' . ADMIN_URL . '/index.php');
                exit;
            } else {
                $errors[] = 'Invalid email or password';
            }
        }
    }
}

// Get error messages from session
if (isset($_SESSION['errors'])) {
    $errors = array_merge($errors, $_SESSION['errors']);
    unset($_SESSION['errors']);
}

// Get success messages from session
if (isset($_SESSION['success'])) {
    $success = array_merge($success, $_SESSION['success']);
    unset($_SESSION['success']);
}

// Set page title
$pageTitle = 'Login';

// Include header without navbar
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Stories Admin</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="<?php echo ADMIN_URL; ?>/assets/css/admin.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <!-- Display error messages -->
        <?php if (!empty($errors)): ?>
            <?php foreach ($errors as $error): ?>
                <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Display success messages -->
        <?php if (!empty($success)): ?>
            <?php foreach ($success as $message): ?>
                <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php include __DIR__ . '/views/auth/login.php'; ?>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="<?php echo ADMIN_URL; ?>/assets/js/admin.js"></script>
</body>
</html>