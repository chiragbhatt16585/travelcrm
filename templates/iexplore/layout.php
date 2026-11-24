<?php
declare(strict_types=1);

function ix_src(?string $path, string $fallback = 'assets/images/3.jpg'): string
{
    $path = trim((string) $path);
    if ($path === '') {
        return $fallback;
    }
    if (preg_match('#^(https?:)?//#i', $path) || str_starts_with($path, '/')) {
        return $path;
    }
    return '/' . ltrim($path, '/');
}

function ix_packages(?int $destinationId = null, int $limit = 0): array
{
    $sql = 'SELECT p.id, p.title, p.nights, p.days, p.route, p.package_type, p.starting_price, p.hero_image,
            d.name AS destination_name, d.slug AS destination_slug, d.hero_image AS destination_image
     FROM packages p
     JOIN destinations d ON d.id = p.destination_id
     WHERE p.is_active = 1 AND d.is_active = 1';
    $params = [];
    if ($destinationId) {
        $sql .= ' AND d.id = ?';
        $params[] = $destinationId;
    }
    $sql .= ' ORDER BY p.sort_order ASC, p.id DESC';
    if ($limit > 0) {
        $sql .= ' LIMIT ' . $limit;
    }
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function ix_head(string $title): void
{
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <base href="/">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($title) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600&family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/templates/iexplore/style.css">
</head>
<body class="ix">
    <?php
    if (isset($_GET['preview'])) {
        echo '<div class="ix-preview">Previewing the iExplore template. Activate it from Client template when you want it live. <a href="/admin/settings/template.php">Choose template</a></div>';
    }
}

function ix_highlights(string $route): array
{
    $route = trim($route);
    if ($route === '') {
        return [];
    }
    if (preg_match('/[–—•|]/u', $route) || str_contains($route, ' - ')) {
        $parts = preg_split('/\s*[–—•|]\s*|\s+-\s+/u', $route) ?: [];
    } else {
        $parts = preg_split('/\s*-\s*/', $route) ?: [];
    }
    $out = [];
    foreach ($parts as $part) {
        $part = trim($part);
        if ($part !== '') {
            $out[] = $part;
        }
    }
    return array_slice($out, 0, 3);
}

function ix_header(string $active = 'home', bool $overlay = false): void
{
    $profile = site_profile();
    $domestic = site_domestic_destinations();
    $international = site_international_destinations();
    $logo = $profile['logo'] !== '' ? ix_src($profile['logo'], '') : '';
    ?>
<header class="ix-header <?= $overlay ? 'is-overlay' : 'is-solid' ?>">
  <a class="ix-logo" href="<?= e(site_href('index.php')) ?>">
    <?php if ($logo !== ''): ?>
      <img src="<?= e($logo) ?>" alt="<?= e($profile['company_name']) ?>">
    <?php else: ?>
      <span class="ix-mark"><?= e($profile['initials']) ?></span>
    <?php endif; ?>
  </a>
  <nav class="ix-nav">
    <a href="<?= e(site_href('index.php')) ?>" class="<?= $active === 'home' ? 'on' : '' ?>">Home</a>
    <div class="ix-drop">
      <a href="<?= e(site_href('index.php#places')) ?>">Destinations</a>
      <div class="ix-menu">
        <?php foreach (array_merge($domestic, $international) as $dest): ?>
          <a href="<?= e(site_href('destination.php?dest=' . urlencode($dest['slug']))) ?>"><?= e($dest['name']) ?></a>
        <?php endforeach; ?>
      </div>
    </div>
    <a href="<?= e(site_href('packages.php')) ?>" class="<?= $active === 'packages' ? 'on' : '' ?>">Packages</a>
    <a href="<?= e(site_href('services.php')) ?>" class="<?= $active === 'about' ? 'on' : '' ?>">About</a>
    <a href="<?= e(site_href('contact.php')) ?>" class="<?= $active === 'contact' ? 'on' : '' ?>">Contact</a>
  </nav>
  <a class="ix-nav-cta" href="<?= e(site_href('contact.php')) ?>">Plan my trip</a>
  <button class="ix-burger" type="button" aria-label="Toggle menu" onclick="document.getElementById('ixMobile').classList.toggle('open')">☰</button>
</header>
<div id="ixMobile" class="ix-mobile">
  <a href="<?= e(site_href('index.php')) ?>">Home</a>
  <?php foreach (array_merge($domestic, $international) as $dest): ?>
    <a href="<?= e(site_href('destination.php?dest=' . urlencode($dest['slug']))) ?>"><?= e($dest['name']) ?></a>
  <?php endforeach; ?>
  <a href="<?= e(site_href('packages.php')) ?>">Packages</a>
  <a href="<?= e(site_href('services.php')) ?>">About</a>
  <a href="<?= e(site_href('contact.php')) ?>">Contact</a>
</div>
    <?php
}

function ix_package_card(array $pkg, bool $compact = false): void
{
    $profile = site_profile();
    $img = ix_src($pkg['hero_image'] ?: ($pkg['destination_image'] ?? ''), 'assets/images/2.jpg');
    $nights = isset($pkg['nights']) && $pkg['nights'] !== null && $pkg['nights'] !== '' ? (int) $pkg['nights'] : null;
    $days = isset($pkg['days']) && $pkg['days'] !== null && $pkg['days'] !== '' ? (int) $pkg['days'] : null;
    $duration = nights_label($nights, $days);
    $wa = trim((string) ($profile['whatsapp_href'] ?? ''));
    $waHref = $wa !== '' ? 'https://wa.me/' . $wa . '?text=' . rawurlencode('Hi ' . $profile['company_name'] . ', I would like more details on the "' . $pkg['title'] . '" package.') : '';
    $highlights = $compact ? [] : ix_highlights((string) ($pkg['route'] ?? ''));
    $href = site_href('package.php?id=' . (int) $pkg['id']);
    ?>
<article class="ix-card">
  <a class="ix-card-media" href="<?= e($href) ?>">
    <img src="<?= e($img) ?>" alt="<?= e($pkg['title']) ?>">
    <?php if ($duration !== ''): ?><span class="ix-badge"><?= e($duration) ?></span><?php endif; ?>
  </a>
  <div class="ix-card-body">
    <p class="ix-kicker"><?= e((string) ($pkg['package_type'] ?: $pkg['destination_name'])) ?></p>
    <h3><a href="<?= e($href) ?>"><?= e($pkg['title']) ?></a></h3>
    <?php if (!empty($pkg['route'])): ?><p class="ix-sub"><?= e((string) $pkg['route']) ?></p><?php endif; ?>
    <div class="ix-meta">
      <?php if ($nights || $days): ?><span><?= (int) $nights ?>N · <?= (int) $days ?>D</span><?php endif; ?>
      <span><?= e((string) $pkg['destination_name']) ?></span>
    </div>
    <?php if ($highlights): ?>
      <ul class="ix-points">
        <?php foreach ($highlights as $point): ?><li><?= e($point) ?></li><?php endforeach; ?>
      </ul>
    <?php endif; ?>
    <div class="ix-card-foot">
      <div>
        <small>From</small>
        <strong><?= e(format_inr($pkg['starting_price'] !== null && $pkg['starting_price'] !== '' ? (float) $pkg['starting_price'] : null)) ?></strong>
      </div>
      <div class="ix-card-actions">
        <?php if ($waHref !== ''): ?><a class="ix-wa" href="<?= e($waHref) ?>" target="_blank" rel="noopener">WhatsApp</a><?php endif; ?>
        <a class="ix-view" href="<?= e($href) ?>">View</a>
      </div>
    </div>
  </div>
</article>
    <?php
}

function ix_footer(): void
{
    $profile = site_profile();
    $wa = trim((string) ($profile['whatsapp_href'] ?? ''));
    $logo = $profile['logo'] !== '' ? ix_src($profile['logo'], '') : '';
    ?>
<footer class="ix-footer">
  <div class="ix-wrap ix-footer-grid">
    <div>
      <a class="ix-logo" href="<?= e(site_href('index.php')) ?>">
        <?php if ($logo !== ''): ?><img src="<?= e($logo) ?>" alt=""><?php else: ?><span class="ix-mark"><?= e($profile['initials']) ?></span><?php endif; ?>
      </a>
      <p><?= e($profile['about']) ?></p>
    </div>
    <div>
      <h4>Explore</h4>
      <a href="<?= e(site_href('packages.php')) ?>">All packages</a>
      <a href="<?= e(site_href('services.php')) ?>">About <?= e($profile['company_name']) ?></a>
      <a href="<?= e(site_href('contact.php')) ?>">Plan my trip</a>
      <?php if ($wa !== ''): ?><a href="https://wa.me/<?= e($wa) ?>" target="_blank" rel="noopener">WhatsApp inquiry</a><?php endif; ?>
    </div>
    <div>
      <h4>Get in touch</h4>
      <?php if ($profile['phone'] !== ''): ?><a href="tel:<?= e($profile['phone_href']) ?>"><?= e($profile['phone']) ?></a><?php endif; ?>
      <?php if ($profile['email_sales'] !== ''): ?><a href="mailto:<?= e($profile['email_sales']) ?>"><?= e($profile['email_sales']) ?></a><?php endif; ?>
      <?php if ($profile['email'] !== '' && $profile['email'] !== $profile['email_sales']): ?><a href="mailto:<?= e($profile['email']) ?>"><?= e($profile['email']) ?></a><?php endif; ?>
      <?php if (trim($profile['address']) !== ''): ?><p><?= nl2br(e($profile['address'])) ?></p><?php endif; ?>
    </div>
  </div>
  <div class="ix-wrap ix-copy">© <?= date('Y') ?> <?= e($profile['company_name']) ?></div>
</footer>
<?php if ($wa !== ''): ?>
  <a class="ix-float" href="https://wa.me/<?= e($wa) ?>?text=<?= rawurlencode('Hi ' . $profile['company_name'] . ', I would like to plan a trip.') ?>" target="_blank" rel="noopener" aria-label="WhatsApp">WhatsApp</a>
<?php endif; ?>
</body>
</html>
    <?php
}
