<?php

declare(strict_types=1);

namespace Catalogist;

final class Plugin {

	public static function boot(): void {
		add_action( 'init', array( CatalogPostType::class, 'register' ) );
	}
}
