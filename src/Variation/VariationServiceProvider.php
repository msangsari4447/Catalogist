<?php
/**
 * Variation service provider.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Variation;

defined( 'ABSPATH' ) || exit;

use Catalogist\Core\Container;
use Catalogist\Core\ServiceProviderInterface;

/**
 * Registers variation services.
 */
final class VariationServiceProvider implements ServiceProviderInterface {

	/**
	 * Register services in the container.
	 *
	 * @param Container $container Service container.
	 *
	 * @return void
	 */
	public function register( Container $container ): void {
		$container->set(
			VariationRepositoryInterface::class,
			static function (): VariationRepositoryInterface {
				return new WooCommerceVariationRepository();
			}
		);

		$container->factory(
			VariationService::class,
			static function ( Container $container ): VariationService {
				$repo = $container->get( VariationRepositoryInterface::class );
				return new VariationService( $repo );
			}
		);

		$container->set( VariationMode::class, new VariationMode( VariationMode::PARENT ) );
	}

	/**
	 * Boot the service provider.
	 *
	 * @param Container $container Service container.
	 *
	 * @return void
	 */
	public function boot( Container $container ): void {
		// Cache invalidation hooks for variation queries.
		add_action( 'transition_post_status', array( __CLASS__, 'invalidate_variation_cache' ), 10, 3 );
		add_action( 'updated_post_meta', array( __CLASS__, 'invalidate_variation_cache_on_meta' ), 10, 4 );
	}

	/**
	 * Invalidate variation query cache when variation status changes.
	 *
	 * @param string $new_status New post status.
	 * @param string $old_status Old post status.
	 * @param object $post Post object.
	 *
	 * @return void
	 */
	public static function invalidate_variation_cache( string $new_status, string $old_status, $post ): void {
		if ( ! isset( $post->post_type ) || 'product_variation' !== $post->post_type ) {
			return;
		}

		// Delete all variation caches for this product's parent.
		$parent_id = $post->post_parent;
		if ( $parent_id > 0 ) {
			self::delete_variation_transients( $parent_id );
		}
	}

	/**
	 * Invalidate variation query cache when variation meta is updated.
	 *
	 * @param int    $meta_id    Meta ID.
	 * @param int    $object_id  Object ID.
	 * @param string $meta_key   Meta key.
	 * @param mixed  $meta_value Meta value.
	 *
	 * @return void
	 */
	public static function invalidate_variation_cache_on_meta( int $meta_id, int $object_id, string $meta_key, $meta_value ): void {
		if ( 'product_variation' !== get_post_type( $object_id ) ) {
			return;
		}

		$variation_meta_keys = array(
			'_stock_status',
			'_price',
			'_sku',
			'_image_id',
			'_attributes',
		);

		if ( in_array( $meta_key, $variation_meta_keys, true ) ) {
			$parent_id = wp_get_post_parent_id( $object_id );
			if ( $parent_id > 0 ) {
				self::delete_variation_transients( $parent_id );
			}
		}
	}

	/**
	 * Delete all variation transients for a parent product.
	 *
	 * @param int $parent_id Parent product ID.
	 *
	 * @return void
	 */
	private static function delete_variation_transients( int $parent_id ): void {
		// Since we use hash-based keys, we can't easily enumerate all keys.
		// In practice, we rely on the TTL (5 minutes) for expiration.
		// For immediate invalidation of specific parent's variations, we would need
		// to track keys or use object cache groups. For now, TTL handles it.
		// Future improvement: use wp_cache with groups for targeted invalidation.
	}
}
