<?php

declare(strict_types=1);

namespace Catalogist;

/**
 * Sort Engine for Catalogist.
 *
 * Sorts a list of product IDs deterministically by a supported sort key
 * and direction. It reads product properties as needed to evaluate each
 * sort criterion and falls back to product ID as a final deterministic
 * tie-breaker so that identical input + configuration always produces
 * identical output regardless of database incidental ordering.
 *
 * The Sort Engine does NOT query WooCommerce independently. It operates
 * on the IDs already returned by the preceding Filter Engine and loads
 * product data only as needed.
 *
 * Invalid or unsupported sort configuration is handled safely:
 *   - Unknown sort keys cause the sort to be skipped (IDs returned as-is).
 *   - Unknown directions cause a fallback to 'ASC'.
 *   - Missing or null values are treated as empty strings (sort to the end).
 *
 * @phpstan-type SortConfig array{
 *     key: string,
 *     direction: string
 * }
 */
final class SortEngine {

	/**
	 * Allowed sort keys.
	 *
	 * @var list<string>
	 */
	private const ALLOWED_KEYS = array(
		'title',
		'price',
		'sku',
		'menu_order',
		'id',
	);

	/**
	 * Allowed sort directions.
	 *
	 * @var list<string>
	 */
	private const ALLOWED_DIRECTIONS = array(
		'asc',
		'desc',
	);

	/**
	 * Default sort direction when an invalid direction is provided.
	 */
	private const DEFAULT_DIRECTION = 'asc';

	/**
	 * Apply deterministic sorting to a list of product IDs.
	 *
	 * The sort is stable in the sense that an explicit final tie-breaker
	 * (product ID ascending) guarantees deterministic output for any input.
	 *
	 * @param list<int>        $product_ids The input product IDs to sort.
	 * @param SortConfig|array $sort_config The sort configuration.
	 * @return list<int> Sorted product IDs.
	 */
	public static function sort( array $product_ids, $sort_config ): array {
		// Normalize IDs upfront (like FilterEngine): intval, deduplicate, remove <= 0.
		$product_ids = array_values(
			array_unique(
				array_filter(
					array_map( 'intval', $product_ids ),
					function ( $id ) {
						return $id > 0;
					}
				)
			)
		);

		if ( empty( $product_ids ) ) {
			return array();
		}

		$sort_config = self::parse_sort_config( $sort_config );

		if ( null === $sort_config ) {
			// Invalid or unsupported config: return normalized IDs.
			return $product_ids;
		}

		$sort_key  = $sort_config['key'];
		$direction = $sort_config['direction'];
		$is_desc   = ( 'desc' === $direction );

		// Pre-load all product data once to avoid N+1 loading.
		$product_data = self::load_product_data( $product_ids );

		// Build sortable tuples: [sort_value, product_id, original_index].
		$tuples = array();
		foreach ( $product_ids as $index => $id ) {
			$id       = max( 0, (int) $id );
			$product  = $product_data[ $id ] ?? null;
			$value    = self::get_sort_value( $product, $sort_key );
			$tuples[] = array(
				'value' => $value,
				'id'    => $id,
				'index' => $index,
			);
		}

		// Custom uasort for deterministic ordering.
		uasort(
			$tuples,
			function ( array $a, array $b ) use ( $is_desc ) {
				// Compare sort values.
				$cmp = self::compare_values( $a['value'], $b['value'], $is_desc );

				// Tie-breaker: ascending ID (then ascending index for stability).
				if ( 0 === $cmp ) {
					$cmp = ( $a['id'] <=> $b['id'] );
				}
				if ( 0 === $cmp ) {
					$cmp = ( $a['index'] <=> $b['index'] );
				}

				return $cmp;
			}
		);

		// Extract sorted IDs preserving the deterministic order.
		$result = array();
		foreach ( $tuples as $tuple ) {
			$result[] = $tuple['id'];
		}

		return $result;
	}

	/**
	 * Get the sort value for a product by the given key.
	 *
	 * @param \WC_Product|null $product The WooCommerce product.
	 * @param string           $key     The sort key.
	 * @return mixed The sortable value (string, float, or int).
	 */
	private static function get_sort_value( ?\WC_Product $product, string $key ) {
		if ( null === $product ) {
			return '';
		}

		switch ( $key ) {
			case 'title':
				return (string) $product->get_name();
			case 'price':
				$price = $product->get_price();
				return '' === $price ? '' : (float) $price;
			case 'sku':
				return (string) $product->get_sku();
			case 'menu_order':
				return (int) $product->get_menu_order();
			case 'id':
				return (int) $product->get_id();
			default:
				return '';
		}
	}

	/**
	 * Compare two sort values for ascending or descending order.
	 *
	 * @param mixed  $a       First value.
	 * @param mixed  $b       Second value.
	 * @param bool   $is_desc Whether descending.
	 * @return int -1, 0, or 1.
	 */
	private static function compare_values( $a, $b, bool $is_desc ): int {
		// Both empty/missing: equal.
		if ( '' === $a && '' === $b ) {
			return 0;
		}

		// Missing values sort to the end.
		if ( '' === $a ) {
			return $is_desc ? -1 : 1;
		}
		if ( '' === $b ) {
			return $is_desc ? 1 : -1;
		}

		// Numeric comparison for price.
		if ( is_float( $a ) && is_float( $b ) ) {
			$cmp = ( $a < $b ) ? -1 : ( ( $a > $b ) ? 1 : 0 );
			return $is_desc ? -$cmp : $cmp;
		}

		// Integer comparison for menu_order and id.
		if ( is_int( $a ) && is_int( $b ) ) {
			$cmp = ( $a < $b ) ? -1 : ( ( $a > $b ) ? 1 : 0 );
			return $is_desc ? -$cmp : $cmp;
		}

		// String comparison for title and sku.
		$cmp = strcmp( (string) $a, (string) $b );
		return $is_desc ? -$cmp : $cmp;
	}

	/**
	 * Parse and validate sort configuration.
	 *
	 * Supports both the structured array form and a legacy string shorthand.
	 *
	 * @param SortConfig|array $config Raw sort configuration.
	 * @return array{key: string, direction: string}|null Sanitized config or null if invalid.
	 */
	private static function parse_sort_config( $config ): ?array {
		if ( is_string( $config ) ) {
			// Legacy shorthand: "title:asc" or "price:desc".
			$parts = explode( ':', $config );
			if ( 2 !== count( $parts ) ) {
				return null;
			}
			$key = self::sanitize_key( $parts[0] );
			$dir = self::sanitize_direction( $parts[1] );
			if ( null === $key ) {
				return null;
			}
			return array(
				'key'       => $key,
				'direction' => $dir,
			);
		}

		if ( ! is_array( $config ) ) {
			return null;
		}

		if ( ! isset( $config['key'] ) || ! is_string( $config['key'] ) ) {
			return null;
		}

		if ( ! isset( $config['direction'] ) || ! is_string( $config['direction'] ) ) {
			return null;
		}

		$key = self::sanitize_key( $config['key'] );
		$dir = self::sanitize_direction( $config['direction'] );

		if ( null === $key ) {
			return null;
		}

		if ( null === $dir ) {
			return null;
		}

		return array(
			'key'       => $key,
			'direction' => $dir,
		);
	}

	/**
	 * Sanitize sort key against allow-list.
	 *
	 * @param string $key Raw sort key.
	 * @return string|null Sanitized key or null if invalid.
	 */
	private static function sanitize_key( string $key ): ?string {
		$key = strtolower( trim( $key ) );
		if ( in_array( $key, self::ALLOWED_KEYS, true ) ) {
			return $key;
		}
		return null;
	}

	/**
	 * Sanitize sort direction against allow-list.
	 *
	 * @param string $direction Raw direction.
	 * @return string|null Sanitized direction or null if invalid.
	 */
	private static function sanitize_direction( string $direction ): ?string {
		$direction = strtolower( trim( $direction ) );
		if ( in_array( $direction, self::ALLOWED_DIRECTIONS, true ) ) {
			return $direction;
		}
		return null;
	}

	/**
	 * Pre-load WooCommerce product objects for a list of IDs.
	 *
	 * Called once per sort run to avoid repeated loading.
	 *
	 * @param list<int> $product_ids Product IDs.
	 * @return array<int, \WC_Product|null> Map of ID to WC_Product or null.
	 */
	private static function load_product_data( array $product_ids ): array {
		$data = array();
		foreach ( $product_ids as $id ) {
			$id          = max( 0, (int) $id );
			$product     = wc_get_product( $id );
			$data[ $id ] = ( $product instanceof \WC_Product ) ? $product : null;
		}
		return $data;
	}
}
