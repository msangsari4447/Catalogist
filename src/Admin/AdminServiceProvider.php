<?php
/**
 * Admin service provider.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Admin;

use Catalogist\Core\Container;
use Catalogist\Core\ServiceProviderInterface;
use Catalogist\Core\I18n;
use Catalogist\Core\Assets;

/**
 * Registers admin services.
 */
final class AdminServiceProvider implements ServiceProviderInterface {

	/**
	 * Register services in the container.
	 *
	 * @param Container $container Service container.
	 *
	 * @return void
	 */
	public function register( Container $container ): void {
		$container->set(
			I18n::class,
			static function () use ( $container ): I18n {
				return new I18n(
					$container->get( 'plugin.text_domain' ),
					'languages'
				);
			}
		);

		$container->set(
			Assets::class,
			static function () use ( $container ): Assets {
				return new Assets(
					$container->get( 'plugin.version' ),
					$container->get( 'plugin.file' )
				);
			}
		);

		$container->set( Menu::class, new Menu() );
		$container->set( Notices::class, new Notices() );
		$container->set( SettingsPage::class, new SettingsPage() );
		$container->set( Assets\AdminAssets::class, new Assets\AdminAssets() );
	}

	/**
	 * Boot the service provider.
	 *
	 * @param Container $container Service container.
	 *
	 * @return void
	 */
	public function boot( Container $container ): void {
		$i18n = $container->get( I18n::class );

		if ( $i18n instanceof I18n ) {
			$i18n->register_hooks();
		}

		$menu = $container->get( Menu::class );

		if ( $menu instanceof Menu ) {
			$menu->register_hooks();
		}

		$notices = $container->get( Notices::class );

		if ( $notices instanceof Notices ) {
			$notices->register_hooks();
		}

		$settings = $container->get( SettingsPage::class );

		if ( $settings instanceof SettingsPage ) {
			$settings->register_hooks();
		}

		$assets = $container->get( Assets\AdminAssets::class );

		if ( $assets instanceof Assets\AdminAssets ) {
			$assets->register_hooks();
		}
	}
}
