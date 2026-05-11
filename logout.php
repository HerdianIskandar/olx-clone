<?php
require_once 'config.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (isLoggedIn()) {
    // Log the logout activity
    $current_user = getCurrentUser();
    if ($current_user) {
        // You could add logging here if needed
        // error_log("User {$current_user['email']} logged out at " . date('Y-m-d H:i:s'));
    }
    
    // Unset all session variables
    $_SESSION = array();
    
    // Destroy the session
    session_destroy();
    
    // Delete session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Set success message
    setFlashMessage('success', 'Anda telah berhasil logout!');
    
    // Redirect to login page
    redirect("login.php");
} else {
    // If user is not logged in, redirect to login page
    redirect("login.php");
}
?>
