<?php
require_once __DIR__ . '/../config/config.php';
requireRole('admin');

$action_filter = trim($_GET['action'] ?? '');
$role_filter = trim($_GET['role'] ?? '');
$limit = (int)($_GET['limit'] ?? 100);
if ($limit <= 0 || $limit > 500) $limit = 100;

// Build query
$where = ["1=1"];
$params = [];
$types = "";
if ($action_filter !== '') {
    $where[] = "a.action LIKE ?";
    $params[] = "%$action_filter%";
    $types .= "s";
}
if ($role_filter && in_array($role_filter, ['student', 'staff', 'admin'])) {
    $where[] = "a.user_role = ?";
    $params[] = $role_filter;
    $types .= "s";
}
$where_str = implode(' AND ', $where);

// Get activities
$activities = dbFetchAll($conn, "SELECT a.*, u.name AS user_name 
    FROM activity_log a 
    LEFT JOIN users u ON a.user_id = u.user_id 
    WHERE $where_str
    ORDER BY a.created_at DESC 
    LIMIT $limit", $params, $types);

$pageTitle = 'Activity Log - Admin Panel';
$activeAdminNav = 'activity';
?>
<!doctype html>
<html lang="en">
<head>
  <?php include __DIR__ . '/../components/head.php'; ?>
</head>
<body>
  <?php include __DIR__ . '/../components/admin_navbar.php'; ?>

  <div class="container-fluid py-4">
    <h2 class="h4 mb-4">Activity Log - System Activities</h2>
    
    <!-- Filters -->
    <div class="card mb-4 shadow-sm">
      <div class="card-body">
        <form method="get" class="row g-3">
          <div class="col-md-4">
            <input type="text" name="action" class="form-control" placeholder="Filter by action..." value="<?php echo cleanHtml($action_filter); ?>">
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
              <option value="50" <?php echo $limit==50?'selected':''; ?>>50 entries</option>
              <option value="100" <?php echo $limit==100?'selected':''; ?>>100 entries</option>
              <option value="200" <?php echo $limit==200?'selected':''; ?>>200 entries</option>
            </select>
          </div>
          <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100">Filter</button>
          </div>
          <div class="col-md-2">
            <a href="activity.php" class="btn btn-outline-secondary w-100">Clear</a>
          </div>
        </form>
      </div>
    </div>

    <!-- Activity Table -->
    <div class="card shadow-sm">
      <div class="card-header bg-info text-white fw-bold">System Activity Log (<?php echo count($activities); ?>)</div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Timestamp</th>
                <th>User</th>
                <th>Role</th>
                <th>Action</th>
                <th>Target Type</th>
                <th>Target ID</th>
                <th>Details</th>
                <th>IP Address</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($activities)): ?>
                <?php foreach ($activities as $act): ?>
                <tr>
                  <td class="text-nowrap"><?php echo date('M d, Y h:i:s A', strtotime($act['created_at'])); ?></td>
                  <td><strong><?php echo cleanHtml($act['user_name'] ?? 'System/Guest'); ?></strong></td>
                  <td>
                    <?php 
                    $roleBadge = ($act['user_role'] ?? '') === 'admin' ? 'danger' : (($act['user_role'] ?? '') === 'staff' ? 'success' : 'primary');
                    ?>
                    <span class="badge text-bg-<?php echo $roleBadge; ?>"><?php echo ucfirst(cleanHtml($act['user_role'] ?? 'unknown')); ?></span>
                  </td>
                  <td><span class="fw-semibold"><?php echo cleanHtml($act['action']); ?></span></td>
                  <td><?php echo cleanHtml($act['item_type'] ?? '-'); ?></td>
                  <td><?php echo $act['item_id'] ? '#' . (int)$act['item_id'] : '-'; ?></td>
                  <td><small class="text-muted"><?php echo cleanHtml($act['details'] ?? '-'); ?></small></td>
                  <td><code><?php echo cleanHtml($act['ip_address'] ?? '-'); ?></code></td>
                </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr><td colspan="8" class="text-center text-muted py-4">No activity records found</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <?php include __DIR__ . '/../components/footer.php'; ?>
