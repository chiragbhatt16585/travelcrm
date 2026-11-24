<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
auth_check();

$destCount = (int) db()->query('SELECT COUNT(*) FROM destinations')->fetchColumn();
$pkgCount = (int) db()->query('SELECT COUNT(*) FROM packages')->fetchColumn();
$enqCount = (int) db()->query('SELECT COUNT(*) FROM enquiries')->fetchColumn();
$dueCount = (int) db()->query(
    "SELECT COUNT(*) FROM enquiries
     WHERE next_followup_date IS NOT NULL
       AND next_followup_date <= CURDATE()
       AND status NOT IN ('booked','not_interested','closed')"
)->fetchColumn();
$newCount = (int) db()->query("SELECT COUNT(*) FROM enquiries WHERE status = 'new'")->fetchColumn();

$recent = db()->query(
    'SELECT p.id, p.title, p.starting_price, p.is_active, d.name AS destination_name
     FROM packages p
     JOIN destinations d ON d.id = p.destination_id
     ORDER BY p.updated_at DESC
     LIMIT 6'
)->fetchAll();

$dueFollowups = db()->query(
    "SELECT id, name, phone, destination, status, next_followup_date, priority
     FROM enquiries
     WHERE next_followup_date IS NOT NULL
       AND next_followup_date <= CURDATE()
       AND status NOT IN ('booked','not_interested','closed')
     ORDER BY next_followup_date ASC, priority = 'high' DESC
     LIMIT 8"
)->fetchAll();

$newLeads = db()->query(
    "SELECT id, name, phone, destination, created_at
     FROM enquiries
     WHERE status = 'new'
     ORDER BY created_at DESC
     LIMIT 5"
)->fetchAll();

$statuses = enquiry_statuses();
$pageTitle = 'Dashboard';
$activeNav = 'dashboard';
require __DIR__ . '/includes/header.php';
?>
<div class="stats">
  <div class="stat"><div class="label">Destinations</div><div class="value"><?= $destCount ?></div></div>
  <div class="stat"><div class="label">Packages</div><div class="value"><?= $pkgCount ?></div></div>
  <div class="stat"><div class="label">New Enquiries</div><div class="value"><?= $newCount ?></div></div>
  <div class="stat"><div class="label">Follow-ups Due</div><div class="value"><?= $dueCount ?></div></div>
</div>

<?php if (auth_can('destinations.add') || auth_can('packages.add') || auth_can('enquiries.add') || auth_can('enquiries')): ?>
<div class="admin-card" style="margin-top:1rem;">
  <div class="admin-top" style="margin-bottom:0.6rem;">
    <h2 style="margin:0;font-size:1.05rem;">Quick actions</h2>
    <div class="actions">
      <?php if (auth_can('enquiries.add')): ?>
        <a class="btn" href="/admin/enquiries/create.php">Add Lead</a>
      <?php endif; ?>
      <?php if (auth_can('destinations.add')): ?>
        <a class="btn" href="/admin/destinations/create.php">Add Destination</a>
      <?php endif; ?>
      <?php if (auth_can('packages.add')): ?>
        <a class="btn btn-secondary" href="/admin/packages/create.php">Add Package</a>
      <?php endif; ?>
      <?php if (auth_can('enquiries')): ?>
        <a class="btn btn-outline" href="/admin/enquiries/index.php?view=due">View Due Follow-ups</a>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php endif; ?>

<div class="form-grid" style="margin-top:0.2rem;">
  <div class="admin-card">
    <h2 style="margin:0 0 0.8rem;font-size:1.05rem;">Follow-ups due today / overdue</h2>
    <table>
      <thead>
        <tr>
          <th>Lead</th>
          <th>Status</th>
          <th>Due</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$dueFollowups): ?>
          <tr><td colspan="4">No follow-ups due. Great job!</td></tr>
        <?php endif; ?>
        <?php foreach ($dueFollowups as $row): ?>
          <tr class="row-due">
            <td>
              <strong><?= e($row['name']) ?></strong><br>
              <span class="help"><?= e((string) $row['phone']) ?> · <?= e((string) ($row['destination'] ?: '—')) ?></span>
            </td>
            <td><span class="badge <?= e(enquiry_status_badge((string) $row['status'])) ?>"><?= e($statuses[$row['status']] ?? $row['status']) ?></span></td>
            <td><?= e(date('d M Y', strtotime((string) $row['next_followup_date']))) ?></td>
            <td><?php if (auth_can('enquiries')): ?><a class="btn btn-sm btn-secondary" href="/admin/enquiries/view.php?id=<?= (int) $row['id'] ?>">Follow-up</a><?php endif; ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="admin-card">
    <h2 style="margin:0 0 0.8rem;font-size:1.05rem;">New leads (<?= $enqCount ?> total)</h2>
    <table>
      <thead>
        <tr>
          <th>Lead</th>
          <th>Received</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$newLeads): ?>
          <tr><td colspan="3">No new leads right now.</td></tr>
        <?php endif; ?>
        <?php foreach ($newLeads as $row): ?>
          <tr>
            <td>
              <strong><?= e($row['name']) ?></strong><br>
              <span class="help"><?= e((string) $row['phone']) ?> · <?= e((string) ($row['destination'] ?: '—')) ?></span>
            </td>
            <td><?= e(date('d M Y H:i', strtotime((string) $row['created_at']))) ?></td>
            <td><?php if (auth_can('enquiries')): ?><a class="btn btn-sm btn-secondary" href="/admin/enquiries/view.php?id=<?= (int) $row['id'] ?>">Open</a><?php endif; ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="admin-card">
  <h2 style="margin:0 0 0.8rem;font-size:1.05rem;">Recent packages</h2>
  <table>
    <thead>
      <tr>
        <th>Package</th>
        <th>Destination</th>
        <th>Price</th>
        <th>Status</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$recent): ?>
        <tr><td colspan="5">No packages yet. Create your first package.</td></tr>
      <?php endif; ?>
      <?php foreach ($recent as $row): ?>
        <tr>
          <td><?= e($row['title']) ?></td>
          <td><?= e($row['destination_name']) ?></td>
          <td><?= e(format_inr($row['starting_price'] !== null ? (float) $row['starting_price'] : null)) ?></td>
          <td>
            <span class="badge <?= $row['is_active'] ? 'badge-ok' : 'badge-off' ?>">
              <?= $row['is_active'] ? 'Active' : 'Hidden' ?>
            </span>
          </td>
          <td><?php if (auth_can('packages.edit')): ?><a class="btn btn-sm btn-outline" href="/admin/packages/edit.php?id=<?= (int) $row['id'] ?>">Edit</a><?php endif; ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
