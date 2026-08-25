<?php
/**
 * Base class for variation dynamic tags.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Elementor\DynamicTags;

use Catalogist\CatalogItem\CatalogItem;

defined( 'ABSPATH' ) || exit;

/**
 * Abstract base class for variation data dynamic tags.
 *
 * Provides common functionality for variation-related dynamic tags.
 */
abstract class VariationDynamicTagBase {

	/**
	 * Tag ID.
	 *
	 * @var string
	 */
	protected string $tag_id = '';

	/**
	 * Tag title.
	 *
	 * @var string
	 */
	protected string $tag_title = '';

	/**
	 * Tag group.
	 *
	 * @var array<string>
	 */
	protected array $tag_group = array( 'catalogist-variations' );

	/**
	 * Variation ID.
	 *
	 * @var int
	 */
	protected int $variation_id = 0;

	/**
	 * Parent product ID.
	 *
	 * @var int
	 */
	protected int $parent_product_id = 0;

	/**
	 * Resolved catalog item.
	 *
	 * @var CatalogItem|null
	 */
	protected ?CatalogItem $catalog_item = null;

	/**
	 * Get the tag ID.
	 *
	 * @return string
	 */
	abstract public function get_name(): string;

	/**
	 * Get the tag title.
	 *
	 * @return string
	 */
	abstract public function get_title(): string;

	/**
	 * Get the tag group.
	 *
	 * @return array<string>
	 */
	public function get_group(): array {
		return $this->tag_group;
	}

	/**
	 * Render the tag content.
	 *
	 * @return string
	 */
	abstract public function render(): string;

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

	/**
	 * Get control settings.
	 *
	 * @return array<string, mixed>
	 */
	public function get_control_settings(): array {
		return array(
			'variation_id' => array(
				'label'   => __( 'Variation ID', 'catalogist' ),
				'type'    => 'text',
				'default' => '',
			),
			'parent_product_id' => array(
				'label'   => __( 'Parent Product ID', 'catalogist' ),
				'type'    => 'text',
				'default' => '',
			),
		);
	}

	/**
	 * Resolve catalog item from variation ID.
	 *
	 * @param int $variation_id Variation ID.
	 * @param int $parent_product_id Parent product ID.
	 *
	 * @return CatalogItem|null
	 */
	protected function resolve_catalog_item( int $variation_id, int $parent_product_id = 0 ): ?CatalogItem {
		// Use global function to get catalog item by variation ID.
		return catalogist_get_catalog_item( $variation_id, $parent_product_id );
	}

	/**
	 * Get the resolved catalog item.
	 *
	 * @return CatalogItem|null
	 */
	public function get_catalog_item(): ?CatalogItem {
		return $this->catalog_item;
	}

	/**
	 * Set the catalog item.
	 *
	 * @param CatalogItem|null $item Catalog item.
	 *
	 * @return void
	 */
	public function set_catalog_item( ?CatalogItem $item ): void {
		$this->catalog_item = $item;
	}
}
