<?php
/**
 * Shared Admin Navigation Bar Component
 */
$activeAdminNav = $activeAdminNav ?? '';
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="<?php echo url('admin/dashboard.php'); ?>">
      🏛️ Admin Panel - College Management
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar" aria-controls="adminNavbar" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="adminNavbar">
      <ul class="navbar-nav ms-auto align-items-lg-center gap-2 mt-3 mt-lg-0">
        <li class="nav-item">
          <a class="btn btn-outline-light btn-sm w-100 text-lg-center <?php echo $activeAdminNav==='dashboard'?'active':''; ?>" href="<?php echo url('admin/dashboard.php'); ?>">📊 Dashboard</a>
        </li>
        <li class="nav-item">
          <a class="btn btn-outline-light btn-sm w-100 text-lg-center <?php echo $activeAdminNav==='items'?'active':''; ?>" href="<?php echo url('admin/items.php'); ?>">📦 Manage Items</a>
        </li>
        <li class="nav-item">
          <a class="btn btn-outline-light btn-sm w-100 text-lg-center <?php echo $activeAdminNav==='users'?'active':''; ?>" href="<?php echo url('admin/users.php'); ?>">👥 Manage Users</a>
        </li>
        <li class="nav-item">
          <a class="btn btn-outline-light btn-sm w-100 text-lg-center <?php echo $activeAdminNav==='matches'?'active':''; ?>" href="<?php echo url('admin/matches.php'); ?>">🤝 Matches</a>
        </li>
        <li class="nav-item">
          <a class="btn btn-outline-light btn-sm w-100 text-lg-center <?php echo $activeAdminNav==='activity'?'active':''; ?>" href="<?php echo url('admin/activity.php'); ?>">📋 Activity Log</a>
        </li>
        <li class="nav-item">
          <a class="btn btn-outline-light btn-sm w-100 text-lg-center <?php echo $activeAdminNav==='reports'?'active':''; ?>" href="<?php echo url('admin/reports.php'); ?>">📈 Reports</a>
        </li>
        <li class="nav-item mt-2 mt-lg-0">
          <a class="btn btn-danger btn-sm w-100" href="<?php echo url('auth/logout.php'); ?>">🚪 Logout</a>
        </li>
      </ul>
    </div>
  </div>
</nav>
