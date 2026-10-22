<?php
/**
 * Path, URL, Flash Messaging, and Redirection Helpers
 */

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(dirname(__DIR__)));
}

if (!defined('BASE_URL')) {
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $scriptDir = dirname($scriptName);
    // Strip subdirectories like /admin, /routes, /auth, /items, /student, /staff, /tests
    $cleanBase = preg_replace('#/(admin|routes|auth|student|staff|items|tests|components|src|database)(/.*)?$#i', '', $scriptDir);
    if ($cleanBase === '/' || $cleanBase === '.' || $cleanBase === '\\') {
        $cleanBase = '';
    }
    define('BASE_URL', rtrim($cleanBase, '/'));
}

if (!function_exists('baseUrl')) {
    function baseUrl($path = '') {
        $trimmed = ltrim((string)$path, '/');
        $cleanBase = ltrim(BASE_URL, '/');

        // Prevent double prefixing if path already includes the base segment
        if ($cleanBase !== '') {
            if ($trimmed === $cleanBase) {
                return '/' . $cleanBase;
            }
            if (strpos($trimmed, $cleanBase . '/') === 0) {
                return '/' . $trimmed;
            }
        }

        return BASE_URL . ($trimmed !== '' ? '/' . $trimmed : '');
    }
}

if (!function_exists('asset')) {
    function asset($path = '') {
        return baseUrl($path);
    }
}

if (!function_exists('url')) {
    function url($path = '') {
        return baseUrl($path);
    }
}

if (!function_exists('redirectWith')) {
    function redirectWith($location, $query = []) {
        $qs = http_build_query($query);
        $target = $location . ($qs ? (strpos($location, '?') !== false ? '&' : '?') . $qs : '');
        
        // If not an absolute URL with scheme
        if (!preg_match('#^https?://#i', $target)) {
            $target = baseUrl($target);
        }

        header('Location: ' . $target);
        exit();
    }
}

if (!function_exists('flashMessage')) {
    function flashMessage($key = 'msg') {
        if (!empty($_GET[$key])) {
            return htmlspecialchars($_GET[$key], ENT_QUOTES, 'UTF-8');
        }
        return null;
    }
}
