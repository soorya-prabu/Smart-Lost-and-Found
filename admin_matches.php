<?php include 'config.php'; requireLogin(); 
if (currentUserRole() !== 'admin') redirectWith('index.html');

$status_filter = sanitize($conn, $_GET['status'] ?? '');

// Handle actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $action = sanitize($conn, $_GET['action']);
    
    if ($action == 'update_status') {
        $new_status = sanitize($conn, $_GET['status']);
        if (in_array($new_status, ['Pending', 'Contacted', 'Resolved'])) {
            mysqli_query($conn, "UPDATE matches SET status='$new_status' WHERE match_id=$id");
            logActivity($conn, "Updated Match Status", "match", $id, "Status changed to $new_status");
            redirectWith('admin_matches.php', ['msg' => 'Match status updated successfully']);
        }
    } elseif ($action == 'delete') {
        mysqli_query($conn, "DELETE FROM matches WHERE match_id=$id");
        logActivity($conn, "Deleted Match", "match", $id);
        redirectWith('admin_matches.php', ['msg' => 'Match deleted successfully']);
    }
}

// Build query
$where = ["1=1"];
if ($status_filter) {
    $where[] = "m.status='$status_filter'";
}
$where_str = implode(' AND ', $where);

// Get matches
$matches_q = mysqli_query($conn, "SELECT m.*, 
    l.item_name AS lost_item, l.user_id AS lost_user_id, l.status AS lost_status,
    f.item_name AS found_item, f.user_id AS found_user_id, f.status AS found_status,
    lu.name AS lost_user_name, lu.email AS lost_user_email, lu.role AS lost_user_role,
    fu.name AS found_user_name, fu.email AS found_user_email, fu.role AS found_user_role
    FROM matches m
    JOIN lost_items l ON m.lost_id = l.lost_id
    JOIN found_items f ON m.found_id = f.found_id
    JOIN users lu ON l.user_id = lu.user_id
    JOIN users fu ON f.user_id = fu.user_id
    WHERE $where_str
    ORDER BY m.created_at DESC");
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Manage Matches - Admin Panel</title>
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
    <?php if (!empty($_GET['msg'])): ?><div class="alert alert-success auto-fade py-2"><?php echo htmlspecialchars($_GET['msg']); ?></div><?php endif; ?>
    <h2 class="h4 mb-4">Manage All Matches</h2>
    
    <!-- Filters -->
    <div class="card mb-4">
      <div class="card-body">
        <form method="get" class="row g-3">
          <div class="col-md-3">
            <select name="status" class="form-select" style="color: #212529;">
              <option value="">All Status</option>
              <option value="Pending" <?php echo $status_filter=='Pending'?'selected':''; ?>>Pending</option>
              <option value="Contacted" <?php echo $status_filter=='Contacted'?'selected':''; ?>>Contacted</option>
              <option value="Resolved" <?php echo $status_filter=='Resolved'?'selected':''; ?>>Resolved</option>
            </select>
          </div>
          <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100">Filter</button>
          </div>
          <div class="col-md-2">
            <a href="admin_matches.php" class="btn btn-outline-secondary w-100">Clear</a>
          </div>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="card-header bg-dark text-white">All Matches</div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-sm table-hover">
            <thead>
              <tr>
                <th>Match ID</th>
                <th>Lost Item</th>
                <th>Lost By</th>
                <th>Found Item</th>
                <th>Found By</th>
                <th>Status</th>
                <th>Matched On</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($matches_q && mysqli_num_rows($matches_q) > 0): ?>
                <?php while($match = mysqli_fetch_assoc($matches_q)): ?>
                <tr>
                  <td><?php echo $match['match_id']; ?></td>
                  <td>
                    <strong class="text-danger"><?php echo htmlspecialchars($match['lost_item']); ?></strong>
                    <div class="small text-muted">Status: <?php echo $match['lost_status']; ?></div>
                  </td>
                  <td>
                    <div><?php echo htmlspecialchars($match['lost_user_name']); ?></div>
                    <div class="small text-muted"><?php echo htmlspecialchars($match['lost_user_email']); ?></div>
                    <span class="badge text-bg-info"><?php echo ucfirst($match['lost_user_role']); ?></span>
                  </td>
                  <td>
                    <strong class="text-success"><?php echo htmlspecialchars($match['found_item']); ?></strong>
                    <div class="small text-muted">Status: <?php echo $match['found_status']; ?></div>
                  </td>
                  <td>
                    <div><?php echo htmlspecialchars($match['found_user_name']); ?></div>
                    <div class="small text-muted"><?php echo htmlspecialchars($match['found_user_email']); ?></div>
                    <span class="badge text-bg-info"><?php echo ucfirst($match['found_user_role']); ?></span>
                  </td>
                  <td>
                    <select class="form-select form-select-sm" style="width: auto; color: #212529;" onchange="updateStatus(<?php echo $match['match_id']; ?>, this.value)">
                      <option value="Pending" <?php echo $match['status']=='Pending'?'selected':''; ?>>Pending</option>
                      <option value="Contacted" <?php echo $match['status']=='Contacted'?'selected':''; ?>>Contacted</option>
                      <option value="Resolved" <?php echo $match['status']=='Resolved'?'selected':''; ?>>Resolved</option>
                    </select>
                  </td>
                  <td><?php echo date('M d, Y h:i A', strtotime($match['created_at'])); ?></td>
                  <td>
                    <a href="?action=delete&id=<?php echo $match['match_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this match?')">Delete</a>
                  </td>
                </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr><td colspan="8" class="text-center text-muted">No matches found</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <script>
    function updateStatus(matchId, status) {
      if (confirm('Update match status to ' + status + '?')) {
        window.location.href = '?action=update_status&id=' + matchId + '&status=' + status;
      }
    }
  </script>
  <script src="assets/js/app.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

