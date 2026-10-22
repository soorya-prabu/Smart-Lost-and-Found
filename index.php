<?php
require_once __DIR__ . '/config/config.php';

// If user is already logged in, provide shortcut to dashboard
$loggedInRole = currentUserRole();
$dashboardUrl = null;
if ($loggedInRole === 'student') {
    $dashboardUrl = 'student/dashboard.php';
} elseif ($loggedInRole === 'staff') {
    $dashboardUrl = 'staff/dashboard.php';
} elseif ($loggedInRole === 'admin') {
    $dashboardUrl = 'admin/dashboard.php';
}

$pageTitle = 'Smart Lost & Found – Kongu Engineering College';
?>
<!doctype html>
<html lang="en">
<head>
  <?php include __DIR__ . '/components/head.php'; ?>
</head>
<body class="bg-campus position-relative text-white">
  <div class="overlay"></div>
  
  <!-- Floating particles background -->
  <div class="particles-container">
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
  </div>

  <!-- Cursor glow effect -->
  <div class="cursor-glow"></div>

  <div class="container position-relative" style="z-index:2;min-height:100vh;display:flex;align-items:center;justify-content:center;">
    <div class="center-card p-4 text-center fade-in">
      <!-- Animated icon badges -->
      <div class="icon-badge-wrapper mb-3">
        <div class="icon-badge icon-badge-1">🔍</div>
        <div class="icon-badge icon-badge-2">✨</div>
        <div class="icon-badge icon-badge-3">🎯</div>
      </div>
      
      <h1 class="h3 mb-1 title-glow animated-title">
        <span class="title-word">Smart</span> 
        <span class="title-word">Lost</span> 
        <span class="title-word">&amp;</span> 
        <span class="title-word">Found</span>
      </h1>
      <div class="accent-line"></div>
      <p class="mb-4 subtitle-soft animated-subtitle">Kongu Engineering College</p>
      
      <?php if ($msg = flashMessage()): ?>
        <div class="alert alert-info auto-fade py-2 mb-3"><?php echo $msg; ?></div>
      <?php endif; ?>

      <?php if ($dashboardUrl): ?>
        <div class="alert alert-light py-2 mb-4 text-dark shadow-sm">
          <span>Signed in as <strong><?php echo cleanHtml(currentUserName()); ?></strong> (<?php echo ucfirst($loggedInRole); ?>)</span>
          <div class="mt-2">
            <a href="<?php echo url($dashboardUrl); ?>" class="btn btn-sm btn-primary">Go to Dashboard</a>
            <a href="<?php echo url('auth/logout.php'); ?>" class="btn btn-sm btn-outline-danger ms-2">Logout</a>
          </div>
        </div>
      <?php endif; ?>

      <div class="d-flex justify-content-center gap-3 mb-4 button-wrapper flex-wrap">
        <a class="btn btn-primary btn-lg role-btn student-btn" href="<?php echo url('auth/login_student.php'); ?>">
          <span class="btn-content">
            <span class="btn-icon">🎓</span>
            <span class="btn-text">Student</span>
            <span class="btn-shine"></span>
          </span>
        </a>
        <a class="btn btn-success btn-lg role-btn staff-btn" href="<?php echo url('auth/login_staff.php'); ?>">
          <span class="btn-content">
            <span class="btn-icon">👨‍🏫</span>
            <span class="btn-text">Staff</span>
            <span class="btn-shine"></span>
          </span>
        </a>
        <a class="btn btn-danger btn-lg role-btn" href="<?php echo url('auth/login_admin.php'); ?>">
          <span class="btn-content">
            <span class="btn-icon">🏛️</span>
            <span class="btn-text">Admin</span>
            <span class="btn-shine"></span>
          </span>
        </a>
      </div>
      
      <p class="mt-4 mb-0 subtitle-soft small description-text">Report lost or found items. The system auto-matches and notifies both users.</p>
      
      <!-- Decorative elements -->
      <div class="decorative-circles">
        <div class="circle circle-1"></div>
        <div class="circle circle-2"></div>
        <div class="circle circle-3"></div>
      </div>
    </div>
  </div>
  
  <?php 
  $extraJs = ['assets/js/index-effects.js'];
  include __DIR__ . '/components/footer.php'; 
  ?>
