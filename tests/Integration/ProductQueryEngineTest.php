<?php

declare(strict_types=1);

namespace Catalogist\Tests\Integration;

use Catalogist\ProductQueryEngine;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for ProductQueryEngine.
 *
 * These tests require a full WordPress + WooCommerce environment (run via docker compose).
 */
final class ProductQueryEngineTest extends TestCase {

	/**
	 * Test IDs created during setUp.
	 *
	 * @var array<string, int>
	 */
	private array $created_product_ids = array();

	/**
	 * Set up test products before each test.
	 */
	protected function setUp(): void {
		parent::setUp();

		// Create test products if they don't exist.
		$this->created_product_ids = $this->create_test_products();
	}

	/**
	 * Clean up test products after each test.
	 */
	protected function tearDown(): void {
		$this->delete_test_products();
		parent::tearDown();
	}

	/**
	 * Create test products for integration testing.
	 *
	 * @return array<string, int> Associative array of product names to IDs.
	 */
	private function create_test_products(): array {
		$products = array(
			'blue-widget'    => array(
				'title'      => 'Blue Widget',
				'type'       => 'simple',
				'sku'        => 'BW-001',
				'categories' => array( 'widgets' ),
				'tags'       => array( 'blue', 'new' ),
				'status'     => 'publish',
				'stock'      => 'instock',
			),
			'red-widget'     => array(
				'title'      => 'Red Widget',
				'type'       => 'simple',
				'sku'        => 'RW-001',
				'categories' => array( 'widgets' ),
				'tags'       => array( 'red' ),
				'status'     => 'publish',
				'stock'      => 'instock',
			),
			'blue-gadget'    => array(
				'title'      => 'Blue Gadget',
				'type'       => 'variable',
				'sku'        => 'BG-001',
				'categories' => array( 'gadgets' ),
				'tags'       => array( 'blue', 'premium' ),
				'status'     => 'publish',
				'stock'      => 'outofstock',
			),
			'draft-product'  => array(
				'title'      => 'Draft Product',
				'type'       => 'simple',
				'sku'        => 'DP-001',
				'categories' => array( 'widgets' ),
				'tags'       => array( 'draft' ),
				'status'     => 'draft',
				'stock'      => 'instock',
			),
			'no-sku-product' => array(
				'title'      => 'No SKU Product',
				'type'       => 'grouped',
				'sku'        => '',
				'categories' => array( 'gadgets' ),
				'tags'       => array( 'grouped' ),
				'status'     => 'publish',
				'stock'      => 'instock',
			),
		);

		$ids = array();

		foreach ( $products as $slug => $data ) {
			// Delete any existing product with this slug to ensure clean state.
			$existing = get_page_by_path( $slug, OBJECT, 'product' );
			if ( $existing ) {
				wp_delete_post( (int) $existing->ID, true );
			}

			// Create product categories if needed.
			$term_ids = array();
			foreach ( $data['categories'] as $cat_slug ) {
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
			foreach ( $data['tags'] as $tag_slug ) {
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
					'post_type'   => 'product',
					'post_slug'   => $slug,
					'post_title'  => $data['title'],
					'post_status' => $data['status'],
				),
				true
			);

			if ( is_wp_error( $product_id ) ) {
				continue;
			}

			// Set product data via WooCommerce.
			$product = wc_get_product( $product_id );
			if ( $product ) {
				if ( $data['sku'] ) {
					$product->set_sku( $data['sku'] );
				}
				$product->set_stock_status( $data['stock'] );
				$product->save();
				// Set product type AFTER save (save() overwrites type from object default).
				wp_set_object_terms( $product_id, $data['type'], 'product_type' );
				wp_cache_flush();
			}

			// Set terms.
			if ( ! empty( $term_ids ) ) {
				wp_set_object_terms( $product_id, $term_ids, 'product_cat' );
			}
			if ( ! empty( $tag_term_ids ) ) {
				wp_set_object_terms( $product_id, $tag_term_ids, 'product_tag' );
			}

			$ids[ $slug ] = (int) $product_id;
		}

		return $ids;
	}

	/**
	 * Delete test products.
	 */
	private function delete_test_products(): void {
		foreach ( $this->created_product_ids as $slug => $id ) {
			wp_delete_post( $id, true );
		}
	}

	// ============================================================
	// Basic Existence Tests.
	// ============================================================

	/**
	 * Test that ProductQueryEngine class is loadable in WordPress context.
	 */
	public function testClassExists(): void {
		$this->assertTrue( class_exists( ProductQueryEngine::class ) );
	}

	/**
	 * Test that WooCommerce product post type exists.
	 */
	public function testProductPostTypeAvailable(): void {
		$this->assertTrue( ProductQueryEngine::is_product_post_type_available() );
	}

	// ============================================================
	// Product ID Filtering.
	// ============================================================

	/**
	 * Test querying by specific product IDs.
	 */
	public function testQueryByIds(): void {
		$ids       = $this->created_product_ids;
		$first_id  = $ids['blue-widget'];
		$second_id = $ids['red-widget'];

		$result = ProductQueryEngine::query( array( 'ids' => array( $first_id, $second_id ) ) );

		$this->assertIsArray( $result );
		$this->assertCount( 2, $result );
		$this->assertContains( $first_id, $result );
		$this->assertContains( $second_id, $result );
	}

	/**
	 * Test that invalid IDs are ignored.
	 */
	public function testQueryByIdsWithInvalidIds(): void {
		$ids      = $this->created_product_ids;
		$first_id = $ids['blue-widget'];

		$result = ProductQueryEngine::query( array( 'ids' => array( $first_id, 99999, -1, 0 ) ) );

		$this->assertIsArray( $result );
		$this->assertCount( 1, $result );
		$this->assertContains( $first_id, $result );
	}

	/**
	 * Test that empty IDs array returns all products (default behavior).
	 */
	public function testQueryWithEmptyIds(): void {
		$result = ProductQueryEngine::query( array( 'ids' => array() ) );

		$this->assertIsArray( $result );
		// Should return publish products (default).
		$this->assertNotEmpty( $result );
	}

	/**
	 * Test that nonexistent IDs return empty result.
	 */
	public function testQueryWithNonexistentIds(): void {
		$result = ProductQueryEngine::query( array( 'ids' => array( 99999, 99998 ) ) );

		$this->assertIsArray( $result );
		$this->assertCount( 0, $result );
	}

	// ============================================================
	// Search.
	// ============================================================

	/**
	 * Test text search by product title.
	 */
	public function testSearchByTitle(): void {
		$result = ProductQueryEngine::query( array( 'search' => 'Blue Widget' ) );

		$this->assertIsArray( $result );
		$this->assertNotEmpty( $result );
		// Should include blue-widget.
		$this->assertContains( $this->created_product_ids['blue-widget'], $result );
	}

	/**
	 * Test search with no matches.
	 */
	public function testSearchNoMatches(): void {
		$result = ProductQueryEngine::query( array( 'search' => 'zzznonexistentzzz' ) );

		$this->assertIsArray( $result );
		$this->assertCount( 0, $result );
	}

	/**
	 * Test empty search returns default results.
	 */
	public function testEmptySearch(): void {
		$with_search    = ProductQueryEngine::query( array( 'search' => '' ) );
		$without_search = ProductQueryEngine::query();

		// Both should return the same default set (publish products).
		$this->assertEquals( $without_search, $with_search );
	}

	// ============================================================
	// Category Filtering.
	// ============================================================

	/**
	 * Test filtering by category slug.
	 */
	public function testFilterByCategory(): void {
		$result = ProductQueryEngine::query( array( 'category' => array( 'widgets' ) ) );

		$this->assertIsArray( $result );
		$this->assertNotEmpty( $result );
		// Should include widget products.
		$this->assertContains( $this->created_product_ids['blue-widget'], $result );
		$this->assertContains( $this->created_product_ids['red-widget'], $result );
		// Should NOT include gadget products.
		$this->assertNotContains( $this->created_product_ids['blue-gadget'], $result );
	}

	/**
	 * Test filtering by nonexistent category ignores the filter (returns default).
	 */
	public function testFilterByNonexistentCategory(): void {
		$result = ProductQueryEngine::query( array( 'category' => array( 'nonexistent-category-xyz' ) ) );

		$this->assertIsArray( $result );
		// Nonexistent category is silently ignored, so default query runs.
		$this->assertNotEmpty( $result );
	}

	/**
	 * Test filtering by multiple categories (OR within same taxonomy - WP default).
	 */
	public function testFilterByMultipleCategories(): void {
		$result = ProductQueryEngine::query( array( 'category' => array( 'widgets', 'gadgets' ) ) );

		$this->assertIsArray( $result );
		$this->assertNotEmpty( $result );
		// Should include products from both categories.
		$this->assertContains( $this->created_product_ids['blue-widget'], $result );
		$this->assertContains( $this->created_product_ids['blue-gadget'], $result );
	}

	// ============================================================
	// Tag Filtering.
	// ============================================================

	/**
	 * Test filtering by tag slug.
	 */
	public function testFilterByTag(): void {
		$result = ProductQueryEngine::query( array( 'tag' => array( 'blue' ) ) );

		$this->assertIsArray( $result );
		$this->assertNotEmpty( $result );
		// Should include products with 'blue' tag.
		$this->assertContains( $this->created_product_ids['blue-widget'], $result );
		$this->assertContains( $this->created_product_ids['blue-gadget'], $result );
		// Should NOT include red-widget.
		$this->assertNotContains( $this->created_product_ids['red-widget'], $result );
	}

	/**
	 * Test filtering by nonexistent tag ignores the filter (returns default).
	 */
	public function testFilterByNonexistentTag(): void {
		$result = ProductQueryEngine::query( array( 'tag' => array( 'nonexistent-tag-xyz' ) ) );

		$this->assertIsArray( $result );
		// Nonexistent tag is silently ignored, so default query runs.
		$this->assertNotEmpty( $result );
	}

	/**
	 * Test filtering by multiple tags (OR within same taxonomy).
	 */
	public function testFilterByMultipleTags(): void {
		$result = ProductQueryEngine::query( array( 'tag' => array( 'blue', 'red' ) ) );

		$this->assertIsArray( $result );
		$this->assertNotEmpty( $result );
		// Should include products with either tag.
		$this->assertContains( $this->created_product_ids['blue-widget'], $result );
		$this->assertContains( $this->created_product_ids['red-widget'], $result );
		$this->assertContains( $this->created_product_ids['blue-gadget'], $result );
	}

	// ============================================================
	// SKU Filtering.
	// ============================================================

	/**
	 * Test filtering by SKU.
	 */
	public function testFilterBySku(): void {
		$result = ProductQueryEngine::query( array( 'sku' => array( 'BW-001' ) ) );

		$this->assertIsArray( $result );
		$this->assertNotEmpty( $result );
		$this->assertContains( $this->created_product_ids['blue-widget'], $result );
	}

	/**
	 * Test filtering by nonexistent SKU returns empty.
	 */
	public function testFilterByNonexistentSku(): void {
		$result = ProductQueryEngine::query( array( 'sku' => array( 'NONEXISTENT-SKU' ) ) );

		$this->assertIsArray( $result );
		$this->assertCount( 0, $result );
	}

	/**
	 * Test filtering by multiple SKUs.
	 */
	public function testFilterByMultipleSkus(): void {
		$result = ProductQueryEngine::query( array( 'sku' => array( 'BW-001', 'RW-001' ) ) );

		$this->assertIsArray( $result );
		$this->assertCount( 2, $result );
		$this->assertContains( $this->created_product_ids['blue-widget'], $result );
		$this->assertContains( $this->created_product_ids['red-widget'], $result );
	}

	/**
	 * Test that products without SKU are excluded when filtering by SKU.
	 */
	public function testFilterBySkuExcludesNoSkuProducts(): void {
		$result = ProductQueryEngine::query( array( 'sku' => array( 'BW-001' ) ) );

		$this->assertIsArray( $result );
		$this->assertNotContains( $this->created_product_ids['no-sku-product'], $result );
	}

	// ============================================================
	// Product Type Filtering.
	// ============================================================

	/**
	 * Test filtering by product type.
	 */
	public function testFilterByType(): void {
		$result = ProductQueryEngine::query( array( 'type' => 'simple' ) );

		$this->assertIsArray( $result );
		$this->assertNotEmpty( $result );
		$this->assertContains( $this->created_product_ids['blue-widget'], $result );
		$this->assertContains( $this->created_product_ids['red-widget'], $result );
		// Should NOT include variable type.
		$this->assertNotContains( $this->created_product_ids['blue-gadget'], $result );
	}

	/**
	 * Test filtering by variable type.
	 */
	public function testFilterByVariableType(): void {
		$result = ProductQueryEngine::query( array( 'type' => 'variable' ) );

		$this->assertIsArray( $result );
		$this->assertNotEmpty( $result );
		$this->assertContains( $this->created_product_ids['blue-gadget'], $result );
	}

	/**
	 * Test filtering by invalid product type returns empty (filtered out).
	 */
	public function testFilterByInvalidType(): void {
		$result = ProductQueryEngine::query( array( 'type' => 'invalid-type-xyz' ) );

		$this->assertIsArray( $result );
		// Invalid type should be ignored, so we get default results (all publish products).
		$this->assertNotEmpty( $result );
	}

	// ============================================================
	// Status Filtering.
	// ============================================================

	/**
	 * Test filtering by status (default is publish).
	 */
	public function testDefaultStatusIsPublish(): void {
		$result = ProductQueryEngine::query();

		$this->assertIsArray( $result );
		// Should only include publish products.
		$this->assertNotContains( $this->created_product_ids['draft-product'], $result );
	}

	/**
	 * Test filtering by draft status.
	 */
	public function testFilterByDraftStatus(): void {
		$result = ProductQueryEngine::query( array( 'status' => 'draft' ) );

		$this->assertIsArray( $result );
		$this->assertNotEmpty( $result );
		$this->assertContains( $this->created_product_ids['draft-product'], $result );
		// Should NOT include publish products.
		$this->assertNotContains( $this->created_product_ids['blue-widget'], $result );
	}

	/**
	 * Test filtering by invalid status returns default.
	 */
	public function testFilterByInvalidStatus(): void {
		$result = ProductQueryEngine::query( array( 'status' => 'invalid-status-xyz' ) );

		$this->assertIsArray( $result );
		// Invalid status should be ignored, returning default (publish).
		$this->assertNotEmpty( $result );
		$this->assertNotContains( $this->created_product_ids['draft-product'], $result );
	}

	// ============================================================
	// Stock Status Filtering.
	// ============================================================

	/**
	 * Test filtering by stock status (instock).
	 */
	public function testFilterByInStockStatus(): void {
		$result = ProductQueryEngine::query( array( 'stock_status' => 'instock' ) );

		$this->assertIsArray( $result );
		$this->assertNotEmpty( $result );
		$this->assertContains( $this->created_product_ids['blue-widget'], $result );
		$this->assertContains( $this->created_product_ids['red-widget'], $result );
		// Should NOT include out-of-stock.
		$this->assertNotContains( $this->created_product_ids['blue-gadget'], $result );
	}

	/**
	 * Test filtering by stock status (outofstock).
	 */
	public function testFilterByOutOfStockStatus(): void {
		$result = ProductQueryEngine::query( array( 'stock_status' => 'outofstock' ) );

		$this->assertIsArray( $result );
		$this->assertNotEmpty( $result );
		$this->assertContains( $this->created_product_ids['blue-gadget'], $result );
		// Should NOT include instock products.
		$this->assertNotContains( $this->created_product_ids['blue-widget'], $result );
	}

	/**
	 * Test filtering by invalid stock status returns default.
	 */
	public function testFilterByInvalidStockStatus(): void {
		$result = ProductQueryEngine::query( array( 'stock_status' => 'invalid-stock-xyz' ) );

		$this->assertIsArray( $result );
		// Invalid stock status should be ignored, returning default.
		$this->assertNotEmpty( $result );
	}

	// ============================================================
	// Sorting.
	// ============================================================

	/**
	 * Test sorting by title ascending.
	 */
	public function testSortByTitleAscending(): void {
		$result = ProductQueryEngine::query(
			array(
				'orderby' => 'title',
				'order'   => 'ASC',
				'status'  => 'publish',
			)
		);

		$this->assertIsArray( $result );
		$this->assertNotEmpty( $result );

		// Get titles to verify order.
		$titles = array();
		foreach ( $result as $id ) {
			$titles[] = get_the_title( $id );
		}

		$sorted_titles = $titles;
		sort( $sorted_titles );
		$this->assertSame( $sorted_titles, $titles );
	}

	/**
	 * Test sorting by title descending.
	 */
	public function testSortByTitleDescending(): void {
		$result = ProductQueryEngine::query(
			array(
				'orderby' => 'title',
				'order'   => 'DESC',
				'status'  => 'publish',
			)
		);

		$this->assertIsArray( $result );
		$this->assertNotEmpty( $result );

		// Get titles to verify order.
		$titles = array();
		foreach ( $result as $id ) {
			$titles[] = get_the_title( $id );
		}

		$sorted_titles = $titles;
		rsort( $sorted_titles );
		$this->assertSame( $sorted_titles, $titles );
	}

	/**
	 * Test sorting by date.
	 */
	public function testSortByDate(): void {
		$result = ProductQueryEngine::query(
			array(
				'orderby' => 'date',
				'order'   => 'DESC',
				'status'  => 'publish',
			)
		);

		$this->assertIsArray( $result );
		$this->assertNotEmpty( $result );
		// Should return products ordered by date descending.
	}

	/**
	 * Test invalid orderby is ignored (falls back to default).
	 */
	public function testInvalidOrderbyIgnored(): void {
		$result_default = ProductQueryEngine::query( array( 'status' => 'publish' ) );
		$result_invalid = ProductQueryEngine::query(
			array(
				'orderby' => 'invalid-orderby-xyz',
				'status'  => 'publish',
			)
		);

		// Both should return the same set of IDs (invalid orderby is ignored).
		$this->assertEquals( $result_default, $result_invalid );
	}

	/**
	 * Test invalid order direction is ignored.
	 */
	public function testInvalidOrderIgnored(): void {
		$result_default = ProductQueryEngine::query(
			array(
				'orderby' => 'title',
				'status'  => 'publish',
			)
		);
		$result_invalid = ProductQueryEngine::query(
			array(
				'orderby' => 'title',
				'order'   => 'invalid-order-xyz',
				'status'  => 'publish',
			)
		);

		// Both should return the same ordered results.
		$this->assertEquals( $result_default, $result_invalid );
	}

	// ============================================================
	// Pagination.
	// ============================================================

	/**
	 * Test pagination with per_page.
	 */
	public function testPagination(): void {
		$result_page1 = ProductQueryEngine::query(
			array(
				'status'   => 'publish',
				'page'     => 1,
				'per_page' => 2,
			)
		);

		$result_page2 = ProductQueryEngine::query(
			array(
				'status'   => 'publish',
				'page'     => 2,
				'per_page' => 2,
			)
		);

		$this->assertIsArray( $result_page1 );
		$this->assertIsArray( $result_page2 );
		$this->assertCount( 2, $result_page1 );
		$this->assertCount( 2, $result_page2 );
		// Pages should not overlap.
		$this->assertCount( 0, array_intersect( $result_page1, $result_page2 ) );
	}

	/**
	 * Test that page 0 is treated as page 1.
	 */
	public function testPaginationZeroPage(): void {
		$result = ProductQueryEngine::query(
			array(
				'status'   => 'publish',
				'page'     => 0,
				'per_page' => 2,
			)
		);

		$this->assertIsArray( $result );
		// Page 0 should be treated as page 1; return first page of results.
		$this->assertNotEmpty( $result );
	}

	/**
	 * Test negative per_page is treated as 1.
	 */
	public function testPaginationNegativePerPage(): void {
		$result = ProductQueryEngine::query(
			array(
				'status'   => 'publish',
				'page'     => 1,
				'per_page' => -5,
			)
		);

		$this->assertIsArray( $result );
		// Should not crash, returns at least 1.
		$this->assertNotEmpty( $result );
	}

	// ============================================================
	// Count Method.
	// ============================================================

	/**
	 * Test count matches query result count.
	 */
	public function testCountMatchesQuery(): void {
		$query_ids = ProductQueryEngine::query( array( 'status' => 'publish' ) );
		$count     = ProductQueryEngine::count( array( 'status' => 'publish' ) );

		$this->assertSame( count( $query_ids ), $count );
	}

	/**
	 * Test count with filters.
	 */
	public function testCountWithFilters(): void {
		$query_ids = ProductQueryEngine::query(
			array(
				'type'   => 'simple',
				'status' => 'publish',
			)
		);
		$count     = ProductQueryEngine::count(
			array(
				'type'   => 'simple',
				'status' => 'publish',
			)
		);

		$this->assertSame( count( $query_ids ), $count );
	}

	// ============================================================
	// Filter Composition.
	// ============================================================

	/**
	 * Test combining multiple filters (AND across filter types).
	 */
	public function testFilterComposition(): void {
		// Blue widget: category=widgets, tag=blue, type=simple, stock=instock.
		$result = ProductQueryEngine::query(
			array(
				'category'     => array( 'widgets' ),
				'tag'          => array( 'blue' ),
				'type'         => 'simple',
				'stock_status' => 'instock',
				'status'       => 'publish',
			)
		);

		$this->assertIsArray( $result );
		$this->assertCount( 1, $result );
		$this->assertContains( $this->created_product_ids['blue-widget'], $result );
	}

	/**
	 * Test that combining category + tag returns intersection.
	 */
	public function testCategoryAndTagComposition(): void {
		// blue-widget has category=widgets AND tag=blue.
		// blue-gadget has category=gadgets AND tag=blue.
		// red-widget has category=widgets AND tag=red.
		$result = ProductQueryEngine::query(
			array(
				'category' => array( 'widgets' ),
				'tag'      => array( 'blue' ),
			)
		);

		$this->assertIsArray( $result );
		$this->assertCount( 1, $result );
		$this->assertContains( $this->created_product_ids['blue-widget'], $result );
		// blue-gadget is in gadgets, not widgets.
		$this->assertNotContains( $this->created_product_ids['blue-gadget'], $result );
		// red-widget has tag=red, not blue.
		$this->assertNotContains( $this->created_product_ids['red-widget'], $result );
	}

	/**
	 * Test that search + category compose correctly.
	 */
	public function testSearchAndCategoryComposition(): void {
		$result = ProductQueryEngine::query(
			array(
				'search'   => 'Widget',
				'category' => array( 'widgets' ),
			)
		);

		$this->assertIsArray( $result );
		$this->assertNotEmpty( $result );
		// Should include both widget products.
		$this->assertContains( $this->created_product_ids['blue-widget'], $result );
		$this->assertContains( $this->created_product_ids['red-widget'], $result );
		// Should NOT include gadget products.
		$this->assertNotContains( $this->created_product_ids['blue-gadget'], $result );
	}

	// ============================================================
	// Edge Cases.
	// ============================================================

	/**
	 * Test query with no matching products returns empty array.
	 */
	public function testNoMatchingProducts(): void {
		$result = ProductQueryEngine::query(
			array(
				'search' => 'nonexistent-product-name',
				'status' => 'publish',
			)
		);

		$this->assertIsArray( $result );
		$this->assertCount( 0, $result );
	}

	/**
	 * Test query with all invalid filters returns default results.
	 */
	public function testAllInvalidFiltersReturnsDefault(): void {
		$result = ProductQueryEngine::query(
			array(
				'category'     => array( 'nonexistent-cat' ),
				'tag'          => array( 'nonexistent-tag' ),
				'type'         => 'nonexistent-type',
				'status'       => 'nonexistent-status',
				'stock_status' => 'nonexistent-stock',
			)
		);

		// All invalid filters should be silently ignored, returning default (publish products).
		$this->assertIsArray( $result );
		$this->assertNotEmpty( $result );
	}

	/**
	 * Test that deleted products are excluded.
	 */
	public function testDeletedProductsExcluded(): void {
		// Create and then delete a product.
		$temp_id = wp_insert_post(
			array(
				'post_type'   => 'product',
				'post_title'  => 'Temp Product To Delete',
				'post_status' => 'publish',
			)
		);

		// It should appear before deletion.
		$before = ProductQueryEngine::query( array( 'ids' => array( $temp_id ) ) );
		$this->assertCount( 1, $before );

		// Delete it.
		wp_delete_post( $temp_id, true );

		// It should not appear after deletion.
		$after = ProductQueryEngine::query( array( 'ids' => array( $temp_id ) ) );
		$this->assertCount( 0, $after );
	}

	/**
	 * Test query returns integers, not strings.
	 */
	public function testQueryReturnsIntegerIds(): void {
		$result = ProductQueryEngine::query( array( 'ids' => array( $this->created_product_ids['blue-widget'] ) ) );

		$this->assertIsArray( $result );
		$this->assertCount( 1, $result );
		$this->assertIsInt( $result[0] );
		$this->assertSame( $this->created_product_ids['blue-widget'], $result[0] );
	}

	/**
	 * Test count returns integer.
	 */
	public function testCountReturnsInteger(): void {
		$count = ProductQueryEngine::count( array( 'status' => 'publish' ) );

		$this->assertIsInt( $count );
		$this->assertGreaterThan( 0, $count );
	}
}
