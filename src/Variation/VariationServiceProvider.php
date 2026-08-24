<?php
/**
 * Variation service provider.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Variation;

defined( 'ABSPATH' ) || exit;

use Catalogist\Core\Container;
use Catalogist\Core\ServiceProviderInterface;

/**
 * Registers variation services.
 */
final class VariationServiceProvider implements ServiceProviderInterface {

	/**
	 * Register services in the container.
	 *
	 * @param Container $container Service container.
	 *
	 * @return void
	 */
	public function register( Container $container ): void {
		$container->set(
			VariationRepositoryInterface::class,
			static function (): VariationRepositoryInterface {
				return new WooCommerceVariationRepository();
			}
		);

		$container->factory(
			VariationService::class,
			static function ( Container $container ): VariationService {
				$repo = $container->get( VariationRepositoryInterface::class );
				return new VariationService( $repo );
			}
		);

		$container->set( VariationMode::class, new VariationMode( VariationMode::PARENT ) );
	}

	/**
	 * Boot the service provider.
	 *
	 * @param Container $container Service container.
	 *
	 * @return void
	 */
	public function boot( Container $container ): void {
		// No hooks to register at boot time for variation expansion.
	}
}
