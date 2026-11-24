<?php
declare(strict_types=1);
/** @var string $pageTitle */
/** @var string $activeNav */
$user = auth_user();
$flash = flash_get();
require_once __DIR__ . '/../../includes/site.php';
$company = site_profile();
$logo = trim((string) ($company['logo'] ?? ''));
if ($logo !== '' && !preg_match('#^(https?:)?//#i', $logo)) {
    $logo = '/' . ltrim($logo, '/');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle ?? 'Admin') ?> | <?= e($company['company_name']) ?> Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/admin/assets/admin.css">
</head>
<body>
<div class="admin-shell">
  <aside class="admin-sidebar">
    <div class="admin-brand">
      <?php if ($logo !== ''): ?>
        <a class="admin-logo" href="<?= e(auth_home_url()) ?>">
          <img src="<?= e($logo) ?>" alt="<?= e($company['company_name']) ?>">
        </a>
      <?php else: ?>
        <?= e($company['company_name']) ?>
      <?php endif; ?>
      <span>Admin Panel</span>
    </div>
    <nav class="admin-nav">
      <?php foreach (auth_sections() as $key => $section): ?>
        <?php if (!auth_can($key)) { continue; } ?>
        <a href="<?= e($section['href']) ?>"<?= !empty($section['blank']) ? ' target="_blank"' : '' ?> class="<?= ($activeNav ?? '') === $key ? 'active' : '' ?>"><?= e($section['label']) ?></a>
      <?php endforeach; ?>
      <a href="/admin/logout.php">Logout</a>
    </nav>
  </aside>
  <main class="admin-main">
    <div class="admin-top">
      <h1><?= e($pageTitle ?? 'Admin') ?></h1>
      <div style="color:#64748b;font-size:0.85rem;display:flex;gap:0.85rem;align-items:center;flex-wrap:wrap;">
        <span>Hi, <?= e($user['name'] ?? 'Admin') ?></span>
        <a href="/admin/password.php">Change password</a>
      </div>
    </div>
    <?php if ($flash): ?>
      <div class="flash flash-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
    <?php endif; ?>
