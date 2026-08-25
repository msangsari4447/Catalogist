<?php
/**
 * Product categories dynamic tag.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Elementor\DynamicTags;

defined( 'ABSPATH' ) || exit;

/**
 * Product categories dynamic tag.
 */
class ProductCategoriesDynamicTag extends ProductDynamicTagBase {

	/**
	 * Get the tag ID.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'catalogist_product_categories';
	}

	/**
	 * Get the tag title.
	 *
	 * @return string
	 */
	public function get_title(): string {
		return __( 'Product Categories', 'catalogist' );
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

		if ( ! $item || empty( $item->get_categories() ) ) {
			return '';
		}

		$category_ids = $item->get_categories();

		// Convert category IDs to names.
		$category_names = array();
		foreach ( $category_ids as $cat_id ) {
			$category = get_term( $cat_id, 'product_cat' );
			if ( $category && ! is_wp_error( $category ) ) {
				$category_names[] = $category->name;
			}
		}

		return implode( ', ', $category_names );
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
