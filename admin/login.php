<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/site.php';
$company = site_profile();

auth_start();
if (auth_user()) {
    if (!auth_is_superadmin() && auth_is_expired()) {
        auth_logout();
        $_GET['expired'] = '1';
    } else {
        redirect(auth_home_url());
    }
}

$logo = trim((string) ($company['logo'] ?? ''));
if ($logo !== '' && !preg_match('#^(https?:)?//#i', $logo)) {
    $logo = '/' . ltrim($logo, '/');
}
$tagline = trim((string) ($company['tagline'] ?? ''));

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($username === '' || $password === '') {
        $error = 'Please enter username and password.';
    } elseif (auth_attempt($username, $password)) {
        redirect(auth_home_url());
    } elseif (auth_login_error() === 'expired') {
        $error = 'This trial has ended. Sign in is closed for this account.';
    } else {
        $error = 'Invalid username or password.';
    }
}
if ($error === '' && ($_GET['expired'] ?? '') === '1') {
    $error = 'This trial has ended. Sign in is closed for this account.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login | <?= e($company['company_name']) ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/admin/assets/admin.css">
</head>
<body class="login-body">
  <div class="login-stage">
    <section class="login-brand">
      <div class="login-logo">
        <?php if ($logo !== ''): ?>
          <img src="<?= e($logo) ?>" alt="<?= e($company['company_name']) ?>">
        <?php else: ?>
          <span class="login-initials"><?= e($company['initials']) ?></span>
        <?php endif; ?>
      </div>
      <h1><?= e($company['company_name']) ?></h1>
      <?php if ($tagline !== ''): ?><p><?= e($tagline) ?></p><?php endif; ?>
    </section>
    <section class="login-panel">
      <form class="login-card" method="post" autocomplete="off">
        <p class="login-kicker">Admin</p>
        <h2>Sign in</h2>
        <p class="login-lead">Enquiries, quotations, and packages.</p>
        <?php if ($error): ?>
          <div class="flash flash-error"><?= e($error) ?></div>
        <?php endif; ?>
        <div class="form-group">
          <label>Username</label>
          <input type="text" name="username" required value="<?= e($_POST['username'] ?? '') ?>" autocomplete="username">
        </div>
        <div class="form-group">
          <label>Password</label>
          <input type="password" name="password" required autocomplete="current-password">
        </div>
        <button class="btn login-submit" type="submit">Sign in</button>
      </form>
    </section>
  </div>
</body>
</html>
