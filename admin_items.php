<?php include 'config.php'; requireLogin(); 
if (currentUserRole() !== 'admin') redirectWith('index.html');

$type = sanitize($conn, $_GET['type'] ?? 'all');
$status = sanitize($conn, $_GET['status'] ?? '');
$search = sanitize($conn, $_GET['search'] ?? '');

// Handle actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $action = sanitize($conn, $_GET['action']);
    
    if ($action == 'delete_lost') {
        mysqli_query($conn, "DELETE FROM lost_items WHERE lost_id=$id");
        logActivity($conn, "Deleted Lost Item", "lost", $id);
        redirectWith('admin_items.php', ['msg' => 'Lost item deleted successfully']);
    } elseif ($action == 'delete_found') {
        mysqli_query($conn, "DELETE FROM found_items WHERE found_id=$id");
        logActivity($conn, "Deleted Found Item", "found", $id);
        redirectWith('admin_items.php', ['msg' => 'Found item deleted successfully']);
    } elseif ($action == 'update_status') {
        $new_status = sanitize($conn, $_GET['status']);
        $item_type = sanitize($conn, $_GET['item_type']);
        if (in_array($new_status, ['Pending', 'Matched', 'Returned'])) {
            if ($item_type == 'lost') {
                mysqli_query($conn, "UPDATE lost_items SET status='$new_status' WHERE lost_id=$id");
                logActivity($conn, "Updated Lost Item Status", "lost", $id, "Status changed to $new_status");
            } else {
                mysqli_query($conn, "UPDATE found_items SET status='$new_status' WHERE found_id=$id");
                logActivity($conn, "Updated Found Item Status", "found", $id, "Status changed to $new_status");
            }
            redirectWith('admin_items.php', ['msg' => 'Status updated successfully']);
        }
    }
}

// Build queries
$lost_where = ["1=1"];
$found_where = ["1=1"];

if ($status) {
    $lost_where[] = "l.status='$status'";
    $found_where[] = "f.status='$status'";
}
if ($search) {
    $lost_where[] = "(l.item_name LIKE '%$search%' OR l.description LIKE '%$search%' OR l.place_lost LIKE '%$search%')";
    $found_where[] = "(f.item_name LIKE '%$search%' OR f.description LIKE '%$search%' OR f.place_found LIKE '%$search%')";
}

$lost_where_str = implode(' AND ', $lost_where);
$found_where_str = implode(' AND ', $found_where);

// Get items
$lost_items = [];
$found_items = [];

if ($type == 'all' || $type == 'lost') {
    $lost_q = mysqli_query($conn, "SELECT l.*, u.name AS reporter_name, u.email AS reporter_email, u.role AS reporter_role 
        FROM lost_items l 
        JOIN users u ON l.user_id = u.user_id 
        WHERE $lost_where_str 
        ORDER BY l.created_at DESC");
    if ($lost_q) {
        while ($row = mysqli_fetch_assoc($lost_q)) {
            $lost_items[] = $row;
        }
    }
}

if ($type == 'all' || $type == 'found') {
    $found_q = mysqli_query($conn, "SELECT f.*, u.name AS reporter_name, u.email AS reporter_email, u.role AS reporter_role 
        FROM found_items f 
        JOIN users u ON f.user_id = u.user_id 
        WHERE $found_where_str 
        ORDER BY f.created_at DESC");
    if ($found_q) {
        while ($row = mysqli_fetch_assoc($found_q)) {
            $found_items[] = $row;
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Manage Items - Admin Panel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
      <a class="navbar-brand" href="admin_dashboard.php">🏛️ Admin Panel</a>
      <div class="d-flex nav-gap">
        <a class="btn btn-outline-light btn-sm" href="admin_dashboard.php">Dashboard</a>
        <a class="btn btn-outline-light btn-sm" href="admin_users.php">Users</a>
        <a class="btn btn-outline-light btn-sm" href="admin_matches.php">Matches</a>
        <a class="btn btn-danger btn-sm" href="logout.php">Logout</a>
      </div>
    </div>
  </nav>

  <div class="container-fluid py-4">
    <?php if (!empty($_GET['msg'])): ?><div class="alert alert-success auto-fade py-2"><?php echo htmlspecialchars($_GET['msg']); ?></div><?php endif; ?>
    <h2 class="h4 mb-4">Manage All Items</h2>
    
    <!-- Filters -->
    <div class="card mb-4">
      <div class="card-body">
        <form method="get" class="row g-3">
          <div class="col-md-4">
            <input type="text" name="search" class="form-control" placeholder="Search items..." value="<?php echo htmlspecialchars($search); ?>">
          </div>
          <div class="col-md-2">
            <select name="type" class="form-select" style="color: #212529;">
              <option value="all" <?php echo $type=='all'?'selected':''; ?>>All Items</option>
              <option value="lost" <?php echo $type=='lost'?'selected':''; ?>>Lost Only</option>
              <option value="found" <?php echo $type=='found'?'selected':''; ?>>Found Only</option>
            </select>
          </div>
          <div class="col-md-2">
            <select name="status" class="form-select" style="color: #212529;">
              <option value="">All Status</option>
              <option value="Pending" <?php echo $status=='Pending'?'selected':''; ?>>Pending</option>
              <option value="Matched" <?php echo $status=='Matched'?'selected':''; ?>>Matched</option>
              <option value="Returned" <?php echo $status=='Returned'?'selected':''; ?>>Returned</option>
            </select>
          </div>
          <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100">Filter</button>
          </div>
          <div class="col-md-2">
            <a href="admin_items.php" class="btn btn-outline-secondary w-100">Clear</a>
          </div>
        </form>
      </div>
    </div>

    <?php if ($type == 'all' || $type == 'lost'): ?>
    <div class="card mb-4">
      <div class="card-header bg-danger text-white">🔴 Lost Items (<?php echo count($lost_items); ?>)</div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-sm table-hover">
            <thead>
              <tr>
                <th>Item</th>
                <th>Reporter</th>
                <th>Category</th>
                <th>Place</th>
                <th>Date</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (count($lost_items) > 0): ?>
                <?php foreach($lost_items as $item): ?>
                <tr>
                  <td>
                    <div class="d-flex align-items-center gap-2">
                      <?php if ($item['image']): ?>
                      <img src="<?php echo htmlspecialchars($item['image']); ?>" class="rounded" style="width: 40px; height: 40px; object-fit: cover; cursor: pointer;" onclick="previewImage(this.src)">
                      <?php endif; ?>
                      <div>
                        <strong><?php echo htmlspecialchars($item['item_name']); ?></strong>
                        <?php if ($item['description']): ?>
                        <div class="small text-muted"><?php echo htmlspecialchars(substr($item['description'], 0, 50)); ?>...</div>
                        <?php endif; ?>
                      </div>
                    </div>
                  </td>
                  <td>
                    <div><?php echo htmlspecialchars($item['reporter_name']); ?></div>
                    <div class="small text-muted"><?php echo htmlspecialchars($item['reporter_email']); ?></div>
                    <span class="badge text-bg-info"><?php echo ucfirst($item['reporter_role']); ?></span>
                  </td>
                  <td><?php echo htmlspecialchars($item['category']); ?></td>
                  <td><?php echo htmlspecialchars($item['place_lost']); ?></td>
                  <td><?php echo date('M d, Y', strtotime($item['date_lost'])); ?></td>
                  <td>
                    <select class="form-select form-select-sm" style="width: auto; color: #212529;" onchange="updateStatus('lost', <?php echo $item['lost_id']; ?>, this.value)">
                      <option value="Pending" <?php echo $item['status']=='Pending'?'selected':''; ?>>Pending</option>
                      <option value="Matched" <?php echo $item['status']=='Matched'?'selected':''; ?>>Matched</option>
                      <option value="Returned" <?php echo $item['status']=='Returned'?'selected':''; ?>>Returned</option>
                    </select>
                  </td>
                  <td>
                    <a href="?action=delete_lost&id=<?php echo $item['lost_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this item?')">Delete</a>
                  </td>
                </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr><td colspan="7" class="text-center text-muted">No lost items found</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <?php if ($type == 'all' || $type == 'found'): ?>
    <div class="card mb-4">
      <div class="card-header bg-success text-white">🟢 Found Items (<?php echo count($found_items); ?>)</div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-sm table-hover">
            <thead>
              <tr>
                <th>Item</th>
                <th>Reporter</th>
                <th>Category</th>
                <th>Place</th>
                <th>Date</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (count($found_items) > 0): ?>
                <?php foreach($found_items as $item): ?>
                <tr>
                  <td>
                    <div class="d-flex align-items-center gap-2">
                      <?php if ($item['image']): ?>
                      <img src="<?php echo htmlspecialchars($item['image']); ?>" class="rounded" style="width: 40px; height: 40px; object-fit: cover; cursor: pointer;" onclick="previewImage(this.src)">
                      <?php endif; ?>
                      <div>
                        <strong><?php echo htmlspecialchars($item['item_name']); ?></strong>
                        <?php if ($item['description']): ?>
                        <div class="small text-muted"><?php echo htmlspecialchars(substr($item['description'], 0, 50)); ?>...</div>
                        <?php endif; ?>
                      </div>
                    </div>
                  </td>
                  <td>
                    <div><?php echo htmlspecialchars($item['reporter_name']); ?></div>
                    <div class="small text-muted"><?php echo htmlspecialchars($item['reporter_email']); ?></div>
                    <span class="badge text-bg-info"><?php echo ucfirst($item['reporter_role']); ?></span>
                  </td>
                  <td><?php echo htmlspecialchars($item['category']); ?></td>
                  <td><?php echo htmlspecialchars($item['place_found']); ?></td>
                  <td><?php echo date('M d, Y', strtotime($item['date_found'])); ?></td>
                  <td>
                    <select class="form-select form-select-sm" style="width: auto; color: #212529;" onchange="updateStatus('found', <?php echo $item['found_id']; ?>, this.value)">
                      <option value="Pending" <?php echo $item['status']=='Pending'?'selected':''; ?>>Pending</option>
                      <option value="Matched" <?php echo $item['status']=='Matched'?'selected':''; ?>>Matched</option>
                      <option value="Returned" <?php echo $item['status']=='Returned'?'selected':''; ?>>Returned</option>
                    </select>
                  </td>
                  <td>
                    <a href="?action=delete_found&id=<?php echo $item['found_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this item?')">Delete</a>
                  </td>
                </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr><td colspan="7" class="text-center text-muted">No found items found</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <script>
    function updateStatus(type, id, status) {
      if (confirm('Update status to ' + status + '?')) {
        window.location.href = '?action=update_status&item_type=' + type + '&id=' + id + '&status=' + status;
      }
    }
  </script>
  <script src="assets/js/app.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

