<?php
/**
 * Simple Login Page
 * 
 * This is a simplified login page that doesn't rely on external resources.
 * It's designed to work even with strict Content Security Policy settings.
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
    if (empty($_POST['email']) || empty($_POST['password'])) {
        $errors[] = 'Email and password are required';
    } else {
        // Sanitize input
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
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

// Basic inline CSS styles
$styles = "
    body { font-family: Arial, sans-serif; background-color: #f8f9fa; margin: 0; padding: 0; }
    .container { max-width: 400px; margin: 100px auto; padding: 20px; background-color: #fff; border-radius: 5px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
    h1 { text-align: center; margin-bottom: 20px; color: #333; }
    .form-group { margin-bottom: 15px; }
    label { display: block; margin-bottom: 5px; font-weight: bold; }
    input[type='email'], input[type='password'] { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
    .checkbox-group { display: flex; align-items: center; margin-bottom: 15px; }
    .checkbox-group input { margin-right: 10px; }
    button { background-color: #007bff; color: white; border: none; padding: 10px 15px; border-radius: 4px; cursor: pointer; width: 100%; }
    button:hover { background-color: #0069d9; }
    .alert { padding: 10px; margin-bottom: 15px; border-radius: 4px; }
    .alert-danger { background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
    .alert-success { background-color: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
";

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Stories Admin</title>
    <style><?php echo $styles; ?></style>
</head>
<body>
    <div class="container">
        <h1>Stories Admin</h1>
        
        <!-- Display error messages -->
        <?php if (!empty($errors)): ?>
            <?php foreach ($errors as $error): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Display success messages -->
        <?php if (!empty($success)): ?>
            <?php foreach ($success as $message): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <div class="checkbox-group">
                <input type="checkbox" id="remember" name="remember" value="1">
                <label for="remember">Remember me</label>
            </div>
            
            <button type="submit">Login</button>
        </form>
    </div>
</body>
</html>