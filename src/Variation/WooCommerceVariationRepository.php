<?php
/**
 * WooCommerce-backed variation repository.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Variation;

defined( 'ABSPATH' ) || exit;

/**
 * Variation repository using WooCommerce APIs.
 */
final class WooCommerceVariationRepository implements VariationRepositoryInterface {

	/**
	 * Get variations for a variable product.
	 *
	 * @param int                      $product_id Parent product ID.
	 * @param VariationQueryArgs       $args       Variation query arguments.
	 *
	 * @return VariationQueryResult
	 */
	public function get_variations( int $product_id, VariationQueryArgs $args ): VariationQueryResult {
		if ( ! $this->is_woocommerce_active() ) {
			return new VariationQueryResult( $product_id, array(), 0, $args->get_mode() );
		}

		$product = wc_get_product( $product_id );

		if ( ! $product || ! $this->is_variable( $product ) ) {
			return new VariationQueryResult( $product_id, array(), 0, $args->get_mode() );
		}

		$variation_ids = $this->get_variation_ids( $product_id );
		$variations    = array();

		foreach ( $variation_ids as $variation_id ) {
			// Skip excluded variations.
			if ( in_array( $variation_id, $args->get_exclude_variation_ids(), true ) ) {
				continue;
			}

			$variation = wc_get_product( $variation_id );
			if ( ! $variation ) {
				continue;
			}

			$variation_data = $this->extract_variation_data( $product, $variation );
			$variations[ $variation_id ] = $variation_data;
		}

		// Apply selected filter for selected/multiple modes.
		$selected_ids = $args->get_selected_variation_ids();
		if ( ! empty( $selected_ids ) ) {
			$filtered = array();
			foreach ( $variations as $id => $data ) {
				if ( in_array( $id, $selected_ids, true ) ) {
					$filtered[ $id ] = $data;
				}
			}
			$variations = $filtered;
		}

		return new VariationQueryResult( $product_id, $variations, count( $variations ), $args->get_mode() );
	}

	/**
	 * Find a single variation by ID.
	 *
	 * @param int $variation_id Variation ID.
	 *
	 * @return array<string, mixed>|null
	 */
	public function find( int $variation_id ): ?array {
		if ( ! $this->is_woocommerce_active() ) {
			return null;
		}

		$variation = wc_get_product( $variation_id );

		if ( ! $variation ) {
			return null;
		}

		$product = wc_get_product( $variation->get_parent_id() );

		return $this->extract_variation_data( $product, $variation );
	}

	/**
	 * Check if a variation exists.
	 *
	 * @param int $variation_id Variation ID.
	 *
	 * @return bool
	 */
	public function exists( int $variation_id ): bool {
		return null !== $this->find( $variation_id );
	}

	/**
	 * Get all variation IDs for a variable product.
	 *
	 * @param int $product_id Parent product ID.
	 *
	 * @return array<int>
	 */
	public function get_variation_ids( int $product_id ): array {
		if ( ! $this->is_woocommerce_active() ) {
			return array();
		}

		$product = wc_get_product( $product_id );

		if ( ! $product || ! $this->is_variable( $product ) ) {
			return array();
		}

		$ids = $product->get_children();

		return array_map( 'absint', array_filter( $ids ) );
	}

	/**
	 * Check if a product is a variable product.
	 *
	 * @param int $product_id Product ID.
	 *
	 * @return bool
	 */
	public function is_variable_product( int $product_id ): bool {
		if ( ! $this->is_woocommerce_active() ) {
			return false;
		}

		$product = wc_get_product( $product_id );

		return $product && $this->is_variable( $product );
	}

	/**
	 * Check if WooCommerce is active.
	 *
	 * @return bool
	 */
	private function is_woocommerce_active(): bool {
		return function_exists( 'wc_get_product' ) && class_exists( 'WC_Product' );
	}

	/**
	 * Check if a product is variable.
	 *
	 * @param object $product Product object.
	 *
	 * @return bool
	 */
	private function is_variable( $product ): bool {
		return 'variable' === $product->get_type();
	}

	/**
	 * Extract variation data as a plain array.
	 *
	 * @param object      $product    Parent product.
	 * @param object      $variation  Variation product.
	 *
	 * @return array<string, mixed>
	 */
	private function extract_variation_data( $product, $variation ): array {
		$data = array(
			'id'          => $variation->get_id(),
			'parent_id'   => $variation->get_parent_id(),
			'type'        => $variation->get_type(),
			'status'      => $variation->get_status(),
			'name'        => $variation->get_name(),
			'sku'         => $variation->get_sku(),
			'price'       => $variation->get_price(),
			'regular_price' => $variation->get_regular_price(),
			'sale_price'  => $variation->get_sale_price(),
			'stock_status' => $variation->get_stock_status(),
			'stock_quantity' => $variation->get_stock_quantity(),
			'purchasable' => $variation->is_purchasable(),
			'visible'     => $variation->is_visible(),
			'attributes'  => $this->get_variation_attributes( $variation ),
			'image'       => $this->get_variation_image( $variation ),
			'dimensions'  => array(
				'length' => $variation->get_length(),
				'width'  => $variation->get_width(),
				'height' => $variation->get_height(),
				'weight' => $variation->get_weight(),
			),
		);

		return $data;
	}

	/**
	 * Get variation attributes.
	 *
	 * @param object $variation Variation product.
	 *
	 * @return array<string, string>
	 */
	private function get_variation_attributes( $variation ): array {
		$attributes = $variation->get_variation_attributes();

		if ( ! is_array( $attributes ) ) {
			return array();
		}

		$result = array();
		foreach ( $attributes as $key => $value ) {
			// Convert attribute key to human-readable name.
			$name = str_replace( 'attribute_', '', $key );
			$result[ $name ] = $value;
		}

		return $result;
	}

	/**
	 * Get variation image data.
	 *
	 * @param object $variation Variation product.
	 *
	 * @return array<string, mixed>|null
	 */
	private function get_variation_image( $variation ): ?array {
		$image_id = $variation->get_image_id();

		if ( ! $image_id ) {
			return null;
		}

		$image_src = wp_get_attachment_image_src( $image_id, 'thumbnail' );

		if ( ! $image_src ) {
			return null;
		}

		return array(
			'id'    => $image_id,
			'src'   => $image_src[0],
			'width' => $image_src[1],
			'height' => $image_src[2],
		);
	}
}
