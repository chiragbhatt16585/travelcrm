<?php
declare(strict_types=1);
require_once __DIR__ . '/layout.php';

$profile = site_profile();
$destinations = array_merge(site_domestic_destinations(), site_international_destinations());
$success = false;
$error = '';
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
        $success = true;
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}
ix_head('Plan a trip — ' . $profile['company_name']);
ix_header('contact');
?>
<section class="ix-page-hero" style="background-image:url('<?= e(ix_src($destinations[0]['hero_image'] ?? null, 'assets/images/1.jpg')) ?>')">
  <div class="ix-wrap">
    <p class="ix-kicker">Contact</p>
    <h1>Plan my trip</h1>
    <p>Share the dates and the places. We will reply with a quotation.</p>
  </div>
</section>
<section class="ix-section">
  <div class="ix-wrap ix-split">
    <form class="ix-form ix-panel" method="post">
      <?php if ($success): ?><div class="ix-note">Message received. We will contact you shortly.</div><?php endif; ?>
      <?php if ($error !== ''): ?><div class="ix-note bad"><?= e($error) ?></div><?php endif; ?>
      <input type="text" name="name" placeholder="Your name" required value="<?= e($_POST['name'] ?? '') ?>">
      <input type="tel" name="phone" placeholder="Mobile number" required value="<?= e($_POST['phone'] ?? '') ?>">
      <input type="email" name="email" placeholder="Email" value="<?= e($_POST['email'] ?? '') ?>">
      <select name="destination">
        <option value="">Preferred destination</option>
        <?php foreach ($destinations as $dest): ?>
          <option value="<?= e($dest['name']) ?>" <?= (($_POST['destination'] ?? '') === $dest['name']) ? 'selected' : '' ?>><?= e($dest['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <textarea name="message" rows="5" placeholder="Preferred dates, travellers, places you want to see"><?= e($_POST['message'] ?? '') ?></textarea>
      <button class="ix-btn" type="submit" style="color:var(--navy);">Send inquiry</button>
    </form>
    <aside class="ix-panel">
      <h3>Get in touch</h3>
      <?php if ($profile['phone'] !== ''): ?><p>Phone<br><a href="tel:<?= e($profile['phone_href']) ?>"><?= e($profile['phone']) ?></a></p><?php endif; ?>
      <?php if ($profile['whatsapp'] !== ''): ?><p>WhatsApp<br><a href="https://wa.me/<?= e($profile['whatsapp_href']) ?>"><?= e($profile['whatsapp']) ?></a></p><?php endif; ?>
      <?php if ($profile['email_sales'] !== ''): ?><p>Email<br><a href="mailto:<?= e($profile['email_sales']) ?>"><?= e($profile['email_sales']) ?></a></p><?php endif; ?>
      <?php if (trim($profile['address']) !== ''): ?><p><?= nl2br(e($profile['address'])) ?></p><?php endif; ?>
      <?php if (trim($profile['hours']) !== ''): ?><p><?= nl2br(e($profile['hours'])) ?></p><?php endif; ?>
    </aside>
  </div>
</section>
<?php ix_footer(); ?>
