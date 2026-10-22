<?php
/**
 * System Activity Logger with Prepared Statements
 */

if (!function_exists('logActivity')) {
    function logActivity($conn, $action, $item_type = null, $item_id = null, $details = null) {
        if (!$conn || !is_object($conn)) {
            return false;
        }

        $uid = currentUserId();
        $role = currentUserRole() ?? 'guest';
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        $sql = "INSERT INTO activity_log (user_id, user_role, action, item_type, item_id, details, ip_address) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";

        return (bool)dbInsert($conn, $sql, [
            $uid ? (int)$uid : null,
            $role,
            $action,
            $item_type,
            $item_id ? (int)$item_id : null,
            $details,
            $ip
        ]);
    }
}
