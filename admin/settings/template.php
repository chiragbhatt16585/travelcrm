<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/site.php';
auth_check();

$templates = site_templates();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $choice = $_POST['client_template'] ?? '';
    if (!isset($templates[$choice])) {
        flash_set('error', 'Unknown template.');
    } else {
        site_setting_save('client_template', $choice);
        flash_set('success', $templates[$choice]['name'] . ' is now the live client website.');
    }
    redirect('/admin/settings/template.php');
}

$active = site_setting('client_template', 'classic');
if (!isset($templates[$active])) {
    $active = 'classic';
}

$pageTitle = 'Client template';
$activeNav = 'template';
require __DIR__ . '/../includes/header.php';
?>
<p style="color:#64748b;margin-top:0;">Both designs stay in the project. Activate the one visitors should see. Preview opens it without changing the live site.</p>
<form method="post" class="form-grid">
  <?php foreach ($templates as $key => $meta): ?>
    <label class="admin-card" style="cursor:pointer;<?= $active === $key ? 'outline:2px solid #ff6b35;' : '' ?>">
      <div style="display:flex;justify-content:space-between;gap:1rem;align-items:center;">
        <strong><?= e($meta['name']) ?></strong>
        <?php if ($active === $key): ?><span class="badge badge-ok">Live</span><?php endif; ?>
      </div>
      <div class="help" style="margin:0.35rem 0 0.8rem;"><?= e($meta['tagline']) ?></div>
      <p style="margin:0 0 1rem;font-size:0.9rem;"><?= e($meta['description']) ?></p>
      <div class="actions">
        <span><input type="radio" name="client_template" value="<?= e($key) ?>" <?= $active === $key ? 'checked' : '' ?>> Use this template</span>
        <a class="btn btn-sm btn-outline" href="/?preview=<?= e(urlencode($key)) ?>" target="_blank">Preview</a>
      </div>
    </label>
  <?php endforeach; ?>
  <div class="form-group full">
    <button class="btn" type="submit">Activate selected template</button>
  </div>
</form>
<?php require __DIR__ . '/../includes/footer.php'; ?>
