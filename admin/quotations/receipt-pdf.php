<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/site.php';
require_once __DIR__ . '/../../includes/quotation_sheet.php';
require_once __DIR__ . '/../../includes/quotation_pdf.php';
require_once __DIR__ . '/../../includes/receipt_sheet.php';
auth_check();

$id = (int) ($_GET['id'] ?? 0);
$payment = $id ? get_quotation_payment($id) : null;
if (!$payment) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Receipt not found.';
    exit;
}

try {
    $pdf = quotation_render_pdf(
        receipt_document($payment, site_profile(), true),
        receipt_filename($payment)
    );
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo $e->getMessage();
    exit;
}

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $pdf['filename'] . '"');
header('Content-Length: ' . (string) filesize($pdf['path']));
header('Cache-Control: no-store');
readfile($pdf['path']);
quotation_remove_dir($pdf['dir']);
exit;
