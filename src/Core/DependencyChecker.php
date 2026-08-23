<?php
/**
 * Dependency checker for required plugins.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Core;

/**
 * Checks for required plugin dependencies.
 */
final class DependencyChecker {

	/**
	 * Minimum PHP version required.
	 *
	 * @var string
	 */
	private const MIN_PHP_VERSION = '8.0';

	/**
	 * Minimum WooCommerce version.
	 *
	 * @var string
	 */
	private const MIN_WC_VERSION = '7.0';

	/**
	 * Check PHP version meets minimum requirement.
	 *
	 * @return bool
	 */
	public function check_php_version(): bool {
		return version_compare( PHP_VERSION, self::MIN_PHP_VERSION, '>=' );
	}

	/**
	 * Check if WooCommerce is active.
	 *
	 * @return bool
	 */
	public function check_woocommerce(): bool {
		return class_exists( 'WooCommerce' );
	}

	/**
	 * Check if Elementor is active.
	 *
	 * @return bool
	 */
	public function check_elementor(): bool {
		return class_exists( '\Elementor\Plugin' );
	}

	/**
	 * Get WooCommerce version if active.
	 *
	 * @return string|null
	 */
	public function get_woocommerce_version(): ?string {
		if ( ! $this->check_woocommerce() ) {
			return null;
		}

		return WC()->version ?? null;
	}

	/**
	 * Get Elementor version if active.
	 *
	 * @return string|null
	 */
	public function get_elementor_version(): ?string {
		if ( ! $this->check_elementor() ) {
			return null;
		}

		return \ELEMENTOR_VERSION ?? null;
	}

	/**
	 * Render PHP version admin notice.
	 *
	 * @return void
	 */
	public function render_php_version_notice(): void {
		$message = sprintf(
			/* translators: 1: Current PHP version, 2: Required PHP version */
			__( 'Catalogist requires PHP version %2$s or higher. You are running version %1$s. Please upgrade PHP to use this plugin.', 'catalogist' ),
			PHP_VERSION,
			self::MIN_PHP_VERSION
		);

		$this->render_notice( $message, 'error' );
	}

	/**
	 * Render WooCommerce missing admin notice.
	 *
	 * @return void
	 */
	public function render_woocommerce_notice(): void {
		$message = sprintf(
			/* translators: %s: WooCommerce plugin name */
			__( 'Catalogist requires %s to be installed and active.', 'catalogist' ),
			'<strong>WooCommerce</strong>'
		);

		$this->render_notice( $message, 'warning' );
	}

	/**
	 * Render an admin notice.
	 *
	 * @param string $message Notice message.
	 * @param string $type    Notice type: 'error', 'warning', 'success', 'info'.
	 *
	 * @return void
	 */
	private function render_notice( string $message, string $type = 'error' ): void {
		printf(
			'<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
			esc_attr( $type ),
			wp_kses( $message, array( 'strong' => array() ) )
		);
	}
}
