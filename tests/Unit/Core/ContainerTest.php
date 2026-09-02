<?php
/**
 * Unit tests for Container.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Tests\Unit\Core;

use Catalogist\Core\Container;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Container.
 */
class ContainerTest extends TestCase {

	/**
	 * Test set() and get() work for simple values.
	 *
	 * @return void
	 */
	public function test_set_and_get_simple_value(): void {
		$container = new Container();
		$container->set( 'test_service', 'test_value' );

		$this->assertEquals( 'test_value', $container->get( 'test_service' ) );
	}

	/**
	 * Test set() and get() work for objects.
	 *
	 * @return void
	 */
	public function test_set_and_get_object(): void {
		$container = new Container();
		$object    = new stdClass();
		$container->set( 'test_service', $object );

		$this->assertSame( $object, $container->get( 'test_service' ) );
	}

	/**
	 * Test factory() creates lazy-loaded services.
	 *
	 * @return void
	 */
	public function test_factory_lazy_loads(): void {
		$container = new Container();
		$call_count = 0;

		$container->factory( 'lazy_service', function () use ( &$call_count ) {
			$call_count++;
			return 'value_' . $call_count;
		} );

		// First get should call the factory.
		$this->assertEquals( 'value_1', $container->get( 'lazy_service' ) );
		// Second get should return the same value (factory not called again).
		$this->assertEquals( 'value_1', $container->get( 'lazy_service' ) );
		$this->assertEquals( 1, $call_count );
	}

	/**
	 * Test factory() receives container as argument.
	 *
	 * @return void
	 */
	public function test_factory_receives_container(): void {
		$container = new Container();
		$container->set( 'other_service', 'other_value' );

		$container->factory( 'lazy_service', function ( Container $c ) {
			return $c->get( 'other_service' ) . '_modified';
		} );

		$this->assertEquals( 'other_value_modified', $container->get( 'lazy_service' ) );
	}

	/**
	 * Test has() returns true for registered services.
	 *
	 * @return void
	 */
	public function test_has(): void {
		$container = new Container();
		$container->set( 'test_service', 'value' );

		$this->assertTrue( $container->has( 'test_service' ) );
		$this->assertFalse( $container->has( 'unknown_service' ) );
	}

	/**
	 * Test get() throws for unknown service.
	 *
	 * @return void
	 */
	public function test_get_throws_for_unknown(): void {
		$container = new Container();

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Service "unknown_service" not found in container.' );

		$container->get( 'unknown_service' );
	}

	/**
	 * Test remove() removes services and factories.
	 *
	 * @return void
	 */
	public function test_remove(): void {
		$container = new Container();
		$container->set( 'test_service', 'value' );
		$container->factory( 'lazy_service', function () {
			return 'lazy';
		} );

		$this->assertTrue( $container->has( 'test_service' ) );
		$this->assertTrue( $container->has( 'lazy_service' ) );

		$container->remove( 'test_service' );
		$container->remove( 'lazy_service' );

		$this->assertFalse( $container->has( 'test_service' ) );
		$this->assertFalse( $container->has( 'lazy_service' ) );
	}

	/**
	 * Test get() throws after remove().
	 *
	 * @return void
	 */
	public function test_get_throws_after_remove(): void {
		$container = new Container();
		$container->set( 'test_service', 'value' );
		$container->remove( 'test_service' );

		$this->expectException( InvalidArgumentException::class );

		$container->get( 'test_service' );
	}
}