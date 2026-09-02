<?php

declare(strict_types=1);

namespace Catalogist;

final class CatalogPostType {

	public const POST_TYPE = 'ctlg_catalog';

	public static function register(): void {
		register_post_type(
			self::POST_TYPE,
			array(
				'public'            => false,
				'show_ui'           => true,
				'show_in_menu'      => true,
				'show_in_rest'      => false,
				'has_archive'       => false,
				'rewrite'           => false,
				'capability_type'   => 'post',
				'map_meta_cap'      => true,
				'supports'          => array( 'title' ),
				'menu_position'     => 20,
				'show_in_admin_bar' => true,
			)
		);
	}
}
