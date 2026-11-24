<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/site.php';
require_once __DIR__ . '/../../includes/quotation_sheet.php';
require_once __DIR__ . '/../../includes/receipt_sheet.php';
auth_check();

$id = (int) ($_GET['id'] ?? 0);
$payment = $id ? get_quotation_payment($id) : null;
if (!$payment) {
    flash_set('error', 'Receipt not found.');
    redirect('/admin/quotations/index.php');
}
$company = site_profile();
$waDigits = site_digits((string) ($payment['client_phone'] ?? ''));
$option = 'Option ' . str_pad((string) (int) $payment['option_no'], 2, '0', STR_PAD_LEFT);
$pageTitle = 'Receipt · ' . $option;
$activeNav = 'quotations';
require __DIR__ . '/../includes/header.php';
?>
<style><?= quotation_sheet_styles() ?></style>
<div class="admin-card qp-staff">
  <div class="actions">
    <?php if (auth_can_any(['quotations.view', 'quotations.print', 'quotations.pdf', 'quotations.whatsapp', 'quotations.payment'])): ?>
      <a class="btn btn-outline" href="/admin/quotations/print.php?id=<?= (int) $payment['quotation_id'] ?>">Back to quotation</a>
    <?php endif; ?>
    <?php if (auth_can('quotations.print')): ?>
      <button class="btn" type="button" onclick="window.print()">Print</button>
    <?php endif; ?>
    <?php if (auth_can('quotations.pdf')): ?>
      <a class="btn" href="/admin/quotations/receipt-pdf.php?id=<?= (int) $payment['id'] ?>">Download PDF</a>
    <?php endif; ?>
    <?php if (auth_can('quotations.whatsapp') && $waDigits !== ''): ?>
      <button class="btn btn-secondary" type="button" id="send-receipt">Send receipt on WhatsApp</button>
    <?php endif; ?>
  </div>
  <p class="help" id="send-receipt-note" style="margin:0.8rem 0 0;">The receipt shows this payment, the total received, and the balance still due.</p>
</div>
<?php if (auth_can('quotations.whatsapp') && $waDigits !== ''): ?>
<script>
document.getElementById('send-receipt').addEventListener('click', async function () {
  const button = this;
  const note = document.getElementById('send-receipt-note');
  button.disabled = true;
  note.textContent = 'Opening WhatsApp and attaching the receipt…';
  try {
    const body = new FormData();
    body.append('id', '<?= (int) $payment['id'] ?>');
    const response = await fetch('/admin/quotations/send-receipt.php', { method: 'POST', body: body, credentials: 'same-origin' });
    const data = await response.json().catch(function () { return {}; });
    if (!response.ok || !data.ok) {
      throw new Error(data.error || 'WhatsApp could not send the receipt.');
    }
    note.textContent = 'The receipt was sent in WhatsApp.';
  } catch (error) {
    note.textContent = error && error.message ? error.message : 'WhatsApp could not send the receipt.';
  } finally {
    button.disabled = false;
  }
});
</script>
<?php endif; ?>
<div class="qd-desk">
<?php receipt_markup($payment, $company); ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
