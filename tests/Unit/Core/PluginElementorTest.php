<?php
/**
 * Tests for Plugin.php Elementor conditional loading.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Tests\Unit\Core;

use Catalogist\Core\Plugin;
use PHPUnit\Framework\TestCase;

/**
 * Plugin Elementor tests.
 */
class PluginElementorTest extends TestCase {

	/**
	 * Test Plugin can be instantiated without Elementor.
	 */
	public function test_plugin_instantiation_without_elementor(): void {
		// Ensure Elementor is not loaded.
		if ( class_exists( '\Elementor\Plugin' ) ) {
			$this->markTestSkipped( 'Elementor is active; this test requires Elementor to be inactive.' );
		}

		// Plugin should instantiate without errors.
		$plugin = Plugin::instance();
		$this->assertInstanceOf( Plugin::class, $plugin );
	}

	/**
	 * Test Plugin bootstrap without Elementor.
	 */
	public function test_plugin_boot_without_elementor(): void {
		// Ensure Elementor is not loaded.
		if ( class_exists( '\Elementor\Plugin' ) ) {
			$this->markTestSkipped( 'Elementor is active; this test requires Elementor to be inactive.' );
		}

		// Plugin should boot without errors.
		$plugin = Plugin::instance();
		$plugin->boot();
		$this->assertNotNull( $plugin->get_container() );
	}

	/**
	 * Test Plugin container has expected services.
	 */
	public function test_plugin_container_has_expected_services(): void {
		// Ensure Elementor is not loaded.
		if ( class_exists( '\Elementor\Plugin' ) ) {
			$this->markTestSkipped( 'Elementor is active; this test requires Elementor to be inactive.' );
		}

		$plugin = Plugin::instance();
		$plugin->boot();
		$container = $plugin->get_container();

		$this->assertNotNull( $container );
		$this->assertTrue( $container->has( 'plugin.file' ) );
		$this->assertTrue( $container->has( 'plugin.version' ) );
		$this->assertTrue( $container->has( 'plugin.slug' ) );
		$this->assertTrue( $container->has( 'plugin.text_domain' ) );
	}
}
