<?php
/**
 * Shared User Navigation Bar Component (Student & Staff)
 */
$currentUid = currentUserId();
$currentRole = currentUserRole();
$activeNav = $activeNav ?? '';

$notifCount = 0;
if ($currentUid && isset($conn) && $conn) {
    $notifCount = dbCount($conn, "SELECT COUNT(*) FROM matches m 
        JOIN lost_items l ON m.lost_id = l.lost_id 
        JOIN found_items f ON m.found_id = f.found_id 
        WHERE (l.user_id = ? OR f.user_id = ?) AND m.status <> 'Resolved'", [(int)$currentUid, (int)$currentUid]);
}
?>
<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom shadow-sm">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="<?php echo url('index.php'); ?>">
      🔍 Smart Lost &amp; Found
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#userNavbar" aria-controls="userNavbar" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="userNavbar">
      <ul class="navbar-nav ms-auto align-items-lg-center gap-2 mt-3 mt-lg-0">
        <?php if ($currentRole === 'staff'): ?>
          <li class="nav-item">
            <a class="nav-link px-2 <?php echo $activeNav==='dashboard'?'active fw-bold text-primary':''; ?>" href="<?php echo url('staff/dashboard.php'); ?>">📊 Dashboard</a>
          </li>
        <?php elseif ($currentRole === 'student'): ?>
          <li class="nav-item">
            <a class="nav-link px-2 <?php echo $activeNav==='dashboard'?'active fw-bold text-primary':''; ?>" href="<?php echo url('student/dashboard.php'); ?>">🎓 Dashboard</a>
          </li>
        <?php endif; ?>

        <li class="nav-item">
          <a class="btn btn-outline-primary btn-sm w-100 text-lg-center" href="<?php echo url('items/report_lost.php'); ?>">🔴 Report Lost</a>
        </li>
        <li class="nav-item">
          <a class="btn btn-outline-success btn-sm w-100 text-lg-center" href="<?php echo url('items/report_found.php'); ?>">🟢 Report Found</a>
        </li>
        <li class="nav-item">
          <a class="btn btn-outline-info btn-sm w-100 text-lg-center" href="<?php echo url('items/search_items.php'); ?>">🔍 Search</a>
        </li>
        <li class="nav-item">
          <a class="btn btn-outline-secondary btn-sm w-100 d-flex justify-content-between justify-content-lg-center align-items-center gap-1" href="<?php echo url('items/match_notification.php'); ?>">
            <span>🔔 Notifications</span>
            <?php if ($notifCount > 0): ?>
            <span class="badge rounded-pill bg-danger">
              <?php echo $notifCount; ?>
            </span>
            <?php endif; ?>
          </a>
        </li>
        <li class="nav-item">
          <a class="btn btn-outline-dark btn-sm w-100 text-lg-center" href="<?php echo url('items/feedback.php'); ?>">💬 Feedback</a>
        </li>
        <li class="nav-item mt-2 mt-lg-0">
          <a class="btn btn-danger btn-sm w-100" href="<?php echo url('auth/logout.php'); ?>">🚪 Logout</a>
        </li>
      </ul>
    </div>
  </div>
</nav>
