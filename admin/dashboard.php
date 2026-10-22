<?php
require_once __DIR__ . '/../config/config.php';
requireRole('admin');

$uid = currentUserId();

// Statistics
$tot_users = dbCount($conn, "SELECT COUNT(*) FROM users");
$tot_students = dbCount($conn, "SELECT COUNT(*) FROM users WHERE role = 'student'");
$tot_staff = dbCount($conn, "SELECT COUNT(*) FROM users WHERE role = 'staff'");
$tot_lost = dbCount($conn, "SELECT COUNT(*) FROM lost_items");
$tot_found = dbCount($conn, "SELECT COUNT(*) FROM found_items");
$tot_matches = dbCount($conn, "SELECT COUNT(*) FROM matches");
$tot_returned = dbCount($conn, "SELECT COUNT(*) FROM lost_items WHERE status = 'Returned'")
               + dbCount($conn, "SELECT COUNT(*) FROM found_items WHERE status = 'Returned'");
$tot_pending = dbCount($conn, "SELECT COUNT(*) FROM lost_items WHERE status = 'Pending'")
              + dbCount($conn, "SELECT COUNT(*) FROM found_items WHERE status = 'Pending'");

$pending_lost_count = dbCount($conn, "SELECT COUNT(*) FROM lost_items WHERE status = 'Pending'");
$pending_found_count = dbCount($conn, "SELECT COUNT(*) FROM found_items WHERE status = 'Pending'");
$pending_matches_count = dbCount($conn, "SELECT COUNT(*) FROM matches WHERE status = 'Pending'");

// Recent activities
$recent_activities = dbQuery($conn, "SELECT a.*, u.name AS user_name 
    FROM activity_log a 
    LEFT JOIN users u ON a.user_id = u.user_id 
    ORDER BY a.created_at DESC LIMIT 10");

// Recent items
$recent_lost = dbQuery($conn, "SELECT l.*, u.name AS reporter_name 
    FROM lost_items l 
    JOIN users u ON l.user_id = u.user_id 
    ORDER BY l.created_at DESC LIMIT 5");
$recent_found = dbQuery($conn, "SELECT f.*, u.name AS reporter_name 
    FROM found_items f 
    JOIN users u ON f.user_id = u.user_id 
    ORDER BY f.created_at DESC LIMIT 5");

$pageTitle = 'Admin Dashboard - College Management';
$activeAdminNav = 'dashboard';
?>
<!doctype html>
<html lang="en">
<head>
  <?php include __DIR__ . '/../components/head.php'; ?>
  <style>
    .stat-card { transition: transform 0.2s; }
    .stat-card:hover { transform: translateY(-5px); }
  </style>
</head>
<body>
  <?php include __DIR__ . '/../components/admin_navbar.php'; ?>

  <div class="container-fluid py-4">
    <?php if ($msg = flashMessage()): ?>
      <div class="alert alert-success auto-fade py-2"><?php echo $msg; ?></div>
    <?php endif; ?>
    
    <h2 class="h4 mb-4">Welcome, <?php echo cleanHtml($_SESSION['name'] ?? 'Admin'); ?> - System Overview</h2>
    
    <!-- Statistics Cards -->
    <div class="row g-3 mb-4">
      <div class="col-md-3">
        <div class="card stat-card text-center border-primary shadow-sm">
          <div class="card-body">
            <div class="text-muted small">Total Users</div>
            <div class="h3 mb-0 text-primary"><?php echo $tot_users; ?></div>
            <div class="small text-muted mt-1"><?php echo $tot_students; ?> Students, <?php echo $tot_staff; ?> Staff</div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card stat-card text-center border-danger shadow-sm">
          <div class="card-body">
            <div class="text-muted small">Lost Items</div>
            <div class="h3 mb-0 text-danger"><?php echo $tot_lost; ?></div>
            <div class="small text-muted mt-1"><?php echo $pending_lost_count; ?> Pending</div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card stat-card text-center border-success shadow-sm">
          <div class="card-body">
            <div class="text-muted small">Found Items</div>
            <div class="h3 mb-0 text-success"><?php echo $tot_found; ?></div>
            <div class="small text-muted mt-1"><?php echo $pending_found_count; ?> Pending</div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card stat-card text-center border-warning shadow-sm">
          <div class="card-body">
            <div class="text-muted small">Total Matches</div>
            <div class="h3 mb-0 text-warning"><?php echo $tot_matches; ?></div>
            <div class="small text-muted mt-1"><?php echo $pending_matches_count; ?> Pending</div>
          </div>
        </div>
      </div>
    </div>

    <div class="row g-3 mb-4">
      <div class="col-md-3">
        <div class="card stat-card text-center border-info shadow-sm">
          <div class="card-body">
            <div class="text-muted small">Returned Items</div>
            <div class="h3 mb-0 text-info"><?php echo $tot_returned; ?></div>
            <div class="small text-success mt-1">✓ Successfully Recovered</div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card stat-card text-center border-secondary shadow-sm">
          <div class="card-body">
            <div class="text-muted small">Pending Items</div>
            <div class="h3 mb-0 text-secondary"><?php echo $tot_pending; ?></div>
            <div class="small text-warning mt-1">Awaiting Match/Return</div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card stat-card text-center border-dark shadow-sm">
          <div class="card-body">
            <div class="text-muted small">Success Rate</div>
            <div class="h3 mb-0 text-dark">
              <?php 
              $total_items = $tot_lost + $tot_found;
              $success_rate = $total_items > 0 ? round(($tot_returned / $total_items) * 100, 1) : 0;
              echo $success_rate;
              ?>%
            </div>
            <div class="small text-muted mt-1">Recovery Rate</div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card stat-card text-center border-primary shadow-sm">
          <div class="card-body">
            <div class="text-muted small">Match Rate</div>
            <div class="h3 mb-0 text-primary">
              <?php 
              $match_rate = $total_items > 0 ? round(($tot_matches / max(1, ($total_items / 2))) * 100, 1) : 0;
              echo $match_rate > 100 ? 100 : $match_rate;
              ?>%
            </div>
            <div class="small text-muted mt-1">Matching Efficiency</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="row g-3 mb-4">
      <div class="col-12">
        <div class="card shadow-sm">
          <div class="card-header bg-dark text-white fw-bold">Quick Actions</div>
          <div class="card-body">
            <div class="row g-2">
              <div class="col-md-2">
                <a href="<?php echo url('admin/items.php?type=lost'); ?>" class="btn btn-outline-danger w-100">View Lost Items</a>
              </div>
              <div class="col-md-2">
                <a href="<?php echo url('admin/items.php?type=found'); ?>" class="btn btn-outline-success w-100">View Found Items</a>
              </div>
              <div class="col-md-2">
                <a href="<?php echo url('admin/matches.php'); ?>" class="btn btn-outline-warning w-100">View Matches</a>
              </div>
              <div class="col-md-2">
                <a href="<?php echo url('admin/users.php'); ?>" class="btn btn-outline-primary w-100">Manage Users</a>
              </div>
              <div class="col-md-2">
                <a href="<?php echo url('admin/activity.php'); ?>" class="btn btn-outline-info w-100">Activity Log</a>
              </div>
              <div class="col-md-2">
                <a href="<?php echo url('admin/reports.php'); ?>" class="btn btn-outline-dark w-100">Generate Reports</a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Activities & Items -->
    <div class="row g-3">
      <div class="col-md-6">
        <div class="card shadow-sm h-100">
          <div class="card-header bg-dark text-white fw-bold">Recent Activities</div>
          <div class="card-body p-0" style="max-height: 400px; overflow-y: auto;">
            <?php if ($recent_activities && mysqli_num_rows($recent_activities) > 0): ?>
            <ul class="list-group list-group-flush">
              <?php while($act = mysqli_fetch_assoc($recent_activities)): ?>
              <li class="list-group-item">
                <div class="d-flex justify-content-between">
                  <div>
                    <strong><?php echo cleanHtml($act['action']); ?></strong>
                    <?php if ($act['user_name']): ?>
                    <div class="small text-muted">by <?php echo cleanHtml($act['user_name']); ?> (<?php echo cleanHtml($act['user_role']); ?>)</div>
                    <?php endif; ?>
                    <?php if ($act['details']): ?>
                    <div class="small"><?php echo cleanHtml($act['details']); ?></div>
                    <?php endif; ?>
                  </div>
                  <div class="text-muted small text-end">
                    <?php echo date('M d, h:i A', strtotime($act['created_at'])); ?>
                  </div>
                </div>
              </li>
              <?php endwhile; ?>
            </ul>
            <?php else: ?>
            <p class="text-muted text-center py-4 mb-0">No recent activities</p>
            <?php endif; ?>
          </div>
        </div>
      </div>
      
      <div class="col-md-6">
        <div class="card shadow-sm h-100">
          <div class="card-header bg-dark text-white fw-bold">Recent Items</div>
          <div class="card-body" style="max-height: 400px; overflow-y: auto;">
            <div class="mb-3">
              <h6 class="text-danger fw-bold">🔴 Recent Lost Items</h6>
              <ul class="list-group list-group-flush">
                <?php 
                if ($recent_lost && mysqli_num_rows($recent_lost) > 0):
                  while($item = mysqli_fetch_assoc($recent_lost)): ?>
                  <li class="list-group-item px-0">
                    <div class="d-flex justify-content-between align-items-center">
                      <div>
                        <strong><?php echo cleanHtml($item['item_name']); ?></strong>
                        <div class="small text-muted">by <?php echo cleanHtml($item['reporter_name']); ?> • <?php echo cleanHtml($item['category']); ?></div>
                      </div>
                      <span class="badge text-bg-<?php echo $item['status']==='Returned'?'success':($item['status']==='Matched'?'warning':'secondary'); ?>">
                        <?php echo $item['status']; ?>
                      </span>
                    </div>
                  </li>
                <?php endwhile; else: ?>
                  <li class="list-group-item text-muted px-0">No recent lost items</li>
                <?php endif; ?>
              </ul>
            </div>
            <div>
              <h6 class="text-success fw-bold">🟢 Recent Found Items</h6>
              <ul class="list-group list-group-flush">
                <?php 
                if ($recent_found && mysqli_num_rows($recent_found) > 0):
                  while($item = mysqli_fetch_assoc($recent_found)): ?>
                  <li class="list-group-item px-0">
                    <div class="d-flex justify-content-between align-items-center">
                      <div>
                        <strong><?php echo cleanHtml($item['item_name']); ?></strong>
                        <div class="small text-muted">by <?php echo cleanHtml($item['reporter_name']); ?> • <?php echo cleanHtml($item['category']); ?></div>
                      </div>
                      <span class="badge text-bg-<?php echo $item['status']==='Returned'?'success':($item['status']==='Matched'?'warning':'secondary'); ?>">
                        <?php echo $item['status']; ?>
                      </span>
                    </div>
                  </li>
                <?php endwhile; else: ?>
                  <li class="list-group-item text-muted px-0">No recent found items</li>
                <?php endif; ?>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <?php include __DIR__ . '/../components/footer.php'; ?>
