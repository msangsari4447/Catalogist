<?php
/**
 * Preview service provider.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Preview;

use Catalogist\Core\Container;
use Catalogist\Core\ServiceProviderInterface;
use Catalogist\Print\PrintEngineInterface;

/**
 * Registers preview engine services in the container.
 */
final class PreviewServiceProvider implements ServiceProviderInterface {

	/**
	 * Register services in the container.
	 *
	 * @param Container $container Service container.
	 *
	 * @return void
	 */
	public function register( Container $container ): void {
		// Preview engine (depends on PrintEngine).
		$container->factory(
			PreviewEngineInterface::class,
			static function ( Container $c ): PreviewEngine {
				$print_engine = $c->get( PrintEngineInterface::class );
				return new PreviewEngine( $print_engine );
			}
		);

		// Register concrete class as well.
		$container->factory(
			PreviewEngine::class,
			static function ( Container $c ): PreviewEngine {
				return $c->get( PreviewEngineInterface::class );
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
		// No boot-time actions needed.
	}
}
