<?php
/**
 * PHPUnit bootstrap file for Catalogist tests.
 *
 * @package Catalogist
 */

declare(strict_types=1);

// Composer autoloader.
$vendor_autoload = __DIR__ . '/../vendor/autoload.php';

if ( ! file_exists( $vendor_autoload ) ) {
	fwrite(
		STDERR,
		'Missing vendor directory. Run `composer install` first.' . PHP_EOL
	);
	exit( 1 );
}

require_once $vendor_autoload;

// Load WordPress test functions.
require_once __DIR__ . '/mocks/WordPressMocks.php';

// Load Elementor mocks.
require_once __DIR__ . '/mocks/ElementorMocks.php';

// Load Catalogist-specific mocks.
require_once __DIR__ . '/mocks/CatalogistMocks.php';
