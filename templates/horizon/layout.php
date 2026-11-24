<?php
declare(strict_types=1);

function hz_head(string $title): void
{
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <base href="/">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($title) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600&family=Manrope:wght@400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="templates/horizon/style.css">
</head>
<body class="hz">
    <?php
    if (isset($_GET['preview'])) {
        echo '<div class="hz-preview"><span>Previewing the Horizon template. The live site is unchanged until you activate it in admin.</span><a href="admin/settings/template.php">Choose template</a></div>';
    }
}

function hz_header(string $active = 'home'): void
{
    $domestic = site_domestic_destinations();
    $international = site_international_destinations();
    ?>
<header class="hz-header">
  <div class="hz-wrap hz-header-inner">
    <?php $profile = site_profile(); ?>
    <a class="hz-logo" href="<?= e(site_href('index.php')) ?>" style="flex-direction:row;align-items:center;gap:0.7rem;">
      <?php if ($profile['logo'] !== ''): ?>
        <img src="<?= e($profile['logo']) ?>" alt="">
      <?php endif; ?>
      <span>
        <strong><?= e($profile['company_name']) ?></strong>
        <span><?= e($profile['tagline']) ?></span>
      </span>
    </a>
    <nav class="hz-nav">
      <a href="<?= e(site_href('index.php')) ?>" class="<?= $active === 'home' ? 'active' : '' ?>">Home</a>
      <div class="hz-nav-drop">
        <a href="<?= e(site_href('index.php#domestic')) ?>">India</a>
        <div class="hz-menu">
          <?php foreach ($domestic as $dest): ?>
            <a href="<?= e(site_href('destination.php?dest=' . urlencode($dest['slug']))) ?>">
              <?= e($dest['name']) ?>
              <small><?= e((string) ($dest['tagline'] ?: 'View packages')) ?></small>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="hz-nav-drop">
        <a href="<?= e(site_href('index.php#international')) ?>">Abroad</a>
        <div class="hz-menu">
          <?php foreach ($international as $dest): ?>
            <a href="<?= e(site_href('destination.php?dest=' . urlencode($dest['slug']))) ?>">
              <?= e($dest['name']) ?>
              <small><?= e((string) ($dest['tagline'] ?: 'View packages')) ?></small>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
      <a href="<?= e(site_href('services.php')) ?>" class="<?= $active === 'services' ? 'active' : '' ?>">Services</a>
      <a href="<?= e(site_href('contact.php')) ?>" class="<?= $active === 'contact' ? 'active' : '' ?>">Contact</a>
    </nav>
    <a class="hz-btn" href="<?= e(site_href('contact.php')) ?>">Plan a trip</a>
    <button class="hz-burger" type="button" onclick="document.getElementById('hzMobile').classList.toggle('open')" aria-label="Menu">☰</button>
  </div>
  <div id="hzMobile" class="hz-wrap hz-mobile">
    <a href="<?= e(site_href('index.php')) ?>">Home</a>
    <?php foreach (array_merge($domestic, $international) as $dest): ?>
      <a href="<?= e(site_href('destination.php?dest=' . urlencode($dest['slug']))) ?>"><?= e($dest['name']) ?></a>
    <?php endforeach; ?>
    <a href="<?= e(site_href('services.php')) ?>">Services</a>
    <a href="<?= e(site_href('contact.php')) ?>">Contact</a>
  </div>
</header>
    <?php
}

function hz_footer(): void
{
    $all = array_merge(site_domestic_destinations(), site_international_destinations());
    $profile = site_profile();
    ?>
<footer class="hz-footer">
  <div class="hz-wrap hz-footer-grid">
    <div>
    <div class="hz-logo" style="flex-direction:row;align-items:center;gap:0.7rem;">
      <?php if ($profile['logo'] !== ''): ?>
        <img src="<?= e($profile['logo']) ?>" alt="">
      <?php endif; ?>
      <span>
        <strong><?= e($profile['company_name']) ?></strong>
        <span><?= e($profile['tagline']) ?></span>
      </span>
    </div>
      <p><?= e($profile['about']) ?></p>
    </div>
    <div>
      <h4>Destinations</h4>
      <?php foreach (array_slice($all, 0, 6) as $dest): ?>
        <a href="<?= e(site_href('destination.php?dest=' . urlencode($dest['slug']))) ?>"><?= e($dest['name']) ?></a>
      <?php endforeach; ?>
    </div>
    <div>
      <h4>Contact</h4>
      <?php if ($profile['phone'] !== ''): ?><a href="tel:<?= e($profile['phone_href']) ?>"><?= e($profile['phone']) ?></a><?php endif; ?>
      <?php if ($profile['whatsapp'] !== ''): ?><a href="https://wa.me/<?= e($profile['whatsapp_href']) ?>" target="_blank" rel="noopener">WhatsApp <?= e($profile['whatsapp']) ?></a><?php endif; ?>
      <?php if ($profile['email'] !== ''): ?><a href="mailto:<?= e($profile['email']) ?>"><?= e($profile['email']) ?></a><?php endif; ?>
      <a href="<?= e(site_href('contact.php')) ?>">Send an enquiry</a>
    </div>
  </div>
  <div class="hz-wrap hz-copy">© <?= date('Y') ?> <?= e($profile['company_name']) ?>. All rights reserved.</div>
</footer>
</body>
</html>
    <?php
}

function hz_image(?string $path, string $fallback = 'assets/images/1.jpg'): string
{
    return ($path !== null && $path !== '') ? $path : $fallback;
}
