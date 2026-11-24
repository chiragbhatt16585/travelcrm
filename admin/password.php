<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
auth_check();

$user = auth_user();
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current = (string) ($_POST['current_password'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    $confirm = (string) ($_POST['confirm_password'] ?? '');
    $stmt = db()->prepare('SELECT password_hash FROM admins WHERE id = ? LIMIT 1');
    $stmt->execute([(int) ($user['id'] ?? 0)]);
    $hash = (string) ($stmt->fetchColumn() ?: '');
    if ($current === '' || $password === '' || $confirm === '') {
        $error = 'Fill in every password field.';
    } elseif (!password_verify($current, $hash)) {
        $error = 'Current password is not correct.';
    } elseif (strlen($password) < 6) {
        $error = 'New password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'New password and confirmation do not match.';
    } else {
        db()->prepare('UPDATE admins SET password_hash = ? WHERE id = ?')
            ->execute([password_hash($password, PASSWORD_DEFAULT), (int) $user['id']]);
        flash_set('success', 'Your password has been changed.');
        redirect(auth_home_url());
    }
}

$pageTitle = 'Change password';
$activeNav = '';
require __DIR__ . '/includes/header.php';
?>
<div class="admin-card" style="max-width:520px;">
  <p class="help" style="margin-top:0;">This changes the password for <?= e($user['name'] ?? 'your account') ?>.</p>
  <?php if ($error !== ''): ?>
    <div class="flash flash-error"><?= e($error) ?></div>
  <?php endif; ?>
  <form method="post" class="form-grid">
    <div class="form-group full">
      <label>Current password</label>
      <input type="password" name="current_password" required autocomplete="current-password">
    </div>
    <div class="form-group full">
      <label>New password</label>
      <input type="password" name="password" required autocomplete="new-password">
    </div>
    <div class="form-group full">
      <label>Confirm new password</label>
      <input type="password" name="confirm_password" required autocomplete="new-password">
    </div>
    <div class="form-group full actions">
      <button class="btn" type="submit">Save password</button>
    </div>
  </form>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
