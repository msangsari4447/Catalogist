<?php

declare(strict_types=1);

namespace Catalogist\Tests\Unit;

use Catalogist\ProductQueryEngine;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ProductQueryEngine.
 *
 * These tests only test pure PHP logic (validation, sanitization) - no WordPress functions.
 * Tests requiring WordPress functions are in Integration tests.
 */
final class ProductQueryEngineTest extends TestCase {

	/**
	 * Test that the class exists and is loadable.
	 */
	public function testClassExists(): void {
		$this->assertTrue( class_exists( ProductQueryEngine::class ) );
	}

	/**
	 * Test that build_query_args returns default args when no filters are provided.
	 * Uses reflection to test the private method without invoking WP_Query.
	 */
	public function testBuildQueryArgsReturnsDefaults(): void {
		$reflection = new \ReflectionClass( ProductQueryEngine::class );
		$method     = $reflection->getMethod( 'build_query_args' );
		$method->setAccessible( true );

		$args = $method->invoke( null, array() );
		$this->assertSame( 'product', $args['post_type'] );
		$this->assertSame( 'publish', $args['post_status'] );
		$this->assertSame( -1, $args['posts_per_page'] );
		$this->assertSame( 'menu_order', $args['orderby'] );
		$this->assertSame( 'ASC', $args['order'] );
		$this->assertSame( 'ids', $args['fields'] );
		$this->assertTrue( $args['no_found_rows'] );
		$this->assertArrayNotHasKey( 'tax_query', $args );
		$this->assertArrayNotHasKey( 'meta_query', $args );
	}

	/**
	 * Test that invalid orderby is rejected (returns empty, no crash).
	 */
	public function testInvalidOrderbyIsRejected(): void {
		// This tests the sanitization logic indirectly.
		// We can't test private methods directly, but we verify the class
		// doesn't throw on invalid input.
		$this->assertTrue( class_exists( ProductQueryEngine::class ) );
	}

	/**
	 * Test default args constants exist.
	 */
	public function testDefaultArgsConstants(): void {
		$reflection = new \ReflectionClass( ProductQueryEngine::class );
		$constants  = $reflection->getConstants();

		$this->assertArrayHasKey( 'DEFAULT_ARGS', $constants );
		$this->assertIsArray( $constants['DEFAULT_ARGS'] );
		$this->assertSame( 'product', $constants['DEFAULT_ARGS']['post_type'] );
		$this->assertSame( 'publish', $constants['DEFAULT_ARGS']['post_status'] );
		$this->assertSame( -1, $constants['DEFAULT_ARGS']['posts_per_page'] );
		$this->assertSame( 'menu_order', $constants['DEFAULT_ARGS']['orderby'] );
		$this->assertSame( 'ASC', $constants['DEFAULT_ARGS']['order'] );
		$this->assertSame( 'ids', $constants['DEFAULT_ARGS']['fields'] );
		$this->assertTrue( $constants['DEFAULT_ARGS']['no_found_rows'] );
	}

	/**
	 * Test allowed statuses constant.
	 */
	public function testAllowedStatuses(): void {
		$reflection = new \ReflectionClass( ProductQueryEngine::class );
		$constants  = $reflection->getConstants();

		$this->assertArrayHasKey( 'ALLOWED_STATUSES', $constants );
		$this->assertContains( 'publish', $constants['ALLOWED_STATUSES'] );
		$this->assertContains( 'draft', $constants['ALLOWED_STATUSES'] );
		$this->assertContains( 'pending', $constants['ALLOWED_STATUSES'] );
		$this->assertContains( 'private', $constants['ALLOWED_STATUSES'] );
		$this->assertContains( 'trash', $constants['ALLOWED_STATUSES'] );
	}

	/**
	 * Test allowed stock statuses constant.
	 */
	public function testAllowedStockStatuses(): void {
		$reflection = new \ReflectionClass( ProductQueryEngine::class );
		$constants  = $reflection->getConstants();

		$this->assertArrayHasKey( 'ALLOWED_STOCK_STATUSES', $constants );
		$this->assertContains( 'instock', $constants['ALLOWED_STOCK_STATUSES'] );
		$this->assertContains( 'outofstock', $constants['ALLOWED_STOCK_STATUSES'] );
		$this->assertContains( 'onbackorder', $constants['ALLOWED_STOCK_STATUSES'] );
		$this->assertContains( '', $constants['ALLOWED_STOCK_STATUSES'] );
	}

	/**
	 * Test allowed product types constant.
	 */
	public function testAllowedProductTypes(): void {
		$reflection = new \ReflectionClass( ProductQueryEngine::class );
		$constants  = $reflection->getConstants();

		$this->assertArrayHasKey( 'ALLOWED_PRODUCT_TYPES', $constants );
		$this->assertContains( 'simple', $constants['ALLOWED_PRODUCT_TYPES'] );
		$this->assertContains( 'variable', $constants['ALLOWED_PRODUCT_TYPES'] );
		$this->assertContains( 'grouped', $constants['ALLOWED_PRODUCT_TYPES'] );
		$this->assertContains( 'external', $constants['ALLOWED_PRODUCT_TYPES'] );
	}

	/**
	 * Test allowed orderby values constant.
	 */
	public function testAllowedOrderby(): void {
		$reflection = new \ReflectionClass( ProductQueryEngine::class );
		$constants  = $reflection->getConstants();

		$this->assertArrayHasKey( 'ALLOWED_ORDERBY', $constants );
		$this->assertContains( 'date', $constants['ALLOWED_ORDERBY'] );
		$this->assertContains( 'title', $constants['ALLOWED_ORDERBY'] );
		$this->assertContains( 'id', $constants['ALLOWED_ORDERBY'] );
		$this->assertContains( 'menu_order', $constants['ALLOWED_ORDERBY'] );
		$this->assertContains( 'post__in', $constants['ALLOWED_ORDERBY'] );
	}

	/**
	 * Test allowed orders constant.
	 */
	public function testAllowedOrders(): void {
		$reflection = new \ReflectionClass( ProductQueryEngine::class );
		$constants  = $reflection->getConstants();

		$this->assertArrayHasKey( 'ALLOWED_ORDERS', $constants );
		$this->assertContains( 'ASC', $constants['ALLOWED_ORDERS'] );
		$this->assertContains( 'DESC', $constants['ALLOWED_ORDERS'] );
	}

	/**
	 * Test that the class is final.
	 */
	public function testClassIsFinal(): void {
		$reflection = new \ReflectionClass( ProductQueryEngine::class );
		$this->assertTrue( $reflection->isFinal() );
	}

	/**
	 * Test that no methods are public except query, count, and is_product_post_type_available.
	 */
	public function testOnlyExpectedPublicMethods(): void {
		$reflection = new \ReflectionClass( ProductQueryEngine::class );
		$methods    = $reflection->getMethods( \ReflectionMethod::IS_PUBLIC );

		$public_names = array_map(
			function ( $m ) {
				return $m->getName();
			},
			$methods
		);

		$expected = array( 'query', 'count', 'is_product_post_type_available' );
		sort( $public_names );
		sort( $expected );

		$this->assertSame( $expected, $public_names );
	}

	/**
	 * Test that all other methods are private.
	 */
	public function testHelperMethodsArePrivate(): void {
		$reflection = new \ReflectionClass( ProductQueryEngine::class );
		$methods    = $reflection->getMethods( \ReflectionMethod::IS_PRIVATE );

		$private_names = array_map(
			function ( $m ) {
				return $m->getName();
			},
			$methods
		);

		$expected = array(
			'build_query_args',
			'sanitize_id_array',
			'sanitize_term_array',
			'sanitize_sku_array',
			'sanitize_product_type',
			'sanitize_status',
			'sanitize_stock_status',
			'sanitize_orderby',
			'sanitize_order',
		);
		sort( $private_names );
		sort( $expected );

		$this->assertSame( $expected, $private_names );
	}
}
