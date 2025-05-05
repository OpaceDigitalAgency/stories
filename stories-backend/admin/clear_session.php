<?php
// Start session
session_start();

// Clear all session data
$_SESSION = array();

// Delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Clear auth_token cookie
setcookie('auth_token', '', time() - 3600, '/', '', false, true);

// Clear all cookies
foreach ($_COOKIE as $name => $value) {
    setcookie($name, '', time() - 3600, '/', '', false, true);
}

echo "<h1>Session and Cookies Cleared</h1>";
echo "<p>All session data and cookies have been cleared.</p>";
echo "<p><a href='login.php'>Go to Login Page</a></p>";
?>
