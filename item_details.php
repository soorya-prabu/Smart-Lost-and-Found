<?php include 'config.php'; requireLogin();
$uid = currentUserId();
$role = currentUserRole();
$type = sanitize($conn, $_GET['type'] ?? '');
$id = (int)($_GET['id'] ?? 0);

if (!$type || !$id) {
    redirectWith($role === 'staff' ? 'staff_dashboard.php' : 'student_dashboard.php', ['msg' => 'Invalid item']);
}

$item = null;
$user = null;
$isOwner = false;

if ($type == 'lost') {
    $item_q = mysqli_query($conn, "SELECT l.*, u.name AS user_name, u.email AS user_email, u.role AS user_role 
        FROM lost_items l 
        JOIN users u ON l.user_id = u.user_id 
        WHERE l.lost_id=$id LIMIT 1");
    if ($item_q && mysqli_num_rows($item_q) > 0) {
        $item = mysqli_fetch_assoc($item_q);
        $isOwner = ($item['user_id'] == $uid);
    }
} else if ($type == 'found') {
    $item_q = mysqli_query($conn, "SELECT f.*, u.name AS user_name, u.email AS user_email, u.role AS user_role 
        FROM found_items f 
        JOIN users u ON f.user_id = u.user_id 
        WHERE f.found_id=$id LIMIT 1");
    if ($item_q && mysqli_num_rows($item_q) > 0) {
        $item = mysqli_fetch_assoc($item_q);
        $isOwner = ($item['user_id'] == $uid);
    }
}

if (!$item) {
    redirectWith($role === 'staff' ? 'staff_dashboard.php' : 'student_dashboard.php', ['msg' => 'Item not found']);
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Item Details - Smart Lost & Found</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom">
    <div class="container-fluid">
      <a class="navbar-brand" href="index.html">Smart Lost & Found</a>
      <div class="d-flex nav-gap">
        <a class="btn btn-outline-secondary" href="search_items.php">Back to Search</a>
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
    <div class="row">
      <div class="col-lg-8">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><?php echo $type == 'lost' ? '🔴' : '🟢'; ?> <?php echo ucfirst($type); ?> Item Details</h5>
            <span class="badge text-bg-<?php echo $item['status']==='Returned'?'success':($item['status']==='Matched'?'warning':'secondary'); ?>">
              <?php echo $item['status']; ?>
            </span>
          </div>
          <div class="card-body">
            <?php if ($item['image']): ?>
            <div class="mb-4 text-center">
              <img src="<?php echo htmlspecialchars($item['image']); ?>" class="img-fluid rounded" style="max-height: 400px; cursor: pointer;" onclick="previewImage(this.src)" alt="<?php echo htmlspecialchars($item['item_name']); ?>">
            </div>
            <?php endif; ?>
            
            <table class="table table-borderless">
              <tr>
                <th width="30%">Item Name:</th>
                <td><strong><?php echo htmlspecialchars($item['item_name']); ?></strong></td>
              </tr>
              <tr>
                <th>Category:</th>
                <td><?php echo htmlspecialchars($item['category']); ?></td>
              </tr>
              <tr>
                <th><?php echo $type == 'lost' ? 'Place Lost:' : 'Place Found:'; ?></th>
                <td><?php echo htmlspecialchars($type == 'lost' ? $item['place_lost'] : $item['place_found']); ?></td>
              </tr>
              <tr>
                <th><?php echo $type == 'lost' ? 'Date Lost:' : 'Date Found:'; ?></th>
                <td><?php echo date('F d, Y', strtotime($type == 'lost' ? $item['date_lost'] : $item['date_found'])); ?></td>
              </tr>
              <tr>
                <th>Reported On:</th>
                <td><?php echo date('F d, Y h:i A', strtotime($item['created_at'])); ?></td>
              </tr>
              <?php if ($item['description']): ?>
              <tr>
                <th>Description:</th>
                <td><?php echo nl2br(htmlspecialchars($item['description'])); ?></td>
              </tr>
              <?php endif; ?>
            </table>
          </div>
        </div>
      </div>
      
      <div class="col-lg-4">
        <div class="card mb-3">
          <div class="card-header">Reporter Information</div>
          <div class="card-body">
            <p class="mb-2"><strong>Name:</strong> <?php echo htmlspecialchars($item['user_name']); ?></p>
            <p class="mb-2"><strong>Role:</strong> <span class="badge text-bg-info"><?php echo ucfirst($item['user_role']); ?></span></p>
            <p class="mb-0"><strong>Email:</strong> <a href="mailto:<?php echo htmlspecialchars($item['user_email']); ?>"><?php echo htmlspecialchars($item['user_email']); ?></a></p>
          </div>
        </div>
        
        <?php if ($isOwner): ?>
        <div class="card">
          <div class="card-header">Your Item</div>
          <div class="card-body">
            <?php if ($type == 'lost' && $item['status'] !== 'Returned'): ?>
            <a href="<?php echo $role === 'staff' ? 'staff' : 'student'; ?>_dashboard.php?return=<?php echo $item['lost_id']; ?>" 
               class="btn btn-success w-100 mb-2" onclick="return confirmReturn()">Mark as Returned</a>
            <?php endif; ?>
            <a href="<?php echo $role === 'staff' ? 'staff' : 'student'; ?>_dashboard.php" class="btn btn-outline-secondary w-100">View in Dashboard</a>
          </div>
        </div>
        <?php else: ?>
        <div class="card">
          <div class="card-header">Contact Reporter</div>
          <div class="card-body">
            <a href="mailto:<?php echo htmlspecialchars($item['user_email']); ?>?subject=Regarding <?php echo urlencode($item['item_name']); ?>" 
               class="btn btn-primary w-100">📧 Contact Reporter</a>
          </div>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <script src="assets/js/app.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

