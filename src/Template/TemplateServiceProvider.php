<?php
/**
 * Template service provider.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Template;

defined( 'ABSPATH' ) || exit;

use Catalogist\Core\Container;
use Catalogist\Core\ServiceProviderInterface;
use Catalogist\Template\Loader\FileTemplateLoader;
use Catalogist\Template\Renderer\FileTemplateRenderer;

/**
 * Registers template engine services in the container.
 */
final class TemplateServiceProvider implements ServiceProviderInterface {

	/**
	 * Plugin directory path.
	 *
	 * @var string
	 */
	private string $pluginDirectory;

	/**
	 * Constructor.
	 *
	 * @param string $pluginDirectory Plugin directory path (with trailing slash).
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
		$pluginDir = untrailingslashit( $this->pluginDirectory );

		// Template loader.
		$container->set(
			FileTemplateLoader::class,
			new FileTemplateLoader( $pluginDir )
		);

		// Template renderer (depends on loader).
		$container->factory(
			FileTemplateRenderer::class,
			static function ( Container $c ): FileTemplateRenderer {
				return new FileTemplateRenderer(
					$c->get( FileTemplateLoader::class )
				);
			}
		);

		// Context builder.
		$container->set(
			TemplateContextBuilder::class,
			new TemplateContextBuilder()
		);

		// Template engine (depends on loader, renderer, context builder).
		$container->factory(
			TemplateEngineInterface::class,
			static function ( Container $c ): TemplateEngine {
				return new TemplateEngine(
					$c->get( FileTemplateLoader::class ),
					$c->get( FileTemplateRenderer::class ),
					$c->get( TemplateContextBuilder::class )
				);
			}
		);

		// Register concrete engine class as well for direct access.
		$container->factory(
			TemplateEngine::class,
			static function ( Container $c ): TemplateEngine {
				return $c->get( TemplateEngineInterface::class );
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
		// Load procedural helper functions and register shortcode.
		require_once __DIR__ . '/template-functions.php';
		require_once __DIR__ . '/template-shortcode.php';

		// Register the catalogist shortcode.
		register_shortcode();
	}
}
