<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
auth_check();

$pageTitle = 'No access';
$activeNav = '';
require __DIR__ . '/includes/header.php';
?>
<div class="admin-card">
  <p style="margin:0;">No sections have been assigned to this account. Ask a Super Admin to grant access.</p>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
