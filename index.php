<?php
require_once __DIR__ . '/includes/site.php';
site_dispatch_template('home');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= e(site_profile()['company_name']) ?> - Explore The World With Us</title>
  <?php
    require_once __DIR__ . '/includes/site.php';
    $domesticDestinations = site_domestic_destinations();
    $internationalDestinations = site_international_destinations();
    $featuredPackage = site_featured_package();
    $heroSlides = site_hero_slides(3);
    $destCount = site_destination_count();
    $pkgCountTotal = site_package_count();
    $allDestinations = array_merge($domesticDestinations, $internationalDestinations);
    $latestPackages = db()->query(
      'SELECT p.id, p.title, p.nights, p.days, p.route, p.package_type, p.starting_price, p.hero_image,
              d.name AS destination_name, d.slug AS destination_slug
       FROM packages p
       JOIN destinations d ON d.id = p.destination_id
       WHERE p.is_active = 1 AND d.is_active = 1
       ORDER BY p.updated_at DESC, p.id DESC
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
        db()->prepare(
          'INSERT INTO enquiries (name, email, phone, destination, message) VALUES (?,?,?,?,?)'
        )->execute([$name, $email ?: null, $phone, $destination ?: null, $message ?: null]);
        $enquirySuccess = true;
      } catch (Throwable $e) {
        $enquiryError = $e->getMessage();
      }
    }
  ?>
  <?php include 'includes/theme-head.php'; ?>
  <style>
    /* Page-specific styles for index page */
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
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: "Poppins", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
      background: radial-gradient(circle at top left, #e0f4ff 0, #f7f9fc 50%, #ffffff 100%);
      color: var(--text);
      line-height: 1.6;
      -webkit-font-smoothing: antialiased;
    }

    img {
      max-width: 100%;
      display: block;
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
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 1.25rem;
    }

    /* NAVBAR */
    .navbar {
      position: sticky;
      top: 0;
      z-index: 100;
      backdrop-filter: blur(16px);
      background: linear-gradient(to bottom, rgba(255, 255, 255, 0.95), rgba(255, 255, 255, 0.85));
      border-bottom: 1px solid rgba(148, 163, 184, 0.18);
    }

    .navbar-inner {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0.85rem 0;
      gap: 1.5rem;
    }

    /* TOP INFO BAR */
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

    img.brand-logo {
      width: auto;
      max-width: 150px;
      height: 44px;
      border-radius: 8px;
      object-fit: contain;
      background: transparent;
      box-shadow: none;
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
      gap: 1.75rem;
      font-size: 0.92rem;
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
      bottom: -6px;
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

    .nav-item {
      position: relative;
      display: flex;
      align-items: center;
      gap: 0.25rem;
    }

    .nav-item .caret {
      font-size: 0.7rem;
      color: var(--muted);
      margin-top: 1px;
    }

    .nav-item.has-submenu:hover > .submenu {
      opacity: 1;
      visibility: visible;
      transform: translateY(0);
      pointer-events: auto;
    }

    .submenu {
      position: absolute;
      top: 115%;
      left: 0;
      min-width: 210px;
      border-radius: 14px;
      background: #ffffff;
      border: 1px solid rgba(148, 163, 184, 0.35);
      box-shadow: 0 18px 40px rgba(15, 23, 42, 0.16);
      padding: 0.55rem 0.55rem 0.6rem;
      display: flex;
      flex-direction: column;
      gap: 0.15rem;
      opacity: 0;
      visibility: hidden;
      transform: translateY(4px);
      transition: opacity 0.16s ease-out, transform 0.16s ease-out, visibility 0.16s ease-out;
      z-index: 90;
    }

    .submenu-title {
      font-size: 0.72rem;
      text-transform: uppercase;
      letter-spacing: 0.16em;
      color: var(--muted);
      padding: 0.15rem 0.5rem 0.25rem;
    }

    .submenu a {
      font-size: 0.82rem;
      padding: 0.28rem 0.5rem;
      border-radius: 10px;
      color: #0f172a;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .submenu a span {
      font-size: 0.72rem;
      color: var(--muted);
    }

    .submenu a:hover {
      background: rgba(15, 23, 42, 0.03);
    }

    .nav-actions {
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }

    .nav-contact {
      display: flex;
      flex-direction: column;
      font-size: 0.7rem;
      color: var(--muted);
      line-height: 1.3;
      text-align: right;
    }

    .nav-contact strong {
      font-size: 0.85rem;
      color: var(--secondary);
    }

    .btn {
      border-radius: 999px;
      padding: 0.55rem 1.4rem;
      border: none;
      cursor: pointer;
      font-size: 0.9rem;
      font-weight: 600;
      display: inline-flex;
      align-items: center;
      gap: 0.45rem;
      transition: transform 0.15s ease-out, box-shadow 0.15s ease-out, background 0.2s ease-out, color 0.2s ease-out;
      white-space: nowrap;
    }

    .btn-primary {
      background: linear-gradient(135deg, var(--primary), var(--accent));
      color: #ffffff;
      box-shadow: 0 13px 28px rgba(248, 113, 113, 0.32);
    }

    .btn-primary:hover {
      transform: translateY(-1px);
      box-shadow: 0 14px 34px rgba(248, 113, 113, 0.42);
    }

    .btn-outline {
      background: #ffffff;
      border: 1px solid rgba(148, 163, 184, 0.55);
      color: var(--secondary);
    }

    .btn-outline:hover {
      background: rgba(15, 23, 42, 0.03);
      transform: translateY(-1px);
    }

    .btn-icon {
      width: 32px;
      height: 32px;
      border-radius: 999px;
      background: rgba(148, 163, 184, 0.18);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.1rem;
    }

    .hamburger {
      display: none;
      width: 40px;
      height: 40px;
      border-radius: 999px;
      border: 1px solid rgba(148, 163, 184, 0.6);
      background: #ffffff;
      align-items: center;
      justify-content: center;
      cursor: pointer;
    }

    .hamburger span {
      display: block;
      width: 18px;
      height: 2px;
      border-radius: 999px;
      background: #0f172a;
      position: relative;
    }

    .hamburger span::before,
    .hamburger span::after {
      content: "";
      position: absolute;
      left: 0;
      width: 18px;
      height: 2px;
      border-radius: 999px;
      background: #0f172a;
    }

    .hamburger span::before {
      top: -5px;
    }

    .hamburger span::after {
      top: 5px;
    }

    .mobile-menu {
      display: none;
      flex-direction: column;
      gap: 0.75rem;
      padding-bottom: 0.9rem;
    }

    .mobile-menu a {
      font-size: 0.95rem;
      font-weight: 500;
      padding: 0.4rem 0;
      border-bottom: 1px dashed rgba(148, 163, 184, 0.4);
    }

    /* HERO */
    .hero {
      padding: 2.5rem 0 2rem;
    }

    .hero-inner {
      display: grid;
      grid-template-columns: minmax(0, 1.35fr) minmax(0, 1.1fr);
      gap: 2.5rem;
      align-items: center;
    }

    /* HERO IMAGE SLIDER (3 big images auto-scroll) */
    .hero-slider {
      padding-top: 0.9rem;
    }

    .slider {
      position: relative;
      width: 100%;
      max-width: 1200px;
      margin: 0 auto;
      border-radius: 22px;
      overflow: hidden;
      box-shadow: 0 18px 40px rgba(15, 23, 42, 0.26);
    }

    .slider-track {
      display: flex;
      width: 300%;
      transition: transform 0.7s ease-in-out;
    }

    .slide {
      flex: 0 0 100%;
      /* Your images are ~1200x600 (2:1). Try to keep close to that ratio. */
      height: 600px;
      background-size: cover;
      background-position: center;
      position: relative;
    }

    .slide::after {
      content: "";
      position: absolute;
      inset: 0;
      background: radial-gradient(circle at top, rgba(15, 23, 42, 0.35), transparent 55%), linear-gradient(to top, rgba(15, 23, 42, 0.6), transparent 60%);
    }

    .slider-dots {
      position: absolute;
      left: 50%;
      bottom: 14px;
      transform: translateX(-50%);
      display: flex;
      gap: 0.45rem;
      z-index: 5;
    }

    .slider-dot {
      width: 9px;
      height: 9px;
      border-radius: 999px;
      border: 1px solid rgba(248, 250, 252, 0.8);
      background: rgba(15, 23, 42, 0.45);
      cursor: pointer;
      padding: 0;
    }

    .slider-dot.active {
      width: 22px;
      background: linear-gradient(90deg, var(--primary), var(--accent));
      border-color: transparent;
    }

    .hero-tagline {
      display: inline-flex;
      align-items: center;
      gap: 0.55rem;
      border-radius: 999px;
      padding: 0.25rem 0.7rem 0.25rem 0.3rem;
      background: rgba(56, 189, 248, 0.12);
      border: 1px solid rgba(56, 189, 248, 0.45);
      margin-bottom: 1rem;
      font-size: 0.8rem;
      font-weight: 500;
      color: #0369a1;
    }

    .hero-tag-dot {
      width: 26px;
      height: 26px;
      border-radius: 999px;
      background: radial-gradient(circle at 30% 20%, #e0f2fe, #0ea5e9 50%, #1d4ed8 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      color: #ffffff;
      font-size: 1rem;
      box-shadow: 0 12px 30px rgba(37, 99, 235, 0.38);
    }

    .hero-title {
      font-size: clamp(2.1rem, 3.2vw, 3rem);
      line-height: 1.1;
      margin-bottom: 1rem;
      letter-spacing: -0.03em;
      color: #020617;
    }

    .hero-title span {
      background: linear-gradient(135deg, #fb923c, #ec4899);
      -webkit-background-clip: text;
      background-clip: text;
      color: transparent;
    }

    .hero-subtitle {
      font-size: 0.98rem;
      color: var(--muted);
      max-width: 32rem;
      margin-bottom: 1.5rem;
    }

    .hero-cta {
      display: flex;
      align-items: center;
      flex-wrap: wrap;
      gap: 0.9rem;
      margin-bottom: 1.75rem;
    }

    .hero-meta {
      display: flex;
      flex-wrap: wrap;
      gap: 1.75rem;
      font-size: 0.8rem;
      color: var(--muted);
    }

    .hero-meta-item strong {
      display: block;
      font-size: 1.05rem;
      color: #0f172a;
    }

    .hero-right {
      display: grid;
      grid-template-columns: 3fr 2.25fr;
      gap: 1rem;
      align-items: stretch;
    }

    .hero-main-card {
      position: relative;
      border-radius: 24px;
      padding: 1.35rem 1.35rem 1.5rem;
      background-color: #020617;
      background-image:
        linear-gradient(to top, rgba(15, 23, 42, 0.85), rgba(15, 23, 42, 0.1)),
        url('assets/images/1.jpg');
      background-size: cover;
      background-position: center;
      color: #e5f0ff;
      box-shadow: var(--shadow-soft);
      overflow: hidden;
      min-height: 260px;
    }

    .hero-main-card::after {
      content: "";
      position: absolute;
      inset: 0;
      background-image: radial-gradient(circle at 10% 20%, rgba(248, 250, 252, 0.14) 0, transparent 40%), radial-gradient(circle at 90% 80%, rgba(14, 165, 233, 0.32) 0, transparent 40%);
      mix-blend-mode: screen;
      pointer-events: none;
    }

    .hero-main-label {
      font-size: 0.72rem;
      text-transform: uppercase;
      letter-spacing: 0.22em;
      color: rgba(226, 232, 240, 0.85);
      margin-bottom: 0.25rem;
    }

    .hero-main-destination {
      font-size: 1.2rem;
      font-weight: 600;
      margin-bottom: 0.4rem;
    }

    .hero-main-meta {
      display: flex;
      flex-wrap: wrap;
      gap: 0.5rem 1.1rem;
      font-size: 0.78rem;
      margin-bottom: 1.25rem;
    }

    .hero-main-price {
      display: inline-flex;
      align-items: baseline;
      gap: 0.25rem;
      background: rgba(15, 23, 42, 0.76);
      border-radius: 999px;
      padding: 0.35rem 0.8rem;
      font-size: 0.78rem;
    }

    .hero-main-price strong {
      font-size: 1rem;
      color: #facc15;
    }

    .hero-main-footer {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-top: 1.2rem;
      font-size: 0.75rem;
    }

    .hero-main-avatars {
      display: flex;
      align-items: center;
    }

    .hero-main-avatars span {
      width: 26px;
      height: 26px;
      border-radius: 999px;
      background: rgba(15, 23, 42, 0.85);
      border: 1px solid rgba(148, 163, 184, 0.8);
      display: block;
      margin-right: -8px;
      font-size: 0.65rem;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .hero-side {
      display: flex;
      flex-direction: column;
      gap: 0.8rem;
    }

    .mini-card {
      border-radius: 18px;
      padding: 0.9rem 0.95rem;
      background: #ffffff;
      box-shadow: var(--shadow-card);
      display: flex;
      flex-direction: column;
      gap: 0.35rem;
      font-size: 0.8rem;
    }

    .mini-title {
      font-size: 0.86rem;
      font-weight: 600;
      color: #0f172a;
    }

    .mini-badge {
      display: inline-flex;
      align-items: center;
      gap: 0.3rem;
      border-radius: 999px;
      padding: 0.2rem 0.6rem;
      font-size: 0.68rem;
      background: rgba(22, 163, 74, 0.09);
      color: #15803d;
    }

    .mini-progress {
      position: relative;
      margin-top: 0.3rem;
      height: 5px;
      border-radius: 999px;
      background: rgba(226, 232, 240, 0.9);
      overflow: hidden;
    }

    .mini-progress span {
      position: absolute;
      inset: 0;
      width: 70%;
      background: linear-gradient(90deg, #22c55e, #a3e635);
    }

    .mini-pill-row {
      display: flex;
      flex-wrap: wrap;
      gap: 0.4rem;
      margin-top: 0.3rem;
    }

    a.pill { text-decoration: none; color: inherit; }
    .pill {
      border-radius: 999px;
      padding: 0.2rem 0.6rem;
      font-size: 0.7rem;
      background: rgba(148, 163, 184, 0.16);
      color: var(--muted);
    }

    .mini-stats {
      display: flex;
      justify-content: space-between;
      font-size: 0.7rem;
      color: var(--muted);
      margin-top: 0.25rem;
    }

    /* SECTION WRAPPER */
    section {
      padding: 2.5rem 0;
    }

    .section-header {
      display: flex;
      flex-wrap: wrap;
      align-items: flex-end;
      justify-content: space-between;
      gap: 0.9rem;
      margin-bottom: 1.8rem;
    }

    .section-kicker {
      font-size: 0.78rem;
      text-transform: uppercase;
      letter-spacing: 0.18em;
      color: var(--accent);
      font-weight: 600;
      margin-bottom: 0.2rem;
    }

    .section-title {
      font-size: 1.35rem;
      font-weight: 600;
      color: #0f172a;
    }

    .section-subtitle {
      font-size: 0.9rem;
      color: var(--muted);
      max-width: 26rem;
    }

    /* TOUR GRIDS */
    .tour-grid {
      display: grid;
      grid-template-columns: repeat(4, minmax(0, 1fr));
      gap: 1.25rem;
    }

    .tour-card {
      border-radius: var(--radius-lg);
      background: #ffffff;
      box-shadow: var(--shadow-card);
      overflow: hidden;
      display: flex;
      flex-direction: column;
      min-height: 100%;
    }

    .tour-media {
      position: relative;
      padding-top: 64%;
      overflow: hidden;
    }

    .tour-media::before {
      content: "";
      position: absolute;
      inset: 0;
      background-size: cover;
      background-position: center;
      transform: scale(1.02);
      transition: transform 0.5s ease-out;
    }

    .tour-card:hover .tour-media::before {
      transform: scale(1.06);
    }

    /* Destination background images (using reference photos for now) */
    .tour-media--kerala::before {
      background-image:
        linear-gradient(to top, rgba(15, 23, 42, 0.45), rgba(15, 23, 42, 0.05)),
        url('assets/images/2.jpg');
      background-size: cover;
      background-position: center;
    }

    .tour-media--himachal::before {
      background-image: linear-gradient(135deg, #0ea5e9, #1d4ed8);
    }

    .tour-media--andaman::before {
      background-image: linear-gradient(135deg, #06b6d4, #0f766e);
    }

    .tour-media--goa::before {
      background-image: linear-gradient(135deg, #eab308, #fb923c);
    }

    .tour-media--bali::before {
      background-image:
        linear-gradient(to top, rgba(15, 23, 42, 0.45), rgba(15, 23, 42, 0.05)),
        url('assets/images/3.jpg');
      background-size: cover;
      background-position: center;
    }

    .tour-media--maldives::before {
      background-image: linear-gradient(135deg, #22c1c3, #1d4ed8);
    }

    .tour-media--dubai::before {
      background-image: linear-gradient(135deg, #facc15, #fb923c);
    }

    .tour-media--thailand::before {
      background-image: linear-gradient(135deg, #22c55e, #0ea5e9);
    }

    .tour-media--dynamic::before {
      background-image:
        linear-gradient(to top, rgba(15, 23, 42, 0.45), rgba(15, 23, 42, 0.05)),
        var(--tour-image, linear-gradient(135deg, #0ea5e9, #1d4ed8));
      background-size: cover;
      background-position: center;
    }

    .tour-chip {
      position: absolute;
      left: 0.75rem;
      top: 0.75rem;
      border-radius: 999px;
      padding: 0.25rem 0.6rem;
      font-size: 0.68rem;
      background: rgba(15, 23, 42, 0.82);
      color: #e5e7eb;
      display: inline-flex;
      align-items: center;
      gap: 0.25rem;
    }

    .tour-duration {
      position: absolute;
      right: 0.75rem;
      bottom: 0.75rem;
      border-radius: 999px;
      padding: 0.26rem 0.7rem;
      font-size: 0.7rem;
      background: rgba(15, 23, 42, 0.76);
      color: #e5e7eb;
    }

    .tour-body {
      padding: 0.9rem 0.95rem 0.95rem;
      display: flex;
      flex-direction: column;
      gap: 0.45rem;
      flex: 1;
    }

    .tour-name {
      font-size: 0.98rem;
      font-weight: 600;
      color: #0f172a;
    }

    .tour-meta-row {
      display: flex;
      justify-content: space-between;
      gap: 0.5rem;
      font-size: 0.75rem;
      color: var(--muted);
    }

    .tour-price {
      font-size: 0.9rem;
      font-weight: 600;
      color: var(--primary-dark);
    }

    .tour-price span {
      font-size: 0.7rem;
      color: var(--muted);
      font-weight: 400;
    }

    .tour-footer {
      margin-top: 0.25rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 0.5rem;
      font-size: 0.75rem;
      color: var(--muted);
    }

    .tour-footer a {
      font-size: 0.76rem;
      font-weight: 600;
      color: var(--secondary);
    }

    /* HIGHLIGHT BAND */
    .highlight-band {
      margin-top: 1.75rem;
      border-radius: var(--radius-lg);
      padding: 1rem 1.25rem;
      background: linear-gradient(90deg, rgba(37, 99, 235, 0.08), rgba(6, 182, 212, 0.08));
      border: 1px solid rgba(129, 140, 248, 0.28);
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      justify-content: space-between;
      gap: 0.75rem;
      font-size: 0.82rem;
    }

    .highlight-band strong {
      color: #0f172a;
    }

    /* TESTIMONIALS */
    .testimonial-grid {
      display: grid;
      grid-template-columns: 1.2fr 1.8fr;
      gap: 1.5rem;
      align-items: stretch;
    }

    .testimonial-intro {
      border-radius: var(--radius-lg);
      padding: 1.25rem 1.3rem;
      background: radial-gradient(circle at top left, #e0f2fe 0, #0f172a 70%);
      color: #e5e7eb;
      box-shadow: var(--shadow-soft);
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }

    .testimonial-intro h3 {
      font-size: 1.1rem;
      margin-bottom: 0.4rem;
    }

    .testimonial-intro p {
      font-size: 0.85rem;
      color: rgba(226, 232, 240, 0.85);
      max-width: 260px;
    }

    .testimonial-score {
      margin-top: 1rem;
      display: flex;
      gap: 0.9rem;
      align-items: center;
      font-size: 0.78rem;
    }

    .testimonial-score strong {
      font-size: 1.6rem;
    }

    .testimonial-list {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 1rem;
    }

    .testimonial-card {
      border-radius: var(--radius-md);
      padding: 0.9rem;
      background: #ffffff;
      box-shadow: var(--shadow-card);
      font-size: 0.8rem;
      display: flex;
      flex-direction: column;
      gap: 0.45rem;
    }

    .testimonial-name {
      font-weight: 600;
      font-size: 0.84rem;
      color: #0f172a;
    }

    .testimonial-rating {
      color: #eab308;
      font-size: 0.8rem;
    }

    /* FOOTER */
    .footer {
      margin-top: auto;
      padding: 2.5rem 0 1.75rem;
      background: #0b1120;
      color: #cbd5f5;
    }

    .footer-top {
      display: grid;
      grid-template-columns: 1.4fr repeat(3, 1fr);
      gap: 1.5rem;
      margin-bottom: 1.75rem;
      font-size: 0.82rem;
    }

    .footer-logo {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      margin-bottom: 0.8rem;
    }

    .footer-logo-text-main {
      font-weight: 600;
      font-size: 1rem;
    }

    .footer-logo-text-sub {
      font-size: 0.7rem;
      color: #64748b;
      text-transform: uppercase;
      letter-spacing: 0.15em;
    }

    .footer-heading {
      font-weight: 600;
      margin-bottom: 0.6rem;
      font-size: 0.9rem;
      color: #e5e7eb;
    }

    .footer-list {
      list-style: none;
      display: flex;
      flex-direction: column;
      gap: 0.3rem;
    }

    .footer-list a {
      color: #94a3b8;
      font-size: 0.8rem;
    }

    .footer-list a:hover {
      color: #e5e7eb;
    }

    .footer-bottom {
      padding-top: 1rem;
      border-top: 1px solid rgba(51, 65, 85, 0.9);
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      justify-content: space-between;
      gap: 0.75rem;
      font-size: 0.78rem;
      color: #64748b;
    }

    .footer-bottom a {
      color: #d1d5db;
    }

    /* RESPONSIVE */
    @media (max-width: 960px) {
      .nav-links,
      .nav-actions {
        display: none;
      }

      .hamburger {
        display: inline-flex;
      }

      .hero-inner {
        grid-template-columns: minmax(0, 1fr);
      }

      .hero-right {
        order: -1;
      }

      .hero {
        padding-top: 1.8rem;
      }

      .slide {
        height: 380px;
      }

      .tour-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
      }

      .testimonial-grid {
        grid-template-columns: minmax(0, 1fr);
      }

      .footer-top {
        grid-template-columns: repeat(2, minmax(0, 1fr));
      }
    }

    @media (max-width: 640px) {
      .hero {
        padding-top: 1.4rem;
      }

      .hero-inner {
        gap: 1.75rem;
      }

      .tour-grid {
        grid-template-columns: minmax(0, 1fr);
      }

      section {
        padding: 2.1rem 0;
      }

      .highlight-band {
        align-items: flex-start;
      }

      .footer-top {
        grid-template-columns: minmax(0, 1fr);
      }

      .slide {
        height: 260px;
      }
    }
  </style>
  <script>
    // Mobile menu toggle - simple, no framework
    function toggleMobileMenu() {
      var menu = document.getElementById('mobileMenu');
      if (!menu) return;
      if (menu.style.display === 'flex') {
        menu.style.display = 'none';
      } else {
        menu.style.display = 'flex';
      }
    }

    // Simple 3-image slider logic
    document.addEventListener('DOMContentLoaded', function () {
      var track = document.getElementById('heroSliderTrack');
      var dots = document.querySelectorAll('.slider-dot');
      if (!track || !dots.length) return;

      var currentIndex = 0;
      var total = dots.length;
      var intervalMs = 5000;
      var timer;

      function goToSlide(index) {
        currentIndex = index;
        track.style.transform = 'translateX(' + (-100 * index) + '%)';
        dots.forEach(function (dot, i) {
          dot.classList.toggle('active', i === index);
        });
      }

      function startAutoSlide() {
        timer = setInterval(function () {
          var next = (currentIndex + 1) % total;
          goToSlide(next);
        }, intervalMs);
      }

      function resetAutoSlide() {
        clearInterval(timer);
        startAutoSlide();
      }

      dots.forEach(function (dot, index) {
        dot.addEventListener('click', function () {
          goToSlide(index);
          resetAutoSlide();
        });
      });

      goToSlide(0);
      startAutoSlide();
    });
  </script>
</head>
  <body>
  <div class="page">
    <?php site_render_topbar(); ?>
    <?php site_render_nav('home'); ?>

    <!-- HERO IMAGE SLIDER (3 big scrolling images) -->
    <section class="hero-slider">
      <div class="slider">
        <div class="slider-track" id="heroSliderTrack">
          <?php foreach ($heroSlides as $slide): ?>
            <div class="slide" style="background-image:url('<?= e($slide['hero_image']) ?>');"></div>
          <?php endforeach; ?>
        </div>
        <div class="slider-dots">
          <?php foreach ($heroSlides as $i => $slide): ?>
            <button type="button" class="slider-dot<?= $i === 0 ? ' active' : '' ?>" aria-label="Slide <?= (int)$i + 1 ?>"></button>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <!-- HERO -->
    <main id="home" class="hero">
      <div class="container hero-inner">
        <div>
          <div class="hero-tagline">
            <span class="hero-tag-dot">✈</span>
            1000+ Happy Travellers &amp; Counting
          </div>
          <h1 class="hero-title">
            Design your <span>perfect holiday</span> with curated
            domestic &amp; international tours.
          </h1>
          <p class="hero-subtitle">
            From hidden Indian gems to iconic world cities, <?= e(site_profile()['company_name']) ?>
            builds handcrafted itineraries, stays, and experiences that match
            your style and budget.
          </p>
          <div class="hero-cta">
            <button class="btn btn-primary">
              <span>Discover Domestic Tours</span>
              <span>⤵</span>
            </button>
            <button class="btn btn-outline">
              <span class="btn-icon">☆</span>
              <span>Explore International</span>
            </button>
          </div>
          <div class="hero-meta">
            <div class="hero-meta-item">
              <strong><?= (int)$destCount ?>+</strong>
              Destinations across India &amp; Abroad
            </div>
            <div class="hero-meta-item">
              <strong><?= (int)$pkgCountTotal ?>+</strong>
              Live tour packages ready to book
            </div>
            <div class="hero-meta-item">
              <strong>24x7</strong>
              Trip assistance &amp; local support
            </div>
          </div>
        </div>

        <div class="hero-right">
          <?php if ($featuredPackage): ?>
          <div class="hero-main-card">
            <div class="hero-main-label">Featured <?= e(ucfirst((string)$featuredPackage['destination_type'])) ?> Escape</div>
            <div class="hero-main-destination"><?= e($featuredPackage['title']) ?></div>
            <div class="hero-main-meta">
              <span><?= e(nights_label($featuredPackage['nights'] !== null ? (int)$featuredPackage['nights'] : null, $featuredPackage['days'] !== null ? (int)$featuredPackage['days'] : null) ?: 'Custom duration') ?></span>
              <span><?= e((string)($featuredPackage['package_type'] ?: $featuredPackage['destination_name'])) ?></span>
            </div>
            <div class="hero-main-price">
              <span>Starting from</span>
              <strong><?= e(format_inr($featuredPackage['starting_price'] !== null ? (float)$featuredPackage['starting_price'] : null)) ?></strong>
              <span>per person</span>
            </div>
            <div class="hero-main-footer">
              <div>
                <div style="font-size: 0.9rem;"><?= e($featuredPackage['destination_name']) ?> • curated package</div>
                <div style="opacity: 0.85;"><?= e((string)($featuredPackage['route'] ?: 'Customisable itinerary')) ?></div>
              </div>
              <button class="btn btn-primary" type="button" style="white-space:nowrap;" onclick="window.location.href='<?= e(site_href('package.php?id=' . (int) $featuredPackage['id'])) ?>'">
                View Package
              </button>
            </div>
          </div>
          <?php else: ?>
          <div class="hero-main-card">
            <div class="hero-main-label"><?= e(site_profile()['company_name']) ?></div>
            <div class="hero-main-destination">Add your first package from admin</div>
            <div class="hero-main-meta">
              <span>Admin panel ready</span>
              <span>Live on website instantly</span>
            </div>
          </div>
          <?php endif; ?>

          <div class="hero-side">
            <div class="mini-card">
              <div class="mini-title">Domestic Favourites</div>
              <div class="mini-badge">Live from admin destinations</div>
              <div class="mini-pill-row">
                <?php foreach (array_slice($domesticDestinations, 0, 4) as $dest): ?>
                  <a class="pill" href="<?= e(site_href('destination.php?dest=' . $dest['slug'])) ?>"><?= e($dest['name']) ?></a>
                <?php endforeach; ?>
                <?php if (!$domesticDestinations): ?>
                  <span class="pill">Add destinations in admin</span>
                <?php endif; ?>
              </div>
              <div class="mini-progress">
                <span></span>
              </div>
              <div class="mini-stats">
                <span><?= count($domesticDestinations) ?> domestic destinations</span>
                <span><?= (int)$pkgCountTotal ?> packages live</span>
              </div>
            </div>

            <div class="mini-card">
              <div class="mini-title">Customise Your Package</div>
              <p>Mix destinations, hotels and activities into a single trip.
                Our travel experts design everything for you.</p>
              <div class="mini-stats">
                <span>Free planning call</span>
                <span>Avg. response &lt; 30 mins</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>

    <!-- DOMESTIC TOURS -->
    <section id="domestic">
      <div class="container">
        <div class="section-header">
          <div>
            <div class="section-kicker">Domestic Tour</div>
            <h2 class="section-title">Explore India’s most loved destinations</h2>
          </div>
          <p class="section-subtitle">
           Handpicked itineraries across beaches, mountains and spiritual
            circuits. Each city offers multiple stay and activity options so you
            can fine tune it to your family or group.
          </p>
        </div>

        <div class="tour-grid">
          <?php if (!$domesticDestinations): ?>
            <p>No domestic destinations yet. Add them from the admin panel.</p>
          <?php endif; ?>
          <?php foreach ($domesticDestinations as $dest): ?>
            <?php
              $price = $dest['min_package_price'] ?? $dest['starting_price'];
              $pkgCount = (int) ($dest['package_count'] ?? 0);
              $mediaClass = 'tour-media--' . preg_replace('/[^a-z0-9]+/', '', strtolower($dest['slug']));
              $hasImage = !empty($dest['hero_image']);
            ?>
            <article class="tour-card">
              <div class="tour-media <?= $hasImage ? 'tour-media--dynamic' : e($mediaClass) ?>"
                   <?php if ($hasImage): ?>style="--tour-image: url('<?= e($dest['hero_image']) ?>');"<?php endif; ?>>
                <span class="tour-chip"><?= e($dest['name']) ?></span>
                <span class="tour-duration"><?= e((string) ($dest['duration_label'] ?: '')) ?></span>
              </div>
              <div class="tour-body">
                <h3 class="tour-name"><?= e((string) $dest['tagline']) ?></h3>
                <div class="tour-meta-row">
                  <span><?= e((string) ($dest['meta_left'] ?: 'Multiple stay options')) ?></span>
                  <span><?= e((string) ($dest['meta_right'] ?: 'Customisable')) ?></span>
                </div>
                <div class="tour-footer">
                  <div class="tour-price">
                    <?= e(format_inr($price !== null ? (float) $price : null)) ?> <span>per person</span>
                  </div>
                  <a href="<?= e(site_href('destination.php?dest=' . $dest['slug'])) ?>">View <?= $pkgCount ?> Package<?= $pkgCount === 1 ? '' : 's' ?> ⟶</a>
                </div>
              </div>
            </article>
          <?php endforeach; ?>
        </div>

        <div class="highlight-band">
          <div>
            <strong>Need something different?</strong> Choose your own dates,
            hotels and activities for every domestic destination.
          </div>
          <button class="btn btn-outline">
            <span>Share your domestic requirements</span>
          </button>
        </div>
      </div>
    </section>

    <!-- INTERNATIONAL TOURS -->
    <section id="international">
      <div class="container">
        <div class="section-header">
          <div>
            <div class="section-kicker">International Tour</div>
            <h2 class="section-title">Discover world destinations in one place</h2>
          </div>
          <p class="section-subtitle">
           From tropical escapes to city skylines, our international tour
            experts handle flights, visas, stays and guided sightseeing for a
            stress-free holiday.
          </p>
        </div>

        <div class="tour-grid">
          <?php if (!$internationalDestinations): ?>
            <p>No international destinations yet. Add them from the admin panel.</p>
          <?php endif; ?>
          <?php foreach ($internationalDestinations as $dest): ?>
            <?php
              $price = $dest['min_package_price'] ?? $dest['starting_price'];
              $pkgCount = (int) ($dest['package_count'] ?? 0);
              $mediaClass = 'tour-media--' . preg_replace('/[^a-z0-9]+/', '', strtolower($dest['slug']));
              $hasImage = !empty($dest['hero_image']);
            ?>
            <article class="tour-card">
              <div class="tour-media <?= $hasImage ? 'tour-media--dynamic' : e($mediaClass) ?>"
                   <?php if ($hasImage): ?>style="--tour-image: url('<?= e($dest['hero_image']) ?>');"<?php endif; ?>>
                <span class="tour-chip"><?= e($dest['name']) ?></span>
                <span class="tour-duration"><?= e((string) ($dest['duration_label'] ?: '')) ?></span>
              </div>
              <div class="tour-body">
                <h3 class="tour-name"><?= e((string) $dest['tagline']) ?></h3>
                <div class="tour-meta-row">
                  <span><?= e((string) ($dest['meta_left'] ?: 'Guided sightseeing')) ?></span>
                  <span><?= e((string) ($dest['meta_right'] ?: 'Customisable')) ?></span>
                </div>
                <div class="tour-footer">
                  <div class="tour-price">
                    <?= e(format_inr($price !== null ? (float) $price : null)) ?> <span>per person</span>
                  </div>
                  <a href="<?= e(site_href('destination.php?dest=' . $dest['slug'])) ?>"><?= $pkgCount ?> Package Option<?= $pkgCount === 1 ? '' : 's' ?> ⟶</a>
                </div>
              </div>
            </article>
          <?php endforeach; ?>
        </div>

        <div class="highlight-band">
          <div>
            <strong>Group departure offers</strong> for Dubai, Thailand &amp; Bali
            this season. Special discounts for 10+ travellers.
          </div>
          <button class="btn btn-outline">
            <span>Ask for group international quote</span>
          </button>
        </div>
      </div>
    </section>

    <!-- POPULAR PACKAGES -->
    <section id="packages">
      <div class="container">
        <div class="section-header">
          <div>
            <div class="section-kicker">Packages</div>
            <h2 class="section-title">Latest packages from our experts</h2>
          </div>
          <p class="section-subtitle">
            These packages are managed from the admin panel. Add or edit a package and it appears here instantly.
          </p>
        </div>
        <div class="tour-grid">
          <?php if (!$latestPackages): ?>
            <p>No packages yet. Create packages from the admin panel.</p>
          <?php endif; ?>
          <?php foreach ($latestPackages as $pkg): ?>
            <article class="tour-card">
              <div class="tour-media tour-media--dynamic"
                   style="--tour-image: url('<?= e($pkg['hero_image'] ?: 'assets/images/1.jpg') ?>');">
                <span class="tour-chip"><?= e($pkg['destination_name']) ?></span>
                <span class="tour-duration"><?= e(nights_label($pkg['nights'] !== null ? (int)$pkg['nights'] : null, $pkg['days'] !== null ? (int)$pkg['days'] : null)) ?></span>
              </div>
              <div class="tour-body">
                <h3 class="tour-name"><?= e($pkg['title']) ?></h3>
                <div class="tour-meta-row">
                  <span><?= e((string)($pkg['route'] ?: 'Custom route')) ?></span>
                  <span><?= e((string)($pkg['package_type'] ?: 'Holiday')) ?></span>
                </div>
                <div class="tour-footer">
                  <div class="tour-price">
                    <?= e(format_inr($pkg['starting_price'] !== null ? (float)$pkg['starting_price'] : null)) ?>
                    <span>per person</span>
                  </div>
                  <a href="<?= e(site_href('package.php?id=' . (int) $pkg['id'])) ?>">View Details ⟶</a>
                </div>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <!-- SERVICES -->
    <section id="services">
      <div class="container">
        <div class="section-header">
          <div>
            <div class="section-kicker">Services</div>
            <h2 class="section-title">End-to-end travel planning under one roof</h2>
          </div>
          <p class="section-subtitle">
           We help you with everything from choosing the right destination,
            comparing packages, visas and tickets to 24x7 support while you’re
            on the trip.
          </p>
        </div>

        <div class="tour-grid" style="grid-template-columns: repeat(3, minmax(0,1fr));">
          <article class="tour-card">
            <div class="tour-body">
              <h3 class="tour-name">Tailor-made Tour Packages</h3>
              <p style="font-size:0.82rem; color:var(--muted);">
                Choose from multiple hotel categories, meal plans and sightseeing
                options for every destination and we will customise it for your
                dates and budget.
              </p>
            </div>
          </article>
          <article class="tour-card">
            <div class="tour-body">
              <h3 class="tour-name">Flights, Visas &amp; Insurance</h3>
              <p style="font-size:0.82rem; color:var(--muted);">
                International ticketing, visa assistance and travel insurance so
                your holiday is smooth and hassle-free from day one.
              </p>
            </div>
          </article>
          <article class="tour-card">
            <div class="tour-body">
              <h3 class="tour-name">Corporate &amp; Group Travel</h3>
              <p style="font-size:0.82rem; color:var(--muted);">
                Specialised solutions for conferences, incentive trips and
                large family groups with dedicated on-trip coordinators.
              </p>
            </div>
          </article>
        </div>
      </div>
    </section>

    <!-- TESTIMONIALS -->
    <section id="testimonials">
      <div class="container">
        <div class="section-header">
          <div>
            <div class="section-kicker">Testimonials</div>
            <h2 class="section-title">Best rated travel agency by real guests</h2>
          </div>
          <p class="section-subtitle">
           Inspired by feedback similar to what travellers share on
            Leeva Holidays, our guests often return with stories about seamless
            planning, great stays and caring local support.
          </p>
        </div>

        <div class="testimonial-grid">
          <div class="testimonial-intro">
            <div>
              <h3>Trusted by families, couples &amp; groups</h3>
              <p>
                “We enjoyed a lot, as promised in the quote. Travel agent was so
                good with us, guiding all the way and making our trip memorable.”
              </p>
            </div>
            <div class="testimonial-score">
              <div>
                <strong>4.9</strong>
                / 5
              </div>
              <div>
                Based on verified reviews
                <br />across our domestic &amp; international tours.
              </div>
            </div>
          </div>

          <div class="testimonial-list">
            <article class="testimonial-card">
              <div class="testimonial-rating">★★★★★</div>
              <p>
                Fantastic experience travelling to Goa. The food, hotel and
                sightseeing were all arranged perfectly – we would love to travel
                again with <?= e(site_profile()['company_name']) ?>.
              </p>
              <div class="testimonial-name">Rupavatiya Family</div>
            </article>
            <article class="testimonial-card">
              <div class="testimonial-rating">★★★★★</div>
              <p>
                Our Manali trip was very well organised. Drivers were polite,
                hotels were comfortable and the team always picked up our calls.
              </p>
              <div class="testimonial-name">Dr. Surendra Shah</div>
            </article>
            <article class="testimonial-card">
              <div class="testimonial-rating">★★★★★</div>
              <p>
                We found a great travel partner who suggested the right itinerary
                and ensured there were no surprises during the journey.
              </p>
              <div class="testimonial-name">Justin G</div>
            </article>
            <article class="testimonial-card">
              <div class="testimonial-rating">★★★★★</div>
              <p>
                Knowledgeable, friendly and safety-conscious team. I will be
                recommending their tours and booking again for our next vacation.
              </p>
              <div class="testimonial-name">Ajay Patel</div>
            </article>
          </div>
        </div>
      </div>
    </section>

    <!-- CONTACT / ENQUIRY -->
    <section id="contact">
      <div class="container">
        <div class="section-header">
          <div>
            <div class="section-kicker">Enquiry</div>
            <h2 class="section-title">Plan your next trip with <?= e(site_profile()['company_name']) ?></h2>
          </div>
          <p class="section-subtitle">
           Share a few basic details and our travel planner will call you with
            multiple package options for your preferred destination and dates.
          </p>
        </div>

        <div class="testimonial-grid">
          <div class="tour-card">
            <div class="tour-body">
              <h3 class="tour-name">Quick enquiry form</h3>
              <p style="font-size:0.82rem; color:var(--muted); margin-bottom:0.75rem;">
                Share your details and our planner will get back with package options.
              </p>
              <?php if ($enquirySuccess): ?>
                <div style="background:#dcfce7;color:#166534;padding:0.7rem 0.8rem;border-radius:10px;margin-bottom:0.8rem;font-size:0.84rem;">
                  Enquiry submitted successfully. We will contact you soon.
                </div>
              <?php endif; ?>
              <?php if ($enquiryError): ?>
                <div style="background:#fee2e2;color:#991b1b;padding:0.7rem 0.8rem;border-radius:10px;margin-bottom:0.8rem;font-size:0.84rem;">
                  <?= e($enquiryError) ?>
                </div>
              <?php endif; ?>
              <form method="post" action="<?= e(site_href('index.php#contact')) ?>">
                <input type="hidden" name="form_type" value="enquiry">
                <div style="display:flex; gap:0.6rem; flex-wrap:wrap;">
                  <input type="text" name="name" placeholder="Full Name" required
                    style="flex:1 1 140px; padding:0.5rem 0.65rem; border-radius:10px; border:1px solid rgba(148, 163, 184, 0.7); font-size:0.82rem;" />
                  <input type="tel" name="phone" placeholder="Phone Number" required
                    style="flex:1 1 140px; padding:0.5rem 0.65rem; border-radius:10px; border:1px solid rgba(148, 163, 184, 0.7); font-size:0.82rem;" />
                </div>
                <div style="display:flex; gap:0.6rem; flex-wrap:wrap; margin-top:0.6rem;">
                  <input type="email" name="email" placeholder="Email Address"
                    style="flex:1 1 180px; padding:0.5rem 0.65rem; border-radius:10px; border:1px solid rgba(148, 163, 184, 0.7); font-size:0.82rem;" />
                  <select name="destination"
                    style="flex:1 1 160px; padding:0.5rem 0.65rem; border-radius:10px; border:1px solid rgba(148, 163, 184, 0.7); font-size:0.82rem; background:#ffffff;">
                    <option value="">Select destination</option>
                    <?php foreach ($allDestinations as $dest): ?>
                      <option value="<?= e($dest['name']) ?>"><?= e($dest['name']) ?> (<?= e($dest['type']) ?>)</option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div style="display:flex; gap:0.6rem; flex-wrap:wrap; margin-top:0.6rem;">
                  <input type="number" name="travellers" min="1" placeholder="Travellers"
                    style="width:120px; padding:0.5rem 0.65rem; border-radius:10px; border:1px solid rgba(148, 163, 184, 0.7); font-size:0.82rem;" />
                </div>
                <textarea name="message" rows="3" placeholder="Tell us about your preferred dates, budget or hotel type..."
                  style="margin-top:0.6rem; width:100%; padding:0.6rem 0.65rem; border-radius:10px; border:1px solid rgba(148, 163, 184, 0.7); font-size:0.82rem; resize:vertical;"></textarea>
                <button type="submit" class="btn btn-primary" style="margin-top:0.8rem;">
                  Submit Enquiry
                </button>
              </form>
            </div>
          </div>

          <div class="tour-card">
            <div class="tour-body">
              <h3 class="tour-name">Office &amp; contact details</h3>
              <?php $profile = site_profile(); ?>
              <p style="font-size:0.8rem;">
                <strong><?= e($profile['company_name']) ?></strong><br />
                <?= nl2br(e($profile['address'])) ?>
              </p>
              <p style="font-size:0.8rem; margin-top:0.5rem;">
                <?php if ($profile['phone'] !== ''): ?>Phone: <strong><a href="tel:<?= e($profile['phone_href']) ?>"><?= e($profile['phone']) ?></a></strong><br /><?php endif; ?>
                <?php if ($profile['whatsapp'] !== ''): ?>WhatsApp: <strong><a href="https://wa.me/<?= e($profile['whatsapp_href']) ?>" target="_blank" rel="noopener"><?= e($profile['whatsapp']) ?></a></strong><br /><?php endif; ?>
                <?php if ($profile['landline'] !== ''): ?>Landline: <strong><?= e($profile['landline']) ?></strong><br /><?php endif; ?>
                <?php if ($profile['email'] !== ''): ?>Email: <strong><a href="mailto:<?= e($profile['email']) ?>"><?= e($profile['email']) ?></a></strong><?php endif; ?>
              </p>
              <p style="font-size:0.78rem; color:var(--muted); margin-top:0.7rem;">
                <?= nl2br(e($profile['hours'])) ?>
              </p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- FOOTER -->
    <footer class="footer">
      <div class="container">
        <div class="footer-top">
          <div>
            <div class="footer-logo">
              <?php $profile = site_profile(); site_brand_mark(); ?>
              <div>
                <div class="footer-logo-text-main"><?= e($profile['company_name']) ?></div>
                <div class="footer-logo-text-sub"><?= e($profile['tagline']) ?></div>
              </div>
            </div>
            <p style="font-size:0.8rem; color:#94a3b8;">
              <?= e($profile['about']) ?>
            </p>
          </div>

          <div>
            <div class="footer-heading">Quick Links</div>
            <ul class="footer-list">
              <li><a href="#home">Home</a></li>
              <li><a href="#about">About Us</a></li>
              <li><a href="#domestic">Domestic Tours</a></li>
              <li><a href="#international">International Tours</a></li>
              <li><a href="#contact">Enquiry &amp; Contact</a></li>
            </ul>
          </div>

          <div>
            <div class="footer-heading">Top Destinations</div>
            <ul class="footer-list">
              <?php foreach (array_slice($allDestinations, 0, 6) as $dest): ?>
                <li>
                  <a href="<?= e(site_href('destination.php?dest=' . $dest['slug'])) ?>"><?= e($dest['name']) ?></a>
                </li>
              <?php endforeach; ?>
              <?php if (!$allDestinations): ?>
                <li>Add destinations from admin</li>
              <?php endif; ?>
            </ul>
          </div>

          <div>
            <div class="footer-heading">Contact</div>
            <ul class="footer-list">
              <?php if ($profile['phone'] !== ''): ?><li><?= e($profile['phone']) ?></li><?php endif; ?>
              <?php if ($profile['whatsapp'] !== ''): ?><li>WhatsApp <?= e($profile['whatsapp']) ?></li><?php endif; ?>
              <?php if ($profile['email'] !== ''): ?><li><?= e($profile['email']) ?></li><?php endif; ?>
              <?php if ($profile['address'] !== ''): ?><li><?= e(strtok($profile['address'], "\n")) ?></li><?php endif; ?>
            </ul>
          </div>
        </div>

        <div class="footer-bottom">
          <span>© <?php echo date('Y'); ?> <?= e(site_profile()['company_name']) ?>. All rights reserved.</span>
        </div>
      </div>
    </footer>
  </div>
</body>
</html>


