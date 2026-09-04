<?php

declare(strict_types=1);

namespace Catalogist;

final class CatalogPostType {

	public const POST_TYPE = 'ctlg_catalog';

	public static function register(): void {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'            => array(
					'name'               => __( 'Catalogs', 'catalogist' ),
					'singular_name'      => __( 'Catalog', 'catalogist' ),
					'add_new'            => __( 'Add New', 'catalogist' ),
					'add_new_item'       => __( 'Add New Catalog', 'catalogist' ),
					'edit_item'          => __( 'Edit Catalog', 'catalogist' ),
					'new_item'           => __( 'New Catalog', 'catalogist' ),
					'view_item'          => __( 'View Catalog', 'catalogist' ),
					'search_items'       => __( 'Search Catalogs', 'catalogist' ),
					'not_found'          => __( 'No catalogs found.', 'catalogist' ),
					'not_found_in_trash' => __( 'No catalogs found in Trash.', 'catalogist' ),
					'all_items'          => __( 'All Catalogs', 'catalogist' ),
					'menu_name'          => __( 'Catalogs', 'catalogist' ),
					'name_admin_bar'     => __( 'Catalog', 'catalogist' ),
				),
				'public'            => false,
				'show_ui'           => true,
				'show_in_menu'      => true,
				'show_in_rest'      => false,
				'has_archive'       => false,
				'rewrite'           => false,
				'capability_type'   => 'post',
				'map_meta_cap'      => true,
				'supports'          => array( 'title' ),
				'menu_position'     => 16,
				'show_in_admin_bar' => true,
			)
		);
	}
}
