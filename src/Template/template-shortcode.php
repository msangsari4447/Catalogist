<?php
/**
 * Template engine shortcode handler.
 *
 * Provides the [catalogist] shortcode for rendering catalogs in post content.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Template;

use Catalogist\Catalog\CatalogRepositoryInterface;
use Catalogist\CatalogItem\CatalogProcessor;
use Catalogist\Product\ProductQueryArgs;
use Catalogist\Print\PrintEngineInterface;
use Catalogist\Product\ProductQueryResult;
use Catalogist\Product\ProductRepositoryInterface;
use Catalogist\Variation\VariationQueryArgs;
use Catalogist\Variation\VariationServiceInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Register the catalogist shortcode.
 *
 * Usage: [catalogist id="123" template="default" columns="2"]
 *
 * @return void
 */
function register_shortcode(): void {
	add_shortcode( 'catalogist', __NAMESPACE__ . '\\catalogist_shortcode' );
}

/**
 * Shortcode handler — renders a catalog by ID.
 *
 * @param array<string, string> $atts Shortcode attributes.
 *
 * @return string Rendered catalog HTML.
 */
function catalogist_shortcode( array $atts ): string {
	$atts = shortcode_atts(
		array(
			'id'          => '0',
			'template'    => 'default',
			'columns'     => '2',
			'order'       => 'ASC',
			'orderby'     => 'menu_order title',
			'print'       => '0',
			'orientation' => 'portrait',
			'page_size'   => 'a4',
		),
		$atts,
		'catalogist'
	);

	// Validate and sanitize inputs.
	$catalogId   = absint( $atts['id'] );
	$template    = sanitize_text_field( $atts['template'] );

	// Whitelist allowed template slugs to prevent template injection.
	$allowed_templates = array( 'default', 'fallback' );
	if ( ! in_array( $template, $allowed_templates, true ) ) {
		$template = 'default';
	}

	$columns     = max( 1, min( 4, absint( $atts['columns'] ) ) );
	$order       = in_array( strtoupper( $atts['order'] ), array( 'ASC', 'DESC' ), true ) ? strtoupper( $atts['order'] ) : 'ASC';
	$orderby     = sanitize_text_field( $atts['orderby'] );
	$is_print    = filter_var( $atts['print'], FILTER_VALIDATE_BOOLEAN );
	$orientation = sanitize_text_field( $atts['orientation'] );
	$page_size   = sanitize_text_field( $atts['page_size'] );

	if ( 0 === $catalogId ) {
		return '<p class="catalogist-error">' .
		       esc_html__( 'Please provide a valid catalog ID.', 'catalogist' ) .
		       '</p>';
	}

	// Load catalog from repository.
	$container = catalogist_get_container();

	if ( ! $container ) {
		return '<p class="catalogist-error">' .
		       esc_html__( 'Catalogist is not properly initialized.', 'catalogist' ) .
		       '</p>';
	}

	$catalogRepo      = $container->get( CatalogRepositoryInterface::class );
	$catalog          = $catalogRepo->find( $catalogId );

	if ( ! $catalog ) {
		return '<p class="catalogist-error">' .
		       esc_html__( 'Catalog not found.', 'catalogist' ) .
		       '</p>';
	}

	// Check capability for draft/private catalogs.
	if ( in_array( $catalog->get_status(), array( 'draft', 'private', 'pending' ), true ) ) {
		if ( ! current_user_can( 'edit_post', $catalog->get_id() ) ) {
			return '<p class="catalogist-error">' .
			       esc_html__( 'You do not have permission to view this catalog.', 'catalogist' ) .
			       '</p>';
		}
	}

	// Build product query args from catalog settings.
	$productQueryArgs = ProductQueryArgs::from_array(
		array_merge(
			array(
				'order'     => $order,
				'orderby'   => $orderby,
				'columns'   => $columns,
			),
			$catalog->get_product_query()
		)
	);

	// Query products.
	$productRepo     = $container->get( ProductRepositoryInterface::class );
	$productResult   = $productRepo->query( $productQueryArgs );

	// Build variation query args from catalog settings.
	$variationArgs = VariationQueryArgs::from_array(
		array_merge(
			array(
				'variation_mode' => 'parent',
			),
			isset( $catalog->get_product_query()['variation_mode'] )
				? array( 'variation_mode' => $catalog->get_product_query()['variation_mode'] )
				: array()
		)
	);

	// Process catalog items.
	$catalogProcessor = $container->get( CatalogProcessor::class );
	$catalogItems     = $catalogProcessor->process( $productResult, $variationArgs );

	// Print mode — delegate to PrintEngine.
	if ( $is_print ) {
		$printEngine = $container->get( PrintEngineInterface::class );

		$printOverrides = array(
			'orientation' => $orientation,
			'page_size'   => $page_size,
			'columns'     => $columns,
		);

		return $printEngine->generatePrintHTML( $catalog, $catalogItems, $printOverrides );
	}

	// Render via template engine (standard web mode).
	$templateEngine = $container->get( TemplateEngineInterface::class );

	$settings = array(
		'template' => $template,
		'layout'   => array(
			'columns' => $columns,
		),
	);

	return $templateEngine->renderCatalog( $catalog, $catalogItems, $settings );
}
