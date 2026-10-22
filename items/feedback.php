<?php
require_once __DIR__ . '/../config/config.php';
requireLogin();

$ok = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $msg = trim($_POST['message'] ?? '');
    if ($msg !== '') {
        $uid = currentUserId();
        $fid = dbInsert($conn, "INSERT INTO feedback (user_id, message) VALUES (?, ?)", [$uid, $msg]);
        if ($fid) {
            logActivity($conn, "Submitted Feedback", "feedback", $fid);
            $ok = 'Thank you for your feedback! It helps improve our college lost & found system.';
        } else {
            $error = 'Failed to submit feedback. Please try again.';
        }
    } else {
        $error = 'Please enter a message.';
    }
}

$recent = dbQuery($conn, "SELECT f.message, f.date, u.name, u.role 
    FROM feedback f 
    JOIN users u ON u.user_id = f.user_id 
    ORDER BY f.date DESC LIMIT 15");

$pageTitle = 'Feedback - Smart Lost & Found';
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
    <div class="row justify-content-center">
      <div class="col-lg-8">
        <h2 class="h4 mb-3">💬 Campus Community Feedback</h2>
        <p class="text-muted small mb-4">Share your thoughts, suggestions, or recovery experiences with the campus community.</p>

        <?php if (!empty($ok)): ?>
          <div class="alert alert-success auto-fade py-2"><?php echo cleanHtml($ok); ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
          <div class="alert alert-danger auto-fade py-2"><?php echo cleanHtml($error); ?></div>
        <?php endif; ?>

        <div class="card shadow-sm mb-4">
          <div class="card-body">
            <form method="post" action="feedback.php">
              <label class="form-label fw-bold">Leave your feedback</label>
              <div class="d-flex flex-column flex-sm-row gap-2">
                <input class="form-control" name="message" placeholder="Share your experience or suggestions..." required>
                <button class="btn btn-primary text-nowrap" type="submit">Submit Feedback</button>
              </div>
            </form>
          </div>
        </div>

        <div class="card shadow-sm">
          <div class="card-header bg-dark text-white fw-bold">Recent Feedback</div>
          <div class="card-body p-0">
            <ul class="list-group list-group-flush">
              <?php if ($recent && mysqli_num_rows($recent) > 0): ?>
                <?php while($r = mysqli_fetch_assoc($recent)): ?>
                <li class="list-group-item">
                  <div class="d-flex justify-content-between align-items-start mb-1">
                    <div>
                      <strong><?php echo cleanHtml($r['name']); ?></strong> 
                      <span class="badge text-bg-secondary ms-1"><?php echo ucfirst($r['role']); ?></span>
                    </div>
                    <span class="text-muted small"><?php echo date('M d, Y h:i A', strtotime($r['date'])); ?></span>
                  </div>
                  <div class="text-secondary"><?php echo cleanHtml($r['message']); ?></div>
                </li>
                <?php endwhile; ?>
              <?php else: ?>
                <li class="list-group-item text-center text-muted py-4">No feedback entries yet. Be the first to share!</li>
              <?php endif; ?>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>

  <?php include __DIR__ . '/../components/footer.php'; ?>
