<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    return trim($text, '-') ?: 'item';
}

function format_inr(?float $amount): string
{
    if ($amount === null) {
        return 'On request';
    }
    return '₹' . number_format($amount, 0);
}

function nights_label(?int $nights, ?int $days): string
{
    if ($nights && $days) {
        return $nights . ' Nights / ' . $days . ' Days';
    }
    if ($nights) {
        return $nights . 'N';
    }
    return '';
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function flash_set(string $type, string $message): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function flash_get(): ?array
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    if (empty($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function upload_image(array $file, string $folder): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Image upload failed.');
    }

    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed, true)) {
        throw new RuntimeException('Only JPG, PNG, WEBP or GIF images are allowed.');
    }

    $dir = dirname(__DIR__) . '/uploads/' . $folder;
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $filename = uniqid($folder . '_', true) . '.' . $ext;
    $target = $dir . '/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $target)) {
        throw new RuntimeException('Could not save uploaded image.');
    }

    return 'uploads/' . $folder . '/' . $filename;
}

function get_destinations(string $type = '', bool $activeOnly = true): array
{
    $sql = 'SELECT d.*,
            (SELECT COUNT(*) FROM packages p WHERE p.destination_id = d.id AND p.is_active = 1) AS package_count,
            (SELECT MIN(p.starting_price) FROM packages p WHERE p.destination_id = d.id AND p.is_active = 1) AS min_package_price
            FROM destinations d WHERE 1=1';
    $params = [];

    if ($activeOnly) {
        $sql .= ' AND d.is_active = 1';
    }
    if ($type !== '') {
        $sql .= ' AND d.type = ?';
        $params[] = $type;
    }
    $sql .= ' ORDER BY d.sort_order ASC, d.name ASC';

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function get_destination_by_slug(string $slug): ?array
{
    $stmt = db()->prepare('SELECT * FROM destinations WHERE slug = ? AND is_active = 1 LIMIT 1');
    $stmt->execute([$slug]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function get_destination_by_id(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM destinations WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function get_packages_by_destination(int $destinationId, bool $activeOnly = true): array
{
    $sql = 'SELECT * FROM packages WHERE destination_id = ?';
    if ($activeOnly) {
        $sql .= ' AND is_active = 1';
    }
    $sql .= ' ORDER BY sort_order ASC, id ASC';
    $stmt = db()->prepare($sql);
    $stmt->execute([$destinationId]);
    return $stmt->fetchAll();
}

function get_package_by_id(int $id): ?array
{
    $stmt = db()->prepare(
        'SELECT p.*, d.name AS destination_name, d.slug AS destination_slug, d.type AS destination_type
         FROM packages p
         JOIN destinations d ON d.id = p.destination_id
         WHERE p.id = ? LIMIT 1'
    );
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function get_package_details(int $packageId): array
{
    $itinerary = db()->prepare('SELECT * FROM package_itinerary WHERE package_id = ? ORDER BY sort_order ASC, id ASC');
    $itinerary->execute([$packageId]);

    $hotels = db()->prepare('SELECT * FROM package_hotels WHERE package_id = ? ORDER BY sort_order ASC, id ASC');
    $hotels->execute([$packageId]);

    $items = db()->prepare('SELECT * FROM package_items WHERE package_id = ? ORDER BY sort_order ASC, id ASC');
    $items->execute([$packageId]);

    $allItems = $items->fetchAll();
    $inclusions = array_values(array_filter($allItems, fn($i) => $i['item_type'] === 'inclusion'));
    $exclusions = array_values(array_filter($allItems, fn($i) => $i['item_type'] === 'exclusion'));

    $hotelRows = $hotels->fetchAll();
    $groupedHotels = [];
    foreach ($hotelRows as $row) {
        $loc = $row['location_name'];
        if (!isset($groupedHotels[$loc])) {
            $groupedHotels[$loc] = [];
        }
        $groupedHotels[$loc][] = $row;
    }

    return [
        'itinerary' => $itinerary->fetchAll(),
        'hotels' => $hotelRows,
        'hotel_groups' => $groupedHotels,
        'inclusions' => $inclusions,
        'exclusions' => $exclusions,
    ];
}

function parse_lines(string $text): array
{
    $lines = preg_split('/\r\n|\r|\n/', $text) ?: [];
    $result = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line !== '') {
            $result[] = $line;
        }
    }
    return $result;
}

function save_package_children(int $packageId, array $data): void
{
    $pdo = db();
    $pdo->prepare('DELETE FROM package_itinerary WHERE package_id = ?')->execute([$packageId]);
    $pdo->prepare('DELETE FROM package_hotels WHERE package_id = ?')->execute([$packageId]);
    $pdo->prepare('DELETE FROM package_items WHERE package_id = ?')->execute([$packageId]);

    $itStmt = $pdo->prepare(
        'INSERT INTO package_itinerary (package_id, day_label, description, sort_order) VALUES (?, ?, ?, ?)'
    );
    foreach (($data['itinerary'] ?? []) as $i => $row) {
        if (empty($row['day_label']) || empty($row['description'])) {
            continue;
        }
        $itStmt->execute([$packageId, trim($row['day_label']), trim($row['description']), $i]);
    }

    $hStmt = $pdo->prepare(
        'INSERT INTO package_hotels (package_id, location_name, category, hotel_name, sort_order) VALUES (?, ?, ?, ?, ?)'
    );
    foreach (($data['hotels'] ?? []) as $i => $row) {
        if (empty($row['location_name']) || empty($row['category']) || empty($row['hotel_name'])) {
            continue;
        }
        $hStmt->execute([
            $packageId,
            trim($row['location_name']),
            trim($row['category']),
            trim($row['hotel_name']),
            $i,
        ]);
    }

    $iStmt = $pdo->prepare(
        'INSERT INTO package_items (package_id, item_type, content, sort_order) VALUES (?, ?, ?, ?)'
    );
    foreach (($data['inclusions'] ?? []) as $i => $content) {
        $content = trim((string) $content);
        if ($content === '') {
            continue;
        }
        $iStmt->execute([$packageId, 'inclusion', $content, $i]);
    }
    foreach (($data['exclusions'] ?? []) as $i => $content) {
        $content = trim((string) $content);
        if ($content === '') {
            continue;
        }
        $iStmt->execute([$packageId, 'exclusion', $content, $i]);
    }
}

function enquiry_statuses(): array
{
    return [
        'new' => 'New',
        'contacted' => 'Contacted',
        'follow_up' => 'Follow-up',
        'interested' => 'Interested',
        'booked' => 'Booked',
        'not_interested' => 'Not Interested',
        'closed' => 'Closed',
    ];
}

function enquiry_priorities(): array
{
    return [
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
    ];
}

function enquiry_parse_count(string $raw): ?int
{
    $raw = trim($raw);
    if ($raw === '') {
        return null;
    }
    if (!preg_match('/^\d{1,2}$/', $raw)) {
        throw new RuntimeException('Adults and children must be a whole number from 0 to 99.');
    }
    return (int) $raw;
}

function enquiry_party_label(array $row): string
{
    $parts = [];
    if (array_key_exists('adults', $row) && $row['adults'] !== null && $row['adults'] !== '') {
        $count = (int) $row['adults'];
        $parts[] = $count . ' adult' . ($count === 1 ? '' : 's');
    }
    if (array_key_exists('children', $row) && $row['children'] !== null && $row['children'] !== '') {
        $count = (int) $row['children'];
        $parts[] = $count . ($count === 1 ? ' child' : ' children');
    }
    return implode(' · ', $parts);
}

function enquiry_status_badge(string $status): string
{
    $map = [
        'new' => 'badge-new',
        'contacted' => 'badge-contacted',
        'follow_up' => 'badge-followup',
        'interested' => 'badge-interested',
        'booked' => 'badge-ok',
        'not_interested' => 'badge-off',
        'closed' => 'badge-off',
    ];
    return $map[$status] ?? 'badge-off';
}

function get_enquiry_by_id(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM enquiries WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function get_enquiry_followups(int $enquiryId): array
{
    $stmt = db()->prepare(
        'SELECT * FROM enquiry_followups WHERE enquiry_id = ? ORDER BY created_at DESC, id DESC'
    );
    $stmt->execute([$enquiryId]);
    return $stmt->fetchAll();
}

function quotation_statuses(): array
{
    return [
        'draft' => 'Draft',
        'sent' => 'Sent',
        'accepted' => 'Accepted',
        'rejected' => 'Rejected',
    ];
}

function quotation_status_badge(string $status): string
{
    return match ($status) {
        'sent' => 'badge-sent',
        'accepted' => 'badge-ok',
        'rejected' => 'badge-off',
        default => 'badge-draft',
    };
}

function format_quote_inr(?float $amount): string
{
    if ($amount === null) {
        return '';
    }
    return 'INR ' . number_format($amount, 0) . '/-';
}

function quotations_all(): array
{
    return db()->query(
        'SELECT q.*, e.name AS client_name, e.phone AS client_phone, e.destination AS client_destination
         FROM quotations q
         JOIN enquiries e ON e.id = q.enquiry_id
         ORDER BY q.updated_at DESC, q.id DESC'
    )->fetchAll();
}

function quotations_for_enquiry(int $enquiryId): array
{
    $stmt = db()->prepare('SELECT * FROM quotations WHERE enquiry_id = ? ORDER BY option_no ASC, id ASC');
    $stmt->execute([$enquiryId]);
    return $stmt->fetchAll();
}

function get_quotation(int $id): ?array
{
    $stmt = db()->prepare(
        'SELECT q.*, e.name AS client_name, e.phone AS client_phone, e.email AS client_email, e.destination AS client_destination
         FROM quotations q
         JOIN enquiries e ON e.id = q.enquiry_id
         WHERE q.id = ? LIMIT 1'
    );
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function get_quotation_by_token(string $token): ?array
{
    $stmt = db()->prepare(
        'SELECT q.*, e.name AS client_name, e.phone AS client_phone, e.email AS client_email, e.destination AS client_destination
         FROM quotations q
         JOIN enquiries e ON e.id = q.enquiry_id
         WHERE q.token = ? LIMIT 1'
    );
    $stmt->execute([$token]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function quotation_days(int $quotationId): array
{
    $stmt = db()->prepare('SELECT * FROM quotation_days WHERE quotation_id = ? ORDER BY sort_order ASC, id ASC');
    $stmt->execute([$quotationId]);
    return $stmt->fetchAll();
}

function quotation_stays(int $quotationId, ?int $packageId = null): array
{
    if ($packageId !== null) {
        $stmt = db()->prepare(
            'SELECT * FROM quotation_stays WHERE quotation_id = ? AND package_id = ? ORDER BY sort_order ASC, id ASC'
        );
        $stmt->execute([$quotationId, $packageId]);
        return $stmt->fetchAll();
    }
    $stmt = db()->prepare('SELECT * FROM quotation_stays WHERE quotation_id = ? ORDER BY sort_order ASC, id ASC');
    $stmt->execute([$quotationId]);
    return $stmt->fetchAll();
}

function quotation_hotel_packages(int $quotationId): array
{
    $stmt = db()->prepare(
        'SELECT * FROM quotation_hotel_packages WHERE quotation_id = ? ORDER BY sort_order ASC, id ASC'
    );
    $stmt->execute([$quotationId]);
    $packages = $stmt->fetchAll();
    foreach ($packages as &$package) {
        $package['stays'] = quotation_stays($quotationId, (int) $package['id']);
    }
    unset($package);
    return $packages;
}

function quotation_package_label(array $package): string
{
    $name = trim((string) ($package['code'] ?? ''));
    if ($name === '') {
        $name = trim((string) ($package['label'] ?? ''));
    }
    return $name !== '' ? $name : 'Option 1';
}

function quotation_package_total(array $quote, array $package): float
{
    return ((float) ($package['price_per_person'] ?? 0) * (int) ($quote['adults'] ?? 0))
        + ((float) ($package['child_with_bed_price'] ?? 0) * (int) ($quote['child_with_bed'] ?? 0))
        + ((float) ($package['child_without_bed_price'] ?? 0) * (int) ($quote['child_without_bed'] ?? 0));
}

function quotation_pricing_package(array $quote, ?array $packages = null): ?array
{
    $packages ??= quotation_hotel_packages((int) $quote['id']);
    if (!$packages) {
        return null;
    }
    $selectedId = (int) ($quote['selected_package_id'] ?? 0);
    if ($selectedId > 0) {
        foreach ($packages as $package) {
            if ((int) $package['id'] === $selectedId) {
                return $package;
            }
        }
    }
    return $packages[0];
}

function quotation_places_line(array $stays): string
{
    $parts = [];
    foreach ($stays as $stay) {
        $nights = (int) ($stay['nights'] ?? 0);
        $city = trim((string) $stay['city']);
        if ($city === '') {
            continue;
        }
        $parts[] = $city . ($nights > 0 ? ' ' . $nights . ' Night' . ($nights === 1 ? '' : 's') : '');
    }
    return implode(' / ', $parts);
}

function quotation_total(array $quote): float
{
    $package = !empty($quote['id']) ? quotation_pricing_package($quote) : null;
    if ($package) {
        return quotation_package_total($quote, $package);
    }
    return ((float) ($quote['price_per_person'] ?? 0) * (int) ($quote['adults'] ?? 0))
        + ((float) ($quote['child_with_bed_price'] ?? 0) * (int) ($quote['child_with_bed'] ?? 0))
        + ((float) ($quote['child_without_bed_price'] ?? 0) * (int) ($quote['child_without_bed'] ?? 0));
}

function quotation_next_package_code(array $existingCodes): string
{
    $normalized = array_map(
        static fn(string $code): string => strtolower(trim($code)),
        $existingCodes
    );
    $n = 1;
    while (in_array('option ' . $n, $normalized, true)) {
        $n++;
    }
    return 'Option ' . $n;
}

function quotation_empty_hotel_package(string $code = 'Option 1'): array
{
    return [
        'code' => $code,
        'label' => '',
        'price_per_person' => '',
        'child_with_bed_price' => '',
        'child_without_bed_price' => '',
        'stays' => [[
            'nights' => '',
            'city' => '',
            'hotel_name' => '',
            'meal_plan' => 'Breakfast & Dinner',
        ]],
    ];
}

function next_quotation_option(int $enquiryId): int
{
    $stmt = db()->prepare('SELECT COALESCE(MAX(option_no), 0) + 1 FROM quotations WHERE enquiry_id = ?');
    $stmt->execute([$enquiryId]);
    return (int) $stmt->fetchColumn();
}

function request_base_url(): string
{
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $host = $_SERVER['HTTP_HOST'] ?? '127.0.0.1:8000';
    return ($https ? 'https' : 'http') . '://' . $host;
}

function payment_txn_no(int $paymentId, string $paidOn): string
{
    $year = preg_match('/^\d{4}/', $paidOn, $match) ? $match[0] : date('Y');
    return 'TXN-' . $year . '-' . str_pad((string) $paymentId, 4, '0', STR_PAD_LEFT);
}

function payment_modes(): array
{
    return [
        'bank' => 'Bank transfer',
        'upi' => 'UPI',
        'cash' => 'Cash',
    ];
}

function quotation_payments(int $quotationId): array
{
    $stmt = db()->prepare(
        'SELECT * FROM quotation_payments WHERE quotation_id = ? ORDER BY paid_on ASC, id ASC'
    );
    $stmt->execute([$quotationId]);
    return $stmt->fetchAll();
}

function quotation_paid_amount(int $quotationId): float
{
    $stmt = db()->prepare('SELECT COALESCE(SUM(amount), 0) FROM quotation_payments WHERE quotation_id = ?');
    $stmt->execute([$quotationId]);
    return (float) $stmt->fetchColumn();
}

function quotation_paid_through(int $quotationId, string $paidOn, int $paymentId): float
{
    $stmt = db()->prepare(
        'SELECT COALESCE(SUM(amount), 0) FROM quotation_payments
         WHERE quotation_id = ? AND (paid_on < ? OR (paid_on = ? AND id <= ?))'
    );
    $stmt->execute([$quotationId, $paidOn, $paidOn, $paymentId]);
    return (float) $stmt->fetchColumn();
}

function quotation_balance(array $quote, float $paid): float
{
    return max(0, quotation_total($quote) - $paid);
}

function get_quotation_payment(int $id): ?array
{
    $stmt = db()->prepare(
        'SELECT p.*, q.enquiry_id, q.token, q.option_no, q.title, q.nights, q.days,
                q.adults, q.price_per_person, q.child_with_bed, q.child_with_bed_price,
                q.child_without_bed, q.child_without_bed_price,
                e.name AS client_name, e.phone AS client_phone, e.email AS client_email
         FROM quotation_payments p
         JOIN quotations q ON q.id = p.quotation_id
         JOIN enquiries e ON e.id = q.enquiry_id
         WHERE p.id = ? LIMIT 1'
    );
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function quotation_record_client_decision(array $quote, string $decision, ?int $packageId = null): void
{
    $newStatus = $decision === 'accept' ? 'accepted' : 'rejected';
    if (!in_array($newStatus, ['accepted', 'rejected'], true) || ($quote['status'] ?? '') === $newStatus) {
        return;
    }

    $packages = quotation_hotel_packages((int) $quote['id']);
    $chosen = null;
    if ($newStatus === 'accepted' && $packages) {
        if ($packageId) {
            foreach ($packages as $package) {
                if ((int) $package['id'] === $packageId) {
                    $chosen = $package;
                    break;
                }
            }
        }
        $chosen ??= $packages[0];
    }

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $pdo->prepare('UPDATE quotations SET status = ?, selected_package_id = ? WHERE id = ?')->execute([
            $newStatus,
            $chosen ? (int) $chosen['id'] : null,
            (int) $quote['id'],
        ]);
        $enquiry = get_enquiry_by_id((int) $quote['enquiry_id']);
        $from = (string) ($enquiry['status'] ?? '');
        $to = $from;
        if ($newStatus === 'accepted' && !in_array($from, ['booked', 'not_interested', 'closed', 'interested'], true)) {
            $to = 'interested';
            $pdo->prepare('UPDATE enquiries SET status = ? WHERE id = ?')->execute([$to, (int) $quote['enquiry_id']]);
        }
        $option = quotation_option_label($quote);
        $packageNote = $chosen ? ' (' . quotation_package_label($chosen) . ')' : '';
        $note = $newStatus === 'accepted'
            ? 'Client accepted ' . $option . $packageNote . ': ' . $quote['title']
            : 'Client declined ' . $option . ': ' . $quote['title'];
        $pdo->prepare(
            'INSERT INTO enquiry_followups (enquiry_id, note, status_from, status_to, created_by) VALUES (?, ?, ?, ?, ?)'
        )->execute([
            (int) $quote['enquiry_id'],
            $note,
            $from !== $to ? $from : null,
            $from !== $to ? $to : null,
            'Client',
        ]);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function quotation_mark_sent(array $quote, string $by): void
{
    $pdo = db();
    if (($quote['status'] ?? '') === 'draft') {
        $pdo->prepare('UPDATE quotations SET status = ? WHERE id = ? AND status = ?')->execute(['sent', (int) $quote['id'], 'draft']);
    }
    $option = quotation_option_label($quote);
    $pdo->prepare(
        'INSERT INTO enquiry_followups (enquiry_id, note, created_by) VALUES (?, ?, ?)'
    )->execute([
        (int) $quote['enquiry_id'],
        'Sent ' . $option . ' PDF on WhatsApp: ' . $quote['title'],
        $by !== '' ? $by : 'Admin',
    ]);
}
