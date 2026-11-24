<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/site.php';
auth_check();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $until = trim($_POST['valid_until'] ?? '');
    if ($until === '') {
        site_setting_save('software_valid_until', '');
        flash_set('success', 'No end date. Staff can sign in any day.');
        redirect('/admin/settings/access.php');
    }
    $date = DateTime::createFromFormat('Y-m-d', $until);
    if (!$date || $date->format('Y-m-d') !== $until) {
        $error = 'Choose a valid date.';
    } else {
        site_setting_save('software_valid_until', $until);
        flash_set('success', 'Staff can sign in until ' . $date->format('d M Y') . '.');
        redirect('/admin/settings/access.php');
    }
}

$until = $error !== '' ? trim($_POST['valid_until'] ?? '') : auth_valid_until();
$expired = auth_is_expired();

$pageTitle = 'Access until';
$activeNav = 'access';
require __DIR__ . '/../includes/header.php';
?>
<div class="admin-card">
  <p class="help" style="margin-top:0;">Set the last day this software stays open for staff. They can sign in on that day. From the next day, only Super Admin can sign in. Leave the date empty for no end date.</p>
  <?php if ($error !== ''): ?>
    <div class="flash flash-error"><?= e($error) ?></div>
  <?php endif; ?>
  <?php if ($until !== '' && $expired): ?>
    <div class="flash flash-error">This date has passed. Staff sign-in is closed.</div>
  <?php elseif ($until !== ''): ?>
    <div class="flash flash-success">Staff can sign in until <?= e(date('d M Y', strtotime($until))) ?>.</div>
  <?php endif; ?>
  <form method="post" class="form-grid">
    <div class="form-group">
      <label>Valid until</label>
      <input type="date" name="valid_until" value="<?= e($until) ?>">
    </div>
    <div class="form-group full actions">
      <button class="btn" type="submit">Save date</button>
    </div>
  </form>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
