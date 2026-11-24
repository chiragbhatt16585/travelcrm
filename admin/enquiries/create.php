<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
auth_check();

$statuses = enquiry_statuses();
$priorities = enquiry_priorities();
$destinations = db()->query('SELECT name FROM destinations ORDER BY name ASC')->fetchAll();
$error = '';
$form = [
    'name' => '',
    'phone' => '',
    'email' => '',
    'destination' => '',
    'adults' => '',
    'children' => '',
    'message' => '',
    'status' => 'new',
    'priority' => 'medium',
    'next_followup_date' => '',
    'admin_notes' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form = [
        'name' => trim($_POST['name'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'destination' => trim($_POST['destination'] ?? ''),
        'adults' => trim($_POST['adults'] ?? ''),
        'children' => trim($_POST['children'] ?? ''),
        'message' => trim($_POST['message'] ?? ''),
        'status' => (string) ($_POST['status'] ?? 'new'),
        'priority' => (string) ($_POST['priority'] ?? 'medium'),
        'next_followup_date' => trim($_POST['next_followup_date'] ?? ''),
        'admin_notes' => trim($_POST['admin_notes'] ?? ''),
    ];

    if ($form['name'] === '' || $form['phone'] === '') {
        $error = 'Name and phone are required.';
    } elseif ($form['email'] !== '' && !filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter a valid email address.';
    } elseif (!isset($statuses[$form['status']])) {
        $error = 'Choose a valid status.';
    } elseif (!isset($priorities[$form['priority']])) {
        $error = 'Choose a valid priority.';
    } elseif ($form['next_followup_date'] !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $form['next_followup_date'])) {
        $error = 'Enter a valid follow-up date.';
    }

    $adults = null;
    $children = null;
    if ($error === '') {
        try {
            $adults = enquiry_parse_count($form['adults']);
            $children = enquiry_parse_count($form['children']);
        } catch (RuntimeException $e) {
            $error = $e->getMessage();
        }
    }

    if ($error === '') {
        $user = auth_user();
        $nextFollowup = $form['next_followup_date'] !== '' ? $form['next_followup_date'] : null;
        $lastContacted = $form['status'] === 'contacted' ? date('Y-m-d H:i:s') : null;
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $pdo->prepare(
                'INSERT INTO enquiries
                 (name, email, phone, destination, adults, children, message, status, priority, admin_notes, next_followup_date, last_contacted_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            )->execute([
                $form['name'],
                $form['email'] !== '' ? $form['email'] : null,
                $form['phone'],
                $form['destination'] !== '' ? $form['destination'] : null,
                $adults,
                $children,
                $form['message'] !== '' ? $form['message'] : null,
                $form['status'],
                $form['priority'],
                $form['admin_notes'] !== '' ? $form['admin_notes'] : null,
                $nextFollowup,
                $lastContacted,
            ]);
            $id = (int) $pdo->lastInsertId();
            $pdo->prepare(
                'INSERT INTO enquiry_followups (enquiry_id, note, status_to, next_followup_date, created_by)
                 VALUES (?, ?, ?, ?, ?)'
            )->execute([
                $id,
                'Lead added from the admin portal.',
                $form['status'],
                $nextFollowup,
                (string) ($user['name'] ?? $user['username'] ?? 'Admin'),
            ]);
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
        flash_set('success', 'Lead added.');
        redirect('/admin/enquiries/view.php?id=' . $id);
    }
}

$pageTitle = 'Add Lead';
$activeNav = 'enquiries';
require __DIR__ . '/../includes/header.php';
?>
<div class="admin-card">
  <?php if ($error !== ''): ?>
    <div class="flash flash-error"><?= e($error) ?></div>
  <?php endif; ?>
  <form method="post" class="form-grid">
    <div class="form-group">
      <label>Name *</label>
      <input type="text" name="name" required value="<?= e($form['name']) ?>">
    </div>
    <div class="form-group">
      <label>Phone *</label>
      <input type="text" name="phone" required value="<?= e($form['phone']) ?>">
    </div>
    <div class="form-group">
      <label>Email</label>
      <input type="email" name="email" value="<?= e($form['email']) ?>">
    </div>
    <div class="form-group">
      <label>Destination</label>
      <input type="text" name="destination" list="destination-names" value="<?= e($form['destination']) ?>" placeholder="Rajasthan, Kerala, or a custom place">
      <datalist id="destination-names">
        <?php foreach ($destinations as $destination): ?>
          <option value="<?= e($destination['name']) ?>"></option>
        <?php endforeach; ?>
      </datalist>
    </div>
    <div class="form-group">
      <label>Adults</label>
      <input type="number" name="adults" min="0" max="99" value="<?= e($form['adults']) ?>">
    </div>
    <div class="form-group">
      <label>Children</label>
      <input type="number" name="children" min="0" max="99" value="<?= e($form['children']) ?>">
    </div>
    <div class="form-group">
      <label>Status</label>
      <select name="status">
        <?php foreach ($statuses as $key => $label): ?>
          <option value="<?= e($key) ?>" <?= $form['status'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group">
      <label>Priority</label>
      <select name="priority">
        <?php foreach ($priorities as $key => $label): ?>
          <option value="<?= e($key) ?>" <?= $form['priority'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group">
      <label>Next follow-up date</label>
      <input type="date" name="next_followup_date" value="<?= e($form['next_followup_date']) ?>">
    </div>
    <div class="form-group full">
      <label>Customer message</label>
      <textarea name="message" placeholder="What the client asked for"><?= e($form['message']) ?></textarea>
    </div>
    <div class="form-group full">
      <label>Internal notes</label>
      <textarea name="admin_notes" placeholder="How this lead came in, budget, travellers"><?= e($form['admin_notes']) ?></textarea>
    </div>
    <div class="form-group full actions">
      <button class="btn" type="submit">Save lead</button>
      <a class="btn btn-outline" href="/admin/enquiries/index.php">Cancel</a>
    </div>
  </form>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
