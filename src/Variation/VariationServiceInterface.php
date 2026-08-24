<?php
/**
 * Variation service interface.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Variation;

defined( 'ABSPATH' ) || exit;

use Catalogist\Product\ProductQueryResult;

/**
 * Interface for variation expansion service.
 */
interface VariationServiceInterface {

	/**
	 * Expand product query results according to variation mode.
	 *
	 * @param ProductQueryResult    $product_result Product query result.
	 * @param VariationQueryArgs    $variation_args Variation query arguments.
	 *
	 * @return VariationQueryResult
	 */
	public function expand( ProductQueryResult $product_result, VariationQueryArgs $variation_args ): VariationQueryResult;

	/**
	 * Get variation data for a single product.
	 *
	 * @param int               $product_id    Product ID.
	 * @param VariationQueryArgs $variation_args Variation query arguments.
	 *
	 * @return VariationQueryResult
	 */
	public function get_product_variations( int $product_id, VariationQueryArgs $variation_args ): VariationQueryResult;

	/**
	 * Check if a product has expandable variations.
	 *
	 * @param int $product_id Product ID.
	 *
	 * @return bool
	 */
	public function has_variations( int $product_id ): bool;
}
