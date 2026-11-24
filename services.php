<?php
require_once __DIR__ . '/includes/site.php';
site_dispatch_template('services');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Our Services - <?= e(site_profile()['company_name']) ?></title>
  <?php require_once __DIR__ . '/includes/site.php'; ?>
  <?php include 'includes/theme-head.php'; ?>
</head>
<body>
  <div class="page">
    <?php site_render_topbar(); ?>
    <?php site_render_nav('services'); ?>

    <main class="content">
      <div class="container">
        <div class="section-header">
          <div class="section-kicker">Services</div>
          <h1 class="section-title">Everything you need for a complete holiday</h1>
          <p class="section-subtitle">
            <?= e(site_profile()['company_name']) ?> provides end-to-end travel services so you don’t have to coordinate with multiple vendors.
            From flights and visas to hotels, cars and cruises, our team handles all arrangements in one place.
          </p>
        </div>

        <section>
          <div class="services-grid">
            <article class="service-card">
              <div class="service-header">
                <div class="service-icon">✈</div>
                <div class="service-title">Flight Booking</div>
              </div>
              <div class="service-tag">Domestic &amp; International</div>
              <p>
                Best routing and fares for your tour dates with options for refundable and non-refundable tickets.
              </p>
              <ul class="service-list">
                <li>Multiple airlines compared</li>
                <li>Seat preference assistance</li>
                <li>Group bookings for families/corporates</li>
              </ul>
            </article>

            <article class="service-card">
              <div class="service-header">
                <div class="service-icon">🛳</div>
                <div class="service-title">Cruise Holidays</div>
              </div>
              <div class="service-tag">Short &amp; long sailings</div>
              <p>
                Sea and river cruise options from popular ports with carefully planned pre and post-cruise stays.
              </p>
              <ul class="service-list">
                <li>India &amp; International departure ports</li>
                <li>Cabin selection &amp; meal options</li>
                <li>Shore excursion planning</li>
              </ul>
            </article>

            <article class="service-card">
              <div class="service-header">
                <div class="service-icon">🚗</div>
                <div class="service-title">Car Rental &amp; Transfers</div>
              </div>
              <div class="service-tag">With professional drivers</div>
              <p>
                Comfortable point-to-point transfers and full day sightseeing by private AC cabs or coaches.
              </p>
              <ul class="service-list">
                <li>Airport/railway station pick-up &amp; drop</li>
                <li>Local &amp; outstation usage</li>
                <li>Well-maintained vehicles &amp; experienced drivers</li>
              </ul>
            </article>

            <article class="service-card">
              <div class="service-header">
                <div class="service-icon">🏨</div>
                <div class="service-title">Hotel &amp; Resort Booking</div>
              </div>
              <div class="service-tag">Budget to Premium</div>
              <p>
                Handpicked stays based on your budget and style, from boutique homestays to luxury resorts.
              </p>
              <ul class="service-list">
                <li>Multiple categories – Deluxe, Super Deluxe, Premium</li>
                <li>Meal plan options (CP, MAP, AP)</li>
                <li>Room selection &amp; special requests</li>
              </ul>
            </article>

            <article class="service-card">
              <div class="service-header">
                <div class="service-icon">🛂</div>
                <div class="service-title">Visa &amp; Passport Assistance</div>
              </div>
              <div class="service-tag">Documentation guidance</div>
              <p>
                Support with visa applications and documentation as per the latest embassy/consulate requirements.
              </p>
              <ul class="service-list">
                <li>Tourist visa file preparation</li>
                <li>Document checklist &amp; form filling help</li>
                <li>Appointment scheduling (where required)</li>
              </ul>
            </article>

            <article class="service-card">
              <div class="service-header">
                <div class="service-icon">🌍</div>
                <div class="service-title">Tour Packages</div>
              </div>
              <div class="service-tag">Domestic &amp; International</div>
              <p>
                Ready-made and fully customised tour packages created by our experienced travel planners.
              </p>
              <ul class="service-list">
                <li>Fixed departures &amp; private tours</li>
                <li>Tailor-made itineraries for families, couples and groups</li>
                <li>End-to-end support before and during travel</li>
              </ul>
            </article>
          </div>
        </section>
      </div>
    </main>

    <footer class="footer">
      <div class="container">
        <div class="footer-inner">
          <span>© <?php echo date('Y'); ?> <?= e(site_profile()['company_name']) ?>. All rights reserved.</span>
          <span><a href="<?= e(site_href('index.php#contact')) ?>" style="color:#e5e7eb;">Plan your trip with us</a></span>
        </div>
      </div>
    </footer>
  </div>
</body>
</html>

