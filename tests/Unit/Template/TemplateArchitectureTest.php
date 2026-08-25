<?php
/**
 * Architecture boundary tests for Template Engine.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Tests\Unit\Template;

use Catalogist\Template\TemplateEngine;
use Catalogist\Template\TemplateLoaderInterface;
use Catalogist\Template\TemplateRendererInterface;
use Catalogist\Template\TemplateContextBuilderInterface;
use Catalogist\Template\Loader\FileTemplateLoader;
use Catalogist\Template\Renderer\FileTemplateRenderer;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Tests to enforce architecture boundaries.
 */
class TemplateArchitectureTest extends TestCase {

	/**
	 * Test TemplateEngine depends only on interfaces.
	 *
	 * @return void
	 */
	public function test_template_engine_depends_on_interfaces(): void {
		$reflection = new ReflectionClass( TemplateEngine::class );
		$properties = $reflection->getProperties();

		foreach ( $properties as $property ) {
			$type = $property->getType();

			if ( $type && $type->isBuiltin() ) {
				continue;
			}

			$interfaceName = $type->getName();

			// Should be one of the interface types.
			$this->assertTrue(
				in_array(
					$interfaceName,
					array(
						TemplateLoaderInterface::class,
						TemplateRendererInterface::class,
						TemplateContextBuilderInterface::class,
					),
					true
				),
				'Vector type should be interface: ' . $interfaceName
			);
		}
	}

	/**
	 * Test TemplateEngine does not depend on concrete implementations.
	 *
	 * @return void
	 */
	public function test_template_engine_no_concrete_dependencies(): void {
		$reflection = new ReflectionClass( TemplateEngine::class );
		$contents = file_get_contents( $reflection->getFileName() );

		$this->assertStringNotContainsString( 'FileTemplateLoader', $contents );
		$this->assertStringNotContainsString( 'FileTemplateRenderer', $contents );
		$this->assertStringNotContainsString( 'TemplateContextBuilder', $contents );
	}

	/**
	 * Test FileTemplateLoader does not depend on renderer.
	 *
	 * @return void
	 */
	public function test_loader_no_renderer_dependency(): void {
		$reflection = new ReflectionClass( FileTemplateLoader::class );
		$contents = file_get_contents( $reflection->getFileName() );

		$this->assertStringNotContainsString( 'TemplateRendererInterface', $contents );
		$this->assertStringNotContainsString( 'FileTemplateRenderer', $contents );
	}

	/**
	 * Test FileTemplateRenderer depends on loader interface.
	 *
	 * @return void
	 */
	public function test_renderer_depends_on_loader_interface(): void {
		$reflection = new ReflectionClass( FileTemplateRenderer::class );
		$properties = $reflection->getProperties();

		$hasLoaderProperty = false;

		foreach ( $properties as $property ) {
			$type = $property->getType();

			if ( $type && TemplateLoaderInterface::class === $type->getName() ) {
				$hasLoaderProperty = true;
				break;
			}
		}

		$this->assertTrue( $hasLoaderProperty, 'Renderer should depend on TemplateLoaderInterface' );
	}

	/**
	 * Test TemplateContextBuilder does not depend on rendering.
	 *
	 * @return void
	 */
	public function test_context_builder_no_rendering_dependency(): void {
		$reflection = new ReflectionClass( TemplateContextBuilder::class );
		$contents = file_get_contents( $reflection->getFileName() );

		$this->assertStringNotContainsString( 'TemplateRendererInterface', $contents );
		$this->assertStringNotContainsString( 'FileTemplateRenderer', $contents );
		$this->assertStringNotContainsString( 'ob_start', $contents );
		$this->assertStringNotContainsString( 'ob_get_clean', $contents );
	}

	/**
	 * Test TemplateEngine renderCatalog does not bypass loader.
	 *
	 * @return void
	 */
	public function test_engine_uses_loader_for_template_resolution(): void {
		$reflection = new ReflectionClass( TemplateEngine::class );
		$method = $reflection->getMethod( 'renderCatalog' );
		$contents = $method->getFileName();

		$methodContents = file_get_contents( $contents );

		// Should call loader or renderer methods.
		$this->assertMatchesRegularExpression(
			'/->(render|getPath|load)\(/',
			$methodContents,
			'Engine should call loader or renderer methods'
		);
	}

	/**
	 * Test interfaces are properly implemented.
	 *
	 * @return void
	 */
	public function test_interfaces_implemented(): void {
		$loader    = new FileTemplateLoader( dirname( dirname( dirname( dirname( __DIR__ ) ) ) ) . '/' );
		$renderer  = new FileTemplateRenderer( $loader );
		$builder   = new TemplateContextBuilder();

		$this->assertInstanceOf( TemplateLoaderInterface::class, $loader );
		$this->assertInstanceOf( TemplateRendererInterface::class, $renderer );
		$this->assertInstanceOf( TemplateContextBuilderInterface::class, $builder );
	}

	/**
	 * Test fallback template is used when no template found.
	 *
	 * @return void
	 */
	public function test_fallback_template_used(): void {
		$pluginDir = dirname( dirname( dirname( dirname( __DIR__ ) ) ) ) . '/';
		$loader    = new FileTemplateLoader( $pluginDir );
		$renderer  = new FileTemplateRenderer( $loader );
		$builder   = new TemplateContextBuilder();
		$engine    = new TemplateEngine( $loader, $renderer, $builder );

		$catalog = new \Catalogist\Catalog\Catalog();
		$catalog->set_title( 'Fallback Test' );
		$items   = array();

		$output = $engine->renderCatalog( $catalog, $items );

		$this->assertStringContainsString( 'Fallback Test', $output );
		$this->assertStringContainsString( 'No products found', $output );
	}
}
