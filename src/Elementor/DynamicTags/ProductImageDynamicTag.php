<?php
/**
 * Product image dynamic tag.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Elementor\DynamicTags;

defined( 'ABSPATH' ) || exit;

/**
 * Product image dynamic tag.
 */
class ProductImageDynamicTag extends ProductDynamicTagBase {

	/**
	 * Get the tag ID.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'catalogist_product_image';
	}

	/**
	 * Get the tag title.
	 *
	 * @return string
	 */
	public function get_title(): string {
		return __( 'Product Image', 'catalogist' );
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

		if ( ! $item || ! $item->get_image() ) {
			return '';
		}

		$image = $item->get_image();

		return sprintf(
			'<img src="%s" alt="%s" width="%d" height="%d" />',
			esc_url( $image['src'] ),
			esc_attr( $item->get_title() ),
			(int) $image['width'],
			(int) $image['height']
		);
	}

	/**
	 * Render plain content for accessibility.
	 *
	 * @param array<string, mixed> $controls_data Control data.
	 *
	 * @return string
	 */
	public function render_plain_content( array $controls_data ): string {
		$product_id = (int) $this->get_settings( 'product_id' );

		if ( ! $product_id ) {
			return '';
		}

		$item = $this->resolve_catalog_item( $product_id );

		if ( ! $item || ! $item->get_image() ) {
			return '';
		}

		return $item->get_image()['src'];
	}
}
