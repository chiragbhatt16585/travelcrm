<?php
declare(strict_types=1);

require_once __DIR__ . '/layout.php';

$all = array_merge(site_domestic_destinations(), site_international_destinations());
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

$profile = site_profile();
hz_head('Contact — ' . $profile['company_name']);
hz_header('contact');
?>
<section class="hz-section">
  <div class="hz-wrap hz-split">
    <div>
      <div class="hz-kicker">Contact</div>
      <h1 class="hz-serif" style="font-size:clamp(2.6rem,5vw,4.4rem);line-height:0.95;margin:0.3rem 0 1rem;">Write, call, or walk in.</h1>
      <p style="color:var(--ink-soft);max-width:36rem;">Share a destination and a rough date. We reply with a plan, not a waiting list.</p>
      <p>
        <strong><?= e($profile['company_name']) ?></strong><br>
        <?= nl2br(e($profile['address'])) ?><br>
        <?php if ($profile['landline'] !== ''): ?><?= e($profile['landline']) ?><br><?php endif; ?>
        <?php if ($profile['phone'] !== ''): ?><a href="tel:<?= e($profile['phone_href']) ?>"><?= e($profile['phone']) ?></a><br><?php endif; ?>
        <?php if ($profile['whatsapp'] !== ''): ?><a href="https://wa.me/<?= e($profile['whatsapp_href']) ?>" target="_blank" rel="noopener">WhatsApp <?= e($profile['whatsapp']) ?></a><br><?php endif; ?>
        <?php if ($profile['email'] !== ''): ?><a href="mailto:<?= e($profile['email']) ?>"><?= e($profile['email']) ?></a><br><?php endif; ?>
        <?php if ($profile['email_sales'] !== ''): ?><a href="mailto:<?= e($profile['email_sales']) ?>"><?= e($profile['email_sales']) ?></a><br><?php endif; ?>
        <?= nl2br(e($profile['hours'])) ?>
      </p>
    </div>
    <form class="hz" method="post" action="<?= e(site_href('contact.php')) ?>" style="background:var(--ink);color:var(--cream);padding:1.4rem;">
      <?php if ($enquirySuccess): ?><div class="hz-note">Message sent. We will be in touch.</div><?php endif; ?>
      <?php if ($enquiryError): ?><div class="hz-note bad"><?= e($enquiryError) ?></div><?php endif; ?>
      <div class="hz-row">
        <input name="name" placeholder="Your name" required>
        <input name="phone" placeholder="Phone" required>
      </div>
      <input type="email" name="email" placeholder="Email" style="margin-top:0.7rem;">
      <select name="destination" style="margin-top:0.7rem;">
        <option value="">Destination</option>
        <?php foreach ($all as $dest): ?>
          <option value="<?= e($dest['name']) ?>"><?= e($dest['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <textarea name="message" placeholder="What are you planning?" style="margin-top:0.7rem;"></textarea>
      <button class="hz-btn gold" type="submit" style="margin-top:0.8rem;">Send message</button>
    </form>
  </div>
</section>
<?php hz_footer(); ?>
