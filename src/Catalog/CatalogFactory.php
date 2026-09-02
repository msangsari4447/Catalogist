<?php
/**
 * Factory for creating Catalog instances.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Catalog;

use WP_Post;

/**
 * Creates Catalog entities from various sources.
 */
final class CatalogFactory {

	/**
	 * Create a Catalog from a WP_Post object.
	 *
	 * @param WP_Post $post WordPress post object.
	 *
	 * @return Catalog
	 */
	public function from_post( WP_Post $post ): Catalog {
		$catalog = new Catalog();

		$catalog->set_id( $post->ID );
		$catalog->set_title( $post->post_title );
		$catalog->set_slug( $post->post_name );
		$catalog->set_status( $post->post_status );
		$catalog->set_created_at( $post->post_date );
		$catalog->set_updated_at( $post->post_modified );

		$this->load_meta( $catalog );

		return $catalog;
	}

	/**
	 * Create a Catalog from an array of data.
	 *
	 * @param array<string, mixed> $data Catalog data.
	 *
	 * @return Catalog
	 */
	public function from_array( array $data ): Catalog {
		$catalog = new Catalog();

		if ( isset( $data['id'] ) ) {
			$catalog->set_id( (int) $data['id'] );
		}

		if ( isset( $data['title'] ) ) {
			$catalog->set_title( (string) $data['title'] );
		}

		if ( isset( $data['slug'] ) ) {
			$catalog->set_slug( (string) $data['slug'] );
		}

		if ( isset( $data['status'] ) ) {
			$catalog->set_status( (string) $data['status'] );
		}

		if ( isset( $data['product_query'] ) && is_array( $data['product_query'] ) ) {
			$catalog->set_product_query( $data['product_query'] );
		}

		if ( isset( $data['filters'] ) && is_array( $data['filters'] ) ) {
			$catalog->set_filters( $data['filters'] );
		}

		if ( isset( $data['selected_products'] ) && is_array( $data['selected_products'] ) ) {
			$catalog->set_selected_products( $data['selected_products'] );
		}

		if ( isset( $data['template_id'] ) ) {
			$catalog->set_template_id( (int) $data['template_id'] );
		}

		if ( isset( $data['layout_settings'] ) && is_array( $data['layout_settings'] ) ) {
			$catalog->set_layout_settings( $data['layout_settings'] );
		}

		if ( isset( $data['print_settings'] ) && is_array( $data['print_settings'] ) ) {
			$catalog->set_print_settings( $data['print_settings'] );
		}

		if ( isset( $data['created_at'] ) ) {
			$catalog->set_created_at( (string) $data['created_at'] );
		}

		if ( isset( $data['updated_at'] ) ) {
			$catalog->set_updated_at( (string) $data['updated_at'] );
		}

		return $catalog;
	}

	/**
	 * Load post meta into a Catalog.
	 *
	 * @param Catalog $catalog Catalog instance.
	 *
	 * @return void
	 */
	private function load_meta( Catalog $catalog ): void {
		$id = $catalog->get_id();

		// Batch load all catalog meta in a single query to avoid N+1 meta queries.
		$meta = get_post_meta( $id );

		$product_query = isset( $meta['_catalogist_product_query'][0] ) ? $meta['_catalogist_product_query'][0] : array();
		if ( is_array( $product_query ) ) {
			$catalog->set_product_query( $product_query );
		}

		$filters = isset( $meta['_catalogist_filters'][0] ) ? $meta['_catalogist_filters'][0] : array();
		if ( is_array( $filters ) ) {
			$catalog->set_filters( $filters );
		}

		$selected_products = isset( $meta['_catalogist_selected_products'][0] ) ? $meta['_catalogist_selected_products'][0] : array();
		if ( is_array( $selected_products ) ) {
			$catalog->set_selected_products( $selected_products );
		}

		$template_id = isset( $meta['_catalogist_template_id'][0] ) ? $meta['_catalogist_template_id'][0] : '';
		if ( $template_id ) {
			$catalog->set_template_id( (int) $template_id );
		}

		$layout_settings = isset( $meta['_catalogist_layout_settings'][0] ) ? $meta['_catalogist_layout_settings'][0] : array();
		if ( is_array( $layout_settings ) ) {
			$catalog->set_layout_settings( $layout_settings );
		}

		$print_settings = isset( $meta['_catalogist_print_settings'][0] ) ? $meta['_catalogist_print_settings'][0] : array();
		if ( is_array( $print_settings ) ) {
			$catalog->set_print_settings( $print_settings );
		}

		$output_settings = isset( $meta['_catalogist_output_settings'][0] ) ? $meta['_catalogist_output_settings'][0] : array();
		if ( is_array( $output_settings ) ) {
			$catalog->set_output_settings( $output_settings );
		}
	}
}
