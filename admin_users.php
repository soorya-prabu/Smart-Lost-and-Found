<?php include 'config.php'; requireLogin(); 
if (currentUserRole() !== 'admin') redirectWith('index.html');

$role_filter = sanitize($conn, $_GET['role'] ?? '');
$search = sanitize($conn, $_GET['search'] ?? '');

// Handle actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $action = sanitize($conn, $_GET['action']);
    
    if ($action == 'delete' && $id != currentUserId()) {
        mysqli_query($conn, "DELETE FROM users WHERE user_id=$id");
        logActivity($conn, "Deleted User", null, $id);
        redirectWith('admin_users.php', ['msg' => 'User deleted successfully']);
    } elseif ($action == 'change_role') {
        $new_role = sanitize($conn, $_GET['role']);
        if (in_array($new_role, ['student', 'staff'])) {
            mysqli_query($conn, "UPDATE users SET role='$new_role' WHERE user_id=$id");
            logActivity($conn, "Changed User Role", null, $id, "Changed to $new_role");
            redirectWith('admin_users.php', ['msg' => 'User role updated successfully']);
        }
    }
}

// Build query
$where = ["1=1"];
if ($role_filter) {
    $where[] = "role='$role_filter'";
}
if ($search) {
    $where[] = "(name LIKE '%$search%' OR email LIKE '%$search%')";
}
$where_str = implode(' AND ', $where);

// Get users
$users_q = mysqli_query($conn, "SELECT u.*, 
    (SELECT COUNT(*) FROM lost_items WHERE user_id=u.user_id) AS lost_count,
    (SELECT COUNT(*) FROM found_items WHERE user_id=u.user_id) AS found_count,
    (SELECT COUNT(*) FROM matches m JOIN lost_items l ON m.lost_id=l.lost_id WHERE l.user_id=u.user_id) + 
    (SELECT COUNT(*) FROM matches m JOIN found_items f ON m.found_id=f.found_id WHERE f.user_id=u.user_id) AS match_count
    FROM users u 
    WHERE $where_str 
    ORDER BY u.created_at DESC");
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Manage Users - Admin Panel</title>
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
        <a class="btn btn-outline-light btn-sm" href="admin_matches.php">Matches</a>
        <a class="btn btn-danger btn-sm" href="logout.php">Logout</a>
      </div>
    </div>
  </nav>

  <div class="container-fluid py-4">
    <?php if (!empty($_GET['msg'])): ?><div class="alert alert-success auto-fade py-2"><?php echo htmlspecialchars($_GET['msg']); ?></div><?php endif; ?>
    <h2 class="h4 mb-4">Manage Users</h2>
    
    <!-- Filters -->
    <div class="card mb-4">
      <div class="card-body">
        <form method="get" class="row g-3">
          <div class="col-md-6">
            <input type="text" name="search" class="form-control" placeholder="Search by name or email..." value="<?php echo htmlspecialchars($search); ?>">
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
            <button type="submit" class="btn btn-primary w-100">Filter</button>
          </div>
          <div class="col-md-2">
            <a href="admin_users.php" class="btn btn-outline-secondary w-100">Clear</a>
          </div>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="card-header bg-dark text-white">All Users</div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-sm table-hover">
            <thead>
              <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Lost Items</th>
                <th>Found Items</th>
                <th>Matches</th>
                <th>Joined</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($users_q && mysqli_num_rows($users_q) > 0): ?>
                <?php while($user = mysqli_fetch_assoc($users_q)): ?>
                <tr>
                  <td><?php echo $user['user_id']; ?></td>
                  <td><strong><?php echo htmlspecialchars($user['name']); ?></strong></td>
                  <td><?php echo htmlspecialchars($user['email']); ?></td>
                  <td>
                    <?php if ($user['user_id'] == currentUserId()): ?>
                      <span class="badge text-bg-danger">Admin (You)</span>
                    <?php else: ?>
                      <select class="form-select form-select-sm" style="width: auto; color: #212529;" onchange="changeRole(<?php echo $user['user_id']; ?>, this.value)">
                        <option value="student" <?php echo $user['role']=='student'?'selected':''; ?>>Student</option>
                        <option value="staff" <?php echo $user['role']=='staff'?'selected':''; ?>>Staff</option>
                        <?php if ($user['role'] == 'admin'): ?>
                        <option value="admin" selected>Admin</option>
                        <?php endif; ?>
                      </select>
                    <?php endif; ?>
                  </td>
                  <td><span class="badge text-bg-danger"><?php echo $user['lost_count']; ?></span></td>
                  <td><span class="badge text-bg-success"><?php echo $user['found_count']; ?></span></td>
                  <td><span class="badge text-bg-warning"><?php echo $user['match_count']; ?></span></td>
                  <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                  <td>
                    <?php if ($user['user_id'] != currentUserId()): ?>
                    <a href="?action=delete&id=<?php echo $user['user_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this user? All their items will also be deleted!')">Delete</a>
                    <?php else: ?>
                    <span class="text-muted">-</span>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr><td colspan="9" class="text-center text-muted">No users found</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <script>
    function changeRole(userId, newRole) {
      if (confirm('Change user role to ' + newRole + '?')) {
        window.location.href = '?action=change_role&id=' + userId + '&role=' + newRole;
      }
    }
  </script>
  <script src="assets/js/app.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

