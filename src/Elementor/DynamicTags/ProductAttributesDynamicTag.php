<?php
/**
 * Product attributes dynamic tag.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Elementor\DynamicTags;

defined( 'ABSPATH' ) || exit;

/**
 * Product attributes dynamic tag.
 */
class ProductAttributesDynamicTag extends ProductDynamicTagBase {

	/**
	 * Get the tag ID.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'catalogist_product_attributes';
	}

	/**
	 * Get the tag title.
	 *
	 * @return string
	 */
	public function get_title(): string {
		return __( 'Product Attributes', 'catalogist' );
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

		if ( ! $item || empty( $item->get_attributes() ) ) {
			return '';
		}

		$attributes = $item->get_attributes();
		$parts      = array();

		foreach ( $attributes as $name => $value ) {
			$parts[] = sprintf( '%s: %s', esc_html( $name ), esc_html( $value ) );
		}

		return implode( '<br>', $parts );
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
