<?php
/**
 * Admin assets handler.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Admin\Assets;

use Catalogist\Core\HookableInterface;

/**
 * Enqueues admin CSS and JavaScript.
 */
final class AdminAssets implements HookableInterface {

	/**
	 * Register hooks with WordPress.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook Current admin page hook.
	 *
	 * @return void
	 */
	public function enqueue( string $hook ): void {
		if ( false === strpos( $hook, 'catalogist' ) ) {
			return;
		}

		$this->enqueue_styles();
		$this->enqueue_scripts();
	}

	/**
	 * Enqueue admin styles.
	 *
	 * @return void
	 */
	private function enqueue_styles(): void {
		wp_enqueue_style(
			'catalogist-admin',
			CATALOGIST_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			CATALOGIST_VERSION
		);

		// Preview CSS for preview pages.
		$hook = get_plugin_page_hookname( 'catalogist-preview', 'admin.php' );
		if ( isset( $_GET['page'] ) && 'catalogist-preview' === sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			wp_enqueue_style(
				'catalogist-preview',
				CATALOGIST_PLUGIN_URL . 'assets/css/preview.css',
				array(),
				CATALOGIST_VERSION
			);
		}
	}

	/**
	 * Enqueue admin scripts.
	 *
	 * @return void
	 */
	private function enqueue_scripts(): void {
		wp_enqueue_script(
			'catalogist-admin',
			CATALOGIST_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			CATALOGIST_VERSION,
			true
		);

		wp_localize_script(
			'catalogist-admin',
			'catalogistAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'catalogist_admin' ),
			)
		);

		// Preview script for preview pages.
		$is_preview = isset( $_GET['page'] ) && 'catalogist-preview' === sanitize_text_field( wp_unslash( $_GET['page'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $is_preview ) {
			wp_enqueue_script(
				'catalogist-preview',
				CATALOGIST_PLUGIN_URL . 'assets/js/preview.js',
				array( 'jquery' ),
				CATALOGIST_VERSION,
				true
			);
		}
	}
}
