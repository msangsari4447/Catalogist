<?php
/**
 * Variation expansion service.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Variation;

defined( 'ABSPATH' ) || exit;

use Catalogist\Product\ProductQueryResult;

/**
 * Orchestrates variation expansion between product and variation repositories.
 *
 * Bridges ProductRepositoryInterface and VariationRepositoryInterface.
 */
final class VariationService {

	/**
	 * Variation repository.
	 *
	 * @var VariationRepositoryInterface
	 */
	private VariationRepositoryInterface $variation_repository;

	/**
	 * Constructor.
	 *
	 * @param VariationRepositoryInterface $variation_repository Variation repository.
	 */
	public function __construct( VariationRepositoryInterface $variation_repository ) {
		$this->variation_repository = $variation_repository;
	}

	/**
	 * Expand product query results according to variation mode.
	 *
	 * @param ProductQueryResult     $product_result Product query result.
	 * @param VariationQueryArgs     $variation_args Variation query arguments.
	 *
	 * @return VariationQueryResult
	 */
	public function expand( ProductQueryResult $product_result, VariationQueryArgs $variation_args ): VariationQueryResult {
		$mode = $variation_args->get_mode();

		// For parent mode, return empty variation result (products stay as-is).
		if ( $mode->is_parent_mode() ) {
			return new VariationQueryResult( 0, array(), 0, $mode );
		}

		// For expansion modes, process each variable product.
		$all_variations = array();
		$product_ids    = $product_result->get_ids();

		foreach ( $product_ids as $product_id ) {
			if ( ! $this->variation_repository->is_variable_product( $product_id ) ) {
				continue;
			}

			$variation_result = $this->variation_repository->get_variations( $product_id, $variation_args );
			$variations       = $variation_result->get_variations();

			foreach ( $variations as $variation_id => $variation_data ) {
				$variation_data['parent_product_id'] = $product_id;
				$all_variations[ $variation_id ] = $variation_data;
			}
		}

		return new VariationQueryResult(
			count( $product_ids ) > 0 ? (int) reset( $product_ids ) : 0,
			$all_variations,
			count( $all_variations ),
			$mode
		);
	}

	/**
	 * Get variation data for a single product.
	 *
	 * @param int                  $product_id    Product ID.
	 * @param VariationQueryArgs   $variation_args Variation query arguments.
	 *
	 * @return VariationQueryResult
	 */
	public function get_product_variations( int $product_id, VariationQueryArgs $variation_args ): VariationQueryResult {
		$mode = $variation_args->get_mode();

		if ( $mode->is_parent_mode() ) {
			return new VariationQueryResult( $product_id, array(), 0, $mode );
		}

		return $this->variation_repository->get_variations( $product_id, $variation_args );
	}

	/**
	 * Check if a product has expandable variations.
	 *
	 * @param int $product_id Product ID.
	 *
	 * @return bool
	 */
	public function has_variations( int $product_id ): bool {
		return $this->variation_repository->is_variable_product( $product_id );
	}
}
