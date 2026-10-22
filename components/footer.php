<?php
/**
 * Shared Footer and Scripts Component
 *
 * Variables expected:
 * - $extraJs (array): Additional JS files (e.g. ['assets/js/index-effects.js'])
 */
$extraJs = $extraJs ?? [];
?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo asset('assets/js/app.js'); ?>"></script>
<?php foreach ($extraJs as $jsFile): ?>
<script src="<?php echo asset($jsFile); ?>"></script>
<?php endforeach; ?>
</body>
</html>
