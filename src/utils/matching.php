<?php
/**
 * Intelligent Lost & Found Matching Engine with Parameterized Queries
 */

if (!function_exists('calculateMatchScore')) {
    /**
     * Calculate matching score between a lost item and a found item
     *
     * @return array ['is_match' => bool, 'score' => float]
     */
    function calculateMatchScore($lostName, $lostCat, $lostPlace, $foundName, $foundCat, $foundPlace) {
        $lName = strtolower(trim((string)$lostName));
        $fName = strtolower(trim((string)$foundName));
        $lCat = strtolower(trim((string)$lostCat));
        $fCat = strtolower(trim((string)$foundCat));
        $lPlace = strtolower(trim((string)$lostPlace));
        $fPlace = strtolower(trim((string)$foundPlace));

        if (empty($lName) || empty($fName)) {
            return ['is_match' => false, 'score' => 0];
        }

        $score = 0;
        $nameMatched = false;

        // 1. Item Name Comparison
        if ($lName === $fName) {
            $score += 100;
            $nameMatched = true;
        } else {
            $pName = 0;
            similar_text($lName, $fName, $pName);
            if ($pName >= 70) {
                $score += $pName;
                $nameMatched = true;
            } elseif (strpos($lName, $fName) !== false || strpos($fName, $lName) !== false) {
                $score += 80;
                $nameMatched = true;
            }
        }

        // 2. Category Similarity
        if ($lCat && $fCat) {
            if ($lCat === $fCat) {
                $score += 30;
            } else {
                $pCat = 0;
                similar_text($lCat, $fCat, $pCat);
                if ($pCat >= 50) {
                    $score += ($pCat * 0.3);
                }
            }
        }

        // 3. Place Similarity
        if ($lPlace && $fPlace) {
            if ($lPlace === $fPlace) {
                $score += 20;
            } else {
                $pPlace = 0;
                similar_text($lPlace, $fPlace, $pPlace);
                if ($pPlace >= 40) {
                    $score += ($pPlace * 0.2);
                }
            }
        }

        // 4. Keyword boost
        $likeBoost = 0;
        if (stripos($fName, $lName) !== false || stripos($fPlace, $lName) !== false || stripos($lName, $fName) !== false) {
            $likeBoost = 15;
        }

        $totalScore = $score + $likeBoost;
        $isMatch = ($nameMatched && $totalScore >= 70);

        return [
            'is_match' => $isMatch,
            'score' => round($totalScore, 2)
        ];
    }
}

if (!function_exists('autoMatchItem')) {
    /**
     * Automatically attempt to match an item (lost or found) against available opposite items
     *
     * @param mysqli $conn
     * @param int $itemId
     * @param string $type 'lost' or 'found'
     * @return int Number of matches created
     */
    function autoMatchItem($conn, $itemId, $type = 'lost') {
        $itemId = (int)$itemId;
        $type = strtolower(trim($type));
        $matchesCreated = 0;

        if ($type === 'lost') {
            $target = dbFetchOne($conn, "SELECT * FROM lost_items WHERE lost_id = ? LIMIT 1", [$itemId]);
            if (!$target) return 0;

            $candidates = dbFetchAll($conn, "SELECT * FROM found_items WHERE status NOT IN ('Returned', 'Matched')");

            foreach ($candidates as $candidate) {
                $eval = calculateMatchScore(
                    $target['item_name'], $target['category'], $target['place_lost'],
                    $candidate['item_name'], $candidate['category'], $candidate['place_found']
                );

                if ($eval['is_match']) {
                    $lid = (int)$target['lost_id'];
                    $fid = (int)$candidate['found_id'];

                    $existing = dbFetchOne($conn, "SELECT match_id FROM matches WHERE lost_id = ? AND found_id = ?", [$lid, $fid]);
                    if (!$existing) {
                        $matchId = dbInsert($conn, "INSERT INTO matches (lost_id, found_id, status, notified) VALUES (?, ?, 'Pending', 'Yes')", [$lid, $fid]);
                        if ($matchId) {
                            $matchesCreated++;

                            if (function_exists('logActivity')) {
                                logActivity(
                                    $conn,
                                    "Match Created",
                                    "match",
                                    $matchId,
                                    "Lost: {$target['item_name']} matched with Found: {$candidate['item_name']} (Score: {$eval['score']})"
                                );
                            }

                            dbExecute($conn, "UPDATE lost_items SET status = 'Matched' WHERE lost_id = ? AND status <> 'Returned'", [$lid]);
                            dbExecute($conn, "UPDATE found_items SET status = 'Matched' WHERE found_id = ? AND status <> 'Returned'", [$fid]);
                        }
                    }
                }
            }
        } elseif ($type === 'found') {
            $target = dbFetchOne($conn, "SELECT * FROM found_items WHERE found_id = ? LIMIT 1", [$itemId]);
            if (!$target) return 0;

            $candidates = dbFetchAll($conn, "SELECT * FROM lost_items WHERE status NOT IN ('Returned', 'Matched')");

            foreach ($candidates as $candidate) {
                $eval = calculateMatchScore(
                    $candidate['item_name'], $candidate['category'], $candidate['place_lost'],
                    $target['item_name'], $target['category'], $target['place_found']
                );

                if ($eval['is_match']) {
                    $lid = (int)$candidate['lost_id'];
                    $fid = (int)$target['found_id'];

                    $existing = dbFetchOne($conn, "SELECT match_id FROM matches WHERE lost_id = ? AND found_id = ?", [$lid, $fid]);
                    if (!$existing) {
                        $matchId = dbInsert($conn, "INSERT INTO matches (lost_id, found_id, status, notified) VALUES (?, ?, 'Pending', 'Yes')", [$lid, $fid]);
                        if ($matchId) {
                            $matchesCreated++;

                            if (function_exists('logActivity')) {
                                logActivity(
                                    $conn,
                                    "Match Created",
                                    "match",
                                    $matchId,
                                    "Lost: {$candidate['item_name']} matched with Found: {$target['item_name']} (Score: {$eval['score']})"
                                );
                            }

                            dbExecute($conn, "UPDATE lost_items SET status = 'Matched' WHERE lost_id = ? AND status <> 'Returned'", [$lid]);
                            dbExecute($conn, "UPDATE found_items SET status = 'Matched' WHERE found_id = ? AND status <> 'Returned'", [$fid]);
                        }
                    }
                }
            }
        }

        return $matchesCreated;
    }
}

// Backward compatibility wrappers
if (!function_exists('tryMatchLost')) {
    function tryMatchLost($conn, $lostId) {
        return autoMatchItem($conn, $lostId, 'lost');
    }
}

if (!function_exists('tryMatchFound')) {
    function tryMatchFound($conn, $foundId) {
        return autoMatchItem($conn, $foundId, 'found');
    }
}
