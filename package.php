<?php
require_once __DIR__ . '/includes/site.php';
site_dispatch_template('package');

$pkgId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$pkg = $pkgId > 0 ? get_package_by_id($pkgId) : null;

if (!$pkg || !(int) $pkg['is_active']) {
  $fallback = db()->query('SELECT id FROM packages WHERE is_active = 1 ORDER BY id ASC LIMIT 1')->fetch();
  if ($fallback) {
    $pkg = get_package_by_id((int) $fallback['id']);
  }
}

if (!$pkg) {
  http_response_code(404);
  echo 'Package not found. Please add packages from the admin panel.';
  exit;
}

$details = get_package_details((int) $pkg['id']);
$dest = $pkg['destination_slug'];
$heroImage = 'assets/images/1.jpg';
if (!empty($pkg['hero_image'])) {
  $heroImage = $pkg['hero_image'];
} else {
  $destRow = get_destination_by_slug((string) $dest);
  if ($destRow && !empty($destRow['hero_image'])) {
    $heroImage = $destRow['hero_image'];
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?php echo htmlspecialchars($pkg['title']); ?> - <?= e(site_profile()['company_name']) ?></title>
  <?php include 'includes/theme-head.php'; ?>
  <style>
    /* Page-specific styles for package page */
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

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: "Poppins", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
      background: radial-gradient(circle at top left, #e0f4ff 0, #f7f9fc 50%, #ffffff 100%);
      color: var(--text);
      line-height: 1.6;
      -webkit-font-smoothing: antialiased;
    }

    a {
      text-decoration: none;
      color: inherit;
    }

    .page {
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    .container {
      width: 100%;
      max-width: 1100px;
      margin: 0 auto;
      padding: 0 1.25rem;
    }

    .topbar {
      background: #0b1120;
      color: #e5e7eb;
      font-size: 0.78rem;
    }

    .topbar-inner {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 1rem;
      padding: 0.3rem 0;
    }

    .topbar-left,
    .topbar-right {
      display: flex;
      align-items: center;
      gap: 1.1rem;
      flex-wrap: wrap;
    }

    .topbar-item {
      display: flex;
      align-items: center;
      gap: 0.4rem;
      color: #cbd5f5;
    }

    .topbar-icon {
      width: 20px;
      height: 20px;
      border-radius: 999px;
      background: rgba(30, 64, 175, 0.8);
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 0.7rem;
    }

    .topbar-social {
      display: flex;
      align-items: center;
      gap: 0.4rem;
    }

    .topbar-social a {
      width: 24px;
      height: 24px;
      border-radius: 999px;
      border: 1px solid rgba(148, 163, 184, 0.5);
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 0.75rem;
      color: #e5e7eb;
    }

    .topbar-social a:hover {
      background: #e5e7eb;
      color: #0b1120;
    }

    .navbar {
      background: #ffffff;
      border-bottom: 1px solid rgba(148, 163, 184, 0.18);
    }

    .navbar-inner {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0.8rem 0;
      gap: 1.5rem;
    }

    .brand {
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }

    .brand-logo {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: radial-gradient(circle at 30% 20%, #ffffff, #00b4d8 45%, #1b4965 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      color: #ffffff;
      font-weight: 700;
      box-shadow: 0 10px 25px rgba(15, 23, 42, 0.25);
    }

    .brand-text-main {
      font-weight: 700;
      font-size: 1.1rem;
      letter-spacing: 0.03em;
    }

    .brand-text-sub {
      font-size: 0.7rem;
      color: var(--muted);
      text-transform: uppercase;
      letter-spacing: 0.14em;
    }

    .nav-links {
      display: flex;
      align-items: center;
      gap: 1.4rem;
      font-size: 0.9rem;
      font-weight: 500;
    }

    .nav-links a {
      position: relative;
      color: #0f172a;
    }

    .nav-links a::after {
      content: "";
      position: absolute;
      left: 0;
      bottom: -5px;
      width: 0;
      height: 2px;
      border-radius: 999px;
      background: linear-gradient(90deg, var(--primary), var(--accent));
      transition: width 0.22s ease-out;
    }

    .nav-links a:hover::after,
    .nav-links a.active::after {
      width: 100%;
    }

    .btn {
      border-radius: 999px;
      padding: 0.5rem 1.25rem;
      border: none;
      cursor: pointer;
      font-size: 0.86rem;
      font-weight: 600;
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
      white-space: nowrap;
    }

    .btn-primary {
      background: linear-gradient(135deg, var(--primary), var(--accent));
      color: #ffffff;
      box-shadow: 0 10px 26px rgba(248, 113, 113, 0.32);
    }

    .btn-outline {
      background: #ffffff;
      border: 1px solid rgba(148, 163, 184, 0.55);
      color: var(--secondary);
    }

    .content {
      padding: 1.8rem 0 2.5rem;
    }

    .breadcrumb {
      font-size: 0.8rem;
      color: var(--muted);
      margin-bottom: 0.8rem;
    }

    .breadcrumb a {
      color: var(--secondary);
    }

    .pkg-hero {
      border-radius: 22px;
      overflow: hidden;
      background: #020617;
      background-image:
        linear-gradient(to top, rgba(15, 23, 42, 0.7), rgba(15, 23, 42, 0.2)),
        url('<?php echo htmlspecialchars($heroImage, ENT_QUOTES); ?>');
      background-size: cover;
      background-position: center;
      color: #e5e7eb;
      padding: 1.5rem 1.7rem 1.6rem;
      box-shadow: var(--shadow-soft);
      margin-bottom: 1.5rem;
      display: flex;
      flex-wrap: wrap;
      gap: 1.5rem;
      justify-content: space-between;
      align-items: flex-end;
    }

    .pkg-hero-main {
      max-width: 32rem;
    }

    .pkg-kicker {
      font-size: 0.78rem;
      text-transform: uppercase;
      letter-spacing: 0.18em;
      color: rgba(226, 232, 240, 0.85);
      margin-bottom: 0.3rem;
    }

    .pkg-title {
      font-size: 1.55rem;
      font-weight: 600;
      margin-bottom: 0.25rem;
    }

    .pkg-sub {
      font-size: 0.9rem;
      color: rgba(226, 232, 240, 0.95);
      margin-bottom: 0.6rem;
    }

    .pkg-meta {
      font-size: 0.8rem;
      color: rgba(226, 232, 240, 0.9);
    }

    .pkg-meta strong {
      color: #facc15;
    }

    .pkg-hero-side {
      text-align: right;
      font-size: 0.84rem;
    }

    .pkg-price {
      font-size: 1.2rem;
      font-weight: 600;
      margin-bottom: 0.15rem;
    }

    .tabs {
      display: flex;
      flex-wrap: wrap;
      gap: 0.6rem;
      margin-bottom: 1rem;
    }

    .tab-btn {
      border-radius: 999px;
      padding: 0.4rem 0.9rem;
      font-size: 0.85rem;
      border: 1px solid rgba(148, 163, 184, 0.7);
      background: #ffffff;
      cursor: pointer;
      color: var(--muted);
    }

    .tab-btn.active {
      background: linear-gradient(135deg, var(--primary), var(--accent));
      color: #ffffff;
      border-color: transparent;
    }

    .tab-content {
      border-radius: var(--radius-lg);
      background: #ffffff;
      box-shadow: var(--shadow-card);
      padding: 1rem 1.1rem;
      font-size: 0.84rem;
    }

    .tab-pane {
      display: none;
    }

    .tab-pane.active {
      display: block;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 0.4rem;
    }

    th, td {
      border: 1px solid rgba(148, 163, 184, 0.4);
      padding: 0.45rem 0.6rem;
      text-align: left;
    }

    th {
      background: #f3f4f6;
      font-weight: 600;
      font-size: 0.8rem;
    }

    td {
      font-size: 0.82rem;
    }

    ul.bullet-list {
      margin-top: 0.3rem;
      padding-left: 1rem;
    }

    ul.bullet-list li {
      margin-bottom: 0.2rem;
    }

    .footer {
      margin-top: auto;
      padding: 1.8rem 0 1.3rem;
      background: #0b1120;
      color: #cbd5f5;
      font-size: 0.78rem;
    }

    .footer-inner {
      display: flex;
      justify-content: space-between;
      gap: 0.75rem;
      flex-wrap: wrap;
      border-top: 1px solid rgba(51, 65, 85, 0.9);
      padding-top: 0.9rem;
      margin-top: 0.3rem;
      color: #64748b;
    }

    @media (max-width: 900px) {
      .navbar-inner {
        flex-direction: column;
        align-items: flex-start;
      }

      .pkg-hero {
        align-items: flex-start;
      }

      .pkg-hero-side {
        text-align: left;
      }
    }

    @media (max-width: 640px) {
      .pkg-hero {
        padding: 1.2rem 1.2rem 1.3rem;
      }
    }
  </style>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      var buttons = document.querySelectorAll('.tab-btn');
      var panes = document.querySelectorAll('.tab-pane');

      function activateTab(tab) {
        var target = tab.getAttribute('data-target');
        buttons.forEach(function (btn) {
          btn.classList.toggle('active', btn === tab);
        });
        panes.forEach(function (pane) {
          pane.classList.toggle('active', pane.id === target);
        });
      }

      buttons.forEach(function (btn) {
        btn.addEventListener('click', function () {
          activateTab(btn);
        });
      });

      if (buttons.length) {
        activateTab(buttons[0]);
      }
    });
  </script>
</head>
<body>
  <div class="page">
    <?php site_render_topbar(); ?>
    <?php site_render_nav(($pkg['destination_type'] ?? 'domestic') === 'international' ? 'international' : 'domestic'); ?>

    <main class="content">
      <div class="container">
        <div class="breadcrumb">
          <a href="<?= e(site_href('index.php')) ?>">Home</a> /
          <a href="<?= e(site_href('destination.php?dest=' . $dest)) ?>"> <?php echo htmlspecialchars($pkg['destination_name']); ?> Packages</a> /
          <?php echo htmlspecialchars($pkg['title']); ?>
        </div>

        <section class="pkg-hero">
          <div class="pkg-hero-main">
            <div class="pkg-kicker">Package Details</div>
            <h1 class="pkg-title"><?php echo htmlspecialchars($pkg['title']); ?></h1>
            <div class="pkg-sub"><?php echo htmlspecialchars((string)$pkg['route']); ?></div>
            <div class="pkg-meta">
              <span><?php echo htmlspecialchars(nights_label($pkg['nights'] !== null ? (int)$pkg['nights'] : null, $pkg['days'] !== null ? (int)$pkg['days'] : null)); ?> • <strong>Customisable</strong> as per your dates &amp; travellers.</span>
            </div>
          </div>
          <div class="pkg-hero-side">
            <div class="pkg-price"><?php echo htmlspecialchars(format_inr($pkg['starting_price'] !== null ? (float)$pkg['starting_price'] : null)); ?></div>
            <div>Per person on twin sharing<br />Starting price, subject to availability</div>
          </div>
        </section>

        <section>
          <div class="tabs">
            <button type="button" class="tab-btn" data-target="tab-itinerary">Itinerary</button>
            <button type="button" class="tab-btn" data-target="tab-hotels">Hotel Details</button>
            <button type="button" class="tab-btn" data-target="tab-inclusions">Inclusions</button>
            <button type="button" class="tab-btn" data-target="tab-exclusions">Exclusions</button>
          </div>

          <div class="tab-content">
            <div id="tab-itinerary" class="tab-pane">
              <h3 style="font-size:0.95rem; margin-bottom:0.2rem;">Day wise itinerary</h3>
              <?php if (!$details['itinerary']): ?>
                <p>Itinerary will be shared on request.</p>
              <?php else: ?>
              <table>
                <thead>
                  <tr>
                    <th style="width:20%;">Day</th>
                    <th>Plan</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($details['itinerary'] as $row): ?>
                    <tr>
                      <td><?php echo htmlspecialchars($row['day_label']); ?></td>
                      <td><?php echo htmlspecialchars($row['description']); ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
              <?php endif; ?>
            </div>

            <div id="tab-hotels" class="tab-pane">
              <h3 style="font-size:0.95rem; margin-bottom:0.2rem;">Sample hotels used</h3>
              <?php if (!$details['hotel_groups']): ?>
                <p>Hotel options will be shared at confirmation.</p>
              <?php else: ?>
              <table>
                <thead>
                  <tr>
                    <th style="width:22%;">Destination</th>
                    <th style="width:18%;">Category</th>
                    <th>Hotel/Resort Names</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($details['hotel_groups'] as $location => $rows): ?>
                    <?php $rowspan = count($rows); ?>
                    <?php foreach ($rows as $idx => $row): ?>
                      <tr>
                        <?php if ($idx === 0): ?>
                          <td rowspan="<?php echo (int) $rowspan; ?>"><?php echo htmlspecialchars($location); ?></td>
                        <?php endif; ?>
                        <td><?php echo htmlspecialchars($row['category']); ?></td>
                        <td><?php echo htmlspecialchars($row['hotel_name']); ?></td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endforeach; ?>
                </tbody>
              </table>
              <?php endif; ?>
              <p style="margin-top:0.4rem; font-size:0.78rem; color:var(--muted);">
                Final hotel names will be shared at the time of confirmation based on availability in the chosen category.
              </p>
            </div>

            <div id="tab-inclusions" class="tab-pane">
              <h3 style="font-size:0.95rem; margin-bottom:0.2rem;">Inclusions</h3>
              <ul class="bullet-list">
                <?php foreach ($details['inclusions'] as $item): ?>
                  <li><?php echo htmlspecialchars($item['content']); ?></li>
                <?php endforeach; ?>
              </ul>
            </div>

            <div id="tab-exclusions" class="tab-pane">
              <h3 style="font-size:0.95rem; margin-bottom:0.2rem;">Exclusions</h3>
              <ul class="bullet-list">
                <?php foreach ($details['exclusions'] as $item): ?>
                  <li><?php echo htmlspecialchars($item['content']); ?></li>
                <?php endforeach; ?>
              </ul>
            </div>
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

