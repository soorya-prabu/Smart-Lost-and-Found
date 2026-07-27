<?php
// Database configuration and common helpers
session_start();

$db_host = 'localhost';
$db_user = 'root';
$db_pass = 'soorya25.';
$db_name = 'smart_lostfound';

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
if (!$conn) {
    die('Database connection failed: ' . mysqli_connect_error());
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: index.html');
        exit();
    }
}

function currentUserId() {
    return $_SESSION['user_id'] ?? null;
}

function currentUserRole() {
    return $_SESSION['role'] ?? null;
}

function sanitize($conn, $value) {
    return mysqli_real_escape_string($conn, trim($value));
}

function handleImageUpload($inputName, $targetDir = 'assets/images/') {
    if (!isset($_FILES[$inputName]) || $_FILES[$inputName]['error'] !== UPLOAD_ERR_OK) {
        return '';
    }
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    $ext = pathinfo($_FILES[$inputName]['name'], PATHINFO_EXTENSION);
    $safe = uniqid('img_') . '.' . strtolower($ext);
    $path = rtrim($targetDir, '/').'/'.$safe;
    if (move_uploaded_file($_FILES[$inputName]['tmp_name'], $path)) {
        return $path;
    }
    return '';
}

function redirectWith($location, $query = []) {
    $qs = http_build_query($query);
    header('Location: ' . $location . ($qs ? ('?' . $qs) : ''));
    exit();
}

// Activity logging function
function logActivity($conn, $action, $item_type = null, $item_id = null, $details = null) {
    $uid = currentUserId();
    $role = currentUserRole();
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $details_escaped = $details ? mysqli_real_escape_string($conn, $details) : null;
    $item_type_escaped = $item_type ? mysqli_real_escape_string($conn, $item_type) : null;
    
    $sql = "INSERT INTO activity_log (user_id, user_role, action, item_type, item_id, details, ip_address) 
            VALUES (" . ($uid ? (int)$uid : 'NULL') . ", 
                    '" . mysqli_real_escape_string($conn, $role ?? 'unknown') . "', 
                    '" . mysqli_real_escape_string($conn, $action) . "', 
                    " . ($item_type_escaped ? "'$item_type_escaped'" : 'NULL') . ", 
                    " . ($item_id ? (int)$item_id : 'NULL') . ", 
                    " . ($details_escaped ? "'$details_escaped'" : 'NULL') . ", 
                    '$ip')";
    mysqli_query($conn, $sql);
}
?>

