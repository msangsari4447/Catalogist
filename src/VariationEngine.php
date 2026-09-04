<?php
declare(strict_types=1);

namespace Catalogist;

/**
 * Variation Engine for Catalogist.
 *
 * Provides a clean boundary between Catalogist and WooCommerce variation queries.
 * Handles variation discovery, expansion, and metadata extraction.
 *
 * @phpstan-type VariationData array{
 *     id: int,
 *     parent_id: int,
 *     label: string
 * }
 */
final class VariationEngine {

	/**
	 * Allowed variation resolution modes.
	 *
	 * 'parent'     — Return only parent product IDs (no expansion).
	 * 'variations' — Expand variable products to include variation child IDs.
	 *
	 * @var list<string>
	 */
	private const ALLOWED_MODES = array(
		'parent',
		'variations',
	);

	/**
	 * Get variation child IDs for a variable product.
	 *
	 * Returns an empty array for simple products, non-existent products,
	 * or products with no valid variations.
	 *
	 * @param int $product_id Product post ID.
	 * @return list<int> Array of variation child IDs.
	 */
	public static function get_variation_ids( int $product_id ): array {
		$product_id = max( 0, (int) $product_id );
		if ( $product_id <= 0 ) {
			return array();
		}

		$product = wc_get_product( $product_id );
		if ( ! $product || is_wp_error( $product ) ) {
			return array();
		}

		if ( 'variable' !== $product->get_type() ) {
			return array();
		}

		$children = $product->get_children();
		if ( ! is_array( $children ) ) {
			return array();
		}

		$variation_ids = array();
		foreach ( $children as $child_id ) {
			$child_id = max( 0, (int) $child_id );
			if ( $child_id <= 0 ) {
				continue;
			}

			$variation = wc_get_product( $child_id );
			if ( $variation && ! is_wp_error( $variation ) ) {
				$variation_ids[] = $child_id;
			}
		}

		return array_values( array_unique( $variation_ids ) );
	}

	/**
	 * Check if a product is a WooCommerce variable product.
	 *
	 * @param int $product_id Product post ID.
	 * @return bool True if the product is variable.
	 */
	public static function is_variable_product( int $product_id ): bool {
		$product_id = max( 0, (int) $product_id );
		if ( $product_id <= 0 ) {
			return false;
		}

		$product = wc_get_product( $product_id );
		if ( ! $product || is_wp_error( $product ) ) {
			return false;
		}

		return 'variable' === $product->get_type();
	}

	/**
	 * Get structured variation data for a single variation.
	 *
	 * @param int $product_id Variation child post ID.
	 * @return VariationData|null Structured data or null if invalid.
	 */
	public static function get_variation_data( int $product_id ): ?array {
		$product_id = max( 0, (int) $product_id );
		if ( $product_id <= 0 ) {
			return null;
		}

		$product = wc_get_product( $product_id );
		if ( ! $product || is_wp_error( $product ) ) {
			return null;
		}

		// Only return data for actual variation children.
		if ( 'variation' !== $product->get_type() ) {
			return null;
		}

		$parent_id = (int) $product->get_parent_id();
		$label     = $product->get_name();

		return array(
			'id'        => $product_id,
			'parent_id' => $parent_id,
			'label'     => is_string( $label ) && '' !== $label ? $label : __( 'Variation', 'catalogist' ),
		);
	}

	/**
	 * Expand product IDs to include variation children.
	 *
	 * Given a list of product IDs (typically from ProductQueryEngine),
	 * returns an expanded list that includes variation IDs for any
	 * variable products when $include_variations is true.
	 *
	 * @param list<int> $product_ids    Product IDs to expand.
	 * @param bool      $include_variations Whether to include variation IDs.
	 * @return list<int> Expanded product IDs.
	 */
	public static function expand_product_ids( array $product_ids, bool $include_variations = false ): array {
		$sanitized_ids = array_filter(
			array_map(
				static function ( $id ): int {
					return max( 0, (int) $id );
				},
				array_filter( $product_ids, 'is_int' )
			),
			static function ( $id ): bool {
				return $id > 0;
			}
		);
		$sanitized_ids = array_values( array_unique( $sanitized_ids ) );

		if ( empty( $sanitized_ids ) ) {
			return array();
		}

		if ( ! $include_variations ) {
			return $sanitized_ids;
		}

		$expanded = array();
		foreach ( $sanitized_ids as $product_id ) {
			$expanded[] = $product_id;

			$variation_ids = self::get_variation_ids( $product_id );
			if ( ! empty( $variation_ids ) ) {
				$expanded = array_merge( $expanded, $variation_ids );
			}
		}

		return array_values( array_unique( $expanded ) );
	}

	/**
	 * Resolve product IDs based on variation mode.
	 *
	 * @param list<int> $product_ids Product IDs to resolve.
	 * @param string    $mode        Variation resolution mode ('parent' or 'variations').
	 * @return list<int> Resolved product IDs.
	 */
	public static function resolve_product_ids( array $product_ids, string $mode = 'parent' ): array {
		$mode = self::sanitize_mode( $mode );
		if ( null === $mode ) {
			$mode = 'parent';
		}

		$include_variations = ( 'variations' === $mode );
		return self::expand_product_ids( $product_ids, $include_variations );
	}

	/**
	 * Sanitize variation mode against allow-list.
	 *
	 * @param string $mode Raw mode string.
	 * @return string|null Sanitized mode or null if invalid.
	 */
	private static function sanitize_mode( string $mode ): ?string {
		$mode = strtolower( trim( $mode ) );
		if ( in_array( $mode, self::ALLOWED_MODES, true ) ) {
			return $mode;
		}
		return null;
	}
}
