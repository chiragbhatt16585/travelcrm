<?php
declare(strict_types=1);

$path = rawurldecode((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));
$path = '/' . trim($path, '/');
if ($path !== '/') {
    $path = rtrim($path, '/') ?: '/';
}

if ($path === '/router.php') {
    http_response_code(404);
    echo 'Not found';
    return true;
}

$full = __DIR__ . ($path === '/' ? '' : $path);
$isGet = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET';

$go = static function (string $target, array $drop = []) use ($isGet): void {
    if (!$isGet) {
        return;
    }
    $params = $_GET;
    foreach ($drop as $key) {
        unset($params[$key]);
    }
    $qs = http_build_query($params);
    header('Location: ' . $target . ($qs !== '' ? '?' . $qs : ''), true, 301);
    exit;
};

$legacy = [
    '/index.php' => '/',
    '/packages.php' => '/packages',
    '/services.php' => '/about',
    '/contact.php' => '/contact',
];

if (isset($legacy[$path])) {
    $go($legacy[$path]);
    $path = $legacy[$path];
    $full = __DIR__ . $path;
} elseif ($path === '/destination.php' && isset($_GET['dest'])) {
    $go('/destinations/' . rawurlencode((string) $_GET['dest']), ['dest']);
} elseif ($path === '/package.php' && isset($_GET['id'])) {
    $go('/package/' . (int) $_GET['id'], ['id']);
} elseif ($path === '/quote.php' && isset($_GET['t'])) {
    $go('/quote/' . rawurlencode((string) $_GET['t']), ['t']);
}

if ($path !== '/' && (is_file($full) || is_dir($full)) && !str_ends_with(strtolower($path), '.php')) {
    return false;
}

$script = null;
if ($path === '/') {
    $script = '/index.php';
} elseif ($path === '/packages') {
    $script = '/packages.php';
} elseif ($path === '/about') {
    $script = '/services.php';
} elseif ($path === '/contact') {
    $script = '/contact.php';
} elseif (preg_match('#^/destinations/([A-Za-z0-9-]+)$#', $path, $match)) {
    $_GET['dest'] = $match[1];
    $_REQUEST['dest'] = $match[1];
    $script = '/destination.php';
} elseif (preg_match('#^/package/([0-9]+)$#', $path, $match)) {
    $_GET['id'] = $match[1];
    $_REQUEST['id'] = $match[1];
    $script = '/package.php';
} elseif (preg_match('#^/quote/([A-Za-z0-9]+)$#', $path, $match)) {
    $_GET['t'] = $match[1];
    $_REQUEST['t'] = $match[1];
    $script = '/quote.php';
}

if ($script === null) {
    if (is_file($full)) {
        return false;
    }
    http_response_code(404);
    echo 'Not found';
    return true;
}

$_SERVER['SCRIPT_NAME'] = $script;
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . $script;
$_SERVER['PHP_SELF'] = $script;
require __DIR__ . $script;
return true;
