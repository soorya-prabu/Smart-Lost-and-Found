<?php include 'config.php'; requireLogin();
$uid = currentUserId(); $role = currentUserRole();

// Update match status
if (isset($_POST['update_status']) && isset($_POST['match_id'])) {
    $match_id = (int)$_POST['match_id'];
    $new_status = sanitize($conn, $_POST['status']);
    if (in_array($new_status, ['Pending', 'Contacted', 'Resolved'])) {
        mysqli_query($conn, "UPDATE matches SET status='$new_status' WHERE match_id=$match_id");
        redirectWith('match_notification.php', ['msg' => 'Status updated successfully']);
    }
}

$q = mysqli_query($conn, "SELECT m.*, 
  l.item_name AS l_item, l.description AS l_desc, l.image AS l_img, l.user_id AS l_uid,
  f.item_name AS f_item, f.description AS f_desc, f.image AS f_img, f.user_id AS f_uid
  FROM matches m
  JOIN lost_items l ON m.lost_id=l.lost_id
  JOIN found_items f ON m.found_id=f.found_id
  WHERE l.user_id=$uid OR f.user_id=$uid
  ORDER BY m.created_at DESC");

function getUserDetails($conn, $id){
  $r = mysqli_fetch_assoc(mysqli_query($conn, "SELECT name, email, role FROM users WHERE user_id=".(int)$id." LIMIT 1"));
  return $r ? $r : ['name' => 'Unknown', 'email' => '', 'role' => ''];
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Match Notifications</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/forms.css">
</head>
<body>
  <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom">
    <div class="container-fluid">
      <a class="navbar-brand" href="index.html">Smart Lost & Found</a>
      <div class="d-flex nav-gap">
        <?php if ($role==='staff'): ?>
          <a class="btn btn-outline-secondary" href="staff_dashboard.php">Back</a>
        <?php else: ?>
          <a class="btn btn-outline-secondary" href="student_dashboard.php">Back</a>
        <?php endif; ?>
        <a class="btn btn-danger" href="logout.php">Logout</a>
      </div>
    </div>
  </nav>

  <div class="container py-4">
    <?php if (!empty($_GET['msg'])): ?><div class="alert alert-success auto-fade py-2"><?php echo htmlspecialchars($_GET['msg']); ?></div><?php endif; ?>
    <h2 class="h5 mb-3">Notifications</h2>
    <div class="row g-3">
      <?php 
      if ($q && mysqli_num_rows($q) > 0):
        while($m = mysqli_fetch_assoc($q)): 
          // Determine which user is the "other" user (the matched person)
          $isLostOwner = ($m['l_uid'] == $uid);
          $otherUid = $isLostOwner ? $m['f_uid'] : $m['l_uid'];
          $otherUser = getUserDetails($conn, $otherUid);
          
          // Get details of both users for display
          $lostUser = getUserDetails($conn, $m['l_uid']);
          $foundUser = getUserDetails($conn, $m['f_uid']);
      ?>
      <div class="col-12">
        <div class="card">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-3">
              <div>
                <h5 class="mb-1" style="color: #212529;"><strong>Potential Match</strong> <span class="badge text-bg-<?php echo $m['status']=='Resolved'?'success':($m['status']=='Contacted'?'warning':'info'); ?>"><?php echo htmlspecialchars($m['status']); ?></span></h5>
                <div style="color: #6c757d; font-size: 0.875rem;">Match created: <?php echo date('M d, Y h:i A', strtotime($m['created_at'])); ?></div>
              </div>
              <span class="badge text-bg-success">Notified</span>
            </div>
            
            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <div class="border rounded p-3 bg-white">
                  <h6 class="mb-2" style="color: #dc3545; font-weight: bold;">🔴 Lost Item</h6>
                  <div class="mb-2" style="color: #212529;">
                    <strong style="color: #212529;">Item:</strong> <span style="color: #212529;"><?php echo htmlspecialchars($m['l_item']); ?></span>
                  </div>
                  <?php if ($m['l_desc']): ?>
                  <div class="mb-2" style="color: #495057; font-size: 0.9rem;">
                    <strong style="color: #212529;">Description:</strong> <span style="color: #495057;"><?php echo htmlspecialchars($m['l_desc']); ?></span>
                  </div>
                  <?php endif; ?>
                  <div class="mt-2 pt-2 border-top">
                    <div class="small" style="color: #212529;"><strong>Reported by:</strong></div>
                    <div style="color: #212529; margin: 5px 0;"><?php echo htmlspecialchars($lostUser['name']); ?> <span class="badge text-bg-secondary"><?php echo ucfirst($lostUser['role']); ?></span></div>
                    <div style="color: #0d6efd; font-size: 0.9rem; margin-top: 5px;">📧 <a href="mailto:<?php echo htmlspecialchars($lostUser['email']); ?>" style="color: #0d6efd; text-decoration: none;"><?php echo htmlspecialchars($lostUser['email']); ?></a></div>
                  </div>
                </div>
              </div>
              
              <div class="col-md-6">
                <div class="border rounded p-3 bg-white">
                  <h6 class="mb-2" style="color: #198754; font-weight: bold;">🟢 Found Item</h6>
                  <div class="mb-2" style="color: #212529;">
                    <strong style="color: #212529;">Item:</strong> <span style="color: #212529;"><?php echo htmlspecialchars($m['f_item']); ?></span>
                  </div>
                  <?php if ($m['f_desc']): ?>
                  <div class="mb-2" style="color: #495057; font-size: 0.9rem;">
                    <strong style="color: #212529;">Description:</strong> <span style="color: #495057;"><?php echo htmlspecialchars($m['f_desc']); ?></span>
                  </div>
                  <?php endif; ?>
                  <div class="mt-2 pt-2 border-top">
                    <div class="small" style="color: #212529;"><strong>Reported by:</strong></div>
                    <div style="color: #212529; margin: 5px 0;"><?php echo htmlspecialchars($foundUser['name']); ?> <span class="badge text-bg-secondary"><?php echo ucfirst($foundUser['role']); ?></span></div>
                    <div style="color: #0d6efd; font-size: 0.9rem; margin-top: 5px;">📧 <a href="mailto:<?php echo htmlspecialchars($foundUser['email']); ?>" style="color: #0d6efd; text-decoration: none;"><?php echo htmlspecialchars($foundUser['email']); ?></a></div>
                  </div>
                </div>
              </div>
            </div>
            
            <div class="bg-light rounded p-3 mb-3 border" style="background-color: #f8f9fa !important;">
              <h6 class="mb-3" style="color: #212529; font-weight: bold;">👤 Matched Person Details</h6>
              <div class="row">
                <div class="col-md-6 mb-2">
                  <div style="color: #212529; margin-bottom: 8px;"><strong style="color: #212529;">Name:</strong> <span style="color: #212529;"><?php echo htmlspecialchars($otherUser['name']); ?></span></div>
                  <div style="color: #212529;"><strong style="color: #212529;">Role:</strong> <span class="badge text-bg-info"><?php echo ucfirst($otherUser['role']); ?></span></div>
                </div>
                <div class="col-md-6 mb-2">
                  <div style="color: #212529;"><strong style="color: #212529;">Email:</strong> <a href="mailto:<?php echo htmlspecialchars($otherUser['email']); ?>?subject=Lost%20and%20Found%20Match" style="color: #0d6efd; text-decoration: underline; font-weight: 500;"><?php echo htmlspecialchars($otherUser['email']); ?></a></div>
                </div>
              </div>
            </div>
            
            <div class="d-flex gap-2 flex-wrap align-items-center">
              <a class="btn btn-primary" href="mailto:<?php echo htmlspecialchars($otherUser['email']); ?>?subject=Lost%20and%20Found%20Match%20-%20<?php echo urlencode($m['l_item']); ?>">
                📧 Contact Matched User
              </a>
              <form method="post" class="d-inline" onsubmit="return confirm('Update match status?');">
                <input type="hidden" name="match_id" value="<?php echo $m['match_id']; ?>">
                <select name="status" class="form-select form-select-sm d-inline-block" style="width: auto;" onchange="this.form.submit()">
                  <option value="Pending" <?php echo $m['status']=='Pending'?'selected':''; ?>>Pending</option>
                  <option value="Contacted" <?php echo $m['status']=='Contacted'?'selected':''; ?>>Contacted</option>
                  <option value="Resolved" <?php echo $m['status']=='Resolved'?'selected':''; ?>>Resolved</option>
                </select>
                <input type="hidden" name="update_status" value="1">
              </form>
              <a class="btn btn-outline-secondary" target="_blank" href="feedback.php">Give Feedback</a>
            </div>
          </div>
        </div>
      </div>
      <?php 
        endwhile; 
      else: ?>
      <div class="col-12">
        <div class="alert alert-info">No match notifications yet.</div>
      </div>
      <?php endif; ?>
    </div>
  </div>
  <script src="assets/js/app.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

