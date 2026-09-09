<?php

declare(strict_types=1);

namespace Catalogist;

final class TemplatePostType {

	public const POST_TYPE = 'ctlg_template';

	public static function register(): void {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'            => array(
					'name'               => __( 'Templates', 'catalogist' ),
					'singular_name'      => __( 'Template', 'catalogist' ),
					'add_new'            => __( 'Add New', 'catalogist' ),
					'add_new_item'       => __( 'Add New Template', 'catalogist' ),
					'edit_item'          => __( 'Edit Template', 'catalogist' ),
					'new_item'           => __( 'New Template', 'catalogist' ),
					'view_item'          => __( 'View Template', 'catalogist' ),
					'search_items'       => __( 'Search Templates', 'catalogist' ),
					'not_found'          => __( 'No templates found.', 'catalogist' ),
					'not_found_in_trash' => __( 'No templates found in Trash.', 'catalogist' ),
					'all_items'          => __( 'All Templates', 'catalogist' ),
					'menu_name'          => __( 'Templates', 'catalogist' ),
					'name_admin_bar'     => __( 'Template', 'catalogist' ),
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
				'menu_position'     => 17,
				'show_in_admin_bar' => true,
			)
		);
	}
}
