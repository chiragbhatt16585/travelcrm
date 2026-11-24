<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/site.php';
auth_check();

$fields = [
    'company_name' => 'Company name',
    'tagline' => 'Tagline',
    'phone' => 'Mobile',
    'whatsapp' => 'WhatsApp',
    'landline' => 'Landline',
    'email' => 'Email',
    'email_sales' => 'Sales email',
    'facebook' => 'Facebook URL',
    'instagram' => 'Instagram URL',
    'twitter' => 'Twitter / X URL',
    'youtube' => 'YouTube URL',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $name = trim($_POST['company_name'] ?? '');
        if ($name === '') {
            throw new RuntimeException('Company name is required.');
        }
        foreach (array_keys($fields) as $key) {
            site_setting_save($key, trim((string) ($_POST[$key] ?? '')));
        }
        site_setting_save('address', trim((string) ($_POST['address'] ?? '')));
        site_setting_save('hours', trim((string) ($_POST['hours'] ?? '')));
        site_setting_save('about', trim((string) ($_POST['about'] ?? '')));
        site_setting_save('map_embed', trim((string) ($_POST['map_embed'] ?? '')));

        $logo = site_setting('logo', '');
        if (!empty($_POST['remove_logo'])) {
            $logo = '';
        }
        if (!empty($_FILES['logo']['name'])) {
            $uploaded = upload_image($_FILES['logo'], 'branding');
            if ($uploaded) {
                $logo = $uploaded;
            }
        }
        site_setting_save('logo', $logo);

        flash_set('success', 'Company profile saved. The website is using these details now.');
        redirect('/admin/settings/company.php');
    } catch (Throwable $e) {
        flash_set('error', $e->getMessage());
        redirect('/admin/settings/company.php');
    }
}

$profile = site_profile();
$pageTitle = 'Company profile';
$activeNav = 'company';
require __DIR__ . '/../includes/header.php';
?>
<form class="admin-card" method="post" enctype="multipart/form-data">
  <div class="form-grid">
    <?php foreach ($fields as $key => $label): ?>
      <div class="form-group">
        <label><?= e($label) ?></label>
        <input type="text" name="<?= e($key) ?>" value="<?= e($profile[$key]) ?>" <?= $key === 'company_name' ? 'required' : '' ?>>
      </div>
    <?php endforeach; ?>
    <div class="form-group full">
      <label>Address</label>
      <textarea name="address"><?= e($profile['address']) ?></textarea>
    </div>
    <div class="form-group">
      <label>Working hours</label>
      <textarea name="hours"><?= e($profile['hours']) ?></textarea>
    </div>
    <div class="form-group">
      <label>Short about text</label>
      <textarea name="about"><?= e($profile['about']) ?></textarea>
      <div class="help">Shown in the website footer.</div>
    </div>
    <div class="form-group">
      <label>Logo</label>
      <?php if ($profile['logo'] !== ''): ?>
        <img src="/<?= e($profile['logo']) ?>" alt="" style="height:56px;width:auto;object-fit:contain;background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:4px;">
      <?php else: ?>
        <div class="help">No logo uploaded. The site shows <?= e($profile['initials']) ?> until you add one.</div>
      <?php endif; ?>
      <input type="file" name="logo" accept="image/*">
      <?php if ($profile['logo'] !== ''): ?>
        <label><input type="checkbox" name="remove_logo" value="1"> Remove current logo</label>
      <?php endif; ?>
    </div>
    <div class="form-group">
      <label>Google Map embed URL</label>
      <input type="text" name="map_embed" value="<?= e($profile['map_embed']) ?>" placeholder="Optional iframe src">
      <div class="help">Leave blank to open a map from the address.</div>
    </div>
  </div>
  <div class="actions" style="margin-top:1rem;">
    <button class="btn" type="submit">Save company profile</button>
    <a class="btn btn-outline" href="/" target="_blank">View website</a>
  </div>
</form>
<?php require __DIR__ . '/../includes/footer.php'; ?>
