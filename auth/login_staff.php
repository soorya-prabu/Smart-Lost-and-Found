<?php
require_once __DIR__ . '/../config/config.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Secure prepared statement
    $u = dbFetchOne($conn, "SELECT user_id, name, password, role FROM users WHERE email = ? AND role = 'staff' LIMIT 1", [$email]);
    if ($u) {
        $needsRehash = false;
        if (verifyPassword($password, $u['password'], $needsRehash)) {
            // Transparently upgrade legacy hash to Bcrypt
            if ($needsRehash) {
                migratePasswordToBcrypt($conn, $u['user_id'], $password);
                logActivity($conn, "Password Upgraded to Bcrypt", "user", $u['user_id'], "Transparently migrated password hash to Bcrypt");
            }

            $_SESSION['user_id'] = $u['user_id'];
            $_SESSION['name'] = $u['name'];
            $_SESSION['role'] = $u['role'];
            redirectWith('staff/dashboard.php');
        } else {
            $error = 'Invalid credentials for staff.';
        }
    } else {
        $error = 'Invalid credentials for staff.';
    }
}

$pageTitle = 'Staff Login - Smart Lost & Found';
$extraCss = ['assets/css/auth.css'];
?>
<!doctype html>
<html lang="en">
<head>
  <?php include __DIR__ . '/../components/head.php'; ?>
</head>
<body>
  <div class="container py-5">
    <div class="center-card p-4">
      <h1 class="h4 mb-3">👨‍🏫 Staff Login</h1>
      <?php if (!empty($error)): ?>
        <div class="alert alert-danger auto-fade py-2"><?php echo cleanHtml($error); ?></div>
      <?php endif; ?>
      <form method="post" action="login_staff.php">
        <div class="mb-3">
          <label class="form-label">Email</label>
          <input type="email" name="email" class="form-control" placeholder="staff_email@kec.edu" value="<?php echo cleanHtml($_POST['email'] ?? ''); ?>" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Password</label>
          <input type="password" name="password" class="form-control" placeholder="••••••••" required>
        </div>
        <div class="d-flex justify-content-between align-items-center">
          <a href="<?php echo url('index.php'); ?>" class="btn btn-outline-secondary">Back</a>
          <button class="btn btn-success" type="submit">Login</button>
        </div>
      </form>
      <div class="mt-3 small">Need an account? <a href="<?php echo url('auth/signup.php'); ?>">Sign Up</a></div>
    </div>
  </div>
  <?php include __DIR__ . '/../components/footer.php'; ?>
