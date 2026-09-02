<?php
/**
 * Settings page handler.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Admin;

use Catalogist\Core\HookableInterface;
use Catalogist\Security\Capability;
use Catalogist\Security\Nonce;
use Catalogist\Security\Sanitizer;

/**
 * Renders and processes the settings page.
 */
final class SettingsPage implements HookableInterface {

	/**
	 * Option name for settings.
	 *
	 * @var string
	 */
	private const OPTION_NAME = 'catalogist_settings';

	/**
	 * Option group for settings.
	 *
	 * @var string
	 */
	private const OPTION_GROUP = Nonce::SETTINGS_ACTION;

	/**
	 * Register hooks with WordPress.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'admin_init', array( $this, 'verify_settings_nonce' ), 0 );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_menu', array( $this, 'register_options_page' ) );
	}

	/**
	 * Verify settings nonce before processing.
	 *
	 * @return void
	 */
	public function verify_settings_nonce(): void {
		if ( ! isset( $_REQUEST['option_page'] ) || self::OPTION_GROUP !== $_REQUEST['option_page'] ) {
			return;
		}

		if ( ! isset( $_REQUEST['_wpnonce'] ) ) {
			return;
		}

		check_admin_referer( self::OPTION_GROUP );
	}

	/**
	 * Register settings with WordPress Settings API.
	 *
	 * @return void
	 */
	public function register_settings(): void {
		register_setting(
			self::OPTION_GROUP,
			self::OPTION_NAME,
			array(
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
			)
		);

		add_settings_section(
			'catalogist_general',
			__( 'General Settings', 'catalogist' ),
			'__return_empty_string',
			'catalogist'
		);

		add_settings_field(
			'post_type_slug',
			__( 'Catalog Slug', 'catalogist' ),
			array( $this, 'render_slug_field' ),
			'catalogist',
			'catalogist_general'
		);

		add_settings_field(
			'per_page',
			__( 'Items Per Page', 'catalogist' ),
			array( $this, 'render_per_page_field' ),
			'catalogist',
			'catalogist_general'
		);

		add_settings_field(
			'enable_print',
			__( 'Enable Print', 'catalogist' ),
			array( $this, 'render_enable_print_field' ),
			'catalogist',
			'catalogist_general'
		);
	}

	/**
	 * Sanitize settings input.
	 *
	 * @param array<string, mixed> $input Raw settings input.
	 *
	 * @return array<string, mixed>
	 */
	public function sanitize_settings( array $input ): array {
		$sanitizer = new Sanitizer();

		return $sanitizer->settings( $input );
	}

	/**
	 * Register the options page.
	 *
	 * @return void
	 */
	public function register_options_page(): void {
		// Options page is registered via Menu class.
	}

	/**
	 * Render the settings page.
	 *
	 * @return void
	 */
	public function render(): void {
		if ( ! Capability::can_manage_settings() ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'catalogist' ) );
		}

		include CATALOGIST_PLUGIN_DIR . 'templates/admin/settings-page.php';
	}

	/**
	 * Render the post type slug field.
	 *
	 * @return void
	 */
	public function render_slug_field(): void {
		$value = $this->get_setting( 'post_type_slug', 'catalogs' );

		printf(
			'<input type="text" name="%s[post_type_slug]" value="%s" class="regular-text">',
			esc_attr( self::OPTION_NAME ),
			esc_attr( $value )
		);

		echo '<p class="description">' . esc_html__( 'The URL slug for catalog archives.', 'catalogist' ) . '</p>';
	}

	/**
	 * Render the per page field.
	 *
	 * @return void
	 */
	public function render_per_page_field(): void {
		$value = $this->get_setting( 'per_page', 20 );

		printf(
			'<input type="number" name="%s[per_page]" value="%d" min="1" max="100" class="small-text">',
			esc_attr( self::OPTION_NAME ),
			(int) $value
		);

		echo '<p class="description">' . esc_html__( 'Number of items to show per page.', 'catalogist' ) . '</p>';
	}

	/**
	 * Render the enable print field.
	 *
	 * @return void
	 */
	public function render_enable_print_field(): void {
		$value = $this->get_setting( 'enable_print', true );

		printf(
			'<label><input type="checkbox" name="%s[enable_print]" value="1" %s> %s</label>',
			esc_attr( self::OPTION_NAME ),
			checked( $value, true, false ),
			esc_html__( 'Enable print functionality', 'catalogist' )
		);
	}

	/**
	 * Get a setting value.
	 *
	 * @param string $key     Setting key.
	 * @param mixed  $default Default value.
	 *
	 * @return mixed
	 */
	private function get_setting( string $key, $default = null ) {
		$settings = get_option( self::OPTION_NAME, array() );

		return $settings[ $key ] ?? $default;
	}
}
