<?php
require_once __DIR__ . '/../config/config.php';
requireLogin();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_name = sanitize($conn, $_POST['item_name'] ?? '');
    $category = sanitize($conn, $_POST['category'] ?? '');
    $place_lost = sanitize($conn, $_POST['place_lost'] ?? '');
    $date_lost = sanitize($conn, $_POST['date_lost'] ?? '');
    $description = sanitize($conn, $_POST['description'] ?? '');
    $image = handleImageUpload('image', 'assets/uploads/');

    if ($item_name && $category && $place_lost && $date_lost) {
        $uid = currentUserId();
        $sql = "INSERT INTO lost_items (user_id, item_name, category, place_lost, date_lost, description, image) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $lostId = dbInsert($conn, $sql, [$uid, $item_name, $category, $place_lost, $date_lost, $description, $image]);
        if ($lostId) {
            logActivity($conn, "Reported Lost Item", "lost", $lostId, "Item: $item_name");
            
            // Execute automated matching
            autoMatchItem($conn, $lostId, 'lost');

            $redirectPage = (currentUserRole() === 'staff') ? 'staff/dashboard.php' : 'student/dashboard.php';
            redirectWith($redirectPage, ['msg' => 'Lost item reported successfully. Matching in progress.']);
        } else {
            $error = 'Failed to submit report. Please try again.';
        }
    } else {
        $error = 'Please fill all required fields.';
    }
}

$pageTitle = 'Report Lost Item - Smart Lost & Found';
$extraCss = ['assets/css/forms.css'];
?>
<!doctype html>
<html lang="en">
<head>
  <?php include __DIR__ . '/../components/head.php'; ?>
</head>
<body>
  <?php include __DIR__ . '/../components/navbar.php'; ?>

  <div class="container py-4">
    <div class="center-card p-4">
      <h1 class="h4 mb-3">🔍 Report Lost Item</h1>
      <?php if (!empty($error)): ?>
        <div class="alert alert-danger auto-fade py-2"><?php echo cleanHtml($error); ?></div>
      <?php endif; ?>
      <form method="post" action="report_lost.php" enctype="multipart/form-data">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Item Name</label>
            <input name="item_name" class="form-control" placeholder="e.g. Blue Dell Laptop Charger" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Category</label>
            <select name="category" class="form-select" required style="color: #212529; background-color: #fff;">
              <option value="" style="color: #6c757d;">Select Category</option>
              <option value="Electronics" style="color: #212529;">Electronics</option>
              <option value="Accessories" style="color: #212529;">Accessories</option>
              <option value="Clothing" style="color: #212529;">Clothing</option>
              <option value="Books" style="color: #212529;">Books</option>
              <option value="Documents" style="color: #212529;">Documents</option>
              <option value="Keys" style="color: #212529;">Keys</option>
              <option value="Wallet/Purse" style="color: #212529;">Wallet/Purse</option>
              <option value="Jewelry" style="color: #212529;">Jewelry</option>
              <option value="Sports Equipment" style="color: #212529;">Sports Equipment</option>
              <option value="Other" style="color: #212529;">Other</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Place Lost</label>
            <input name="place_lost" class="form-control" placeholder="e.g. IT Park 2nd Floor / Central Library" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Date Lost</label>
            <input type="date" name="date_lost" class="form-control" max="<?php echo date('Y-m-d'); ?>" required>
          </div>
          <div class="col-12">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="3" placeholder="Provide any unique marks, serial numbers, colors, or distinctive characteristics..."></textarea>
          </div>
          <div class="col-12">
            <label class="form-label fw-semibold">Item Photo (Optional)</label>
            <input type="file" name="image" id="itemImageInput" accept="image/jpeg,image/png,image/gif,image/webp" class="form-control">
            <div class="form-text">Formats: JPG, PNG, GIF, WEBP (Max 10MB)</div>
            <div id="imagePreviewContainer" class="mt-3 d-none">
              <div class="card p-2 border shadow-sm d-inline-block bg-light" style="max-width: 260px;">
                <div class="position-relative">
                  <img id="imagePreviewThumb" src="" alt="Upload Preview" class="img-fluid rounded border" style="max-height: 180px; width: 100%; object-fit: cover;">
                  <button type="button" id="clearImageBtn" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1 rounded-circle" style="width: 28px; height: 28px; padding: 0; line-height: 1;" title="Remove image">&times;</button>
                </div>
                <div id="imagePreviewName" class="small text-truncate mt-1 text-muted text-center"></div>
              </div>
            </div>
          </div>
          <div class="col-12 d-flex flex-column flex-sm-row justify-content-between gap-2 mt-4">
            <?php 
            $backPage = (currentUserRole() === 'staff') ? 'staff/dashboard.php' : 'student/dashboard.php';
            ?>
            <a href="<?php echo url($backPage); ?>" class="btn btn-outline-secondary order-2 order-sm-1">Back to Dashboard</a>
            <button class="btn btn-primary order-1 order-sm-2" type="submit">Submit Report</button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <?php include __DIR__ . '/../components/footer.php'; ?>
