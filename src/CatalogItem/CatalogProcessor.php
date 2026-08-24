<?php
/**
 * Catalog Processor — normalizes query results into CatalogItem objects.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\CatalogItem;

defined( 'ABSPATH' ) || exit;

use Catalogist\Product\ProductQueryResult;
use Catalogist\Product\ProductRepositoryInterface;
use Catalogist\Variation\VariationQueryArgs;
use Catalogist\Variation\VariationServiceInterface;

/**
 * Orchestrates normalization from product/variation query results to CatalogItem objects.
 */
final class CatalogProcessor {

	/**
	 * Item factory.
	 *
	 * @var CatalogItemFactory
	 */
	private CatalogItemFactory $factory;

	/**
	 * Variation service.
	 *
	 * @var VariationServiceInterface
	 */
	private VariationServiceInterface $variation_service;

	/**
	 * Product repository.
	 *
	 * @var ProductRepositoryInterface
	 */
	private ProductRepositoryInterface $product_repository;

	/**
	 * Cache for product normalization during a single process() call.
	 *
	 * @var array<int, CatalogItem>
	 */
	private array $product_cache = array();

	/**
	 * Constructor.
	 *
	 * @param CatalogItemFactory           $factory            Item factory.
	 * @param VariationServiceInterface    $variation_service  Variation service.
	 * @param ProductRepositoryInterface   $product_repository Product repository.
	 */
	public function __construct(
		CatalogItemFactory $factory,
		VariationServiceInterface $variation_service,
		ProductRepositoryInterface $product_repository
	) {
		$this->factory            = $factory;
		$this->variation_service  = $variation_service;
		$this->product_repository = $product_repository;
	}

	/**
	 * Process product query results into normalized CatalogItem objects.
	 *
	 * @param ProductQueryResult     $product_result     Product query result.
	 * @param VariationQueryArgs|null $variation_args     Variation expansion args.
	 *                                                     null = no expansion (parent mode).
	 *
	 * @return array<CatalogItem>
	 */
	public function process(
		ProductQueryResult $product_result,
		?VariationQueryArgs $variation_args = null
	): array {
		$this->product_cache = array();

		$items = array();
		$mode  = $variation_args ? $variation_args->get_mode() : null;

		// Normalize all products first.
		$products = $product_result->get_products();
		foreach ( $products as $product ) {
			$catalog_item = $this->normalize_product( $product );
			if ( $catalog_item ) {
				$this->product_cache[ $catalog_item->get_id() ] = $catalog_item;
				$items[] = $catalog_item;
			}
		}

		// Expand variations if mode requires it.
		if ( $mode && $mode->is_expansion_mode() ) {
			$variation_result = $this->variation_service->expand( $product_result, $variation_args );

			if ( $mode->is_table_mode() ) {
				// Table mode: attach variation data to parent items.
				$items = $this->apply_table_mode( $items, $variation_result );
			} elseif ( $variation_result->has_variations() ) {
				// All/selected/multiple modes: add variation items.
				$items = $this->apply_variation_items( $items, $variation_result );
			}
		}

		return $items;
	}

	/**
	 * Get a single CatalogItem by ID.
	 *
	 * @param int $id Product or variation ID.
	 *
	 * @return CatalogItem|null
	 */
	public function find( int $id ): ?CatalogItem {
		$product = $this->product_repository->find( $id );

		if ( ! $product ) {
			return null;
		}

		return $this->factory->from_product( $product );
	}

	/**
	 * Normalize a single product (object or array) to CatalogItem.
	 *
	 * @param mixed $product Product object or array.
	 *
	 * @return CatalogItem|null
	 */
	private function normalize_product( $product ): ?CatalogItem {
		if ( is_int( $product ) ) {
			$data = $this->product_repository->find( $product );
			if ( ! $data ) {
				return null;
			}
			return $this->factory->from_product( $data );
		}

		if ( is_array( $product ) ) {
			return $this->factory->from_product( $product );
		}

		if ( is_object( $product ) ) {
			return $this->factory->from_product( $product );
		}

		return null;
	}

	/**
	 * Apply table mode: attach variation data to parent CatalogItems.
	 *
	 * @param array<CatalogItem>   $items            Existing catalog items.
	 * @param \Catalogist\Variation\VariationQueryResult $variation_result Variation query result.
	 *
	 * @return array<CatalogItem>
	 */
	private function apply_table_mode( array $items, $variation_result ): array {
		$variations = $variation_result->get_variations();
		if ( empty( $variations ) ) {
			return $items;
		}

		$parent_id = $variation_result->get_parent_product_id();
		$normalized_variations = array();

		foreach ( $variations as $variation_id => $variation_data ) {
			$normalized = array(
				'id'           => $variation_id,
				'title'        => $variation_data['name'] ?? '',
				'attributes'   => $variation_data['attributes'] ?? array(),
				'price'        => $variation_data['price'] ?? '',
				'sale_price'   => $variation_data['sale_price'] ?? '',
				'sku'          => $variation_data['sku'] ?? '',
				'stock_status' => $variation_data['stock_status'] ?? 'instock',
				'permalink'    => '',
				'image'        => $variation_data['image'] ?? null,
			);
			$normalized_variations[ $variation_id ] = $normalized;
		}

		$variation_table = array(
			'variations' => $normalized_variations,
			'parent_id'  => $parent_id,
		);

		// Replace parent item with one that has variation table data.
		$updated = array();
		foreach ( $items as $item ) {
			if ( $item->get_id() === $parent_id ) {
				$updated[] = $this->attach_variation_table( $item, $variation_table );
			} else {
				$updated[] = $item;
			}
		}

		return $updated;
	}

	/**
	 * Attach variation table data to a CatalogItem.
	 *
	 * @param CatalogItem           $item           Parent item.
	 * @param array<string, mixed>  $variation_table Variation table data.
	 *
	 * @return CatalogItem
	 */
	private function attach_variation_table( CatalogItem $item, array $variation_table ): CatalogItem {
		$data = $item->to_array();
		$data['variation_table'] = $variation_table;
		$data['metadata'] = array_merge( $data['metadata'], array( 'has_variations' => true ) );

		return new CatalogItem(
			$data['id'],
			$data['type'],
			$data['parent_product_id'],
			$data['title'],
			$data['sku'],
			$data['price'],
			$data['regular_price'],
			$data['sale_price'],
			$data['description'],
			$data['short_description'],
			$data['image'],
			$data['gallery'],
			$data['categories'],
			$data['tags'],
			$data['attributes'],
			$data['stock_status'],
			$data['stock_quantity'],
			$data['permalink'],
			$data['parent_product'],
			$data['variation_table'],
			$data['metadata']
		);
	}

	/**
	 * Apply variation expansion modes: add variation CatalogItems to the list.
	 *
	 * @param array<CatalogItem>   $items            Existing catalog items.
	 * @param \Catalogist\Variation\VariationQueryResult $variation_result Variation query result.
	 *
	 * @return array<CatalogItem>
	 */
	private function apply_variation_items( array $items, $variation_result ): array {
		$variations = $variation_result->get_variations();
		if ( empty( $variations ) ) {
			return $items;
		}

		$parent_id = $variation_result->get_parent_product_id();
		$parent_context = $this->factory->get_parent_context( $parent_id );

		// Remove the parent product from items (it's replaced by variations).
		$updated = array_values(
			array_filter(
				$items,
				static function ( CatalogItem $item ) use ( $parent_id ): bool {
					return $item->get_id() !== $parent_id;
				}
			)
		);

		// Add each variation as a separate CatalogItem.
		foreach ( $variations as $variation_id => $variation_data ) {
			$catalog_item = $this->factory->from_variation( $variation_data, $parent_context );
			$updated[] = $catalog_item;
		}

		return $updated;
	}
}
