<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
auth_check();

$rows = db()->query(
    'SELECT p.*, d.name AS destination_name, d.slug AS destination_slug
     FROM packages p
     JOIN destinations d ON d.id = p.destination_id
     ORDER BY d.name ASC, p.sort_order ASC, p.id DESC'
)->fetchAll();

$pageTitle = 'Packages';
$activeNav = 'packages';
require __DIR__ . '/../includes/header.php';
?>
<div class="admin-card">
  <div class="admin-top" style="margin-bottom:0.8rem;">
    <div></div>
    <?php if (auth_can('packages.add')): ?>
      <a class="btn" href="/admin/packages/create.php">+ Add Package</a>
    <?php endif; ?>
  </div>
  <table>
    <thead>
      <tr>
        <th class="col-num">#</th>
        <th>Package</th>
        <th>Destination</th>
        <th>Duration</th>
        <th>Price</th>
        <th>Status</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$rows): ?>
        <tr><td colspan="7">No packages yet.</td></tr>
      <?php endif; ?>
      <?php foreach ($rows as $index => $row): ?>
        <tr>
          <td class="col-num"><?= $index + 1 ?></td>
          <td><strong><?= e($row['title']) ?></strong><br><span class="help"><?= e((string) $row['package_type']) ?></span></td>
          <td><?= e($row['destination_name']) ?></td>
          <td><?= e(nights_label($row['nights'] !== null ? (int) $row['nights'] : null, $row['days'] !== null ? (int) $row['days'] : null)) ?></td>
          <td><?= e(format_inr($row['starting_price'] !== null ? (float) $row['starting_price'] : null)) ?></td>
          <td><span class="badge <?= $row['is_active'] ? 'badge-ok' : 'badge-off' ?>"><?= $row['is_active'] ? 'Active' : 'Hidden' ?></span></td>
          <td class="actions">
            <?php if (auth_can('packages.view')): ?>
              <a class="btn btn-sm btn-outline" href="<?= e(site_href('package.php?id=' . (int) $row['id'])) ?>" target="_blank">View</a>
            <?php endif; ?>
            <?php if (auth_can('packages.edit')): ?>
              <a class="btn btn-sm btn-secondary" href="/admin/packages/edit.php?id=<?= (int) $row['id'] ?>">Edit</a>
            <?php endif; ?>
            <?php if (auth_can('packages.delete')): ?>
              <a class="btn btn-sm btn-danger" href="/admin/packages/delete.php?id=<?= (int) $row['id'] ?>" onclick="return confirm('Delete this package?')">Delete</a>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
