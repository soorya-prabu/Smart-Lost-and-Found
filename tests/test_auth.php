<?php
/**
 * Test Authentication and Sanitization Utilities
 */
require_once dirname(__DIR__) . '/config/config.php';

function testSanitizeAndCleanHtml($conn) {
    $raw = "<script>alert('xss');</script>";
    $clean = cleanHtml($raw);
    if (strpos($clean, '<script>') !== false) {
        throw new Exception("cleanHtml failed to escape tags: " . $clean);
    }

    $sqlInput = "admin' OR '1'='1";
    $escaped = sanitize($conn, $sqlInput);
    if (strpos($escaped, "\'") === false && strpos($escaped, "admin\'") === false) {
        throw new Exception("sanitize failed to escape quotes: " . $escaped);
    }
    return true;
}

function testVerifyPasswordLegacyAndBcrypt() {
    // Legacy MD5 test
    $md5Hash = md5('alice123');
    if (!verifyPassword('alice123', $md5Hash)) {
        throw new Exception("verifyPassword failed for valid MD5 password.");
    }
    if (verifyPassword('wrongpassword', $md5Hash)) {
        throw new Exception("verifyPassword falsely accepted wrong password for MD5.");
    }

    // Modern Bcrypt test
    $bcryptHash = password_hash('securepass', PASSWORD_BCRYPT);
    if (!verifyPassword('securepass', $bcryptHash)) {
        throw new Exception("verifyPassword failed for valid bcrypt password.");
    }
    if (verifyPassword('wrongpass', $bcryptHash)) {
        throw new Exception("verifyPassword falsely accepted wrong password for bcrypt.");
    }

    // Rehash flag test
    $needsRehash = false;
    verifyPassword('alice123', $md5Hash, $needsRehash);
    if (!$needsRehash) {
        throw new Exception("verifyPassword failed to set needsRehash=true for MD5 hash.");
    }

    $needsRehash = false;
    verifyPassword('securepass', $bcryptHash, $needsRehash);
    if ($needsRehash) {
        throw new Exception("verifyPassword falsely set needsRehash=true for valid Bcrypt hash.");
    }
    return true;
}

function testPreparedStatementEngine($conn) {
    $count = dbCount($conn, "SELECT COUNT(*) FROM users");
    if (!is_int($count) || $count < 1) {
        throw new Exception("dbCount failed to return valid integer count: " . var_export($count, true));
    }

    $user = dbFetchOne($conn, "SELECT user_id, email, role FROM users WHERE user_id = ?", [1], "i");
    if (!$user || !isset($user['email'])) {
        throw new Exception("dbFetchOne failed to fetch user #1 with parameterized bindings.");
    }

    $escapedQuery = dbFetchAll($conn, "SELECT user_id FROM users WHERE email = ?", ["alice@student.kec.edu"], "s");
    if (empty($escapedQuery)) {
        throw new Exception("dbFetchAll failed to fetch record with string parameter.");
    }
    return true;
}

// Execute tests
testSanitizeAndCleanHtml($conn);
testVerifyPasswordLegacyAndBcrypt();
testPreparedStatementEngine($conn);

echo "[PASS] Sanitization, Bcrypt auth/rehash, and prepared statement engine verified." . PHP_EOL;
