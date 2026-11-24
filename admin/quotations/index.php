<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
auth_check();

$quotes = quotations_all();
$statuses = quotation_statuses();
$pageTitle = 'Quotations';
$activeNav = 'quotations';
require __DIR__ . '/../includes/header.php';
?>
<div class="admin-card">
  <p class="help" style="margin-top:0;">Each enquiry can have several options. Open an enquiry and choose Create quotation, or continue one from this list. Terms, payment terms, and bank details are shared by every quotation.<?php if (auth_can('quote-terms')): ?> Edit them under <a href="/admin/settings/quotation.php">Quotation terms</a>.<?php endif; ?></p>
  <?php if (!$quotes): ?>
    <p>No quotations yet. When a client is interested, open their enquiry and build Option 01.</p>
  <?php else: ?>
    <table class="admin-table">
      <thead>
        <tr>
          <th class="col-num">#</th>
          <th>Option</th>
          <th>Client</th>
          <th>Tour</th>
          <th>Total</th>
          <th>Status</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($quotes as $index => $quote): ?>
          <tr>
            <td class="col-num"><?= $index + 1 ?></td>
            <td>Option <?= e(str_pad((string) (int) $quote['option_no'], 2, '0', STR_PAD_LEFT)) ?></td>
            <td>
              <?php if (auth_can('enquiries')): ?>
                <a href="/admin/enquiries/view.php?id=<?= (int) $quote['enquiry_id'] ?>"><?= e($quote['client_name']) ?></a>
              <?php else: ?>
                <?= e($quote['client_name']) ?>
              <?php endif; ?>
              <?php if (!empty($quote['client_destination'])): ?><div class="help"><?= e($quote['client_destination']) ?></div><?php endif; ?>
            </td>
            <td><?= e($quote['title']) ?><?php if ($quote['nights'] || $quote['days']): ?><div class="help"><?= e(nights_label((int) $quote['nights'], (int) $quote['days'])) ?></div><?php endif; ?></td>
            <td><?php
              $quotePackages = quotation_hotel_packages((int) $quote['id']);
              if (count($quotePackages) > 1) {
                  $bits = [];
                  foreach ($quotePackages as $pack) {
                      $bits[] = quotation_package_label($pack) . ' ' . format_quote_inr(quotation_package_total($quote, $pack));
                  }
                  echo e(implode(' · ', $bits));
              } else {
                  echo e(format_quote_inr(quotation_total($quote)));
              }
            ?></td>
            <td><span class="badge <?= e(quotation_status_badge($quote['status'])) ?>"><?= e($statuses[$quote['status']] ?? $quote['status']) ?></span></td>
            <td class="actions">
              <?php if (auth_can_any(['quotations.view', 'quotations.print', 'quotations.pdf', 'quotations.whatsapp', 'quotations.payment'])): ?>
                <a class="btn btn-sm" href="/admin/quotations/print.php?id=<?= (int) $quote['id'] ?>">View</a>
              <?php endif; ?>
              <?php if (auth_can('quotations.edit')): ?>
                <a class="btn btn-outline btn-sm" href="/admin/quotations/form.php?id=<?= (int) $quote['id'] ?>">Edit</a>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
