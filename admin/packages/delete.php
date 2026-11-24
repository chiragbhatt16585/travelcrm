<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
auth_check();

$id = (int) ($_GET['id'] ?? 0);
$package = get_package_by_id($id);
if (!$package) {
    flash_set('error', 'Package not found.');
    redirect('/admin/packages/index.php');
}

db()->prepare('DELETE FROM packages WHERE id = ?')->execute([$id]);
flash_set('success', 'Package deleted.');
redirect('/admin/packages/index.php');
