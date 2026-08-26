<?php
/**
 * Print service provider.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Print;

use Catalogist\Core\Container;
use Catalogist\Core\ServiceProviderInterface;
use Catalogist\Template\TemplateEngineInterface;

/**
 * Registers print engine services in the container.
 */
final class PrintServiceProvider implements ServiceProviderInterface {

	/**
	 * Plugin directory path (with trailing slash).
	 *
	 * @var string
	 */
	private string $pluginDirectory;

	/**
	 * Constructor.
	 *
	 * @param string $pluginDirectory Plugin directory path.
	 */
	public function __construct( string $pluginDirectory ) {
		$this->pluginDirectory = $pluginDirectory;
	}

	/**
	 * Register services in the container.
	 *
	 * @param Container $container Service container.
	 *
	 * @return void
	 */
	public function register( Container $container ): void {
		// Print engine (depends on TemplateEngine).
		$container->factory(
			PrintEngineInterface::class,
			static function ( Container $c ): PrintEngine {
				$template_engine = $c->get( TemplateEngineInterface::class );
				$plugin_dir = untrailingslashit( $c->get( 'plugin.file' ) );

				return new PrintEngine(
					$template_engine,
					'assets/css/print.css'
				);
			}
		);

		// Register concrete class as well.
		$container->factory(
			PrintEngine::class,
			static function ( Container $c ): PrintEngine {
				return $c->get( PrintEngineInterface::class );
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
