<?php
/**
 * Catalog custom post type registration.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Catalog;

use Catalogist\Core\HookableInterface;

/**
 * Registers the ctlg_catalog custom post type.
 */
final class CatalogPostType implements HookableInterface {

	/**
	 * Post type slug.
	 *
	 * @var string
	 */
	public const POST_TYPE = 'ctlg_catalog';

	/**
	 * Register hooks with WordPress.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'init', array( $this, 'register_meta' ) );
	}

	/**
	 * Register the catalog post type.
	 *
	 * @return void
	 */
	public function register_post_type(): void {
		$labels = array(
			'name'               => __( 'Catalogs', 'catalogist' ),
			'singular_name'      => __( 'Catalog', 'catalogist' ),
			'add_new'            => __( 'Add New', 'catalogist' ),
			'add_new_item'       => __( 'Add New Catalog', 'catalogist' ),
			'edit_item'          => __( 'Edit Catalog', 'catalogist' ),
			'new_item'           => __( 'New Catalog', 'catalogist' ),
			'view_item'          => __( 'View Catalog', 'catalogist' ),
			'search_items'       => __( 'Search Catalogs', 'catalogist' ),
			'not_found'          => __( 'No catalogs found.', 'catalogist' ),
			'not_found_in_trash' => __( 'No catalogs found in trash.', 'catalogist' ),
			'menu_name'          => __( 'Catalogs', 'catalogist' ),
		);

		$args = array(
			'labels'              => $labels,
			'public'              => true,
			'publicly_queryable'  => true,
			'show_ui'             => true,
			'show_in_menu'        => false,
			'query_var'           => true,
			'rewrite'             => array(
				'slug' => $this->get_slug(),
			),
			'capability_type'     => 'post',
			'has_archive'         => true,
			'hierarchical'        => false,
			'menu_position'       => null,
			'supports'            => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt' ),
			'show_in_rest'        => true,
		);

		register_post_type( self::POST_TYPE, $args );
	}

	/**
	 * Register post meta for the catalog post type.
	 *
	 * @return void
	 */
	public function register_meta(): void {
		$meta_fields = $this->get_meta_fields();

		foreach ( $meta_fields as $key => $args ) {
			register_post_meta( self::POST_TYPE, $key, $args );
		}
	}

	/**
	 * Get meta field definitions.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private function get_meta_fields(): array {
		return array(
			'_catalogist_product_query'    => array(
				'type'              => 'object',
				'single'            => true,
				'show_in_rest'      => array(
					'schema' => array(
						'type'       => 'object',
						'properties' => array(),
					),
				),
				'auth_callback'     => array( $this, 'auth_callback' ),
				'sanitize_callback' => array( $this, 'sanitize_array' ),
			),
			'_catalogist_filters'          => array(
				'type'              => 'object',
				'single'            => true,
				'show_in_rest'      => array(
					'schema' => array(
						'type'       => 'object',
						'properties' => array(),
					),
				),
				'auth_callback'     => array( $this, 'auth_callback' ),
				'sanitize_callback' => array( $this, 'sanitize_array' ),
			),
			'_catalogist_selected_products' => array(
				'type'              => 'array',
				'single'            => true,
				'show_in_rest'      => array(
					'schema' => array(
						'type'  => 'array',
						'items' => array( 'type' => 'integer' ),
					),
				),
				'auth_callback'     => array( $this, 'auth_callback' ),
				'sanitize_callback' => array( $this, 'sanitize_int_array' ),
			),
			'_catalogist_template_id'      => array(
				'type'              => 'integer',
				'single'            => true,
				'show_in_rest'      => true,
				'auth_callback'     => array( $this, 'auth_callback' ),
				'sanitize_callback' => 'absint',
			),
			'_catalogist_layout_settings'  => array(
				'type'              => 'object',
				'single'            => true,
				'show_in_rest'      => array(
					'schema' => array(
						'type'       => 'object',
						'properties' => array(),
					),
				),
				'auth_callback'     => array( $this, 'auth_callback' ),
				'sanitize_callback' => array( $this, 'sanitize_array' ),
			),
			'_catalogist_print_settings'   => array(
				'type'              => 'object',
				'single'            => true,
				'show_in_rest'      => array(
					'schema' => array(
						'type'       => 'object',
						'properties' => array(),
					),
				),
				'auth_callback'     => array( $this, 'auth_callback' ),
				'sanitize_callback' => array( $this, 'sanitize_array' ),
			),
		);
	}

	/**
	 * Get the URL slug for the post type.
	 *
	 * @return string
	 */
	private function get_slug(): string {
		$settings = get_option( 'catalogist_settings', array() );

		return $settings['post_type_slug'] ?? 'catalogs';
	}

	/**
	 * Authorization callback for meta fields.
	 *
	 * @param bool   $allowed Whether the user is allowed.
	 * @param string $meta_key Meta key being edited.
	 * @param int    $post_id  Post ID.
	 *
	 * @return bool
	 */
	public function auth_callback( bool $allowed, string $meta_key, int $post_id ): bool {
		return current_user_can( 'edit_post', $post_id );
	}

	/**
	 * Sanitize an array value.
	 *
	 * @param mixed $value Input value.
	 *
	 * @return array<string, mixed>
	 */
	public function sanitize_array( $value ): array {
		if ( ! is_array( $value ) ) {
			return array();
		}

		return $value;
	}

	/**
	 * Sanitize an integer array.
	 *
	 * @param mixed $value Input value.
	 *
	 * @return array<int>
	 */
	public function sanitize_int_array( $value ): array {
		if ( ! is_array( $value ) ) {
			return array();
		}

		return array_map( 'absint', $value );
	}
}
