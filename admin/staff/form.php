<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
auth_check();

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$staff = null;
if ($id > 0) {
    $stmt = db()->prepare("SELECT * FROM admins WHERE id = ? AND role = 'staff' LIMIT 1");
    $stmt->execute([$id]);
    $staff = $stmt->fetch();
    if (!$staff) {
        flash_set('error', 'Staff account not found.');
        redirect('/admin/staff/index.php');
    }
}

$groups = auth_action_groups();
$allowedKeys = array_keys(auth_assignable_permissions());
$selected = [];
if ($staff) {
    $perm = db()->prepare('SELECT permission FROM admin_permissions WHERE admin_id = ?');
    $perm->execute([(int) $staff['id']]);
    $selected = auth_normalize_grants(array_column($perm->fetchAll(), 'permission'));
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    $selected = array_values(array_intersect($allowedKeys, (array) ($_POST['permissions'] ?? [])));

    if ($name === '' || $username === '') {
        $error = 'Name and username are required.';
    } elseif (!preg_match('/^[A-Za-z0-9._-]{3,50}$/', $username)) {
        $error = 'Username must be 3 to 50 letters, numbers, dots, or dashes.';
    } elseif (strcasecmp($username, 'superadmin') === 0) {
        $error = 'That username is reserved.';
    } elseif (!$staff && $password === '') {
        $error = 'Password is required for a new staff account.';
    } elseif ($password !== '' && strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif (!$selected) {
        $error = 'Tick at least one button this person can use.';
    } else {
        $taken = db()->prepare('SELECT id FROM admins WHERE username = ? AND id <> ? LIMIT 1');
        $taken->execute([$username, $id]);
        if ($taken->fetch()) {
            $error = 'That username is already in use.';
        }
    }

    if ($error === '') {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            if ($staff) {
                if ($password !== '') {
                    $pdo->prepare('UPDATE admins SET name = ?, username = ?, password_hash = ? WHERE id = ? AND role = ?')
                        ->execute([$name, $username, password_hash($password, PASSWORD_DEFAULT), $id, 'staff']);
                } else {
                    $pdo->prepare('UPDATE admins SET name = ?, username = ? WHERE id = ? AND role = ?')
                        ->execute([$name, $username, $id, 'staff']);
                }
                $staffId = $id;
            } else {
                $pdo->prepare('INSERT INTO admins (username, password_hash, name, role, created_by) VALUES (?, ?, ?, ?, ?)')
                    ->execute([
                        $username,
                        password_hash($password, PASSWORD_DEFAULT),
                        $name,
                        'staff',
                        (string) (auth_user()['username'] ?? ''),
                    ]);
                $staffId = (int) $pdo->lastInsertId();
            }
            $pdo->prepare('DELETE FROM admin_permissions WHERE admin_id = ?')->execute([$staffId]);
            $insert = $pdo->prepare('INSERT INTO admin_permissions (admin_id, permission) VALUES (?, ?)');
            foreach ($selected as $permission) {
                $insert->execute([$staffId, $permission]);
            }
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
        flash_set('success', $staff ? 'Staff account updated.' : 'Staff account created.');
        redirect('/admin/staff/index.php');
    }
}

$pageTitle = $staff ? 'Edit staff' : 'Add staff';
$activeNav = 'staff';
require __DIR__ . '/../includes/header.php';
?>
<div class="admin-card">
  <?php if ($error !== ''): ?>
    <div class="flash flash-error"><?= e($error) ?></div>
  <?php endif; ?>
  <form method="post" class="form-grid">
    <input type="hidden" name="id" value="<?= (int) $id ?>">
    <div class="form-group">
      <label>Name</label>
      <input type="text" name="name" required value="<?= e($_POST['name'] ?? ($staff['name'] ?? '')) ?>">
    </div>
    <div class="form-group">
      <label>Username</label>
      <input type="text" name="username" required value="<?= e($_POST['username'] ?? ($staff['username'] ?? '')) ?>">
    </div>
    <div class="form-group full">
      <?php if ($staff): ?>
        <a class="btn btn-outline" href="/admin/staff/password.php?id=<?= (int) $staff['id'] ?>">Set a new password</a>
      <?php else: ?>
        <label>Password</label>
        <input type="password" name="password" required autocomplete="new-password">
      <?php endif; ?>
    </div>
    <div class="form-group full">
      <label>Buttons they can use</label>
      <p class="help">The menu shows a page when at least one of its buttons is ticked. Quotation terms, company profile, client template, staff, and access until stay with Super Admin.</p>
      <?php foreach ($groups as $group): ?>
        <div class="rights-group">
          <h3><?= e($group['label']) ?></h3>
          <div class="rights-grid">
            <?php foreach ($group['actions'] as $key => $label): ?>
              <label>
                <input type="checkbox" name="permissions[]" value="<?= e($key) ?>" <?= in_array($key, $selected, true) ? 'checked' : '' ?>>
                <?= e($label) ?>
              </label>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="form-group full actions">
      <button class="btn" type="submit"><?= $staff ? 'Save staff' : 'Create staff' ?></button>
      <a class="btn btn-outline" href="/admin/staff/index.php">Cancel</a>
    </div>
  </form>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
