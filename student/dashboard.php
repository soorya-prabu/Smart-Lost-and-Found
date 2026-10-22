<?php
require_once __DIR__ . '/../config/config.php';
requireRole('student', 'auth/login_student.php');

$uid = currentUserId();

// Mark item returned
if (isset($_GET['return']) && ctype_digit($_GET['return'])) {
    $lid = (int)$_GET['return'];
    dbExecute($conn, "UPDATE lost_items SET status='Returned' WHERE lost_id = ? AND user_id = ?", [$lid, $uid]);
    // Also resolve matches
    dbExecute($conn, "UPDATE matches SET status='Resolved' WHERE lost_id = ?", [$lid]);
    // Log activity
    logActivity($conn, "Marked Item as Returned", "lost", $lid);
    redirectWith('student/dashboard.php', ['msg' => 'Item marked as Returned!']);
}

$lost_q = dbQuery($conn, "SELECT * FROM lost_items WHERE user_id = ? ORDER BY created_at DESC", [$uid]);
$found_q = dbQuery($conn, "SELECT * FROM found_items WHERE user_id = ? ORDER BY created_at DESC", [$uid]);

// Notifications: matches relevant to this user
$notif_q = dbQuery($conn, "SELECT m.*, l.item_name AS l_item, f.item_name AS f_item
  FROM matches m
  JOIN lost_items l ON m.lost_id=l.lost_id
  JOIN found_items f ON m.found_id=f.found_id
  WHERE (l.user_id = ? OR f.user_id = ?) AND m.status <> 'Resolved'
  ORDER BY m.created_at DESC", [$uid, $uid]);

$pageTitle = 'Student Dashboard - Smart Lost & Found';
$extraCss = ['assets/css/student.css'];
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

    <h2 class="h5 mb-3">Welcome, <?php echo cleanHtml(currentUserName()); ?> 🎓</h2>

    <div class="row g-4">
      <div class="col-lg-6">
        <div class="card shadow-sm h-100">
          <div class="card-header bg-primary text-white fw-bold">Your Lost Items</div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                  <tr><th>Item</th><th>Date</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                <?php 
                if ($lost_q && mysqli_num_rows($lost_q) > 0):
                  while($r = mysqli_fetch_assoc($lost_q)): ?>
                  <tr>
                    <td>
                      <div class="d-flex align-items-center gap-2">
                        <?php if ($r['image']): ?>
                        <img src="<?php echo asset($r['image']); ?>" class="rounded" style="width: 50px; height: 50px; object-fit: cover; cursor: pointer;" onclick="previewImage(this.src)" alt="<?php echo cleanHtml($r['item_name']); ?>">
                        <?php endif; ?>
                        <div>
                          <strong><?php echo cleanHtml($r['item_name']); ?></strong>
                          <div class="text-muted small"><?php echo cleanHtml($r['place_lost']); ?></div>
                        </div>
                      </div>
                    </td>
                    <td><?php echo cleanHtml($r['date_lost']); ?></td>
                    <td><span class="badge text-bg-<?php echo $r['status']==='Returned'?'success':($r['status']==='Matched'?'warning':'secondary'); ?> status-badge"><?php echo $r['status']; ?></span></td>
                    <td class="text-end pe-3">
                      <?php if ($r['status']!=='Returned'): ?>
                        <a class="btn btn-sm btn-outline-success" onclick="return confirmReturn()" href="?return=<?php echo $r['lost_id']; ?>">Mark Returned</a>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php 
                  endwhile; 
                else: ?>
                  <tr><td colspan="4" class="text-center text-muted py-4">No lost items reported yet.</td></tr>
                <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-6">
        <div class="card shadow-sm h-100">
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
                      <div class="d-flex align-items-center gap-2">
                        <?php if ($f['image']): ?>
                        <img src="<?php echo asset($f['image']); ?>" class="rounded" style="width: 50px; height: 50px; object-fit: cover; cursor: pointer;" onclick="previewImage(this.src)" alt="<?php echo cleanHtml($f['item_name']); ?>">
                        <?php endif; ?>
                        <div>
                          <strong><?php echo cleanHtml($f['item_name']); ?></strong>
                          <div class="text-muted small"><?php echo cleanHtml($f['place_found']); ?></div>
                        </div>
                      </div>
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
                  <tr><td colspan="4" class="text-center text-muted py-4">No pending match notifications.</td></tr>
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
