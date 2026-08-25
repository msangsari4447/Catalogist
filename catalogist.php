<?php
/**
 * Plugin Name:       Catalogist
 * Plugin URI:        https://example.com/catalogist
 * Description:       Professional WooCommerce catalog builder for WordPress.
 * Version:           0.1.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            Catalogist
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       catalogist
 * Domain Path:       /languages
 *
 * @package Catalogist
 */

declare(strict_types=1);

use Catalogist\Core\Plugin;

defined( 'ABSPATH' ) || exit;

/**
 * Plugin constants.
 */
define( 'CATALOGIST_FILE', __FILE__ );
define( 'CATALOGIST_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CATALOGIST_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'CATALOGIST_VERSION', '0.1.0' );

if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}

// Load Elementor functions (conditional - only active when Elementor is loaded).
require_once __DIR__ . '/src/Elementor/functions.php';

/**
 * Retrieve the plugin instance.
 *
 * @return Plugin
 */
function catalogist() {
	return Plugin::instance();
}

// Boot only after WordPress has fully loaded the active plugins.
add_action(
	'plugins_loaded',
	static function () {
		catalogist()->boot();
	}
);
