<?php
/**
 * Variation repository interface.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Variation;

defined( 'ABSPATH' ) || exit;

/**
 * Interface for variation retrieval.
 *
 * Strictly separated from ProductRepositoryInterface.
 */
interface VariationRepositoryInterface {

	/**
	 * Get variations for a variable product.
	 *
	 * @param int                        $product_id Parent product ID.
	 * @param VariationQueryArgs         $args       Variation query arguments.
	 *
	 * @return VariationQueryResult
	 */
	public function get_variations( int $product_id, VariationQueryArgs $args ): VariationQueryResult;

	/**
	 * Find a single variation by ID.
	 *
	 * @param int $variation_id Variation ID.
	 *
	 * @return array<string, mixed>|null
	 */
	public function find( int $variation_id ): ?array;

	/**
	 * Check if a variation exists.
	 *
	 * @param int $variation_id Variation ID.
	 *
	 * @return bool
	 */
	public function exists( int $variation_id ): bool;

	/**
	 * Get all variation IDs for a variable product.
	 *
	 * @param int $product_id Parent product ID.
	 *
	 * @return array<int>
	 */
	public function get_variation_ids( int $product_id ): array;

	/**
	 * Check if a product is a variable product.
	 *
	 * @param int $product_id Product ID.
	 *
	 * @return bool
	 */
	public function is_variable_product( int $product_id ): bool;
}
