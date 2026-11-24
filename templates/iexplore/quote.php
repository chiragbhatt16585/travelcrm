<?php
declare(strict_types=1);
require_once __DIR__ . '/layout.php';
require_once dirname(__DIR__, 2) . '/includes/quotation_sheet.php';

/** @var array|null $quote */
/** @var array $days */
/** @var array $stays */
/** @var array $company */
/** @var array $siblings */
/** @var string $title */

ix_head($title);
echo '<style>' . quotation_sheet_styles() . '.qd-desk{padding:1.5rem 0 2.5rem;}</style>';
ix_header('');
if (!function_exists('quote_sheet_markup')) {
    echo '<p class="ix-wrap">Quotation unavailable.</p>';
} else {
    quote_sheet_markup($quote, $days, $stays, $company, $siblings, $packages ?? []);
}
ix_footer();
