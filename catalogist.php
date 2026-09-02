<?php

declare(strict_types=1);

/**
 * Plugin Name: Catalogist
 * Description: WooCommerce Catalog Builder for WordPress.
 * Version: 0.1.0
 * Author: Mahdi Sangsari
 * Text Domain: catalogist
 */

defined('ABSPATH') || exit;

require_once __DIR__ . '/vendor/autoload.php';

\Catalogist\Plugin::boot();