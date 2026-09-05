<?php

declare(strict_types=1);

namespace Catalogist;

/**
 * Selection Engine for Catalogist.
 *
 * Applies offset and limit selection to a sorted list of product IDs,
 * producing the final deterministic subset that enters the Catalog pipeline.
 *
 * Selection occurs after sorting, so pagination and truncation are applied
 * to the already-ordered result set.
 *
 * If no selection configuration is provided, the complete input is preserved.
 * If the input is empty, an empty array is returned safely.
 *
 * @phpstan-type SelectionConfig array{
 *     offset?: int,
 *     limit?: int
 * }
 */
final class SelectionEngine {

	/**
	 * Apply offset and limit selection to a sorted list of product IDs.
	 *
	 * @param list<int>          $product_ids The sorted input product IDs.
	 * @param SelectionConfig|array|null $selection_config The selection configuration.
	 * @return list<int> Selected product IDs.
	 */
	public static function select( array $product_ids, $selection_config = null ): array {
		if ( empty( $product_ids ) ) {
			return array();
		}

		// Normalize IDs upfront: deduplicate and convert to integers.
		$product_ids = array_values(
			array_unique(
				array_filter(
					array_map( 'intval', $product_ids ),
					'intval'
				)
			)
		);

		$config = self::parse_selection_config( $selection_config );

		if ( null === $config ) {
			// No valid selection config: return all IDs as-is.
			return $product_ids;
		}

		$offset = $config['offset'];
		$limit  = $config['limit'];

		// Apply offset.
		$result = array_slice( $product_ids, $offset );

		// Apply limit (even if 0, which returns empty array).
		// null limit means "not specified" — do not apply any limit.
		if ( null !== $limit ) {
			$result = array_slice( $result, 0, $limit );
		}

		return $result;
	}

	/**
	 * Parse and validate selection configuration.
	 *
	 * Supports both structured array form and legacy shorthand.
	 *
	 * @param SelectionConfig|array|null $config Raw selection configuration.
	 * @return array{offset: int, limit: int}|null Sanitized config or null if invalid.
	 */
	private static function parse_selection_config( $config ): ?array {
		if ( null === $config ) {
			return null;
		}

		if ( is_string( $config ) ) {
			// Legacy shorthand not applicable for selection; skip.
			return null;
		}

		if ( ! is_array( $config ) ) {
			return null;
		}

		$offset = isset( $config['offset'] ) ? self::sanitize_offset( $config['offset'] ) : 0;
		$limit  = isset( $config['limit'] ) ? self::sanitize_limit( $config['limit'] ) : null;

		// If neither offset nor limit is meaningfully set, no selection is needed.
		if ( 0 === $offset && null === $limit ) {
			return null;
		}

		return array(
			'offset' => $offset,
			'limit'  => $limit,
		);
	}

	/**
	 * Sanitize offset to a non-negative integer.
	 *
	 * @param mixed $offset Raw offset value.
	 * @return int Sanitized non-negative offset.
	 */
	private static function sanitize_offset( $offset ): int {
		$offset = (int) $offset;
		return max( 0, $offset );
	}

	/**
	 * Sanitize limit to a non-negative integer, or null if not set.
	 *
	 * @param mixed $limit Raw limit value.
	 * @return int|null Sanitized limit or null if not meaningful.
	 */
	private static function sanitize_limit( $limit ): ?int {
		$limit = (int) $limit;
		if ( $limit < 0 ) {
			// Negative limit means no limit — return null.
			return null;
		}
		// Zero or positive limit is valid.
		return $limit;
	}
}
