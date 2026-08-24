<?php
/**
 * Catalog Item factory.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\CatalogItem;

defined( 'ABSPATH' ) || exit;

use Catalogist\Product\ProductRepositoryInterface;

/**
 * Creates CatalogItem instances from raw product and variation data.
 */
final class CatalogItemFactory {

	/**
	 * Product repository for parent context lookups.
	 *
	 * @var ProductRepositoryInterface
	 */
	private ProductRepositoryInterface $product_repository;

	/**
	 * Cache for parent product lookups to avoid duplicate calls.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $parent_cache = array();

	/**
	 * Cache for image resolution to avoid duplicate calls.
	 *
	 * @var array<int, array{id:int,src:string,width:int,height:int}|null>
	 */
	private array $image_cache = array();

	/**
	 * Constructor.
	 *
	 * @param ProductRepositoryInterface $product_repository Product repository.
	 */
	public function __construct( ProductRepositoryInterface $product_repository ) {
		$this->product_repository = $product_repository;
	}

	/**
	 * Create a CatalogItem from product data.
	 *
	 * Accepts either a \WC_Product object or a normalized array.
	 *
	 * @param object|array<string, mixed> $data          Product data.
	 * @param array<string, mixed>        $extra_metadata  Extra metadata to merge.
	 *
	 * @return CatalogItem
	 */
	public function from_product( $data, array $extra_metadata = array() ): CatalogItem {
		$props = $this->extract_product_properties( $data );

		$image = null;
		if ( isset( $props['image_id'] ) && $props['image_id'] > 0 ) {
			$image = $this->resolve_image( $props['image_id'] );
		}

		$metadata = array_merge(
			array(
				'has_variations' => isset( $props['is_variable'] ) ? $props['is_variable'] : false,
				'dimensions'     => isset( $props['dimensions'] ) ? $props['dimensions'] : null,
				'weight'         => isset( $props['weight'] ) ? $props['weight'] : null,
			),
			$extra_metadata
		);

		return new CatalogItem(
			(int) $props['id'],
			'product',
			0,
			(string) $props['name'],
			(string) $props['sku'],
			(string) $props['price'],
			(string) $props['regular_price'],
			(string) $props['sale_price'],
			(string) $props['description'],
			(string) $props['short_description'],
			$image,
			is_array( $props['gallery'] ) ? array_map( 'absint', $props['gallery'] ) : array(),
			is_array( $props['categories'] ) ? array_map( 'absint', $props['categories'] ) : array(),
			is_array( $props['tags'] ) ? array_map( 'absint', $props['tags'] ) : array(),
			array(),
			(string) $props['stock_status'],
			is_numeric( $props['stock_quantity'] ) ? (int) $props['stock_quantity'] : null,
			(string) $props['permalink'],
			null,
			null,
			$metadata
		);
	}

	/**
	 * Create a CatalogItem from variation data.
	 *
	 * @param array<string, mixed> $variation_data    Variation data from VariationQueryResult.
	 * @param array<string, mixed>|null $parent_product  Parent product data from ProductRepositoryInterface::find().
	 *
	 * @return CatalogItem
	 */
	public function from_variation( array $variation_data, ?array $parent_product = null ): CatalogItem {
		$image = null;
		if ( isset( $variation_data['image'] ) && is_array( $variation_data['image'] ) ) {
			$image = $variation_data['image'];
		}

		$parent_context = null;
		if ( $parent_product ) {
			$parent_context = array(
				'id'        => isset( $parent_product['id'] ) ? (int) $parent_product['id'] : 0,
				'name'      => isset( $parent_product['name'] ) ? (string) $parent_product['name'] : '',
				'sku'       => isset( $parent_product['sku'] ) ? (string) $parent_product['sku'] : '',
				'permalink' => isset( $parent_product['permalink'] ) ? (string) $parent_product['permalink'] : '',
			);
		}

		$metadata = array(
			'dimensions' => isset( $variation_data['dimensions'] ) ? $variation_data['dimensions'] : null,
			'weight'     => isset( $variation_data['dimensions']['weight'] ) ? $variation_data['dimensions']['weight'] : null,
		);

		return new CatalogItem(
			(int) $variation_data['id'],
			'variation',
			(int) ( $variation_data['parent_id'] ?? 0 ),
			(string) ( $variation_data['name'] ?? '' ),
			(string) ( $variation_data['sku'] ?? '' ),
			(string) ( $variation_data['price'] ?? '' ),
			(string) ( $variation_data['regular_price'] ?? '' ),
			(string) ( $variation_data['sale_price'] ?? '' ),
			'',
			'',
			$image,
			array(),
			array(),
			array(),
			is_array( $variation_data['attributes'] ) ? $variation_data['attributes'] : array(),
			(string) ( $variation_data['stock_status'] ?? 'instock' ),
			is_numeric( $variation_data['stock_quantity'] ?? null ) ? (int) $variation_data['stock_quantity'] : null,
			'',
			$parent_context,
			null,
			$metadata
		);
	}

	/**
	 * Create a CatalogItem from product data array (for already-normalized data).
	 *
	 * @param array<string, mixed> $data              Product data array.
	 * @param array<string, mixed> $extra_metadata    Extra metadata.
	 *
	 * @return CatalogItem
	 */
	public function from_product_array( array $data, array $extra_metadata = array() ): CatalogItem {
		$props = array_merge(
			array(
				'id'              => 0,
				'name'            => '',
				'sku'             => '',
				'price'           => '',
				'regular_price'   => '',
				'sale_price'      => '',
				'description'     => '',
				'short_description' => '',
				'image_id'        => 0,
				'gallery'         => array(),
				'categories'      => array(),
				'tags'            => array(),
				'stock_status'    => 'instock',
				'stock_quantity'  => null,
				'permalink'       => '',
				'is_variable'     => false,
				'dimensions'      => null,
				'weight'          => null,
			),
			$data
		);

		$image = null;
		if ( isset( $props['image_id'] ) && $props['image_id'] > 0 ) {
			$image = $this->resolve_image( (int) $props['image_id'] );
		}

		$metadata = array_merge(
			array(
				'has_variations' => isset( $props['is_variable'] ) ? $props['is_variable'] : false,
				'dimensions'     => isset( $props['dimensions'] ) ? $props['dimensions'] : null,
				'weight'         => isset( $props['weight'] ) ? $props['weight'] : null,
			),
			$extra_metadata
		);

		return new CatalogItem(
			(int) $props['id'],
			'product',
			0,
			(string) $props['name'],
			(string) $props['sku'],
			(string) $props['price'],
			(string) $props['regular_price'],
			(string) $props['sale_price'],
			(string) $props['description'],
			(string) $props['short_description'],
			$image,
			is_array( $props['gallery'] ) ? array_map( 'absint', $props['gallery'] ) : array(),
			is_array( $props['categories'] ) ? array_map( 'absint', $props['categories'] ) : array(),
			is_array( $props['tags'] ) ? array_map( 'absint', $props['tags'] ) : array(),
			array(),
			(string) $props['stock_status'],
			is_numeric( $props['stock_quantity'] ) ? (int) $props['stock_quantity'] : null,
			(string) $props['permalink'],
			null,
			null,
			$metadata
		);
	}

	/**
	 * Clear parent product cache.
	 *
	 * @return void
	 */
	public function clear_cache(): void {
		$this->parent_cache = array();
		$this->image_cache  = array();
	}

	/**
	 * Extract product properties from a \WC_Product object or array.
	 *
	 * @param object|array<string, mixed> $data Product data.
	 *
	 * @return array<string, mixed>
	 */
	private function extract_product_properties( $data ): array {
		if ( is_object( $data ) && method_exists( $data, 'get_id' ) ) {
			return $this->extract_from_wc_product( $data );
		}

		if ( is_array( $data ) ) {
			return $this->extract_from_array( $data );
		}

		return array();
	}

	/**
	 * Extract properties from a \WC_Product object.
	 *
	 * @param object $product Product object.
	 *
	 * @return array<string, mixed>
	 */
	private function extract_from_wc_product( $product ): array {
		return array(
			'id'               => $product->get_id(),
			'name'             => $product->get_name(),
			'sku'              => $product->get_sku(),
			'price'            => $product->get_price(),
			'regular_price'    => $product->get_regular_price(),
			'sale_price'       => $product->get_sale_price(),
			'description'      => $product->get_description(),
			'short_description' => $product->get_short_description(),
			'image_id'         => $product->get_image_id(),
			'gallery'          => $product->get_gallery_image_ids(),
			'categories'       => $product->get_category_ids(),
			'tags'             => $product->get_tag_ids(),
			'stock_status'     => $product->get_stock_status(),
			'stock_quantity'   => $product->get_stock_quantity(),
			'permalink'        => $product->get_permalink(),
			'is_variable'      => 'variable' === $product->get_type(),
			'dimensions'       => array(
				'length' => $product->get_length(),
				'width'  => $product->get_width(),
				'height' => $product->get_height(),
			),
			'weight'           => $product->get_weight(),
		);
	}

	/**
	 * Extract properties from an array.
	 *
	 * @param array<string, mixed> $data Product data array.
	 *
	 * @return array<string, mixed>
	 */
	private function extract_from_array( array $data ): array {
		$defaults = array(
			'id'                => 0,
			'name'              => '',
			'sku'               => '',
			'price'             => '',
			'regular_price'     => '',
			'sale_price'        => '',
			'description'       => '',
			'short_description' => '',
			'image_id'          => 0,
			'gallery'           => array(),
			'categories'        => array(),
			'tags'              => array(),
			'stock_status'      => 'instock',
			'stock_quantity'    => null,
			'permalink'         => '',
			'is_variable'       => false,
			'dimensions'        => null,
			'weight'            => null,
		);

		return array_merge( $defaults, $data );
	}

	/**
	 * Resolve image URL from attachment ID.
	 *
	 * @param int $attachment_id Attachment ID.
	 *
	 * @return array{id: int, src: string, width: int, height: int}|null
	 */
	private function resolve_image( int $attachment_id ): ?array {
		if ( isset( $this->image_cache[ $attachment_id ] ) ) {
			return $this->image_cache[ $attachment_id ];
		}

		if ( ! function_exists( 'wp_get_attachment_image_src' ) ) {
			$this->image_cache[ $attachment_id ] = null;
			return null;
		}

		$image_src = wp_get_attachment_image_src( $attachment_id, 'thumbnail' );

		if ( ! $image_src ) {
			$this->image_cache[ $attachment_id ] = null;
			return null;
		}

		$result = array(
			'id'     => $attachment_id,
			'src'    => $image_src[0],
			'width'  => $image_src[1],
			'height' => $image_src[2],
		);

		$this->image_cache[ $attachment_id ] = $result;
		return $result;
	}

	/**
	 * Get parent product context for a given product ID.
	 *
	 * @param int $product_id Parent product ID.
	 *
	 * @return array<string, mixed>|null
	 */
	public function get_parent_context( int $product_id ): ?array {
		if ( isset( $this->parent_cache[ $product_id ] ) ) {
			return $this->parent_cache[ $product_id ];
		}

		$parent = $this->product_repository->find( $product_id );

		if ( ! $parent ) {
			$this->parent_cache[ $product_id ] = null;
			return null;
		}

		$context = array(
			'id'        => $parent['id'] ?? 0,
			'name'      => $parent['name'] ?? '',
			'sku'       => $parent['sku'] ?? '',
			'permalink' => '',
		);

		$this->parent_cache[ $product_id ] = $context;
		return $context;
	}
}
