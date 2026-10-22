<?php
require_once __DIR__ . '/../config/config.php';
requireRole('admin');

$role_filter = sanitize($conn, $_GET['role'] ?? '');
$search = trim($_GET['search'] ?? '');

// Handle actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $action = $_GET['action'] ?? '';
    
    if ($action == 'delete' && $id != currentUserId()) {
        dbExecute($conn, "DELETE FROM users WHERE user_id = ?", [$id], "i");
        logActivity($conn, "Deleted User", null, $id);
        redirectWith('users.php', ['msg' => 'User deleted successfully']);
    } elseif ($action == 'change_role') {
        $new_role = $_GET['role'] ?? '';
        if (in_array($new_role, ['student', 'staff'])) {
            dbExecute($conn, "UPDATE users SET role = ? WHERE user_id = ?", [$new_role, $id], "si");
            logActivity($conn, "Changed User Role", null, $id, "Changed to $new_role");
            redirectWith('users.php', ['msg' => 'User role updated successfully']);
        }
    }
}

// Build query
$where = ["1=1"];
$params = [];
$types = "";
if ($role_filter && in_array($role_filter, ['student', 'staff', 'admin'])) {
    $where[] = "u.role = ?";
    $params[] = $role_filter;
    $types .= "s";
}
if ($search !== '') {
    $where[] = "(u.name LIKE ? OR u.email LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $types .= "ss";
}
$where_str = implode(' AND ', $where);

// Get users
$users = dbFetchAll($conn, "SELECT u.*, 
    (SELECT COUNT(*) FROM lost_items WHERE user_id=u.user_id) AS lost_count,
    (SELECT COUNT(*) FROM found_items WHERE user_id=u.user_id) AS found_count,
    (SELECT COUNT(*) FROM matches m JOIN lost_items l ON m.lost_id=l.lost_id WHERE l.user_id=u.user_id) + 
    (SELECT COUNT(*) FROM matches m JOIN found_items f ON m.found_id=f.found_id WHERE f.user_id=u.user_id) AS match_count
    FROM users u 
    WHERE $where_str 
    ORDER BY u.created_at DESC", $params, $types);

$pageTitle = 'Manage Users - Admin Panel';
$activeAdminNav = 'users';
?>
<!doctype html>
<html lang="en">
<head>
  <?php include __DIR__ . '/../components/head.php'; ?>
</head>
<body>
  <?php include __DIR__ . '/../components/admin_navbar.php'; ?>

  <div class="container-fluid py-4">
    <?php if ($msg = flashMessage()): ?>
      <div class="alert alert-success auto-fade py-2"><?php echo $msg; ?></div>
    <?php endif; ?>
    <h2 class="h4 mb-4">Manage Users</h2>
    
    <!-- Filters -->
    <div class="card mb-4 shadow-sm">
      <div class="card-body">
        <form method="get" class="row g-3">
          <div class="col-md-6">
            <input type="text" name="search" class="form-control" placeholder="Search by name or email..." value="<?php echo cleanHtml($search); ?>">
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
            <a href="users.php" class="btn btn-outline-secondary w-100">Clear</a>
          </div>
        </form>
      </div>
    </div>

    <!-- Users Table -->
    <div class="card shadow-sm">
      <div class="card-header bg-primary text-white fw-bold">All Registered Users (<?php echo count($users); ?>)</div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
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
              <?php if (!empty($users)): ?>
                <?php foreach ($users as $u): ?>
                <tr>
                  <td><strong><?php echo cleanHtml($u['name']); ?></strong></td>
                  <td><a href="mailto:<?php echo cleanHtml($u['email']); ?>"><?php echo cleanHtml($u['email']); ?></a></td>
                  <td>
                    <?php if ($u['role'] !== 'admin'): ?>
                    <select class="form-select form-select-sm" style="width: auto; color: #212529;" onchange="changeRole(<?php echo (int)$u['user_id']; ?>, this.value)">
                      <option value="student" <?php echo $u['role']=='student'?'selected':''; ?>>Student</option>
                      <option value="staff" <?php echo $u['role']=='staff'?'selected':''; ?>>Staff</option>
                    </select>
                    <?php else: ?>
                    <span class="badge text-bg-danger">Admin</span>
                    <?php endif; ?>
                  </td>
                  <td><?php echo (int)$u['lost_count']; ?></td>
                  <td><?php echo (int)$u['found_count']; ?></td>
                  <td><?php echo (int)$u['match_count']; ?></td>
                  <td><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
                  <td>
                    <?php if ($u['user_id'] != currentUserId()): ?>
                    <a href="?action=delete&id=<?php echo (int)$u['user_id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this user? This will also remove their reported items.')">Delete</a>
                    <?php else: ?>
                    <span class="badge text-bg-secondary">Current User</span>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr><td colspan="8" class="text-center text-muted py-4">No users found</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <script>
    function changeRole(id, role) {
      if (confirm('Change user role to ' + role + '?')) {
        window.location.href = 'users.php?action=change_role&id=' + id + '&role=' + encodeURIComponent(role);
      }
    }
  </script>

  <?php include __DIR__ . '/../components/footer.php'; ?>
