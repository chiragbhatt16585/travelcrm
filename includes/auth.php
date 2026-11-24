<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

function auth_start(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function auth_user(): ?array
{
    auth_start();
    return $_SESSION['admin'] ?? null;
}

function auth_check(): void
{
    if (!auth_account()) {
        auth_logout();
        redirect('/admin/login.php');
    }
    if (!auth_is_superadmin() && auth_is_expired()) {
        auth_logout();
        redirect('/admin/login.php?expired=1');
    }
    $permission = auth_page_permission();
    $allowed = $permission === null
        || (is_array($permission) ? auth_can_any($permission) : auth_can($permission));
    if (!$allowed) {
        flash_set('error', 'You do not have access to that section.');
        $home = auth_home_url();
        $here = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
        redirect($home === $here ? '/admin/no-access.php' : $home);
    }
}

function auth_account(): ?array
{
    static $account = false;
    if ($account !== false) {
        return $account;
    }
    $user = auth_user();
    if (!$user) {
        return $account = null;
    }
    $stmt = db()->prepare('SELECT id, username, name, role FROM admins WHERE id = ? LIMIT 1');
    $stmt->execute([(int) $user['id']]);
    $row = $stmt->fetch();
    return $account = $row ?: null;
}

function auth_is_superadmin(): bool
{
    $account = auth_account();
    return $account !== null && ($account['role'] ?? '') === 'superadmin';
}

function auth_sections(): array
{
    return [
        'dashboard' => ['label' => 'Dashboard', 'href' => '/admin/index.php'],
        'destinations' => ['label' => 'Destinations', 'href' => '/admin/destinations/index.php'],
        'packages' => ['label' => 'Packages', 'href' => '/admin/packages/index.php'],
        'enquiries' => ['label' => 'Enquiries & Follow-ups', 'href' => '/admin/enquiries/index.php'],
        'quotations' => ['label' => 'Quotations', 'href' => '/admin/quotations/index.php'],
        'quote-terms' => ['label' => 'Quotation terms', 'href' => '/admin/settings/quotation.php'],
        'company' => ['label' => 'Company profile', 'href' => '/admin/settings/company.php'],
        'template' => ['label' => 'Client template', 'href' => '/admin/settings/template.php'],
        'staff' => ['label' => 'Staff', 'href' => '/admin/staff/index.php'],
        'access' => ['label' => 'Access until', 'href' => '/admin/settings/access.php'],
        'website' => ['label' => 'View website', 'href' => '/', 'blank' => true],
    ];
}

function auth_superadmin_only(): array
{
    return ['quote-terms', 'company', 'template', 'staff', 'access'];
}

function auth_action_groups(): array
{
    return [
        'dashboard' => [
            'label' => 'Dashboard',
            'actions' => [
                'dashboard' => 'Open dashboard',
            ],
        ],
        'destinations' => [
            'label' => 'Destinations',
            'actions' => [
                'destinations.add' => 'Add',
                'destinations.edit' => 'Edit',
                'destinations.view' => 'View',
                'destinations.delete' => 'Delete',
            ],
        ],
        'packages' => [
            'label' => 'Packages',
            'actions' => [
                'packages.add' => 'Add',
                'packages.edit' => 'Edit',
                'packages.view' => 'View',
                'packages.delete' => 'Delete',
            ],
        ],
        'enquiries' => [
            'label' => 'Enquiries & Follow-ups',
            'actions' => [
                'enquiries.add' => 'Add',
                'enquiries.view' => 'View',
                'enquiries.edit' => 'Follow-up',
                'enquiries.call' => 'Call',
                'enquiries.whatsapp' => 'WhatsApp',
            ],
        ],
        'quotations' => [
            'label' => 'Quotations',
            'actions' => [
                'quotations.add' => 'Add',
                'quotations.edit' => 'Edit',
                'quotations.view' => 'View',
                'quotations.delete' => 'Delete',
                'quotations.print' => 'Print',
                'quotations.pdf' => 'Download PDF',
                'quotations.whatsapp' => 'Send on WhatsApp',
                'quotations.payment' => 'Payments',
            ],
        ],
        'website' => [
            'label' => 'Website',
            'actions' => [
                'website' => 'View website',
            ],
        ],
    ];
}

function auth_assignable_permissions(): array
{
    $permissions = [];
    foreach (auth_action_groups() as $group) {
        foreach ($group['actions'] as $key => $label) {
            $permissions[$key] = $label;
        }
    }
    return $permissions;
}

function auth_legacy_actions(): array
{
    $map = [];
    foreach (auth_action_groups() as $section => $group) {
        $actions = array_keys($group['actions']);
        if ($actions === [$section]) {
            continue;
        }
        $map[$section] = $actions;
    }
    return $map;
}

function auth_normalize_grants(array $grants): array
{
    $legacy = auth_legacy_actions();
    $normalized = [];
    foreach ($grants as $grant) {
        if (isset($legacy[$grant])) {
            foreach ($legacy[$grant] as $action) {
                $normalized[] = $action;
            }
            continue;
        }
        $normalized[] = (string) $grant;
    }
    return array_values(array_unique($normalized));
}

function auth_grants(): array
{
    static $grants = null;
    if ($grants !== null) {
        return $grants;
    }
    $account = auth_account();
    if (!$account) {
        return $grants = [];
    }
    if (($account['role'] ?? '') === 'superadmin') {
        return $grants = array_keys(auth_sections());
    }
    $stmt = db()->prepare('SELECT permission FROM admin_permissions WHERE admin_id = ?');
    $stmt->execute([(int) $account['id']]);
    return $grants = auth_normalize_grants(array_column($stmt->fetchAll(), 'permission'));
}

function auth_can(string $permission): bool
{
    if (auth_is_superadmin()) {
        return true;
    }
    $grants = auth_grants();
    if (in_array($permission, $grants, true)) {
        return true;
    }
    $prefix = $permission . '.';
    foreach ($grants as $grant) {
        if (str_starts_with($grant, $prefix)) {
            return true;
        }
    }
    return false;
}

function auth_can_any(array $permissions): bool
{
    foreach ($permissions as $permission) {
        if (auth_can($permission)) {
            return true;
        }
    }
    return false;
}

function auth_home_url(): string
{
    foreach (auth_sections() as $key => $section) {
        if ($key === 'website' || !auth_can($key)) {
            continue;
        }
        return $section['href'];
    }
    return '/admin/no-access.php';
}

function auth_page_permission(): string|array|null
{
    $path = (string) parse_url((string) ($_SERVER['SCRIPT_NAME'] ?? ''), PHP_URL_PATH);
    $file = basename($path);
    if ($path === '/admin/index.php') {
        return 'dashboard';
    }
    if (str_starts_with($path, '/admin/destinations/')) {
        return match ($file) {
            'create.php' => 'destinations.add',
            'edit.php' => 'destinations.edit',
            'delete.php' => 'destinations.delete',
            'form.php' => auth_record_permission('destinations'),
            default => 'destinations',
        };
    }
    if (str_starts_with($path, '/admin/packages/')) {
        return match ($file) {
            'create.php' => 'packages.add',
            'edit.php' => 'packages.edit',
            'delete.php' => 'packages.delete',
            'form.php' => auth_record_permission('packages'),
            default => 'packages',
        };
    }
    if (str_starts_with($path, '/admin/enquiries/')) {
        if ($file === 'create.php') {
            return 'enquiries.add';
        }
        if ($file === 'view.php' && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            return 'enquiries.edit';
        }
        return 'enquiries';
    }
    if (str_starts_with($path, '/admin/quotations/')) {
        return match ($file) {
            'print.php' => ['quotations.view', 'quotations.print', 'quotations.pdf', 'quotations.whatsapp', 'quotations.payment'],
            'pdf.php' => 'quotations.pdf',
            'send-whatsapp.php' => 'quotations.whatsapp',
            'payment.php' => 'quotations.payment',
            'receipt.php' => 'quotations.payment',
            'receipt-pdf.php' => 'quotations.pdf',
            'send-receipt.php' => 'quotations.whatsapp',
            'form.php' => auth_quotation_form_permission(),
            default => 'quotations',
        };
    }
    $map = [
        '/admin/settings/quotation.php' => 'quote-terms',
        '/admin/settings/company.php' => 'company',
        '/admin/settings/template.php' => 'template',
        '/admin/staff/' => 'staff',
        '/admin/settings/access.php' => 'access',
    ];
    foreach ($map as $prefix => $key) {
        if ($path === $prefix || str_starts_with($path, $prefix)) {
            return $key;
        }
    }
    return null;
}

function auth_record_permission(string $section): string
{
    $id = (int) ($_GET['id'] ?? 0);
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        $id = (int) ($_POST['id'] ?? $id);
    }
    return $id > 0 ? $section . '.edit' : $section . '.add';
}

function auth_quotation_form_permission(): string
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['action'] ?? '') === 'delete') {
        return 'quotations.delete';
    }
    return (int) ($_GET['id'] ?? 0) > 0 ? 'quotations.edit' : 'quotations.add';
}

function auth_attempt(string $username, string $password): bool
{
    $stmt = db()->prepare('SELECT * FROM admins WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $admin = $stmt->fetch();
    if (!$admin || !password_verify($password, $admin['password_hash'])) {
        return false;
    }
    if (($admin['role'] ?? '') !== 'superadmin' && auth_is_expired()) {
        $GLOBALS['auth_login_error'] = 'expired';
        return false;
    }

    auth_start();
    $_SESSION['admin'] = [
        'id' => (int) $admin['id'],
        'username' => $admin['username'],
        'name' => $admin['name'],
    ];
    return true;
}

function auth_login_error(): string
{
    return (string) ($GLOBALS['auth_login_error'] ?? '');
}

function auth_valid_until(): string
{
    static $value = null;
    if ($value !== null) {
        return $value;
    }
    try {
        $stmt = db()->prepare('SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1');
        $stmt->execute(['software_valid_until']);
        $raw = $stmt->fetchColumn();
        $value = is_string($raw) ? $raw : '';
    } catch (Throwable $e) {
        $value = '';
    }
    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : '';
}

function auth_is_expired(): bool
{
    $until = auth_valid_until();
    return $until !== '' && date('Y-m-d') > $until;
}

function auth_logout(): void
{
    auth_start();
    unset($_SESSION['admin']);
    session_destroy();
}
