<?php

declare(strict_types=1);

namespace Catalogist;

/**
 * Catalog Item Mapper — maps WooCommerce products to CatalogItem.
 *
 * All WooCommerce-specific logic lives here; Template/Renderer layers
 * should only work with CatalogItem objects.
 */
final class CatalogItemMapper {

	/**
	 * Map a WooCommerce product to a CatalogItem.
	 *
	 * @param \WC_Product $product  WooCommerce product object.
	 * @param CatalogContext $context Catalog context.
	 * @return CatalogItem
	 */
	public static function map_product( \WC_Product $product, CatalogContext $context ): CatalogItem {
		$id   = $product->get_id();
		$type = $product->get_type();

		// Normalize type.
		if ( 'variable' === $type ) {
			$type = 'variable';
		} elseif ( 'variation' === $type ) {
			$type = 'variation';
		} else {
			$type = 'simple';
		}

		// Get variation-specific data if applicable.
		$parent_id         = null;
		$title             = $product->get_name();
		$sku               = $product->get_sku();
		$regular_price_raw = $product->get_regular_price();
		$sale_price_raw    = $product->get_sale_price();
		$price_raw         = $product->get_price();
		$regular_price     = '' !== $regular_price_raw ? (float) $regular_price_raw : null;
		$sale_price        = '' !== $sale_price_raw ? (float) $sale_price_raw : null;
		$price             = '' !== $price_raw ? (float) $price_raw : null;
		$stock_status      = $product->get_stock_status();
		$image_url         = self::get_main_image_url( $product );
		$permalink         = $product->get_permalink();
		$slug              = $product->get_slug();

		// For variations, use parent product for most fields.
		if ( 'variation' === $type ) {
			$parent_id = $product->get_parent_id();
			$parent    = wc_get_product( $parent_id );
			if ( $parent ) {
				$title     = $product->get_name(); // variation name
				$slug      = $parent->get_slug();
				$permalink = $parent->get_permalink();
				$image_url = self::get_main_image_url( $parent );
			}
		}

		// Get categories and tags.
		$categories = self::get_term_slugs( $product, 'product_cat' );
		$tags       = self::get_term_slugs( $product, 'product_tag' );

		return new CatalogItem(
			$id,
			$type,
			$parent_id,
			$title,
			$slug,
			$sku,
			$regular_price,
			$sale_price,
			$price,
			$stock_status,
			$image_url,
			$permalink,
			$categories,
			$tags,
			$context
		);
	}

	/**
	 * Get main image URL from product.
	 *
	 * @param \WC_Product $product WooCommerce product.
	 * @return string|null Image URL or null.
	 */
	private static function get_main_image_url( \WC_Product $product ): ?string {
		$image_id = $product->get_image_id();
		if ( ! $image_id ) {
			return null;
		}

		$image_size = apply_filters( 'catalogist_product_image_size', 'thumbnail' );
		$image_src  = wp_get_attachment_image_src( $image_id, $image_size );

		if ( ! $image_src || ! isset( $image_src[0] ) ) {
			return null;
		}

		return $image_src[0];
	}

	/**
	 * Get term slugs for a product taxonomy.
	 *
	 * @param \WC_Product $product WooCommerce product.
	 * @param string      $taxonomy Taxonomy name.
	 * @return list<string> Term slugs.
	 */
	private static function get_term_slugs( \WC_Product $product, string $taxonomy ): array {
		$terms = wp_get_object_terms( $product->get_id(), $taxonomy );

		if ( is_wp_error( $terms ) ) {
			return array();
		}

		$slugs = array();
		foreach ( $terms as $term ) {
			$slugs[] = $term->slug;
		}

		return $slugs;
	}
}
