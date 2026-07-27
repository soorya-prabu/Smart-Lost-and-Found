<?php include 'config.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($conn, $_POST['email'] ?? '');
    $password = sanitize($conn, $_POST['password'] ?? '');
    $hash = md5($password);
    $q = mysqli_query($conn, "SELECT user_id,name,role FROM users WHERE email='$email' AND password='$hash' AND role='admin' LIMIT 1");
    if ($q && mysqli_num_rows($q) === 1) {
        $u = mysqli_fetch_assoc($q);
        $_SESSION['user_id'] = $u['user_id'];
        $_SESSION['name'] = $u['name'];
        $_SESSION['role'] = $u['role'];
        
        // Log admin login
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        mysqli_query($conn, "INSERT INTO activity_log (user_id, user_role, action, ip_address) VALUES ({$u['user_id']}, 'admin', 'Admin Login', '$ip')");
        
        redirectWith('admin_dashboard.php');
    } else {
        $error = 'Invalid admin credentials.';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin Login - Smart Lost & Found</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body>
  <div class="container py-5">
    <div class="center-card p-4">
      <h1 class="h4 mb-3">🔐 Admin Login - College Management</h1>
      <?php if (!empty($error)): ?>
      <div class="alert alert-danger auto-fade py-2"><?php echo $error; ?></div>
      <?php endif; ?>
      <form method="post">
        <div class="mb-3">
          <label class="form-label">Email</label>
          <input type="email" name="email" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Password</label>
          <input type="password" name="password" class="form-control" required>
        </div>
        <div class="d-flex justify-content-between align-items-center">
          <a href="index.html" class="btn btn-outline-secondary">Back</a>
          <button class="btn btn-danger" type="submit">Login as Admin</button>
        </div>
      </form>
      <div class="mt-3 small text-muted">Default: admin@kec.edu / admin123</div>
    </div>
  </div>
  <script src="assets/js/app.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

