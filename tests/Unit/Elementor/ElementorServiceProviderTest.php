<?php
/**
 * Tests for Elementor Service Provider.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Tests\Unit\Elementor;

use Catalogist\Core\Container;
use Catalogist\Elementor\ElementorServiceProvider;
use Catalogist\Elementor\Widgets\ProductCardWidget;
use Catalogist\Elementor\Widgets\CatalogWidget;
use PHPUnit\Framework\TestCase;

/**
 * Elementor service provider tests.
 */
class ElementorServiceProviderTest extends TestCase {

	/**
	 * Test that provider registers when Elementor is not active.
	 */
	public function test_provider_registers_without_elementor(): void {
		// Ensure Elementor is not loaded.
		if ( class_exists( '\Elementor\Plugin' ) ) {
			$this->markTestSkipped( 'Elementor is active; this test requires Elementor to be inactive.' );
		}

		$container = new Container();
		$container->set( 'plugin.file', __FILE__ );
		$container->set( 'plugin.version', '0.1.0' );
		$container->set( 'plugin.slug', 'catalogist' );
		$container->set( 'plugin.text_domain', 'catalogist' );

		$provider = new ElementorServiceProvider( __DIR__ . '/..' );
		$provider->register( $container );

		// Should not throw; provider should be registered silently.
		$this->assertTrue( true );
	}

	/**
	 * Test provider with mock Elementor.
	 */
	public function test_provider_registers_with_mock_elementor(): void {
		// Ensure mock classes are loaded.
		require_once __DIR__ . '/../../mocks/ElementorMocks.php';

		$container = new Container();
		$container->set( 'plugin.file', __FILE__ );
		$container->set( 'plugin.version', '0.1.0' );
		$container->set( 'plugin.slug', 'catalogist' );
		$container->set( 'plugin.text_domain', 'catalogist' );

		// Mock Elementor class.
		if ( ! class_exists( '\Elementor\Plugin' ) ) {
			class_alias( Elementor_Mock_Plugin::class, '\Elementor\Plugin' );
		}

		$provider = new ElementorServiceProvider( __DIR__ . '/..' );
		$provider->register( $container );

		// Verify dynamic tags config is registered.
		$this->assertTrue( $container->has( 'elementor.dynamic_tags' ) );

		$tags = $container->get( 'elementor.dynamic_tags' );
		$this->assertCount( 16, $tags );
		$this->assertArrayHasKey( 'catalogist_product_name', $tags );
		$this->assertArrayHasKey( 'catalogist_variation_sku', $tags );
		$this->assertArrayHasKey( 'catalogist_catalog_title', $tags );
	}
}
