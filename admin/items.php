<?php
require_once __DIR__ . '/../config/config.php';
requireRole('admin');

$type = sanitize($conn, $_GET['type'] ?? 'all');
$status = sanitize($conn, $_GET['status'] ?? '');
$search = sanitize($conn, $_GET['search'] ?? '');

// Handle actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $action = trim($_GET['action'] ?? '');
    
    if ($action === 'delete_lost') {
        dbExecute($conn, "DELETE FROM lost_items WHERE lost_id = ?", [$id]);
        logActivity($conn, "Deleted Lost Item", "lost", $id);
        redirectWith('items.php', ['msg' => 'Lost item deleted successfully']);
    } elseif ($action === 'delete_found') {
        dbExecute($conn, "DELETE FROM found_items WHERE found_id = ?", [$id]);
        logActivity($conn, "Deleted Found Item", "found", $id);
        redirectWith('items.php', ['msg' => 'Found item deleted successfully']);
    } elseif ($action === 'update_status') {
        $new_status = trim($_GET['status'] ?? '');
        $item_type = trim($_GET['item_type'] ?? '');
        if (in_array($new_status, ['Pending', 'Matched', 'Returned'])) {
            if ($item_type === 'lost') {
                dbExecute($conn, "UPDATE lost_items SET status = ? WHERE lost_id = ?", [$new_status, $id]);
                logActivity($conn, "Updated Lost Item Status", "lost", $id, "Status changed to $new_status");
            } else {
                dbExecute($conn, "UPDATE found_items SET status = ? WHERE found_id = ?", [$new_status, $id]);
                logActivity($conn, "Updated Found Item Status", "found", $id, "Status changed to $new_status");
            }
            redirectWith('items.php', ['msg' => 'Status updated successfully']);
        }
    }
}

// Build queries with prepared statements
$lost_where = ["1=1"];
$lost_params = [];
$found_where = ["1=1"];
$found_params = [];

if ($status !== '') {
    $lost_where[] = "l.status = ?";
    $lost_params[] = $status;
    $found_where[] = "f.status = ?";
    $found_params[] = $status;
}
if ($search !== '') {
    $lost_where[] = "(l.item_name LIKE ? OR l.description LIKE ? OR l.place_lost LIKE ?)";
    $like = '%' . $search . '%';
    $lost_params[] = $like;
    $lost_params[] = $like;
    $lost_params[] = $like;

    $found_where[] = "(f.item_name LIKE ? OR f.description LIKE ? OR f.place_found LIKE ?)";
    $found_params[] = $like;
    $found_params[] = $like;
    $found_params[] = $like;
}

$lost_where_str = implode(' AND ', $lost_where);
$found_where_str = implode(' AND ', $found_where);

// Get items
$lost_items = [];
$found_items = [];

if ($type === 'all' || $type === 'lost') {
    $lost_items = dbFetchAll($conn, "SELECT l.*, u.name AS reporter_name, u.email AS reporter_email, u.role AS reporter_role 
        FROM lost_items l 
        JOIN users u ON l.user_id = u.user_id 
        WHERE $lost_where_str 
        ORDER BY l.created_at DESC", $lost_params);
}

if ($type === 'all' || $type === 'found') {
    $found_items = dbFetchAll($conn, "SELECT f.*, u.name AS reporter_name, u.email AS reporter_email, u.role AS reporter_role 
        FROM found_items f 
        JOIN users u ON f.user_id = u.user_id 
        WHERE $found_where_str 
        ORDER BY f.created_at DESC", $found_params);
}

$pageTitle = 'Manage Items - Admin Panel';
$activeAdminNav = 'items';
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
    <h2 class="h4 mb-4">Manage All Items</h2>
    
    <!-- Filters -->
    <div class="card mb-4 shadow-sm">
      <div class="card-body">
        <form method="get" class="row g-3">
          <div class="col-md-4">
            <input type="text" name="search" class="form-control" placeholder="Search items..." value="<?php echo cleanHtml($search); ?>">
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
            <a href="items.php" class="btn btn-outline-secondary w-100">Clear</a>
          </div>
        </form>
      </div>
    </div>

    <?php if ($type == 'all' || $type == 'lost'): ?>
    <div class="card mb-4 shadow-sm">
      <div class="card-header bg-danger text-white fw-bold">🔴 Lost Items (<?php echo count($lost_items); ?>)</div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
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
                      <img src="<?php echo asset($item['image']); ?>" class="rounded" style="width: 40px; height: 40px; object-fit: cover; cursor: pointer;" onclick="previewImage(this.src)" alt="<?php echo cleanHtml($item['item_name']); ?>">
                      <?php endif; ?>
                      <div>
                        <strong><?php echo cleanHtml($item['item_name']); ?></strong>
                        <?php if ($item['description']): ?>
                        <div class="small text-muted"><?php echo cleanHtml(substr($item['description'], 0, 50)); ?>...</div>
                        <?php endif; ?>
                      </div>
                    </div>
                  </td>
                  <td>
                    <div><?php echo cleanHtml($item['reporter_name']); ?></div>
                    <div class="small text-muted"><?php echo cleanHtml($item['reporter_email']); ?></div>
                    <span class="badge text-bg-info"><?php echo ucfirst($item['reporter_role']); ?></span>
                  </td>
                  <td><?php echo cleanHtml($item['category']); ?></td>
                  <td><?php echo cleanHtml($item['place_lost']); ?></td>
                  <td><?php echo date('M d, Y', strtotime($item['date_lost'])); ?></td>
                  <td>
                    <select class="form-select form-select-sm" style="width: auto; color: #212529;" onchange="updateStatus('lost', <?php echo $item['lost_id']; ?>, this.value)">
                      <option value="Pending" <?php echo $item['status']=='Pending'?'selected':''; ?>>Pending</option>
                      <option value="Matched" <?php echo $item['status']=='Matched'?'selected':''; ?>>Matched</option>
                      <option value="Returned" <?php echo $item['status']=='Returned'?'selected':''; ?>>Returned</option>
                    </select>
                  </td>
                  <td>
                    <a href="?action=delete_lost&id=<?php echo $item['lost_id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this item?')">Delete</a>
                  </td>
                </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No lost items found</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <?php if ($type == 'all' || $type == 'found'): ?>
    <div class="card mb-4 shadow-sm">
      <div class="card-header bg-success text-white fw-bold">🟢 Found Items (<?php echo count($found_items); ?>)</div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
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
                      <img src="<?php echo asset($item['image']); ?>" class="rounded" style="width: 40px; height: 40px; object-fit: cover; cursor: pointer;" onclick="previewImage(this.src)" alt="<?php echo cleanHtml($item['item_name']); ?>">
                      <?php endif; ?>
                      <div>
                        <strong><?php echo cleanHtml($item['item_name']); ?></strong>
                        <?php if ($item['description']): ?>
                        <div class="small text-muted"><?php echo cleanHtml(substr($item['description'], 0, 50)); ?>...</div>
                        <?php endif; ?>
                      </div>
                    </div>
                  </td>
                  <td>
                    <div><?php echo cleanHtml($item['reporter_name']); ?></div>
                    <div class="small text-muted"><?php echo cleanHtml($item['reporter_email']); ?></div>
                    <span class="badge text-bg-info"><?php echo ucfirst($item['reporter_role']); ?></span>
                  </td>
                  <td><?php echo cleanHtml($item['category']); ?></td>
                  <td><?php echo cleanHtml($item['place_found']); ?></td>
                  <td><?php echo date('M d, Y', strtotime($item['date_found'])); ?></td>
                  <td>
                    <select class="form-select form-select-sm" style="width: auto; color: #212529;" onchange="updateStatus('found', <?php echo $item['found_id']; ?>, this.value)">
                      <option value="Pending" <?php echo $item['status']=='Pending'?'selected':''; ?>>Pending</option>
                      <option value="Matched" <?php echo $item['status']=='Matched'?'selected':''; ?>>Matched</option>
                      <option value="Returned" <?php echo $item['status']=='Returned'?'selected':''; ?>>Returned</option>
                    </select>
                  </td>
                  <td>
                    <a href="?action=delete_found&id=<?php echo $item['found_id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this item?')">Delete</a>
                  </td>
                </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No found items found</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <script>
    function updateStatus(type, id, val) {
      if (confirm('Change status to ' + val + '?')) {
        window.location.href = 'items.php?action=update_status&item_type=' + type + '&id=' + id + '&status=' + encodeURIComponent(val);
      }
    }
  </script>

  <?php include __DIR__ . '/../components/footer.php'; ?>
