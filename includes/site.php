<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

function site_domestic_destinations(): array
{
    static $rows = null;
    if ($rows === null) {
        $rows = get_destinations('domestic');
    }
    return $rows;
}

function site_international_destinations(): array
{
    static $rows = null;
    if ($rows === null) {
        $rows = get_destinations('international');
    }
    return $rows;
}

function site_package_count(): int
{
    static $count = null;
    if ($count === null) {
        $count = (int) db()->query('SELECT COUNT(*) FROM packages WHERE is_active = 1')->fetchColumn();
    }
    return $count;
}

function site_destination_count(): int
{
    static $count = null;
    if ($count === null) {
        $count = (int) db()->query('SELECT COUNT(*) FROM destinations WHERE is_active = 1')->fetchColumn();
    }
    return $count;
}

function site_featured_package(): ?array
{
    static $pkg = null;
    static $loaded = false;
    if ($loaded) {
        return $pkg;
    }
    $loaded = true;
    $stmt = db()->query(
        'SELECT p.*, d.name AS destination_name, d.slug AS destination_slug, d.type AS destination_type
         FROM packages p
         JOIN destinations d ON d.id = p.destination_id
         WHERE p.is_active = 1 AND d.is_active = 1
         ORDER BY p.sort_order ASC, p.id ASC
         LIMIT 1'
    );
    $pkg = $stmt->fetch() ?: null;
    return $pkg;
}

function site_hero_slides(int $limit = 3): array
{
    $stmt = db()->prepare(
        'SELECT name, slug, hero_image, tagline
         FROM destinations
         WHERE is_active = 1 AND hero_image IS NOT NULL AND hero_image <> ""
         ORDER BY sort_order ASC, id ASC
         LIMIT ?'
    );
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    $slides = $stmt->fetchAll();
    if ($slides) {
        return $slides;
    }
    return [
        ['name' => 'Travel World', 'slug' => '', 'hero_image' => 'assets/images/1.jpg', 'tagline' => ''],
        ['name' => 'Travel World', 'slug' => '', 'hero_image' => 'assets/images/2.jpg', 'tagline' => ''],
        ['name' => 'Travel World', 'slug' => '', 'hero_image' => 'assets/images/3.jpg', 'tagline' => ''],
    ];
}

/**
 * @param string $active One of: home, domestic, international, services, contact, testimonials
 */
function site_render_nav(string $active = 'home', string $base = ''): void
{
    $domestic = site_domestic_destinations();
    $international = site_international_destinations();
    $profile = site_profile();
    ?>
    <header class="navbar">
      <div class="container">
        <div class="navbar-inner">
          <a href="<?= e(site_href('index.php#top')) ?>" class="brand">
            <?php site_brand_mark(); ?>
            <div>
              <div class="brand-text-main"><?= e($profile['company_name']) ?></div>
              <?php if ($profile['tagline'] !== ''): ?>
                <div class="brand-text-sub"><?= e($profile['tagline']) ?></div>
              <?php endif; ?>
            </div>
          </a>

          <nav class="nav-links">
            <a href="<?= e(site_href('index.php#home')) ?>" class="<?= $active === 'home' ? 'active' : '' ?>">Home</a>

            <div class="nav-item has-submenu">
              <a href="<?= e(site_href('index.php#domestic')) ?>" class="<?= $active === 'domestic' ? 'active' : '' ?>">Domestic Tours</a>
              <span class="caret">▾</span>
              <div class="submenu">
                <div class="submenu-title">Popular Domestic</div>
                <?php if (!$domestic): ?>
                  <a href="<?= e(site_href('index.php#domestic')) ?>">No destinations yet<span>Add from admin</span></a>
                <?php endif; ?>
                <?php foreach ($domestic as $dest): ?>
                  <a href="<?= e(site_href('destination.php?dest=' . $dest['slug'])) ?>">
                    <?= e($dest['name']) ?>
                    <span><?= e((string) ($dest['tagline'] ?: 'View packages')) ?></span>
                  </a>
                <?php endforeach; ?>
              </div>
            </div>

            <div class="nav-item has-submenu">
              <a href="<?= e(site_href('index.php#international')) ?>" class="<?= $active === 'international' ? 'active' : '' ?>">International Tours</a>
              <span class="caret">▾</span>
              <div class="submenu">
                <div class="submenu-title">Top International</div>
                <?php if (!$international): ?>
                  <a href="<?= e(site_href('index.php#international')) ?>">No destinations yet<span>Add from admin</span></a>
                <?php endif; ?>
                <?php foreach ($international as $dest): ?>
                  <a href="<?= e(site_href('destination.php?dest=' . $dest['slug'])) ?>">
                    <?= e($dest['name']) ?>
                    <span><?= e((string) ($dest['tagline'] ?: 'View packages')) ?></span>
                  </a>
                <?php endforeach; ?>
              </div>
            </div>

            <a href="<?= e(site_href('services.php')) ?>" class="<?= $active === 'services' ? 'active' : '' ?>">Services</a>
            <a href="<?= e(site_href('index.php#testimonials')) ?>" class="<?= $active === 'testimonials' ? 'active' : '' ?>">Testimonials</a>
            <a href="<?= e(site_href('contact.php')) ?>" class="<?= $active === 'contact' ? 'active' : '' ?>">Contact</a>
          </nav>

          <div class="nav-actions">
            <button class="btn btn-outline" type="button" onclick="window.location.href='<?= e(site_href('contact.php')) ?>'">
              <span class="btn-icon">✉</span>
              <span>Enquiry</span>
            </button>
            <button class="btn btn-primary" type="button" onclick="window.location.href='<?= e(site_href('index.php#contact')) ?>'">
              <span>Plan My Trip</span>
              <span>➜</span>
            </button>
          </div>

          <button class="hamburger" onclick="toggleMobileMenu()" aria-label="Toggle navigation">
            <span></span>
          </button>
        </div>
        <div id="mobileMenu" class="mobile-menu">
          <a href="<?= e(site_href('index.php#home')) ?>">Home</a>
          <a href="<?= e(site_href('index.php#domestic')) ?>">Domestic Tours</a>
          <?php foreach ($domestic as $dest): ?>
            <div style="font-size:0.8rem; color:var(--muted); padding-left:0.75rem;">
              <a href="<?= e(site_href('destination.php?dest=' . $dest['slug'])) ?>">• <?= e($dest['name']) ?></a>
            </div>
          <?php endforeach; ?>
          <a href="<?= e(site_href('index.php#international')) ?>">International Tours</a>
          <?php foreach ($international as $dest): ?>
            <div style="font-size:0.8rem; color:var(--muted); padding-left:0.75rem;">
              <a href="<?= e(site_href('destination.php?dest=' . $dest['slug'])) ?>">• <?= e($dest['name']) ?></a>
            </div>
          <?php endforeach; ?>
          <a href="<?= e(site_href('services.php')) ?>">Services</a>
          <a href="<?= e(site_href('index.php#testimonials')) ?>">Testimonials</a>
          <a href="<?= e(site_href('contact.php')) ?>">Contact</a>
        </div>
      </div>
    </header>
    <?php
}

function site_templates(): array
{
    return [
        'classic' => [
            'name' => 'Classic',
            'tagline' => 'Current website',
            'description' => 'The original Travel World layout: orange accents, tour cards, and the theme switcher.',
        ],
        'horizon' => [
            'name' => 'Horizon',
            'tagline' => 'New template',
            'description' => 'An editorial layout with full-bleed photography, serif headlines, and a cream and ink palette.',
        ],
        'iexplore' => [
            'name' => 'iExplore',
            'tagline' => 'iexplore.abcditsolutions.com',
            'description' => 'Full-screen mountain hero, navy and sand colours, package cards, and a WhatsApp enquiry path.',
        ],
    ];
}

function site_setting(string $key, string $default = ''): string
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        try {
            $rows = db()->query('SELECT setting_key, setting_value FROM settings')->fetchAll();
            foreach ($rows as $row) {
                $cache[$row['setting_key']] = (string) $row['setting_value'];
            }
        } catch (Throwable $e) {
            $cache = [];
        }
    }
    return $cache[$key] ?? $default;
}

function site_setting_save(string $key, string $value): void
{
    db()->prepare(
        'INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
    )->execute([$key, $value]);
}

function site_digits(string $phone): string
{
    $digits = preg_replace('/\D+/', '', $phone) ?? '';
    if (strlen($digits) === 10) {
        $digits = '91' . $digits;
    }
    return $digits;
}

function site_initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $letters = '';
    foreach ($parts as $part) {
        if ($part === '') {
            continue;
        }
        $letters .= strtoupper(substr($part, 0, 1));
        if (strlen($letters) >= 2) {
            break;
        }
    }
    return $letters !== '' ? $letters : 'TW';
}

function site_external_url(string $url): string
{
    $url = trim($url);
    if ($url === '' || $url === '#') {
        return '';
    }
    if (!preg_match('#^https?://#i', $url)) {
        $url = 'https://' . $url;
    }
    return $url;
}

function site_profile(): array
{
    static $profile = null;
    if ($profile !== null) {
        return $profile;
    }

    $defaults = [
        'company_name' => 'Travel World',
        'tagline' => 'Domestic & International Tours',
        'logo' => '',
        'phone' => '+91 8866 70 6000',
        'whatsapp' => '+91 9930 70 8696',
        'landline' => '+91 261 253 3355',
        'email' => 'info@travelworld.com',
        'email_sales' => 'sale@travelworld.com',
        'address' => "3rd Floor, Plot No-1, Sample Business Center,\nOpp. Landmark Mall, Main Road, Your City,\nGujarat, India - 395004",
        'hours' => "Monday to Saturday: 10:00 AM – 7:00 PM\nSunday and public holidays by prior appointment.",
        'about' => 'Travel World is your trusted partner for curated holidays across India and abroad, offering personalised itineraries and reliable on-ground support at every step.',
        'map_embed' => '',
        'facebook' => '',
        'instagram' => '',
        'twitter' => '',
        'youtube' => '',
    ];

    $profile = [];
    foreach ($defaults as $key => $value) {
        $profile[$key] = site_setting($key, $value);
    }
    $profile['initials'] = site_initials($profile['company_name'] !== '' ? $profile['company_name'] : 'Travel World');
    $profile['phone_href'] = site_digits($profile['phone']);
    $profile['whatsapp_href'] = site_digits($profile['whatsapp']);
    $profile['landline_href'] = site_digits($profile['landline']);
    return $profile;
}

function site_map_src(): string
{
    $profile = site_profile();
    $embed = trim($profile['map_embed']);
    if ($embed !== '') {
        return $embed;
    }
    $query = trim($profile['company_name'] . ' ' . preg_replace('/\s+/', ' ', $profile['address']));
    return 'https://maps.google.com/maps?q=' . rawurlencode($query) . '&output=embed';
}

function site_brand_mark(string $class = 'brand-logo'): void
{
    $profile = site_profile();
    if ($profile['logo'] !== '') {
        echo '<img class="' . e($class) . '" src="' . e($profile['logo']) . '" alt="' . e($profile['company_name']) . '">';
        return;
    }
    echo '<div class="' . e($class) . '">' . e($profile['initials']) . '</div>';
}

function site_active_template(): string
{
    $allowed = array_keys(site_templates());
    $preview = $_GET['preview'] ?? '';
    if (is_string($preview) && in_array($preview, $allowed, true)) {
        return $preview;
    }
    $saved = site_setting('client_template', 'classic');
    return in_array($saved, $allowed, true) ? $saved : 'classic';
}

function site_dispatch_template(string $page): void
{
    $key = site_active_template();
    if ($key === 'classic' || !preg_match('/^[a-z0-9_-]+$/', $key) || !preg_match('/^[a-z0-9_-]+$/', $page)) {
        return;
    }
    $file = dirname(__DIR__) . '/templates/' . $key . '/' . $page . '.php';
    if (is_file($file)) {
        require $file;
        exit;
    }
}

function site_href(string $path): string
{
    $hash = '';
    $query = '';
    if (str_contains($path, '#')) {
        [$path, $hash] = explode('#', $path, 2);
        $hash = '#' . $hash;
    }
    if (str_contains($path, '?')) {
        [$path, $query] = explode('?', $path, 2);
    }
    $params = [];
    if ($query !== '') {
        parse_str($query, $params);
    }
    $file = basename(str_replace('\\', '/', $path));
    $clean = '/' . ltrim($path, '/');
    if ($file === '' || $file === 'index.php') {
        $clean = '/';
    } elseif ($file === 'packages.php') {
        $clean = '/packages';
    } elseif ($file === 'services.php') {
        $clean = '/about';
    } elseif ($file === 'contact.php') {
        $clean = '/contact';
    } elseif ($file === 'destination.php') {
        $clean = '/destinations/' . rawurlencode((string) ($params['dest'] ?? ''));
        unset($params['dest']);
    } elseif ($file === 'package.php') {
        $clean = '/package/' . (int) ($params['id'] ?? 0);
        unset($params['id']);
    } elseif ($file === 'quote.php') {
        $clean = '/quote/' . rawurlencode((string) ($params['t'] ?? ''));
        unset($params['t']);
    }
    $preview = $_GET['preview'] ?? '';
    if (is_string($preview) && $preview !== '' && isset(site_templates()[$preview]) && !isset($params['preview'])) {
        $params['preview'] = $preview;
    }
    $qs = http_build_query($params);
    return $clean . ($qs !== '' ? '?' . $qs : '') . $hash;
}

function site_render_topbar(): void
{
    $profile = site_profile();
    $socials = array_filter([
        'Facebook' => ['f', site_external_url($profile['facebook'])],
        'Instagram' => ['⌾', site_external_url($profile['instagram'])],
        'Twitter' => ['t', site_external_url($profile['twitter'])],
        'YouTube' => ['▶', site_external_url($profile['youtube'])],
    ], fn ($item) => $item[1] !== '');
    ?>
    <div class="topbar">
      <div class="container topbar-inner">
        <div class="topbar-left">
          <?php if ($profile['phone'] !== ''): ?>
            <a class="topbar-item" href="tel:<?= e($profile['phone_href']) ?>">
              <span class="topbar-icon">☎</span>
              <span><?= e($profile['phone']) ?></span>
            </a>
          <?php endif; ?>
          <?php if ($profile['whatsapp'] !== ''): ?>
            <a class="topbar-item" href="https://wa.me/<?= e($profile['whatsapp_href']) ?>" target="_blank" rel="noopener">
              <span class="topbar-icon">W</span>
              <span><?= e($profile['whatsapp']) ?></span>
            </a>
          <?php endif; ?>
          <?php if ($profile['email'] !== ''): ?>
            <a class="topbar-item" href="mailto:<?= e($profile['email']) ?>">
              <span class="topbar-icon">@</span>
              <span><?= e($profile['email']) ?></span>
            </a>
          <?php endif; ?>
        </div>
        <?php if ($socials): ?>
          <div class="topbar-right">
            <div class="topbar-item" style="color:#9ca3af;">Follow us:</div>
            <div class="topbar-social">
              <?php foreach ($socials as $label => $item): ?>
                <a href="<?= e($item[1]) ?>" target="_blank" rel="noopener" aria-label="<?= e($label) ?>"><?= $item[0] ?></a>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>
    <?php
}
