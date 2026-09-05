<?php

declare(strict_types=1);

namespace Catalogist\Tests\Unit;

use Catalogist\SortEngine;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for SortEngine.
 *
 * These tests only test pure PHP logic (validation, class structure) —
 * no WordPress or WooCommerce functions. Tests requiring WordPress/WooCommerce
 * runtime are in Integration tests.
 */
final class SortEngineTest extends TestCase {

	/**
	 * Test that the class exists and is loadable.
	 */
	public function testClassExists(): void {
		$this->assertTrue( class_exists( SortEngine::class ) );
	}

	// ============================================================
	// Validation and structure tests.
	// ============================================================

	/**
	 * Test that sort() is public and static.
	 */
	public function testSortMethodIsPublicStatic(): void {
		$reflection = new \ReflectionClass( SortEngine::class );
		$method     = $reflection->getMethod( 'sort' );
		$this->assertTrue( $method->isPublic() );
		$this->assertTrue( $method->isStatic() );
	}

	/**
	 * Test that parse_sort_config rejects non-array/non-string input.
	 */
	public function testParseSortConfigRejectsNonArrayNonString(): void {
		$reflection = new \ReflectionClass( SortEngine::class );
		$method     = $reflection->getMethod( 'parse_sort_config' );
		$method->setAccessible( true );

		$this->assertNull( $method->invoke( null, 123 ) );
		$this->assertNull( $method->invoke( null, null ) );
		$this->assertNull( $method->invoke( null, new \stdClass() ) );
	}

	/**
	 * Test that parse_sort_config rejects invalid key.
	 */
	public function testParseSortConfigRejectsInvalidKey(): void {
		$reflection = new \ReflectionClass( SortEngine::class );
		$method     = $reflection->getMethod( 'parse_sort_config' );
		$method->setAccessible( true );

		$this->assertNull(
			$method->invoke(
				null,
				array(
					'key'       => 'nonexistent',
					'direction' => 'asc',
				)
			)
		);
	}

	/**
	 * Test that parse_sort_config accepts valid key and direction.
	 */
	public function testParseSortConfigAcceptsValidKeyAndDirection(): void {
		$reflection = new \ReflectionClass( SortEngine::class );
		$method     = $reflection->getMethod( 'parse_sort_config' );
		$method->setAccessible( true );

		$result = $method->invoke(
			null,
			array(
				'key'       => 'title',
				'direction' => 'asc',
			)
		);

		$this->assertIsArray( $result );
		$this->assertSame( 'title', $result['key'] );
		$this->assertSame( 'asc', $result['direction'] );
	}

	/**
	 * Test that parse_sort_config rejects invalid direction.
	 */
	public function testParseSortConfigRejectsInvalidDirection(): void {
		$reflection = new \ReflectionClass( SortEngine::class );
		$method     = $reflection->getMethod( 'parse_sort_config' );
		$method->setAccessible( true );

		$result = $method->invoke(
			null,
			array(
				'key'       => 'price',
				'direction' => 'INVALID',
			)
		);

		$this->assertNull( $result );
	}

	/**
	 * Test that parse_sort_config rejects missing key.
	 */
	public function testParseSortConfigRejectsMissingKey(): void {
		$reflection = new \ReflectionClass( SortEngine::class );
		$method     = $reflection->getMethod( 'parse_sort_config' );
		$method->setAccessible( true );

		$this->assertNull(
			$method->invoke(
				null,
				array(
					'direction' => 'asc',
				)
			)
		);
	}

	/**
	 * Test that parse_sort_config rejects missing direction.
	 */
	public function testParseSortConfigRejectsMissingDirection(): void {
		$reflection = new \ReflectionClass( SortEngine::class );
		$method     = $reflection->getMethod( 'parse_sort_config' );
		$method->setAccessible( true );

		$this->assertNull(
			$method->invoke(
				null,
				array(
					'key' => 'title',
				)
			)
		);
	}

	/**
	 * Test that parse_sort_config accepts legacy string shorthand.
	 */
	public function testParseSortConfigAcceptsLegacyShorthand(): void {
		$reflection = new \ReflectionClass( SortEngine::class );
		$method     = $reflection->getMethod( 'parse_sort_config' );
		$method->setAccessible( true );

		$result = $method->invoke( null, 'sku:desc' );
		$this->assertIsArray( $result );
		$this->assertSame( 'sku', $result['key'] );
		$this->assertSame( 'desc', $result['direction'] );
	}

	/**
	 * Test that parse_sort_config rejects invalid legacy shorthand.
	 */
	public function testParseSortConfigRejectsInvalidShorthand(): void {
		$reflection = new \ReflectionClass( SortEngine::class );
		$method     = $reflection->getMethod( 'parse_sort_config' );
		$method->setAccessible( true );

		$this->assertNull( $method->invoke( null, 'invalid' ) );
		$this->assertNull( $method->invoke( null, 'title' ) );
		$this->assertNull( $method->invoke( null, 'title:asc:extra' ) );
	}

	// ============================================================
	// Sorting behavior tests (pure PHP, no WP/WC).
	// ============================================================

	/**
	 * Test that sort() returns empty array for empty input.
	 */
	public function testSortReturnsEmptyForEmptyInput(): void {
		$result = SortEngine::sort(
			array(),
			array(
				'key'       => 'title',
				'direction' => 'asc',
			)
		);
		$this->assertSame( array(), $result );
	}

	/**
	 * Test that sort() returns input as-is when config is invalid.
	 */
	public function testSortReturnsInputForInvalidConfig(): void {
		$ids    = array( 3, 1, 2 );
		$result = SortEngine::sort( $ids, 'invalid-sort' );
		// Invalid config preserves original order (after normalization/dedup)
		$this->assertSame( array( 3, 1, 2 ), $result );
	}

	/**
	 * Test that sort() normalizes IDs to integers (with invalid config).
	 */
	public function testSortNormalizesIdsToIntegers(): void {
		$result = SortEngine::sort(
			array( '3', '1', '2' ),
			'invalid-sort'
		);
		// Invalid config preserves order after normalization
		$this->assertSame( array( 3, 1, 2 ), $result );
	}

	/**
	 * Test that sort() deduplicates IDs (with invalid config).
	 */
	public function testSortDeduplicatesIds(): void {
		$ids    = array( 2, 1, 2, 1, 3 );
		$result = SortEngine::sort( $ids, array() );
		// Invalid config preserves original order after dedup
		$this->assertSame( array( 2, 1, 3 ), $result );
	}

	/**
	 * Test that sort() with invalid key returns normalized IDs.
	 */
	public function testSortWithInvalidKeyReturnsNormalizedIds(): void {
		$ids    = array( 5, 3, 1, 3 );
		$result = SortEngine::sort(
			$ids,
			array(
				'key'       => 'nonexistent',
				'direction' => 'asc',
			)
		);
		$this->assertSame( array( 5, 3, 1 ), $result );
	}

	/**
	 * Test that sort() with invalid direction returns normalized IDs.
	 */
	public function testSortWithInvalidDirectionReturnsNormalizedIds(): void {
		// Invalid direction makes config invalid, so returns normalized IDs (not sorted).
		$result = SortEngine::sort(
			array( 3, 1, 2 ),
			array(
				'key'       => 'title',
				'direction' => 'INVALID',
			)
		);
		$this->assertSame( array( 3, 1, 2 ), $result );
	}

	// ============================================================
	// Determinism tests.
	// ============================================================

	/**
	 * Test that sort() produces identical output on repeated calls with same input (invalid config).
	 *
	 * Determinism with valid config requires WordPress/WooCommerce runtime.
	 */
	public function testSortDeterministicInvalidConfig(): void {
		$ids = array( 5, 3, 1, 4, 2 );

		$results = array();
		for ( $i = 0; $i < 10; $i++ ) {
			$results[] = SortEngine::sort( $ids, 'invalid' );
		}

		$first = $results[0];
		foreach ( $results as $result ) {
			$this->assertSame( $first, $result );
		}
	}
}
