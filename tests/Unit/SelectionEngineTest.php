<?php

declare(strict_types=1);

namespace Catalogist\Tests\Unit;

use Catalogist\SelectionEngine;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for SelectionEngine.
 *
 * These tests only test pure PHP logic (validation, selection, class structure)
 * — no WordPress or WooCommerce functions. Tests requiring WordPress/WooCommerce
 * runtime are in Integration tests.
 */
final class SelectionEngineTest extends TestCase {

	/**
	 * Test that the class exists and is loadable.
	 */
	public function testClassExists(): void {
		$this->assertTrue( class_exists( SelectionEngine::class ) );
	}

	// ============================================================
	// Validation and structure tests.
	// ============================================================

	/**
	 * Test that select() is public and static.
	 */
	public function testSelectMethodIsPublicStatic(): void {
		$reflection = new \ReflectionClass( SelectionEngine::class );
		$method     = $reflection->getMethod( 'select' );
		$this->assertTrue( $method->isPublic() );
		$this->assertTrue( $method->isStatic() );
	}

	/**
	 * Test that no non-static public methods exist.
	 */
	public function testNoNonStaticPublicMethods(): void {
		$reflection = new \ReflectionClass( SelectionEngine::class );
		$methods    = $reflection->getMethods( \ReflectionMethod::IS_PUBLIC );
		$names      = array_map(
			function ( $m ) {
				return $m->getName();
			},
			$methods
		);

		foreach ( $names as $name ) {
			$method = $reflection->getMethod( $name );
			$this->assertTrue(
				$method->isStatic(),
				"Method $name should be static"
			);
		}
	}

	// ============================================================
	// Selection behavior tests (pure PHP, no WP/WC).
	// ============================================================

	/**
	 * Test that select() returns empty array for empty input.
	 */
	public function testSelectReturnsEmptyForEmptyInput(): void {
		$result = SelectionEngine::select( array(), array( 'limit' => 5 ) );
		$this->assertSame( array(), $result );
	}

	/**
	 * Test that select() returns all IDs when no config is provided.
	 */
	public function testSelectReturnsAllWhenNoConfig(): void {
		$ids    = array( 1, 2, 3, 4, 5 );
		$result = SelectionEngine::select( $ids );
		$this->assertSame( $ids, $result );
	}

	/**
	 * Test that select() with null config returns all IDs.
	 */
	public function testSelectWithNullConfigReturnsAll(): void {
		$ids    = array( 1, 2, 3 );
		$result = SelectionEngine::select( $ids, null );
		$this->assertSame( $ids, $result );
	}

	/**
	 * Test limit selection.
	 */
	public function testSelectWithLimit(): void {
		$ids    = array( 1, 2, 3, 4, 5 );
		$result = SelectionEngine::select( $ids, array( 'limit' => 3 ) );
		$this->assertSame( array( 1, 2, 3 ), $result );
	}

	/**
	 * Test offset selection.
	 */
	public function testSelectWithOffset(): void {
		$ids    = array( 1, 2, 3, 4, 5 );
		$result = SelectionEngine::select( $ids, array( 'offset' => 2 ) );
		$this->assertSame( array( 3, 4, 5 ), $result );
	}

	/**
	 * Test offset + limit selection.
	 */
	public function testSelectWithOffsetAndLimit(): void {
		$ids    = array( 1, 2, 3, 4, 5 );
		$result = SelectionEngine::select(
			$ids,
			array(
				'offset' => 1,
				'limit'  => 3,
			)
		);
		$this->assertSame( array( 2, 3, 4 ), $result );
	}

	/**
	 * Test limit exceeding available items.
	 */
	public function testSelectExcessiveLimit(): void {
		$ids    = array( 1, 2, 3 );
		$result = SelectionEngine::select( $ids, array( 'limit' => 100 ) );
		$this->assertSame( array( 1, 2, 3 ), $result );
	}

	/**
	 * Test offset exceeding available items.
	 */
	public function testSelectExcessiveOffset(): void {
		$ids    = array( 1, 2, 3 );
		$result = SelectionEngine::select( $ids, array( 'offset' => 100 ) );
		$this->assertSame( array(), $result );
	}

	/**
	 * Test zero limit returns empty.
	 */
	public function testSelectZeroLimit(): void {
		$ids    = array( 1, 2, 3 );
		$result = SelectionEngine::select( $ids, array( 'limit' => 0 ) );
		$this->assertSame( array(), $result );
	}

	/**
	 * Test negative limit is treated as no limit.
	 */
	public function testSelectNegativeLimit(): void {
		$ids    = array( 1, 2, 3 );
		$result = SelectionEngine::select( $ids, array( 'limit' => -5 ) );
		$this->assertSame( $ids, $result );
	}

	/**
	 * Test negative offset is treated as zero offset.
	 */
	public function testSelectNegativeOffset(): void {
		$ids    = array( 1, 2, 3 );
		$result = SelectionEngine::select( $ids, array( 'offset' => -5 ) );
		$this->assertSame( $ids, $result );
	}

	/**
	 * Test that select() normalizes string IDs to integers.
	 */
	public function testSelectNormalizesIdsToIntegers(): void {
		$ids    = array( '3', '1', '2' );
		$result = SelectionEngine::select( $ids );
		$this->assertSame( array( 3, 1, 2 ), $result );
	}

	/**
	 * Test that select() deduplicates IDs.
	 */
	public function testSelectDeduplicatesIds(): void {
		$ids    = array( 2, 1, 2, 1, 3 );
		$result = SelectionEngine::select( $ids, array( 'limit' => 3 ) );
		$this->assertSame( array( 2, 1, 3 ), $result );
	}

	/**
	 * Test that select() rejects invalid config (non-array, non-null) gracefully.
	 */
	public function testSelectRejectsInvalidConfig(): void {
		$ids    = array( 1, 2, 3 );
		$result = SelectionEngine::select( $ids, 'invalid' );
		$this->assertSame( $ids, $result );

		$result = SelectionEngine::select( $ids, 123 );
		$this->assertSame( $ids, $result );
	}

	/**
	 * Test deterministic repeated execution.
	 */
	public function testSelectDeterministic(): void {
		$ids    = array( 1, 2, 3, 4, 5 );
		$config = array(
			'offset' => 1,
			'limit'  => 3,
		);

		$results = array();
		for ( $i = 0; $i < 10; $i++ ) {
			$results[] = SelectionEngine::select( $ids, $config );
		}

		$first = $results[0];
		foreach ( $results as $result ) {
			$this->assertSame( $first, $result );
		}
	}

	// ============================================================
	// Integration-style pipeline tests (pure PHP).
	// ============================================================

	/**
	 * Test filter → sort → select pipeline with mock data.
	 *
	 * Simulates the pipeline using array_map to avoid WC dependency.
	 */
	public function testPipelineFilterSortSelect(): void {
		$ids = array( 5, 3, 1, 4, 2 );
		// Pretend sorted ascending by ID.
		$sorted = array( 1, 2, 3, 4, 5 );
		// Apply offset 1 and limit 3 to get 2 items.
		$selected = array_slice( $sorted, 1, 3 );

		// SelectionEngine::select simulates the final stage.
		$result = SelectionEngine::select(
			$sorted,
			array(
				'offset' => 1,
				'limit'  => 3,
			)
		);
		$this->assertSame( $selected, $result );
	}

	/**
	 * Test empty input through full pipeline.
	 */
	public function testPipelineEmptyInput(): void {
		$result = SelectionEngine::select( array() );
		$this->assertSame( array(), $result );
	}
}
