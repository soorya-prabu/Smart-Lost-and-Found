<?php include 'config.php'; requireLogin();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $msg = sanitize($conn, $_POST['message'] ?? '');
    if ($msg) {
        $uid = currentUserId();
        mysqli_query($conn, "INSERT INTO feedback (user_id,message) VALUES ($uid,'$msg')");
        $ok = 'Thanks for your feedback!';
    } else {
        $error = 'Please enter a message.';
    }
}
$recent = mysqli_query($conn, "SELECT f.message, f.date, u.name FROM feedback f JOIN users u ON u.user_id=f.user_id ORDER BY f.date DESC LIMIT 10");
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Feedback</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/forms.css">
</head>
<body>
  <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom">
    <div class="container-fluid">
      <a class="navbar-brand" href="index.html">Smart Lost & Found</a>
      <div class="d-flex nav-gap">
        <?php if (currentUserRole()==='staff'): ?>
          <a class="btn btn-outline-secondary" href="staff_dashboard.php">Back</a>
        <?php else: ?>
          <a class="btn btn-outline-secondary" href="student_dashboard.php">Back</a>
        <?php endif; ?>
        <a class="btn btn-danger" href="logout.php">Logout</a>
      </div>
    </div>
  </nav>
  <div class="container py-4">
    <h2 class="h5 mb-3">Feedback</h2>
    <?php if (!empty($ok)): ?><div class="alert alert-success auto-fade py-2"><?php echo $ok; ?></div><?php endif; ?>
    <?php if (!empty($error)): ?><div class="alert alert-danger auto-fade py-2"><?php echo $error; ?></div><?php endif; ?>
    <form method="post" class="mb-4">
      <div class="input-group">
        <input class="form-control" name="message" placeholder="Share your experience..." required>
        <button class="btn btn-primary" type="submit">Submit</button>
      </div>
    </form>
    <div class="card">
      <div class="card-header">Recent Feedback</div>
      <ul class="list-group list-group-flush">
        <?php while($r=$recent && mysqli_fetch_assoc($recent)): ?>
        <li class="list-group-item">
          <div><strong><?php echo htmlspecialchars($r['name']); ?>:</strong> <?php echo htmlspecialchars($r['message']); ?></div>
          <div class="text-muted small"><?php echo htmlspecialchars($r['date']); ?></div>
        </li>
        <?php endwhile; ?>
      </ul>
    </div>
  </div>
  <script src="assets/js/app.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

