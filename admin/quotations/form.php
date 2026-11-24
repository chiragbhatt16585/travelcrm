<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
auth_check();

$statuses = quotation_statuses();
$id = (int) ($_GET['id'] ?? 0);
$copyId = (int) ($_GET['copy'] ?? 0);
$error = '';

$quote = $id ? get_quotation($id) : null;
if ($id && !$quote) {
    flash_set('error', 'Quotation not found.');
    redirect('/admin/quotations/index.php');
}

$source = $quote;
if (!$source && $copyId) {
    $source = get_quotation($copyId);
}

$enquiryId = (int) ($quote['enquiry_id'] ?? $_GET['enquiry_id'] ?? 0);
if ($source && !$quote) {
    $enquiryId = (int) $source['enquiry_id'];
}
$enquiry = $enquiryId ? get_enquiry_by_id($enquiryId) : null;
if (!$enquiry) {
    flash_set('error', 'Open an enquiry first, then create a quotation.');
    redirect('/admin/enquiries/index.php');
}

$days = [['day_date' => '', 'heading' => '', 'description' => '']];
$packages = [quotation_empty_hotel_package('Option 1')];
if ($source) {
    $loadedDays = quotation_days((int) $source['id']);
    $loadedPackages = quotation_hotel_packages((int) $source['id']);
    if ($loadedDays) {
        $days = $loadedDays;
    }
    if ($loadedPackages) {
        $packages = [];
        foreach ($loadedPackages as $package) {
            $packages[] = [
                'code' => (string) $package['code'],
                'label' => (string) ($package['label'] ?? ''),
                'price_per_person' => $package['price_per_person'] ?? '',
                'child_with_bed_price' => $package['child_with_bed_price'] ?? '',
                'child_without_bed_price' => $package['child_without_bed_price'] ?? '',
                'stays' => $package['stays'] ?: [[
                    'nights' => '',
                    'city' => '',
                    'hotel_name' => '',
                    'meal_plan' => 'Breakfast & Dinner',
                ]],
            ];
        }
    }
}

$form = [
    'option_no' => $quote['option_no'] ?? next_quotation_option($enquiryId),
    'title' => $source['title'] ?? ($enquiry['destination'] ? $enquiry['destination'] . ' Tour' : ''),
    'nights' => $source['nights'] ?? '',
    'days' => $source['days'] ?? '',
    'travel_date' => $source['travel_date'] ?? '',
    'adults' => $source['adults'] ?? ((isset($enquiry['adults']) && $enquiry['adults'] !== null && $enquiry['adults'] !== '') ? (int) $enquiry['adults'] : 2),
    'child_with_bed' => $source['child_with_bed'] ?? ((isset($enquiry['children']) && $enquiry['children'] !== null && $enquiry['children'] !== '') ? (int) $enquiry['children'] : 0),
    'child_without_bed' => $source['child_without_bed'] ?? 0,
    'room_note' => $source['room_note'] ?? 'with 1 Double Room (Minimum 02 paying pax)',
    'child_free_note' => $source['child_free_note'] ?? 'Child below 5 years will be free of cost',
    'inclusions' => $source['inclusions'] ?? '',
    'exclusions' => $source['exclusions'] ?? '',
    'notes' => $source['notes'] ?? '',
    'status' => $quote['status'] ?? 'draft',
];

$parsePackages = static function (array $posted) use (&$packages): array {
    $rows = [];
    $seen = [];
    foreach ($posted as $index => $raw) {
        if (!is_array($raw)) {
            continue;
        }
        $code = trim((string) ($raw['code'] ?? ''));
        if ($code === '') {
            $code = quotation_next_package_code($seen);
        }
        if (mb_strlen($code) > 100) {
            throw new RuntimeException('Package name can be up to 100 characters.');
        }
        foreach ($seen as $existing) {
            if (strcasecmp($existing, $code) === 0) {
                throw new RuntimeException('Each hotel package needs a different name.');
            }
        }
        $seen[] = $code;

        $money = static function (string $key) use ($raw): ?string {
            $value = trim((string) ($raw[$key] ?? ''));
            if ($value === '') {
                return null;
            }
            if (!is_numeric($value)) {
                throw new RuntimeException('Enter package amounts as numbers only.');
            }
            return number_format((float) $value, 2, '.', '');
        };

        $stayRows = [];
        $nightsList = $raw['stay_nights'] ?? [];
        $cities = $raw['stay_city'] ?? [];
        $hotels = $raw['stay_hotel'] ?? [];
        $meals = $raw['stay_meal'] ?? [];
        $stayCount = max(count($nightsList), count($cities), count($hotels), count($meals));
        for ($i = 0; $i < $stayCount; $i++) {
            $city = trim((string) ($cities[$i] ?? ''));
            $hotel = trim((string) ($hotels[$i] ?? ''));
            $meal = trim((string) ($meals[$i] ?? ''));
            $nightsRaw = trim((string) ($nightsList[$i] ?? ''));
            if ($city === '' && $hotel === '' && $meal === '' && $nightsRaw === '') {
                continue;
            }
            if ($city === '' || $hotel === '') {
                throw new RuntimeException($code . ' needs a city and hotel name on every hotel row.');
            }
            $stayRows[] = [
                'nights' => $nightsRaw === '' ? null : max(0, (int) $nightsRaw),
                'city' => $city,
                'hotel_name' => $hotel,
                'meal_plan' => $meal,
            ];
        }
        if (!$stayRows) {
            throw new RuntimeException($code . ' needs at least one hotel.');
        }

        $rows[] = [
            'code' => $code,
            'label' => trim((string) ($raw['label'] ?? '')),
            'price_per_person' => $money('price_per_person'),
            'child_with_bed_price' => $money('child_with_bed_price'),
            'child_without_bed_price' => $money('child_without_bed_price'),
            'stays' => $stayRows,
        ];
    }
    if (!$rows) {
        throw new RuntimeException('Add at least one hotel package.');
    }
    return $rows;
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save';
    if ($action === 'delete' && $quote) {
        $stmt = db()->prepare('DELETE FROM quotations WHERE id = ?');
        $stmt->execute([(int) $quote['id']]);
        flash_set('success', 'Quotation deleted.');
        redirect(auth_can('enquiries') ? '/admin/enquiries/view.php?id=' . $enquiryId : '/admin/quotations/index.php');
    }

    try {
        $title = trim($_POST['title'] ?? '');
        if ($title === '') {
            throw new RuntimeException('Tour title is required.');
        }
        $status = $_POST['status'] ?? 'draft';
        if (!isset($statuses[$status])) {
            $status = 'draft';
        }
        $optionNo = max(1, (int) ($_POST['option_no'] ?? 1));
        $travelDate = trim($_POST['travel_date'] ?? '');
        if ($travelDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $travelDate)) {
            throw new RuntimeException('Travel date is not valid.');
        }

        $dayRows = [];
        $dates = $_POST['day_date'] ?? [];
        $headings = $_POST['day_heading'] ?? [];
        $descriptions = $_POST['day_description'] ?? [];
        $count = max(count($dates), count($headings), count($descriptions));
        for ($i = 0; $i < $count; $i++) {
            $heading = trim((string) ($headings[$i] ?? ''));
            $description = trim((string) ($descriptions[$i] ?? ''));
            $dayDate = trim((string) ($dates[$i] ?? ''));
            if ($heading === '' && $description === '' && $dayDate === '') {
                continue;
            }
            if ($heading === '') {
                throw new RuntimeException('Each itinerary day needs a heading.');
            }
            if ($dayDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dayDate)) {
                throw new RuntimeException('One of the day dates is not valid.');
            }
            $dayRows[] = [
                'day_date' => $dayDate !== '' ? $dayDate : null,
                'heading' => $heading,
                'description' => $description,
            ];
        }
        if (!$dayRows) {
            throw new RuntimeException('Add at least one day to the itinerary.');
        }

        $packageRows = $parsePackages((array) ($_POST['packages'] ?? []));

        $nights = trim($_POST['nights'] ?? '');
        $daysCount = trim($_POST['days'] ?? '');
        if ($nights === '') {
            $sum = 0;
            foreach ($packageRows[0]['stays'] as $stay) {
                $sum += (int) ($stay['nights'] ?? 0);
            }
            $nights = $sum > 0 ? (string) $sum : '';
        }
        if ($daysCount === '' && $nights !== '') {
            $daysCount = (string) ((int) $nights + 1);
        }

        $adults = max(0, (int) ($_POST['adults'] ?? 0));
        $childWithBed = max(0, (int) ($_POST['child_with_bed'] ?? 0));
        $childWithoutBed = max(0, (int) ($_POST['child_without_bed'] ?? 0));
        $first = $packageRows[0];

        $payload = [
            $optionNo,
            $title,
            $nights === '' ? null : (int) $nights,
            $daysCount === '' ? null : (int) $daysCount,
            $travelDate !== '' ? $travelDate : null,
            $adults,
            $first['price_per_person'],
            $childWithBed,
            $first['child_with_bed_price'],
            $childWithoutBed,
            $first['child_without_bed_price'],
            trim($_POST['room_note'] ?? ''),
            trim($_POST['child_free_note'] ?? ''),
            trim($_POST['inclusions'] ?? ''),
            trim($_POST['exclusions'] ?? ''),
            trim($_POST['notes'] ?? ''),
            $status,
        ];

        $pdo = db();
        $pdo->beginTransaction();
        $user = auth_user();
        $actor = $user['name'] ?? 'Admin';
        if ($quote) {
            $oldStatus = (string) $quote['status'];
            $stmt = $pdo->prepare(
                'UPDATE quotations SET option_no=?, title=?, nights=?, days=?, travel_date=?, adults=?, price_per_person=?,
                 child_with_bed=?, child_with_bed_price=?, child_without_bed=?, child_without_bed_price=?,
                 room_note=?, child_free_note=?, inclusions=?, exclusions=?, notes=?, status=? WHERE id=?'
            );
            $stmt->execute([...$payload, (int) $quote['id']]);
            $quoteId = (int) $quote['id'];
            if ($oldStatus !== $status) {
                $log = $pdo->prepare(
                    'INSERT INTO enquiry_followups (enquiry_id, note, created_by) VALUES (?, ?, ?)'
                );
                $label = 'Option ' . str_pad((string) $optionNo, 2, '0', STR_PAD_LEFT);
                $log->execute([
                    $enquiryId,
                    $label . ' marked ' . ($statuses[$status] ?? $status) . ': ' . $title,
                    $actor,
                ]);
            }
        } else {
            $token = bin2hex(random_bytes(16));
            $stmt = $pdo->prepare(
                'INSERT INTO quotations (enquiry_id, token, option_no, title, nights, days, travel_date, adults, price_per_person,
                 child_with_bed, child_with_bed_price, child_without_bed, child_without_bed_price,
                 room_note, child_free_note, inclusions, exclusions, notes, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([$enquiryId, $token, ...$payload]);
            $quoteId = (int) $pdo->lastInsertId();
            $log = $pdo->prepare(
                'INSERT INTO enquiry_followups (enquiry_id, note, created_by) VALUES (?, ?, ?)'
            );
            $label = 'Option ' . str_pad((string) $optionNo, 2, '0', STR_PAD_LEFT);
            $log->execute([
                $enquiryId,
                'Created quotation ' . $label . ': ' . $title,
                $actor,
            ]);
        }

        $keptSelectedId = null;
        $previousSelected = $quote ? (int) ($quote['selected_package_id'] ?? 0) : 0;
        $previousCode = '';
        if ($previousSelected > 0) {
            $prev = $pdo->prepare('SELECT code FROM quotation_hotel_packages WHERE id = ? AND quotation_id = ? LIMIT 1');
            $prev->execute([$previousSelected, $quoteId]);
            $previousCode = (string) ($prev->fetchColumn() ?: '');
        }

        $pdo->prepare('DELETE FROM quotation_days WHERE quotation_id = ?')->execute([$quoteId]);
        $pdo->prepare('DELETE FROM quotation_hotel_packages WHERE quotation_id = ?')->execute([$quoteId]);
        $dayStmt = $pdo->prepare(
            'INSERT INTO quotation_days (quotation_id, day_date, heading, description, sort_order) VALUES (?, ?, ?, ?, ?)'
        );
        foreach ($dayRows as $i => $row) {
            $dayStmt->execute([$quoteId, $row['day_date'], $row['heading'], $row['description'], $i]);
        }

        $packageStmt = $pdo->prepare(
            'INSERT INTO quotation_hotel_packages
             (quotation_id, code, label, price_per_person, child_with_bed_price, child_without_bed_price, sort_order)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stayStmt = $pdo->prepare(
            'INSERT INTO quotation_stays (quotation_id, package_id, nights, city, hotel_name, meal_plan, sort_order)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        foreach ($packageRows as $i => $package) {
            $packageStmt->execute([
                $quoteId,
                $package['code'],
                null,
                $package['price_per_person'],
                $package['child_with_bed_price'],
                $package['child_without_bed_price'],
                $i,
            ]);
            $packageId = (int) $pdo->lastInsertId();
            if ($previousCode !== '' && strcasecmp($package['code'], $previousCode) === 0) {
                $keptSelectedId = $packageId;
            }
            foreach ($package['stays'] as $j => $stay) {
                $stayStmt->execute([
                    $quoteId,
                    $packageId,
                    $stay['nights'],
                    $stay['city'],
                    $stay['hotel_name'],
                    $stay['meal_plan'],
                    $j,
                ]);
            }
        }
        $pdo->prepare('UPDATE quotations SET selected_package_id = ? WHERE id = ?')->execute([
            $keptSelectedId,
            $quoteId,
        ]);

        $pdo->commit();
        flash_set('success', 'Quotation saved.');
        redirect('/admin/quotations/print.php?id=' . $quoteId);
    } catch (Throwable $e) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }
        $error = $e->getMessage();
        $form = [
            'option_no' => $_POST['option_no'] ?? 1,
            'title' => $_POST['title'] ?? '',
            'nights' => $_POST['nights'] ?? '',
            'days' => $_POST['days'] ?? '',
            'travel_date' => $_POST['travel_date'] ?? '',
            'adults' => $_POST['adults'] ?? 2,
            'child_with_bed' => $_POST['child_with_bed'] ?? 0,
            'child_without_bed' => $_POST['child_without_bed'] ?? 0,
            'room_note' => $_POST['room_note'] ?? '',
            'child_free_note' => $_POST['child_free_note'] ?? '',
            'inclusions' => $_POST['inclusions'] ?? '',
            'exclusions' => $_POST['exclusions'] ?? '',
            'notes' => $_POST['notes'] ?? '',
            'status' => $_POST['status'] ?? 'draft',
        ];
        $days = [];
        $dates = $_POST['day_date'] ?? [];
        $headings = $_POST['day_heading'] ?? [];
        $descriptions = $_POST['day_description'] ?? [];
        $count = max(count($dates), count($headings), count($descriptions));
        for ($i = 0; $i < $count; $i++) {
            $days[] = [
                'day_date' => $dates[$i] ?? '',
                'heading' => $headings[$i] ?? '',
                'description' => $descriptions[$i] ?? '',
            ];
        }
        if (!$days) {
            $days = [['day_date' => '', 'heading' => '', 'description' => '']];
        }
        $packages = [];
        foreach ((array) ($_POST['packages'] ?? []) as $raw) {
            if (!is_array($raw)) {
                continue;
            }
            $stayRows = [];
            $nightsList = $raw['stay_nights'] ?? [];
            $cities = $raw['stay_city'] ?? [];
            $hotels = $raw['stay_hotel'] ?? [];
            $meals = $raw['stay_meal'] ?? [];
            $stayCount = max(count($nightsList), count($cities), count($hotels), count($meals), 1);
            for ($i = 0; $i < $stayCount; $i++) {
                $stayRows[] = [
                    'nights' => $nightsList[$i] ?? '',
                    'city' => $cities[$i] ?? '',
                    'hotel_name' => $hotels[$i] ?? '',
                    'meal_plan' => $meals[$i] ?? '',
                ];
            }
            $packages[] = [
                'code' => $raw['code'] ?? 'Option 1',
                'label' => $raw['label'] ?? '',
                'price_per_person' => $raw['price_per_person'] ?? '',
                'child_with_bed_price' => $raw['child_with_bed_price'] ?? '',
                'child_without_bed_price' => $raw['child_without_bed_price'] ?? '',
                'stays' => $stayRows,
            ];
        }
        if (!$packages) {
            $packages = [quotation_empty_hotel_package('Option 1')];
        }
    }
}

$pageTitle = $quote ? 'Edit quotation' : 'New quotation';
$activeNav = 'quotations';
require __DIR__ . '/../includes/header.php';
?>
<div class="admin-card">
  <p class="help" style="margin-top:0;">
    Client: <strong><?= e($enquiry['name']) ?></strong>
    <?php if (!empty($enquiry['destination'])): ?> · <?= e($enquiry['destination']) ?><?php endif; ?>
    <?php if (auth_can('enquiries')): ?> · <a href="/admin/enquiries/view.php?id=<?= (int) $enquiry['id'] ?>">Back to enquiry</a><?php endif; ?>
  </p>
  <?php if ($error): ?><div class="flash flash-error"><?= e($error) ?></div><?php endif; ?>
  <form method="post">
    <input type="hidden" name="action" value="save">
    <div class="form-grid">
      <div class="form-group">
        <label>Option number</label>
        <input type="number" min="1" name="option_no" value="<?= e((string) $form['option_no']) ?>">
      </div>
      <div class="form-group">
        <label>Status</label>
        <select name="status">
          <?php foreach ($statuses as $key => $label): ?>
            <option value="<?= e($key) ?>" <?= $form['status'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group full">
        <label>Tour title</label>
        <input type="text" name="title" required value="<?= e((string) $form['title']) ?>" placeholder="Kasol-Manali Tour">
      </div>
      <div class="form-group">
        <label>Nights</label>
        <input type="number" min="0" name="nights" value="<?= e((string) $form['nights']) ?>" placeholder="Filled from hotels if left blank">
      </div>
      <div class="form-group">
        <label>Days</label>
        <input type="number" min="0" name="days" value="<?= e((string) $form['days']) ?>" placeholder="Nights + 1 if left blank">
      </div>
      <div class="form-group">
        <label>Travel start date</label>
        <input type="date" name="travel_date" value="<?= e((string) $form['travel_date']) ?>">
      </div>
    </div>

    <h3 style="margin:1.2rem 0 0.6rem;font-size:1rem;">Day wise itinerary</h3>
    <div id="day-list">
      <?php foreach ($days as $row): ?>
        <div class="repeat-block day-row">
          <div class="form-grid">
            <div class="form-group">
              <label>Date</label>
              <input type="date" name="day_date[]" value="<?= e((string) ($row['day_date'] ?? '')) ?>">
            </div>
            <div class="form-group">
              <label>Heading</label>
              <input type="text" name="day_heading[]" value="<?= e((string) ($row['heading'] ?? '')) ?>" placeholder="Arrive Chandigarh & transfer to Kasol">
            </div>
            <div class="form-group full">
              <label>Plan</label>
              <textarea name="day_description[]" style="min-height:70px;"><?= e((string) ($row['description'] ?? '')) ?></textarea>
            </div>
          </div>
          <button class="btn btn-outline btn-sm" type="button" onclick="this.closest('.day-row').remove()">Remove day</button>
        </div>
      <?php endforeach; ?>
    </div>
    <button class="btn btn-outline btn-sm" type="button" onclick="addDay()">+ Add day</button>

    <h3 style="margin:1.2rem 0 0.6rem;font-size:1rem;">Travellers</h3>
    <div class="form-grid">
      <div class="form-group">
        <label>Adults</label>
        <input type="number" min="0" name="adults" value="<?= e((string) $form['adults']) ?>">
      </div>
      <div class="form-group">
        <label>Children with extra bed</label>
        <input type="number" min="0" name="child_with_bed" value="<?= e((string) $form['child_with_bed']) ?>">
      </div>
      <div class="form-group">
        <label>Children without bed</label>
        <input type="number" min="0" name="child_without_bed" value="<?= e((string) $form['child_without_bed']) ?>">
      </div>
    </div>

    <h3 style="margin:1.2rem 0 0.6rem;font-size:1rem;">Hotel packages</h3>
    <p class="help">Name each package any way you like — Option 1, Luxury, Budget, 4 Star, 5 Star. Each package has its own hotels and amount. The itinerary stays the same.</p>
    <div id="package-list">
      <?php foreach ($packages as $pIndex => $package): ?>
        <div class="repeat-block package-row" data-package-index="<?= (int) $pIndex ?>">
          <div class="form-grid">
            <div class="form-group">
              <label>Package name</label>
              <input type="text" name="packages[<?= (int) $pIndex ?>][code]" value="<?= e((string) $package['code']) ?>" placeholder="Option 1 / Luxury / 4 Star" required maxlength="100">
              <input type="hidden" name="packages[<?= (int) $pIndex ?>][label]" value="">
            </div>
            <div class="form-group">
              <label>Price per person (₹)</label>
              <input type="number" step="0.01" name="packages[<?= (int) $pIndex ?>][price_per_person]" value="<?= e((string) $package['price_per_person']) ?>">
            </div>
            <div class="form-group">
              <label>Child with bed price (₹)</label>
              <input type="number" step="0.01" name="packages[<?= (int) $pIndex ?>][child_with_bed_price]" value="<?= e((string) $package['child_with_bed_price']) ?>">
            </div>
            <div class="form-group">
              <label>Child without bed price (₹)</label>
              <input type="number" step="0.01" name="packages[<?= (int) $pIndex ?>][child_without_bed_price]" value="<?= e((string) $package['child_without_bed_price']) ?>">
            </div>
          </div>
          <p class="help" style="margin:0.4rem 0;">Hotels for this package</p>
          <div class="stay-list">
            <?php foreach ($package['stays'] as $stay): ?>
              <div class="repeat-block stay-row">
                <div class="form-grid">
                  <div class="form-group">
                    <label>Nights</label>
                    <input type="number" min="0" name="packages[<?= (int) $pIndex ?>][stay_nights][]" value="<?= e((string) ($stay['nights'] ?? '')) ?>">
                  </div>
                  <div class="form-group">
                    <label>City / place</label>
                    <input type="text" name="packages[<?= (int) $pIndex ?>][stay_city][]" value="<?= e((string) ($stay['city'] ?? '')) ?>" placeholder="Kasol">
                  </div>
                  <div class="form-group">
                    <label>Hotel name</label>
                    <input type="text" name="packages[<?= (int) $pIndex ?>][stay_hotel][]" value="<?= e((string) ($stay['hotel_name'] ?? '')) ?>" placeholder="Royal Palace (Super Deluxe Room)">
                  </div>
                  <div class="form-group">
                    <label>Meal plan</label>
                    <input type="text" name="packages[<?= (int) $pIndex ?>][stay_meal][]" value="<?= e((string) ($stay['meal_plan'] ?? '')) ?>" placeholder="Breakfast & Dinner">
                  </div>
                </div>
                <button class="btn btn-outline btn-sm" type="button" onclick="this.closest('.stay-row').remove()">Remove hotel</button>
              </div>
            <?php endforeach; ?>
          </div>
          <div class="actions" style="margin-top:0.5rem;">
            <button class="btn btn-outline btn-sm" type="button" onclick="addStay(this)">+ Add hotel</button>
            <button class="btn btn-outline btn-sm" type="button" onclick="removePackage(this)">Remove package</button>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <button class="btn btn-outline btn-sm" type="button" onclick="addPackage()">+ Add hotel package</button>

    <h3 style="margin:1.2rem 0 0.6rem;font-size:1rem;">Notes &amp; terms text</h3>
    <div class="form-grid">
      <div class="form-group full">
        <label>Room note</label>
        <input type="text" name="room_note" value="<?= e((string) $form['room_note']) ?>">
      </div>
      <div class="form-group full">
        <label>Child policy</label>
        <input type="text" name="child_free_note" value="<?= e((string) $form['child_free_note']) ?>">
      </div>
      <div class="form-group">
        <label>Inclusions (one per line)</label>
        <textarea name="inclusions" style="min-height:120px;"><?= e((string) $form['inclusions']) ?></textarea>
      </div>
      <div class="form-group">
        <label>Exclusions (one per line)</label>
        <textarea name="exclusions" style="min-height:120px;"><?= e((string) $form['exclusions']) ?></textarea>
      </div>
      <div class="form-group full">
        <label>Notes</label>
        <textarea name="notes" style="min-height:80px;"><?= e((string) $form['notes']) ?></textarea>
      </div>
    </div>

    <div class="actions" style="margin-top:1rem;">
      <button class="btn" type="submit">Save quotation</button>
      <a class="btn btn-outline" href="<?= auth_can('enquiries') ? '/admin/enquiries/view.php?id=' . (int) $enquiry['id'] : '/admin/quotations/index.php' ?>">Cancel</a>
    </div>
  </form>
  <?php if ($quote && auth_can('quotations.delete')): ?>
    <form method="post" style="margin-top:0.8rem;" onsubmit="return confirm('Delete this quotation?');">
      <input type="hidden" name="action" value="delete">
      <button class="btn btn-danger btn-sm" type="submit">Delete quotation</button>
    </form>
  <?php endif; ?>
</div>
<script>
let packageIndex = <?= (int) count($packages) ?>;

function addDay() {
  const list = document.getElementById('day-list');
  const div = document.createElement('div');
  div.className = 'repeat-block day-row';
  div.innerHTML = `
    <div class="form-grid">
      <div class="form-group"><label>Date</label><input type="date" name="day_date[]"></div>
      <div class="form-group"><label>Heading</label><input type="text" name="day_heading[]" placeholder="Sightseeing"></div>
      <div class="form-group full"><label>Plan</label><textarea name="day_description[]" style="min-height:70px;"></textarea></div>
    </div>
    <button class="btn btn-outline btn-sm" type="button" onclick="this.closest('.day-row').remove()">Remove day</button>`;
  list.appendChild(div);
}

function stayMarkup(index) {
  return `
    <div class="repeat-block stay-row">
      <div class="form-grid">
        <div class="form-group"><label>Nights</label><input type="number" min="0" name="packages[${index}][stay_nights][]"></div>
        <div class="form-group"><label>City / place</label><input type="text" name="packages[${index}][stay_city][]" placeholder="Manali"></div>
        <div class="form-group"><label>Hotel name</label><input type="text" name="packages[${index}][stay_hotel][]"></div>
        <div class="form-group"><label>Meal plan</label><input type="text" name="packages[${index}][stay_meal][]" value="Breakfast & Dinner"></div>
      </div>
      <button class="btn btn-outline btn-sm" type="button" onclick="this.closest('.stay-row').remove()">Remove hotel</button>
    </div>`;
}

function addStay(button) {
  const packageRow = button.closest('.package-row');
  const index = packageRow.getAttribute('data-package-index');
  packageRow.querySelector('.stay-list').insertAdjacentHTML('beforeend', stayMarkup(index));
}

function nextPackageCode() {
  const used = [...document.querySelectorAll('#package-list input[name$="[code]"]')].map((el) => el.value.trim().toLowerCase());
  let n = 1;
  while (used.includes('option ' + n)) n += 1;
  return 'Option ' + n;
}

function addPackage() {
  const index = packageIndex++;
  const code = nextPackageCode();
  const list = document.getElementById('package-list');
  const div = document.createElement('div');
  div.className = 'repeat-block package-row';
  div.setAttribute('data-package-index', String(index));
  div.innerHTML = `
    <div class="form-grid">
      <div class="form-group"><label>Package name</label><input type="text" name="packages[${index}][code]" value="${code}" required maxlength="100"><input type="hidden" name="packages[${index}][label]" value=""></div>
      <div class="form-group"><label>Price per person (₹)</label><input type="number" step="0.01" name="packages[${index}][price_per_person]"></div>
      <div class="form-group"><label>Child with bed price (₹)</label><input type="number" step="0.01" name="packages[${index}][child_with_bed_price]"></div>
      <div class="form-group"><label>Child without bed price (₹)</label><input type="number" step="0.01" name="packages[${index}][child_without_bed_price]"></div>
    </div>
    <p class="help" style="margin:0.4rem 0;">Hotels for this package</p>
    <div class="stay-list">${stayMarkup(index)}</div>
    <div class="actions" style="margin-top:0.5rem;">
      <button class="btn btn-outline btn-sm" type="button" onclick="addStay(this)">+ Add hotel</button>
      <button class="btn btn-outline btn-sm" type="button" onclick="removePackage(this)">Remove package</button>
    </div>`;
  list.appendChild(div);
}

function removePackage(button) {
  const list = document.getElementById('package-list');
  if (list.querySelectorAll('.package-row').length <= 1) {
    alert('Keep at least one hotel package.');
    return;
  }
  button.closest('.package-row').remove();
}
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
