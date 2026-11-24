<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/site.php';
require_once __DIR__ . '/../../includes/quotation_sheet.php';
require_once __DIR__ . '/../../includes/quotation_pdf.php';
require_once __DIR__ . '/../../includes/receipt_sheet.php';
auth_check();

header('Content-Type: application/json; charset=utf-8');

$id = (int) ($_POST['id'] ?? 0);
$payment = $id ? get_quotation_payment($id) : null;
if (!$payment) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'Receipt not found.']);
    exit;
}

$phone = site_digits((string) ($payment['client_phone'] ?? ''));
if ($phone === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Add the client’s mobile number on the enquiry first.']);
    exit;
}

$option = 'Option ' . str_pad((string) (int) $payment['option_no'], 2, '0', STR_PAD_LEFT);
$message = 'Hello ' . $payment['client_name'] . ', payment receipt ' . ($payment['txn_no'] ?? '') . ' of ' . format_quote_inr((float) $payment['amount']) . ' for ' . $option . '.';
$pdf = null;

try {
    set_time_limit(60);
    $pdf = quotation_render_pdf(receipt_document($payment, site_profile(), true), receipt_filename($payment));
    quotation_send_pdf_on_whatsapp($pdf['path'], $phone, $message);
    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
} finally {
    if (is_array($pdf)) {
        quotation_remove_dir($pdf['dir']);
    }
}
exit;
