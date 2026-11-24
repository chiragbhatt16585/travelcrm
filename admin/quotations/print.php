<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/site.php';
require_once __DIR__ . '/../../includes/quotation_sheet.php';
auth_check();

$id = (int) ($_GET['id'] ?? 0);
$quote = $id ? get_quotation($id) : null;
if (!$quote) {
    flash_set('error', 'Quotation not found.');
    redirect('/admin/quotations/index.php');
}
$days = quotation_days($id);
$packages = quotation_hotel_packages($id);
$stays = $packages[0]['stays'] ?? quotation_stays($id);
$company = site_profile();
$option = quotation_option_label($quote);
$waDigits = site_digits((string) ($quote['client_phone'] ?? ''));
$waMessage = 'Hello ' . $quote['client_name'] . ', please find ' . $option . ' for ' . $quote['title'] . '.';
$payments = quotation_payments($id);
$paid = quotation_paid_amount($id);
$total = quotation_total($quote);
$due = quotation_balance($quote, $paid);
$modes = payment_modes();
$statuses = quotation_statuses();
$siblings = [];
foreach (quotations_for_enquiry((int) $quote['enquiry_id']) as $row) {
    $siblings[] = [
        'href' => '/admin/quotations/print.php?id=' . (int) $row['id'],
        'option_no' => $row['option_no'],
        'title' => $row['title'],
        'current' => (int) $row['id'] === (int) $quote['id'],
        'total' => quotation_total($row),
    ];
}

$pageTitle = $option . ' · ' . $quote['title'];
$activeNav = 'quotations';
require __DIR__ . '/../includes/header.php';
?>
<style><?= quotation_sheet_styles() ?></style>
<div class="admin-card qp-staff">
  <div class="actions">
    <?php if (auth_can('enquiries')): ?>
      <a class="btn btn-outline" href="/admin/enquiries/view.php?id=<?= (int) $quote['enquiry_id'] ?>">Back to enquiry</a>
    <?php endif; ?>
    <?php if (auth_can('quotations.edit')): ?>
      <a class="btn btn-outline" href="/admin/quotations/form.php?id=<?= (int) $quote['id'] ?>">Edit</a>
    <?php endif; ?>
    <?php if (auth_can('quotations.add')): ?>
      <a class="btn btn-outline" href="/admin/quotations/form.php?copy=<?= (int) $quote['id'] ?>">New option</a>
    <?php endif; ?>
    <?php if (auth_can('quotations.print')): ?>
      <button class="btn" type="button" onclick="window.print()">Print</button>
    <?php endif; ?>
    <?php if (auth_can('quotations.pdf')): ?>
      <a class="btn" href="/admin/quotations/pdf.php?id=<?= (int) $quote['id'] ?>">Download PDF</a>
    <?php endif; ?>
    <?php if (auth_can('quotations.whatsapp') && $waDigits !== ''): ?>
      <button class="btn btn-secondary" type="button" id="send-pdf">Send PDF on WhatsApp</button>
    <?php elseif (auth_can('quotations.whatsapp')): ?>
      <span class="help">Add the client’s mobile number on the enquiry to send the PDF on WhatsApp.</span>
    <?php endif; ?>
  </div>
  <?php if (auth_can('quotations.whatsapp')): ?>
    <p class="help" id="send-pdf-note" style="margin:0.8rem 0 0;">Sends the PDF straight into this client’s WhatsApp chat. A draft is marked Sent after it goes out. Status now: <?= e($statuses[$quote['status']] ?? $quote['status']) ?>.</p>
  <?php endif; ?>
</div>
<div class="admin-card qp-staff">
  <h2 style="margin:0 0 0.8rem;font-size:1.05rem;">Payments</h2>
  <div class="stats" style="margin-bottom:1rem;">
    <div class="stat"><div class="label">Quotation total</div><div class="value"><?= e(format_quote_inr($total)) ?></div></div>
    <div class="stat"><div class="label">Received</div><div class="value"><?= e(format_quote_inr($paid)) ?></div></div>
    <div class="stat"><div class="label">Balance due</div><div class="value"><?= e(format_quote_inr($due)) ?></div></div>
  </div>
  <?php if ($payments): ?>
    <table class="admin-table">
      <thead><tr><th>Transaction no.</th><th>Date</th><th>Mode</th><th>Amount</th><th>Note</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($payments as $payment): ?>
          <tr>
            <td><?= e((string) $payment['txn_no']) ?></td>
            <td><?= e(date('d M Y', strtotime((string) $payment['paid_on']))) ?></td>
            <td><?= e($modes[$payment['pay_mode']] ?? $payment['pay_mode']) ?></td>
            <td><?= e(format_quote_inr((float) $payment['amount'])) ?></td>
            <td><?= e((string) ($payment['note'] ?? '')) ?></td>
            <td class="actions">
              <?php if (auth_can('quotations.payment')): ?>
                <a class="btn btn-sm" href="/admin/quotations/receipt.php?id=<?= (int) $payment['id'] ?>">Receipt</a>
                <form method="post" action="/admin/quotations/payment.php" style="display:inline;" onsubmit="return confirm('Remove this payment?');">
                  <input type="hidden" name="quotation_id" value="<?= (int) $quote['id'] ?>">
                  <input type="hidden" name="delete_id" value="<?= (int) $payment['id'] ?>">
                  <button class="btn btn-outline btn-sm" type="submit">Remove</button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php else: ?>
    <p class="help"><?= auth_can('quotations.payment') ? 'No payment recorded yet. The advance to confirm this option can be added here.' : 'No payment recorded yet.' ?></p>
  <?php endif; ?>
  <?php if (auth_can('quotations.payment')): ?>
  <form method="post" action="/admin/quotations/payment.php" style="margin-top:1rem;">
    <input type="hidden" name="quotation_id" value="<?= (int) $quote['id'] ?>">
    <div class="form-grid">
      <div class="form-group">
        <label>Amount received</label>
        <input type="number" name="amount" min="1" step="1" required>
      </div>
      <div class="form-group">
        <label>Date</label>
        <input type="date" name="paid_on" value="<?= e(date('Y-m-d')) ?>" required>
      </div>
      <div class="form-group">
        <label>Mode</label>
        <select name="pay_mode">
          <?php foreach ($modes as $key => $label): ?>
            <option value="<?= e($key) ?>"><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Note</label>
        <input type="text" name="note" placeholder="Advance, or balance">
      </div>
    </div>
    <div class="actions" style="margin-top:0.8rem;">
      <button class="btn" type="submit">Save payment</button>
    </div>
  </form>
  <?php endif; ?>
</div>
<?php if (auth_can('quotations.whatsapp') && $waDigits !== ''): ?>
<script>
document.getElementById('send-pdf').addEventListener('click', async function () {
  const button = this;
  const note = document.getElementById('send-pdf-note');
  button.disabled = true;
  note.textContent = 'Opening WhatsApp and attaching the PDF…';
  try {
    const body = new FormData();
    body.append('id', '<?= (int) $quote['id'] ?>');
    const response = await fetch('/admin/quotations/send-whatsapp.php', { method: 'POST', body: body, credentials: 'same-origin' });
    const data = await response.json().catch(function () { return {}; });
    if (!response.ok || !data.ok) {
      throw new Error(data.error || 'WhatsApp could not send the PDF.');
    }
    note.textContent = 'The PDF was sent in WhatsApp. This quotation is marked Sent.';
  } catch (error) {
    note.textContent = error && error.message ? error.message : 'WhatsApp could not send the PDF.';
  } finally {
    button.disabled = false;
  }
});
</script>
<?php endif; ?>
<?php render_quotation_proposal($quote, $days, $stays, $company, $siblings, $packages); ?>
<style>
.qd-desk { width: min(860px, 100%); }
@media print {
  .admin-sidebar, .admin-top, .flash, .qp-staff { display: none !important; }
  .admin-shell, .admin-main { display: block; width: auto; margin: 0; padding: 0; background: #fff; }
}
</style>
<?php require __DIR__ . '/../includes/footer.php'; ?>
