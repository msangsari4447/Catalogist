<?php

declare(strict_types=1);

namespace Catalogist;

/**
 * Product Query Engine for Catalogist.
 *
 * Provides a clean boundary between Catalogist and WooCommerce product queries.
 * All query construction happens inside this class using safe, allow-listed values.
 *
 * @phpstan-type QueryArgs array{
 *     ids?: list<int>,
 *     search?: string,
 *     category?: list<string>,
 *     tag?: list<string>,
 *     sku?: list<string>,
 *     type?: string,
 *     status?: string,
 *     stock_status?: string,
 *     orderby?: string,
 *     order?: string,
 *     page?: int,
 *     per_page?: int
 * }
 */
final class ProductQueryEngine {

	/**
	 * Allowed post statuses for product queries.
	 *
	 * @var list<string>
	 */
	private const ALLOWED_STATUSES = array(
		'publish',
		'draft',
		'pending',
		'private',
		'trash',
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
		'',
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
	 * Allowed sort orderby values.
	 *
	 * @var list<string>
	 */
	private const ALLOWED_ORDERBY = array(
		'date',
		'title',
		'id',
		'menu_order',
		'post__in',
	);

	/**
	 * Allowed order directions.
	 *
	 * @var list<string>
	 */
	private const ALLOWED_ORDERS = array(
		'ASC',
		'DESC',
	);

	/**
	 * Default query arguments.
	 *
	 * @var array<string, mixed>
	 */
	private const DEFAULT_ARGS = array(
		'post_type'      => 'product',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'menu_order',
		'order'          => 'ASC',
		'fields'         => 'ids',
		'no_found_rows'  => true,
	);

	/**
	 * Query WooCommerce products with the given filters.
	 *
	 * Returns an array of product IDs sorted according to the query arguments.
	 * Invalid or missing filters are silently ignored.
	 *
	 * @param QueryArgs $args Query arguments.
	 * @return list<int> Array of product IDs.
	 */
	public static function query( array $args = array() ): array {
		$query_args = self::build_query_args( $args );

		$query       = new \WP_Query( $query_args );
		$product_ids = is_array( $query->posts ) ? $query->posts : array();

		return array_map( 'intval', $product_ids );
	}

	/**
	 * Get total count of matching products without loading them.
	 *
	 * @param QueryArgs $args Query arguments.
	 * @return int Total matching product count.
	 */
	public static function count( array $args = array() ): int {
		$query_args = self::build_query_args( $args, true );

		$query = new \WP_Query( $query_args );
		return (int) $query->found_posts;
	}

	/**
	 * Check if WooCommerce product post type exists.
	 *
	 * @return bool True if product post type is registered.
	 */
	public static function is_product_post_type_available(): bool {
		return post_type_exists( 'product' );
	}

	/**
	 * Build safe WP_Query arguments from user-supplied args.
	 *
	 * @param QueryArgs $args User-supplied query arguments.
	 * @param bool      $count_only Whether to build args for count-only query.
	 * @return array<string, mixed> Safe WP_Query arguments.
	 */
	private static function build_query_args( array $args, bool $count_only = false ): array {
		$query_args = self::DEFAULT_ARGS;

		// Product IDs.
		if ( ! empty( $args['ids'] ) && is_array( $args['ids'] ) ) {
			$sanitized_ids = self::sanitize_id_array( $args['ids'] );
			if ( ! empty( $sanitized_ids ) ) {
				$query_args['post__in'] = $sanitized_ids;
				$query_args['orderby']  = 'post__in';
				$query_args['order']    = 'ASC';
			}
		}

		// Search.
		if ( ! empty( $args['search'] ) && is_string( $args['search'] ) ) {
			$query_args['s'] = sanitize_text_field( $args['search'] );
		}

		// Category.
		if ( ! empty( $args['category'] ) && is_array( $args['category'] ) ) {
			$terms = self::sanitize_term_array( $args['category'], 'product_cat' );
			if ( ! empty( $terms ) ) {
				$query_args['tax_query'][] = array(
					'taxonomy' => 'product_cat',
					'field'    => 'slug',
					'terms'    => $terms,
				);
			}
		}

		// Tag.
		if ( ! empty( $args['tag'] ) && is_array( $args['tag'] ) ) {
			$terms = self::sanitize_term_array( $args['tag'], 'product_tag' );
			if ( ! empty( $terms ) ) {
				$query_args['tax_query'][] = array(
					'taxonomy' => 'product_tag',
					'field'    => 'slug',
					'terms'    => $terms,
				);
			}
		}

		// SKU.
		if ( ! empty( $args['sku'] ) && is_array( $args['sku'] ) ) {
			$skus = self::sanitize_sku_array( $args['sku'] );
			if ( ! empty( $skus ) ) {
				$query_args['meta_query'][] = array(
					'key'     => '_sku',
					'value'   => $skus,
					'compare' => 'IN',
				);
			}
		}

		// Product type (stored as product_type taxonomy term in WC 11+).
		if ( ! empty( $args['type'] ) && is_string( $args['type'] ) ) {
			$type = self::sanitize_product_type( $args['type'] );
			if ( null !== $type ) {
				$query_args['tax_query'][] = array(
					'taxonomy' => 'product_type',
					'field'    => 'slug',
					'terms'    => $type,
				);
			}
		}

		// Status.
		if ( ! empty( $args['status'] ) && is_string( $args['status'] ) ) {
			$status = self::sanitize_status( $args['status'] );
			if ( null !== $status ) {
				$query_args['post_status'] = $status;
			}
		}

		// Stock status.
		if ( ! empty( $args['stock_status'] ) && is_string( $args['stock_status'] ) ) {
			$stock_status = self::sanitize_stock_status( $args['stock_status'] );
			if ( null !== $stock_status ) {
				$query_args['meta_query'][] = array(
					'key'     => '_stock_status',
					'value'   => $stock_status,
					'compare' => '=',
				);
			}
		}

		// Sorting.
		if ( ! empty( $args['orderby'] ) && is_string( $args['orderby'] ) ) {
			$orderby = self::sanitize_orderby( $args['orderby'] );
			if ( null !== $orderby ) {
				$query_args['orderby'] = $orderby;
			}
		}

		if ( ! empty( $args['order'] ) && is_string( $args['order'] ) ) {
			$order = self::sanitize_order( $args['order'] );
			if ( null !== $order ) {
				$query_args['order'] = $order;
			}
		}

		// Pagination.
		if ( ! empty( $args['page'] ) && is_numeric( $args['page'] ) ) {
			$page                         = max( 1, (int) $args['page'] );
			$per_page                     = ! empty( $args['per_page'] ) && is_numeric( $args['per_page'] )
				? max( 1, (int) $args['per_page'] )
				: -1;
			$query_args['paged']          = $page;
			$query_args['posts_per_page'] = $per_page;
			$query_args['no_found_rows']  = false;
		}

		// Count-only queries need found_posts.
		if ( $count_only ) {
			$query_args['no_found_rows'] = false;
		}

		// Clean up empty meta_query and tax_query.
		if ( empty( $query_args['meta_query'] ) ) {
			unset( $query_args['meta_query'] );
		}
		if ( empty( $query_args['tax_query'] ) ) {
			unset( $query_args['tax_query'] );
		}

		return $query_args;
	}

	/**
	 * Sanitize an array of IDs to integers.
	 *
	 * @param list<mixed> $ids Raw IDs.
	 * @return list<int> Sanitized IDs.
	 */
	private static function sanitize_id_array( array $ids ): array {
		$sanitized = array();
		foreach ( $ids as $id ) {
			$int_id = (int) $id;
			if ( $int_id > 0 ) {
				$sanitized[] = $int_id;
			}
		}
		return array_unique( $sanitized );
	}

	/**
	 * Sanitize an array of term slugs for a taxonomy.
	 *
	 * Only returns slugs that correspond to existing terms.
	 *
	 * @param list<mixed> $slugs Raw slugs.
	 * @param string      $taxonomy Taxonomy name.
	 * @return list<string> Sanitized slugs that exist in the taxonomy.
	 */
	private static function sanitize_term_array( array $slugs, string $taxonomy ): array {
		$sanitized = array();
		foreach ( $slugs as $slug ) {
			$s = sanitize_title( $slug );
			if ( '' !== $s ) {
				$term = get_term_by( 'slug', $s, $taxonomy );
				if ( $term && ! is_wp_error( $term ) ) {
					$sanitized[] = $s;
				}
			}
		}
		return array_unique( $sanitized );
	}

	/**
	 * Sanitize an array of SKUs.
	 *
	 * @param list<mixed> $skus Raw SKUs.
	 * @return list<string> Sanitized SKUs.
	 */
	private static function sanitize_sku_array( array $skus ): array {
		$sanitized = array();
		foreach ( $skus as $sku ) {
			$s = sanitize_text_field( (string) $sku );
			if ( '' !== $s ) {
				$sanitized[] = $s;
			}
		}
		return array_unique( $sanitized );
	}

	/**
	 * Sanitize product type against allow-list.
	 *
	 * @param string $type Raw product type.
	 * @return string|null Sanitized type or null if invalid.
	 */
	private static function sanitize_product_type( string $type ): ?string {
		$type = strtolower( $type );
		if ( in_array( $type, self::ALLOWED_PRODUCT_TYPES, true ) ) {
			return $type;
		}
		return null;
	}

	/**
	 * Sanitize post status against allow-list.
	 *
	 * @param string $status Raw post status.
	 * @return string|null Sanitized status or null if invalid.
	 */
	private static function sanitize_status( string $status ): ?string {
		$status = strtolower( $status );
		if ( in_array( $status, self::ALLOWED_STATUSES, true ) ) {
			return $status;
		}
		return null;
	}

	/**
	 * Sanitize stock status against allow-list.
	 *
	 * @param string $stock_status Raw stock status.
	 * @return string|null Sanitized stock status or null if invalid.
	 */
	private static function sanitize_stock_status( string $stock_status ): ?string {
		$stock_status = strtolower( $stock_status );
		if ( in_array( $stock_status, self::ALLOWED_STOCK_STATUSES, true ) ) {
			return $stock_status;
		}
		return null;
	}

	/**
	 * Sanitize orderby value against allow-list.
	 *
	 * @param string $orderby Raw orderby value.
	 * @return string|null Sanitized orderby or null if invalid.
	 */
	private static function sanitize_orderby( string $orderby ): ?string {
		$orderby = strtolower( $orderby );
		if ( in_array( $orderby, self::ALLOWED_ORDERBY, true ) ) {
			return $orderby;
		}
		return null;
	}

	/**
	 * Sanitize order direction against allow-list.
	 *
	 * @param string $order Raw order direction.
	 * @return string|null Sanitized order or null if invalid.
	 */
	private static function sanitize_order( string $order ): ?string {
		$order = strtoupper( $order );
		if ( in_array( $order, self::ALLOWED_ORDERS, true ) ) {
			return $order;
		}
		return null;
	}
}
