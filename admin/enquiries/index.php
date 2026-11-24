<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
auth_check();

$statuses = enquiry_statuses();
$filter = trim($_GET['status'] ?? '');
$view = trim($_GET['view'] ?? '');
$q = trim($_GET['q'] ?? '');

$sql = 'SELECT * FROM enquiries WHERE 1=1';
$params = [];

if ($filter !== '' && isset($statuses[$filter])) {
    $sql .= ' AND status = ?';
    $params[] = $filter;
}

if ($view === 'due') {
    $sql .= " AND next_followup_date IS NOT NULL AND next_followup_date <= CURDATE() AND status NOT IN ('booked','not_interested','closed')";
} elseif ($view === 'upcoming') {
    $sql .= " AND next_followup_date IS NOT NULL AND next_followup_date > CURDATE() AND status NOT IN ('booked','not_interested','closed')";
} elseif ($view === 'open') {
    $sql .= " AND status NOT IN ('booked','not_interested','closed')";
}

if ($q !== '') {
    $sql .= ' AND (name LIKE ? OR phone LIKE ? OR email LIKE ? OR destination LIKE ?)';
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like, $like);
}

$sql .= ' ORDER BY
  CASE
    WHEN next_followup_date IS NOT NULL AND next_followup_date <= CURDATE() AND status NOT IN (\'booked\',\'not_interested\',\'closed\') THEN 0
    WHEN status = \'new\' THEN 1
    WHEN status = \'follow_up\' THEN 2
    ELSE 3
  END,
  next_followup_date IS NULL,
  next_followup_date ASC,
  created_at DESC
  LIMIT 200';

$stmt = db()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$counts = [
    'all' => (int) db()->query('SELECT COUNT(*) FROM enquiries')->fetchColumn(),
    'new' => (int) db()->query("SELECT COUNT(*) FROM enquiries WHERE status = 'new'")->fetchColumn(),
    'due' => (int) db()->query("SELECT COUNT(*) FROM enquiries WHERE next_followup_date IS NOT NULL AND next_followup_date <= CURDATE() AND status NOT IN ('booked','not_interested','closed')")->fetchColumn(),
    'open' => (int) db()->query("SELECT COUNT(*) FROM enquiries WHERE status NOT IN ('booked','not_interested','closed')")->fetchColumn(),
];

$pageTitle = 'Enquiries & Follow-ups';
$activeNav = 'enquiries';
require __DIR__ . '/../includes/header.php';
?>
<div class="stats" style="margin-bottom:1rem;">
  <a class="stat" href="/admin/enquiries/index.php" style="<?= $view === '' && $filter === '' ? 'border-color:#ff6b35;' : '' ?>">
    <div class="label">All Enquiries</div><div class="value"><?= $counts['all'] ?></div>
  </a>
  <a class="stat" href="/admin/enquiries/index.php?status=new" style="<?= $filter === 'new' ? 'border-color:#ff6b35;' : '' ?>">
    <div class="label">New</div><div class="value"><?= $counts['new'] ?></div>
  </a>
  <a class="stat" href="/admin/enquiries/index.php?view=due" style="<?= $view === 'due' ? 'border-color:#ff6b35;' : '' ?>">
    <div class="label">Follow-ups Due</div><div class="value"><?= $counts['due'] ?></div>
  </a>
  <a class="stat" href="/admin/enquiries/index.php?view=open" style="<?= $view === 'open' ? 'border-color:#ff6b35;' : '' ?>">
    <div class="label">Open Leads</div><div class="value"><?= $counts['open'] ?></div>
  </a>
</div>

<div class="admin-card">
  <?php if (auth_can('enquiries.add')): ?>
    <div class="admin-top" style="margin-bottom:0.8rem;">
      <p class="help" style="margin:0;">Add a lead that came by phone, walk-in, or WhatsApp.</p>
      <a class="btn" href="/admin/enquiries/create.php">+ Add Lead</a>
    </div>
  <?php endif; ?>
  <form method="get" class="admin-top" style="margin-bottom:0.9rem; gap:0.6rem; flex-wrap:wrap;">
    <div class="actions" style="flex:1;">
      <input type="text" name="q" value="<?= e($q) ?>" placeholder="Search name, phone, email, destination" style="min-width:220px;">
      <select name="status" style="width:auto;min-width:150px;">
        <option value="">All statuses</option>
        <?php foreach ($statuses as $key => $label): ?>
          <option value="<?= e($key) ?>" <?= $filter === $key ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
      <select name="view" style="width:auto;min-width:150px;">
        <option value="">All dates</option>
        <option value="due" <?= $view === 'due' ? 'selected' : '' ?>>Follow-ups due</option>
        <option value="upcoming" <?= $view === 'upcoming' ? 'selected' : '' ?>>Upcoming follow-ups</option>
        <option value="open" <?= $view === 'open' ? 'selected' : '' ?>>Open leads</option>
      </select>
      <button class="btn btn-secondary" type="submit">Filter</button>
      <a class="btn btn-outline" href="/admin/enquiries/index.php">Reset</a>
    </div>
  </form>

  <table>
    <thead>
      <tr>
        <th class="col-num">#</th>
        <th>Lead</th>
        <th>Destination</th>
        <th>Status</th>
        <th>Priority</th>
        <th>Next Follow-up</th>
        <th>Last Contact</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$rows): ?>
        <tr><td colspan="8">No enquiries found for this filter.</td></tr>
      <?php endif; ?>
      <?php foreach ($rows as $index => $row): ?>
        <?php
          $due = !empty($row['next_followup_date'])
            && $row['next_followup_date'] <= date('Y-m-d')
            && !in_array($row['status'], ['booked', 'not_interested', 'closed'], true);
        ?>
        <tr class="<?= $due ? 'row-due' : '' ?>">
          <td class="col-num"><?= $index + 1 ?></td>
          <td>
            <strong><?= e($row['name']) ?></strong><br>
            <span class="help"><?= e((string) $row['phone']) ?><?= $row['email'] ? ' · ' . e((string) $row['email']) : '' ?></span>
            <?php $party = enquiry_party_label($row); ?>
            <?php if ($party !== ''): ?><br><span class="help"><?= e($party) ?></span><?php endif; ?>
            <br>
            <span class="help">Received <?= e(date('d M Y', strtotime((string) $row['created_at']))) ?></span>
          </td>
          <td><?= e((string) ($row['destination'] ?: '—')) ?></td>
          <td>
            <span class="badge <?= e(enquiry_status_badge((string) $row['status'])) ?>">
              <?= e($statuses[$row['status']] ?? ucfirst((string) $row['status'])) ?>
            </span>
          </td>
          <td>
            <span class="badge badge-priority-<?= e((string) $row['priority']) ?>">
              <?= e(ucfirst((string) $row['priority'])) ?>
            </span>
          </td>
          <td>
            <?php if (!empty($row['next_followup_date'])): ?>
              <?= e(date('d M Y', strtotime((string) $row['next_followup_date']))) ?>
              <?php if ($due): ?><div class="help" style="color:#b91c1c;font-weight:600;">Due now</div><?php endif; ?>
            <?php else: ?>
              <span class="help">Not set</span>
            <?php endif; ?>
          </td>
          <td>
            <?= !empty($row['last_contacted_at'])
              ? e(date('d M Y H:i', strtotime((string) $row['last_contacted_at'])))
              : '<span class="help">Never</span>' ?>
          </td>
          <td class="actions">
            <a class="btn btn-sm btn-secondary" href="/admin/enquiries/view.php?id=<?= (int) $row['id'] ?>">Follow-up</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
