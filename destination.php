<?php
require_once __DIR__ . '/includes/site.php';
site_dispatch_template('destination');

$slug = isset($_GET['dest']) ? strtolower(trim($_GET['dest'])) : '';
$destination = $slug !== '' ? get_destination_by_slug($slug) : null;

if (!$destination) {
  $fallback = get_destinations();
  $destination = $fallback[0] ?? null;
}

if (!$destination) {
  http_response_code(404);
  echo 'No destinations found. Please add destinations from the admin panel.';
  exit;
}

$slug = $destination['slug'];
$packages = get_packages_by_destination((int) $destination['id']);
$heroImage = !empty($destination['hero_image']) ? $destination['hero_image'] : 'assets/images/1.jpg';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= e(site_profile()['company_name']) ?> Packages - <?= e(site_profile()['company_name']) ?></title>
  <?php include 'includes/theme-head.php'; ?>
  <style>
    /* Page-specific styles for destination page */
    :root {
      --primary: #ff6b35;
      --primary-dark: #e55a26;
      --secondary: #1b4965;
      --accent: #00b4d8;
      --bg: #f7f9fc;
      --text: #1b1f3b;
      --muted: #6c7280;
      --radius-lg: 16px;
      --radius-md: 12px;
      --shadow-soft: 0 18px 40px rgba(15, 23, 42, 0.16);
      --shadow-card: 0 12px 30px rgba(15, 23, 42, 0.12);
    }

    .breadcrumb {
      font-size: 0.8rem;
      color: var(--muted);
      margin-bottom: 0.8rem;
    }

    .breadcrumb a {
      color: var(--secondary);
    }

    .dest-hero {
      border-radius: 22px;
      overflow: hidden;
      background: #020617;
      background-image:
        linear-gradient(to top, rgba(15, 23, 42, 0.7), rgba(15, 23, 42, 0.2)),
        url('<?php echo htmlspecialchars($heroImage, ENT_QUOTES); ?>');
      background-size: cover;
      background-position: center;
      color: #e5e7eb;
      padding: 1.6rem 1.7rem 1.8rem;
      box-shadow: var(--shadow-soft);
      margin-bottom: 1.8rem;
    }

    .dest-kicker {
      font-size: 0.78rem;
      text-transform: uppercase;
      letter-spacing: 0.18em;
      color: rgba(226, 232, 240, 0.85);
      margin-bottom: 0.3rem;
    }

    .dest-title {
      font-size: 1.6rem;
      font-weight: 600;
      margin-bottom: 0.3rem;
    }

    .dest-tagline {
      font-size: 0.95rem;
      color: rgba(226, 232, 240, 0.9);
      margin-bottom: 0.8rem;
    }

    .dest-desc {
      font-size: 0.86rem;
      max-width: 34rem;
      color: rgba(226, 232, 240, 0.9);
      margin-bottom: 1rem;
    }

    .dest-meta {
      display: flex;
      flex-wrap: wrap;
      gap: 1.4rem;
      font-size: 0.8rem;
      color: rgba(226, 232, 240, 0.9);
    }

    .dest-meta strong {
      display: block;
      font-size: 1rem;
      color: #facc15;
    }

    .section-header {
      display: flex;
      align-items: flex-end;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 0.8rem;
      margin-bottom: 1.3rem;
    }

    .section-title {
      font-size: 1.25rem;
      font-weight: 600;
      color: #0f172a;
    }

    .section-subtitle {
      font-size: 0.86rem;
      color: var(--muted);
      max-width: 22rem;
    }

    .packages-grid {
      display: grid;
      grid-template-columns: minmax(0, 1.9fr) minmax(0, 1fr);
      gap: 1.5rem;
      align-items: flex-start;
    }

    .package-card {
      border-radius: var(--radius-lg);
      background: #ffffff;
      box-shadow: var(--shadow-card);
      padding: 1rem 1.1rem;
      margin-bottom: 0.9rem;
      display: flex;
      flex-direction: column;
      gap: 0.35rem;
    }

    .package-title {
      font-size: 1rem;
      font-weight: 600;
      color: #0f172a;
    }

    .package-meta {
      display: flex;
      flex-wrap: wrap;
      gap: 0.6rem 1.4rem;
      font-size: 0.8rem;
      color: var(--muted);
    }

    .package-price {
      margin-top: 0.3rem;
      font-size: 0.94rem;
      font-weight: 600;
      color: var(--primary-dark);
    }

    .package-price span {
      font-size: 0.75rem;
      color: var(--muted);
      font-weight: 400;
    }

    .package-footer {
      margin-top: 0.6rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 0.6rem;
      font-size: 0.8rem;
      color: var(--muted);
    }

    .side-panel {
      border-radius: var(--radius-lg);
      background: #0b1120;
      color: #e5e7eb;
      padding: 1rem 1.1rem;
      box-shadow: var(--shadow-soft);
      font-size: 0.82rem;
    }

    .side-panel h3 {
      font-size: 0.98rem;
      margin-bottom: 0.4rem;
    }

    .side-panel ul {
      list-style: none;
      padding-left: 0;
      margin-top: 0.4rem;
      display: flex;
      flex-direction: column;
      gap: 0.25rem;
    }

    .side-panel li::before {
      content: "•";
      color: #22c55e;
      margin-right: 0.35rem;
    }


    @media (max-width: 900px) {
      .navbar-inner {
        flex-direction: column;
        align-items: flex-start;
      }

      .packages-grid {
        grid-template-columns: minmax(0, 1fr);
      }
    }

    @media (max-width: 640px) {
      .dest-hero {
        padding: 1.1rem 1.1rem 1.3rem;
      }
    }
  </style>
</head>
<body>
  <div class="page">
    <?php site_render_topbar(); ?>
    <?php site_render_nav($destination['type'] === 'international' ? 'international' : 'domestic'); ?>

    <main class="content">
      <div class="container">
        <div class="breadcrumb">
          <a href="<?= e(site_href('index.php')) ?>">Home</a> / <?php echo htmlspecialchars($destination['name']); ?> Packages
        </div>

        <section class="dest-hero">
          <div class="dest-kicker">Popular Packages</div>
          <h1 class="dest-title"><?php echo htmlspecialchars($destination['name']); ?> Tour Packages</h1>
          <div class="dest-tagline"><?php echo htmlspecialchars((string)($destination['tagline'] ?? '')); ?></div>
          <p class="dest-desc">
            <?php echo htmlspecialchars((string)($destination['description'] ?? '')); ?>
          </p>
          <div class="dest-meta">
            <div>
              <strong><?php echo count($packages); ?>+</strong>
              curated itineraries with hotel &amp; cab options.
            </div>
            <div>
              <strong>Custom</strong>
              dates, meal plans and sightseeing can be added on request.
            </div>
          </div>
        </section>

        <section>
          <div class="section-header">
            <h2 class="section-title">Choose your preferred package</h2>
            <p class="section-subtitle">
              Packages managed from the Travel World admin panel and shown live for this destination.
            </p>
          </div>

          <div class="packages-grid">
            <div>
              <?php if (!$packages): ?>
                <article class="package-card">
                  <h3 class="package-title">No packages yet</h3>
                  <div class="package-meta">
                    <span>Please check back soon or ask for a custom quote.</span>
                  </div>
                </article>
              <?php endif; ?>
              <?php foreach ($packages as $pkg): ?>
                <article class="package-card">
                  <h3 class="package-title"><?php echo htmlspecialchars($pkg['title']); ?></h3>
                  <div class="package-meta">
                    <span><?php echo htmlspecialchars(nights_label($pkg['nights'] !== null ? (int)$pkg['nights'] : null, $pkg['days'] !== null ? (int)$pkg['days'] : null)); ?></span>
                    <span><?php echo htmlspecialchars((string)$pkg['route']); ?></span>
                    <span><?php echo htmlspecialchars((string)$pkg['package_type']); ?></span>
                  </div>
                  <div class="package-price">
                    <?php echo htmlspecialchars(format_inr($pkg['starting_price'] !== null ? (float)$pkg['starting_price'] : null)); ?>
                    <span>per person (twin sharing)</span>
                  </div>
                  <div class="package-footer">
                    <span>Includes hotels, breakfast &amp; transfers.</span>
                    <button class="btn btn-outline" type="button" onclick="window.location.href='<?= e(site_href('package.php?id=' . (int) $pkg['id'])) ?>'">
                      View Details
                    </button>
                  </div>
                </article>
              <?php endforeach; ?>
            </div>

            <aside class="side-panel">
              <h3>Need a customised <?php echo htmlspecialchars($destination['name']); ?> trip?</h3>
              <p>
                Share your travel dates, starting city and number of travellers.
                Our expert will send 2–3 itinerary options within a few hours.
              </p>
              <ul>
                <li>Flexible hotels: budget, standard, premium</li>
                <li>Private cab or sharing options</li>
                <li>Flight / train ticket assistance</li>
                <li>24x7 support during the trip</li>
              </ul>
              <button class="btn btn-primary" type="button" style="margin-top:0.7rem;" onclick="window.location.href='<?= e(site_href('index.php#contact')) ?>'">
                Get Custom Quote
              </button>
            </aside>
          </div>
        </section>
      </div>
    </main>

    <footer class="footer">
      <div class="container">
        <div class="footer-inner">
          <span>© <?php echo date('Y'); ?> <?= e(site_profile()['company_name']) ?>. All rights reserved.</span>
          <span><a href="<?= e(site_href('index.php#contact')) ?>" style="color:#e5e7eb;">Back to enquiry form</a></span>
        </div>
      </div>
    </footer>
  </div>
</body>
</html>

