<?php
require_once __DIR__ . '/../config/config.php';
requireLogin();

$uid = currentUserId();
$role = currentUserRole();

// Update match status
if (isset($_POST['update_status']) && isset($_POST['match_id'])) {
    $match_id = (int)$_POST['match_id'];
    $new_status = trim($_POST['status'] ?? '');
    if (in_array($new_status, ['Pending', 'Contacted', 'Resolved'])) {
        dbExecute($conn, "UPDATE matches SET status = ? WHERE match_id = ?", [$new_status, $match_id]);
        logActivity($conn, "Updated Match Status", "match", $match_id, "User updated status to $new_status");
        redirectWith('items/match_notification.php', ['msg' => 'Match status updated successfully']);
    }
}

$q = dbQuery($conn, "SELECT m.*, 
  l.item_name AS l_item, l.description AS l_desc, l.image AS l_img, l.user_id AS l_uid, l.place_lost AS l_place, l.date_lost AS l_date,
  f.item_name AS f_item, f.description AS f_desc, f.image AS f_img, f.user_id AS f_uid, f.place_found AS f_place, f.date_found AS f_date
  FROM matches m
  JOIN lost_items l ON m.lost_id=l.lost_id
  JOIN found_items f ON m.found_id=f.found_id
  WHERE l.user_id = ? OR f.user_id = ?
  ORDER BY m.created_at DESC", [$uid, $uid]);

function getUserDetails($conn, $id){
    $r = dbFetchOne($conn, "SELECT name, email, role FROM users WHERE user_id = ? LIMIT 1", [(int)$id]);
    return $r ? $r : ['name' => 'Unknown User', 'email' => '', 'role' => ''];
}

$pageTitle = 'Match Notifications - Smart Lost & Found';
$extraCss = ['assets/css/forms.css'];
?>
<!doctype html>
<html lang="en">
<head>
  <?php include __DIR__ . '/../components/head.php'; ?>
</head>
<body>
  <?php include __DIR__ . '/../components/navbar.php'; ?>

  <div class="container py-4">
    <?php if ($msg = flashMessage()): ?>
      <div class="alert alert-success auto-fade py-2"><?php echo $msg; ?></div>
    <?php endif; ?>

    <h2 class="h4 mb-3">🔔 Potential Match Notifications</h2>
    <p class="text-muted small mb-4">Our automated matching engine pairs lost and found reports based on item names, descriptions, categories, and locations.</p>

    <div class="row g-4">
      <?php 
      if ($q && mysqli_num_rows($q) > 0):
        while($m = mysqli_fetch_assoc($q)): 
          $isLostOwner = ($m['l_uid'] == $uid);
          $otherUid = $isLostOwner ? $m['f_uid'] : $m['l_uid'];
          $otherUser = getUserDetails($conn, $otherUid);
          
          $lostUser = getUserDetails($conn, $m['l_uid']);
          $foundUser = getUserDetails($conn, $m['f_uid']);
      ?>
      <div class="col-12">
        <div class="card shadow-sm border-<?php echo $m['status']=='Resolved'?'success':($m['status']=='Contacted'?'warning':'primary'); ?>">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
              <div>
                <h5 class="mb-1 fw-bold text-dark">
                  Potential Match #<?php echo $m['match_id']; ?> 
                  <span class="badge text-bg-<?php echo $m['status']=='Resolved'?'success':($m['status']=='Contacted'?'warning':'info'); ?>">
                    <?php echo cleanHtml($m['status']); ?>
                  </span>
                </h5>
                <div class="text-muted small">Generated on: <?php echo date('M d, Y h:i A', strtotime($m['created_at'])); ?></div>
              </div>
              <span class="badge text-bg-success">Automated Match</span>
            </div>
            
            <div class="row g-3 mb-3">
              <!-- Lost Item Card -->
              <div class="col-md-6">
                <div class="border rounded p-3 bg-white h-100 shadow-sm">
                  <h6 class="mb-2 text-danger fw-bold">🔴 Lost Item Details</h6>
                  <div class="d-flex gap-3 align-items-start mb-2">
                    <?php if ($m['l_img']): ?>
                    <img src="<?php echo asset($m['l_img']); ?>" class="rounded" style="width: 70px; height: 70px; object-fit: cover; cursor: pointer;" onclick="previewImage(this.src)" alt="<?php echo cleanHtml($m['l_item']); ?>">
                    <?php endif; ?>
                    <div>
                      <div class="fw-bold fs-6"><?php echo cleanHtml($m['l_item']); ?></div>
                      <div class="small text-muted"><strong>Place:</strong> <?php echo cleanHtml($m['l_place']); ?></div>
                      <div class="small text-muted"><strong>Date:</strong> <?php echo cleanHtml($m['l_date']); ?></div>
                    </div>
                  </div>
                  <?php if ($m['l_desc']): ?>
                  <div class="small text-secondary mb-2 bg-light p-2 rounded">
                    <?php echo cleanHtml($m['l_desc']); ?>
                  </div>
                  <?php endif; ?>
                  <div class="pt-2 border-top small">
                    <strong>Reported by:</strong> <?php echo cleanHtml($lostUser['name']); ?> 
                    <span class="badge text-bg-secondary"><?php echo ucfirst($lostUser['role']); ?></span>
                    <div class="mt-1">📧 <a href="mailto:<?php echo cleanHtml($lostUser['email']); ?>"><?php echo cleanHtml($lostUser['email']); ?></a></div>
                  </div>
                </div>
              </div>
              
              <!-- Found Item Card -->
              <div class="col-md-6">
                <div class="border rounded p-3 bg-white h-100 shadow-sm">
                  <h6 class="mb-2 text-success fw-bold">🟢 Found Item Details</h6>
                  <div class="d-flex gap-3 align-items-start mb-2">
                    <?php if ($m['f_img']): ?>
                    <img src="<?php echo asset($m['f_img']); ?>" class="rounded" style="width: 70px; height: 70px; object-fit: cover; cursor: pointer;" onclick="previewImage(this.src)" alt="<?php echo cleanHtml($m['f_item']); ?>">
                    <?php endif; ?>
                    <div>
                      <div class="fw-bold fs-6"><?php echo cleanHtml($m['f_item']); ?></div>
                      <div class="small text-muted"><strong>Place:</strong> <?php echo cleanHtml($m['f_place']); ?></div>
                      <div class="small text-muted"><strong>Date:</strong> <?php echo cleanHtml($m['f_date']); ?></div>
                    </div>
                  </div>
                  <?php if ($m['f_desc']): ?>
                  <div class="small text-secondary mb-2 bg-light p-2 rounded">
                    <?php echo cleanHtml($m['f_desc']); ?>
                  </div>
                  <?php endif; ?>
                  <div class="pt-2 border-top small">
                    <strong>Reported by:</strong> <?php echo cleanHtml($foundUser['name']); ?> 
                    <span class="badge text-bg-secondary"><?php echo ucfirst($foundUser['role']); ?></span>
                    <div class="mt-1">📧 <a href="mailto:<?php echo cleanHtml($foundUser['email']); ?>"><?php echo cleanHtml($foundUser['email']); ?></a></div>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Matched Person Details -->
            <div class="bg-light rounded p-3 mb-3 border">
              <h6 class="mb-2 fw-bold text-dark">👤 Matched Campus Member: <?php echo cleanHtml($otherUser['name']); ?></h6>
              <div class="row small">
                <div class="col-md-6 mb-1">
                  <strong>Role:</strong> <span class="badge text-bg-info"><?php echo ucfirst($otherUser['role']); ?></span>
                </div>
                <div class="col-md-6 mb-1">
                  <strong>Email:</strong> <a href="mailto:<?php echo cleanHtml($otherUser['email']); ?>" class="fw-bold"><?php echo cleanHtml($otherUser['email']); ?></a>
                </div>
              </div>
            </div>
            
            <!-- Action buttons -->
            <div class="d-flex gap-2 flex-wrap align-items-center">
              <a class="btn btn-primary btn-sm" href="mailto:<?php echo cleanHtml($otherUser['email']); ?>?subject=Lost%20and%20Found%20Match%20-%20<?php echo urlencode($m['l_item']); ?>">
                📧 Contact Matched Member
              </a>
              <form method="post" class="d-inline" action="match_notification.php">
                <input type="hidden" name="match_id" value="<?php echo $m['match_id']; ?>">
                <div class="input-group input-group-sm">
                  <label class="input-group-text bg-white">Status</label>
                  <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="Pending" <?php echo $m['status']=='Pending'?'selected':''; ?>>Pending</option>
                    <option value="Contacted" <?php echo $m['status']=='Contacted'?'selected':''; ?>>Contacted</option>
                    <option value="Resolved" <?php echo $m['status']=='Resolved'?'selected':''; ?>>Resolved</option>
                  </select>
                </div>
                <input type="hidden" name="update_status" value="1">
              </form>
              <a class="btn btn-outline-secondary btn-sm" href="<?php echo url('items/feedback.php'); ?>">Give Feedback</a>
            </div>
          </div>
        </div>
      </div>
      <?php 
        endwhile; 
      else: ?>
      <div class="col-12">
        <div class="alert alert-info py-4 text-center">
          <h5>No Match Notifications Yet</h5>
          <p class="mb-0">When someone reports an item that matches one of your lost or found submissions, you'll receive a notification here!</p>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <?php include __DIR__ . '/../components/footer.php'; ?>
