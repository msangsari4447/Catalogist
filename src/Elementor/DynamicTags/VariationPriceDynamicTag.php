<?php
/**
 * Variation price dynamic tag.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Elementor\DynamicTags;

defined( 'ABSPATH' ) || exit;

/**
 * Variation price dynamic tag.
 */
class VariationPriceDynamicTag extends VariationDynamicTagBase {

	/**
	 * Get the tag ID.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'catalogist_variation_price';
	}

	/**
	 * Get the tag title.
	 *
	 * @return string
	 */
	public function get_title(): string {
		return __( 'Variation Price', 'catalogist' );
	}

	/**
	 * Render the tag content.
	 *
	 * @return string
	 */
	public function render(): string {
		$variation_id = (int) $this->get_settings( 'variation_id' );
		$parent_id    = (int) $this->get_settings( 'parent_product_id' );

		if ( ! $variation_id ) {
			return '';
		}

		$item = $this->resolve_catalog_item( $variation_id, $parent_id );

		if ( ! $item ) {
			return '';
		}

		// Format price using wc_price if WooCommerce is active.
		if ( function_exists( 'wc_price' ) ) {
			return wc_price( $item->get_price() );
		}

		return $item->get_price();
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
