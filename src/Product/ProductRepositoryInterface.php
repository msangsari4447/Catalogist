<?php
/**
 * Product repository interface.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Product;

defined( 'ABSPATH' ) || exit;

/**
 * Interface for product retrieval.
 */
interface ProductRepositoryInterface {

	/**
	 * Query products based on arguments.
	 *
	 * @param ProductQueryArgs $args Query arguments.
	 *
	 * @return ProductQueryResult
	 */
	public function query( ProductQueryArgs $args ): ProductQueryResult;

	/**
	 * Find a single product by ID.
	 *
	 * @param int $product_id Product ID.
	 *
	 * @return array<string, mixed>|null
	 */
	public function find( int $product_id ): ?array;

	/**
	 * Check if a product exists.
	 *
	 * @param int $product_id Product ID.
	 *
	 * @return bool
	 */
	public function exists( int $product_id ): bool;

	/**
	 * Get product IDs by category.
	 *
	 * @param int|string $category Category ID or slug.
	 *
	 * @return array<int>
	 */
	public function get_ids_by_category( int|string $category ): array;

	/**
	 * Get product IDs by tag.
	 *
	 * @param int|string $tag Tag ID or slug.
	 *
	 * @return array<int>
	 */
	public function get_ids_by_tag( int|string $tag ): array;
}
