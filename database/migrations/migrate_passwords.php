<?php
/**
 * Database Migration Script: Legacy Password Migration to Bcrypt
 *
 * This script audits the `users` table for legacy (MD5 or plaintext) password hashes.
 * It directly migrates known default seed accounts to Bcrypt and reports on accounts
 * that will be transparently upgraded upon next user login via `verifyPassword()`.
 *
 * Usage:
 *   CLI: php database/migrations/migrate_passwords.php
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../src/utils/auth.php';

$isCli = (php_sapi_name() === 'cli');

if (!$isCli && !isAdmin()) {
    http_response_code(403);
    die('Forbidden: Administrator privileges required to run migration.');
}

if (!$isCli) {
    echo '<pre style="font-family: monospace; background: #1e1e1e; color: #d4d4d4; padding: 20px; border-radius: 8px;">';
}

echo "=========================================================\n";
echo " Smart Lost & Found - Password Migration to Bcrypt\n";
echo "=========================================================\n\n";

// Known default credentials for seed accounts
$seedDefaults = [
    'alice@student.kec.edu' => 'alice123',
    'bob@kec.edu'           => 'bob123',
    'admin@kec.edu'         => 'admin123',
];

$users = dbFetchAll($conn, "SELECT user_id, name, email, password, role FROM users");

if (empty($users)) {
    echo "[!] No users found in database.\n";
    if (!$isCli) echo '</pre>';
    exit(0);
}

$totalUsers = count($users);
$alreadyBcrypt = 0;
$migratedDirectly = 0;
$pendingOnLogin = 0;

echo "Found {$totalUsers} user account(s) to inspect.\n\n";

foreach ($users as $u) {
    $currentHash = $u['password'];
    $isBcrypt = (strpos($currentHash, '$2y$') === 0 || strpos($currentHash, '$2a$') === 0 || strpos($currentHash, '$2b$') === 0);

    if ($isBcrypt) {
        $alreadyBcrypt++;
        echo " [OK]  User #{$u['user_id']} ({$u['email']}) is already using Bcrypt.\n";
    } else {
        // Check if it matches a known seed account
        $email = strtolower($u['email']);
        if (isset($seedDefaults[$email])) {
            $plain = $seedDefaults[$email];
            $newHash = password_hash($plain, PASSWORD_BCRYPT);
            dbExecute($conn, "UPDATE users SET password = ? WHERE user_id = ?", [$newHash, $u['user_id']], "si");
            $migratedDirectly++;
            echo " [UPD] User #{$u['user_id']} ({$u['email']}) migrated to Bcrypt (default seed match).\n";
        } else {
            $pendingOnLogin++;
            echo " [INFO] User #{$u['user_id']} ({$u['email']}) has legacy hash; will migrate to Bcrypt on next login.\n";
        }
    }
}

echo "\n---------------------------------------------------------\n";
echo "Migration Summary:\n";
echo " Total Users:              {$totalUsers}\n";
echo " Already Secure (Bcrypt):  {$alreadyBcrypt}\n";
echo " Migrated Now:             {$migratedDirectly}\n";
echo " Upgrading On Next Login:  {$pendingOnLogin}\n";
echo "---------------------------------------------------------\n";
echo "[SUCCESS] Migration process completed successfully.\n";

if (!$isCli) {
    echo '</pre>';
}
