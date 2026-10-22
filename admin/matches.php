<?php
require_once __DIR__ . '/../config/config.php';
requireRole('admin');

$status_filter = sanitize($conn, $_GET['status'] ?? '');

// Handle actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $action = $_GET['action'] ?? '';
    
    if ($action == 'update_status') {
        $new_status = $_GET['status'] ?? '';
        if (in_array($new_status, ['Pending', 'Contacted', 'Resolved'])) {
            dbExecute($conn, "UPDATE matches SET status = ? WHERE match_id = ?", [$new_status, $id], "si");
            logActivity($conn, "Updated Match Status", "match", $id, "Status changed to $new_status");
            redirectWith('matches.php', ['msg' => 'Match status updated successfully']);
        }
    } elseif ($action == 'delete') {
        dbExecute($conn, "DELETE FROM matches WHERE match_id = ?", [$id], "i");
        logActivity($conn, "Deleted Match", "match", $id);
        redirectWith('matches.php', ['msg' => 'Match deleted successfully']);
    }
}

// Build query
$where = ["1=1"];
$params = [];
$types = "";
if ($status_filter && in_array($status_filter, ['Pending', 'Contacted', 'Resolved'])) {
    $where[] = "m.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}
$where_str = implode(' AND ', $where);

// Get matches
$matches = dbFetchAll($conn, "SELECT m.*, 
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
    ORDER BY m.created_at DESC", $params, $types);

$pageTitle = 'Manage Matches - Admin Panel';
$activeAdminNav = 'matches';
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
    <h2 class="h4 mb-4">Manage All Matches</h2>
    
    <!-- Filters -->
    <div class="card mb-4 shadow-sm">
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
            <a href="matches.php" class="btn btn-outline-secondary w-100">Clear</a>
          </div>
        </form>
      </div>
    </div>

    <!-- Matches Table -->
    <div class="card shadow-sm">
      <div class="card-header bg-warning text-dark fw-bold">All Matches (<?php echo count($matches); ?>)</div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Match ID</th>
                <th>Lost Item</th>
                <th>Found Item</th>
                <th>Created</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($matches)): ?>
                <?php foreach ($matches as $m): ?>
                <tr>
                  <td>#<?php echo (int)$m['match_id']; ?></td>
                  <td>
                    <div><strong><?php echo cleanHtml($m['lost_item']); ?></strong></div>
                    <div class="small text-muted">By: <?php echo cleanHtml($m['lost_user_name']); ?> (<?php echo cleanHtml($m['lost_user_email']); ?>)</div>
                    <span class="badge text-bg-secondary"><?php echo cleanHtml($m['lost_status']); ?></span>
                  </td>
                  <td>
                    <div><strong><?php echo cleanHtml($m['found_item']); ?></strong></div>
                    <div class="small text-muted">By: <?php echo cleanHtml($m['found_user_name']); ?> (<?php echo cleanHtml($m['found_user_email']); ?>)</div>
                    <span class="badge text-bg-secondary"><?php echo cleanHtml($m['found_status']); ?></span>
                  </td>
                  <td><?php echo date('M d, Y h:i A', strtotime($m['created_at'])); ?></td>
                  <td>
                    <select class="form-select form-select-sm" style="width: auto; color: #212529;" onchange="updateMatchStatus(<?php echo (int)$m['match_id']; ?>, this.value)">
                      <option value="Pending" <?php echo $m['status']=='Pending'?'selected':''; ?>>Pending</option>
                      <option value="Contacted" <?php echo $m['status']=='Contacted'?'selected':''; ?>>Contacted</option>
                      <option value="Resolved" <?php echo $m['status']=='Resolved'?'selected':''; ?>>Resolved</option>
                    </select>
                  </td>
                  <td>
                    <div class="btn-group btn-group-sm">
                      <a href="mailto:<?php echo cleanHtml($m['lost_user_email']); ?>?cc=<?php echo cleanHtml($m['found_user_email']); ?>&subject=Lost%20and%20Found%20Match" class="btn btn-outline-primary" title="Email Both">📧 Email</a>
                      <a href="?action=delete&id=<?php echo (int)$m['match_id']; ?>" class="btn btn-outline-danger" onclick="return confirm('Delete this match?')">Delete</a>
                    </div>
                  </td>
                </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr><td colspan="6" class="text-center text-muted py-4">No matches found</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <script>
    function updateMatchStatus(id, val) {
      if (confirm('Change match status to ' + val + '?')) {
        window.location.href = 'matches.php?action=update_status&id=' + id + '&status=' + encodeURIComponent(val);
      }
    }
  </script>

  <?php include __DIR__ . '/../components/footer.php'; ?>
