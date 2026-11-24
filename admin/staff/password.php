<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
auth_check();

if (!auth_is_superadmin()) {
    flash_set('error', 'Only Super Admin can change another account password.');
    redirect(auth_home_url());
}

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = db()->prepare('SELECT id, username, name, role FROM admins WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$account = $stmt->fetch();
if (!$account) {
    flash_set('error', 'Account not found.');
    redirect('/admin/staff/index.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = (string) ($_POST['password'] ?? '');
    $confirm = (string) ($_POST['confirm_password'] ?? '');
    if ($password === '' || $confirm === '') {
        $error = 'Enter the new password twice.';
    } elseif (strlen($password) < 6) {
        $error = 'New password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'New password and confirmation do not match.';
    } else {
        db()->prepare('UPDATE admins SET password_hash = ? WHERE id = ?')
            ->execute([password_hash($password, PASSWORD_DEFAULT), $id]);
        flash_set('success', 'Password updated for ' . $account['name'] . '.');
        redirect($account['role'] === 'staff' ? '/admin/staff/index.php' : auth_home_url());
    }
}

$pageTitle = 'Set password';
$activeNav = 'staff';
require __DIR__ . '/../includes/header.php';
?>
<div class="admin-card" style="max-width:520px;">
  <p class="help" style="margin-top:0;">Set a new password for <strong><?= e($account['name']) ?></strong> (<?= e($account['username']) ?>). The current password is not required.</p>
  <?php if ($error !== ''): ?>
    <div class="flash flash-error"><?= e($error) ?></div>
  <?php endif; ?>
  <form method="post" class="form-grid">
    <input type="hidden" name="id" value="<?= (int) $account['id'] ?>">
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
      <a class="btn btn-outline" href="/admin/staff/index.php">Cancel</a>
    </div>
  </form>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
