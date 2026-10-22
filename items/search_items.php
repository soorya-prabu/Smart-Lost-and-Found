<?php
require_once __DIR__ . '/../config/config.php';
requireLogin();

$uid = currentUserId();
$role = currentUserRole();

// Search and filter parameters
$search = trim($_GET['search'] ?? '');
$category = trim($_GET['category'] ?? '');
$type = trim($_GET['type'] ?? 'all'); // all, lost, found
$status = trim($_GET['status'] ?? '');

// Build query for lost items with prepared statements
$lost_where = ["1=1"];
$lost_params = [];
if ($search !== '') {
    $lost_where[] = "(l.item_name LIKE ? OR l.description LIKE ? OR l.place_lost LIKE ?)";
    $like = '%' . $search . '%';
    $lost_params[] = $like;
    $lost_params[] = $like;
    $lost_params[] = $like;
}
if ($category !== '') {
    $lost_where[] = "l.category = ?";
    $lost_params[] = $category;
}
if ($status !== '') {
    $lost_where[] = "l.status = ?";
    $lost_params[] = $status;
}
$lost_where_str = implode(' AND ', $lost_where);

// Build query for found items with prepared statements
$found_where = ["1=1"];
$found_params = [];
if ($search !== '') {
    $found_where[] = "(f.item_name LIKE ? OR f.description LIKE ? OR f.place_found LIKE ?)";
    $like = '%' . $search . '%';
    $found_params[] = $like;
    $found_params[] = $like;
    $found_params[] = $like;
}
if ($category !== '') {
    $found_where[] = "f.category = ?";
    $found_params[] = $category;
}
if ($status !== '') {
    $found_where[] = "f.status = ?";
    $found_params[] = $status;
}
$found_where_str = implode(' AND ', $found_where);

// Get categories for dropdown
$categories_q = dbQuery($conn, "SELECT DISTINCT category FROM lost_items UNION SELECT DISTINCT category FROM found_items ORDER BY category");

// Get items based on type
$lost_items = [];
$found_items = [];

if ($type == 'all' || $type == 'lost') {
    $lost_items = dbFetchAll($conn, "SELECT l.*, u.name AS user_name, u.email AS user_email, u.role AS user_role 
        FROM lost_items l 
        JOIN users u ON l.user_id = u.user_id 
        WHERE $lost_where_str 
        ORDER BY l.created_at DESC LIMIT 50", $lost_params);
}

if ($type == 'all' || $type == 'found') {
    $found_items = dbFetchAll($conn, "SELECT f.*, u.name AS user_name, u.email AS user_email, u.role AS user_role 
        FROM found_items f 
        JOIN users u ON f.user_id = u.user_id 
        WHERE $found_where_str 
        ORDER BY f.created_at DESC LIMIT 50", $found_params);
}

$pageTitle = 'Search Items - Smart Lost & Found';
?>
<!doctype html>
<html lang="en">
<head>
  <?php include __DIR__ . '/../components/head.php'; ?>
</head>
<body>
  <?php include __DIR__ . '/../components/navbar.php'; ?>

  <div class="container py-4">
    <h2 class="h4 mb-4">🔍 Search Campus Lost &amp; Found Items</h2>
    
    <!-- Search Form -->
    <div class="card mb-4 shadow-sm">
      <div class="card-body">
        <form method="get" action="search_items.php" class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Search Keyword</label>
            <input type="text" name="search" class="form-control" placeholder="Item name, description, location..." value="<?php echo cleanHtml($search); ?>">
          </div>
          <div class="col-md-2">
            <label class="form-label">Category</label>
            <select name="category" class="form-select" style="color: #212529; background-color: #fff;">
              <option value="" style="color: #6c757d;">All Categories</option>
              <?php 
              if ($categories_q) {
                while($cat = mysqli_fetch_assoc($categories_q)): ?>
                  <option value="<?php echo cleanHtml($cat['category']); ?>" <?php echo $category==$cat['category']?'selected':''; ?> style="color: #212529;">
                    <?php echo cleanHtml($cat['category']); ?>
                  </option>
                <?php endwhile;
              }
              ?>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label">Type</label>
            <select name="type" class="form-select" style="color: #212529; background-color: #fff;">
              <option value="all" <?php echo $type=='all'?'selected':''; ?>>All Items</option>
              <option value="lost" <?php echo $type=='lost'?'selected':''; ?>>Lost Only</option>
              <option value="found" <?php echo $type=='found'?'selected':''; ?>>Found Only</option>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label">Status</label>
            <select name="status" class="form-select" style="color: #212529; background-color: #fff;">
              <option value="" style="color: #6c757d;">All Status</option>
              <option value="Pending" <?php echo $status=='Pending'?'selected':''; ?>>Pending</option>
              <option value="Matched" <?php echo $status=='Matched'?'selected':''; ?>>Matched</option>
              <option value="Returned" <?php echo $status=='Returned'?'selected':''; ?>>Returned</option>
            </select>
          </div>
          <div class="col-md-2 d-flex align-items-end">
            <button type="submit" class="btn btn-primary w-100">Search</button>
          </div>
        </form>
        <?php if ($search || $category || $status || $type !== 'all'): ?>
        <div class="mt-2">
          <a href="<?php echo url('items/search_items.php'); ?>" class="btn btn-sm btn-outline-secondary">Clear Filters</a>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Results -->
    <?php if ($type == 'all' || $type == 'lost'): ?>
    <div class="card mb-4 shadow-sm">
      <div class="card-header bg-danger text-white fw-bold">🔴 Lost Items (<?php echo count($lost_items); ?>)</div>
      <div class="card-body">
        <?php if (count($lost_items) > 0): ?>
        <div class="row g-3">
          <?php foreach($lost_items as $item): ?>
          <div class="col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm">
              <?php if ($item['image']): ?>
              <img src="<?php echo asset($item['image']); ?>" class="card-img-top" style="height: 200px; object-fit: cover; cursor: pointer;" onclick="previewImage(this.src)" alt="<?php echo cleanHtml($item['item_name']); ?>">
              <?php endif; ?>
              <div class="card-body d-flex flex-column">
                <h6 class="card-title fw-bold"><?php echo cleanHtml($item['item_name']); ?></h6>
                <p class="card-text small text-muted mb-2">
                  <strong>Category:</strong> <?php echo cleanHtml($item['category']); ?><br>
                  <strong>Place:</strong> <?php echo cleanHtml($item['place_lost']); ?><br>
                  <strong>Date:</strong> <?php echo date('M d, Y', strtotime($item['date_lost'])); ?>
                </p>
                <?php if ($item['description']): ?>
                <p class="card-text small flex-grow-1"><?php echo cleanHtml(substr($item['description'], 0, 100)); ?><?php echo strlen($item['description']) > 100 ? '...' : ''; ?></p>
                <?php endif; ?>
                <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                  <span class="badge text-bg-<?php echo $item['status']==='Returned'?'success':($item['status']==='Matched'?'warning':'secondary'); ?>">
                    <?php echo $item['status']; ?>
                  </span>
                  <a href="<?php echo url('items/item_details.php?type=lost&id=' . $item['lost_id']); ?>" class="btn btn-sm btn-outline-primary">View Details</a>
                </div>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p class="text-muted text-center py-4 mb-0">No lost items found matching the criteria.</p>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <?php if ($type == 'all' || $type == 'found'): ?>
    <div class="card mb-4 shadow-sm">
      <div class="card-header bg-success text-white fw-bold">🟢 Found Items (<?php echo count($found_items); ?>)</div>
      <div class="card-body">
        <?php if (count($found_items) > 0): ?>
        <div class="row g-3">
          <?php foreach($found_items as $item): ?>
          <div class="col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm">
              <?php if ($item['image']): ?>
              <img src="<?php echo asset($item['image']); ?>" class="card-img-top" style="height: 200px; object-fit: cover; cursor: pointer;" onclick="previewImage(this.src)" alt="<?php echo cleanHtml($item['item_name']); ?>">
              <?php endif; ?>
              <div class="card-body d-flex flex-column">
                <h6 class="card-title fw-bold"><?php echo cleanHtml($item['item_name']); ?></h6>
                <p class="card-text small text-muted mb-2">
                  <strong>Category:</strong> <?php echo cleanHtml($item['category']); ?><br>
                  <strong>Place:</strong> <?php echo cleanHtml($item['place_found']); ?><br>
                  <strong>Date:</strong> <?php echo date('M d, Y', strtotime($item['date_found'])); ?>
                </p>
                <?php if ($item['description']): ?>
                <p class="card-text small flex-grow-1"><?php echo cleanHtml(substr($item['description'], 0, 100)); ?><?php echo strlen($item['description']) > 100 ? '...' : ''; ?></p>
                <?php endif; ?>
                <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                  <span class="badge text-bg-<?php echo $item['status']==='Returned'?'success':($item['status']==='Matched'?'warning':'secondary'); ?>">
                    <?php echo $item['status']; ?>
                  </span>
                  <a href="<?php echo url('items/item_details.php?type=found&id=' . $item['found_id']); ?>" class="btn btn-sm btn-outline-primary">View Details</a>
                </div>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p class="text-muted text-center py-4 mb-0">No found items found matching the criteria.</p>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <?php include __DIR__ . '/../components/footer.php'; ?>
