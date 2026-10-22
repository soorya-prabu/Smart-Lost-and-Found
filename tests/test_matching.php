<?php
/**
 * Test Intelligent Matching Engine
 */
require_once dirname(__DIR__) . '/config/config.php';

function testExactItemMatch() {
    $res = calculateMatchScore('Black Wallet', 'Accessories', 'Library', 'Black Wallet', 'Accessories', 'Library');
    if (!$res['is_match']) {
        throw new Exception("Exact match failed to qualify as match. Result: " . json_encode($res));
    }
    if ($res['score'] < 100) {
        throw new Exception("Exact match score should be >= 100, got " . $res['score']);
    }
    return true;
}

function testSubstringItemMatch() {
    $res = calculateMatchScore('Computer Mouse', 'Electronics', 'Lab 3', 'Mouse', 'Electronics', 'Lab 3');
    if (!$res['is_match']) {
        throw new Exception("Substring match (Computer Mouse / Mouse) failed. Result: " . json_encode($res));
    }
    return true;
}

function testFuzzyNameMatch() {
    $res = calculateMatchScore('Casio Scientific Calculator', 'Electronics', 'Block 2', 'Casio Sci Calculator', 'Electronics', 'Block 2');
    if (!$res['is_match']) {
        throw new Exception("Fuzzy match failed. Result: " . json_encode($res));
    }
    return true;
}

function testDifferentItemsMismatch() {
    $res = calculateMatchScore('Umbrella', 'Accessories', 'Canteen', 'Gold Watch', 'Jewelry', 'Library');
    if ($res['is_match']) {
        throw new Exception("Different items (Umbrella / Gold Watch) incorrectly identified as match. Result: " . json_encode($res));
    }
    return true;
}

// Execute tests
testExactItemMatch();
testSubstringItemMatch();
testFuzzyNameMatch();
testDifferentItemsMismatch();

echo "[PASS] Matching engine scoring verified across exact, substring, fuzzy, and negative test cases." . PHP_EOL;
