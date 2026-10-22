<?php
require_once __DIR__ . '/../config/config.php';
requireLogin();

$uid = currentUserId();
$role = currentUserRole();
$dashboardUrl = $role === 'staff' ? 'staff/dashboard.php' : 'student/dashboard.php';
$type = sanitize($conn, $_GET['type'] ?? '');
$id = (int)($_GET['id'] ?? 0);

if (!$type || !$id) {
    redirectWith($role === 'staff' ? 'staff/dashboard.php' : 'student/dashboard.php', ['msg' => 'Invalid item']);
}

$item = null;
$isOwner = false;

if ($type === 'lost') {
    $item = dbFetchOne($conn, "SELECT l.*, u.name AS user_name, u.email AS user_email, u.role AS user_role 
        FROM lost_items l 
        JOIN users u ON l.user_id = u.user_id 
        WHERE l.lost_id = ? LIMIT 1", [$id]);
    if ($item) {
        $isOwner = ((int)$item['user_id'] === (int)$uid);
    }
} elseif ($type === 'found') {
    $item = dbFetchOne($conn, "SELECT f.*, u.name AS user_name, u.email AS user_email, u.role AS user_role 
        FROM found_items f 
        JOIN users u ON f.user_id = u.user_id 
        WHERE f.found_id = ? LIMIT 1", [$id]);
    if ($item) {
        $isOwner = ((int)$item['user_id'] === (int)$uid);
    }
}

if (!$item) {
    redirectWith($role === 'staff' ? 'staff/dashboard.php' : 'student/dashboard.php', ['msg' => 'Item not found']);
}

$pageTitle = cleanHtml($item['item_name']) . ' - Item Details';
?>
<!doctype html>
<html lang="en">
<head>
  <?php include __DIR__ . '/../components/head.php'; ?>
</head>
<body>
  <?php include __DIR__ . '/../components/navbar.php'; ?>

  <div class="container py-4">
    <div class="row g-4">
      <div class="col-lg-8">
        <div class="card shadow-sm">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold"><?php echo $type === 'lost' ? '🔴' : '🟢'; ?> <?php echo ucfirst($type); ?> Item Details</h5>
            <span class="badge text-bg-<?php echo $item['status']==='Returned'?'success':($item['status']==='Matched'?'warning':'secondary'); ?>">
              <?php echo $item['status']; ?>
            </span>
          </div>
          <div class="card-body">
            <?php if ($item['image']): ?>
            <div class="mb-4 text-center">
              <img src="<?php echo asset($item['image']); ?>" class="img-fluid rounded shadow-sm" style="max-height: 400px; cursor: pointer;" onclick="previewImage(this.src)" alt="<?php echo cleanHtml($item['item_name']); ?>">
            </div>
            <?php endif; ?>
            
            <div class="table-responsive">
              <table class="table table-borderless align-middle mb-0">
                <tr>
                  <th style="width: 30%;">Item Name:</th>
                  <td><strong><?php echo cleanHtml($item['item_name']); ?></strong></td>
                </tr>
                <tr>
                  <th>Category:</th>
                  <td><?php echo cleanHtml($item['category']); ?></td>
                </tr>
                <tr>
                  <th><?php echo $type === 'lost' ? 'Place Lost:' : 'Place Found:'; ?></th>
                  <td><?php echo cleanHtml($type === 'lost' ? $item['place_lost'] : $item['place_found']); ?></td>
                </tr>
                <tr>
                  <th><?php echo $type === 'lost' ? 'Date Lost:' : 'Date Found:'; ?></th>
                  <td><?php echo date('F d, Y', strtotime($type === 'lost' ? $item['date_lost'] : $item['date_found'])); ?></td>
                </tr>
                <tr>
                  <th>Reported On:</th>
                  <td><?php echo date('F d, Y h:i A', strtotime($item['created_at'])); ?></td>
                </tr>
                <?php if ($item['description']): ?>
                <tr>
                  <th>Description:</th>
                  <td><?php echo nl2br(cleanHtml($item['description'])); ?></td>
                </tr>
                <?php endif; ?>
              </table>
            </div>
          </div>
        </div>
      </div>
      
      <div class="col-lg-4">
        <div class="card shadow-sm mb-3">
          <div class="card-header bg-primary text-white fw-bold">Reporter Information</div>
          <div class="card-body">
            <p class="mb-2"><strong>Name:</strong> <?php echo cleanHtml($item['user_name']); ?></p>
            <p class="mb-2"><strong>Role:</strong> <span class="badge text-bg-info"><?php echo ucfirst($item['user_role']); ?></span></p>
            <p class="mb-0"><strong>Email:</strong> <a href="mailto:<?php echo cleanHtml($item['user_email']); ?>"><?php echo cleanHtml($item['user_email']); ?></a></p>
          </div>
        </div>
        
        <?php if ($isOwner): ?>
        <div class="card shadow-sm">
          <div class="card-header bg-dark text-white fw-bold">Your Item</div>
          <div class="card-body">
            <?php if ($type === 'lost' && $item['status'] !== 'Returned'): ?>
            <a href="<?php echo url($dashboardUrl . '?return=' . $item['lost_id']); ?>" 
               class="btn btn-success w-100 mb-2" onclick="return confirmReturn()">Mark as Returned</a>
            <?php endif; ?>
            <a href="<?php echo url($dashboardUrl); ?>" class="btn btn-outline-secondary w-100">View in Dashboard</a>
          </div>
        </div>
        <?php else: ?>
        <div class="card shadow-sm">
          <div class="card-header bg-dark text-white fw-bold">Contact Reporter</div>
          <div class="card-body">
            <a href="mailto:<?php echo cleanHtml($item['user_email']); ?>?subject=Regarding%20<?php echo urlencode($item['item_name']); ?>%20on%20Smart%20Lost%20and%20Found" 
               class="btn btn-primary w-100">📧 Contact Reporter</a>
          </div>
        </div>
        <?php endif; ?>

        <div class="mt-3">
          <a href="<?php echo url('items/search_items.php'); ?>" class="btn btn-outline-secondary w-100">← Back to Search</a>
        </div>
      </div>
    </div>
  </div>

  <?php include __DIR__ . '/../components/footer.php'; ?>
