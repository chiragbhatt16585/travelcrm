<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
auth_check();

$rows = db()->query(
    'SELECT d.*,
      (SELECT COUNT(*) FROM packages p WHERE p.destination_id = d.id) AS package_count
     FROM destinations d
     ORDER BY d.sort_order ASC, d.name ASC'
)->fetchAll();

$pageTitle = 'Destinations';
$activeNav = 'destinations';
require __DIR__ . '/../includes/header.php';
?>
<div class="admin-card">
  <div class="admin-top" style="margin-bottom:0.8rem;">
    <div></div>
    <?php if (auth_can('destinations.add')): ?>
      <a class="btn" href="/admin/destinations/create.php">+ Add Destination</a>
    <?php endif; ?>
  </div>
  <table>
    <thead>
      <tr>
        <th class="col-num">#</th>
        <th>Image</th>
        <th>Name</th>
        <th>Type</th>
        <th>Packages</th>
        <th>From</th>
        <th>Status</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$rows): ?>
        <tr><td colspan="8">No destinations yet.</td></tr>
      <?php endif; ?>
      <?php foreach ($rows as $index => $row): ?>
        <tr>
          <td class="col-num"><?= $index + 1 ?></td>
          <td>
            <?php if ($row['hero_image']): ?>
              <img class="thumb" src="/<?= e($row['hero_image']) ?>" alt="">
            <?php else: ?>
              <div class="thumb"></div>
            <?php endif; ?>
          </td>
          <td>
            <strong><?= e($row['name']) ?></strong><br>
            <span class="help"><?= e($row['slug']) ?></span>
          </td>
          <td><span class="badge badge-<?= e($row['type']) ?>"><?= e(ucfirst($row['type'])) ?></span></td>
          <td><?= (int) $row['package_count'] ?></td>
          <td><?= e(format_inr($row['starting_price'] !== null ? (float) $row['starting_price'] : null)) ?></td>
          <td><span class="badge <?= $row['is_active'] ? 'badge-ok' : 'badge-off' ?>"><?= $row['is_active'] ? 'Active' : 'Hidden' ?></span></td>
          <td class="actions">
            <?php if (auth_can('destinations.view')): ?>
              <a class="btn btn-sm btn-outline" href="<?= e(site_href('destination.php?dest=' . $row['slug'])) ?>" target="_blank">View</a>
            <?php endif; ?>
            <?php if (auth_can('destinations.edit')): ?>
              <a class="btn btn-sm btn-secondary" href="/admin/destinations/edit.php?id=<?= (int) $row['id'] ?>">Edit</a>
            <?php endif; ?>
            <?php if (auth_can('destinations.delete')): ?>
              <a class="btn btn-sm btn-danger" href="/admin/destinations/delete.php?id=<?= (int) $row['id'] ?>" onclick="return confirm('Delete this destination and all its packages?')">Delete</a>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
