<?php
/**
 * Product stock status dynamic tag.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Elementor\DynamicTags;

defined( 'ABSPATH' ) || exit;

/**
 * Product stock status dynamic tag.
 */
class ProductStockStatusDynamicTag extends ProductDynamicTagBase {

	/**
	 * Get the tag ID.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'catalogist_product_stock_status';
	}

	/**
	 * Get the tag title.
	 *
	 * @return string
	 */
	public function get_title(): string {
		return __( 'Product Stock Status', 'catalogist' );
	}

	/**
	 * Render the tag content.
	 *
	 * @return string
	 */
	public function render(): string {
		$product_id = (int) $this->get_settings( 'product_id' );

		if ( ! $product_id ) {
			return '';
		}

		$item = $this->resolve_catalog_item( $product_id );

		if ( ! $item ) {
			return '';
		}

		$status = $item->get_stock_status();

		// Map status to human-readable text.
		$status_map = array(
			'instock'     => __( 'In Stock', 'catalogist' ),
			'outofstock'  => __( 'Out of Stock', 'catalogist' ),
			'onbackorder' => __( 'On Backorder', 'catalogist' ),
		);

		return $status_map[ $status ] ?? $status;
	}

	/**
	 * Render plain content for accessibility.
	 *
	 * @param array<string, mixed> $controls_data Control data.
	 *
	 * @return string
	 */
	public function render_plain_content( array $controls_data ): string {
		return $this->render();
	}
}
