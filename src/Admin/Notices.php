<?php
/**
 * Admin notices handler.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Admin;

use Catalogist\Core\HookableInterface;

/**
 * Renders admin notices.
 */
final class Notices implements HookableInterface {

	/**
	 * Register hooks with WordPress.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'admin_notices', array( $this, 'show_notices' ) );
	}

	/**
	 * Show admin notices.
	 *
	 * @return void
	 */
	public function show_notices(): void {
		$this->maybe_show_woocommerce_notice();
	}

	/**
	 * Show WooCommerce missing notice if applicable.
	 *
	 * @return void
	 */
	private function maybe_show_woocommerce_notice(): void {
		if ( class_exists( 'WooCommerce' ) ) {
			return;
		}

		$screen = get_current_screen();

		if ( ! $screen ) {
			return;
		}

		if ( false === strpos( $screen->id, 'catalogist' ) ) {
			return;
		}

		$this->render_template( 'missing-woocommerce' );
	}

	/**
	 * Render a notice template.
	 *
	 * @param string $template Template name.
	 *
	 * @return void
	 */
	private function render_template( string $template ): void {
		$template_path = CATALOGIST_PLUGIN_DIR . 'templates/admin/notices/' . $template . '.php';

		if ( file_exists( $template_path ) ) {
			include $template_path;
		}
	}
}
