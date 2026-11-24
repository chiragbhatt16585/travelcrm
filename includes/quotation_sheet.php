<?php
declare(strict_types=1);

function quotation_lines(string $text): array
{
    $lines = preg_split('/\r\n|\r|\n/', $text) ?: [];
    $out = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line !== '') {
            $out[] = $line;
        }
    }
    return $out;
}

function quotation_option_label(array $quote): string
{
    return 'Option ' . str_pad((string) (int) ($quote['option_no'] ?? 1), 2, '0', STR_PAD_LEFT);
}

function quotation_sheet_styles(): string
{
    return <<<'CSS'
.qd-desk { width: min(860px, calc(100% - 2rem)); margin: 0 auto 2.5rem; }
.qd-switch { display: flex; flex-wrap: wrap; gap: .4rem; margin: 0 0 .75rem; font-family: "Source Sans 3", "Segoe UI", sans-serif; font-size: .85rem; }
.qd-switch a { color: #333; text-decoration: none; background: #fff; border: 1px solid #d5d5d5; border-radius: 4px; padding: .3rem .65rem; }
.qd-switch a.is-on { background: #1d4c45; color: #fff; border-color: #1d4c45; }
.qd { background: #fff; color: #1a1a1a; font-family: "Source Sans 3", "Segoe UI", sans-serif; font-size: 15px; line-height: 1.45; padding: 42px 48px 36px; box-shadow: 0 1px 4px rgba(0,0,0,.08), 0 12px 32px rgba(0,0,0,.06); }
.qd * { box-sizing: border-box; }
.qd-head { display: flex; justify-content: space-between; gap: 24px; align-items: flex-start; padding-bottom: 16px; border-bottom: 3px solid #1d4c45; }
.qd-brand { display: flex; gap: 16px; align-items: flex-start; }
.qd-brand img { width: 190px; height: auto; max-height: 92px; object-fit: contain; object-position: left center; display: block; }
.qd-mark { width: 48px; height: 48px; background: #1d4c45; color: #fff; display: grid; place-items: center; font-weight: 700; letter-spacing: .04em; }
.qd-brand strong { display: block; font-size: 20px; letter-spacing: .04em; text-transform: uppercase; }
.qd-brand span { display: block; color: #555; font-size: 13px; max-width: 360px; }
.qd-doc { text-align: right; }
.qd-doc em { display: block; font-style: normal; letter-spacing: .18em; text-transform: uppercase; font-size: 12px; color: #1d4c45; font-weight: 700; }
.qd-doc b { display: block; font-size: 26px; font-weight: 700; line-height: 1.1; margin-top: 2px; }
.qd-doc span { display: block; color: #555; font-size: 13px; margin-top: 4px; }
.qd-who { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 18px; }
.qd-who h2 { margin: 0 0 4px; font-size: 11px; letter-spacing: .14em; text-transform: uppercase; color: #777; font-weight: 700; }
.qd-who p { margin: 0; }
.qd-who .name { font-size: 18px; font-weight: 700; }
.qd-title { margin: 22px 0 0; padding-top: 16px; border-top: 1px solid #e4e4e4; }
.qd-title h1 { margin: 0; font-size: 28px; line-height: 1.15; font-weight: 700; }
.qd-title .meta { margin: 6px 0 0; font-size: 16px; color: #333; }
.qd-title .places { margin: 4px 0 0; color: #1d4c45; font-weight: 700; }
.qd h3 { margin: 26px 0 8px; font-size: 12px; letter-spacing: .16em; text-transform: uppercase; color: #1d4c45; font-weight: 700; border-bottom: 1px solid #e4e4e4; padding-bottom: 6px; }
.qd-day { display: grid; grid-template-columns: 108px 1fr; gap: 12px; padding: 10px 0; border-bottom: 1px solid #eee; }
.qd-day:last-child { border-bottom: 0; }
.qd-day .when { font-weight: 700; color: #1d4c45; }
.qd-day .when small { display: block; font-weight: 600; color: #777; font-size: 12px; }
.qd-day strong { display: block; font-size: 16px; }
.qd-day p { margin: 3px 0 0; color: #333; }
.qd table { width: 100%; border-collapse: collapse; }
.qd th, .qd td { text-align: left; padding: 8px 10px; border: 1px solid #e0e0e0; vertical-align: top; }
.qd th { background: #1d4c45; color: #fff; font-size: 12px; letter-spacing: .06em; text-transform: uppercase; font-weight: 700; }
.qd-cost { margin-top: 8px; border: 1px solid #e0e0e0; }
.qd-cost div { display: flex; justify-content: space-between; gap: 16px; padding: 8px 12px; border-bottom: 1px solid #eee; }
.qd-cost div:last-child { border-bottom: 0; }
.qd-cost .sum { background: #f4f7f6; font-size: 18px; font-weight: 700; }
.qd-pack { margin-top: 14px; border: 1px solid #e0e0e0; padding: 12px 14px 14px; }
.qd-pack h4 { margin: 0 0 8px; font-size: 16px; color: #1d4c45; }
.qd-pack.is-picked { border-color: #1d4c45; box-shadow: inset 0 0 0 1px #1d4c45; }
.qd-pack-picked { display: inline-block; margin-left: 8px; font-size: 11px; letter-spacing: .08em; text-transform: uppercase; color: #1d4c45; }
.qd-aside { margin: 6px 0 0; color: #555; font-size: 13px; }
.qd-two { display: grid; grid-template-columns: 1fr 1fr; gap: 22px; }
.qd-two ul { margin: 0; padding-left: 18px; }
.qd-two li { margin: 0 0 4px; }
.qd-note { margin: 0; white-space: pre-wrap; }
.qd ol { margin: 0; padding-left: 20px; }
.qd ol li { margin: 0 0 5px; }
.qd-bank { margin-top: 8px; }
.qd-bank td:first-child { width: 160px; font-weight: 700; color: #1d4c45; background: #f4f7f6; }
.qd-foot { margin-top: 28px; padding-top: 10px; border-top: 1px solid #e4e4e4; color: #666; font-size: 12px; }
.qd-decide { width: min(860px, calc(100% - 2rem)); margin: 0 auto 1rem; display: flex; flex-wrap: wrap; gap: .6rem; align-items: center; }
.qd-decide form { margin: 0; }
.qd-decide button { font: inherit; cursor: pointer; padding: .7rem 1rem; }
.qd-accept { background: #1d4c45; color: #fff; border: 0; }
.qd-decline { background: #fff; color: #1a1a1a; border: 1px solid #ccc; }
.qd-decided { margin: 0; color: #1d4c45; font-weight: 700; }
@media (max-width: 700px) {
  .qd { padding: 22px 16px; }
  .qd-head, .qd-who, .qd-day, .qd-two { grid-template-columns: 1fr; display: block; }
  .qd-doc { text-align: left; margin-top: 12px; }
  .qd-day .when { margin-bottom: 4px; }
}
@media print {
  .qd-switch, .qp-staff, .hz-header, .hz-footer, .hz-preview, .qd-decide { display: none !important; }
  .qd-desk { width: auto; margin: 0; }
  .qd { box-shadow: none; padding: 0; }
  .qd th, .qd-bank td:first-child, .qd-brand img, .qd-mark { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  .qd-brand img { width: 190px !important; max-height: 92px !important; display: block !important; }
}
CSS;
}

function quotation_default_terms(): string
{
    return implode("\n", [
        'This quotation is valid for 7 days from the date of issue and is subject to availability.',
        'Rates are for the hotels, room category, and travel dates mentioned. Any change may revise the price.',
        'The booking is confirmed only after the advance payment is received.',
        'Services not listed under inclusions are payable extra.',
        'The company is not liable for delays caused by weather, traffic, or other events outside its control.',
        'Government taxes, permits, or hotel rate revisions, if applicable, will be charged extra.',
    ]);
}

function quotation_default_payment(): string
{
    return implode("\n", [
        '50% of the package cost is payable in advance to confirm the booking.',
        'The balance is payable 15 days before the date of travel.',
        'Please share the payment reference after transfer so the booking can be updated.',
    ]);
}

function quotation_policy(): array
{
    $bank = array_filter([
        'Account name' => site_setting('quote_account_name'),
        'Bank' => site_setting('quote_bank_name'),
        'Account number' => site_setting('quote_account_number'),
        'IFSC' => site_setting('quote_ifsc'),
        'Branch' => site_setting('quote_branch'),
        'UPI' => site_setting('quote_upi'),
    ], static fn (string $value): bool => trim($value) !== '');

    return [
        'terms' => quotation_lines(site_setting('quote_terms', quotation_default_terms())),
        'payment' => quotation_lines(site_setting('quote_payment_terms', quotation_default_payment())),
        'bank' => $bank,
    ];
}

function quotation_sheet_document(array $quote, array $days, array $stays, array $company, bool $embedLocalImages = false, ?array $packages = null): string
{
    $option = quotation_option_label($quote);
    ob_start();
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= e($option . ' · ' . $quote['title']) ?></title>
  <style>
    <?= quotation_sheet_styles() ?>
    html, body { margin: 0; background: #fff; }
    .qd-desk { width: auto; margin: 0; }
    .qd { box-shadow: none; }
    @page { size: A4; margin: 14mm; }
  </style>
</head>
<body>
<?php render_quotation_proposal($quote, $days, $stays, $company, [], $packages); ?>
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

function render_quotation_proposal(array $quote, array $days, array $stays, array $company, array $siblings = [], ?array $packages = null): void
{
    $option = quotation_option_label($quote);
    $duration = nights_label(
        isset($quote['nights']) && $quote['nights'] !== null && $quote['nights'] !== '' ? (int) $quote['nights'] : null,
        isset($quote['days']) && $quote['days'] !== null && $quote['days'] !== '' ? (int) $quote['days'] : null
    );
    $packages ??= !empty($quote['id']) ? quotation_hotel_packages((int) $quote['id']) : [];
    if (!$packages && $stays) {
        $packages = [[
            'id' => 0,
            'code' => 'Option 1',
            'label' => 'Option 1',
            'price_per_person' => $quote['price_per_person'] ?? null,
            'child_with_bed_price' => $quote['child_with_bed_price'] ?? null,
            'child_without_bed_price' => $quote['child_without_bed_price'] ?? null,
            'stays' => $stays,
        ]];
    }
    $primaryStays = $packages[0]['stays'] ?? $stays;
    $places = quotation_places_line($primaryStays);
    $adults = (int) ($quote['adults'] ?? 0);
    $childWithBed = (int) ($quote['child_with_bed'] ?? 0);
    $childWithoutBed = (int) ($quote['child_without_bed'] ?? 0);
    $selectedId = (int) ($quote['selected_package_id'] ?? 0);
    $inclusions = quotation_lines((string) ($quote['inclusions'] ?? ''));
    $exclusions = quotation_lines((string) ($quote['exclusions'] ?? ''));
    $address = trim((string) ($company['address'] ?? ''));
    $logo = trim((string) ($company['logo'] ?? ''));
    if ($logo !== '' && !preg_match('#^(https?:)?//#i', $logo)) {
        $logo = '/' . ltrim($logo, '/');
    }
    $issued = !empty($quote['updated_at']) ? date('d M Y', strtotime((string) $quote['updated_at'])) : date('d M Y');
    ?>
    <div class="qd-desk">
      <?php if (count($siblings) > 1): ?>
        <div class="qd-switch">
          <?php foreach ($siblings as $sibling): ?>
            <a class="<?= !empty($sibling['current']) ? 'is-on' : '' ?>" href="<?= e((string) $sibling['href']) ?>"><?= e(quotation_option_label($sibling)) ?></a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <article class="qd">
        <header class="qd-head">
          <div class="qd-brand">
            <?php if ($logo !== ''): ?>
              <img src="<?= e($logo) ?>" alt="<?= e($company['company_name'] ?? 'Logo') ?>">
            <?php else: ?>
              <div class="qd-mark"><?= e($company['initials'] ?? 'TW') ?></div>
            <?php endif; ?>
            <div>
              <strong><?= e($company['company_name'] ?? 'Travel World') ?></strong>
              <?php if (!empty($company['tagline'])): ?><span><?= e($company['tagline']) ?></span><?php endif; ?>
              <?php if ($address !== ''): ?><span><?= nl2br(e($address)) ?></span><?php endif; ?>
              <?php if (!empty($company['phone'])): ?><span><?= e($company['phone']) ?><?php if (!empty($company['email_sales'])): ?> · <?= e($company['email_sales']) ?><?php endif; ?></span><?php endif; ?>
            </div>
          </div>
          <div class="qd-doc">
            <em>Quotation</em>
            <b><?= e($option) ?></b>
            <span><?= e($issued) ?></span>
          </div>
        </header>

        <div class="qd-who">
          <div>
            <h2>Prepared for</h2>
            <p class="name"><?= e((string) $quote['client_name']) ?></p>
            <?php if (!empty($quote['client_phone'])): ?><p><?= e((string) $quote['client_phone']) ?></p><?php endif; ?>
            <?php if (!empty($quote['client_email'])): ?><p><?= e((string) $quote['client_email']) ?></p><?php endif; ?>
          </div>
          <div>
            <h2>Travel</h2>
            <?php if ($duration !== ''): ?><p class="name"><?= e($duration) ?></p><?php endif; ?>
            <?php if (!empty($quote['travel_date'])): ?><p>From <?= e(date('d M Y', strtotime((string) $quote['travel_date']))) ?></p><?php endif; ?>
            <?php if ($adults > 0): ?><p><?= $adults ?> adult<?= $adults === 1 ? '' : 's' ?></p><?php endif; ?>
          </div>
        </div>

        <div class="qd-title">
          <h1><?= e((string) $quote['title']) ?></h1>
          <?php if ($places !== ''): ?><p class="places"><?= e($places) ?></p><?php endif; ?>
        </div>

        <?php if ($days): ?>
          <h3>Day wise itinerary</h3>
          <?php foreach ($days as $i => $day): ?>
            <div class="qd-day">
              <div class="when">
                <?php if (!empty($day['day_date'])): ?>
                  <?= e(date('d M', strtotime((string) $day['day_date']))) ?>
                  <small>Day <?= $i + 1 ?></small>
                <?php else: ?>
                  Day <?= $i + 1 ?>
                <?php endif; ?>
              </div>
              <div>
                <strong><?= e((string) $day['heading']) ?></strong>
                <?php if (trim((string) $day['description']) !== ''): ?>
                  <p><?= nl2br(e((string) $day['description'])) ?></p>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>

        <h3>Hotel packages</h3>
        <?php if ($adults > 0 || $childWithBed > 0 || $childWithoutBed > 0): ?>
          <p class="qd-aside" style="margin-top:0;">
            Travellers:
            <?php if ($adults > 0): ?><?= $adults ?> adult<?= $adults === 1 ? '' : 's' ?><?php endif; ?>
            <?php if ($childWithBed > 0): ?><?= $adults > 0 ? ' · ' : '' ?><?= $childWithBed ?> child with bed<?= $childWithBed === 1 ? '' : 's' ?><?php endif; ?>
            <?php if ($childWithoutBed > 0): ?><?= ($adults > 0 || $childWithBed > 0) ? ' · ' : '' ?><?= $childWithoutBed ?> child without bed<?= $childWithoutBed === 1 ? '' : 's' ?><?php endif; ?>
          </p>
        <?php endif; ?>
        <?php if (!$packages): ?>
          <p class="qd-aside">Hotel packages will appear here once added.</p>
        <?php endif; ?>
        <?php foreach ($packages as $package): ?>
          <?php
            $packStays = $package['stays'] ?? [];
            $ppp = $package['price_per_person'] !== null && $package['price_per_person'] !== '' ? (float) $package['price_per_person'] : null;
            $packTotal = quotation_package_total($quote, $package);
            $isPicked = $selectedId > 0 && (int) ($package['id'] ?? 0) === $selectedId;
          ?>
          <div class="qd-pack<?= $isPicked ? ' is-picked' : '' ?>">
            <h4>
              <?= e(quotation_package_label($package)) ?>
              <?php if ($isPicked): ?><span class="qd-pack-picked">Selected</span><?php endif; ?>
            </h4>
            <?php if ($packStays): ?>
              <table>
                <thead>
                  <tr><th>Nights</th><th>City</th><th>Hotel</th><th>Meal plan</th></tr>
                </thead>
                <tbody>
                  <?php foreach ($packStays as $stay): ?>
                    <tr>
                      <td><?= e((string) ($stay['nights'] ?? '')) ?></td>
                      <td><?= e((string) $stay['city']) ?></td>
                      <td><?= e((string) $stay['hotel_name']) ?></td>
                      <td><?= e((string) ($stay['meal_plan'] ?? '')) ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            <?php endif; ?>
            <div class="qd-cost">
              <?php if ($ppp !== null): ?>
                <div><span><?= e(format_quote_inr($ppp)) ?> per person × <?= $adults ?> adult<?= $adults === 1 ? '' : 's' ?></span><span><?= e(format_quote_inr($ppp * $adults)) ?></span></div>
              <?php endif; ?>
              <?php if ($childWithBed > 0 && (float) ($package['child_with_bed_price'] ?? 0) > 0): ?>
                <div><span>Child with extra bed × <?= $childWithBed ?></span><span><?= e(format_quote_inr((float) $package['child_with_bed_price'] * $childWithBed)) ?></span></div>
              <?php elseif ($childWithBed === 0 && (float) ($package['child_with_bed_price'] ?? 0) > 0): ?>
                <div><span>Child with extra bed</span><span><?= e(format_quote_inr((float) $package['child_with_bed_price'])) ?></span></div>
              <?php endif; ?>
              <?php if ($childWithoutBed > 0 && (float) ($package['child_without_bed_price'] ?? 0) > 0): ?>
                <div><span>Child without bed × <?= $childWithoutBed ?></span><span><?= e(format_quote_inr((float) $package['child_without_bed_price'] * $childWithoutBed)) ?></span></div>
              <?php elseif ($childWithoutBed === 0 && (float) ($package['child_without_bed_price'] ?? 0) > 0): ?>
                <div><span>Child without bed</span><span><?= e(format_quote_inr((float) $package['child_without_bed_price'])) ?></span></div>
              <?php endif; ?>
              <div class="sum"><span>Total</span><span><?= $packTotal > 0 ? e(format_quote_inr($packTotal)) : 'On request' ?></span></div>
            </div>
          </div>
        <?php endforeach; ?>
        <?php if (trim((string) ($quote['room_note'] ?? '')) !== ''): ?><p class="qd-aside"><?= e((string) $quote['room_note']) ?></p><?php endif; ?>
        <?php if (trim((string) ($quote['child_free_note'] ?? '')) !== ''): ?><p class="qd-aside"><?= e((string) $quote['child_free_note']) ?></p><?php endif; ?>

        <?php if ($inclusions || $exclusions): ?>
          <div class="qd-two">
            <?php if ($inclusions): ?>
              <div>
                <h3>Inclusions</h3>
                <ul><?php foreach ($inclusions as $line): ?><li><?= e($line) ?></li><?php endforeach; ?></ul>
              </div>
            <?php endif; ?>
            <?php if ($exclusions): ?>
              <div>
                <h3>Exclusions</h3>
                <ul><?php foreach ($exclusions as $line): ?><li><?= e($line) ?></li><?php endforeach; ?></ul>
              </div>
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <?php if (trim((string) ($quote['notes'] ?? '')) !== ''): ?>
          <h3>Notes</h3>
          <p class="qd-note"><?= e((string) $quote['notes']) ?></p>
        <?php endif; ?>

        <?php $policy = quotation_policy(); ?>
        <?php if ($policy['terms']): ?>
          <h3>Terms &amp; conditions</h3>
          <ol><?php foreach ($policy['terms'] as $line): ?><li><?= e($line) ?></li><?php endforeach; ?></ol>
        <?php endif; ?>
        <?php if ($policy['payment']): ?>
          <h3>Payment terms</h3>
          <ol><?php foreach ($policy['payment'] as $line): ?><li><?= e($line) ?></li><?php endforeach; ?></ol>
        <?php endif; ?>
        <?php if ($policy['bank']): ?>
          <h3>Bank details</h3>
          <table class="qd-bank">
            <tbody>
              <?php foreach ($policy['bank'] as $label => $value): ?>
                <tr><td><?= e($label) ?></td><td><?= e($value) ?></td></tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>

        <footer class="qd-foot">
          <?= e($company['company_name'] ?? '') ?>
          <?php if (!empty($company['phone'])): ?> · <?= e($company['phone']) ?><?php endif; ?>
          <?php if (!empty($company['whatsapp'])): ?> · WhatsApp <?= e($company['whatsapp']) ?><?php endif; ?>
          <?php if (!empty($company['email_sales'])): ?> · <?= e($company['email_sales']) ?><?php endif; ?>
        </footer>
      </article>
    </div>
    <?php
}
