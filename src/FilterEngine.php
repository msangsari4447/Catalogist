<?php

declare(strict_types=1);

namespace Catalogist;

/**
 * Filter Engine for Catalogist.
 *
 * Applies composable, deterministic filters to a list of product IDs
 * produced by the preceding Product Query Engine and Variation Engine.
 *
 * The Filter Engine does NOT query WooCommerce independently.
 * It operates on the IDs already returned by the pipeline and
 * reads product properties as needed to evaluate each filter.
 *
 * Filter composition is sequential and deterministic:
 *   Input IDs → Filter A → Filter B → Filter C → Output IDs
 *
 * Invalid or unsupported filters are safely skipped without affecting
 * the result set or triggering PHP warnings/notices.
 *
 * @phpstan-type FilterConfig array{
 *     type: string,
 *     value: mixed,
 *     operator?: string,
 * }
 */
final class FilterEngine {

	/**
	 * Allowed filter types.
	 *
	 * @var list<string>
	 */
	private const ALLOWED_FILTER_TYPES = array(
		'type',
		'category',
		'tag',
		'stock_status',
		'price',
		'sku',
	);

	/**
	 * Allowed operators for numeric filters.
	 *
	 * @var list<string>
	 */
	private const ALLOWED_OPERATORS = array(
		'gte',
		'lte',
		'eq',
		'neq',
	);

	/**
	 * Allowed stock statuses.
	 *
	 * @var list<string>
	 */
	private const ALLOWED_STOCK_STATUSES = array(
		'instock',
		'outofstock',
		'onbackorder',
	);

	/**
	 * Allowed product types.
	 *
	 * @var list<string>
	 */
	private const ALLOWED_PRODUCT_TYPES = array(
		'simple',
		'variable',
		'grouped',
		'external',
	);

	/**
	 * Apply one or more filters to a list of product IDs.
	 *
	 * Filters are applied sequentially in the order provided.
	 * Each filter narrows the result set; invalid filters are skipped.
	 *
	 * @param list<int>          $product_ids The input product IDs.
	 * @param list<FilterConfig> $filters     The list of filter configurations.
	 * @return list<int> The filtered product IDs.
	 */
	public static function filter( array $product_ids, array $filters ): array {
		// Normalize to integers, deduplicate, and remove invalid IDs (<= 0).
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

		if ( empty( $filters ) ) {
			return $product_ids;
		}

		// Validate filters first to avoid unnecessary WooCommerce calls.
		$valid_filters = array();
		foreach ( $filters as $filter ) {
			if ( self::validate_filter( $filter ) ) {
				$valid_filters[] = $filter;
			}
		}

		if ( empty( $valid_filters ) ) {
			return $product_ids;
		}

		// Pre-load all product data once to avoid N+1 loading across filters.
		$product_data = self::load_product_data( $product_ids );

		$result = $product_ids;

		foreach ( $valid_filters as $filter ) {
			$result = self::apply_single_filter( $result, $filter, $product_data );

			if ( empty( $result ) ) {
				return array();
			}
		}

		return array_values( array_unique( $result ) );
	}

	/**
	 * Apply a single validated filter to a list of product IDs.
	 *
	 * @param list<int>                                            $product_ids  Current candidate IDs.
	 * @param array{type: string, value: mixed, operator?: string} $filter Validated filter config.
	 * @param array<int, \WC_Product|null>                         $product_data Pre-loaded product data.
	 * @return list<int> Filtered product IDs.
	 */
	private static function apply_single_filter(
		array $product_ids,
		array $filter,
		array $product_data
	): array {
		$filter_type = $filter['type'];

		switch ( $filter_type ) {
			case 'type':
				return self::apply_type_filter( $product_ids, $filter, $product_data );
			case 'category':
				return self::apply_category_filter( $product_ids, $filter, $product_data );
			case 'tag':
				return self::apply_tag_filter( $product_ids, $filter, $product_data );
			case 'stock_status':
				return self::apply_stock_status_filter( $product_ids, $filter, $product_data );
			case 'price':
				return self::apply_price_filter( $product_ids, $filter, $product_data );
			case 'sku':
				return self::apply_sku_filter( $product_ids, $filter, $product_data );
			default:
				return $product_ids;
		}
	}

	/**
	 * Validate a single filter configuration.
	 *
	 * @param mixed $filter Raw filter configuration.
	 * @return bool True if valid, false otherwise.
	 */
	public static function validate_filter( $filter ): bool {
		if ( ! is_array( $filter ) ) {
			return false;
		}

		if ( ! isset( $filter['type'] ) || ! is_string( $filter['type'] ) ) {
			return false;
		}

		if ( ! isset( $filter['value'] ) ) {
			return false;
		}

		if ( ! in_array( $filter['type'], self::ALLOWED_FILTER_TYPES, true ) ) {
			return false;
		}

		// Type-specific validation.
		switch ( $filter['type'] ) {
			case 'type':
				return is_string( $filter['value'] )
					&& in_array( strtolower( $filter['value'] ), self::ALLOWED_PRODUCT_TYPES, true );
			case 'category':
			case 'tag':
				return is_array( $filter['value'] )
					|| ( is_string( $filter['value'] ) && '' !== trim( $filter['value'] ) );
			case 'stock_status':
				return is_string( $filter['value'] )
					&& in_array( strtolower( trim( $filter['value'] ) ), self::ALLOWED_STOCK_STATUSES, true );
			case 'price':
				return self::validate_price_filter( $filter['value'] );
			case 'sku':
				return is_array( $filter['value'] )
					|| ( is_string( $filter['value'] ) && '' !== trim( $filter['value'] ) );
			default:
				return false;
		}
	}

	/**
	 * Validate a price filter value.
	 *
	 * @param mixed $value Price filter value.
	 * @return bool True if valid.
	 */
	private static function validate_price_filter( $value ): bool {
		if ( ! is_array( $value ) ) {
			return false;
		}

		if ( empty( $value ) ) {
			return false;
		}

		if ( isset( $value['min'] ) && ! is_numeric( $value['min'] ) ) {
			return false;
		}

		if ( isset( $value['max'] ) && ! is_numeric( $value['max'] ) ) {
			return false;
		}

		if ( isset( $value['min'] ) && isset( $value['max'] )
			&& (float) $value['min'] > (float) $value['max']
		) {
			return false;
		}

		return true;
	}

	/**
	 * Pre-load WooCommerce product objects for a list of IDs.
	 *
	 * Called once per filter run to avoid repeated loading.
	 *
	 * @param list<int> $product_ids Product IDs.
	 * @return array<int, \WC_Product|null> Map of ID to WC_Product or null.
	 */
	private static function load_product_data( array $product_ids ): array {
		$data = array();
		foreach ( $product_ids as $id ) {
			$product               = wc_get_product( absint( $id ) );
			$data[ absint( $id ) ] = ( $product instanceof \WC_Product ) ? $product : null;
		}
		return $data;
	}

	/**
	 * Filter by product type.
	 *
	 * @param list<int>                          $product_ids Candidate IDs.
	 * @param array{type: string, value: string} $filter Filter config.
	 * @param array<int, \WC_Product|null>       $product_data Pre-loaded data.
	 * @return list<int>
	 */
	private static function apply_type_filter(
		array $product_ids,
		array $filter,
		array $product_data
	): array {
		$allowed_type = strtolower( $filter['value'] );
		$result       = array();

		foreach ( $product_ids as $id ) {
			$product = $product_data[ $id ] ?? null;
			if ( null === $product ) {
				continue;
			}

			if ( strtolower( $product->get_type() ) === $allowed_type ) {
				$result[] = $id;
			}
		}

		return $result;
	}

	/**
	 * Filter by product category slugs.
	 *
	 * @param list<int>                         $product_ids Candidate IDs.
	 * @param array{type: string, value: mixed} $filter Filter config.
	 * @param array<int, \WC_Product|null>      $product_data Pre-loaded data.
	 * @return list<int>
	 */
	private static function apply_category_filter(
		array $product_ids,
		array $filter,
		array $product_data
	): array {
		$target_categories = self::sanitize_category_value( $filter['value'] );
		if ( empty( $target_categories ) ) {
			return $product_ids;
		}

		$result = array();
		foreach ( $product_ids as $id ) {
			$product = $product_data[ $id ] ?? null;
			if ( null === $product ) {
				continue;
			}

			$product_categories = wp_get_post_terms(
				$id,
				'product_cat',
				array( 'fields' => 'slugs' )
			);

			if ( is_wp_error( $product_categories ) ) {
				$product_categories = array();
			}

			$intersect = array_intersect( $product_categories, $target_categories );
			if ( ! empty( $intersect ) ) {
				$result[] = $id;
			}
		}

		return $result;
	}

	/**
	 * Sanitize category filter value to a list of slugs.
	 *
	 * @param mixed $value Raw category value.
	 * @return list<string>
	 */
	private static function sanitize_category_value( $value ): array {
		if ( is_string( $value ) && '' !== trim( $value ) ) {
			return array( sanitize_text_field( trim( $value ) ) );
		}

		if ( is_array( $value ) ) {
			$sanitized = array();
			foreach ( $value as $slug ) {
				$s = sanitize_text_field( (string) $slug );
				if ( '' !== $s ) {
					$sanitized[] = $s;
				}
			}
			return array_unique( $sanitized );
		}

		return array();
	}

	/**
	 * Filter by product tag slugs.
	 *
	 * @param list<int>                         $product_ids Candidate IDs.
	 * @param array{type: string, value: mixed} $filter Filter config.
	 * @param array<int, \WC_Product|null>      $product_data Pre-loaded data.
	 * @return list<int>
	 */
	private static function apply_tag_filter(
		array $product_ids,
		array $filter,
		array $product_data
	): array {
		$target_tags = self::sanitize_tag_value( $filter['value'] );
		if ( empty( $target_tags ) ) {
			return $product_ids;
		}

		$result = array();
		foreach ( $product_ids as $id ) {
			$product = $product_data[ $id ] ?? null;
			if ( null === $product ) {
				continue;
			}

			$product_tags = wp_get_post_terms(
				$id,
				'product_tag',
				array( 'fields' => 'slugs' )
			);

			if ( is_wp_error( $product_tags ) ) {
				$product_tags = array();
			}

			$intersect = array_intersect( $product_tags, $target_tags );
			if ( ! empty( $intersect ) ) {
				$result[] = $id;
			}
		}

		return $result;
	}

	/**
	 * Sanitize tag filter value to a list of slugs.
	 *
	 * @param mixed $value Raw tag value.
	 * @return list<string>
	 */
	private static function sanitize_tag_value( $value ): array {
		if ( is_string( $value ) && '' !== trim( $value ) ) {
			return array( sanitize_text_field( trim( $value ) ) );
		}

		if ( is_array( $value ) ) {
			$sanitized = array();
			foreach ( $value as $slug ) {
				$s = sanitize_text_field( (string) $slug );
				if ( '' !== $s ) {
					$sanitized[] = $s;
				}
			}
			return array_unique( $sanitized );
		}

		return array();
	}

	/**
	 * Filter by stock status.
	 *
	 * @param list<int>                          $product_ids Candidate IDs.
	 * @param array{type: string, value: string} $filter Filter config.
	 * @param array<int, \WC_Product|null>       $product_data Pre-loaded data.
	 * @return list<int>
	 */
	private static function apply_stock_status_filter(
		array $product_ids,
		array $filter,
		array $product_data
	): array {
		$target_status = strtolower( trim( $filter['value'] ) );
		$result        = array();

		foreach ( $product_ids as $id ) {
			$product = $product_data[ $id ] ?? null;
			if ( null === $product ) {
				continue;
			}

			$status = $product->get_stock_status();
			if ( '' === $status ) {
				$status = 'instock';
			}

			if ( strtolower( $status ) === $target_status ) {
				$result[] = $id;
			}
		}

		return $result;
	}

	/**
	 * Filter by price range.
	 *
	 * Supports min and/or max bounds. Both are inclusive.
	 *
	 * @param list<int>                                                   $product_ids Candidate IDs.
	 * @param array{type: string, value: array{min?: float, max?: float}} $filter Filter config.
	 * @param array<int, \WC_Product|null>                                $product_data Pre-loaded data.
	 * @return list<int>
	 */
	private static function apply_price_filter(
		array $product_ids,
		array $filter,
		array $product_data
	): array {
		$value  = $filter['value'];
		$min    = isset( $value['min'] ) ? (float) $value['min'] : null;
		$max    = isset( $value['max'] ) ? (float) $value['max'] : null;
		$result = array();

		foreach ( $product_ids as $id ) {
			$product = $product_data[ $id ] ?? null;
			if ( null === $product ) {
				continue;
			}

			$price = $product->get_price();
			if ( null === $price && '' === $price ) {
				continue;
			}

			$numeric_price = (float) $price;

			if ( null !== $min && $numeric_price < $min ) {
				continue;
			}

			if ( null !== $max && $numeric_price > $max ) {
				continue;
			}

			$result[] = $id;
		}

		return $result;
	}

	/**
	 * Filter by SKU.
	 *
	 * @param list<int>                         $product_ids Candidate IDs.
	 * @param array{type: string, value: mixed} $filter Filter config.
	 * @param array<int, \WC_Product|null>      $product_data Pre-loaded data.
	 * @return list<int>
	 */
	private static function apply_sku_filter(
		array $product_ids,
		array $filter,
		array $product_data
	): array {
		$target_skus = self::sanitize_sku_value( $filter['value'] );
		if ( empty( $target_skus ) ) {
			return $product_ids;
		}

		$result = array();
		foreach ( $product_ids as $id ) {
			$product = $product_data[ $id ] ?? null;
			if ( null === $product ) {
				continue;
			}

			$sku = $product->get_sku();
			if ( in_array( (string) $sku, $target_skus, true ) ) {
				$result[] = $id;
			}
		}

		return $result;
	}

	/**
	 * Sanitize SKU filter value to a list of SKUs.
	 *
	 * @param mixed $value Raw SKU value.
	 * @return list<string>
	 */
	private static function sanitize_sku_value( $value ): array {
		if ( is_string( $value ) && '' !== trim( $value ) ) {
			return array( trim( $value ) );
		}

		if ( is_array( $value ) ) {
			$sanitized = array();
			foreach ( $value as $sku ) {
				$s = trim( (string) $sku );
				if ( '' !== $s ) {
					$sanitized[] = $s;
				}
			}
			return array_unique( $sanitized );
		}

		return array();
	}
}
