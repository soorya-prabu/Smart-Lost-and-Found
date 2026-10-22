<?php
/**
 * Smart Lost & Found - CLI Test Suite Runner
 */

echo "========================================================" . PHP_EOL;
echo "  Smart Lost & Found - Automated Test Suite Runner" . PHP_EOL;
echo "========================================================" . PHP_EOL . PHP_EOL;

$tests = [
    'Database & Schema Test' => __DIR__ . '/test_db.php',
    'Intelligent Matching Engine Test' => __DIR__ . '/test_matching.php',
    'Authentication & Security Test' => __DIR__ . '/test_auth.php',
];

$passed = 0;
$failed = 0;
$startTime = microtime(true);

foreach ($tests as $title => $file) {
    echo "Running: $title... " . PHP_EOL;
    try {
        $phpBin = defined('PHP_BINARY') && file_exists(PHP_BINARY) ? PHP_BINARY : 'C:\\xampp\\php\\php.exe';
        exec(escapeshellarg($phpBin) . " " . escapeshellarg($file), $output, $returnCode);
        if ($returnCode === 0) {
            foreach ($output as $line) {
                echo "   " . $line . PHP_EOL;
            }
            $passed++;
        } else {
            echo "   [FAIL] Test exited with error code $returnCode" . PHP_EOL;
            foreach ($output as $line) {
                echo "   " . $line . PHP_EOL;
            }
            $failed++;
        }
    } catch (Throwable $e) {
        echo "   [FAIL] Exception: " . $e->getMessage() . PHP_EOL;
        $failed++;
    }
    echo PHP_EOL;
    $output = [];
}

$elapsed = round(microtime(true) - $startTime, 3);

echo "========================================================" . PHP_EOL;
echo "  Test Summary: $passed Passed, $failed Failed in {$elapsed}s" . PHP_EOL;
echo "========================================================" . PHP_EOL;

exit($failed > 0 ? 1 : 0);
