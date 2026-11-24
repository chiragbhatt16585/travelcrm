<?php
declare(strict_types=1);
require_once __DIR__ . '/layout.php';

$pkgId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$pkg = $pkgId > 0 ? get_package_by_id($pkgId) : null;
if (!$pkg || !(int) $pkg['is_active']) {
    $fallback = db()->query('SELECT id FROM packages WHERE is_active = 1 ORDER BY id ASC LIMIT 1')->fetch();
    $pkg = $fallback ? get_package_by_id((int) $fallback['id']) : null;
}
if (!$pkg) {
    http_response_code(404);
    echo 'Package not found.';
    exit;
}
$details = get_package_details((int) $pkg['id']);
$hero = ix_src($pkg['hero_image'] ?: null, 'assets/images/1.jpg');
if (($pkg['hero_image'] ?? '') === '' || $pkg['hero_image'] === null) {
    $destRow = get_destination_by_slug((string) $pkg['destination_slug']);
    if ($destRow && !empty($destRow['hero_image'])) {
        $hero = ix_src($destRow['hero_image'], $hero);
    }
}
$profile = site_profile();
$wa = trim((string) ($profile['whatsapp_href'] ?? ''));
$waHref = $wa !== '' ? 'https://wa.me/' . $wa . '?text=' . rawurlencode('Hi ' . $profile['company_name'] . ', I am interested in ' . $pkg['title']) : '';
ix_head($pkg['title'] . ' — ' . $profile['company_name']);
ix_header('packages');
?>
<section class="ix-page-hero" style="background-image:url('<?= e($hero) ?>')">
  <div class="ix-wrap">
    <div class="ix-crumbs">
      <a href="<?= e(site_href('index.php')) ?>">Home</a> /
      <a href="<?= e(site_href('destination.php?dest=' . urlencode((string) $pkg['destination_slug']))) ?>"><?= e($pkg['destination_name']) ?></a>
    </div>
    <p class="ix-kicker"><?= e((string) ($pkg['package_type'] ?: $pkg['destination_name'])) ?></p>
    <h1><?= e($pkg['title']) ?></h1>
    <p><?= e(nights_label($pkg['nights'] !== null ? (int) $pkg['nights'] : null, $pkg['days'] !== null ? (int) $pkg['days'] : null)) ?><?php if (!empty($pkg['route'])): ?> · <?= e((string) $pkg['route']) ?><?php endif; ?></p>
  </div>
</section>
<section class="ix-section">
  <div class="ix-wrap ix-split">
    <div>
      <h2 style="font-family:'Cormorant Garamond',Georgia,serif;font-weight:500;">Day wise itinerary</h2>
      <?php if (!$details['itinerary']): ?><p>The day plan will be shared on request.</p><?php endif; ?>
      <?php foreach ($details['itinerary'] as $row): ?>
        <div class="ix-day">
          <strong><?= e($row['day_label']) ?></strong>
          <div><?= e($row['description']) ?></div>
        </div>
      <?php endforeach; ?>

      <?php if ($details['hotel_groups']): ?>
        <h2 style="font-family:'Cormorant Garamond',Georgia,serif;font-weight:500;">Hotels</h2>
        <table class="ix-table">
          <thead><tr><th>Place</th><th>Category</th><th>Hotel</th></tr></thead>
          <tbody>
            <?php foreach ($details['hotel_groups'] as $location => $rows): ?>
              <?php foreach ($rows as $idx => $row): ?>
                <tr>
                  <?php if ($idx === 0): ?><td rowspan="<?= count($rows) ?>"><?= e((string) $location) ?></td><?php endif; ?>
                  <td><?= e($row['category']) ?></td>
                  <td><?= e($row['hotel_name']) ?></td>
                </tr>
              <?php endforeach; ?>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>

      <?php if ($details['inclusions'] || $details['exclusions']): ?>
        <div class="ix-grid" style="margin-top:1.4rem;">
          <?php if ($details['inclusions']): ?>
            <div class="ix-panel"><h3>Included</h3><ul><?php foreach ($details['inclusions'] as $item): ?><li><?= e($item['content']) ?></li><?php endforeach; ?></ul></div>
          <?php endif; ?>
          <?php if ($details['exclusions']): ?>
            <div class="ix-panel"><h3>Not included</h3><ul><?php foreach ($details['exclusions'] as $item): ?><li><?= e($item['content']) ?></li><?php endforeach; ?></ul></div>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
    <aside class="ix-panel">
      <p class="ix-kicker">Land package</p>
      <h2><?= e(format_inr($pkg['starting_price'] !== null ? (float) $pkg['starting_price'] : null)) ?></h2>
      <p>Per person. A full quotation with hotels and day plan can be prepared for your dates.</p>
      <div class="ix-hero-actions">
        <a class="ix-btn" href="<?= e(site_href('contact.php')) ?>" style="color:var(--navy);">Plan this trip</a>
        <?php if ($waHref !== ''): ?><a class="ix-ghost" href="<?= e($waHref) ?>" target="_blank" rel="noopener">WhatsApp</a><?php endif; ?>
      </div>
    </aside>
  </div>
</section>
<?php ix_footer(); ?>
