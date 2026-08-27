<?php
/**
 * Output service provider.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Output;

use Catalogist\Core\Container;
use Catalogist\Core\ServiceProviderInterface;
use Catalogist\Template\TemplateEngineInterface;
use Catalogist\Print\PrintEngineInterface;

/**
 * Registers output engine services in the container.
 */
final class OutputServiceProvider implements ServiceProviderInterface {

	/**
	 * Register services in the container.
	 *
	 * @param Container $container Service container.
	 *
	 * @return void
	 */
	public function register( Container $container ): void {
		$container->factory(
			OutputEngineInterface::class,
			static function ( Container $c ): OutputEngine {
				$template_engine = $c->get( TemplateEngineInterface::class );
				$print_engine    = $c->get( PrintEngineInterface::class );
				return new OutputEngine( $template_engine, $print_engine );
			}
		);

		$container->factory(
			OutputEngine::class,
			static function ( Container $c ): OutputEngine {
				return $c->get( OutputEngineInterface::class );
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