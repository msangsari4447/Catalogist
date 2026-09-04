<?php

declare(strict_types=1);

namespace Catalogist;

final class Plugin {

	public static function boot(): void {
		add_action( 'init', array( CatalogPostType::class, 'register' ) );
		add_action( 'admin_init', array( Admin::class, 'boot' ) );
		add_action( 'wp_ajax_catalogist_search_products', array( Admin::class, 'ajax_search_products' ) );
	}
}
