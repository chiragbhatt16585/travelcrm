<?php
require_once __DIR__ . '/includes/site.php';
site_dispatch_template('contact');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Contact Us - <?= e(site_profile()['company_name']) ?></title>
  <?php
    require_once __DIR__ . '/includes/site.php';
    $allDestinations = array_merge(site_domestic_destinations(), site_international_destinations());
    $enquirySuccess = false;
    $enquiryError = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      try {
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $destination = trim($_POST['destination'] ?? '');
        $message = trim($_POST['message'] ?? '');
        if ($name === '' || $phone === '') {
          throw new RuntimeException('Name and phone are required.');
        }
        db()->prepare('INSERT INTO enquiries (name, email, phone, destination, message) VALUES (?,?,?,?,?)')
          ->execute([$name, $email ?: null, $phone, $destination ?: null, $message ?: null]);
        $enquirySuccess = true;
      } catch (Throwable $e) {
        $enquiryError = $e->getMessage();
      }
    }
  ?>
  <?php include 'includes/theme-head.php'; ?>
  <style>
    /* Page-specific styles for contact page */

    .contact-grid {
      display: grid;
      grid-template-columns: minmax(0, 1.2fr) minmax(0, 1.4fr);
      gap: 1.4rem;
      margin-top: 0.6rem;
      align-items: flex-start;
    }

    .contact-card {
      border-radius: var(--radius-lg);
      background: #ffffff;
      box-shadow: var(--shadow-card);
      padding: 1rem 1.1rem;
      font-size: 0.84rem;
    }

    .contact-card h2 {
      font-size: 1rem;
      margin-bottom: 0.4rem;
      color: #0f172a;
    }

    .contact-group {
      margin-bottom: 0.7rem;
    }

    .contact-label {
      font-size: 0.78rem;
      font-weight: 600;
      color: var(--muted);
      text-transform: uppercase;
      letter-spacing: 0.12em;
      margin-bottom: 0.1rem;
    }

    .contact-text {
      font-size: 0.86rem;
      color: #0f172a;
    }

    .contact-text p {
      margin-bottom: 0.1rem;
    }

    .contact-form {
      margin-top: 0.4rem;
      display: flex;
      flex-direction: column;
      gap: 0.55rem;
    }

    .contact-form-row {
      display: flex;
      gap: 0.6rem;
      flex-wrap: wrap;
    }

    .contact-form input,
    .contact-form textarea {
      border-radius: 10px;
      border: 1px solid rgba(148, 163, 184, 0.7);
      padding: 0.5rem 0.65rem;
      font-size: 0.82rem;
      width: 100%;
    }

    .contact-form textarea {
      resize: vertical;
      min-height: 80px;
    }

    .map-card {
      border-radius: var(--radius-lg);
      overflow: hidden;
      box-shadow: var(--shadow-card);
      background: #e5e7eb;
      height: 100%;
      min-height: 260px;
    }

    .map-card iframe {
      width: 100%;
      height: 100%;
      border: 0;
      display: block;
      min-height: 260px;
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

      .contact-grid {
        grid-template-columns: minmax(0, 1fr);
      }

      .map-card {
        min-height: 220px;
      }
    }
  </style>
</head>
<body>
  <div class="page">
    <?php site_render_topbar(); ?>
    <?php site_render_nav('contact'); ?>

    <main class="content">
      <div class="container">
        <div class="section-header">
          <div class="section-kicker">Contact Us</div>
          <h1 class="section-title">We’d love to help plan your next holiday</h1>
          <p class="section-subtitle">
            Reach out to <?= e(site_profile()['company_name']) ?> by phone, email or by visiting our office. Share your basic trip
            idea and our team will get back with customised package options.
          </p>
        </div>

        <section class="contact-grid">
          <article class="contact-card">
            <?php $profile = site_profile(); ?>
            <h2><?= e($profile['company_name']) ?> Head Office</h2>

            <div class="contact-group">
              <div class="contact-label">Company Name</div>
              <div class="contact-text">
                <p><strong><?= e($profile['company_name']) ?></strong></p>
                <?php if ($profile['tagline'] !== ''): ?><p><?= e($profile['tagline']) ?></p><?php endif; ?>
              </div>
            </div>

            <div class="contact-group">
              <div class="contact-label">Address</div>
              <div class="contact-text">
                <p><?= nl2br(e($profile['address'])) ?></p>
              </div>
            </div>

            <div class="contact-group">
              <div class="contact-label">Contact Numbers</div>
              <div class="contact-text">
                <?php if ($profile['landline'] !== ''): ?><p>Phone: <strong><?= e($profile['landline']) ?></strong></p><?php endif; ?>
                <?php if ($profile['phone'] !== ''): ?><p>Mobile: <strong><a href="tel:<?= e($profile['phone_href']) ?>"><?= e($profile['phone']) ?></a></strong></p><?php endif; ?>
                <?php if ($profile['whatsapp'] !== ''): ?><p>WhatsApp: <strong><a href="https://wa.me/<?= e($profile['whatsapp_href']) ?>" target="_blank" rel="noopener"><?= e($profile['whatsapp']) ?></a></strong></p><?php endif; ?>
              </div>
            </div>

            <div class="contact-group">
              <div class="contact-label">Email</div>
              <div class="contact-text">
                <?php if ($profile['email'] !== ''): ?><p>General: <strong><a href="mailto:<?= e($profile['email']) ?>"><?= e($profile['email']) ?></a></strong></p><?php endif; ?>
                <?php if ($profile['email_sales'] !== ''): ?><p>Sales: <strong><a href="mailto:<?= e($profile['email_sales']) ?>"><?= e($profile['email_sales']) ?></a></strong></p><?php endif; ?>
              </div>
            </div>

            <div class="contact-group">
              <div class="contact-label">Working Hours</div>
              <div class="contact-text">
                <p><?= nl2br(e($profile['hours'])) ?></p>
              </div>
            </div>

            <?php if ($enquirySuccess): ?>
              <div style="background:#dcfce7;color:#166534;padding:0.7rem 0.8rem;border-radius:10px;margin-bottom:0.8rem;font-size:0.84rem;">
                Message sent successfully. We will contact you soon.
              </div>
            <?php endif; ?>
            <?php if ($enquiryError): ?>
              <div style="background:#fee2e2;color:#991b1b;padding:0.7rem 0.8rem;border-radius:10px;margin-bottom:0.8rem;font-size:0.84rem;">
                <?= e($enquiryError) ?>
              </div>
            <?php endif; ?>
            <form class="contact-form" method="post">
              <div class="contact-form-row">
                <input type="text" name="name" placeholder="Your Name" required style="flex:1 1 140px;" />
                <input type="tel" name="phone" placeholder="Phone Number" required style="flex:1 1 140px;" />
              </div>
              <div class="contact-form-row">
                <input type="email" name="email" placeholder="Email Address" style="flex:1 1 180px;" />
                <select name="destination" style="flex:1 1 160px;">
                  <option value="">Select destination</option>
                  <?php foreach ($allDestinations as $dest): ?>
                    <option value="<?= e($dest['name']) ?>"><?= e($dest['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <textarea name="message" placeholder="Write your message or travel requirements here..."></textarea>
              <button type="submit" class="btn btn-primary">Send Message</button>
            </form>
          </article>

          <aside class="map-card">
            <!-- Static Google Maps embed placeholder; replace src with your actual location link later -->
            <iframe
              loading="lazy"
              referrerpolicy="no-referrer-when-downgrade"
              src="<?= e(site_map_src()) ?>">
            </iframe>
          </aside>
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

