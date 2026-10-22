<?php
require_once __DIR__ . '/../config/config.php';

if (isLoggedIn()) {
    logActivity($conn, "User Logout", "user", currentUserId(), "User logged out");
}

$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

redirectWith('index.php', ['msg' => 'You have been successfully logged out.']);
