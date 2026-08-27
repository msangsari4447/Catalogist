<?php
/**
 * Catalog repository implementation.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Catalog;

use WP_Query;
use WP_Error;

/**
 * Handles catalog persistence using WordPress post storage.
 */
final class CatalogRepository implements CatalogRepositoryInterface {

	/**
	 * Find a catalog by ID.
	 *
	 * @param int $id Catalog ID.
	 *
	 * @return Catalog|null
	 */
	public function find( int $id ): ?Catalog {
		$post = get_post( $id );

		if ( ! $post || CatalogPostType::POST_TYPE !== $post->post_type ) {
			return null;
		}

		$factory = new CatalogFactory();

		return $factory->from_post( $post );
	}

	/**
	 * Find all catalogs matching criteria.
	 *
	 * @param array<string, mixed> $args Query arguments.
	 *
	 * @return array<Catalog>
	 */
	public function find_by( array $args = array() ): array {
		$defaults = array(
			'post_type'      => CatalogPostType::POST_TYPE,
			'posts_per_page' => -1,
			'post_status'    => 'any',
		);

		$query_args = wp_parse_args( $args, $defaults );

		$query = new WP_Query( $query_args );

		if ( ! $query->have_posts() ) {
			return array();
		}

		$catalogs   = array();
		$factory    = new CatalogFactory();

		foreach ( $query->posts as $post ) {
			$catalogs[] = $factory->from_post( $post );
		}

		return $catalogs;
	}

	/**
	 * Save a catalog.
	 *
	 * @param Catalog $catalog Catalog to save.
	 *
	 * @return int|\WP_Error Catalog ID or error.
	 */
	public function save( Catalog $catalog ) {
		$post_data = array(
			'post_title'   => $catalog->get_title(),
			'post_name'    => $catalog->get_slug(),
			'post_status'  => $catalog->get_status() ?: 'draft',
			'post_type'    => CatalogPostType::POST_TYPE,
		);

		$id = $catalog->get_id();

		if ( $id > 0 ) {
			$post_data['ID'] = $id;
			$result = wp_update_post( $post_data, true );
		} else {
			$result = wp_insert_post( $post_data, true );
		}

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$saved_id = (int) $result;

		$this->save_meta( $saved_id, $catalog );

		return $saved_id;
	}

	/**
	 * Delete a catalog.
	 *
	 * @param int $id Catalog ID.
	 *
	 * @return bool
	 */
	public function delete( int $id ): bool {
		$result = wp_delete_post( $id, true );

		return null !== $result && ! is_wp_error( $result );
	}

	/**
	 * Save catalog meta data.
	 *
	 * @param int     $id      Catalog post ID.
	 * @param Catalog $catalog Catalog instance.
	 *
	 * @return void
	 */
	private function save_meta( int $id, Catalog $catalog ): void {
		update_post_meta( $id, '_catalogist_product_query', $catalog->get_product_query() );
		update_post_meta( $id, '_catalogist_filters', $catalog->get_filters() );
		update_post_meta( $id, '_catalogist_selected_products', $catalog->get_selected_products() );
		update_post_meta( $id, '_catalogist_template_id', $catalog->get_template_id() );
		update_post_meta( $id, '_catalogist_layout_settings', $catalog->get_layout_settings() );
		update_post_meta( $id, '_catalogist_print_settings', $catalog->get_print_settings() );
		update_post_meta( $id, '_catalogist_output_settings', $catalog->get_output_settings() );
	}
}
