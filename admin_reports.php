<?php include 'config.php'; requireLogin(); 
if (currentUserRole() !== 'admin') redirectWith('index.html');

// Get comprehensive statistics
$stats = [
    'total_users' => mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM users"))[0] ?? 0,
    'students' => mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM users WHERE role='student'"))[0] ?? 0,
    'staff' => mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM users WHERE role='staff'"))[0] ?? 0,
    'total_lost' => mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM lost_items"))[0] ?? 0,
    'total_found' => mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM found_items"))[0] ?? 0,
    'total_matches' => mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM matches"))[0] ?? 0,
    'returned' => (mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM lost_items WHERE status='Returned'"))[0] ?? 0) +
                  (mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM found_items WHERE status='Returned'"))[0] ?? 0),
    'pending' => (mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM lost_items WHERE status='Pending'"))[0] ?? 0) +
                 (mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM found_items WHERE status='Pending'"))[0] ?? 0),
];

// Category breakdown
$category_stats = [];
$cat_q = mysqli_query($conn, "SELECT category, COUNT(*) as count FROM lost_items GROUP BY category");
while ($row = mysqli_fetch_assoc($cat_q)) {
    $category_stats[$row['category']] = ($category_stats[$row['category']] ?? 0) + $row['count'];
}
$cat_q = mysqli_query($conn, "SELECT category, COUNT(*) as count FROM found_items GROUP BY category");
while ($row = mysqli_fetch_assoc($cat_q)) {
    $category_stats[$row['category']] = ($category_stats[$row['category']] ?? 0) + $row['count'];
}

// Recent activity summary
$today_activities = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM activity_log WHERE DATE(created_at) = CURDATE()"))[0] ?? 0;
$week_activities = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM activity_log WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"))[0] ?? 0;
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Reports & Analytics - Admin Panel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
      <a class="navbar-brand" href="admin_dashboard.php">🏛️ Admin Panel</a>
      <div class="d-flex nav-gap">
        <a class="btn btn-outline-light btn-sm" href="admin_dashboard.php">Dashboard</a>
        <a class="btn btn-outline-light btn-sm" href="admin_items.php">Items</a>
        <a class="btn btn-outline-light btn-sm" href="admin_users.php">Users</a>
        <a class="btn btn-danger btn-sm" href="logout.php">Logout</a>
      </div>
    </div>
  </nav>

  <div class="container-fluid py-4">
    <h2 class="h4 mb-4">📊 Reports & Analytics</h2>
    
    <!-- Summary Statistics -->
    <div class="row g-3 mb-4">
      <div class="col-md-3">
        <div class="card text-center border-primary">
          <div class="card-body">
            <h5 class="text-primary">Total Users</h5>
            <h2><?php echo $stats['total_users']; ?></h2>
            <div class="small text-muted"><?php echo $stats['students']; ?> Students, <?php echo $stats['staff']; ?> Staff</div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card text-center border-danger">
          <div class="card-body">
            <h5 class="text-danger">Lost Items</h5>
            <h2><?php echo $stats['total_lost']; ?></h2>
            <div class="small text-muted"><?php echo mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM lost_items WHERE status='Pending'"))[0]; ?> Pending</div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card text-center border-success">
          <div class="card-body">
            <h5 class="text-success">Found Items</h5>
            <h2><?php echo $stats['total_found']; ?></h2>
            <div class="small text-muted"><?php echo mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM found_items WHERE status='Pending'"))[0]; ?> Pending</div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card text-center border-warning">
          <div class="card-body">
            <h5 class="text-warning">Total Matches</h5>
            <h2><?php echo $stats['total_matches']; ?></h2>
            <div class="small text-muted"><?php echo mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM matches WHERE status='Resolved'"))[0]; ?> Resolved</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Performance Metrics -->
    <div class="row g-3 mb-4">
      <div class="col-md-6">
        <div class="card">
          <div class="card-header bg-dark text-white">Performance Metrics</div>
          <div class="card-body">
            <table class="table table-sm">
              <tr>
                <td><strong>Success Rate:</strong></td>
                <td>
                  <?php 
                  $total = $stats['total_lost'] + $stats['total_found'];
                  $rate = $total > 0 ? round(($stats['returned'] / $total) * 100, 2) : 0;
                  ?>
                  <span class="badge text-bg-<?php echo $rate >= 50 ? 'success' : ($rate >= 30 ? 'warning' : 'danger'); ?>">
                    <?php echo $rate; ?>%
                  </span>
                </td>
              </tr>
              <tr>
                <td><strong>Match Rate:</strong></td>
                <td>
                  <?php 
                  $match_rate = $total > 0 ? round(($stats['total_matches'] / ($total / 2)) * 100, 2) : 0;
                  $match_rate = $match_rate > 100 ? 100 : $match_rate;
                  ?>
                  <span class="badge text-bg-<?php echo $match_rate >= 50 ? 'success' : ($match_rate >= 30 ? 'warning' : 'danger'); ?>">
                    <?php echo $match_rate; ?>%
                  </span>
                </td>
              </tr>
              <tr>
                <td><strong>Pending Items:</strong></td>
                <td><span class="badge text-bg-warning"><?php echo $stats['pending']; ?></span></td>
              </tr>
              <tr>
                <td><strong>Returned Items:</strong></td>
                <td><span class="badge text-bg-success"><?php echo $stats['returned']; ?></span></td>
              </tr>
            </table>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card">
          <div class="card-header bg-dark text-white">Activity Summary</div>
          <div class="card-body">
            <table class="table table-sm">
              <tr>
                <td><strong>Today's Activities:</strong></td>
                <td><span class="badge text-bg-info"><?php echo $today_activities; ?></span></td>
              </tr>
              <tr>
                <td><strong>This Week's Activities:</strong></td>
                <td><span class="badge text-bg-primary"><?php echo $week_activities; ?></span></td>
              </tr>
              <tr>
                <td><strong>Total Activities Logged:</strong></td>
                <td><span class="badge text-bg-dark"><?php echo mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM activity_log"))[0]; ?></span></td>
              </tr>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Category Breakdown -->
    <div class="card mb-4">
      <div class="card-header bg-dark text-white">Category Distribution</div>
      <div class="card-body">
        <div class="row">
          <?php foreach($category_stats as $cat => $count): ?>
          <div class="col-md-3 mb-3">
            <div class="card">
              <div class="card-body text-center">
                <h6><?php echo htmlspecialchars($cat); ?></h6>
                <h4><?php echo $count; ?></h4>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- Export Options -->
    <div class="card">
      <div class="card-header bg-dark text-white">Export Reports</div>
      <div class="card-body">
        <p class="text-muted">Export comprehensive reports for college management:</p>
        <div class="row g-2">
          <div class="col-md-3">
            <a href="admin_export.php?type=items" class="btn btn-outline-primary w-100">Export All Items</a>
          </div>
          <div class="col-md-3">
            <a href="admin_export.php?type=users" class="btn btn-outline-primary w-100">Export All Users</a>
          </div>
          <div class="col-md-3">
            <a href="admin_export.php?type=matches" class="btn btn-outline-primary w-100">Export All Matches</a>
          </div>
          <div class="col-md-3">
            <a href="admin_export.php?type=activity" class="btn btn-outline-primary w-100">Export Activity Log</a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="assets/js/app.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

