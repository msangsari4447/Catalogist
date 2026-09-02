<?php
/**
 * Template engine helper functions.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Template;

use Catalogist\Core\Plugin;
use Catalogist\Output\OutputEngineInterface;
use Catalogist\Output\OutputFormat;
use Catalogist\Product\ProductQueryArgs;
use Catalogist\Product\ProductRepositoryInterface;
use Catalogist\Catalog\CatalogRepositoryInterface;
use Catalogist\CatalogItem\CatalogProcessor;
use Catalogist\Variation\VariationQueryArgs;

defined( 'ABSPATH' ) || exit;

/**
 * Get the plugin service container.
 *
 * @return \Catalogist\Core\Container|null Container or null if plugin not booted.
 */
function catalogist_get_container(): ?\Catalogist\Core\Container {
	$plugin = Plugin::instance();

	if ( ! method_exists( $plugin, 'get_container' ) ) {
		return null;
	}

	return $plugin->get_container();
}

/**
 * Render a catalog using the template engine.
 *
 * Wrapper around TemplateEngineInterface for convenience.
 *
 * @param int                          $catalogId      Catalog post ID.
 * @param array<string, mixed>|null     $settings       Override settings.
 *
 * @return string Rendered HTML output.
 */
function render_catalog( int $catalogId, ?array $settings = null ): string {
	$container = catalogist_get_container();

	if ( ! $container ) {
		return '<p class="catalogist-error">' .
		       esc_html__( 'Catalogist is not properly initialized.', 'catalogist' ) .
		       '</p>';
	}

	$catalogRepo     = $container->get( \Catalogist\Catalog\CatalogRepositoryInterface::class );
	$catalog         = $catalogRepo->find( $catalogId );

	if ( ! $catalog ) {
		return '<p class="catalogist-error">' .
		       esc_html__( 'Catalog not found.', 'catalogist' ) .
		       '</p>';
	}

	$productRepo      = $container->get( \Catalogist\Product\ProductRepositoryInterface::class );
	$catalogProcessor = $container->get( \Catalogist\CatalogItem\CatalogProcessor::class );

	// Build default product query args.
	$queryArgs = \Catalogist\Product\ProductQueryArgs::from_array(
		array_merge(
			array(
				'order'     => 'ASC',
				'orderby'   => 'menu_order title',
			),
			$catalog->get_product_query()
		)
	);

	$productResult  = $productRepo->query( $queryArgs );

	$variationArgs = \Catalogist\Variation\VariationQueryArgs::from_array(
		array_merge(
			array( 'variation_mode' => 'parent' ),
			isset( $catalog->get_product_query()['variation_mode'] )
				? array( 'variation_mode' => $catalog->get_product_query()['variation_mode'] )
				: array()
		)
	);

	$catalogItems = $catalogProcessor->process( $productResult, $variationArgs );

	$outputEngine = $container->get( OutputEngineInterface::class );

	return $outputEngine->generate(
		$catalog,
		$catalogItems,
		OutputFormat::HTML,
		$settings ?? array()
	);
}

/**
 * Render a catalog for print.
 *
 * Wrapper around PrintEngineInterface for convenience.
 *
 * @param int                          $catalogId      Catalog post ID.
 * @param array<string, mixed>|null     $settings       Print override settings.
 *
 * @return string Rendered print HTML output.
 */
function render_catalog_print( int $catalogId, ?array $settings = null ): string {
	$container = catalogist_get_container();

	if ( ! $container ) {
		return '<p class="catalogist-error">' .
		       esc_html__( 'Catalogist is not properly initialized.', 'catalogist' ) .
		       '</p>';
	}

	$catalogRepo     = $container->get( CatalogRepositoryInterface::class );
	$catalog         = $catalogRepo->find( $catalogId );

	if ( ! $catalog ) {
		return '<p class="catalogist-error">' .
		       esc_html__( 'Catalog not found.', 'catalogist' ) .
		       '</p>';
	}

	$productRepo      = $container->get( ProductRepositoryInterface::class );
	$catalogProcessor = $container->get( CatalogProcessor::class );

	// Build default product query args.
	$queryArgs = ProductQueryArgs::from_array(
		array_merge(
			array(
				'order'     => 'ASC',
				'orderby'   => 'menu_order title',
			),
			$catalog->get_product_query()
		)
	);

	$productResult  = $productRepo->query( $queryArgs );

	$variationArgs = VariationQueryArgs::from_array(
		array_merge(
			array( 'variation_mode' => 'parent' ),
			isset( $catalog->get_product_query()['variation_mode'] )
				? array( 'variation_mode' => $catalog->get_product_query()['variation_mode'] )
				: array()
		)
	);

	$catalogItems = $catalogProcessor->process( $productResult, $variationArgs );

	$outputEngine = $container->get( OutputEngineInterface::class );

	return $outputEngine->generate(
		$catalog,
		$catalogItems,
		OutputFormat::PRINT,
		$settings ?? array()
	);
}
