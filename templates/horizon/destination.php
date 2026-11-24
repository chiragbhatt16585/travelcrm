<?php
declare(strict_types=1);

require_once __DIR__ . '/layout.php';

$slug = isset($_GET['dest']) ? strtolower(trim((string) $_GET['dest'])) : '';
$destination = $slug !== '' ? get_destination_by_slug($slug) : null;
if (!$destination) {
    $fallback = get_destinations();
    $destination = $fallback[0] ?? null;
}
if (!$destination) {
    http_response_code(404);
    echo 'No destinations found.';
    exit;
}

$packages = get_packages_by_destination((int) $destination['id']);
$hero = hz_image($destination['hero_image'] ?? null, 'assets/images/2.jpg');
$active = ($destination['type'] ?? '') === 'international' ? 'international' : 'domestic';

hz_head($destination['name'] . ' packages — ' . site_profile()['company_name']);
hz_header($active);
?>
<section class="hz-page-hero" style="background-image:url('<?= e($hero) ?>')">
  <div class="hz-wrap">
    <div class="hz-crumbs"><a href="<?= e(site_href('index.php')) ?>">Home</a> / <?= e($destination['name']) ?></div>
    <div class="hz-kicker"><?= e(ucfirst((string) $destination['type'])) ?></div>
    <h1><?= e($destination['name']) ?></h1>
    <p><?= e((string) ($destination['tagline'] ?: '')) ?></p>
  </div>
</section>

<section class="hz-section">
  <div class="hz-wrap hz-split">
    <div>
      <p style="max-width:40rem;color:var(--ink-soft);font-size:1.05rem;"><?= e((string) ($destination['description'] ?: '')) ?></p>
      <div class="hz-kicker" style="margin-top:2rem;">Packages · <?= count($packages) ?></div>
      <?php if (!$packages): ?>
        <p>No packages for this destination yet. Ask us for a custom plan.</p>
      <?php endif; ?>
      <?php foreach ($packages as $pkg): ?>
        <article class="hz-offer">
          <h3><?= e($pkg['title']) ?></h3>
          <div class="meta" style="color:var(--ink-soft);">
            <?= e(nights_label($pkg['nights'] !== null ? (int) $pkg['nights'] : null, $pkg['days'] !== null ? (int) $pkg['days'] : null)) ?>
            · <?= e((string) $pkg['route']) ?>
            · <?= e((string) $pkg['package_type']) ?>
          </div>
          <p style="font-family:Fraunces,serif;font-size:1.6rem;margin:0.4rem 0 0.8rem;">
            <?= e(format_inr($pkg['starting_price'] !== null ? (float) $pkg['starting_price'] : null)) ?>
            <span style="font-family:Manrope,sans-serif;font-size:0.8rem;color:var(--ink-soft);"> per person</span>
          </p>
          <a class="hz-btn" href="<?= e(site_href('package.php?id=' . (int) $pkg['id'])) ?>">View itinerary</a>
        </article>
      <?php endforeach; ?>
    </div>
    <aside class="hz-side">
      <div class="hz-kicker">Custom trip</div>
      <h3>Want <?= e($destination['name']) ?> shaped around your dates?</h3>
      <ul>
        <li>Budget, standard, or premium hotels</li>
        <li>Private cab or sharing transfers</li>
        <li>Flights and trains on request</li>
        <li>Support while you are travelling</li>
      </ul>
      <a class="hz-btn gold" href="<?= e(site_href('contact.php')) ?>">Request a quote</a>
    </aside>
  </div>
</section>
<?php hz_footer(); ?>
