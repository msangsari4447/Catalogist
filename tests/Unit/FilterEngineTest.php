<?php

declare(strict_types=1);

namespace Catalogist\Tests\Unit;

use Catalogist\FilterEngine;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for FilterEngine.
 *
 * These tests only test pure PHP logic (validation, class structure) —
 * no WordPress or WooCommerce functions. Tests requiring WordPress/WooCommerce
 * runtime are in Integration tests.
 */
final class FilterEngineTest extends TestCase {

	/**
	 * Test that the class exists and is loadable.
	 */
	public function testClassExists(): void {
		$this->assertTrue( class_exists( FilterEngine::class ) );
	}

	// ============================================================
	// Validation tests.
	// ============================================================

	/**
	 * Test validate_filter accepts a valid type filter.
	 */
	public function testValidateFilterValidType(): void {
		$this->assertTrue(
			FilterEngine::validate_filter(
				array(
					'type'  => 'type',
					'value' => 'simple',
				)
			)
		);
	}

	/**
	 * Test validate_filter accepts a valid category filter.
	 */
	public function testValidateFilterValidCategory(): void {
		$this->assertTrue(
			FilterEngine::validate_filter(
				array(
					'type'  => 'category',
					'value' => 'electronics',
				)
			)
		);
		$this->assertTrue(
			FilterEngine::validate_filter(
				array(
					'type'  => 'category',
					'value' => array( 'electronics', 'gadgets' ),
				)
			)
		);
	}

	/**
	 * Test validate_filter accepts a valid tag filter.
	 */
	public function testValidateFilterValidTag(): void {
		$this->assertTrue(
			FilterEngine::validate_filter(
				array(
					'type'  => 'tag',
					'value' => 'new-arrival',
				)
			)
		);
		$this->assertTrue(
			FilterEngine::validate_filter(
				array(
					'type'  => 'tag',
					'value' => array( 'sale', 'featured' ),
				)
			)
		);
	}

	/**
	 * Test validate_filter accepts a valid stock_status filter.
	 */
	public function testValidateFilterValidStockStatus(): void {
		$this->assertTrue(
			FilterEngine::validate_filter(
				array(
					'type'  => 'stock_status',
					'value' => 'instock',
				)
			)
		);
		$this->assertTrue(
			FilterEngine::validate_filter(
				array(
					'type'  => 'stock_status',
					'value' => 'outofstock',
				)
			)
		);
		$this->assertTrue(
			FilterEngine::validate_filter(
				array(
					'type'  => 'stock_status',
					'value' => 'onbackorder',
				)
			)
		);
	}

	/**
	 * Test validate_filter accepts a valid price filter with min and max.
	 */
	public function testValidateFilterValidPriceRange(): void {
		$this->assertTrue(
			FilterEngine::validate_filter(
				array(
					'type'  => 'price',
					'value' => array(
						'min' => 10,
						'max' => 100,
					),
				)
			)
		);
	}

	/**
	 * Test validate_filter accepts a valid price filter with only min.
	 */
	public function testValidateFilterValidPriceMinOnly(): void {
		$this->assertTrue(
			FilterEngine::validate_filter(
				array(
					'type'  => 'price',
					'value' => array( 'min' => 5 ),
				)
			)
		);
	}

	/**
	 * Test validate_filter accepts a valid price filter with only max.
	 */
	public function testValidateFilterValidPriceMaxOnly(): void {
		$this->assertTrue(
			FilterEngine::validate_filter(
				array(
					'type'  => 'price',
					'value' => array( 'max' => 50 ),
				)
			)
		);
	}

	/**
	 * Test validate_filter accepts a valid sku filter.
	 */
	public function testValidateFilterValidSku(): void {
		$this->assertTrue(
			FilterEngine::validate_filter(
				array(
					'type'  => 'sku',
					'value' => 'ABC123',
				)
			)
		);
		$this->assertTrue(
			FilterEngine::validate_filter(
				array(
					'type'  => 'sku',
					'value' => array( 'ABC123', 'DEF456' ),
				)
			)
		);
	}

	/**
	 * Test validate_filter rejects missing type key.
	 */
	public function testValidateFilterRejectsMissingType(): void {
		$this->assertFalse(
			FilterEngine::validate_filter( array( 'value' => 'simple' ) )
		);
	}

	/**
	 * Test validate_filter rejects missing value key.
	 */
	public function testValidateFilterRejectsMissingValue(): void {
		$this->assertFalse(
			FilterEngine::validate_filter( array( 'type' => 'type' ) )
		);
	}

	/**
	 * Test validate_filter rejects invalid filter type.
	 */
	public function testValidateFilterRejectsInvalidType(): void {
		$this->assertFalse(
			FilterEngine::validate_filter(
				array(
					'type'  => 'nonexistent',
					'value' => 'x',
				)
			)
		);
	}

	/**
	 * Test validate_filter rejects invalid product type value.
	 */
	public function testValidateFilterRejectsInvalidProductType(): void {
		$this->assertFalse(
			FilterEngine::validate_filter(
				array(
					'type'  => 'type',
					'value' => 'invalid-type',
				)
			)
		);
	}

	/**
	 * Test validate_filter rejects invalid stock status.
	 */
	public function testValidateFilterRejectsInvalidStockStatus(): void {
		$this->assertFalse(
			FilterEngine::validate_filter(
				array(
					'type'  => 'stock_status',
					'value' => 'invalid-status',
				)
			)
		);
	}

	/**
	 * Test validate_filter rejects malformed price filter (non-numeric min).
	 */
	public function testValidateFilterRejectsMalformedPriceMin(): void {
		$this->assertFalse(
			FilterEngine::validate_filter(
				array(
					'type'  => 'price',
					'value' => array( 'min' => 'not-a-number' ),
				)
			)
		);
	}

	/**
	 * Test validate_filter rejects malformed price filter (non-numeric max).
	 */
	public function testValidateFilterRejectsMalformedPriceMax(): void {
		$this->assertFalse(
			FilterEngine::validate_filter(
				array(
					'type'  => 'price',
					'value' => array( 'max' => 'not-a-number' ),
				)
			)
		);
	}

	/**
	 * Test validate_filter rejects price filter with min > max.
	 */
	public function testValidateFilterRejectsPriceMinGreaterThanMax(): void {
		$this->assertFalse(
			FilterEngine::validate_filter(
				array(
					'type'  => 'price',
					'value' => array(
						'min' => 100,
						'max' => 10,
					),
				)
			)
		);
	}

	/**
	 * Test validate_filter rejects empty price filter.
	 */
	public function testValidateFilterRejectsEmptyPrice(): void {
		$this->assertFalse(
			FilterEngine::validate_filter(
				array(
					'type'  => 'price',
					'value' => array(),
				)
			)
		);
	}

	/**
	 * Test validate_filter rejects non-array input.
	 */
	public function testValidateFilterRejectsNonArray(): void {
		$this->assertFalse(
			FilterEngine::validate_filter( 'not-an-array' )
		);
		$this->assertFalse(
			FilterEngine::validate_filter( null )
		);
		$this->assertFalse(
			FilterEngine::validate_filter( 123 )
		);
	}

	/**
	 * Test validate_filter rejects empty string category.
	 */
	public function testValidateFilterRejectsEmptyCategory(): void {
		$this->assertFalse(
			FilterEngine::validate_filter(
				array(
					'type'  => 'category',
					'value' => '',
				)
			)
		);
	}

	/**
	 * Test validate_filter rejects empty string tag.
	 */
	public function testValidateFilterRejectsEmptyTag(): void {
		$this->assertFalse(
			FilterEngine::validate_filter(
				array(
					'type'  => 'tag',
					'value' => '',
				)
			)
		);
	}

	// ============================================================
	// Composition and determinism tests (pure PHP, no WP).
	// ============================================================

	/**
	 * Test that filter() returns original IDs when no filters provided.
	 */
	public function testFilterReturnsOriginalIdsWhenNoFilters(): void {
		$ids    = array( 1, 2, 3, 4, 5 );
		$result = FilterEngine::filter( $ids, array() );
		$this->assertSame( $ids, $result );
	}

	/**
	 * Test that filter() returns empty array for empty input.
	 */
	public function testFilterReturnsEmptyForEmptyInput(): void {
		$result = FilterEngine::filter(
			array(),
			array(
				array(
					'type'  => 'type',
					'value' => 'simple',
				),
			)
		);
		$this->assertSame( array(), $result );
	}

	/**
	 * Test that filter() returns empty array when invalid filter is provided.
	 * Invalid filters should be silently skipped.
	 */
	public function testFilterSkipsInvalidFilters(): void {
		$ids    = array( 1, 2, 3 );
		$result = FilterEngine::filter(
			$ids,
			array(
				array(
					'type'  => 'nonexistent',
					'value' => 'x',
				),
			)
		);
		$this->assertSame( $ids, $result );
	}

	/**
	 * Test that filter() normalizes IDs to integers.
	 */
	public function testFilterNormalizesIdsToIntegers(): void {
		$ids    = array( '1', '2', '3' );
		$result = FilterEngine::filter( $ids, array() );
		$this->assertSame( array( 1, 2, 3 ), $result );
	}

	/**
	 * Test that filter() deduplicates IDs.
	 */
	public function testFilterDeduplicatesIds(): void {
		$ids    = array( 1, 1, 2, 2, 3 );
		$result = FilterEngine::filter( $ids, array() );
		$this->assertSame( array( 1, 2, 3 ), $result );
	}

	/**
	 * Test that filter() with mixed valid/invalid filters skips invalid ones.
	 */
	public function testFilterMixedValidAndInvalidFilters(): void {
		$ids = array( 1, 2, 3 );
		// All filters invalid — should return original unchanged.
		$result = FilterEngine::filter(
			$ids,
			array(
				array(
					'type'  => 'bad',
					'value' => 'x',
				),
				array(
					'type'  => 'nonexistent',
					'value' => 'y',
				),
			)
		);
		$this->assertSame( $ids, $result );
	}

	/**
	 * Test that filter() does not mutate the original input array.
	 */
	public function testFilterDoesNotMutateInput(): void {
		$ids      = array( 1, 2, 3 );
		$original = $ids;
		FilterEngine::filter( $ids, array() );
		$this->assertSame( $original, $ids );
	}

	// ============================================================
	// Class structure tests.
	// ============================================================

	/**
	 * Test that FilterEngine is a final class.
	 */
	public function testClassIsFinal(): void {
		$reflection = new \ReflectionClass( FilterEngine::class );
		$this->assertTrue( $reflection->isFinal() );
	}

	/**
	 * Test that filter() is a public static method.
	 */
	public function testFilterMethodIsPublicStatic(): void {
		$reflection = new \ReflectionClass( FilterEngine::class );
		$method     = $reflection->getMethod( 'filter' );
		$this->assertTrue( $method->isPublic() );
		$this->assertTrue( $method->isStatic() );
	}

	/**
	 * Test that validate_filter is a public static method.
	 */
	public function testValidateFilterMethodIsPublicStatic(): void {
		$reflection = new \ReflectionClass( FilterEngine::class );
		$method     = $reflection->getMethod( 'validate_filter' );
		$this->assertTrue( $method->isPublic() );
		$this->assertTrue( $method->isStatic() );
	}

	/**
	 * Test that no non-static public methods exist.
	 */
	public function testNoNonStaticPublicMethods(): void {
		$reflection = new \ReflectionClass( FilterEngine::class );
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
}
