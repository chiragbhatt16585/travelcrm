<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/site.php';
require_once __DIR__ . '/../../includes/quotation_sheet.php';
auth_check();

$quotationId = (int) ($_POST['quotation_id'] ?? 0);
$quote = $quotationId ? get_quotation($quotationId) : null;
if (!$quote) {
    flash_set('error', 'Quotation not found.');
    redirect('/admin/quotations/index.php');
}

$user = auth_user();
$back = '/admin/quotations/print.php?id=' . (int) $quote['id'];

if (isset($_POST['delete_id'])) {
    $paymentId = (int) $_POST['delete_id'];
    db()->prepare('DELETE FROM quotation_payments WHERE id = ? AND quotation_id = ?')->execute([$paymentId, (int) $quote['id']]);
    flash_set('success', 'Payment removed.');
    redirect($back);
}

$modes = payment_modes();
$amount = (float) str_replace(',', '', trim((string) ($_POST['amount'] ?? '')));
$paidOn = trim((string) ($_POST['paid_on'] ?? ''));
$mode = (string) ($_POST['pay_mode'] ?? 'bank');
$note = trim((string) ($_POST['note'] ?? ''));

if ($amount <= 0) {
    flash_set('error', 'Enter the amount received.');
    redirect($back);
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $paidOn)) {
    flash_set('error', 'Enter the date of payment.');
    redirect($back);
}
if (!isset($modes[$mode])) {
    $mode = 'bank';
}

db()->prepare(
    'INSERT INTO quotation_payments (quotation_id, amount, paid_on, pay_mode, txn_no, note) VALUES (?, ?, ?, ?, ?, ?)'
)->execute([
    (int) $quote['id'],
    $amount,
    $paidOn,
    $mode,
    'pending',
    $note !== '' ? $note : null,
]);
$paymentId = (int) db()->lastInsertId();
$txnNo = payment_txn_no($paymentId, $paidOn);
db()->prepare('UPDATE quotation_payments SET txn_no = ? WHERE id = ?')->execute([$txnNo, $paymentId]);

$option = quotation_option_label($quote);
db()->prepare(
    'INSERT INTO enquiry_followups (enquiry_id, note, created_by) VALUES (?, ?, ?)'
)->execute([
    (int) $quote['enquiry_id'],
    'Payment ' . $txnNo . ' of ' . format_quote_inr($amount) . ' received by ' . strtolower($modes[$mode]) . ' for ' . $option . ': ' . $quote['title'],
    (string) ($user['name'] ?? $user['username'] ?? 'Admin'),
]);

flash_set('success', 'Payment saved. Balance due is updated on this quotation.');
redirect($back);
