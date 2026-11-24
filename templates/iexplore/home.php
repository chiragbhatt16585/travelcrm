<?php
declare(strict_types=1);
require_once __DIR__ . '/layout.php';

$profile = site_profile();
$destinations = array_merge(site_domestic_destinations(), site_international_destinations());
$packages = ix_packages(null, 8);
$heroDest = $destinations[0] ?? null;
$heroImage = ix_src($heroDest['hero_image'] ?? null, 'assets/images/3.jpg');
$focus = $heroDest['name'] ?? 'travel';
$about = trim($profile['about']);
if (strlen($about) > 280) {
    $about = rtrim(substr($about, 0, 277)) . '…';
}

ix_head($profile['company_name'] . ' — Curated journeys');
ix_header('home', true);
?>
<section class="ix-hero" style="background-image:url('<?= e($heroImage) ?>')">
  <div class="ix-hero-copy">
    <div class="ix-kicker"><?= e($profile['tagline'] !== '' ? $profile['tagline'] : $profile['company_name']) ?></div>
    <h1>Discover the magic of <em><?= e($focus) ?></em>. Curated experiences. Real adventures.</h1>
    <p><?= e($about !== '' ? $about : 'Handpicked stays, clear itineraries, and someone you can reach before, during, and after the trip.') ?></p>
    <div class="ix-hero-actions">
      <a class="ix-btn" href="<?= e(site_href('packages.php')) ?>">View packages →</a>
      <a class="ix-btn line" href="<?= e(site_href('contact.php')) ?>">Plan my trip</a>
    </div>
  </div>
</section>

<section class="ix-section">
  <div class="ix-wrap">
    <p class="ix-kicker">Why travellers choose us</p>
    <h2>Built around the journey, not a catalogue.</h2>
    <div class="ix-reasons">
      <article class="ix-reason"><h3>Local expertise</h3><p>Routes, seasons, and stays chosen by people who plan these trips every week.</p></article>
      <article class="ix-reason"><h3>Handpicked stays</h3><p>Hotels and camps are listed on the quotation, so you know where each night is spent.</p></article>
      <article class="ix-reason"><h3>Reachable support</h3><p>Call or WhatsApp before you book and while you are travelling.</p></article>
      <article class="ix-reason"><h3>Clear pricing</h3><p>Package prices and what is included are written down before you decide.</p></article>
    </div>
  </div>
</section>

<section class="ix-section" id="packages" style="padding-top:0;">
  <div class="ix-wrap">
    <p class="ix-kicker">Featured journeys · <?= count($packages) ?></p>
    <h2>Packages ready to view.</h2>
    <?php if (!$packages): ?>
      <p class="ix-lead">Packages will appear here once they are published from the admin panel.</p>
    <?php else: ?>
      <div class="ix-grid">
        <?php foreach ($packages as $pkg): ix_package_card($pkg, true); endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php if ($destinations): ?>
<section class="ix-section" id="places" style="padding-top:0;">
  <div class="ix-wrap">
    <p class="ix-kicker">Places</p>
    <h2>Where these journeys go.</h2>
    <div class="ix-places">
      <?php foreach (array_slice($destinations, 0, 4) as $dest): ?>
        <a class="ix-place" href="<?= e(site_href('destination.php?dest=' . urlencode($dest['slug']))) ?>" style="background-image:url('<?= e(ix_src($dest['hero_image'] ?? null, 'assets/images/1.jpg')) ?>')">
          <div>
            <h3><?= e($dest['name']) ?></h3>
            <?php if (!empty($dest['tagline'])): ?><p><?= e($dest['tagline']) ?></p><?php endif; ?>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="ix-band">
  <p class="ix-kicker">Ready when you are</p>
  <h2>Tell us the dates and we will shape the itinerary.</h2>
  <a class="ix-btn" href="<?= e(site_href('contact.php')) ?>">Send an inquiry</a>
</section>
<?php ix_footer(); ?>
