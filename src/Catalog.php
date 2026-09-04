<?php

declare(strict_types=1);

namespace Catalogist;

/**
 * Catalog data structure and helper methods.
 *
 * Represents a single catalog entity with its meta fields.
 * This is a minimal data container for Stage 1 - no complex business logic.
 */
final class Catalog {

	/**
	 * Meta key for catalog description.
	 */
	public const META_DESCRIPTION = 'ctlg_catalog_description';

	/**
	 * Meta key for catalog settings (JSON encoded array).
	 */
	public const META_SETTINGS = 'ctlg_catalog_settings';

	/**
	 * Meta key for selected product IDs (JSON encoded array).
	 */
	public const META_PRODUCTS = 'ctlg_catalog_products';

	/**
	 * Default catalog settings.
	 *
	 * @return array<string, mixed>
	 */
	public static function default_settings(): array {
		return array(
			'layout'     => 'grid',
			'columns'    => 3,
			'show_price' => true,
			'show_sku'   => false,
			'show_stock' => false,
		);
	}

	/**
	 * Get all meta keys used by Catalog.
	 *
	 * @return array<int, string>
	 */
	public static function meta_keys(): array {
		return array(
			self::META_DESCRIPTION,
			self::META_SETTINGS,
			self::META_PRODUCTS,
		);
	}

	/**
	 * Build catalog data array from post ID.
	 *
	 * @param int $post_id Catalog post ID.
	 * @return array<string, mixed> Catalog data with defaults applied.
	 */
	public static function get_data( int $post_id ): array {
		$description = get_post_meta( $post_id, self::META_DESCRIPTION, true );
		$settings    = get_post_meta( $post_id, self::META_SETTINGS, true );
		$products    = get_post_meta( $post_id, self::META_PRODUCTS, true );

		$settings_array = self::default_settings();
		if ( is_array( $settings ) ) {
			$settings_array = array_merge( $settings_array, $settings );
		}

		$products_array = array();
		if ( is_array( $products ) ) {
			$products_array = array_map( 'intval', $products );
		}

		return array(
			'id'          => $post_id,
			'title'       => get_the_title( $post_id ),
			'description' => $description ? $description : '',
			'settings'    => $settings_array,
			'products'    => $products_array,
			'created_at'  => get_the_date( 'c', $post_id ),
			'updated_at'  => get_the_modified_date( 'c', $post_id ),
		);
	}

	/**
	 * Validate and sanitize catalog input data from $_POST.
	 *
	 * @param array<string, mixed> $input Raw input data.
	 * @return array{description: string, settings: array<string, mixed>, products: array<int>} Sanitized data.
	 */
	public static function sanitize_input( array $input ): array {
		$description = isset( $input['catalog_description'] )
			? sanitize_textarea_field( wp_unslash( $input['catalog_description'] ) )
			: '';

		$settings = array();
		if ( isset( $input['catalog_settings'] ) && is_array( $input['catalog_settings'] ) ) {
			$defaults = self::default_settings();
			foreach ( $defaults as $key => $default ) {
				if ( isset( $input['catalog_settings'][ $key ] ) ) {
					$value = $input['catalog_settings'][ $key ];
					if ( is_bool( $default ) ) {
						$settings[ $key ] = (bool) $value;
					} elseif ( is_int( $default ) ) {
						$settings[ $key ] = max( 1, intval( $value ) );
					} else {
						$settings[ $key ] = sanitize_text_field( wp_unslash( $value ) );
					}
				} else {
					$settings[ $key ] = $default;
				}
			}
		} else {
			$settings = self::default_settings();
		}

		$products = array();
		if ( isset( $input['catalog_products'] ) && is_array( $input['catalog_products'] ) ) {
			foreach ( $input['catalog_products'] as $product_id ) {
				$id = intval( $product_id );
				if ( $id > 0 ) {
					$products[] = $id;
				}
			}
		}

		return array(
			'description' => $description,
			'settings'    => $settings,
			'products'    => $products,
		);
	}

	/**
	 * Save catalog meta data for a post.
	 *
	 * @param int   $post_id Catalog post ID.
	 * @param array $data    Sanitized data from sanitize_input().
	 * @return bool True on success.
	 */
	public static function save( int $post_id, array $data ): bool {
		$results = array();

		$results[] = update_post_meta( $post_id, self::META_DESCRIPTION, $data['description'] );
		$results[] = update_post_meta( $post_id, self::META_SETTINGS, $data['settings'] );
		$results[] = update_post_meta( $post_id, self::META_PRODUCTS, $data['products'] );

		return ! in_array( false, $results, true );
	}

	/**
	 * Delete all catalog meta for a post.
	 *
	 * @param int $post_id Catalog post ID.
	 * @return bool True on success.
	 */
	public static function delete_meta( int $post_id ): bool {
		$results = array();
		foreach ( self::meta_keys() as $key ) {
			$results[] = delete_post_meta( $post_id, $key );
		}
		return ! in_array( false, $results, true );
	}
}
