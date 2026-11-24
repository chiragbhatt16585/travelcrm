<?php
declare(strict_types=1);
require_once __DIR__ . '/layout.php';

$profile = site_profile();
$packages = ix_packages();
ix_head('Packages — ' . $profile['company_name']);
ix_header('packages');
?>
<section class="ix-list-head">
  <div class="ix-wrap">
    <p class="ix-kicker">Curated itineraries · <?= count($packages) ?> journeys</p>
    <h1>Trips, the way they should be experienced.</h1>
    <p class="ix-lead"><?= e($profile['about']) ?></p>
  </div>
</section>
<section class="ix-section" style="padding-top:0;">
  <div class="ix-wrap">
    <?php if (!$packages): ?>
      <p>Packages will appear here once they are published from the admin panel.</p>
    <?php else: ?>
      <div class="ix-grid ix-grid-3">
        <?php foreach ($packages as $pkg): ix_package_card($pkg); endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
<?php ix_footer(); ?>
