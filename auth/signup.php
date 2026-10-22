<?php
require_once __DIR__ . '/../config/config.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = trim($_POST['role'] ?? 'student');

    if ($name && $email && $password && in_array($role, ['student', 'staff'])) {
        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please provide a valid email address.';
        } else {
            // Check if email already exists using prepared statement
            $existing = dbFetchOne($conn, "SELECT user_id FROM users WHERE email = ? LIMIT 1", [$email]);
            if ($existing) {
                $error = 'An account with this email address already exists.';
            } else {
                // Securely hash with Bcrypt
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $newId = dbInsert($conn, "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)", [$name, $email, $hash, $role]);
                
                if ($newId) {
                    logActivity($conn, "User Registered", "user", $newId, "New $role account registered: $email");
                    redirectWith('index.php', ['msg' => 'Signup successful! Please login with your credentials.']);
                } else {
                    $error = 'Registration failed. Please try again.';
                }
            }
        }
    } else {
        $error = 'Please fill all required fields correctly.';
    }
}

$pageTitle = 'Sign Up - Smart Lost & Found';
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
      <h1 class="h4 mb-3">Create Account</h1>
      <?php if (!empty($error)): ?>
        <div class="alert alert-danger auto-fade py-2"><?php echo cleanHtml($error); ?></div>
      <?php endif; ?>
      <form method="post" action="signup.php">
        <div class="row g-3">
          <div class="col-12">
            <label class="form-label">Full Name</label>
            <input type="text" name="name" class="form-control" placeholder="e.g. John Doe" value="<?php echo cleanHtml($_POST['name'] ?? ''); ?>" required>
          </div>
          <div class="col-12">
            <label class="form-label">Email Address</label>
            <input type="email" name="email" class="form-control" placeholder="e.g. john@student.kec.edu" value="<?php echo cleanHtml($_POST['email'] ?? ''); ?>" required>
          </div>
          <div class="col-12">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" placeholder="Choose a secure password" required>
          </div>
          <div class="col-12">
            <label class="form-label">Role</label>
            <select name="role" class="form-select">
              <option value="student" <?php echo (($_POST['role'] ?? '') === 'student') ? 'selected' : ''; ?>>Student</option>
              <option value="staff" <?php echo (($_POST['role'] ?? '') === 'staff') ? 'selected' : ''; ?>>Staff</option>
            </select>
          </div>
          <div class="col-12 d-flex justify-content-between align-items-center">
            <a href="<?php echo url('index.php'); ?>" class="btn btn-outline-secondary">Back</a>
            <button class="btn btn-primary" type="submit">Sign Up</button>
          </div>
        </div>
      </form>
      <div class="mt-3 small">Already have an account? <a href="<?php echo url('auth/login_student.php'); ?>">Login as Student</a> or <a href="<?php echo url('auth/login_staff.php'); ?>">Staff</a></div>
    </div>
  </div>
  <?php include __DIR__ . '/../components/footer.php'; ?>
