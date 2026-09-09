<?php

declare(strict_types=1);

namespace Catalogist\Tests\Integration;

use Catalogist\FilterEngine;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for FilterEngine.
 *
 * These tests require a full WordPress + WooCommerce environment (run via docker compose).
 */
final class FilterEngineTest extends TestCase {

	/**
	 * Test IDs created during setUp.
	 *
	 * @var array<string, int>
	 */
	private array $created_product_ids = array();

	/**
	 * Set up test fixtures: create products for filtering.
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->created_product_ids = array(
			'simple-instock'    => $this->create_product(
				'simple',
				'Simple In-Stock Product',
				array(
					'price'      => '25.00',
					'sku'        => 'SIM-001',
					'stock_qty'  => 10,
					'categories' => array( 'electronics' ),
					'tags'       => array( 'new' ),
				)
			),
			'simple-outofstock' => $this->create_product(
				'simple',
				'Simple Out-Of-Stock Product',
				array(
					'price'      => '50.00',
					'sku'        => 'SIM-002',
					'stock_qty'  => 0,
					'categories' => array( 'electronics' ),
					'tags'       => array( 'sale' ),
				)
			),
			'variable-product'  => $this->create_product(
				'variable',
				'Variable Product',
				array(
					'price'      => '75.00',
					'sku'        => 'VAR-001',
					'categories' => array( 'clothing' ),
					'tags'       => array( 'new', 'sale' ),
				)
			),
			'external-product'  => $this->create_product(
				'external',
				'External Product',
				array(
					'price'      => '100.00',
					'sku'        => 'EXT-001',
					'categories' => array( 'books' ),
					'tags'       => array( 'featured' ),
				)
			),
			'no-sku-product'    => $this->create_product(
				'simple',
				'Product Without SKU',
				array(
					'price'      => '30.00',
					'sku'        => '',
					'categories' => array( 'books' ),
					'tags'       => array( 'new' ),
				)
			),
		);
	}

	/**
	 * Clean up created products.
	 */
	protected function tearDown(): void {
		foreach ( $this->created_product_ids as $id ) {
			wp_delete_post( $id, true );
		}
		parent::tearDown();
	}

	// ============================================================
	// Basic existence and structure tests.
	// ============================================================

	/**
	 * Test that FilterEngine class is loadable.
	 */
	public function testClassExists(): void {
		$this->assertTrue( class_exists( FilterEngine::class ) );
	}

	// ============================================================
	// No filters / empty input tests.
	// ============================================================

	/**
	 * Test filter returns all IDs when no filters provided.
	 */
	public function testFilterReturnsAllIdsWhenNoFilters(): void {
		$ids    = array_values( $this->created_product_ids );
		$result = FilterEngine::filter( $ids, array() );
		$this->assertSameSorted( $ids, $result );
	}

	/**
	 * Test filter returns empty array for empty input IDs.
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
	 * Test filter returns empty array when no products match.
	 */
	public function testFilterReturnsEmptyWhenNoMatch(): void {
		$ids    = array_values( $this->created_product_ids );
		$result = FilterEngine::filter(
			$ids,
			array(
				array(
					'type'  => 'type',
					'value' => 'grouped',
				),
			)
		);
		$this->assertSame( array(), $result );
	}

	// ============================================================
	// Type filter tests.
	// ============================================================

	/**
	 * Test filtering by simple product type.
	 */
	public function testFilterByTypeSimple(): void {
		$ids    = array_values( $this->created_product_ids );
		$result = FilterEngine::filter(
			$ids,
			array(
				array(
					'type'  => 'type',
					'value' => 'simple',
				),
			)
		);

		$this->assertCount( 3, $result );
		$this->assertContains( $this->created_product_ids['simple-instock'], $result );
		$this->assertContains( $this->created_product_ids['simple-outofstock'], $result );
		$this->assertContains( $this->created_product_ids['no-sku-product'], $result );
		$this->assertNotContains( $this->created_product_ids['variable-product'], $result );
		$this->assertNotContains( $this->created_product_ids['external-product'], $result );
	}

	/**
	 * Test filtering by variable product type.
	 */
	public function testFilterByTypeVariable(): void {
		$ids    = array_values( $this->created_product_ids );
		$result = FilterEngine::filter(
			$ids,
			array(
				array(
					'type'  => 'type',
					'value' => 'variable',
				),
			)
		);

		$this->assertCount( 1, $result );
		$this->assertContains( $this->created_product_ids['variable-product'], $result );
	}

	/**
	 * Test filtering by external product type.
	 */
	public function testFilterByTypeExternal(): void {
		$ids    = array_values( $this->created_product_ids );
		$result = FilterEngine::filter(
			$ids,
			array(
				array(
					'type'  => 'type',
					'value' => 'external',
				),
			)
		);

		$this->assertCount( 1, $result );
		$this->assertContains( $this->created_product_ids['external-product'], $result );
	}

	/**
	 * Test filtering by case-insensitive product type.
	 */
	public function testFilterByTypeCaseInsensitive(): void {
		$ids    = array_values( $this->created_product_ids );
		$result = FilterEngine::filter(
			$ids,
			array(
				array(
					'type'  => 'type',
					'value' => 'SIMPLE',
				),
			)
		);

		$this->assertCount( 3, $result );
	}

	// ============================================================
	// Category filter tests.
	// ============================================================

	/**
	 * Test filtering by single category slug.
	 */
	public function testFilterByCategorySingle(): void {
		$ids    = array_values( $this->created_product_ids );
		$result = FilterEngine::filter(
			$ids,
			array(
				array(
					'type'  => 'category',
					'value' => 'electronics',
				),
			)
		);

		$this->assertCount( 2, $result );
		$this->assertContains( $this->created_product_ids['simple-instock'], $result );
		$this->assertContains( $this->created_product_ids['simple-outofstock'], $result );
	}

	/**
	 * Test filtering by multiple category slugs.
	 */
	public function testFilterByCategoryMultiple(): void {
		$ids    = array_values( $this->created_product_ids );
		$result = FilterEngine::filter(
			$ids,
			array(
				array(
					'type'  => 'category',
					'value' => array( 'electronics', 'books' ),
				),
			)
		);

		$this->assertCount( 4, $result );
		$this->assertContains( $this->created_product_ids['simple-instock'], $result );
		$this->assertContains( $this->created_product_ids['simple-outofstock'], $result );
		$this->assertContains( $this->created_product_ids['external-product'], $result );
		$this->assertContains( $this->created_product_ids['no-sku-product'], $result );
	}

	/**
	 * Test filtering by non-existent category returns empty.
	 */
	public function testFilterByCategoryNonExistent(): void {
		$ids    = array_values( $this->created_product_ids );
		$result = FilterEngine::filter(
			$ids,
			array(
				array(
					'type'  => 'category',
					'value' => 'nonexistent-category',
				),
			)
		);

		$this->assertSame( array(), $result );
	}

	// ============================================================
	// Tag filter tests.
	// ============================================================

	/**
	 * Test filtering by single tag slug.
	 */
	public function testFilterByTagSingle(): void {
		$ids    = array_values( $this->created_product_ids );
		$result = FilterEngine::filter(
			$ids,
			array(
				array(
					'type'  => 'tag',
					'value' => 'new',
				),
			)
		);

		$this->assertCount( 3, $result );
		$this->assertContains( $this->created_product_ids['simple-instock'], $result );
		$this->assertContains( $this->created_product_ids['variable-product'], $result );
		$this->assertContains( $this->created_product_ids['no-sku-product'], $result );
	}

	/**
	 * Test filtering by multiple tag slugs.
	 */
	public function testFilterByTagMultiple(): void {
		$ids    = array_values( $this->created_product_ids );
		$result = FilterEngine::filter(
			$ids,
			array(
				array(
					'type'  => 'tag',
					'value' => array( 'new', 'featured' ),
				),
			)
		);

		$this->assertCount( 4, $result );
	}

	/**
	 * Test filtering by non-existent tag returns empty.
	 */
	public function testFilterByTagNonExistent(): void {
		$ids    = array_values( $this->created_product_ids );
		$result = FilterEngine::filter(
			$ids,
			array(
				array(
					'type'  => 'tag',
					'value' => 'nonexistent-tag',
				),
			)
		);

		$this->assertSame( array(), $result );
	}

	// ============================================================
	// Stock status filter tests.
	// ============================================================

	/**
	 * Test filtering by instock status.
	 */
	public function testFilterByStockStatusInstock(): void {
		$ids    = array_values( $this->created_product_ids );
		$result = FilterEngine::filter(
			$ids,
			array(
				array(
					'type'  => 'stock_status',
					'value' => 'instock',
				),
			)
		);

		$this->assertCount( 4, $result );
		$this->assertContains( $this->created_product_ids['simple-instock'], $result );
		$this->assertContains( $this->created_product_ids['variable-product'], $result );
		$this->assertContains( $this->created_product_ids['external-product'], $result );
		$this->assertContains( $this->created_product_ids['no-sku-product'], $result );
	}

	/**
	 * Test filtering by outofstock status.
	 */
	public function testFilterByStockStatusOutOfStock(): void {
		$ids    = array_values( $this->created_product_ids );
		$result = FilterEngine::filter(
			$ids,
			array(
				array(
					'type'  => 'stock_status',
					'value' => 'outofstock',
				),
			)
		);

		$this->assertCount( 1, $result );
		$this->assertContains( $this->created_product_ids['simple-outofstock'], $result );
	}

	/**
	 * Test filtering by invalid stock status is skipped.
	 */
	public function testFilterByInvalidStockStatusSkipped(): void {
		$ids      = array_values( $this->created_product_ids );
		$original = $ids;
		$result   = FilterEngine::filter(
			$ids,
			array(
				array(
					'type'  => 'stock_status',
					'value' => 'invalid-status',
				),
			)
		);

		// Invalid filter should be skipped entirely.
		$this->assertSameSorted( $original, $result );
	}

	// ============================================================
	// Price filter tests.
	// ============================================================

	/**
	 * Test filtering by price range (min and max).
	 */
	public function testFilterByPriceRange(): void {
		$ids    = array_values( $this->created_product_ids );
		$result = FilterEngine::filter(
			$ids,
			array(
				array(
					'type'  => 'price',
					'value' => array(
						'min' => 20,
						'max' => 60,
					),
				),
			)
		);

		$this->assertCount( 3, $result );
		$this->assertContains( $this->created_product_ids['simple-instock'], $result );   // 25
		$this->assertContains( $this->created_product_ids['simple-outofstock'], $result ); // 50
		$this->assertContains( $this->created_product_ids['no-sku-product'], $result );   // 30
	}

	/**
	 * Test filtering by minimum price only.
	 */
	public function testFilterByPriceMinOnly(): void {
		$ids    = array_values( $this->created_product_ids );
		$result = FilterEngine::filter(
			$ids,
			array(
				array(
					'type'  => 'price',
					'value' => array( 'min' => 75 ),
				),
			)
		);

		$this->assertCount( 2, $result );
		$this->assertContains( $this->created_product_ids['variable-product'], $result ); // 75
		$this->assertContains( $this->created_product_ids['external-product'], $result ); // 100
	}

	/**
	 * Test filtering by maximum price only.
	 */
	public function testFilterByPriceMaxOnly(): void {
		$ids    = array_values( $this->created_product_ids );
		$result = FilterEngine::filter(
			$ids,
			array(
				array(
					'type'  => 'price',
					'value' => array( 'max' => 30 ),
				),
			)
		);

		$this->assertCount( 2, $result );
		$this->assertContains( $this->created_product_ids['simple-instock'], $result );   // 25
		$this->assertContains( $this->created_product_ids['no-sku-product'], $result );   // 30
	}

	/**
	 * Test filtering by price range with no matches returns empty.
	 */
	public function testFilterByPriceNoMatch(): void {
		$ids    = array_values( $this->created_product_ids );
		$result = FilterEngine::filter(
			$ids,
			array(
				array(
					'type'  => 'price',
					'value' => array(
						'min' => 1000,
						'max' => 2000,
					),
				),
			)
		);

		$this->assertSame( array(), $result );
	}

	// ============================================================
	// SKU filter tests.
	// ============================================================

	/**
	 * Test filtering by single SKU.
	 */
	public function testFilterBySkuSingle(): void {
		$ids    = array_values( $this->created_product_ids );
		$result = FilterEngine::filter(
			$ids,
			array(
				array(
					'type'  => 'sku',
					'value' => 'SIM-001',
				),
			)
		);

		$this->assertCount( 1, $result );
		$this->assertContains( $this->created_product_ids['simple-instock'], $result );
	}

	/**
	 * Test filtering by multiple SKUs.
	 */
	public function testFilterBySkuMultiple(): void {
		$ids    = array_values( $this->created_product_ids );
		$result = FilterEngine::filter(
			$ids,
			array(
				array(
					'type'  => 'sku',
					'value' => array( 'SIM-001', 'VAR-001' ),
				),
			)
		);

		$this->assertCount( 2, $result );
		$this->assertContains( $this->created_product_ids['simple-instock'], $result );
		$this->assertContains( $this->created_product_ids['variable-product'], $result );
	}

	/**
	 * Test filtering by SKU excludes products without SKU.
	 */
	public function testFilterBySkuExcludesNoSkuProducts(): void {
		$ids    = array_values( $this->created_product_ids );
		$result = FilterEngine::filter(
			$ids,
			array(
				array(
					'type'  => 'sku',
					'value' => 'SIM-001',
				),
			)
		);

		$this->assertNotContains( $this->created_product_ids['no-sku-product'], $result );
	}

	/**
	 * Test filtering by non-existent SKU returns empty.
	 */
	public function testFilterBySkuNonExistent(): void {
		$ids    = array_values( $this->created_product_ids );
		$result = FilterEngine::filter(
			$ids,
			array(
				array(
					'type'  => 'sku',
					'value' => 'NONEXISTENT',
				),
			)
		);

		$this->assertSame( array(), $result );
	}

	// ============================================================
	// Filter composition tests.
	// ============================================================

	/**
	 * Test composition: type + category filter.
	 */
	public function testFilterCompositionTypeAndCategory(): void {
		$ids    = array_values( $this->created_product_ids );
		$result = FilterEngine::filter(
			$ids,
			array(
				array(
					'type'  => 'type',
					'value' => 'simple',
				),
				array(
					'type'  => 'category',
					'value' => 'electronics',
				),
			)
		);

		// Only simple products in electronics category.
		$this->assertCount( 2, $result );
		$this->assertContains( $this->created_product_ids['simple-instock'], $result );
		$this->assertContains( $this->created_product_ids['simple-outofstock'], $result );
	}

	/**
	 * Test composition: category + stock status filter.
	 */
	public function testFilterCompositionCategoryAndStockStatus(): void {
		$ids    = array_values( $this->created_product_ids );
		$result = FilterEngine::filter(
			$ids,
			array(
				array(
					'type'  => 'category',
					'value' => 'electronics',
				),
				array(
					'type'  => 'stock_status',
					'value' => 'instock',
				),
			)
		);

		$this->assertCount( 1, $result );
		$this->assertContains( $this->created_product_ids['simple-instock'], $result );
	}

	/**
	 * Test composition: type + price + category.
	 */
	public function testFilterCompositionThreeFilters(): void {
		$ids    = array_values( $this->created_product_ids );
		$result = FilterEngine::filter(
			$ids,
			array(
				array(
					'type'  => 'type',
					'value' => 'simple',
				),
				array(
					'type'  => 'price',
					'value' => array(
						'min' => 20,
						'max' => 60,
					),
				),
				array(
					'type'  => 'category',
					'value' => 'electronics',
				),
			)
		);

		$this->assertCount( 2, $result );
		$this->assertContains( $this->created_product_ids['simple-instock'], $result );
		$this->assertContains( $this->created_product_ids['simple-outofstock'], $result );
	}

	/**
	 * Test composition with invalid filter in the middle (should be skipped).
	 */
	public function testFilterCompositionWithInvalidFilter(): void {
		$ids    = array_values( $this->created_product_ids );
		$result = FilterEngine::filter(
			$ids,
			array(
				array(
					'type'  => 'type',
					'value' => 'simple',
				),
				array(
					'type'  => 'nonexistent',
					'value' => 'x',
				),
				array(
					'type'  => 'category',
					'value' => 'electronics',
				),
			)
		);

		// Invalid filter is skipped; result should match the two valid filters.
		$this->assertCount( 2, $result );
		$this->assertContains( $this->created_product_ids['simple-instock'], $result );
		$this->assertContains( $this->created_product_ids['simple-outofstock'], $result );
	}

	/**
	 * Test deterministic composition: same filters produce same result.
	 */
	public function testFilterCompositionDeterministic(): void {
		$ids    = array_values( $this->created_product_ids );
		$filter = array(
			array(
				'type'  => 'type',
				'value' => 'simple',
			),
			array(
				'type'  => 'category',
				'value' => 'electronics',
			),
		);

		$result1 = FilterEngine::filter( $ids, $filter );
		$result2 = FilterEngine::filter( $ids, $filter );

		$this->assertSameSorted( $result1, $result2 );
	}

	// ============================================================
	// Invalid input handling tests.
	// ============================================================

	/**
	 * Test filter with invalid filter config does not crash.
	 */
	public function testFilterWithInvalidConfigDoesNotCrash(): void {
		$ids    = array_values( $this->created_product_ids );
		$result = FilterEngine::filter(
			$ids,
			array(
				'not-an-array',
				null,
				123,
				array(
					'type'  => 'bad-type',
					'value' => 'x',
				),
			)
		);

		// All invalid filters should be skipped; original IDs returned.
		$this->assertSameSorted( $ids, $result );
	}

	/**
	 * Test filter with null input IDs.
	 */
	public function testFilterWithNullIds(): void {
		$result = FilterEngine::filter( array( null, 0, -1 ), array() );
		$this->assertSame( array(), $result );
	}

	/**
	 * Test filter with string IDs normalized to integers.
	 */
	public function testFilterWithStringIds(): void {
		$ids    = array_map( 'strval', array_values( $this->created_product_ids ) );
		$result = FilterEngine::filter( $ids, array() );
		$this->assertSameSorted( array_values( $this->created_product_ids ), $result );
	}

	/**
	 * Test filter with duplicate IDs.
	 */
	public function testFilterWithDuplicateIds(): void {
		$ids    = array(
			$this->created_product_ids['simple-instock'],
			$this->created_product_ids['simple-instock'],
			$this->created_product_ids['variable-product'],
		);
		$result = FilterEngine::filter( $ids, array() );
		$this->assertCount( 2, $result );
		$this->assertContains( $this->created_product_ids['simple-instock'], $result );
		$this->assertContains( $this->created_product_ids['variable-product'], $result );
	}

	/**
	 * Test filter with nonexistent product IDs.
	 */
	public function testFilterWithNonexistentIds(): void {
		$result = FilterEngine::filter( array( 99999, 99998 ), array() );
		$this->assertCount( 2, $result );
		$this->assertContains( 99998, $result );
		$this->assertContains( 99999, $result );
	}

	/**
	 * Test filter with nonexistent IDs and a valid filter.
	 */
	public function testFilterNonexistentIdsWithValidFilter(): void {
		$result = FilterEngine::filter(
			array( 99999 ),
			array(
				array(
					'type'  => 'type',
					'value' => 'simple',
				),
			)
		);
		$this->assertSame( array(), $result );
	}

	// ============================================================
	// Edge case tests.
	// ============================================================

	/**
	 * Test filter with zero as a product ID.
	 */
	public function testFilterWithZeroId(): void {
		$ids    = array( 0, $this->created_product_ids['simple-instock'] );
		$result = FilterEngine::filter( $ids, array() );
		$this->assertCount( 1, $result );
		$this->assertContains( $this->created_product_ids['simple-instock'], $result );
	}

	/**
	 * Test that validate_filter is public and static.
	 */
	public function testValidateFilterIsPublicStatic(): void {
		$reflection = new \ReflectionClass( FilterEngine::class );
		$method     = $reflection->getMethod( 'validate_filter' );
		$this->assertTrue( $method->isPublic() );
		$this->assertTrue( $method->isStatic() );
	}

	/**
	 * Test that filter() is public and static.
	 */
	public function testFilterMethodIsPublicStatic(): void {
		$reflection = new \ReflectionClass( FilterEngine::class );
		$method     = $reflection->getMethod( 'filter' );
		$this->assertTrue( $method->isPublic() );
		$this->assertTrue( $method->isStatic() );
	}

	// ============================================================
	// Integration with preceding pipeline tests.
	// ============================================================

	/**
	 * Test filter works after ProductQueryEngine results.
	 */
	public function testFilterAfterProductQuery(): void {
		// Use ProductQueryEngine to get all published products.
		$query_ids = \Catalogist\ProductQueryEngine::query( array( 'status' => 'publish' ) );
		$this->assertNotEmpty( $query_ids );

		// Filter the query results by simple type.
		$filtered = FilterEngine::filter(
			$query_ids,
			array(
				array(
					'type'  => 'type',
					'value' => 'simple',
				),
			)
		);

		// Result should be a subset of query results.
		$this->assertIsArray( $filtered );
		foreach ( $filtered as $id ) {
			$this->assertContains( $id, $query_ids );
		}
	}

	/**
	 * Test filter works after VariationEngine expansion.
	 */
	public function testFilterAfterVariationExpand(): void {
		// Get all products first.
		$query_ids = \Catalogist\ProductQueryEngine::query( array( 'status' => 'publish' ) );
		$this->assertNotEmpty( $query_ids );

		// Expand with variations.
		$expanded_ids = \Catalogist\VariationEngine::expand_product_ids(
			$query_ids,
			true
		);
		$this->assertIsArray( $expanded_ids );

		// Filter expanded IDs by simple type.
		$filtered = FilterEngine::filter(
			$expanded_ids,
			array(
				array(
					'type'  => 'type',
					'value' => 'simple',
				),
			)
		);

		// Should still be an array of integers.
		$this->assertIsArray( $filtered );
		foreach ( $filtered as $id ) {
			$this->assertIsInt( $id );
		}
	}

	// ============================================================
	// Helpers.
	// ============================================================

	/**
	 * Create a WooCommerce product for testing.
	 *
	 * @param string $product_type Product type.
	 * @param string $title        Product title.
	 * @param array  $args         Additional product args.
	 * @return int The created product ID.
	 */
	private function create_product( string $product_type, string $title, array $args = array() ): int {
		$sku        = $args['sku'] ?? '';
		$price      = $args['price'] ?? '0';
		$stock_qty  = $args['stock_qty'] ?? 10;
		$categories = $args['categories'] ?? array();
		$tags       = $args['tags'] ?? array();
		$stock      = $stock_qty > 0 ? 'instock' : 'outofstock';

		// Delete any existing products with this SKU to ensure clean state.
		if ( '' !== $sku ) {
			global $wpdb;

			// Delete products using this SKU.
		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Test fixture cleanup.
			$existing_ids = get_posts(
				array(
					'post_type'      => 'product',
					'post_status'    => 'any',
					'meta_key'       => '_sku',
					'meta_value'     => $sku,
					'posts_per_page' => -1,
					'fields'         => 'ids',
				)
			);

			foreach ( $existing_ids as $existing_id ) {
				wp_delete_post( (int) $existing_id, true );
			}

			// Remove ALL WooCommerce lookup rows for this SKU,
			// including orphaned rows.
			$wpdb->delete(
				$wpdb->wc_product_meta_lookup,
				array( 'sku' => $sku ),
				array( '%s' )
			);

			wp_cache_flush();
		}

		// Create product categories if needed.
		$term_ids = array();

		foreach ( $categories as $cat_slug ) {
			$term = get_term_by( 'slug', $cat_slug, 'product_cat' );

			if ( ! $term || is_wp_error( $term ) ) {
				$term = wp_insert_term( $cat_slug, 'product_cat' );
			}

			if ( ! is_wp_error( $term ) ) {
				$term_ids[] = (int) $term->term_id;
			}
		}

		// Create product tags if needed.
		$tag_term_ids = array();

		foreach ( $tags as $tag_slug ) {
			$term = get_term_by( 'slug', $tag_slug, 'product_tag' );

			if ( ! $term || is_wp_error( $term ) ) {
				$term = wp_insert_term( $tag_slug, 'product_tag' );
			}

			if ( ! is_wp_error( $term ) ) {
				$tag_term_ids[] = (int) $term->term_id;
			}
		}

		// Insert product post.
		$product_id = wp_insert_post(
			array(
				'post_title'   => $title,
				'post_type'    => 'product',
				'post_status'  => 'publish',
				'post_content' => '',
			),
			true
		);

		if ( is_wp_error( $product_id ) ) {
			return 0;
		}

		// Set product data via WooCommerce.
		$product = wc_get_product( $product_id );

		if ( $product ) {
			if ( '' !== $sku ) {
				$product->set_sku( $sku );
			}

			$product->set_stock_status( $stock );
			$product->set_regular_price( $price );
			$product->set_price( $price );

			$product->save();

			// Set product type after WooCommerce product save.
			wp_set_object_terms(
				$product_id,
				$product_type,
				'product_type'
			);

			wp_cache_flush();
		}

		// Set categories.
		if ( ! empty( $term_ids ) ) {
			wp_set_object_terms(
				$product_id,
				$term_ids,
				'product_cat'
			);
		}

		// Set tags.
		if ( ! empty( $tag_term_ids ) ) {
			wp_set_object_terms(
				$product_id,
				$tag_term_ids,
				'product_tag'
			);
		}

		return $product_id;
	}

	/**
	 * Assert two arrays are the same regardless of order.
	 *
	 * @param array $expected Expected values.
	 * @param array $actual   Actual values.
	 */
	private function assertSameSorted( array $expected, array $actual ): void {
		sort( $expected );
		sort( $actual );
		$this->assertSame( $expected, $actual );
	}
}
