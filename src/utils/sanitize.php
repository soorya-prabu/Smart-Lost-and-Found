<?php
/**
 * Data Sanitization and Security Helpers
 */

if (!function_exists('sanitize')) {
    function sanitize($conn, $value) {
        if ($value === null) {
            return '';
        }
        $trimmed = trim((string)$value);
        if ($conn && is_object($conn)) {
            return mysqli_real_escape_string($conn, $trimmed);
        }
        return addslashes($trimmed);
    }
}

if (!function_exists('cleanHtml')) {
    function cleanHtml($value) {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}
