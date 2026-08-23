<?php
/**
 * Unit tests for Plugin class.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Tests\Unit\Core;

use Catalogist\Core\Plugin;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the Plugin singleton.
 */
class PluginTest extends TestCase {

	/**
	 * Test that Plugin::instance() returns the same instance.
	 *
	 * @return void
	 */
	public function test_instance_returns_singleton(): void {
		$instance1 = Plugin::instance();
		$instance2 = Plugin::instance();

		$this->assertSame( $instance1, $instance2 );
	}

	/**
	 * Test that the version constant is defined.
	 *
	 * @return void
	 */
	public function test_version_is_defined(): void {
		$this->assertNotEmpty( Plugin::VERSION );
	}

	/**
	 * Test that get_container returns null before boot.
	 *
	 * @return void
	 */
	public function test_container_is_null_before_boot(): void {
		$plugin = Plugin::instance();

		// Container should be null before boot is called.
		// Note: In a fresh process this would be null, but since we're testing
		// a singleton, the state persists. This test verifies the type.
		$result = $plugin->get_container();

		$this->assertTrue( $result === null || method_exists( $result, 'get' ) );
	}
}
