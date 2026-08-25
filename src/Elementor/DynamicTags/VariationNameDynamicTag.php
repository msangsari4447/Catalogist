<?php
/**
 * Variation name dynamic tag.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Elementor\DynamicTags;

defined( 'ABSPATH' ) || exit;

/**
 * Variation name dynamic tag.
 */
class VariationNameDynamicTag extends VariationDynamicTagBase {

	/**
	 * Get the tag ID.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'catalogist_variation_name';
	}

	/**
	 * Get the tag title.
	 *
	 * @return string
	 */
	public function get_title(): string {
		return __( 'Variation Name', 'catalogist' );
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

		return $item->get_title();
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
