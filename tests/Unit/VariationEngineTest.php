<?php

declare(strict_types=1);

namespace Catalogist\Tests\Unit;

use Catalogist\VariationEngine;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for VariationEngine.
 *
 * These tests only test pure PHP logic (class structure, method existence)
 * — no WordPress or WooCommerce functions.
 * Tests requiring WordPress/WooCommerce runtime are in Integration tests.
 */
final class VariationEngineTest extends TestCase {

	/**
	 * Test that the class exists and is loadable.
	 */
	public function testClassExists(): void {
		$this->assertTrue( class_exists( VariationEngine::class ) );
	}

	/**
	 * Test that all expected public methods exist.
	 */
	public function testPublicMethodsExist(): void {
		$methods = get_class_methods( VariationEngine::class );

		$expected = array(
			'get_variation_ids',
			'is_variable_product',
			'get_variation_data',
			'expand_product_ids',
			'resolve_product_ids',
		);

		sort( $methods );
		sort( $expected );

		$this->assertSame( $expected, $methods );
	}

	/**
	 * Test that the class has the expected constant.
	 */
	public function testAllowedModesConstantExists(): void {
		$reflection = new \ReflectionClass( VariationEngine::class );
		$constants  = $reflection->getConstants();

		$this->assertArrayHasKey( 'ALLOWED_MODES', $constants );
		$this->assertSame(
			array( 'parent', 'variations' ),
			$constants['ALLOWED_MODES']
		);
	}

	/**
	 * Test that resolve_product_ids handles invalid mode gracefully.
	 */
	public function testResolveProductIdsWithInvalidMode(): void {
		// Invalid mode should fall back to 'parent' behavior (no expansion).
		$result = VariationEngine::resolve_product_ids(
			array( 1, 2, 3 ),
			'invalid-mode'
		);

		$this->assertSame( array( 1, 2, 3 ), $result );
	}

	/**
	 * Test that resolve_product_ids with empty array returns empty.
	 */
	public function testResolveProductIdsWithEmptyArray(): void {
		$result = VariationEngine::resolve_product_ids( array(), 'variations' );
		$this->assertSame( array(), $result );
	}

	/**
	 * Test that expand_product_ids with empty array returns empty.
	 */
	public function testExpandProductIdsWithEmptyArray(): void {
		$result = VariationEngine::expand_product_ids( array(), true );
		$this->assertSame( array(), $result );
	}

	/**
	 * Test that expand_product_ids with flag false returns sanitized input.
	 */
	public function testExpandProductIdsWithoutFlag(): void {
		$result = VariationEngine::expand_product_ids( array( 1, 2, 3 ), false );
		$this->assertSame( array( 1, 2, 3 ), $result );
	}

	/**
	 * Test that expand_product_ids filters non-integer values.
	 */
	public function testExpandProductIdsFiltersNonIntegers(): void {
		// Non-integer values should be filtered out.
		$result = VariationEngine::expand_product_ids(
			array( 'abc', 0, -1, 42, null, '' ),
			false
		);
		$this->assertSame( array( 42 ), $result );
	}

	/**
	 * Test that expand_product_ids deduplicates.
	 */
	public function testExpandProductIdsDeduplicates(): void {
		$result = VariationEngine::expand_product_ids(
			array( 1, 1, 2, 2, 3 ),
			false
		);
		$this->assertSame( array( 1, 2, 3 ), $result );
	}

	/**
	 * Test resolve_product_ids with 'parent' mode returns unchanged IDs.
	 */
	public function testResolveProductIdsParentMode(): void {
		$result = VariationEngine::resolve_product_ids(
			array( 10, 20, 30 ),
			'parent'
		);
		$this->assertSame( array( 10, 20, 30 ), $result );
	}

	/**
	 * Test that sanitize_mode accepts valid modes via reflection.
	 */
	public function testSanitizeModeValidModes(): void {
		$reflection = new \ReflectionClass( VariationEngine::class );
		$method     = $reflection->getMethod( 'sanitize_mode' );
		$method->setAccessible( true );

		$this->assertSame( 'parent', $method->invoke( null, 'parent' ) );
		$this->assertSame( 'variations', $method->invoke( null, 'VARIATIONS' ) );
		$this->assertSame( 'variations', $method->invoke( null, '  Variations  ' ) );
		$this->assertNull( $method->invoke( null, 'invalid-mode' ) );
		$this->assertNull( $method->invoke( null, '' ) );
		$this->assertNull( $method->invoke( null, '0' ) );
	}
}
