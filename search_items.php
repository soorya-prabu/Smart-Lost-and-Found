<?php include 'config.php'; requireLogin();
$uid = currentUserId();
$role = currentUserRole();

// Search and filter parameters
$search = sanitize($conn, $_GET['search'] ?? '');
$category = sanitize($conn, $_GET['category'] ?? '');
$type = sanitize($conn, $_GET['type'] ?? 'all'); // all, lost, found
$status = sanitize($conn, $_GET['status'] ?? '');

// Build query for lost items
$lost_where = ["1=1"];
if ($search) {
    $lost_where[] = "(item_name LIKE '%$search%' OR description LIKE '%$search%' OR place_lost LIKE '%$search%')";
}
if ($category) {
    $lost_where[] = "category='$category'";
}
if ($status) {
    $lost_where[] = "status='$status'";
}
$lost_where_str = implode(' AND ', $lost_where);

// Build query for found items
$found_where = ["1=1"];
if ($search) {
    $found_where[] = "(item_name LIKE '%$search%' OR description LIKE '%$search%' OR place_found LIKE '%$search%')";
}
if ($category) {
    $found_where[] = "category='$category'";
}
if ($status) {
    $found_where[] = "status='$status'";
}
$found_where_str = implode(' AND ', $found_where);

// Get categories for dropdown
$categories_q = mysqli_query($conn, "SELECT DISTINCT category FROM lost_items UNION SELECT DISTINCT category FROM found_items ORDER BY category");

// Get items based on type
$lost_items = [];
$found_items = [];
if ($type == 'all' || $type == 'lost') {
    $lost_res = mysqli_query($conn, "SELECT l.*, u.name AS user_name, u.email AS user_email, u.role AS user_role 
        FROM lost_items l 
        JOIN users u ON l.user_id = u.user_id 
        WHERE $lost_where_str 
        ORDER BY l.created_at DESC LIMIT 50");
    if ($lost_res) {
        while ($row = mysqli_fetch_assoc($lost_res)) {
            $lost_items[] = $row;
        }
    }
}
if ($type == 'all' || $type == 'found') {
    $found_res = mysqli_query($conn, "SELECT f.*, u.name AS user_name, u.email AS user_email, u.role AS user_role 
        FROM found_items f 
        JOIN users u ON f.user_id = u.user_id 
        WHERE $found_where_str 
        ORDER BY f.created_at DESC LIMIT 50");
    if ($found_res) {
        while ($row = mysqli_fetch_assoc($found_res)) {
            $found_items[] = $row;
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Search Items - Smart Lost & Found</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom">
    <div class="container-fluid">
      <a class="navbar-brand" href="index.html">Smart Lost & Found</a>
      <div class="d-flex nav-gap">
        <?php if ($role==='staff'): ?>
          <a class="btn btn-outline-secondary" href="staff_dashboard.php">Dashboard</a>
        <?php else: ?>
          <a class="btn btn-outline-secondary" href="student_dashboard.php">Dashboard</a>
        <?php endif; ?>
        <a class="btn btn-danger" href="logout.php">Logout</a>
      </div>
    </div>
  </nav>

  <div class="container py-4">
    <h2 class="h4 mb-4">🔍 Search Items</h2>
    
    <!-- Search Form -->
    <div class="card mb-4">
      <div class="card-body">
        <form method="get" class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Search</label>
            <input type="text" name="search" class="form-control" placeholder="Item name, description, place..." value="<?php echo htmlspecialchars($search); ?>">
          </div>
          <div class="col-md-2">
            <label class="form-label">Category</label>
            <select name="category" class="form-select" style="color: #212529; background-color: #fff;">
              <option value="" style="color: #6c757d;">All Categories</option>
              <?php 
              if ($categories_q) {
                while($cat = mysqli_fetch_assoc($categories_q)): ?>
                  <option value="<?php echo htmlspecialchars($cat['category']); ?>" <?php echo $category==$cat['category']?'selected':''; ?> style="color: #212529;">
                    <?php echo htmlspecialchars($cat['category']); ?>
                  </option>
                <?php endwhile;
              }
              ?>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label">Type</label>
            <select name="type" class="form-select" style="color: #212529; background-color: #fff;">
              <option value="all" <?php echo $type=='all'?'selected':''; ?> style="color: #212529;">All Items</option>
              <option value="lost" <?php echo $type=='lost'?'selected':''; ?> style="color: #212529;">Lost Only</option>
              <option value="found" <?php echo $type=='found'?'selected':''; ?> style="color: #212529;">Found Only</option>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label">Status</label>
            <select name="status" class="form-select" style="color: #212529; background-color: #fff;">
              <option value="" style="color: #6c757d;">All Status</option>
              <option value="Pending" <?php echo $status=='Pending'?'selected':''; ?> style="color: #212529;">Pending</option>
              <option value="Matched" <?php echo $status=='Matched'?'selected':''; ?> style="color: #212529;">Matched</option>
              <option value="Returned" <?php echo $status=='Returned'?'selected':''; ?> style="color: #212529;">Returned</option>
            </select>
          </div>
          <div class="col-md-2 d-flex align-items-end">
            <button type="submit" class="btn btn-primary w-100">Search</button>
          </div>
        </form>
        <?php if ($search || $category || $status): ?>
        <div class="mt-2">
          <a href="search_items.php" class="btn btn-sm btn-outline-secondary">Clear Filters</a>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Results -->
    <?php if ($type == 'all' || $type == 'lost'): ?>
    <div class="card mb-4">
      <div class="card-header">🔴 Lost Items (<?php echo count($lost_items); ?>)</div>
      <div class="card-body">
        <?php if (count($lost_items) > 0): ?>
        <div class="row g-3">
          <?php foreach($lost_items as $item): ?>
          <div class="col-md-6 col-lg-4">
            <div class="card h-100">
              <?php if ($item['image']): ?>
              <img src="<?php echo htmlspecialchars($item['image']); ?>" class="card-img-top" style="height: 200px; object-fit: cover; cursor: pointer;" onclick="previewImage(this.src)" alt="<?php echo htmlspecialchars($item['item_name']); ?>">
              <?php endif; ?>
              <div class="card-body">
                <h6 class="card-title"><?php echo htmlspecialchars($item['item_name']); ?></h6>
                <p class="card-text small text-muted mb-2">
                  <strong>Category:</strong> <?php echo htmlspecialchars($item['category']); ?><br>
                  <strong>Place:</strong> <?php echo htmlspecialchars($item['place_lost']); ?><br>
                  <strong>Date:</strong> <?php echo date('M d, Y', strtotime($item['date_lost'])); ?>
                </p>
                <?php if ($item['description']): ?>
                <p class="card-text small"><?php echo htmlspecialchars(substr($item['description'], 0, 100)); ?><?php echo strlen($item['description']) > 100 ? '...' : ''; ?></p>
                <?php endif; ?>
                <div class="d-flex justify-content-between align-items-center mt-2">
                  <span class="badge text-bg-<?php echo $item['status']==='Returned'?'success':($item['status']==='Matched'?'warning':'secondary'); ?>">
                    <?php echo $item['status']; ?>
                  </span>
                  <a href="item_details.php?type=lost&id=<?php echo $item['lost_id']; ?>" class="btn btn-sm btn-outline-primary">View Details</a>
                </div>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p class="text-muted text-center py-3">No lost items found.</p>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <?php if ($type == 'all' || $type == 'found'): ?>
    <div class="card mb-4">
      <div class="card-header">🟢 Found Items (<?php echo count($found_items); ?>)</div>
      <div class="card-body">
        <?php if (count($found_items) > 0): ?>
        <div class="row g-3">
          <?php foreach($found_items as $item): ?>
          <div class="col-md-6 col-lg-4">
            <div class="card h-100">
              <?php if ($item['image']): ?>
              <img src="<?php echo htmlspecialchars($item['image']); ?>" class="card-img-top" style="height: 200px; object-fit: cover; cursor: pointer;" onclick="previewImage(this.src)" alt="<?php echo htmlspecialchars($item['item_name']); ?>">
              <?php endif; ?>
              <div class="card-body">
                <h6 class="card-title"><?php echo htmlspecialchars($item['item_name']); ?></h6>
                <p class="card-text small text-muted mb-2">
                  <strong>Category:</strong> <?php echo htmlspecialchars($item['category']); ?><br>
                  <strong>Place:</strong> <?php echo htmlspecialchars($item['place_found']); ?><br>
                  <strong>Date:</strong> <?php echo date('M d, Y', strtotime($item['date_found'])); ?>
                </p>
                <?php if ($item['description']): ?>
                <p class="card-text small"><?php echo htmlspecialchars(substr($item['description'], 0, 100)); ?><?php echo strlen($item['description']) > 100 ? '...' : ''; ?></p>
                <?php endif; ?>
                <div class="d-flex justify-content-between align-items-center mt-2">
                  <span class="badge text-bg-<?php echo $item['status']==='Returned'?'success':($item['status']==='Matched'?'warning':'secondary'); ?>">
                    <?php echo $item['status']; ?>
                  </span>
                  <a href="item_details.php?type=found&id=<?php echo $item['found_id']; ?>" class="btn btn-sm btn-outline-primary">View Details</a>
                </div>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p class="text-muted text-center py-3">No found items found.</p>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <script src="assets/js/app.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

