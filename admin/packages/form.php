<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
auth_check();

$isEdit = isset($_GET['id']);
$destinations = db()->query('SELECT id, name FROM destinations ORDER BY name ASC')->fetchAll();

$package = [
    'destination_id' => '',
    'title' => '',
    'nights' => '',
    'days' => '',
    'route' => '',
    'package_type' => '',
    'starting_price' => '',
    'hero_image' => '',
    'sort_order' => 0,
    'is_active' => 1,
];
$itinerary = [['day_label' => 'Day 1', 'description' => '']];
$hotels = [['location_name' => '', 'category' => 'Deluxe', 'hotel_name' => '']];
$inclusionsText = '';
$exclusionsText = '';

if ($isEdit) {
    $found = get_package_by_id((int) $_GET['id']);
    if (!$found) {
        flash_set('error', 'Package not found.');
        redirect('/admin/packages/index.php');
    }
    $package = $found;
    $details = get_package_details((int) $found['id']);
    $itinerary = $details['itinerary'] ?: $itinerary;
    $hotels = $details['hotels'] ?: $hotels;
    $inclusionsText = implode("\n", array_column($details['inclusions'], 'content'));
    $exclusionsText = implode("\n", array_column($details['exclusions'], 'content'));
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (empty($destinations)) {
            throw new RuntimeException('Create a destination first.');
        }

        $title = trim($_POST['title'] ?? '');
        $destinationId = (int) ($_POST['destination_id'] ?? 0);
        if ($title === '' || $destinationId <= 0) {
            throw new RuntimeException('Title and destination are required.');
        }

        $heroImage = $package['hero_image'] ?? '';
        if (!empty($_FILES['hero_image']['name'])) {
            $uploaded = upload_image($_FILES['hero_image'], 'packages');
            if ($uploaded) {
                $heroImage = $uploaded;
            }
        } elseif (!empty($_POST['hero_image_path'])) {
            $heroImage = trim($_POST['hero_image_path']);
        }

        $fields = [
            $destinationId,
            $title,
            $_POST['nights'] !== '' ? (int) $_POST['nights'] : null,
            $_POST['days'] !== '' ? (int) $_POST['days'] : null,
            trim($_POST['route'] ?? '') ?: null,
            trim($_POST['package_type'] ?? '') ?: null,
            $_POST['starting_price'] !== '' ? (float) $_POST['starting_price'] : null,
            $heroImage ?: null,
            (int) ($_POST['sort_order'] ?? 0),
            isset($_POST['is_active']) ? 1 : 0,
        ];

        $pdo = db();
        $pdo->beginTransaction();

        if ($isEdit) {
            $sql = 'UPDATE packages SET destination_id=?, title=?, nights=?, days=?, route=?, package_type=?, starting_price=?, hero_image=?, sort_order=?, is_active=? WHERE id=?';
            $fields[] = (int) $package['id'];
            $pdo->prepare($sql)->execute($fields);
            $packageId = (int) $package['id'];
        } else {
            $sql = 'INSERT INTO packages (destination_id, title, nights, days, route, package_type, starting_price, hero_image, sort_order, is_active) VALUES (?,?,?,?,?,?,?,?,?,?)';
            $pdo->prepare($sql)->execute($fields);
            $packageId = (int) $pdo->lastInsertId();
        }

        $itineraryRows = [];
        $dayLabels = $_POST['day_label'] ?? [];
        $dayDescs = $_POST['day_description'] ?? [];
        foreach ($dayLabels as $i => $label) {
            $itineraryRows[] = [
                'day_label' => $label,
                'description' => $dayDescs[$i] ?? '',
            ];
        }

        $hotelRows = [];
        $locs = $_POST['hotel_location'] ?? [];
        $cats = $_POST['hotel_category'] ?? [];
        $names = $_POST['hotel_name'] ?? [];
        foreach ($locs as $i => $loc) {
            $hotelRows[] = [
                'location_name' => $loc,
                'category' => $cats[$i] ?? '',
                'hotel_name' => $names[$i] ?? '',
            ];
        }

        save_package_children($packageId, [
            'itinerary' => $itineraryRows,
            'hotels' => $hotelRows,
            'inclusions' => parse_lines($_POST['inclusions'] ?? ''),
            'exclusions' => parse_lines($_POST['exclusions'] ?? ''),
        ]);

        $pdo->commit();
        flash_set('success', $isEdit ? 'Package updated.' : 'Package created.');
        redirect('/admin/packages/index.php');
    } catch (Throwable $ex) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }
        $error = $ex->getMessage();
        $package = array_merge($package, $_POST);
        $package['is_active'] = isset($_POST['is_active']) ? 1 : 0;
        $inclusionsText = $_POST['inclusions'] ?? '';
        $exclusionsText = $_POST['exclusions'] ?? '';

        $itinerary = [];
        foreach (($_POST['day_label'] ?? []) as $i => $label) {
            $itinerary[] = ['day_label' => $label, 'description' => $_POST['day_description'][$i] ?? ''];
        }
        $hotels = [];
        foreach (($_POST['hotel_location'] ?? []) as $i => $loc) {
            $hotels[] = [
                'location_name' => $loc,
                'category' => $_POST['hotel_category'][$i] ?? '',
                'hotel_name' => $_POST['hotel_name'][$i] ?? '',
            ];
        }
    }
}

$pageTitle = $isEdit ? 'Edit Package' : 'Add Package';
$activeNav = 'packages';
require __DIR__ . '/../includes/header.php';
?>
<?php if ($error): ?><div class="flash flash-error"><?= e($error) ?></div><?php endif; ?>
<?php if (!$destinations): ?>
  <div class="flash flash-error">Please create a destination before adding packages.<?php if (auth_can('destinations.add')): ?> <a href="/admin/destinations/create.php">Add a destination</a><?php endif; ?></div>
<?php endif; ?>

<form class="admin-card" method="post" enctype="multipart/form-data">
  <div class="form-grid">
    <div class="form-group">
      <label>Destination *</label>
      <select name="destination_id" required>
        <option value="">Select destination</option>
        <?php foreach ($destinations as $d): ?>
          <option value="<?= (int) $d['id'] ?>" <?= (string) $package['destination_id'] === (string) $d['id'] ? 'selected' : '' ?>>
            <?= e($d['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group">
      <label>Title *</label>
      <input type="text" name="title" required value="<?= e((string) $package['title']) ?>">
    </div>
    <div class="form-group">
      <label>Nights</label>
      <input type="number" name="nights" value="<?= e((string) ($package['nights'] ?? '')) ?>">
    </div>
    <div class="form-group">
      <label>Days</label>
      <input type="number" name="days" value="<?= e((string) ($package['days'] ?? '')) ?>">
    </div>
    <div class="form-group full">
      <label>Route</label>
      <input type="text" name="route" value="<?= e((string) ($package['route'] ?? '')) ?>" placeholder="Kochi – Munnar – Thekkady – Alleppey">
    </div>
    <div class="form-group">
      <label>Package type</label>
      <input type="text" name="package_type" value="<?= e((string) ($package['package_type'] ?? '')) ?>" placeholder="Family • Sightseeing">
    </div>
    <div class="form-group">
      <label>Starting price (₹)</label>
      <input type="number" step="0.01" name="starting_price" value="<?= e((string) ($package['starting_price'] ?? '')) ?>">
    </div>
    <div class="form-group">
      <label>Sort order</label>
      <input type="number" name="sort_order" value="<?= e((string) ($package['sort_order'] ?? 0)) ?>">
    </div>
    <div class="form-group">
      <label>Upload image</label>
      <input type="file" name="hero_image" accept="image/*">
    </div>
    <div class="form-group">
      <label>Or image path</label>
      <input type="text" name="hero_image_path" value="<?= e((string) ($package['hero_image'] ?? '')) ?>" placeholder="assets/images/2.jpg">
    </div>
    <div class="form-group">
      <label><input type="checkbox" name="is_active" <?= !empty($package['is_active']) ? 'checked' : '' ?>> Active on website</label>
    </div>
  </div>

  <h3 style="margin:1.2rem 0 0.6rem;font-size:1rem;">Itinerary</h3>
  <div id="itinerary-list">
    <?php foreach ($itinerary as $row): ?>
      <div class="repeat-block itinerary-row">
        <div class="form-grid">
          <div class="form-group">
            <label>Day label</label>
            <input type="text" name="day_label[]" value="<?= e($row['day_label']) ?>" placeholder="Day 1">
          </div>
          <div class="form-group">
            <label>Description</label>
            <input type="text" name="day_description[]" value="<?= e($row['description']) ?>">
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <button class="btn btn-outline btn-sm" type="button" onclick="addItinerary()">+ Add day</button>

  <h3 style="margin:1.2rem 0 0.6rem;font-size:1rem;">Hotels</h3>
  <div id="hotel-list">
    <?php foreach ($hotels as $row): ?>
      <div class="repeat-block hotel-row">
        <div class="form-grid">
          <div class="form-group">
            <label>Location</label>
            <input type="text" name="hotel_location[]" value="<?= e($row['location_name']) ?>" placeholder="Munnar">
          </div>
          <div class="form-group">
            <label>Category</label>
            <input type="text" name="hotel_category[]" value="<?= e($row['category']) ?>" placeholder="Deluxe">
          </div>
          <div class="form-group full">
            <label>Hotel name</label>
            <input type="text" name="hotel_name[]" value="<?= e($row['hotel_name']) ?>">
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <button class="btn btn-outline btn-sm" type="button" onclick="addHotel()">+ Add hotel row</button>

  <div class="form-grid" style="margin-top:1.2rem;">
    <div class="form-group">
      <label>Inclusions (one per line)</label>
      <textarea name="inclusions" style="min-height:140px;"><?= e($inclusionsText) ?></textarea>
    </div>
    <div class="form-group">
      <label>Exclusions (one per line)</label>
      <textarea name="exclusions" style="min-height:140px;"><?= e($exclusionsText) ?></textarea>
    </div>
  </div>

  <div class="actions" style="margin-top:1rem;">
    <button class="btn" type="submit" <?= !$destinations ? 'disabled' : '' ?>>Save Package</button>
    <a class="btn btn-outline" href="/admin/packages/index.php">Cancel</a>
  </div>
</form>

<script>
function addItinerary() {
  const list = document.getElementById('itinerary-list');
  const n = list.querySelectorAll('.itinerary-row').length + 1;
  const div = document.createElement('div');
  div.className = 'repeat-block itinerary-row';
  div.innerHTML = `
    <div class="form-grid">
      <div class="form-group">
        <label>Day label</label>
        <input type="text" name="day_label[]" value="Day ${n}" placeholder="Day ${n}">
      </div>
      <div class="form-group">
        <label>Description</label>
        <input type="text" name="day_description[]" value="">
      </div>
    </div>`;
  list.appendChild(div);
}
function addHotel() {
  const list = document.getElementById('hotel-list');
  const div = document.createElement('div');
  div.className = 'repeat-block hotel-row';
  div.innerHTML = `
    <div class="form-grid">
      <div class="form-group">
        <label>Location</label>
        <input type="text" name="hotel_location[]" placeholder="Munnar">
      </div>
      <div class="form-group">
        <label>Category</label>
        <input type="text" name="hotel_category[]" value="Deluxe" placeholder="Deluxe">
      </div>
      <div class="form-group full">
        <label>Hotel name</label>
        <input type="text" name="hotel_name[]">
      </div>
    </div>`;
  list.appendChild(div);
}
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
