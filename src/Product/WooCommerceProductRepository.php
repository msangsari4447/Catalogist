<?php
/**
 * WooCommerce-backed product repository.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Product;

defined( 'ABSPATH' ) || exit;

/**
 * Product repository using WooCommerce APIs.
 */
final class WooCommerceProductRepository implements ProductRepositoryInterface {

	/**
	 * Cache transient prefix.
	 *
	 * @var string
	 */
	private const CACHE_PREFIX = 'catalogist_product_query_';

	/**
	 * Cache expiration in seconds (1 hour).
	 *
	 * @var int
	 */
	private const CACHE_EXPIRATION = 3600;

	/**
	 * Query products based on arguments.
	 *
	 * @param ProductQueryArgs $args Query arguments.
	 *
	 * @return ProductQueryResult
	 */
	public function query( ProductQueryArgs $args ): ProductQueryResult {
		if ( ! $this->is_woocommerce_active() ) {
			return new ProductQueryResult( array(), 0, 1, $args->get_per_page(), $args );
		}

		$query_args = $this->build_query_args( $args );
		$cache_key  = $this->get_cache_key( $query_args );

		// Try to load from transient cache.
		$cached = get_transient( $cache_key );

		if ( false !== $cached && is_array( $cached ) ) {
			return new ProductQueryResult(
				$cached['products'],
				$cached['total'],
				$args->get_page(),
				$args->get_per_page(),
				$args
			);
		}

		$results = $this->execute_query( $query_args, $args );

		// Cache the result.
		set_transient( $cache_key, $results, self::CACHE_EXPIRATION );

		return new ProductQueryResult(
			$results['products'],
			$results['total'],
			$args->get_page(),
			$args->get_per_page(),
			$args
		);
	}

	/**
	 * Find a single product by ID.
	 *
	 * @param int $product_id Product ID.
	 *
	 * @return array<string, mixed>|null Product data or null if not found.
	 */
	public function find( int $product_id ): ?array {
		if ( ! $this->is_woocommerce_active() ) {
			return null;
		}

		$product = wc_get_product( $product_id );

		if ( ! $product ) {
			return null;
		}

		return array(
			'id'       => $product->get_id(),
			'type'     => $product->get_type(),
			'status'   => $product->get_status(),
			'name'     => $product->get_name(),
			'slug'     => $product->get_slug(),
			'sku'      => $product->get_sku(),
			'price'    => $product->get_price(),
			'stock'    => $product->get_stock_quantity(),
			'categories' => $product->get_category_ids(),
			'tags'     => $product->get_tag_ids(),
		);
	}

	/**
	 * Check if a product exists.
	 *
	 * @param int $product_id Product ID.
	 *
	 * @return bool
	 */
	public function exists( int $product_id ): bool {
		return null !== $this->find( $product_id );
	}

	/**
	 * Get product IDs by category.
	 *
	 * @param int|string $category Category ID or slug.
	 *
	 * @return array<int>
	 */
	public function get_ids_by_category( int|string $category ): array {
		if ( ! $this->is_woocommerce_active() ) {
			return array();
		}

		$args = array(
			'status'   => 'publish',
			'limit'    => -1,
			'return'   => 'ids',
			'category' => is_numeric( $category ) ? array( absint( $category ) ) : array( sanitize_title( $category ) ),
		);

		$ids = wc_get_products( $args );

		return is_array( $ids ) ? array_map( 'absint', $ids ) : array();
	}

	/**
	 * Get product IDs by tag.
	 *
	 * @param int|string $tag Tag ID or slug.
	 *
	 * @return array<int>
	 */
	public function get_ids_by_tag( int|string $tag ): array {
		if ( ! $this->is_woocommerce_active() ) {
			return array();
		}

		$args = array(
			'status' => 'publish',
			'limit'  => -1,
			'return' => 'ids',
			'tag'    => is_numeric( $tag ) ? array( absint( $tag ) ) : array( sanitize_title( $tag ) ),
		);

		$ids = wc_get_products( $args );

		return is_array( $ids ) ? array_map( 'absint', $ids ) : array();
	}

	/**
	 * Check if WooCommerce is active.
	 *
	 * @return bool
	 */
	private function is_woocommerce_active(): bool {
		return function_exists( 'wc_get_products' ) && class_exists( 'WC_Product' );
	}

	/**
	 * Build WooCommerce query arguments from ProductQueryArgs.
	 *
	 * @param ProductQueryArgs $args Query arguments.
	 *
	 * @return array<string, mixed>
	 */
	private function build_query_args( ProductQueryArgs $args ): array {
		$query_args = array(
			'status'   => $args->get_status(),
			'limit'    => $args->get_per_page(),
			'page'     => $args->get_page(),
			'orderby'  => $args->get_orderby(),
			'order'    => $args->get_order(),
			'return'   => $args->get_return(),
		);

		// Include specific product IDs.
		$include = $args->get_include();
		if ( ! empty( $include ) ) {
			$query_args['include'] = $include;
		}

		// Exclude specific product IDs.
		$exclude = $args->get_exclude();
		if ( ! empty( $exclude ) ) {
			$query_args['exclude'] = $exclude;
		}

		// Category filter.
		$categories = $args->get_categories();
		if ( ! empty( $categories ) ) {
			$query_args['category'] = $categories;
		}

		// Tag filter.
		$tags = $args->get_tags();
		if ( ! empty( $tags ) ) {
			$query_args['tag'] = $tags;
		}

		// Search term.
		$search = $args->get_search();
		if ( '' !== $search ) {
			$query_args['s'] = $search;
		}

		// Stock status.
		$stock_status = $args->get_stock_status();
		if ( ! empty( $stock_status ) ) {
			$query_args['stock_status'] = $stock_status;
		}

		// Visibility.
		$visibility = $args->get_visibility();
		if ( ! empty( $visibility ) ) {
			$query_args['visibility'] = $visibility;
		}

		return $query_args;
	}

	/**
	 * Execute the WooCommerce product query.
	 *
	 * @param array<string, mixed> $query_args WooCommerce query args.
	 * @param ProductQueryArgs     $args       Original args for context.
	 *
	 * @return array<string, mixed>
	 */
	private function execute_query( array $query_args, ProductQueryArgs $args ): array {
		$products = wc_get_products( $query_args );

		if ( ! is_array( $products ) ) {
			return array(
				'products' => array(),
				'total'    => 0,
			);
		}

		$total = $this->get_total_count( $query_args );

		return array(
			'products' => $products,
			'total'    => $total,
		);
	}

	/**
	 * Get total count for pagination using count return type.
	 *
	 * @param array<string, mixed> $query_args Query args.
	 *
	 * @return int
	 */
	private function get_total_count( array $query_args ): int {
		$count_args = $query_args;
		unset( $count_args['limit'], $count_args['page'] );
		$count_args['return'] = 'count';

		$total = wc_get_products( $count_args );

		return is_int( $total ) ? $total : 0;
	}

	/**
	 * Generate a cache key from query arguments.
	 *
	 * @param array<string, mixed> $args Query args.
	 *
	 * @return string
	 */
	private function get_cache_key( array $args ): string {
		return self::CACHE_PREFIX . md5( wp_json_encode( $args ) );
	}
}
