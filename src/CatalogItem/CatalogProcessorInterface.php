<?php
/**
 * Catalog Processor Interface — defines the contract for catalog item processing.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\CatalogItem;

use Catalogist\Product\ProductQueryResult;
use Catalogist\Variation\VariationQueryArgs;

/**
 * Interface for processing catalog products and variations into CatalogItem objects.
 */
interface CatalogProcessorInterface {

	/**
	 * Process product query results into normalized CatalogItem objects.
	 *
	 * @param ProductQueryResult     $product_result     Product query result.
	 * @param VariationQueryArgs|null $variation_args     Variation expansion args.
	 *                                                     null = no expansion (parent mode).
	 *
	 * @return array<CatalogItem>
	 */
	public function process( ProductQueryResult $product_result, ?VariationQueryArgs $variation_args = null ): array;

	/**
	 * Get a single CatalogItem by ID.
	 *
	 * @param int $id Product or variation ID.
	 *
	 * @return CatalogItem|null
	 */
	public function find( int $id ): ?CatalogItem;
}