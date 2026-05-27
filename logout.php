<?php
// Start output buffering to prevent any output before headers
if (!ob_get_level()) {
    ob_start();
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Destroy all session data
$_SESSION = array();

// Destroy the session cookie if it exists
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Clear output buffer
if (ob_get_level()) {
    ob_end_clean();
}

// Redirect to homepage
// Use relative path to avoid issues with different domains/configurations
header("Location: index.php", true, 302);
exit();