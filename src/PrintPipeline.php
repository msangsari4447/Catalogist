<?php

declare(strict_types=1);

namespace Catalogist;

/**
 * Print Pipeline — orchestrates Catalog → Renderer → PrintEngine.
 *
 * Composes the existing pipeline engines into a single printable HTML
 * fragment for a real catalog. This class is final and stateless: it
 * owns no queries, no persistence, and no print-CSS generation beyond
 * delegating to the Stage 9 Renderer and Stage 10 PrintEngine.
 *
 * It does not provide Preview, Output, Elementor, or PDF behavior.
 */
final class PrintPipeline {

	/**
	 * Render a catalog as a print-ready HTML fragment.
	 *
	 * @param int              $catalog_id  Catalog post ID.
	 * @param array<string, mixed> $print_config PrintEngine configuration.
	 * @return string Print-ready HTML (empty string on invalid catalog).
	 */
	public static function render( int $catalog_id, array $print_config = [] ): string {
		$catalog_id = absint( $catalog_id );

		if ( $catalog_id <= 0 ) {
			return '';
		}

		$catalog = Catalog::get_data( $catalog_id );

		if ( empty( $catalog ) || CatalogPostType::POST_TYPE !== get_post_type( $catalog_id ) ) {
			return '';
		}

		$configuration = $catalog['configuration'] ?? Catalog::default_configuration();
		$context       = new CatalogContext(
			$catalog_id,
			get_locale(),
			get_woocommerce_currency()
		);

		$product_ids = self::resolve_product_ids( $configuration, $catalog );
		$items       = self::map_items( $product_ids, $context );

		$template_id = $configuration['template']['id'] ?? 0;
		$template    = Template::get_data( (int) $template_id );
		$bound       = Template::bind( $template, $context );
		$template    = $bound['template'] ?? [];

		$html = ( new Renderer() )->render( $context, $template, $items );

		return ( new PrintEngine() )->render( $html, $print_config );
	}

	/**
	 * AJAX handler for print rendering.
	 *
	 * Expects `catalog_id` in $_GET. Validates capability and post type,
	 * then echoes the print-ready HTML and exits.
	 */
	public static function ajax_render(): void {
		if ( ! isset( $_GET['catalog_id'] ) ) {
			wp_die( '1', '', [ 'response' => 400 ] );
		}

		$catalog_id = absint( $_GET['catalog_id'] );

		if ( $catalog_id <= 0 || CatalogPostType::POST_TYPE !== get_post_type( $catalog_id ) ) {
			wp_die( '1', '', [ 'response' => 404 ] );
		}

		if ( ! current_user_can( 'edit_post', $catalog_id ) ) {
			wp_die( '1', '', [ 'response' => 403 ] );
		}

		echo self::render( $catalog_id );
		wp_die();
	}

	/**
	 * Resolve product IDs for a catalog configuration.
	 *
	 * Manual legacy product IDs take precedence when present so the
	 * ProductQueryEngine empty-ID behavior (which queries all products)
	 * is never triggered accidentally.
	 *
	 * @param array<string, mixed> $configuration Catalog configuration.
	 * @param array<string, mixed> $catalog       Catalog data from Catalog::get_data().
	 * @return list<int> Resolved product IDs.
	 */
	private static function resolve_product_ids( array $configuration, array $catalog ): array {
		$manual_ids = $catalog['products'] ?? [];

		if ( is_array( $manual_ids ) && count( $manual_ids ) > 0 ) {
			return array_values(
				array_unique(
					array_filter(
						array_map( 'intval', $manual_ids ),
						function ( $id ): bool {
							return $id > 0;
						}
					)
				)
			);
		}

		$filters = $configuration['filters'] ?? [];
		$sort    = $configuration['sort'] ?? [];
		$selection = $configuration['selection'] ?? [];

		$ids = ProductQueryEngine::query( [] );
		$ids = FilterEngine::filter( $ids, $filters );
		$ids = SortEngine::sort( $ids, $sort );
		$ids = SelectionEngine::select( $ids, $selection );

		return array_values( array_unique( array_filter( $ids, function ( $id ): bool {
			return $id > 0;
		} ) ) );
	}

	/**
	 * Map product IDs to CatalogItem objects.
	 *
	 * @param list<int>        $product_ids Product IDs.
	 * @param CatalogContext   $context     Rendering context.
	 * @return list<CatalogItem> Mapped items.
	 */
	private static function map_items( array $product_ids, CatalogContext $context ): array {
		$items = [];

		foreach ( $product_ids as $id ) {
			$product = wc_get_product( $id );

			if ( ! $product instanceof \WC_Product ) {
				continue;
			}

			$items[] = CatalogItemMapper::map_product( $product, $context );
		}

		return $items;
	}
}
