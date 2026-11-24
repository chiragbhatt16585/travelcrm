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
$hero = 'assets/images/1.jpg';
if (!empty($pkg['hero_image'])) {
    $hero = $pkg['hero_image'];
} else {
    $destRow = get_destination_by_slug((string) $pkg['destination_slug']);
    if ($destRow && !empty($destRow['hero_image'])) {
        $hero = $destRow['hero_image'];
    }
}
$active = ($pkg['destination_type'] ?? '') === 'international' ? 'international' : 'domestic';

hz_head($pkg['title'] . ' — ' . site_profile()['company_name']);
hz_header($active);
?>
<section class="hz-page-hero" style="background-image:url('<?= e($hero) ?>')">
  <div class="hz-wrap">
    <div class="hz-crumbs">
      <a href="<?= e(site_href('index.php')) ?>">Home</a> /
      <a href="<?= e(site_href('destination.php?dest=' . urlencode((string) $pkg['destination_slug']))) ?>"><?= e($pkg['destination_name']) ?></a>
    </div>
    <div class="hz-kicker"><?= e((string) ($pkg['package_type'] ?: 'Package')) ?></div>
    <h1><?= e($pkg['title']) ?></h1>
    <p><?= e((string) $pkg['route']) ?> · <?= e(nights_label($pkg['nights'] !== null ? (int) $pkg['nights'] : null, $pkg['days'] !== null ? (int) $pkg['days'] : null)) ?></p>
    <p style="font-family:Fraunces,serif;font-size:2rem;margin-top:0.4rem;"><?= e(format_inr($pkg['starting_price'] !== null ? (float) $pkg['starting_price'] : null)) ?> <span style="font-family:Manrope,sans-serif;font-size:0.85rem;">per person, twin sharing</span></p>
  </div>
</section>

<section class="hz-section hz">
  <div class="hz-wrap">
    <div class="hz-tabs">
      <button class="hz-tab on" type="button" data-pane="itinerary">Itinerary</button>
      <button class="hz-tab" type="button" data-pane="hotels">Hotels</button>
      <button class="hz-tab" type="button" data-pane="inclusions">Inclusions</button>
      <button class="hz-tab" type="button" data-pane="exclusions">Exclusions</button>
    </div>

    <div id="itinerary" class="hz-pane on">
      <?php if (!$details['itinerary']): ?><p>Itinerary will be shared on request.</p><?php endif; ?>
      <table>
        <tbody>
          <?php foreach ($details['itinerary'] as $row): ?>
            <tr>
              <th style="width:140px;"><?= e($row['day_label']) ?></th>
              <td><?= e($row['description']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div id="hotels" class="hz-pane">
      <?php if (!$details['hotel_groups']): ?><p>Hotel options are confirmed at booking.</p><?php endif; ?>
      <table>
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
    </div>

    <div id="inclusions" class="hz-pane">
      <ul>
        <?php foreach ($details['inclusions'] as $item): ?><li><?= e($item['content']) ?></li><?php endforeach; ?>
      </ul>
    </div>
    <div id="exclusions" class="hz-pane">
      <ul>
        <?php foreach ($details['exclusions'] as $item): ?><li><?= e($item['content']) ?></li><?php endforeach; ?>
      </ul>
    </div>

    <p style="margin-top:1.5rem;"><a class="hz-btn gold" href="<?= e(site_href('contact.php')) ?>">Ask about this package</a></p>
  </div>
</section>
<script>
document.querySelectorAll('.hz-tab').forEach(function (btn) {
  btn.addEventListener('click', function () {
    document.querySelectorAll('.hz-tab').forEach(function (b) { b.classList.remove('on'); });
    document.querySelectorAll('.hz-pane').forEach(function (p) { p.classList.remove('on'); });
    btn.classList.add('on');
    document.getElementById(btn.getAttribute('data-pane')).classList.add('on');
  });
});
</script>
<?php hz_footer(); ?>
