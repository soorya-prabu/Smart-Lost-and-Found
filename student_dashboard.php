<?php include 'config.php'; requireLogin(); if (currentUserRole() !== 'student') redirectWith('index.html');

// Mark item returned
if (isset($_GET['return']) && ctype_digit($_GET['return'])) {
    $lid = (int)$_GET['return'];
    mysqli_query($conn, "UPDATE lost_items SET status='Returned' WHERE lost_id=$lid AND user_id=".currentUserId());
    // also soften matches
    mysqli_query($conn, "UPDATE matches SET status='Resolved' WHERE lost_id=$lid");
    // Log activity
    logActivity($conn, "Marked Item as Returned", "lost", $lid);
    redirectWith('student_dashboard.php', ['msg' => 'Marked as Returned']);
}

$uid = currentUserId();
$lost_q = mysqli_query($conn, "SELECT * FROM lost_items WHERE user_id=$uid ORDER BY created_at DESC");
$found_q = mysqli_query($conn, "SELECT * FROM found_items WHERE user_id=$uid ORDER BY created_at DESC");

// Notifications: matches relevant to this user
$notif_q = mysqli_query($conn, "SELECT m.*, l.item_name AS l_item, f.item_name AS f_item
  FROM matches m
  JOIN lost_items l ON m.lost_id=l.lost_id
  JOIN found_items f ON m.found_id=f.found_id
  WHERE l.user_id=$uid OR f.user_id=$uid
  ORDER BY m.created_at DESC");
$notif_count = mysqli_num_rows($notif_q);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Student Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/student.css">
</head>
<body>
  <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom">
    <div class="container-fluid">
      <a class="navbar-brand" href="index.html">Smart Lost & Found</a>
      <div class="d-flex nav-gap">
        <a class="btn btn-outline-primary" href="report_lost.php">Report Lost</a>
        <a class="btn btn-outline-success" href="report_found.php">Report Found</a>
        <a class="btn btn-outline-info" href="search_items.php">Search</a>
        <a class="btn btn-outline-secondary position-relative" href="match_notification.php">
          Notifications
          <?php if ($notif_count > 0): ?>
          <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"><?php echo $notif_count; ?></span>
          <?php endif; ?>
        </a>
        <a class="btn btn-outline-dark" href="feedback.php">Feedback</a>
        <a class="btn btn-danger" href="logout.php">Logout</a>
      </div>
    </div>
  </nav>
  <div class="container py-4">
    <?php if (!empty($_GET['msg'])): ?><div class="alert alert-success auto-fade py-2"><?php echo htmlspecialchars($_GET['msg']); ?></div><?php endif; ?>
    <h2 class="h5 mb-3">Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?></h2>

    <div class="row g-4">
      <div class="col-lg-6">
        <div class="card">
          <div class="card-header">Your Lost Items</div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-sm table-small align-middle">
                <thead><tr><th>Item</th><th>Date</th><th>Status</th><th></th></tr></thead>
                <tbody>
                <?php 
                if ($lost_q && mysqli_num_rows($lost_q) > 0):
                  while($r = mysqli_fetch_assoc($lost_q)): ?>
                  <tr>
                    <td>
                      <div class="d-flex align-items-center gap-2">
                        <?php if ($r['image']): ?>
                        <img src="<?php echo htmlspecialchars($r['image']); ?>" class="rounded" style="width: 50px; height: 50px; object-fit: cover; cursor: pointer;" onclick="previewImage(this.src)" alt="<?php echo htmlspecialchars($r['item_name']); ?>">
                        <?php endif; ?>
                        <div>
                          <?php echo htmlspecialchars($r['item_name']); ?>
                          <div class="text-muted small"><?php echo htmlspecialchars($r['place_lost']); ?></div>
                        </div>
                      </div>
                    </td>
                    <td><?php echo htmlspecialchars($r['date_lost']); ?></td>
                    <td><span class="badge text-bg-<?php echo $r['status']==='Returned'?'success':($r['status']==='Matched'?'warning':'secondary'); ?> status-badge"><?php echo $r['status']; ?></span></td>
                    <td class="text-end">
                      <?php if ($r['status']!=='Returned'): ?>
                        <a class="btn btn-sm btn-outline-success" onclick="return confirmReturn()" href="?return=<?php echo $r['lost_id']; ?>">Mark Returned</a>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php 
                  endwhile; 
                else: ?>
                  <tr><td colspan="4" class="text-center text-muted">No lost items reported yet.</td></tr>
                <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="card">
          <div class="card-header">Your Found Items</div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-sm table-small align-middle">
                <thead><tr><th>Item</th><th>Date</th><th>Status</th></tr></thead>
                <tbody>
                <?php 
                if ($found_q && mysqli_num_rows($found_q) > 0):
                  while($f = mysqli_fetch_assoc($found_q)): ?>
                  <tr>
                    <td>
                      <div class="d-flex align-items-center gap-2">
                        <?php if ($f['image']): ?>
                        <img src="<?php echo htmlspecialchars($f['image']); ?>" class="rounded" style="width: 50px; height: 50px; object-fit: cover; cursor: pointer;" onclick="previewImage(this.src)" alt="<?php echo htmlspecialchars($f['item_name']); ?>">
                        <?php endif; ?>
                        <div>
                          <?php echo htmlspecialchars($f['item_name']); ?>
                          <div class="text-muted small"><?php echo htmlspecialchars($f['place_found']); ?></div>
                        </div>
                      </div>
                    </td>
                    <td><?php echo htmlspecialchars($f['date_found']); ?></td>
                    <td><span class="badge text-bg-<?php echo $f['status']==='Returned'?'success':($f['status']==='Matched'?'warning':'secondary'); ?> status-badge"><?php echo $f['status']; ?></span></td>
                  </tr>
                <?php 
                  endwhile; 
                else: ?>
                  <tr><td colspan="3" class="text-center text-muted">No found items reported yet.</td></tr>
                <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
      <div class="col-12">
        <div class="card">
          <div class="card-header">Notifications</div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-sm table-small">
                <thead><tr><th>Lost Item</th><th>Found Item</th><th>Status</th><th></th></tr></thead>
                <tbody>
                <?php 
                if ($notif_q && mysqli_num_rows($notif_q) > 0):
                  while($m = mysqli_fetch_assoc($notif_q)): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($m['l_item']); ?></td>
                    <td><?php echo htmlspecialchars($m['f_item']); ?></td>
                    <td><?php echo htmlspecialchars($m['status']); ?></td>
                    <td class="text-end"><a href="match_notification.php" class="btn btn-sm btn-outline-primary">View</a></td>
                  </tr>
                <?php 
                  endwhile; 
                else: ?>
                  <tr><td colspan="4" class="text-center text-muted">No notifications yet.</td></tr>
                <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <script src="assets/js/app.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

