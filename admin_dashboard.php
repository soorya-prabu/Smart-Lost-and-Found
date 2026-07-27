<?php include 'config.php'; requireLogin(); 
if (currentUserRole() !== 'admin') redirectWith('index.html');

$uid = currentUserId();

// Statistics
$tot_users = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM users"))[0] ?? 0;
$tot_students = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM users WHERE role='student'"))[0] ?? 0;
$tot_staff = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM users WHERE role='staff'"))[0] ?? 0;
$tot_lost = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM lost_items"))[0] ?? 0;
$tot_found = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM found_items"))[0] ?? 0;
$tot_matches = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM matches"))[0] ?? 0;
$tot_returned = (mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM lost_items WHERE status='Returned'"))[0] ?? 0)
               + (mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM found_items WHERE status='Returned'"))[0] ?? 0);
$tot_pending = (mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM lost_items WHERE status='Pending'"))[0] ?? 0)
              + (mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM found_items WHERE status='Pending'"))[0] ?? 0);

// Recent activities
$recent_activities = mysqli_query($conn, "SELECT a.*, u.name AS user_name 
    FROM activity_log a 
    LEFT JOIN users u ON a.user_id = u.user_id 
    ORDER BY a.created_at DESC LIMIT 10");

// Recent items
$recent_lost = mysqli_query($conn, "SELECT l.*, u.name AS reporter_name 
    FROM lost_items l 
    JOIN users u ON l.user_id = u.user_id 
    ORDER BY l.created_at DESC LIMIT 5");
$recent_found = mysqli_query($conn, "SELECT f.*, u.name AS reporter_name 
    FROM found_items f 
    JOIN users u ON f.user_id = u.user_id 
    ORDER BY f.created_at DESC LIMIT 5");

// Category distribution
$category_stats = mysqli_query($conn, "SELECT category, COUNT(*) as count FROM lost_items GROUP BY category UNION ALL SELECT category, COUNT(*) FROM found_items GROUP BY category");
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin Dashboard - College Management</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    .stat-card { transition: transform 0.2s; }
    .stat-card:hover { transform: translateY(-5px); }
  </style>
</head>
<body>
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
      <a class="navbar-brand" href="index.html">🏛️ Admin Panel - College Management</a>
      <div class="d-flex nav-gap">
        <a class="btn btn-outline-light btn-sm" href="admin_items.php">Manage Items</a>
        <a class="btn btn-outline-light btn-sm" href="admin_users.php">Manage Users</a>
        <a class="btn btn-outline-light btn-sm" href="admin_matches.php">Manage Matches</a>
        <a class="btn btn-outline-light btn-sm" href="admin_activity.php">Activity Log</a>
        <a class="btn btn-outline-light btn-sm" href="admin_reports.php">Reports</a>
        <a class="btn btn-danger btn-sm" href="logout.php">Logout</a>
      </div>
    </div>
  </nav>

  <div class="container-fluid py-4">
    <?php if (!empty($_GET['msg'])): ?><div class="alert alert-success auto-fade py-2"><?php echo htmlspecialchars($_GET['msg']); ?></div><?php endif; ?>
    <h2 class="h4 mb-4">Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?> - System Overview</h2>
    
    <!-- Statistics Cards -->
    <div class="row g-3 mb-4">
      <div class="col-md-3">
        <div class="card stat-card text-center border-primary">
          <div class="card-body">
            <div class="text-muted small">Total Users</div>
            <div class="h3 mb-0 text-primary"><?php echo $tot_users; ?></div>
            <div class="small text-muted mt-1"><?php echo $tot_students; ?> Students, <?php echo $tot_staff; ?> Staff</div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card stat-card text-center border-danger">
          <div class="card-body">
            <div class="text-muted small">Lost Items</div>
            <div class="h3 mb-0 text-danger"><?php echo $tot_lost; ?></div>
            <div class="small text-muted mt-1"><?php echo mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM lost_items WHERE status='Pending'"))[0]; ?> Pending</div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card stat-card text-center border-success">
          <div class="card-body">
            <div class="text-muted small">Found Items</div>
            <div class="h3 mb-0 text-success"><?php echo $tot_found; ?></div>
            <div class="small text-muted mt-1"><?php echo mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM found_items WHERE status='Pending'"))[0]; ?> Pending</div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card stat-card text-center border-warning">
          <div class="card-body">
            <div class="text-muted small">Total Matches</div>
            <div class="h3 mb-0 text-warning"><?php echo $tot_matches; ?></div>
            <div class="small text-muted mt-1"><?php echo mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM matches WHERE status='Pending'"))[0]; ?> Pending</div>
          </div>
        </div>
      </div>
    </div>

    <div class="row g-3 mb-4">
      <div class="col-md-3">
        <div class="card stat-card text-center border-info">
          <div class="card-body">
            <div class="text-muted small">Returned Items</div>
            <div class="h3 mb-0 text-info"><?php echo $tot_returned; ?></div>
            <div class="small text-success mt-1">✓ Successfully Recovered</div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card stat-card text-center border-secondary">
          <div class="card-body">
            <div class="text-muted small">Pending Items</div>
            <div class="h3 mb-0 text-secondary"><?php echo $tot_pending; ?></div>
            <div class="small text-warning mt-1">Awaiting Match/Return</div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card stat-card text-center border-dark">
          <div class="card-body">
            <div class="text-muted small">Success Rate</div>
            <div class="h3 mb-0 text-dark">
              <?php 
              $total_items = $tot_lost + $tot_found;
              $success_rate = $total_items > 0 ? round(($tot_returned / $total_items) * 100, 1) : 0;
              echo $success_rate;
              ?>%
            </div>
            <div class="small text-muted mt-1">Recovery Rate</div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card stat-card text-center border-primary">
          <div class="card-body">
            <div class="text-muted small">Match Rate</div>
            <div class="h3 mb-0 text-primary">
              <?php 
              $match_rate = $total_items > 0 ? round(($tot_matches / ($total_items / 2)) * 100, 1) : 0;
              echo $match_rate > 100 ? 100 : $match_rate;
              ?>%
            </div>
            <div class="small text-muted mt-1">Matching Efficiency</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="row g-3 mb-4">
      <div class="col-12">
        <div class="card">
          <div class="card-header bg-dark text-white">Quick Actions</div>
          <div class="card-body">
            <div class="row g-2">
              <div class="col-md-2">
                <a href="admin_items.php?type=lost" class="btn btn-outline-danger w-100">View Lost Items</a>
              </div>
              <div class="col-md-2">
                <a href="admin_items.php?type=found" class="btn btn-outline-success w-100">View Found Items</a>
              </div>
              <div class="col-md-2">
                <a href="admin_matches.php" class="btn btn-outline-warning w-100">View Matches</a>
              </div>
              <div class="col-md-2">
                <a href="admin_users.php" class="btn btn-outline-primary w-100">Manage Users</a>
              </div>
              <div class="col-md-2">
                <a href="admin_activity.php" class="btn btn-outline-info w-100">Activity Log</a>
              </div>
              <div class="col-md-2">
                <a href="admin_reports.php" class="btn btn-outline-dark w-100">Generate Reports</a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Activities & Items -->
    <div class="row g-3">
      <div class="col-md-6">
        <div class="card">
          <div class="card-header bg-dark text-white">Recent Activities</div>
          <div class="card-body" style="max-height: 400px; overflow-y: auto;">
            <?php if ($recent_activities && mysqli_num_rows($recent_activities) > 0): ?>
            <ul class="list-group list-group-flush">
              <?php while($act = mysqli_fetch_assoc($recent_activities)): ?>
              <li class="list-group-item">
                <div class="d-flex justify-content-between">
                  <div>
                    <strong><?php echo htmlspecialchars($act['action']); ?></strong>
                    <?php if ($act['user_name']): ?>
                    <div class="small text-muted">by <?php echo htmlspecialchars($act['user_name']); ?> (<?php echo htmlspecialchars($act['user_role']); ?>)</div>
                    <?php endif; ?>
                    <?php if ($act['details']): ?>
                    <div class="small"><?php echo htmlspecialchars($act['details']); ?></div>
                    <?php endif; ?>
                  </div>
                  <div class="text-muted small text-end">
                    <?php echo date('M d, h:i A', strtotime($act['created_at'])); ?>
                  </div>
                </div>
              </li>
              <?php endwhile; ?>
            </ul>
            <?php else: ?>
            <p class="text-muted text-center py-3">No recent activities</p>
            <?php endif; ?>
          </div>
        </div>
      </div>
      
      <div class="col-md-6">
        <div class="card">
          <div class="card-header bg-dark text-white">Recent Items</div>
          <div class="card-body" style="max-height: 400px; overflow-y: auto;">
            <div class="mb-3">
              <h6 class="text-danger">🔴 Recent Lost Items</h6>
              <ul class="list-group list-group-flush">
                <?php 
                if ($recent_lost && mysqli_num_rows($recent_lost) > 0):
                  while($item = mysqli_fetch_assoc($recent_lost)): ?>
                  <li class="list-group-item">
                    <div class="d-flex justify-content-between">
                      <div>
                        <strong><?php echo htmlspecialchars($item['item_name']); ?></strong>
                        <div class="small text-muted">by <?php echo htmlspecialchars($item['reporter_name']); ?> • <?php echo htmlspecialchars($item['category']); ?></div>
                      </div>
                      <span class="badge text-bg-<?php echo $item['status']==='Returned'?'success':($item['status']==='Matched'?'warning':'secondary'); ?>">
                        <?php echo $item['status']; ?>
                      </span>
                    </div>
                  </li>
                <?php endwhile; else: ?>
                  <li class="list-group-item text-muted">No recent lost items</li>
                <?php endif; ?>
              </ul>
            </div>
            <div>
              <h6 class="text-success">🟢 Recent Found Items</h6>
              <ul class="list-group list-group-flush">
                <?php 
                if ($recent_found && mysqli_num_rows($recent_found) > 0):
                  while($item = mysqli_fetch_assoc($recent_found)): ?>
                  <li class="list-group-item">
                    <div class="d-flex justify-content-between">
                      <div>
                        <strong><?php echo htmlspecialchars($item['item_name']); ?></strong>
                        <div class="small text-muted">by <?php echo htmlspecialchars($item['reporter_name']); ?> • <?php echo htmlspecialchars($item['category']); ?></div>
                      </div>
                      <span class="badge text-bg-<?php echo $item['status']==='Returned'?'success':($item['status']==='Matched'?'warning':'secondary'); ?>">
                        <?php echo $item['status']; ?>
                      </span>
                    </div>
                  </li>
                <?php endwhile; else: ?>
                  <li class="list-group-item text-muted">No recent found items</li>
                <?php endif; ?>
              </ul>
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

