<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/site.php';
require_once __DIR__ . '/../../includes/quotation_sheet.php';
require_once __DIR__ . '/../../includes/quotation_pdf.php';
auth_check();

header('Content-Type: application/json; charset=utf-8');

$id = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);
$quote = $id ? get_quotation($id) : null;
if (!$quote) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'Quotation not found.']);
    exit;
}

$phone = site_digits((string) ($quote['client_phone'] ?? ''));
if ($phone === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Add the client’s mobile number on the enquiry first.']);
    exit;
}

$option = quotation_option_label($quote);
$message = 'Hello ' . $quote['client_name'] . ', please find ' . $option . ' for ' . $quote['title'] . '.';
$pdf = null;

try {
    set_time_limit(60);
    $pdf = quotation_create_pdf($quote);
    quotation_send_pdf_on_whatsapp($pdf['path'], $phone, $message);
    $actor = auth_user();
    quotation_mark_sent($quote, (string) ($actor['name'] ?? $actor['username'] ?? 'Admin'));
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
