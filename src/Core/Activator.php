<?php
/**
 * Plugin activation handler.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Core;

/**
 * Handles plugin activation tasks.
 */
final class Activator {

	/**
	 * Run activation tasks.
	 *
	 * @param Container $container Service container.
	 *
	 * @return void
	 */
	public static function activate( Container $container ): void {
		self::create_capabilities();
		self::create_default_options();
		self::flush_rewrite_rules();
	}

	/**
	 * Create custom capabilities for roles.
	 *
	 * @return void
	 */
	private static function create_capabilities(): void {
		$admin = get_role( 'administrator' );

		if ( ! $admin ) {
			return;
		}

		$capabilities = array(
			'catalogist_manage_catalogs',
			'catalogist_edit_catalogs',
			'catalogist_delete_catalogs',
			'catalogist_manage_templates',
			'catalogist_manage_settings',
		);

		foreach ( $capabilities as $cap ) {
			if ( ! $admin->has_cap( $cap ) ) {
				$admin->add_cap( $cap );
			}
		}
	}

	/**
	 * Create default plugin options.
	 *
	 * @return void
	 */
	private static function create_default_options(): void {
		add_option(
			'catalogist_settings',
			array(
				'post_type_slug' => 'catalogs',
				'per_page'       => 20,
				'enable_print'   => true,
			)
		);

		add_option( 'catalogist_version', Plugin::VERSION );
	}

	/**
	 * Flush rewrite rules to register custom post types.
	 *
	 * @return void
	 */
	private static function flush_rewrite_rules(): void {
		// Post types will be registered on 'init' by the CatalogServiceProvider.
		// We need to manually register them here for flush to work.
		if ( class_exists( '\Catalogist\Catalog\CatalogPostType' ) ) {
			$post_type = new \Catalogist\Catalog\CatalogPostType();
			$post_type->register_post_type();
		}

		flush_rewrite_rules();
	}
}
