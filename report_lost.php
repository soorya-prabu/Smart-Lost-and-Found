<?php include 'config.php'; requireLogin();

// Auto-matching helper
function tryMatchLost($conn, $lostId) {
    // fetch newly created lost
    $lost = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM lost_items WHERE lost_id=$lostId"));
    if (!$lost) return;
    $lostName = strtolower(trim($lost['item_name']));
    $lostCat = strtolower(trim($lost['category']));
    $lostPlace = strtolower(trim($lost['place_lost']));

    $foundRes = mysqli_query($conn, "SELECT * FROM found_items WHERE status<>'Returned' AND status<>'Matched'");
    if (!$foundRes) return;
    
    while ($f = mysqli_fetch_assoc($foundRes)) {
        $foundName = strtolower(trim($f['item_name']));
        $foundCat = strtolower(trim($f['category']));
        $foundPlace = strtolower(trim($f['place_found']));
        
        $score = 0; 
        $fieldsMatched = 0;
        $matchFound = false;
        
        // Exact item name match (highest priority)
        if ($lostName === $foundName) {
            $score += 100;
            $fieldsMatched++;
            $matchFound = true;
        }
        // Item name similarity using similar_text
        else {
            $p1 = 0;
            similar_text($lostName, $foundName, $p1);
            if ($p1 >= 70) { 
                $score += $p1; 
                $fieldsMatched++; 
                $matchFound = true;
            }
            // Also check if one contains the other (for cases like "mouse" matching "computer mouse")
            elseif (strpos($lostName, $foundName) !== false || strpos($foundName, $lostName) !== false) {
                $score += 80;
                $fieldsMatched++;
                $matchFound = true;
            }
        }
        
        // Category similarity
        if ($lostCat === $foundCat) {
            $score += 30;
            $fieldsMatched++;
        } else {
            $p2 = 0;
            similar_text($lostCat, $foundCat, $p2);
            if ($p2 >= 50) { 
                $score += $p2 * 0.3; 
                $fieldsMatched++; 
            }
        }
        
        // Place similarity (optional but helpful)
        $p3 = 0;
        similar_text($lostPlace, $foundPlace, $p3);
        if ($p3 >= 40) { 
            $score += $p3 * 0.2; 
            $fieldsMatched++; 
        }

        // Keyword LIKE boost for partial matches
        $kw = mysqli_real_escape_string($conn, $lostName);
        $likeBoost = 0;
        if (stripos($f['item_name'], $lostName) !== false || stripos($f['place_found'], $lostName) !== false) {
            $likeBoost = 15;
        }

        // Match if item name matches well (primary criteria) OR if multiple fields match
        if ($matchFound && ($score + $likeBoost) >= 70) {
            // Insert match if not exists
            $lid = (int)$lostId; 
            $fid = (int)$f['found_id'];
            
            // Check if match already exists
            $existingMatch = mysqli_query($conn, "SELECT match_id FROM matches WHERE lost_id=$lid AND found_id=$fid");
            if (!$existingMatch || mysqli_num_rows($existingMatch) == 0) {
                mysqli_query($conn, "INSERT INTO matches (lost_id, found_id, status, notified) VALUES ($lid,$fid,'Pending','Yes')");
                $matchId = mysqli_insert_id($conn);
                // Log match creation
                logActivity($conn, "Match Created", "match", $matchId, "Lost: $lostName matched with Found: $foundName");
                // Update statuses as matched
                mysqli_query($conn, "UPDATE lost_items SET status='Matched' WHERE lost_id=$lid AND status<>'Returned'");
                mysqli_query($conn, "UPDATE found_items SET status='Matched' WHERE found_id=$fid AND status<>'Returned'");
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_name = sanitize($conn, $_POST['item_name'] ?? '');
    $category = sanitize($conn, $_POST['category'] ?? '');
    $place_lost = sanitize($conn, $_POST['place_lost'] ?? '');
    $date_lost = sanitize($conn, $_POST['date_lost'] ?? '');
    $description = sanitize($conn, $_POST['description'] ?? '');
    $image = handleImageUpload('image');

    if ($item_name && $category && $place_lost && $date_lost) {
        $uid = currentUserId();
        $sql = "INSERT INTO lost_items (user_id,item_name,category,place_lost,date_lost,description,image) VALUES ($uid,'$item_name','$category','$place_lost','$date_lost','$description','$image')";
        if (mysqli_query($conn, $sql)) {
            $lostId = mysqli_insert_id($conn);
            // Log activity
            logActivity($conn, "Reported Lost Item", "lost", $lostId, "Item: $item_name");
            // Try to match automatically
            tryMatchLost($conn, $lostId);
            // Redirect based on user role
            $redirectPage = (currentUserRole() === 'staff') ? 'staff_dashboard.php' : 'student_dashboard.php';
            redirectWith($redirectPage, ['msg' => 'Lost item reported. Matching in progress.']);
        } else {
            $error = 'Failed to submit. Please try again.';
        }
    } else {
        $error = 'Please fill all required fields.';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Report Lost Item</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/forms.css">
</head>
<body>
  <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom">
    <div class="container-fluid">
      <a class="navbar-brand" href="index.html">Smart Lost & Found</a>
      <div class="d-flex nav-gap">
        <?php 
        $backPage = (currentUserRole() === 'staff') ? 'staff_dashboard.php' : 'student_dashboard.php';
        ?>
        <a class="btn btn-outline-secondary" href="<?php echo $backPage; ?>">Back to Dashboard</a>
        <a class="btn btn-danger" href="logout.php">Logout</a>
      </div>
    </div>
  </nav>
  <div class="container py-4">
    <div class="center-card p-4">
      <h1 class="h4 mb-3">🔍 Report Lost Item</h1>
      <?php if (!empty($error)): ?><div class="alert alert-danger auto-fade py-2"><?php echo $error; ?></div><?php endif; ?>
      <form method="post" enctype="multipart/form-data">
        <div class="row g-3">
          <div class="col-md-6"><label class="form-label">Item Name</label><input name="item_name" class="form-control" required></div>
          <div class="col-md-6">
            <label class="form-label">Category</label>
            <select name="category" class="form-select" required style="color: #212529; background-color: #fff;">
              <option value="" style="color: #6c757d;">Select Category</option>
              <option value="Electronics" style="color: #212529;">Electronics</option>
              <option value="Accessories" style="color: #212529;">Accessories</option>
              <option value="Clothing" style="color: #212529;">Clothing</option>
              <option value="Books" style="color: #212529;">Books</option>
              <option value="Documents" style="color: #212529;">Documents</option>
              <option value="Keys" style="color: #212529;">Keys</option>
              <option value="Wallet/Purse" style="color: #212529;">Wallet/Purse</option>
              <option value="Jewelry" style="color: #212529;">Jewelry</option>
              <option value="Sports Equipment" style="color: #212529;">Sports Equipment</option>
              <option value="Other" style="color: #212529;">Other</option>
            </select>
          </div>
          <div class="col-md-6"><label class="form-label">Place Lost</label><input name="place_lost" class="form-control" required></div>
          <div class="col-md-6"><label class="form-label">Date</label><input type="date" name="date_lost" class="form-control" required></div>
          <div class="col-12"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="3"></textarea></div>
          <div class="col-12"><label class="form-label">Image (optional)</label><input type="file" name="image" accept="image/*" class="form-control"></div>
          <div class="col-12 d-flex justify-content-between">
            <?php 
            $backPage = (currentUserRole() === 'staff') ? 'staff_dashboard.php' : 'student_dashboard.php';
            ?>
            <a href="<?php echo $backPage; ?>" class="btn btn-outline-secondary">Back</a>
            <button class="btn btn-primary" type="submit">Submit</button>
          </div>
        </div>
      </form>
    </div>
  </div>
  <script src="assets/js/app.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

