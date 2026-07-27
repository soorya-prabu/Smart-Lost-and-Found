<?php include 'config.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($conn, $_POST['name'] ?? '');
    $email = sanitize($conn, $_POST['email'] ?? '');
    $password = sanitize($conn, $_POST['password'] ?? '');
    $role = sanitize($conn, $_POST['role'] ?? 'student');
    if ($name && $email && $password && in_array($role, ['student','staff'])) {
        $hash = md5($password);
        $sql = "INSERT INTO users (name,email,password,role) VALUES ('$name','$email','$hash','$role')";
        if (mysqli_query($conn, $sql)) {
            redirectWith('index.html', ['msg' => 'Signup successful. Please login.']);
        } else {
            $error = 'Email already exists or invalid data.';
        }
    } else {
        $error = 'Please fill all fields correctly.';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sign Up - Smart Lost & Found</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body>
  <div class="container py-5">
    <div class="center-card p-4">
      <h1 class="h4 mb-3">Create Account</h1>
      <?php if (!empty($error)): ?>
      <div class="alert alert-danger auto-fade py-2"><?php echo $error; ?></div>
      <?php endif; ?>
      <form method="post">
        <div class="row g-3">
          <div class="col-12">
            <label class="form-label">Name</label>
            <input type="text" name="name" class="form-control" required>
          </div>
          <div class="col-12">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" required>
          </div>
          <div class="col-12">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required>
          </div>
          <div class="col-12">
            <label class="form-label">Role</label>
            <select name="role" class="form-select">
              <option value="student">Student</option>
              <option value="staff">Staff</option>
            </select>
          </div>
          <div class="col-12 d-flex justify-content-between align-items-center">
            <a href="index.html" class="btn btn-outline-secondary">Back</a>
            <button class="btn btn-primary" type="submit">Sign Up</button>
          </div>
        </div>
      </form>
      <div class="mt-3 small">Already have an account? <a href="login_student.php">Login</a></div>
    </div>
  </div>
  <script src="assets/js/app.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  </body></n></html>

