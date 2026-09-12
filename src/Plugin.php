<?php

declare(strict_types=1);

namespace Catalogist;

final class Plugin {

	public static function boot(): void {
		add_action( 'init', array( CatalogPostType::class, 'register' ) );
		add_action( 'init', array( TemplatePostType::class, 'register' ) );
		add_action( 'admin_init', array( Admin::class, 'boot' ) );
		add_action( 'wp_ajax_catalogist_search_products', array( Admin::class, 'ajax_search_products' ) );
		add_shortcode( 'catalogist_print', array( self::class, 'render_print_catalog' ) );
	}

	/**
	 * Render a catalog as a print-ready HTML fragment via shortcode.
	 *
	 * Usage: [catalogist_print id="123"]
	 *
	 * @param array<string, mixed> $atts Shortcode attributes.
	 * @return string Print-ready HTML or empty string on invalid catalog.
	 */
	public static function render_print_catalog( array $atts ): string {
		$catalog_id = isset( $atts['id'] ) ? absint( $atts['id'] ) : 0;

		if ( $catalog_id <= 0 ) {
			return '';
		}

		return PrintPipeline::render( $catalog_id, PrintEngine::default_configuration() );
	}
}
