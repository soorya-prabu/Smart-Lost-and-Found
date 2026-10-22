<?php
require_once __DIR__ . '/../config/config.php';
requireRole('admin');

// Get comprehensive statistics
$stats = [
    'total_users' => dbCount($conn, "SELECT COUNT(*) FROM users"),
    'students' => dbCount($conn, "SELECT COUNT(*) FROM users WHERE role = 'student'"),
    'staff' => dbCount($conn, "SELECT COUNT(*) FROM users WHERE role = 'staff'"),
    'total_lost' => dbCount($conn, "SELECT COUNT(*) FROM lost_items"),
    'total_found' => dbCount($conn, "SELECT COUNT(*) FROM found_items"),
    'total_matches' => dbCount($conn, "SELECT COUNT(*) FROM matches"),
    'returned' => dbCount($conn, "SELECT COUNT(*) FROM lost_items WHERE status = 'Returned'") +
                  dbCount($conn, "SELECT COUNT(*) FROM found_items WHERE status = 'Returned'"),
    'pending' => dbCount($conn, "SELECT COUNT(*) FROM lost_items WHERE status = 'Pending'") +
                 dbCount($conn, "SELECT COUNT(*) FROM found_items WHERE status = 'Pending'"),
];

// Category breakdown
$category_stats = [];
$lost_cats = dbFetchAll($conn, "SELECT category, COUNT(*) as count FROM lost_items GROUP BY category");
foreach ($lost_cats as $row) {
    $cat = $row['category'] ?? 'Other';
    $category_stats[$cat] = ($category_stats[$cat] ?? 0) + (int)$row['count'];
}
$found_cats = dbFetchAll($conn, "SELECT category, COUNT(*) as count FROM found_items GROUP BY category");
foreach ($found_cats as $row) {
    $cat = $row['category'] ?? 'Other';
    $category_stats[$cat] = ($category_stats[$cat] ?? 0) + (int)$row['count'];
}
arsort($category_stats);

// Recent activity summary
$today_activities = dbCount($conn, "SELECT COUNT(*) FROM activity_log WHERE DATE(created_at) = CURDATE()");
$week_activities = dbCount($conn, "SELECT COUNT(*) FROM activity_log WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$total_activities = dbCount($conn, "SELECT COUNT(*) FROM activity_log");
$total_feedback = dbCount($conn, "SELECT COUNT(*) FROM feedback");

$pageTitle = 'Reports & Analytics - Admin Panel';
$activeAdminNav = 'reports';
?>
<!doctype html>
<html lang="en">
<head>
  <?php include __DIR__ . '/../components/head.php'; ?>
  <style>
    @media print {
      .navbar, .btn-print, .no-print { display: none !important; }
      body { background: #fff !important; color: #000 !important; }
      .card { border: 1px solid #ccc !important; box-shadow: none !important; }
    }
  </style>
</head>
<body>
  <div class="no-print">
    <?php include __DIR__ . '/../components/admin_navbar.php'; ?>
  </div>

  <div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="h4 mb-0">📊 Reports &amp; Analytics</h2>
      <button class="btn btn-primary btn-print" onclick="window.print()">🖨️ Print Report</button>
    </div>
    
    <!-- Summary Statistics -->
    <div class="row g-3 mb-4">
      <div class="col-md-3">
        <div class="card text-center border-primary shadow-sm">
          <div class="card-body">
            <div class="text-muted small">Total Registered Users</div>
            <div class="h3 mb-0 text-primary"><?php echo $stats['total_users']; ?></div>
            <div class="small text-muted mt-1"><?php echo $stats['students']; ?> Students, <?php echo $stats['staff']; ?> Staff</div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card text-center border-danger shadow-sm">
          <div class="card-body">
            <div class="text-muted small">Total Lost Reports</div>
            <div class="h3 mb-0 text-danger"><?php echo $stats['total_lost']; ?></div>
            <div class="small text-muted mt-1">Logged by campus users</div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card text-center border-success shadow-sm">
          <div class="card-body">
            <div class="text-muted small">Total Found Reports</div>
            <div class="h3 mb-0 text-success"><?php echo $stats['total_found']; ?></div>
            <div class="small text-muted mt-1">Submitted items</div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card text-center border-warning shadow-sm">
          <div class="card-body">
            <div class="text-muted small">Auto Matches Generated</div>
            <div class="h3 mb-0 text-warning"><?php echo $stats['total_matches']; ?></div>
            <div class="small text-muted mt-1">Algorithmic matches</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Recovery & Efficiency Metrics -->
    <div class="row g-3 mb-4">
      <div class="col-md-4">
        <div class="card shadow-sm h-100">
          <div class="card-header bg-dark text-white fw-bold">Recovery Metrics</div>
          <div class="card-body">
            <div class="d-flex justify-content-between mb-3 border-bottom pb-2">
              <span>Returned Items:</span>
              <strong class="text-success"><?php echo $stats['returned']; ?></strong>
            </div>
            <div class="d-flex justify-content-between mb-3 border-bottom pb-2">
              <span>Pending Items:</span>
              <strong class="text-warning"><?php echo $stats['pending']; ?></strong>
            </div>
            <div class="d-flex justify-content-between mb-2">
              <span>Recovery Success Rate:</span>
              <strong class="text-primary">
                <?php 
                $total = $stats['total_lost'] + $stats['total_found'];
                echo $total > 0 ? round(($stats['returned'] / $total) * 100, 1) : 0;
                ?>%
              </strong>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card shadow-sm h-100">
          <div class="card-header bg-dark text-white fw-bold">Platform Activity Metrics</div>
          <div class="card-body">
            <div class="d-flex justify-content-between mb-3 border-bottom pb-2">
              <span>Today's Actions:</span>
              <strong><?php echo $today_activities; ?></strong>
            </div>
            <div class="d-flex justify-content-between mb-3 border-bottom pb-2">
              <span>Past 7 Days Actions:</span>
              <strong><?php echo $week_activities; ?></strong>
            </div>
            <div class="d-flex justify-content-between mb-2">
              <span>Total Activity Records:</span>
              <strong class="text-info"><?php echo $total_activities; ?></strong>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card shadow-sm h-100">
          <div class="card-header bg-dark text-white fw-bold">Feedback Metrics</div>
          <div class="card-body">
            <div class="d-flex justify-content-between mb-3 border-bottom pb-2">
              <span>Total Feedback Received:</span>
              <strong><?php echo $total_feedback; ?></strong>
            </div>
            <div class="d-flex justify-content-between mb-2">
              <span>System Status:</span>
              <span class="badge text-bg-success">Active &amp; Healthy</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Category Breakdown Table -->
    <div class="card shadow-sm">
      <div class="card-header bg-dark text-white fw-bold">Category Distribution Breakdown</div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-striped table-hover mb-0 align-middle">
            <thead class="table-light">
              <tr>
                <th>Category</th>
                <th>Total Reports</th>
                <th>Percentage</th>
              </tr>
            </thead>
            <tbody>
              <?php 
              $grandTotal = array_sum($category_stats);
              if ($category_stats && count($category_stats) > 0):
                foreach ($category_stats as $catName => $count): 
                  $pct = $grandTotal > 0 ? round(($count / $grandTotal) * 100, 1) : 0;
              ?>
              <tr>
                <td><strong><?php echo cleanHtml($catName); ?></strong></td>
                <td><?php echo $count; ?></td>
                <td>
                  <div class="d-flex align-items-center gap-2">
                    <div class="progress flex-grow-1" style="height: 8px;">
                      <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo $pct; ?>%" aria-valuenow="<?php echo $pct; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <span class="small text-muted" style="min-width: 45px;"><?php echo $pct; ?>%</span>
                  </div>
                </td>
              </tr>
              <?php endforeach; else: ?>
                <tr><td colspan="3" class="text-center text-muted py-4">No category data available</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <?php include __DIR__ . '/../components/footer.php'; ?>
