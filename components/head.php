<?php
/**
 * Shared HTML Head Component
 *
 * Variables expected:
 * - $pageTitle (string): Title of the page
 * - $extraCss (array): Additional CSS files (e.g. ['assets/css/auth.css'])
 */
$pageTitle = $pageTitle ?? 'Smart Lost & Found – Kongu Engineering College';
$extraCss = $extraCss ?? [];
$stylePath = dirname(__DIR__) . '/assets/css/style.css';
?>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no, viewport-fit=cover">
<meta name="theme-color" content="#0d6efd">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="format-detection" content="telephone=no">
<link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🔍</text></svg>">
<title><?php echo htmlspecialchars($pageTitle); ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="<?php echo asset('assets/css/style.css') . '?v=' . filemtime($stylePath); ?>">
<?php foreach ($extraCss as $cssFile): ?>
<link rel="stylesheet" href="<?php echo asset($cssFile); ?>">
<?php endforeach; ?>
