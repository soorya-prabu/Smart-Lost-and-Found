<?php
require_once __DIR__ . '/../config/config.php';
requireRole('staff', 'auth/login_staff.php');

$uid = currentUserId();

$lost_q = dbQuery($conn, "SELECT * FROM lost_items WHERE user_id = ? ORDER BY created_at DESC", [$uid]);
$found_q = dbQuery($conn, "SELECT * FROM found_items WHERE user_id = ? ORDER BY created_at DESC", [$uid]);

// Notifications: matches relevant to this user
$notif_q = dbQuery($conn, "SELECT m.*, l.item_name AS l_item, f.item_name AS f_item
  FROM matches m
  JOIN lost_items l ON m.lost_id=l.lost_id
  JOIN found_items f ON m.found_id=f.found_id
  WHERE (l.user_id = ? OR f.user_id = ?) AND m.status <> 'Resolved'
  ORDER BY m.created_at DESC", [$uid, $uid]);

$tot_lost = dbCount($conn, "SELECT COUNT(*) FROM lost_items");
$tot_found = dbCount($conn, "SELECT COUNT(*) FROM found_items");
$tot_returned = dbCount($conn, "SELECT COUNT(*) FROM lost_items WHERE status = 'Returned'")
              + dbCount($conn, "SELECT COUNT(*) FROM found_items WHERE status = 'Returned'");
$tot_matches = dbCount($conn, "SELECT COUNT(*) FROM matches");
$tot_pending_lost = dbCount($conn, "SELECT COUNT(*) FROM lost_items WHERE status = 'Pending'");
$tot_pending_found = dbCount($conn, "SELECT COUNT(*) FROM found_items WHERE status = 'Pending'");
$tot_matched = dbCount($conn, "SELECT COUNT(*) FROM matches WHERE status = 'Pending'");

$recent = dbQuery($conn, "SELECT 'lost' AS type, item_name, created_at FROM lost_items ORDER BY created_at DESC LIMIT 5");
$recent2 = dbQuery($conn, "SELECT 'found' AS type, item_name, created_at FROM found_items ORDER BY created_at DESC LIMIT 5");

$pageTitle = 'Staff Dashboard - Smart Lost & Found';
$extraCss = ['assets/css/staff.css'];
$activeNav = 'dashboard';
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

    <h2 class="h5 mb-3">Welcome, <?php echo cleanHtml(currentUserName()); ?> 👨‍🏫</h2>
    
    <!-- Summary Cards -->
    <div class="row g-3">
      <div class="col-md-3">
        <div class="card text-center shadow-sm">
          <div class="card-body">
            <div class="text-muted">Total Lost</div>
            <div class="h4 mb-0 text-danger"><?php echo $tot_lost; ?></div>
            <div class="small text-muted"><?php echo $tot_pending_lost; ?> Pending</div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card text-center shadow-sm">
          <div class="card-body">
            <div class="text-muted">Total Found</div>
            <div class="h4 mb-0 text-success"><?php echo $tot_found; ?></div>
            <div class="small text-muted"><?php echo $tot_pending_found; ?> Pending</div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card text-center shadow-sm">
          <div class="card-body">
            <div class="text-muted">Total Returned</div>
            <div class="h4 mb-0 text-primary"><?php echo $tot_returned; ?></div>
            <div class="small text-success">✓ Successfully Returned</div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card text-center shadow-sm">
          <div class="card-body">
            <div class="text-muted">Total Matches</div>
            <div class="h4 mb-0 text-warning"><?php echo $tot_matches; ?></div>
            <div class="small text-warning"><?php echo $tot_matched; ?> Pending</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Lost and Found -->
    <div class="row g-4 mt-1">
      <div class="col-md-6">
        <div class="card shadow-sm h-100">
          <div class="card-header bg-danger text-white fw-bold">Recent Campus Lost Items</div>
          <div class="card-body p-0">
            <ul class="list-group list-group-flush">
              <?php 
              if ($recent && mysqli_num_rows($recent) > 0):
                while($r = mysqli_fetch_assoc($recent)): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                  <span>🔴 <strong><?php echo cleanHtml($r['item_name']); ?></strong></span>
                  <span class="text-muted small"><?php echo cleanHtml($r['created_at']); ?></span>
                </li>
              <?php 
                endwhile; 
              else: ?>
                <li class="list-group-item text-center text-muted py-3">No recent lost items.</li>
              <?php endif; ?>
            </ul>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card shadow-sm h-100">
          <div class="card-header bg-success text-white fw-bold">Recent Campus Found Items</div>
          <div class="card-body p-0">
            <ul class="list-group list-group-flush">
              <?php 
              if ($recent2 && mysqli_num_rows($recent2) > 0):
                while($r2 = mysqli_fetch_assoc($recent2)): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                  <span>🟢 <strong><?php echo cleanHtml($r2['item_name']); ?></strong></span>
                  <span class="text-muted small"><?php echo cleanHtml($r2['created_at']); ?></span>
                </li>
              <?php 
                endwhile; 
              else: ?>
                <li class="list-group-item text-center text-muted py-3">No recent found items.</li>
              <?php endif; ?>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <!-- Staff Personal Items & Notifications -->
    <div class="row g-4 mt-2">
      <div class="col-md-6">
        <div class="card shadow-sm">
          <div class="card-header bg-primary text-white fw-bold">Your Lost Items</div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                  <tr><th>Item</th><th>Date</th><th>Status</th></tr>
                </thead>
                <tbody>
                <?php 
                if ($lost_q && mysqli_num_rows($lost_q) > 0):
                  while($r = mysqli_fetch_assoc($lost_q)): ?>
                  <tr>
                    <td>
                      <strong><?php echo cleanHtml($r['item_name']); ?></strong>
                      <div class="text-muted small"><?php echo cleanHtml($r['place_lost']); ?></div>
                    </td>
                    <td><?php echo cleanHtml($r['date_lost']); ?></td>
                    <td><span class="badge text-bg-<?php echo $r['status']==='Returned'?'success':($r['status']==='Matched'?'warning':'secondary'); ?> status-badge"><?php echo $r['status']; ?></span></td>
                  </tr>
                <?php 
                  endwhile; 
                else: ?>
                  <tr><td colspan="3" class="text-center text-muted py-4">No lost items reported yet.</td></tr>
                <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-6">
        <div class="card shadow-sm">
          <div class="card-header bg-success text-white fw-bold">Your Found Items</div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                  <tr><th>Item</th><th>Date</th><th>Status</th></tr>
                </thead>
                <tbody>
                <?php 
                if ($found_q && mysqli_num_rows($found_q) > 0):
                  while($f = mysqli_fetch_assoc($found_q)): ?>
                  <tr>
                    <td>
                      <strong><?php echo cleanHtml($f['item_name']); ?></strong>
                      <div class="text-muted small"><?php echo cleanHtml($f['place_found']); ?></div>
                    </td>
                    <td><?php echo cleanHtml($f['date_found']); ?></td>
                    <td><span class="badge text-bg-<?php echo $f['status']==='Returned'?'success':($f['status']==='Matched'?'warning':'secondary'); ?> status-badge"><?php echo $f['status']; ?></span></td>
                  </tr>
                <?php 
                  endwhile; 
                else: ?>
                  <tr><td colspan="3" class="text-center text-muted py-4">No found items reported yet.</td></tr>
                <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <div class="col-12">
        <div class="card shadow-sm">
          <div class="card-header bg-warning text-dark fw-bold">Pending Notifications</div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                  <tr><th>Lost Item</th><th>Found Item</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                <?php 
                if ($notif_q && mysqli_num_rows($notif_q) > 0):
                  while($m = mysqli_fetch_assoc($notif_q)): ?>
                  <tr>
                    <td><strong><?php echo cleanHtml($m['l_item']); ?></strong></td>
                    <td><strong><?php echo cleanHtml($m['f_item']); ?></strong></td>
                    <td><span class="badge text-bg-info"><?php echo cleanHtml($m['status']); ?></span></td>
                    <td class="text-end pe-3">
                      <a href="<?php echo url('items/match_notification.php'); ?>" class="btn btn-sm btn-outline-primary">View Match</a>
                    </td>
                  </tr>
                <?php 
                  endwhile; 
                else: ?>
                  <tr><td colspan="4" class="text-center text-muted py-4">No notifications yet.</td></tr>
                <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <?php include __DIR__ . '/../components/footer.php'; ?>
