<?php
/**
 * Admin menu registration.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Admin;

use Catalogist\Core\HookableInterface;
use Catalogist\Security\Capability;
use Catalogist\Catalog\CatalogRepositoryInterface;
use Catalogist\Preview\PreviewEngineInterface;

/**
 * Registers the admin menu.
 */
final class Menu implements HookableInterface {

	/**
	 * Register hooks with WordPress.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
	}

	/**
	 * Add the plugin menu.
	 *
	 * @return void
	 */
	public function add_menu(): void {
		$capability = Capability::MANAGE_CATALOGS;

		add_menu_page(
			__( 'Catalogs', 'catalogist' ),
			__( 'Catalogs', 'catalogist' ),
			$capability,
			'catalogist',
			null,
			'dashicons-media-spreadsheet',
			30
		);

		add_submenu_page(
			'catalogist',
			__( 'All Catalogs', 'catalogist' ),
			__( 'All Catalogs', 'catalogist' ),
			$capability,
			'catalogist',
			array( $this, 'render_catalogs_page' )
		);

		add_submenu_page(
			'catalogist',
			__( 'Settings', 'catalogist' ),
			__( 'Settings', 'catalogist' ),
			Capability::MANAGE_SETTINGS,
			'catalogist-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Render the catalogs admin page.
	 *
	 * @return void
	 */
	public function render_catalogs_page(): void {
		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'Catalogs', 'catalogist' ) . '</h1>';
		echo '<p>' . esc_html__( 'Manage your product catalogs.', 'catalogist' ) . '</p>';
		echo '</div>';
	}

	/**
	 * Render the settings page.
	 *
	 * @return void
	 */
	public function render_settings_page(): void {
		$page = new SettingsPage();
		$page->render();
	}
}
