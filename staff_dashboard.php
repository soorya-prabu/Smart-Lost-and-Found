<?php include 'config.php'; requireLogin(); if (currentUserRole() !== 'staff') redirectWith('index.html');

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

$tot_lost = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM lost_items"))[0] ?? 0;
$tot_found = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM found_items"))[0] ?? 0;
$tot_returned = (mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM lost_items WHERE status='Returned'"))[0] ?? 0)
               + (mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM found_items WHERE status='Returned'"))[0] ?? 0);
$tot_matches = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM matches"))[0] ?? 0;
$tot_pending_lost = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM lost_items WHERE status='Pending'"))[0] ?? 0;
$tot_pending_found = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM found_items WHERE status='Pending'"))[0] ?? 0;
$tot_matched = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM matches WHERE status='Pending'"))[0] ?? 0;

$recent = mysqli_query($conn, "SELECT 'lost' AS type, item_name, created_at FROM lost_items ORDER BY created_at DESC LIMIT 5");
$recent2 = mysqli_query($conn, "SELECT 'found' AS type, item_name, created_at FROM found_items ORDER BY created_at DESC LIMIT 5");
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Staff Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/staff.css">
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
    <div class="row g-3">
      <div class="col-md-3"><div class="card text-center"><div class="card-body"><div class="text-muted">Total Lost</div><div class="h4 mb-0"><?php echo $tot_lost; ?></div><div class="small text-muted"><?php echo $tot_pending_lost; ?> Pending</div></div></div></div>
      <div class="col-md-3"><div class="card text-center"><div class="card-body"><div class="text-muted">Total Found</div><div class="h4 mb-0"><?php echo $tot_found; ?></div><div class="small text-muted"><?php echo $tot_pending_found; ?> Pending</div></div></div></div>
      <div class="col-md-3"><div class="card text-center"><div class="card-body"><div class="text-muted">Total Returned</div><div class="h4 mb-0"><?php echo $tot_returned; ?></div><div class="small text-success">✓ Successfully Returned</div></div></div></div>
      <div class="col-md-3"><div class="card text-center"><div class="card-body"><div class="text-muted">Total Matches</div><div class="h4 mb-0"><?php echo $tot_matches; ?></div><div class="small text-warning"><?php echo $tot_matched; ?> Pending</div></div></div></div>
    </div>
    <div class="row g-4 mt-1">
      <div class="col-md-6"><div class="card"><div class="card-header">Recent Lost</div><div class="card-body">
        <ul class="list-group list-group-flush">
          <?php 
          if ($recent && mysqli_num_rows($recent) > 0):
            while($r = mysqli_fetch_assoc($recent)): ?>
            <li class="list-group-item d-flex justify-content-between"><span>🟥 <?php echo htmlspecialchars($r['item_name']); ?></span><span class="text-muted small"><?php echo htmlspecialchars($r['created_at']); ?></span></li>
          <?php 
            endwhile; 
          else: ?>
            <li class="list-group-item text-center text-muted">No recent lost items.</li>
          <?php endif; ?>
        </ul></div></div></div>
      <div class="col-md-6"><div class="card"><div class="card-header">Recent Found</div><div class="card-body">
        <ul class="list-group list-group-flush">
          <?php 
          if ($recent2 && mysqli_num_rows($recent2) > 0):
            while($r2 = mysqli_fetch_assoc($recent2)): ?>
            <li class="list-group-item d-flex justify-content-between"><span>🟩 <?php echo htmlspecialchars($r2['item_name']); ?></span><span class="text-muted small"><?php echo htmlspecialchars($r2['created_at']); ?></span></li>
          <?php 
            endwhile; 
          else: ?>
            <li class="list-group-item text-center text-muted">No recent found items.</li>
          <?php endif; ?>
        </ul></div></div></div>
    </div>
    <div class="row g-4 mt-3">
      <div class="col-12">
        <div class="card">
          <div class="card-header">Your Lost Items</div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-sm table-small align-middle">
                <thead><tr><th>Item</th><th>Date</th><th>Status</th></tr></thead>
                <tbody>
                <?php 
                if ($lost_q && mysqli_num_rows($lost_q) > 0):
                  while($r = mysqli_fetch_assoc($lost_q)): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($r['item_name']); ?><div class="text-muted small"><?php echo htmlspecialchars($r['place_lost']); ?></div></td>
                    <td><?php echo htmlspecialchars($r['date_lost']); ?></td>
                    <td><span class="badge text-bg-<?php echo $r['status']==='Returned'?'success':($r['status']==='Matched'?'warning':'secondary'); ?> status-badge"><?php echo $r['status']; ?></span></td>
                  </tr>
                <?php 
                  endwhile; 
                else: ?>
                  <tr><td colspan="3" class="text-center text-muted">No lost items reported yet.</td></tr>
                <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
      <div class="col-12">
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
                    <td><?php echo htmlspecialchars($f['item_name']); ?><div class="text-muted small"><?php echo htmlspecialchars($f['place_found']); ?></div></td>
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

