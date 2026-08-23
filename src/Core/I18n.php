<?php
/**
 * Internationalization handler.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Core;

/**
 * Handles plugin internationalization.
 */
final class I18n implements HookableInterface {

	/**
	 * Plugin text domain.
	 *
	 * @var string
	 */
	private string $text_domain;

	/**
	 * Languages directory path (relative to plugin root).
	 *
	 * @var string
	 */
	private string $languages_dir;

	/**
	 * Constructor.
	 *
	 * @param string $text_domain    Text domain.
	 * @param string $languages_dir  Languages directory.
	 */
	public function __construct( string $text_domain, string $languages_dir ) {
		$this->text_domain   = $text_domain;
		$this->languages_dir = $languages_dir;
	}

	/**
	 * Register hooks with WordPress.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'init', array( $this, 'load_textdomain' ) );
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @return void
	 */
	public function load_textdomain(): void {
		// Load from the languages directory first (custom translations).
		$loaded = load_plugin_textdomain(
			$this->text_domain,
			false,
			dirname( plugin_basename( CATALOGIST_FILE ) ) . '/' . $this->languages_dir
		);

		// If not found, try the wp-content/languages/plugins directory.
		if ( ! $loaded ) {
			$locale = determine_locale();
			$path   = WP_LANG_DIR . '/plugins/' . $this->text_domain . '-' . $locale . '.mo';

			if ( file_exists( $path ) ) {
				load_textdomain( $this->text_domain, $path );
			}
		}
	}
}
