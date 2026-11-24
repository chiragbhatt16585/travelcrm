<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/site.php';
require_once __DIR__ . '/includes/quotation_sheet.php';

$token = trim((string) ($_GET['t'] ?? ''));
$quote = $token !== '' ? get_quotation_by_token($token) : null;
$company = site_profile();
if (!$quote) {
    http_response_code(404);
}
$days = $quote ? quotation_days((int) $quote['id']) : [];
$packages = $quote ? quotation_hotel_packages((int) $quote['id']) : [];
$stays = $packages[0]['stays'] ?? ($quote ? quotation_stays((int) $quote['id']) : []);
$siblings = [];
if ($quote) {
    foreach (quotations_for_enquiry((int) $quote['enquiry_id']) as $row) {
        $siblings[] = [
            'href' => site_href('quote.php?t=' . rawurlencode((string) $row['token'])),
            'option_no' => $row['option_no'],
            'title' => $row['title'],
            'current' => (int) $row['id'] === (int) $quote['id'],
            'total' => quotation_total($row),
        ];
    }
}
$option = $quote ? quotation_option_label($quote) : 'Quotation';
$title = ($quote ? $option . ' · ' . $quote['title'] : 'Quotation') . ' | ' . $company['company_name'];

if ($quote && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $decision = (string) ($_POST['decision'] ?? '');
    if ($decision === 'accept' || $decision === 'decline') {
        $packageId = (int) ($_POST['package_id'] ?? 0);
        quotation_record_client_decision($quote, $decision, $packageId > 0 ? $packageId : null);
        redirect(site_href('quote.php?t=' . rawurlencode($token)));
    }
}

if ($quote && isset($_GET['sheet'])) {
    header('Content-Type: text/html; charset=utf-8');
    echo quotation_sheet_document($quote, $days, $stays, $company, false, $packages);
    exit;
}

function quote_sheet_markup(?array $quote, array $days, array $stays, array $company, array $siblings, array $packages = []): void
{
    if (!$quote) {
        echo '<div class="qd-missing"><h1>Quotation not found</h1><p>This link is not valid. Please ask ' . e($company['company_name']) . ' for a fresh quotation.</p></div>';
        return;
    }
    render_quotation_decision($quote);
    render_quotation_proposal($quote, $days, $stays, $company, $siblings, $packages);
}

function render_quotation_decision(array $quote): void
{
    $status = (string) ($quote['status'] ?? '');
    $action = 'quote.php?t=' . rawurlencode((string) $quote['token']);
    $packages = quotation_hotel_packages((int) $quote['id']);
    $selected = quotation_pricing_package($quote, $packages);
    ?>
    <div class="qd-decide">
      <?php if ($status === 'accepted'): ?>
        <p class="qd-decided">You accepted this quotation<?= $selected ? ' · ' . e(quotation_package_label($selected)) : '' ?>. We will confirm the booking with you.</p>
      <?php elseif ($status === 'rejected'): ?>
        <p class="qd-decided">You declined this quotation. Ask us if you would like another option.</p>
      <?php else: ?>
        <?php if (count($packages) > 1): ?>
          <?php foreach ($packages as $package): ?>
            <form method="post" action="<?= e(site_href($action)) ?>">
              <input type="hidden" name="decision" value="accept">
              <input type="hidden" name="package_id" value="<?= (int) $package['id'] ?>">
              <button class="qd-accept" type="submit">Accept <?= e(quotation_package_label($package)) ?> · <?= e(format_quote_inr(quotation_package_total($quote, $package))) ?></button>
            </form>
          <?php endforeach; ?>
        <?php else: ?>
          <form method="post" action="<?= e(site_href($action)) ?>">
            <input type="hidden" name="decision" value="accept">
            <?php if ($packages): ?>
              <input type="hidden" name="package_id" value="<?= (int) $packages[0]['id'] ?>">
            <?php endif; ?>
            <button class="qd-accept" type="submit">Accept this quotation</button>
          </form>
        <?php endif; ?>
        <form method="post" action="<?= e(site_href($action)) ?>">
          <input type="hidden" name="decision" value="decline">
          <button class="qd-decline" type="submit">Decline</button>
        </form>
      <?php endif; ?>
    </div>
    <?php
}

if (site_active_template() === 'iexplore') {
    require __DIR__ . '/templates/iexplore/quote.php';
    exit;
}

if (site_active_template() === 'horizon') {
    require_once __DIR__ . '/templates/horizon/layout.php';
    hz_head($title);
    echo '<style>' . quotation_sheet_styles() . '.qd-desk{padding:1.5rem 0 2.5rem;} .qd-missing{width:min(720px,calc(100% - 2rem));margin:3rem auto;background:#fff;padding:1.4rem;}</style>';
    hz_header('');
    quote_sheet_markup($quote, $days, $stays, $company, $siblings, $packages);
    hz_footer();
    return;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($title) ?></title>
  <?php include __DIR__ . '/includes/theme-head.php'; ?>
  <style>
    <?= quotation_sheet_styles() ?>
    .qd-desk { padding: 1.2rem 0 2rem; }
    .qd-missing { max-width: 720px; margin: 2rem auto; background: #fff; padding: 1.4rem; border-radius: 12px; }
    @media print {
      .topbar, .navbar, .footer, .mobile-menu { display: none !important; }
    }
  </style>
</head>
<body>
  <div class="page">
    <?php site_render_topbar(); ?>
    <?php site_render_nav(''); ?>
    <main class="content">
      <?php quote_sheet_markup($quote, $days, $stays, $company, $siblings, $packages); ?>
    </main>
    <footer class="footer">
      <div class="container">
        <div class="footer-inner">
          <span>© <?= date('Y') ?> <?= e($company['company_name']) ?>. All rights reserved.</span>
          <span><a href="<?= e(site_href('contact.php')) ?>">Contact</a></span>
        </div>
      </div>
    </footer>
  </div>
</body>
</html>
