<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
auth_check();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    $stmt = db()->prepare('SELECT id, role FROM admins WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row || ($row['role'] ?? '') === 'superadmin') {
        flash_set('error', 'That staff account cannot be deleted.');
    } else {
        db()->prepare('DELETE FROM admins WHERE id = ? AND role = ?')->execute([$id, 'staff']);
        flash_set('success', 'Staff account deleted.');
    }
    redirect('/admin/staff/index.php');
}

$staff = db()->query(
    "SELECT s.id, s.username, s.name, s.created_by, s.created_at, c.name AS added_by_name
     FROM admins s
     LEFT JOIN admins c ON c.username = s.created_by
     WHERE s.role = 'staff'
     ORDER BY s.name ASC, s.username ASC"
)->fetchAll();

$pageTitle = 'Staff';
$activeNav = 'staff';
require __DIR__ . '/../includes/header.php';
?>
<div class="admin-card">
  <div class="admin-top" style="margin-bottom:0.8rem;">
    <p class="help" style="margin:0;">Add a person, then use Password to set or replace their password.</p>
    <a class="btn" href="/admin/staff/form.php">+ Add staff</a>
  </div>
  <table>
    <thead>
      <tr>
        <th class="col-num">#</th>
        <th>Name</th>
        <th>Username</th>
        <th>Added by</th>
        <th>Added on</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$staff): ?>
        <tr><td colspan="6">No staff accounts yet.</td></tr>
      <?php endif; ?>
      <?php foreach ($staff as $index => $row): ?>
        <tr>
          <td class="col-num"><?= $index + 1 ?></td>
          <td><strong><?= e($row['name']) ?></strong></td>
          <td><?= e($row['username']) ?></td>
          <td><?php
            $addedBy = trim((string) ($row['added_by_name'] ?? ''));
            if ($addedBy === '') {
                $addedBy = trim((string) ($row['created_by'] ?? ''));
            }
            echo $addedBy !== '' ? e($addedBy) : '—';
          ?></td>
          <td><?= !empty($row['created_at']) ? e(date('d M Y H:i', strtotime((string) $row['created_at']))) : '—' ?></td>
          <td class="actions">
            <a class="btn btn-sm btn-secondary" href="/admin/staff/form.php?id=<?= (int) $row['id'] ?>">Edit</a>
            <a class="btn btn-sm btn-outline" href="/admin/staff/password.php?id=<?= (int) $row['id'] ?>">Password</a>
            <form method="post" style="display:inline;" onsubmit="return confirm('Delete this staff account?')">
              <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
              <button class="btn btn-sm btn-danger" type="submit">Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
