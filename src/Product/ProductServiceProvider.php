<?php
/**
 * Product service provider.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Product;

defined( 'ABSPATH' ) || exit;

use Catalogist\Core\Container;
use Catalogist\Core\ServiceProviderInterface;

/**
 * Registers product services.
 */
final class ProductServiceProvider implements ServiceProviderInterface {

	/**
	 * Register services in the container.
	 *
	 * @param Container $container Service container.
	 *
	 * @return void
	 */
	public function register( Container $container ): void {
		$container->set(
			ProductRepositoryInterface::class,
			static function (): ProductRepositoryInterface {
				return new WooCommerceProductRepository();
			}
		);

		$container->set( ProductFilters::class, new ProductFilters() );
		$container->set( ProductSorter::class, new ProductSorter() );
		$container->set( ProductSearch::class, new ProductSearch() );
	}

	/**
	 * Boot the service provider.
	 *
	 * @param Container $container Service container.
	 *
	 * @return void
	 */
	public function boot( Container $container ): void {
		// Cache invalidation hooks for product queries.
		add_action( 'transition_post_status', array( __CLASS__, 'invalidate_product_cache' ), 10, 3 );
		add_action( 'updated_post_meta', array( __CLASS__, 'invalidate_product_cache_on_meta' ), 10, 4 );
	}

	/**
	 * Invalidate product query cache when post status changes.
	 *
	 * @param string $new_status New post status.
	 * @param string $old_status Old post status.
	 * @param object $post Post object.
	 *
	 * @return void
	 */
	public static function invalidate_product_cache( string $new_status, string $old_status, $post ): void {
		if ( ! isset( $post->post_type ) || 'product' !== $post->post_type ) {
			return;
		}

		// Delete all product query caches.
		$prefix = 'catalogist_product_query_';
		// Use transient deletion - we'd need to track keys, so we use a simpler approach.
		// In production, you might want to use object cache groups for easier invalidation.
		delete_transient( $prefix . 'all' );
	}

	/**
	 * Invalidate product query cache when product meta is updated.
	 *
	 * @param int    $meta_id    Meta ID.
	 * @param int    $object_id  Object ID.
	 * @param string $meta_key   Meta key.
	 * @param mixed  $meta_value Meta value.
	 *
	 * @return void
	 */
	public static function invalidate_product_cache_on_meta( int $meta_id, int $object_id, string $meta_key, $meta_value ): void {
		if ( 'product' !== get_post_type( $object_id ) ) {
			return;
		}

		// Invalidate cache on key product meta changes.
		$product_meta_keys = array(
			'_stock_status',
			'_visibility',
			'_sku',
			'_price',
			'_category_ids',
			'_tag_ids',
		);

		if ( in_array( $meta_key, $product_meta_keys, true ) ) {
			delete_transient( 'catalogist_product_query_all' );
		}
	}
}
