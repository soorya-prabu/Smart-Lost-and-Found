<?php
/**
 * Authentication and Session Utilities with Automatic Bcrypt Migration
 */

if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
}

if (!function_exists('requireLogin')) {
    function requireLogin($redirect = null) {
        if (!isLoggedIn()) {
            $target = $redirect !== null ? $redirect : url('index.php');
            redirectWith($target);
            exit();
        }
    }
}

if (!function_exists('requireRole')) {
    function requireRole($roles, $redirect = null) {
        $allowed = is_array($roles) ? $roles : [$roles];
        $role = currentUserRole();
        if (!isLoggedIn() || !in_array($role, $allowed)) {
            if ($redirect !== null) {
                $target = $redirect;
            } elseif (in_array('admin', $allowed)) {
                $target = url('auth/login_admin.php');
            } elseif (in_array('staff', $allowed)) {
                $target = url('auth/login_staff.php');
            } else {
                $target = url('auth/login_student.php');
            }
            redirectWith($target);
            exit();
        }
    }
}

if (!function_exists('currentUserId')) {
    function currentUserId() {
        return $_SESSION['user_id'] ?? null;
    }
}

if (!function_exists('currentUserRole')) {
    function currentUserRole() {
        return $_SESSION['role'] ?? null;
    }
}

if (!function_exists('currentUserName')) {
    function currentUserName() {
        return $_SESSION['name'] ?? 'User';
    }
}

if (!function_exists('verifyPassword')) {
    /**
     * Backward-compatible password verification with migration flag
     *
     * @param string $plainPassword User submitted plain password
     * @param string $storedHash Password hash currently in database
     * @param bool &$needsRehash Output flag set to true if password requires upgrading to Bcrypt
     * @return bool True if valid, false otherwise
     */
    function verifyPassword($plainPassword, $storedHash, &$needsRehash = false) {
        $needsRehash = false;
        if (empty($plainPassword) || empty($storedHash)) {
            return false;
        }

        // 1. Verify modern password_hash (Bcrypt, Argon2, etc.)
        if (password_verify($plainPassword, $storedHash)) {
            $needsRehash = password_needs_rehash($storedHash, PASSWORD_BCRYPT);
            return true;
        }

        // 2. Fallback to legacy MD5 hash verification
        if (md5($plainPassword) === $storedHash) {
            $needsRehash = true;
            return true;
        }

        // 3. Fallback for any legacy plaintext (safety check)
        if ($plainPassword === $storedHash) {
            $needsRehash = true;
            return true;
        }

        return false;
    }
}

if (!function_exists('migratePasswordToBcrypt')) {
    /**
     * Transparently rehash a verified password to Bcrypt and update the database record
     *
     * @param mysqli $conn
     * @param int $userId
     * @param string $plainPassword
     * @return bool
     */
    function migratePasswordToBcrypt($conn, $userId, $plainPassword) {
        if (!$conn || empty($userId) || empty($plainPassword)) {
            return false;
        }
        $newHash = password_hash($plainPassword, PASSWORD_BCRYPT);
        $sql = "UPDATE users SET password = ? WHERE user_id = ?";
        return (bool)dbExecute($conn, $sql, [$newHash, (int)$userId]);
    }
}
