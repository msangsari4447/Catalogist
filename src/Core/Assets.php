<?php
/**
 * Asset management for the plugin.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Core;

/**
 * Handles enqueueing of CSS and JavaScript assets.
 */
class Assets implements HookableInterface {

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	private string $version;

	/**
	 * Plugin file path.
	 *
	 * @var string
	 */
	private string $file;

	/**
	 * Constructor.
	 *
	 * @param string $version Plugin version.
	 * @param string $file    Main plugin file path.
	 */
	public function __construct( string $version, string $file ) {
		$this->version = $version;
		$this->file    = $file;
	}

	/**
	 * Register hooks with WordPress.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_public_assets' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Enqueue public-facing assets.
	 *
	 * @return void
	 */
	public function enqueue_public_assets(): void {
		// Public assets will be added in later milestones.
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook Current admin page hook.
	 *
	 * @return void
	 */
	public function enqueue_admin_assets( string $hook ): void {
		// Admin assets will be conditionally loaded by AdminAssets class.
	}

	/**
	 * Get the URL to an asset file.
	 *
	 * @param string $path Relative path to the asset.
	 *
	 * @return string
	 */
	public function get_asset_url( string $path ): string {
		return plugin_dir_url( $this->file ) . $path;
	}

	/**
	 * Get the filesystem path to an asset file.
	 *
	 * @param string $path Relative path to the asset.
	 *
	 * @return string
	 */
	public function get_asset_path( string $path ): string {
		return plugin_dir_path( $this->file ) . $path;
	}

	/**
	 * Generate a version string for assets with file modification time.
	 *
	 * @param string $path Relative path to the asset.
	 *
	 * @return string|int|false
	 */
	public function get_asset_version( string $path ) {
		$full_path = $this->get_asset_path( $path );

		if ( file_exists( $full_path ) ) {
			return filemtime( $full_path );
		}

		return $this->version;
	}
}
