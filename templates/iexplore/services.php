<?php
declare(strict_types=1);
require_once __DIR__ . '/layout.php';

$profile = site_profile();
$places = array_merge(site_domestic_destinations(), site_international_destinations());
$hero = $places[0]['hero_image'] ?? null;
ix_head('About — ' . $profile['company_name']);
ix_header('about');
?>
<section class="ix-page-hero" style="background-image:url('<?= e(ix_src($hero, 'assets/images/3.jpg')) ?>')">
  <div class="ix-wrap">
    <p class="ix-kicker">About</p>
    <h1><?= e($profile['company_name']) ?></h1>
    <p><?= e($profile['tagline']) ?></p>
  </div>
</section>
<section class="ix-section">
  <div class="ix-wrap ix-split">
    <div>
      <h2 style="font-family:'Cormorant Garamond',Georgia,serif;font-weight:500;font-size:2.4rem;margin-top:0;">How we plan a trip.</h2>
      <p class="ix-lead"><?= e($profile['about']) ?></p>
      <div class="ix-reasons" style="grid-template-columns:1fr 1fr;">
        <article class="ix-reason"><h3>1. Tell us the plan</h3><p>Dates, travellers, and the places you want to see.</p></article>
        <article class="ix-reason"><h3>2. Receive a quotation</h3><p>Day-wise itinerary, hotels, and a clear package cost.</p></article>
        <article class="ix-reason"><h3>3. Adjust the option</h3><p>More than one option can be sent until the trip fits.</p></article>
        <article class="ix-reason"><h3>4. Travel with support</h3><p>The same team stays reachable on the road.</p></article>
      </div>
    </div>
    <aside class="ix-panel">
      <h3>Talk to us</h3>
      <?php if ($profile['phone'] !== ''): ?><p><a href="tel:<?= e($profile['phone_href']) ?>"><?= e($profile['phone']) ?></a></p><?php endif; ?>
      <?php if ($profile['whatsapp'] !== ''): ?><p><a href="https://wa.me/<?= e($profile['whatsapp_href']) ?>">WhatsApp <?= e($profile['whatsapp']) ?></a></p><?php endif; ?>
      <?php if ($profile['email_sales'] !== ''): ?><p><a href="mailto:<?= e($profile['email_sales']) ?>"><?= e($profile['email_sales']) ?></a></p><?php endif; ?>
      <a class="ix-btn" href="<?= e(site_href('contact.php')) ?>" style="margin-top:1rem;color:var(--navy);">Plan my trip</a>
    </aside>
  </div>
</section>
<?php ix_footer(); ?>
