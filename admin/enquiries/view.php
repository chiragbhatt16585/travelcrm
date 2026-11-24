<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
auth_check();

$id = (int) ($_GET['id'] ?? 0);
$enquiry = get_enquiry_by_id($id);
if (!$enquiry) {
    flash_set('error', 'Enquiry not found.');
    redirect('/admin/enquiries/index.php');
}

$statuses = enquiry_statuses();
$priorities = enquiry_priorities();
$error = '';
$user = auth_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $oldStatus = (string) $enquiry['status'];
        $status = $_POST['status'] ?? 'new';
        if (!isset($statuses[$status])) {
            throw new RuntimeException('Invalid status.');
        }
        $priority = $_POST['priority'] ?? 'medium';
        if (!isset($priorities[$priority])) {
            $priority = 'medium';
        }

        $adminNotes = trim($_POST['admin_notes'] ?? '');
        $clientResponse = trim($_POST['client_response'] ?? '');
        $followupNote = trim($_POST['followup_note'] ?? '');
        $nextFollowup = trim($_POST['next_followup_date'] ?? '');
        $markContacted = isset($_POST['mark_contacted']);

        $nextFollowupDate = $nextFollowup !== '' ? $nextFollowup : null;
        if ($nextFollowupDate !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $nextFollowupDate)) {
            throw new RuntimeException('Invalid follow-up date.');
        }
        $adults = enquiry_parse_count((string) ($_POST['adults'] ?? ''));
        $children = enquiry_parse_count((string) ($_POST['children'] ?? ''));

        $lastContacted = $enquiry['last_contacted_at'];
        if ($markContacted || $status === 'contacted' || $followupNote !== '') {
            $lastContacted = date('Y-m-d H:i:s');
        }

        $pdo = db();
        $pdo->beginTransaction();

        $pdo->prepare(
            'UPDATE enquiries
             SET status = ?, priority = ?, adults = ?, children = ?, admin_notes = ?, client_response = ?, next_followup_date = ?, last_contacted_at = ?
             WHERE id = ?'
        )->execute([
            $status,
            $priority,
            $adults,
            $children,
            $adminNotes !== '' ? $adminNotes : null,
            $clientResponse !== '' ? $clientResponse : null,
            $nextFollowupDate,
            $lastContacted,
            $id,
        ]);

        if ($followupNote !== '' || $oldStatus !== $status || $nextFollowupDate !== ($enquiry['next_followup_date'] ?? null)) {
            $note = $followupNote !== ''
                ? $followupNote
                : ('Status updated to ' . ($statuses[$status] ?? $status));

            $pdo->prepare(
                'INSERT INTO enquiry_followups (enquiry_id, note, status_from, status_to, next_followup_date, created_by)
                 VALUES (?, ?, ?, ?, ?, ?)'
            )->execute([
                $id,
                $note,
                $oldStatus,
                $status,
                $nextFollowupDate,
                $user['name'] ?? $user['username'] ?? 'Admin',
            ]);
        }

        $pdo->commit();
        flash_set('success', 'Follow-up saved successfully.');
        redirect('/admin/enquiries/view.php?id=' . $id);
    } catch (Throwable $ex) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }
        $error = $ex->getMessage();
        $enquiry = array_merge($enquiry, $_POST);
    }
}

$followups = get_enquiry_followups($id);
$pageTitle = 'Follow-up: ' . $enquiry['name'];
$activeNav = 'enquiries';
require __DIR__ . '/../includes/header.php';
?>
<?php if ($error): ?><div class="flash flash-error"><?= e($error) ?></div><?php endif; ?>

<div class="form-grid" style="align-items:start;">
  <div class="admin-card">
    <h2 style="margin:0 0 0.8rem;font-size:1.05rem;">Lead details</h2>
    <div class="lead-box">
      <div><span class="help">Name</span><strong><?= e($enquiry['name']) ?></strong></div>
      <div><span class="help">Phone</span><strong><?= e((string) $enquiry['phone']) ?></strong>
        <?php if (!empty($enquiry['phone']) && (auth_can('enquiries.call') || auth_can('enquiries.whatsapp'))): ?>
          <div class="actions" style="margin-top:0.35rem;">
            <?php if (auth_can('enquiries.call')): ?>
              <a class="btn btn-sm btn-outline" href="tel:<?= e((string) $enquiry['phone']) ?>">Call</a>
            <?php endif; ?>
            <?php if (auth_can('enquiries.whatsapp')): ?>
              <a class="btn btn-sm btn-outline" target="_blank" href="https://wa.me/<?= e(preg_replace('/\D+/', '', (string) $enquiry['phone'])) ?>">WhatsApp</a>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>
      <div><span class="help">Email</span><strong><?= e((string) ($enquiry['email'] ?: '—')) ?></strong></div>
      <div><span class="help">Destination</span><strong><?= e((string) ($enquiry['destination'] ?: '—')) ?></strong></div>
      <div><span class="help">Travellers</span><strong><?= e(enquiry_party_label($enquiry) !== '' ? enquiry_party_label($enquiry) : '—') ?></strong></div>
      <div class="full"><span class="help">Customer message</span><p style="margin:0.2rem 0 0;"><?= nl2br(e((string) ($enquiry['message'] ?: '—'))) ?></p></div>
      <div><span class="help">Received</span><strong><?= e(date('d M Y H:i', strtotime((string) $enquiry['created_at']))) ?></strong></div>
      <div><span class="help">Last contacted</span><strong><?= !empty($enquiry['last_contacted_at']) ? e(date('d M Y H:i', strtotime((string) $enquiry['last_contacted_at']))) : 'Never' ?></strong></div>
    </div>
  </div>

  <?php if (auth_can('enquiries.edit')): ?>
  <form class="admin-card" method="post">
    <h2 style="margin:0 0 0.8rem;font-size:1.05rem;">Update follow-up</h2>
    <div class="form-grid">
      <div class="form-group">
        <label>Status</label>
        <select name="status" required>
          <?php foreach ($statuses as $key => $label): ?>
            <option value="<?= e($key) ?>" <?= ($enquiry['status'] ?? '') === $key ? 'selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Priority</label>
        <select name="priority">
          <?php foreach ($priorities as $key => $label): ?>
            <option value="<?= e($key) ?>" <?= ($enquiry['priority'] ?? 'medium') === $key ? 'selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Next follow-up date</label>
        <input type="date" name="next_followup_date" value="<?= e((string) ($enquiry['next_followup_date'] ?? '')) ?>">
      </div>
      <div class="form-group">
        <label>Adults</label>
        <input type="number" name="adults" min="0" max="99" value="<?= e(isset($enquiry['adults']) && $enquiry['adults'] !== null && $enquiry['adults'] !== '' ? (string) (int) $enquiry['adults'] : '') ?>">
      </div>
      <div class="form-group">
        <label>Children</label>
        <input type="number" name="children" min="0" max="99" value="<?= e(isset($enquiry['children']) && $enquiry['children'] !== null && $enquiry['children'] !== '' ? (string) (int) $enquiry['children'] : '') ?>">
      </div>
      <div class="form-group">
        <label style="display:flex;align-items:center;gap:0.4rem;margin-top:1.5rem;">
          <input type="checkbox" name="mark_contacted" value="1" checked>
          Mark as contacted now
        </label>
      </div>
      <div class="form-group full">
        <label>Client response</label>
        <textarea name="client_response" placeholder="What did the client say on call / WhatsApp?"><?= e((string) ($enquiry['client_response'] ?? '')) ?></textarea>
      </div>
      <div class="form-group full">
        <label>Internal notes</label>
        <textarea name="admin_notes" placeholder="Budget, travellers, preferred hotels, reminders..."><?= e((string) ($enquiry['admin_notes'] ?? '')) ?></textarea>
      </div>
      <div class="form-group full">
        <label>Add follow-up log note</label>
        <textarea name="followup_note" placeholder="Example: Called client, shared 2 package options, will confirm by Friday."></textarea>
        <div class="help">This creates a history entry for retention tracking.</div>
      </div>
    </div>
    <div class="actions" style="margin-top:1rem;">
      <button class="btn" type="submit">Save Follow-up</button>
      <a class="btn btn-outline" href="/admin/enquiries/index.php">Back to list</a>
    </div>
  </form>
  <?php else: ?>
  <div class="admin-card">
    <a class="btn btn-outline" href="/admin/enquiries/index.php">Back to list</a>
  </div>
  <?php endif; ?>
</div>

<?php
$quotes = quotations_for_enquiry((int) $enquiry['id']);
$quoteStatuses = quotation_statuses();
?>
<div class="admin-card" style="margin-top:1rem;">
  <div style="display:flex;justify-content:space-between;gap:1rem;align-items:center;flex-wrap:wrap;">
    <h2 style="margin:0;font-size:1.05rem;">Quotations</h2>
    <?php if (auth_can('quotations.add')): ?>
      <a class="btn btn-sm" href="/admin/quotations/form.php?enquiry_id=<?= (int) $enquiry['id'] ?>">Create quotation</a>
    <?php endif; ?>
  </div>
  <p class="help">When the client is interested, build one or more options. Each option has places, hotels, a day-wise itinerary, and a price you can print or send.</p>
  <?php if (!$quotes): ?>
    <p class="help" style="margin:0;">No quotations yet for this client.</p>
  <?php else: ?>
    <table class="admin-table">
      <thead>
        <tr><th>Option</th><th>Tour</th><th>Places</th><th>Total</th><th>Received</th><th>Due</th><th>Status</th><th></th></tr>
      </thead>
      <tbody>
        <?php foreach ($quotes as $quote): ?>
          <?php
            $quotePackages = quotation_hotel_packages((int) $quote['id']);
            $stayLine = quotation_places_line($quotePackages[0]['stays'] ?? quotation_stays((int) $quote['id']));
            $packageTotals = [];
            foreach ($quotePackages as $pack) {
                $packageTotals[] = quotation_package_label($pack) . ' ' . format_quote_inr(quotation_package_total($quote, $pack));
            }
          ?>
          <tr>
            <td>Option <?= e(str_pad((string) (int) $quote['option_no'], 2, '0', STR_PAD_LEFT)) ?></td>
            <td>
              <?= e($quote['title']) ?>
              <?php if ($quote['nights'] || $quote['days']): ?>
                <div class="help"><?= e(nights_label($quote['nights'] !== null ? (int) $quote['nights'] : null, $quote['days'] !== null ? (int) $quote['days'] : null)) ?></div>
              <?php endif; ?>
            </td>
            <td><?= e($stayLine) ?></td>
            <td><?= $packageTotals ? e(implode(' · ', $packageTotals)) : e(format_quote_inr(quotation_total($quote))) ?></td>
            <?php $paid = quotation_paid_amount((int) $quote['id']); ?>
            <td><?= e(format_quote_inr($paid)) ?></td>
            <td><?= e(format_quote_inr(quotation_balance($quote, $paid))) ?></td>
            <td><span class="badge <?= e(quotation_status_badge((string) $quote['status'])) ?>"><?= e($quoteStatuses[$quote['status']] ?? $quote['status']) ?></span></td>
            <td class="actions">
              <?php if (auth_can_any(['quotations.view', 'quotations.print', 'quotations.pdf', 'quotations.whatsapp', 'quotations.payment'])): ?>
                <a class="btn btn-sm" href="/admin/quotations/print.php?id=<?= (int) $quote['id'] ?>">View</a>
              <?php endif; ?>
              <?php if (auth_can('quotations.edit')): ?>
                <a class="btn btn-outline btn-sm" href="/admin/quotations/form.php?id=<?= (int) $quote['id'] ?>">Edit</a>
              <?php endif; ?>
              <?php if (auth_can('quotations.add')): ?>
                <a class="btn btn-outline btn-sm" href="/admin/quotations/form.php?copy=<?= (int) $quote['id'] ?>">New option</a>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<div class="admin-card" style="margin-top:1rem;">
  <h2 style="margin:0 0 0.8rem;font-size:1.05rem;">Follow-up history</h2>
  <?php if (!$followups): ?>
    <p class="help" style="margin:0;">No follow-up logs yet. Save an update to start the history.</p>
  <?php else: ?>
    <div class="timeline">
      <?php foreach ($followups as $log): ?>
        <div class="timeline-item">
          <div class="timeline-meta">
            <?= e(date('d M Y H:i', strtotime((string) $log['created_at']))) ?>
            · <?= e((string) ($log['created_by'] ?: 'Admin')) ?>
            <?php if (!empty($log['status_from']) || !empty($log['status_to'])): ?>
              · <?= e($statuses[$log['status_from']] ?? (string) $log['status_from']) ?>
              → <?= e($statuses[$log['status_to']] ?? (string) $log['status_to']) ?>
            <?php endif; ?>
            <?php if (!empty($log['next_followup_date'])): ?>
              · Next: <?= e(date('d M Y', strtotime((string) $log['next_followup_date']))) ?>
            <?php endif; ?>
          </div>
          <div class="timeline-note"><?= nl2br(e($log['note'])) ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
