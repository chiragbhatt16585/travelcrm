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
$packages = ix_packages((int) $destination['id']);
$profile = site_profile();
ix_head($destination['name'] . ' packages — ' . $profile['company_name']);
ix_header('packages');
?>
<section class="ix-list-head">
  <div class="ix-wrap">
    <p class="ix-kicker"><?= e(ucfirst((string) $destination['type'])) ?> · <?= count($packages) ?> journeys</p>
    <h1><?= e($destination['name']) ?></h1>
    <p class="ix-lead"><?= e((string) ($destination['description'] ?: $destination['tagline'] ?: '')) ?></p>
  </div>
</section>
<section class="ix-section" style="padding-top:0;">
  <div class="ix-wrap">
    <?php if (!$packages): ?>
      <p>No packages for this destination yet. <a href="<?= e(site_href('contact.php')) ?>">Ask for a custom plan.</a></p>
    <?php else: ?>
      <div class="ix-grid ix-grid-3">
        <?php foreach ($packages as $pkg): ix_package_card($pkg); endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
<?php ix_footer(); ?>
