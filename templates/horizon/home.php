<?php
declare(strict_types=1);

require_once __DIR__ . '/layout.php';

$domestic = site_domestic_destinations();
$international = site_international_destinations();
$featured = site_featured_package();
$slides = site_hero_slides(1);
$all = array_merge($domestic, $international);
$latest = db()->query(
    'SELECT p.id, p.title, p.nights, p.days, p.route, p.package_type, p.starting_price, p.hero_image,
            d.name AS destination_name, d.slug AS destination_slug, d.hero_image AS destination_image
     FROM packages p
     JOIN destinations d ON d.id = p.destination_id
     WHERE p.is_active = 1 AND d.is_active = 1
     ORDER BY p.sort_order ASC, p.id DESC
     LIMIT 6'
)->fetchAll();

$enquirySuccess = false;
$enquiryError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_type'] ?? '') === 'enquiry') {
    try {
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $destination = trim($_POST['destination'] ?? '');
        $message = trim($_POST['message'] ?? '');
        $travellers = trim($_POST['travellers'] ?? '');
        if ($name === '' || $phone === '') {
            throw new RuntimeException('Name and phone are required.');
        }
        if ($travellers !== '') {
            $message = trim($message . "\nTravellers: " . $travellers);
        }
        db()->prepare('INSERT INTO enquiries (name, email, phone, destination, message) VALUES (?,?,?,?,?)')
            ->execute([$name, $email ?: null, $phone, $destination ?: null, $message ?: null]);
        $enquirySuccess = true;
    } catch (Throwable $e) {
        $enquiryError = $e->getMessage();
    }
}

$heroImage = hz_image($slides[0]['hero_image'] ?? null, 'assets/images/3.jpg');
if ($featured && !empty($featured['hero_image'])) {
    $heroImage = $featured['hero_image'];
}

hz_head(site_profile()['company_name'] . ' — Journeys composed for you');
hz_header('home');
?>
<section class="hz-hero">
  <div class="hz-hero-copy">
    <div class="hz-kicker"><?= e(site_profile()['company_name']) ?></div>
    <h1>Holidays with room to breathe.</h1>
    <p>Handpicked stays, private transfers, and itineraries you can actually follow. Domestic hills, backwaters, and cities abroad — planned in one place.</p>
    <div class="hz-hero-actions">
      <a class="hz-btn gold" href="<?= e(site_href('index.php#domestic')) ?>">Explore India</a>
      <a class="hz-btn light" href="<?= e(site_href('index.php#international')) ?>">See the world</a>
    </div>
    <div class="hz-stats">
      <div><strong><?= (int) site_destination_count() ?></strong><span>Destinations</span></div>
      <div><strong><?= (int) site_package_count() ?></strong><span>Live packages</span></div>
      <div><strong>24×7</strong><span>On-trip support</span></div>
    </div>
  </div>
  <div class="hz-hero-media">
    <img src="<?= e($heroImage) ?>" alt="">
    <?php if ($featured): ?>
      <a class="hz-hero-card" href="<?= e(site_href('package.php?id=' . (int) $featured['id'])) ?>">
        <div>
          <div class="hz-kicker"><?= e($featured['destination_name']) ?></div>
          <h2><?= e($featured['title']) ?></h2>
          <p><?= e(nights_label($featured['nights'] !== null ? (int) $featured['nights'] : null, $featured['days'] !== null ? (int) $featured['days'] : null)) ?></p>
        </div>
        <strong><?= e(format_inr($featured['starting_price'] !== null ? (float) $featured['starting_price'] : null)) ?></strong>
      </a>
    <?php endif; ?>
  </div>
</section>

<?php
$sections = [
    'domestic' => ['India', 'Places we return to', 'Beaches, hills, and cities with more than one way to stay.', $domestic],
    'international' => ['Abroad', 'Further afield', 'Islands, skylines, and trips that need visas handled properly.', $international],
];
foreach ($sections as $id => [$kicker, $title, $blurb, $rows]):
?>
<section class="hz-section" id="<?= e($id) ?>">
  <div class="hz-wrap">
    <div class="hz-section-head">
      <div>
        <div class="hz-kicker"><?= e($kicker) ?></div>
        <h2><?= e($title) ?></h2>
      </div>
      <p><?= e($blurb) ?></p>
    </div>
    <?php if (!$rows): ?>
      <p>No destinations yet. Add them from the admin panel.</p>
    <?php else: ?>
      <div class="hz-mosaic">
        <?php foreach ($rows as $i => $dest): ?>
          <?php
            $price = $dest['min_package_price'] ?? $dest['starting_price'];
            $count = (int) ($dest['package_count'] ?? 0);
          ?>
          <a class="hz-tile <?= $i === 0 ? 'tall' : '' ?>" href="<?= e(site_href('destination.php?dest=' . urlencode($dest['slug']))) ?>">
            <img src="<?= e(hz_image($dest['hero_image'] ?? null, $i % 2 ? 'assets/images/2.jpg' : 'assets/images/1.jpg')) ?>" alt="">
            <div class="shade"></div>
            <div class="copy">
              <div class="hz-kicker"><?= e((string) ($dest['duration_label'] ?: $kicker)) ?></div>
              <h3><?= e($dest['name']) ?></h3>
              <p><?= e((string) ($dest['tagline'] ?: 'View packages')) ?> · <?= e(format_inr($price !== null ? (float) $price : null)) ?> · <?= $count ?> package<?= $count === 1 ? '' : 's' ?></p>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
<?php endforeach; ?>

<section class="hz-section" id="packages" style="padding-top:0;">
  <div class="hz-wrap">
    <div class="hz-section-head">
      <div>
        <div class="hz-kicker">Packages</div>
        <h2>Ready to leave.</h2>
      </div>
      <p>These come straight from the admin panel. Edit a price or itinerary and this list updates.</p>
    </div>
    <?php if (!$latest): ?>
      <p>No packages yet.</p>
    <?php endif; ?>
    <?php foreach ($latest as $pkg): ?>
      <a class="hz-package" href="<?= e(site_href('package.php?id=' . (int) $pkg['id'])) ?>">
        <img src="<?= e(hz_image($pkg['hero_image'] ?: ($pkg['destination_image'] ?? null))) ?>" alt="">
        <div>
          <div class="hz-kicker"><?= e($pkg['destination_name']) ?></div>
          <h3><?= e($pkg['title']) ?></h3>
          <div class="meta"><?= e(nights_label($pkg['nights'] !== null ? (int) $pkg['nights'] : null, $pkg['days'] !== null ? (int) $pkg['days'] : null)) ?> · <?= e((string) ($pkg['route'] ?: $pkg['package_type'])) ?></div>
        </div>
        <div class="hz-price">
          <strong><?= e(format_inr($pkg['starting_price'] !== null ? (float) $pkg['starting_price'] : null)) ?></strong>
          <span>per person</span>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
</section>

<section class="hz" id="contact">
  <div class="hz-wrap hz-band">
    <div>
      <div class="hz-kicker" style="color:#d7b48a;">Enquiry</div>
      <h2>Tell us where you want to wake up.</h2>
      <p>A planner calls back with two or three real options, not a brochure dump.</p>
    </div>
    <form method="post" action="<?= e(site_href('index.php#contact')) ?>">
      <input type="hidden" name="form_type" value="enquiry">
      <?php if ($enquirySuccess): ?><div class="hz-note">Enquiry received. We will contact you shortly.</div><?php endif; ?>
      <?php if ($enquiryError): ?><div class="hz-note bad"><?= e($enquiryError) ?></div><?php endif; ?>
      <div class="hz-row">
        <input name="name" placeholder="Full name" required>
        <input name="phone" placeholder="Phone" required>
      </div>
      <div class="hz-row">
        <input type="email" name="email" placeholder="Email">
        <select name="destination">
          <option value="">Destination</option>
          <?php foreach ($all as $dest): ?>
            <option value="<?= e($dest['name']) ?>"><?= e($dest['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <input type="number" name="travellers" min="1" placeholder="Travellers">
      <textarea name="message" placeholder="Dates, budget, or the kind of stay you want"></textarea>
      <button class="hz-btn gold" type="submit">Send enquiry</button>
    </form>
  </div>
</section>
<?php hz_footer(); ?>
