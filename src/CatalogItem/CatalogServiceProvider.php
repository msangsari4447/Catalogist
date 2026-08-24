<?php
/**
 * Catalog Item service provider.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\CatalogItem;

defined( 'ABSPATH' ) || exit;

use Catalogist\Core\Container;
use Catalogist\Core\ServiceProviderInterface;
use Catalogist\Product\ProductRepositoryInterface;
use Catalogist\Variation\VariationService;
use Catalogist\Variation\VariationServiceInterface;

/**
 * Registers catalog item services in the container.
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
		$container->factory(
			CatalogItemFactory::class,
			static function ( Container $c ): CatalogItemFactory {
				return new CatalogItemFactory(
					$c->get( ProductRepositoryInterface::class )
				);
			}
		);

		$container->set( VariationServiceInterface::class, new VariationService() );

		$container->factory(
			CatalogProcessor::class,
			static function ( Container $c ): CatalogProcessor {
				return new CatalogProcessor(
					$c->get( CatalogItemFactory::class ),
					$c->get( VariationServiceInterface::class ),
					$c->get( ProductRepositoryInterface::class )
				);
			}
		);
	}

	/**
	 * Boot the service provider.
	 *
	 * @param Container $container Service container.
	 *
	 * @return void
	 */
	public function boot( Container $container ): void {
		// No hooks to register at boot time for catalog processing.
	}
}
