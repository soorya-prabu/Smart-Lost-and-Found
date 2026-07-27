<?php include 'config.php'; requireLogin(); 
if (currentUserRole() !== 'admin') redirectWith('index.html');

$action_filter = sanitize($conn, $_GET['action'] ?? '');
$role_filter = sanitize($conn, $_GET['role'] ?? '');
$limit = (int)($_GET['limit'] ?? 100);

// Build query
$where = ["1=1"];
if ($action_filter) {
    $where[] = "action LIKE '%$action_filter%'";
}
if ($role_filter) {
    $where[] = "user_role='$role_filter'";
}
$where_str = implode(' AND ', $where);

// Get activities
$activities_q = mysqli_query($conn, "SELECT a.*, u.name AS user_name 
    FROM activity_log a 
    LEFT JOIN users u ON a.user_id = u.user_id 
    WHERE $where_str
    ORDER BY a.created_at DESC 
    LIMIT $limit");
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Activity Log - Admin Panel</title>
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
    <h2 class="h4 mb-4">Activity Log - System Activities</h2>
    
    <!-- Filters -->
    <div class="card mb-4">
      <div class="card-body">
        <form method="get" class="row g-3">
          <div class="col-md-4">
            <input type="text" name="action" class="form-control" placeholder="Filter by action..." value="<?php echo htmlspecialchars($action_filter); ?>">
          </div>
          <div class="col-md-2">
            <select name="role" class="form-select" style="color: #212529;">
              <option value="">All Roles</option>
              <option value="student" <?php echo $role_filter=='student'?'selected':''; ?>>Students</option>
              <option value="staff" <?php echo $role_filter=='staff'?'selected':''; ?>>Staff</option>
              <option value="admin" <?php echo $role_filter=='admin'?'selected':''; ?>>Admins</option>
            </select>
          </div>
          <div class="col-md-2">
            <select name="limit" class="form-select" style="color: #212529;">
              <option value="50" <?php echo $limit==50?'selected':''; ?>>Last 50</option>
              <option value="100" <?php echo $limit==100?'selected':''; ?>>Last 100</option>
              <option value="200" <?php echo $limit==200?'selected':''; ?>>Last 200</option>
              <option value="500" <?php echo $limit==500?'selected':''; ?>>Last 500</option>
            </select>
          </div>
          <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100">Filter</button>
          </div>
          <div class="col-md-2">
            <a href="admin_activity.php" class="btn btn-outline-secondary w-100">Clear</a>
          </div>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="card-header bg-dark text-white">Activity Log</div>
      <div class="card-body" style="max-height: 600px; overflow-y: auto;">
        <div class="table-responsive">
          <table class="table table-sm table-hover">
            <thead>
              <tr>
                <th>Time</th>
                <th>User</th>
                <th>Role</th>
                <th>Action</th>
                <th>Item Type</th>
                <th>Item ID</th>
                <th>Details</th>
                <th>IP Address</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($activities_q && mysqli_num_rows($activities_q) > 0): ?>
                <?php while($act = mysqli_fetch_assoc($activities_q)): ?>
                <tr>
                  <td><?php echo date('M d, Y<br>h:i A', strtotime($act['created_at'])); ?></td>
                  <td><?php echo htmlspecialchars($act['user_name'] ?? 'System'); ?></td>
                  <td><span class="badge text-bg-<?php echo $act['user_role']=='admin'?'danger':($act['user_role']=='staff'?'warning':'info'); ?>"><?php echo ucfirst($act['user_role'] ?? 'Unknown'); ?></span></td>
                  <td><strong><?php echo htmlspecialchars($act['action']); ?></strong></td>
                  <td><?php echo htmlspecialchars($act['item_type'] ?? '-'); ?></td>
                  <td><?php echo $act['item_id'] ?? '-'; ?></td>
                  <td class="small"><?php echo htmlspecialchars($act['details'] ?? '-'); ?></td>
                  <td class="small text-muted"><?php echo htmlspecialchars($act['ip_address']); ?></td>
                </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr><td colspan="8" class="text-center text-muted">No activities found</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <script src="assets/js/app.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

