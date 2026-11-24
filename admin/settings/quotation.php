<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/site.php';
require_once __DIR__ . '/../../includes/quotation_sheet.php';
auth_check();

$bankFields = [
    'quote_account_name' => 'Account name',
    'quote_bank_name' => 'Bank name',
    'quote_account_number' => 'Account number',
    'quote_ifsc' => 'IFSC',
    'quote_branch' => 'Branch',
    'quote_upi' => 'UPI ID',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    site_setting_save('quote_terms', trim((string) ($_POST['quote_terms'] ?? '')));
    site_setting_save('quote_payment_terms', trim((string) ($_POST['quote_payment_terms'] ?? '')));
    foreach (array_keys($bankFields) as $key) {
        site_setting_save($key, trim((string) ($_POST[$key] ?? '')));
    }
    flash_set('success', 'Quotation terms saved. Every quotation now uses these details.');
    redirect('/admin/settings/quotation.php');
}

$terms = site_setting('quote_terms', quotation_default_terms());
$payment = site_setting('quote_payment_terms', quotation_default_payment());
$pageTitle = 'Quotation terms';
$activeNav = 'quote-terms';
require __DIR__ . '/../includes/header.php';
?>
<form class="admin-card" method="post">
  <p class="help" style="margin-top:0;">These details are common to every quotation. Write one point on each line. Leave a section blank if it should not appear.</p>
  <div class="form-grid">
    <div class="form-group full">
      <label>Terms &amp; conditions</label>
      <textarea name="quote_terms" rows="8"><?= e($terms) ?></textarea>
    </div>
    <div class="form-group full">
      <label>Payment terms</label>
      <textarea name="quote_payment_terms" rows="5"><?= e($payment) ?></textarea>
    </div>
    <?php foreach ($bankFields as $key => $label): ?>
      <div class="form-group">
        <label><?= e($label) ?></label>
        <input type="text" name="<?= e($key) ?>" value="<?= e(site_setting($key)) ?>">
      </div>
    <?php endforeach; ?>
  </div>
  <div class="actions" style="margin-top:1rem;">
    <button class="btn" type="submit">Save quotation terms</button>
  </div>
</form>
<?php require __DIR__ . '/../includes/footer.php'; ?>
