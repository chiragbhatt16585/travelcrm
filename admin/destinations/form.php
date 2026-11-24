<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
auth_check();

$isEdit = isset($_GET['id']);
$destination = [
    'name' => '',
    'slug' => '',
    'tagline' => '',
    'description' => '',
    'hero_image' => '',
    'type' => 'domestic',
    'duration_label' => '',
    'starting_price' => '',
    'meta_left' => '',
    'meta_right' => '',
    'sort_order' => 0,
    'is_active' => 1,
];

if ($isEdit) {
    $found = get_destination_by_id((int) $_GET['id']);
    if (!$found) {
        flash_set('error', 'Destination not found.');
        redirect('/admin/destinations/index.php');
    }
    $destination = $found;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        if ($slug === '') {
            $slug = slugify($name);
        } else {
            $slug = slugify($slug);
        }

        if ($name === '') {
            throw new RuntimeException('Destination name is required.');
        }

        $heroImage = $destination['hero_image'] ?? '';
        if (!empty($_FILES['hero_image']['name'])) {
            $uploaded = upload_image($_FILES['hero_image'], 'destinations');
            if ($uploaded) {
                $heroImage = $uploaded;
            }
        } elseif (!empty($_POST['hero_image_path'])) {
            $heroImage = trim($_POST['hero_image_path']);
        }

        $data = [
            $name,
            $slug,
            trim($_POST['tagline'] ?? ''),
            trim($_POST['description'] ?? ''),
            $heroImage ?: null,
            ($_POST['type'] ?? 'domestic') === 'international' ? 'international' : 'domestic',
            trim($_POST['duration_label'] ?? '') ?: null,
            $_POST['starting_price'] !== '' ? (float) $_POST['starting_price'] : null,
            trim($_POST['meta_left'] ?? '') ?: null,
            trim($_POST['meta_right'] ?? '') ?: null,
            (int) ($_POST['sort_order'] ?? 0),
            isset($_POST['is_active']) ? 1 : 0,
        ];

        if ($isEdit) {
            $sql = 'UPDATE destinations SET name=?, slug=?, tagline=?, description=?, hero_image=?, type=?, duration_label=?, starting_price=?, meta_left=?, meta_right=?, sort_order=?, is_active=? WHERE id=?';
            $data[] = (int) $destination['id'];
            db()->prepare($sql)->execute($data);
            flash_set('success', 'Destination updated.');
        } else {
            $sql = 'INSERT INTO destinations (name, slug, tagline, description, hero_image, type, duration_label, starting_price, meta_left, meta_right, sort_order, is_active) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)';
            db()->prepare($sql)->execute($data);
            flash_set('success', 'Destination created.');
        }
        redirect('/admin/destinations/index.php');
    } catch (Throwable $ex) {
        $error = $ex->getMessage();
        $destination = array_merge($destination, $_POST);
        $destination['is_active'] = isset($_POST['is_active']) ? 1 : 0;
    }
}

$pageTitle = $isEdit ? 'Edit Destination' : 'Add Destination';
$activeNav = 'destinations';
require __DIR__ . '/../includes/header.php';
?>
<?php if ($error): ?><div class="flash flash-error"><?= e($error) ?></div><?php endif; ?>
<form class="admin-card" method="post" enctype="multipart/form-data">
  <div class="form-grid">
    <div class="form-group">
      <label>Name *</label>
      <input type="text" name="name" required value="<?= e((string) $destination['name']) ?>">
    </div>
    <div class="form-group">
      <label>Slug</label>
      <input type="text" name="slug" value="<?= e((string) $destination['slug']) ?>" placeholder="auto from name">
    </div>
    <div class="form-group full">
      <label>Tagline</label>
      <input type="text" name="tagline" value="<?= e((string) ($destination['tagline'] ?? '')) ?>" placeholder="Munnar • Alleppey • Kochi">
    </div>
    <div class="form-group full">
      <label>Description</label>
      <textarea name="description"><?= e((string) ($destination['description'] ?? '')) ?></textarea>
    </div>
    <div class="form-group">
      <label>Type</label>
      <select name="type">
        <option value="domestic" <?= ($destination['type'] ?? '') === 'domestic' ? 'selected' : '' ?>>Domestic</option>
        <option value="international" <?= ($destination['type'] ?? '') === 'international' ? 'selected' : '' ?>>International</option>
      </select>
    </div>
    <div class="form-group">
      <label>Duration label</label>
      <input type="text" name="duration_label" value="<?= e((string) ($destination['duration_label'] ?? '')) ?>" placeholder="5N / 6D">
    </div>
    <div class="form-group">
      <label>Starting price (₹)</label>
      <input type="number" step="0.01" name="starting_price" value="<?= e((string) ($destination['starting_price'] ?? '')) ?>">
    </div>
    <div class="form-group">
      <label>Sort order</label>
      <input type="number" name="sort_order" value="<?= e((string) ($destination['sort_order'] ?? 0)) ?>">
    </div>
    <div class="form-group">
      <label>Meta left</label>
      <input type="text" name="meta_left" value="<?= e((string) ($destination['meta_left'] ?? '')) ?>" placeholder="3 hotel options • Private cab">
    </div>
    <div class="form-group">
      <label>Meta right</label>
      <input type="text" name="meta_right" value="<?= e((string) ($destination['meta_right'] ?? '')) ?>" placeholder="Family, Honeymoon">
    </div>
    <div class="form-group">
      <label>Upload image</label>
      <input type="file" name="hero_image" accept="image/*">
    </div>
    <div class="form-group">
      <label>Or image path</label>
      <input type="text" name="hero_image_path" value="<?= e((string) ($destination['hero_image'] ?? '')) ?>" placeholder="assets/images/1.jpg">
      <div class="help">Use existing assets path if you don't upload.</div>
    </div>
    <div class="form-group">
      <label><input type="checkbox" name="is_active" <?= !empty($destination['is_active']) ? 'checked' : '' ?>> Active on website</label>
    </div>
  </div>
  <div class="actions" style="margin-top:1rem;">
    <button class="btn" type="submit">Save Destination</button>
    <a class="btn btn-outline" href="/admin/destinations/index.php">Cancel</a>
  </div>
</form>
<?php require __DIR__ . '/../includes/footer.php'; ?>
