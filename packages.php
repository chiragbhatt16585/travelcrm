<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/site.php';
site_dispatch_template('packages');
header('Location: /#packages');
exit;
