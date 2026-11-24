<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
auth_check();

$id = (int) ($_GET['id'] ?? 0);
$destination = get_destination_by_id($id);
if (!$destination) {
    flash_set('error', 'Destination not found.');
    redirect('/admin/destinations/index.php');
}

db()->prepare('DELETE FROM destinations WHERE id = ?')->execute([$id]);
flash_set('success', 'Destination deleted.');
redirect('/admin/destinations/index.php');
