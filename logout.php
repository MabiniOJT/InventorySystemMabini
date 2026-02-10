<?php
session_start();

// Clear all session variables
$_SESSION = array();

// Destroy the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Clear remember me cookie
setcookie('remember_email', '', time() - 3600, '/');

// Redirect to login page
$_SESSION = array();
session_start();
$_SESSION['success'] = 'You have been successfully logged out.';

header('Location: index.php');
exit;
?>
