<?php
declare(strict_types=1);

function receipt_filename(array $payment): string
{
    $name = trim((string) preg_replace('/[^A-Za-z0-9._-]+/', '-', ($payment['txn_no'] ?? 'Receipt') . '-' . ($payment['client_name'] ?? 'Client')), '-');
    $name = (string) preg_replace('/-+/', '-', $name);
    return ($name !== '' ? $name : 'receipt') . '.pdf';
}

function receipt_markup(array $payment, array $company): void
{
    $logo = trim((string) ($company['logo'] ?? ''));
    if ($logo !== '' && !preg_match('#^(https?:)?//#i', $logo)) {
        $logo = '/' . ltrim($logo, '/');
    }
    $modes = payment_modes();
    $quote = [
        'adults' => $payment['adults'] ?? 0,
        'price_per_person' => $payment['price_per_person'] ?? 0,
        'child_with_bed' => $payment['child_with_bed'] ?? 0,
        'child_with_bed_price' => $payment['child_with_bed_price'] ?? 0,
        'child_without_bed' => $payment['child_without_bed'] ?? 0,
        'child_without_bed_price' => $payment['child_without_bed_price'] ?? 0,
    ];
    $total = quotation_total($quote);
    $paid = quotation_paid_through(
        (int) $payment['quotation_id'],
        (string) $payment['paid_on'],
        (int) $payment['id']
    );
    $due = quotation_balance($quote, $paid);
    $option = 'Option ' . str_pad((string) (int) ($payment['option_no'] ?? 1), 2, '0', STR_PAD_LEFT);
    ?>
  <article class="qd">
    <header class="qd-head">
      <div class="qd-brand">
        <?php if ($logo !== ''): ?>
          <img src="<?= e($logo) ?>" alt="<?= e($company['company_name'] ?? 'Logo') ?>">
        <?php else: ?>
          <div class="qd-mark"><?= e($company['initials'] ?? 'TW') ?></div>
        <?php endif; ?>
        <div>
          <strong><?= e($company['company_name'] ?? '') ?></strong>
          <?php if (!empty($company['phone'])): ?><span><?= e($company['phone']) ?></span><?php endif; ?>
        </div>
      </div>
      <div class="qd-doc">
        <em>Receipt</em>
        <b><?= e((string) ($payment['txn_no'] ?? '')) ?></b>
        <span><?= e($option) ?> · <?= e(date('d M Y', strtotime((string) $payment['paid_on']))) ?></span>
      </div>
    </header>
    <div class="qd-who">
      <div>
        <h2>Received from</h2>
        <p class="name"><?= e((string) $payment['client_name']) ?></p>
        <?php if (!empty($payment['client_phone'])): ?><p><?= e((string) $payment['client_phone']) ?></p><?php endif; ?>
      </div>
      <div>
        <h2>For</h2>
        <p class="name"><?= e((string) $payment['title']) ?></p>
        <p><?= e($modes[$payment['pay_mode']] ?? (string) $payment['pay_mode']) ?></p>
      </div>
    </div>
    <h3>Payment</h3>
    <div class="qd-cost">
      <div><span>Transaction no.</span><span><?= e((string) ($payment['txn_no'] ?? '')) ?></span></div>
      <div><span>This payment</span><span><?= e(format_quote_inr((float) $payment['amount'])) ?></span></div>
      <div><span>Quotation total</span><span><?= e(format_quote_inr($total)) ?></span></div>
      <div><span>Received up to this receipt</span><span><?= e(format_quote_inr($paid)) ?></span></div>
      <div class="sum"><span>Balance after this receipt</span><span><?= e(format_quote_inr($due)) ?></span></div>
    </div>
    <?php if (trim((string) ($payment['note'] ?? '')) !== ''): ?>
      <p class="qd-aside"><?= e((string) $payment['note']) ?></p>
    <?php endif; ?>
    <footer class="qd-foot"><?= e($company['company_name'] ?? '') ?> · Payment receipt</footer>
  </article>
    <?php
}

function receipt_document(array $payment, array $company, bool $embedLocalImages = false): string
{
    ob_start();
    $option = 'Option ' . str_pad((string) (int) ($payment['option_no'] ?? 1), 2, '0', STR_PAD_LEFT);
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Receipt <?= e($option) ?></title>
  <style>
    <?= quotation_sheet_styles() ?>
    html, body { margin: 0; background: #fff; }
    .qd { box-shadow: none; }
    @page { size: A4; margin: 14mm; }
  </style>
</head>
<body>
<?php receipt_markup($payment, $company); ?>
</body>
</html>
    <?php
    $html = (string) ob_get_clean();
    if (!$embedLocalImages) {
        return $html;
    }
    $root = dirname(__DIR__);
    return (string) preg_replace_callback(
        '#src="(/[^"]+)"#',
        static function (array $match) use ($root): string {
            $path = $root . rawurldecode($match[1]);
            if (!is_file($path)) {
                return $match[0];
            }
            $mime = mime_content_type($path) ?: 'application/octet-stream';
            return 'src="data:' . $mime . ';base64,' . base64_encode((string) file_get_contents($path)) . '"';
        },
        $html
    );
}
