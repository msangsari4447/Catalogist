<?php
/**
 * Catalog settings handler.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Catalog;

use Catalogist\Core\HookableInterface;

/**
 * Handles catalog-specific settings and defaults.
 */
final class CatalogSettings implements HookableInterface {

	/**
	 * Register hooks with WordPress.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_filter( 'default_content', array( $this, 'set_default_content' ), 10, 2 );
	}

	/**
	 * Set default content for new catalogs.
	 *
	 * @param string  $content Default content.
	 * @param WP_Post $post    Post object.
	 *
	 * @return string
	 */
	public function set_default_content( string $content, $post ): string {
		if ( CatalogPostType::POST_TYPE === $post->post_type ) {
			return '';
		}

		return $content;
	}

	/**
	 * Get default catalog settings.
	 *
	 * @return array<string, mixed>
	 */
	public function get_defaults(): array {
		return array(
			'product_query'     => array(),
			'filters'           => array(),
			'selected_products' => array(),
			'template_id'       => 0,
			'layout_settings'   => $this->get_default_layout_settings(),
			'print_settings'    => $this->get_default_print_settings(),
			'output_settings'   => $this->get_default_output_settings(),
		);
	}

	/**
	 * Get default layout settings.
	 *
	 * @return array<string, mixed>
	 */
	public function get_default_layout_settings(): array {
		return array(
			'columns'      => 2,
			'card_style'   => 'default',
			'show_image'   => true,
			'show_price'   => true,
			'show_sku'     => true,
			'show_excerpt' => false,
		);
	}

	/**
	 * Get default print settings.
	 *
	 * @return array<string, mixed>
	 */
	public function get_default_print_settings(): array {
		return array(
			'page_size'   => 'a4',
			'orientation' => 'portrait',
			'margins'     => array(
				'top'    => 20,
				'right'  => 20,
				'bottom' => 20,
				'left'   => 20,
			),
			'columns'     => 2,
		);
	}

	/**
	 * Get default output settings.
	 *
	 * @return array<string, mixed>
	 */
	public function get_default_output_settings(): array {
		return array(
			'default_format' => 'html',
			'filename'       => 'catalog-{title}-{date}',
		);
	}
}
