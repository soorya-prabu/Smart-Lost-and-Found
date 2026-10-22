<?php
/**
 * Test Database Connection and Schema Integrity
 */
require_once dirname(__DIR__) . '/config/config.php';

function testDatabaseConnection($conn) {
    if (!$conn || !is_object($conn)) {
        throw new Exception("Database connection is null or invalid.");
    }
    if (mysqli_connect_errno()) {
        throw new Exception("Database connection error: " . mysqli_connect_error());
    }
    return true;
}

function testRequiredTables($conn) {
    $expectedTables = ['users', 'lost_items', 'found_items', 'matches', 'feedback', 'activity_log'];
    $res = mysqli_query($conn, "SHOW TABLES");
    $existing = [];
    while ($row = mysqli_fetch_row($res)) {
        $existing[] = strtolower($row[0]);
    }

    $missing = [];
    foreach ($expectedTables as $tbl) {
        if (!in_array(strtolower($tbl), $existing)) {
            $missing[] = $tbl;
        }
    }

    if (!empty($missing)) {
        throw new Exception("Missing tables in database: " . implode(', ', $missing));
    }
    return count($existing);
}

function testDefaultUsers($conn) {
    $res = mysqli_query($conn, "SELECT COUNT(*) FROM users WHERE role='admin'");
    $adminCount = mysqli_fetch_row($res)[0] ?? 0;
    if ($adminCount < 1) {
        throw new Exception("No admin user found in database. Please run database/smart_lostfound.sql.");
    }
    return true;
}

// Execute tests
testDatabaseConnection($conn);
$tableCount = testRequiredTables($conn);
testDefaultUsers($conn);

echo "[PASS] Database connection verified, $tableCount tables present, default accounts confirmed." . PHP_EOL;
