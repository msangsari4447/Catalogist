<?php
/**
 * Catalog service provider.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Catalog;

use Catalogist\Core\Container;
use Catalogist\Core\ServiceProviderInterface;

/**
 * Registers catalog services.
 */
final class CatalogServiceProvider implements ServiceProviderInterface {

	/**
	 * Register services in the container.
	 *
	 * @param Container $container Service container.
	 *
	 * @return void
	 */
	public function register( Container $container ): void {
		$container->set( CatalogPostType::class, new CatalogPostType() );
		$container->set( CatalogFactory::class, new CatalogFactory() );
		$container->set( CatalogRepositoryInterface::class, new CatalogRepository() );
		$container->set( CatalogSettings::class, new CatalogSettings() );
	}

	/**
	 * Boot the service provider.
	 *
	 * @param Container $container Service container.
	 *
	 * @return void
	 */
	public function boot( Container $container ): void {
		$post_type = $container->get( CatalogPostType::class );

		if ( $post_type instanceof CatalogPostType ) {
			$post_type->register_hooks();
		}

		$settings = $container->get( CatalogSettings::class );

		if ( $settings instanceof CatalogSettings ) {
			$settings->register_hooks();
		}
	}
}
