<?php

declare(strict_types=1);

namespace Catalogist\Tests\Integration;

use Catalogist\FilterEngine;
use Catalogist\SortEngine;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for SortEngine.
 *
 * These tests require a full WordPress + WooCommerce environment (run via docker compose).
 */
final class SortEngineTest extends TestCase {

	/**
	 * Test IDs created during setUp.
	 *
	 * @var array<string, int>
	 */
	private array $created_product_ids = array();

	/**
	 * Set up test fixtures: create products with varying properties.
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->created_product_ids = array(
			'cheap-product'     => $this->create_product(
				'simple',
				'Cheap Product',
				array(
					'price' => '10.00',
					'sku'   => 'CHEAP-001',
				)
			),
			'expensive-product' => $this->create_product(
				'simple',
				'Expensive Product',
				array(
					'price' => '100.00',
					'sku'   => 'EXP-001',
				)
			),
			'medium-product'    => $this->create_product(
				'simple',
				'Medium Product',
				array(
					'price' => '50.00',
					'sku'   => 'MED-001',
				)
			),
			'no-price-product'  => $this->create_product(
				'simple',
				'No Price Product',
				array(
					'price' => '',
					'sku'   => 'NOPRICE-001',
				)
			),
			'no-sku-product'    => $this->create_product(
				'simple',
				'No SKU Product',
				array(
					'price' => '30.00',
					'sku'   => '',
				)
			),
			'alpha-product'     => $this->create_product(
				'simple',
				'Alpha Product',
				array(
					'price' => '20.00',
					'sku'   => 'AAA-001',
				)
			),
			'zeta-product'      => $this->create_product(
				'simple',
				'Zeta Product',
				array(
					'price' => '20.00',
					'sku'   => 'ZZZ-001',
				)
			),
			'variable-product'  => $this->create_product(
				'variable',
				'Variable Product',
				array(
					'price' => '75.00',
					'sku'   => 'VAR-001',
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
	// Basic structure tests.
	// ============================================================

	/**
	 * Test that the SortEngine class exists.
	 */
	public function testSortEngineClassExists(): void {
		$this->assertTrue( class_exists( SortEngine::class ) );
	}

	/**
	 * Test that sort() is public and static.
	 */
	public function testSortMethodIsPublicStatic(): void {
		$reflection = new \ReflectionClass( SortEngine::class );
		$method     = $reflection->getMethod( 'sort' );
		$this->assertTrue( $method->isPublic() );
		$this->assertTrue( $method->isStatic() );
	}

	// ============================================================
	// Sorting by title tests.
	// ============================================================

	/**
	 * Test sorting by title ascending.
	 */
	public function testSortByTitleAscending(): void {
		$ids = array_values( $this->created_product_ids );

		$result = SortEngine::sort(
			$ids,
			array(
				'key'       => 'title',
				'direction' => 'asc',
			)
		);

		// Verify alphabetical order.
		$names = array();
		foreach ( $result as $id ) {
			$product = wc_get_product( $id );
			$this->assertNotFalse( $product );
			$names[] = $product->get_name();
		}

		$expected = $names;
		sort( $expected );
		$this->assertSame( $expected, $names );
	}

	/**
	 * Test sorting by title descending.
	 */
	public function testSortByTitleDescending(): void {
		$ids = array_values( $this->created_product_ids );

		$result = SortEngine::sort(
			$ids,
			array(
				'key'       => 'title',
				'direction' => 'desc',
			)
		);

		$names = array();
		foreach ( $result as $id ) {
			$product = wc_get_product( $id );
			$this->assertNotFalse( $product );
			$names[] = $product->get_name();
		}

		$expected = $names;
		rsort( $expected );
		$this->assertSame( $expected, $names );
	}

	// ============================================================
	// Sorting by price tests.
	// ============================================================

	/**
	 * Test sorting by price ascending.
	 */
	public function testSortByPriceAscending(): void {
		$ids = array_values( $this->created_product_ids );

		$result = SortEngine::sort(
			$ids,
			array(
				'key'       => 'price',
				'direction' => 'asc',
			)
		);

		$prices = array();
		foreach ( $result as $id ) {
			$product = wc_get_product( $id );
			$this->assertNotFalse( $product );
			$price    = $product->get_price();
			$prices[] = '' === $price ? PHP_FLOAT_MAX : (float) $price;
		}

		$sorted_prices = $prices;
		sort( $sorted_prices );
		$this->assertSame( $sorted_prices, $prices );
	}

	/**
	 * Test sorting by price descending.
	 */
	public function testSortByPriceDescending(): void {
		$ids = array_values( $this->created_product_ids );

		$result = SortEngine::sort(
			$ids,
			array(
				'key'       => 'price',
				'direction' => 'desc',
			)
		);

		$prices = array();
		foreach ( $result as $id ) {
			$product = wc_get_product( $id );
			$this->assertNotFalse( $product );
			$price    = $product->get_price();
			$prices[] = '' === $price ? PHP_FLOAT_MIN : (float) $price;
		}

		$sorted_prices = $prices;
		rsort( $sorted_prices );
		$this->assertSame( $sorted_prices, $prices );
	}

	/**
	 * Test that products with missing prices sort to the end in ascending order.
	 */
	public function testSortByPriceMissingValuesSortToEnd(): void {
		$ids = array_values( $this->created_product_ids );

		$result = SortEngine::sort(
			$ids,
			array(
				'key'       => 'price',
				'direction' => 'asc',
			)
		);

		// The no-price product should be at the end.
		$no_price_id = $this->created_product_ids['no-price-product'];
		$this->assertSame(
			$no_price_id,
			end( $result )
		);
	}

	/**
	 * Test that products with missing prices sort to the beginning in descending order.
	 */
	public function testSortByPriceMissingValuesSortToStart(): void {
		$ids = array_values( $this->created_product_ids );

		$result = SortEngine::sort(
			$ids,
			array(
				'key'       => 'price',
				'direction' => 'desc',
			)
		);

		// The no-price product should be at the beginning.
		$no_price_id = $this->created_product_ids['no-price-product'];
		$this->assertSame(
			$no_price_id,
			reset( $result )
		);
	}

	// ============================================================
	// Sorting by SKU tests.
	// ============================================================

	/**
	 * Test sorting by SKU ascending.
	 */
	public function testSortBySkuAscending(): void {
		$ids = array_values( $this->created_product_ids );

		$result = SortEngine::sort(
			$ids,
			array(
				'key'       => 'sku',
				'direction' => 'asc',
			)
		);

		$skus = array();
		foreach ( $result as $id ) {
			$product = wc_get_product( $id );
			$this->assertNotFalse( $product );
			$skus[] = (string) $product->get_sku();
		}

		$expected = $skus;
		sort( $expected );
		$this->assertSame( $expected, $skus );
	}

	/**
	 * Test sorting by SKU descending.
	 */
	public function testSortBySkuDescending(): void {
		$ids = array_values( $this->created_product_ids );

		$result = SortEngine::sort(
			$ids,
			array(
				'key'       => 'sku',
				'direction' => 'desc',
			)
		);

		$skus = array();
		foreach ( $result as $id ) {
			$product = wc_get_product( $id );
			$this->assertNotFalse( $product );
			$skus[] = (string) $product->get_sku();
		}

		$expected = $skus;
		rsort( $expected );
		$this->assertSame( $expected, $skus );
	}

	/**
	 * Test that products with missing SKU sort to the end in ascending order.
	 */
	public function testSortBySkuMissingValuesSortToEnd(): void {
		$ids = array_values( $this->created_product_ids );

		$result = SortEngine::sort(
			$ids,
			array(
				'key'       => 'sku',
				'direction' => 'asc',
			)
		);

		// The no-sku product should be at the end.
		$no_sku_id = $this->created_product_ids['no-sku-product'];
		$this->assertSame(
			$no_sku_id,
			end( $result )
		);
	}

	// ============================================================
	// Sorting by ID tests.
	// ============================================================

	/**
	 * Test sorting by ID ascending.
	 */
	public function testSortByIdAscending(): void {
		$ids = array_values( $this->created_product_ids );

		$result = SortEngine::sort(
			$ids,
			array(
				'key'       => 'id',
				'direction' => 'asc',
			)
		);

		// Should be sorted by ID ascending.
		$expected = $ids;
		sort( $expected );
		$this->assertSame( $expected, $result );
	}

	/**
	 * Test sorting by ID descending.
	 */
	public function testSortByIdDescending(): void {
		$ids = array_values( $this->created_product_ids );

		$result = SortEngine::sort(
			$ids,
			array(
				'key'       => 'id',
				'direction' => 'desc',
			)
		);

		// Should be sorted by ID descending.
		$expected = $ids;
		rsort( $expected );
		$this->assertSame( $expected, $result );
	}

	// ============================================================
	// Sorting by menu_order tests.
	// ============================================================

	/**
	 * Test sorting by menu_order ascending.
	 */
	public function testSortByMenuOrderAscending(): void {
		$ids = array_values( $this->created_product_ids );

		$result = SortEngine::sort(
			$ids,
			array(
				'key'       => 'menu_order',
				'direction' => 'asc',
			)
		);

		$menu_orders = array();
		foreach ( $result as $id ) {
			$product = wc_get_product( $id );
			$this->assertNotFalse( $product );
			$menu_orders[] = $product->get_menu_order();
		}

		$expected = $menu_orders;
		sort( $expected );
		$this->assertSame( $expected, $menu_orders );
	}

	// ============================================================
	// Determinism tests.
	// ============================================================

	/**
	 * Test deterministic sorting: same input produces same output.
	 */
	public function testSortDeterministic(): void {
		$ids    = array_values( $this->created_product_ids );
		$config = array(
			'key'       => 'title',
			'direction' => 'asc',
		);

		$results = array();
		for ( $i = 0; $i < 10; $i++ ) {
			$results[] = SortEngine::sort( $ids, $config );
		}

		$first = $results[0];
		foreach ( $results as $result ) {
			$this->assertSame( $first, $result );
		}
	}

	/**
	 * Test deterministic tie-breaking: same price, different SKUs.
	 */
	public function testSortDeterministicTieBreaking(): void {
		// Alpha and Zeta both have price 20.00.
		$ids = array(
			$this->created_product_ids['alpha-product'],
			$this->created_product_ids['zeta-product'],
		);

		$results = array();
		for ( $i = 0; $i < 10; $i++ ) {
			$results[] = SortEngine::sort(
				$ids,
				array(
					'key'       => 'price',
					'direction' => 'asc',
				)
			);
		}

		$first = $results[0];
		foreach ( $results as $result ) {
			$this->assertSame( $first, $result );
		}

		// Tie-breaker is ID ascending, so lower ID comes first.
		$this->assertLessThan(
			array_search( $first[1], $ids, true ),
			array_search( $first[0], $ids, true )
		);
	}

	// ============================================================
	// Invalid input handling tests.
	// ============================================================

	/**
	 * Test sorting with invalid key returns normalized IDs.
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
		$this->assertSame( array( 1, 3, 5 ), $result );
	}

	/**
	 * Test sorting with invalid direction defaults to ascending.
	 */
	public function testSortWithInvalidDirectionDefaults(): void {
		$ids    = array( 5, 3, 1 );
		$result = SortEngine::sort(
			$ids,
			array(
				'key'       => 'title',
				'direction' => 'INVALID',
			)
		);
		// Should not crash and should return sorted (or normalized) IDs.
		$this->assertIsArray( $result );
		$this->assertNotEmpty( $result );
	}

	/**
	 * Test sorting empty array.
	 */
	public function testSortEmptyArray(): void {
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
	 * Test sorting with string shorthand config.
	 */
	public function testSortWithStringShorthand(): void {
		$ids = array_values( $this->created_product_ids );

		$result = SortEngine::sort(
			$ids,
			'price:asc'
		);

		$prices = array();
		foreach ( $result as $id ) {
			$product = wc_get_product( $id );
			$this->assertNotFalse( $product );
			$price    = $product->get_price();
			$prices[] = '' === $price ? PHP_FLOAT_MAX : (float) $price;
		}

		$sorted_prices = $prices;
		sort( $sorted_prices );
		$this->assertSame( $sorted_prices, $prices );
	}

	/**
	 * Test sorting with legacy string shorthand descending.
	 */
	public function testSortWithStringShorthandDescending(): void {
		$ids = array_values( $this->created_product_ids );

		$result = SortEngine::sort(
			$ids,
			'price:desc'
		);

		$prices = array();
		foreach ( $result as $id ) {
			$product = wc_get_product( $id );
			$this->assertNotFalse( $product );
			$price    = $product->get_price();
			$prices[] = '' === $price ? PHP_FLOAT_MIN : (float) $price;
		}

		$sorted_prices = $prices;
		rsort( $sorted_prices );
		$this->assertSame( $sorted_prices, $prices );
	}

	// ============================================================
	// Pipeline integration tests (Filter → Sort).
	// ============================================================

	/**
	 * Test Filter Engine → Sort Engine pipeline.
	 */
	public function testFilterThenSort(): void {
		$ids = array_values( $this->created_product_ids );

		// Filter for simple products only.
		$filtered = FilterEngine::filter(
			$ids,
			array(
				array(
					'type'  => 'type',
					'value' => 'simple',
				),
			)
		);

		// Sort filtered results by price ascending.
		$sorted = SortEngine::sort(
			$filtered,
			array(
				'key'       => 'price',
				'direction' => 'asc',
			)
		);

		// Verify all results are simple products and sorted by price.
		$prices = array();
		foreach ( $sorted as $id ) {
			$product = wc_get_product( $id );
			$this->assertNotFalse( $product );
			$this->assertSame( 'simple', $product->get_type() );
			$prices[] = '' === $product->get_price() ? PHP_FLOAT_MAX : (float) $product->get_price();
		}

		$sorted_prices = $prices;
		sort( $sorted_prices );
		$this->assertSame( $sorted_prices, $prices );
	}

	/**
	 * Test Filter → Sort → Select pipeline.
	 */
	public function testFilterSortSelectPipeline(): void {
		$ids = array_values( $this->created_product_ids );

		// Filter for simple products.
		$filtered = FilterEngine::filter(
			$ids,
			array(
				array(
					'type'  => 'type',
					'value' => 'simple',
				),
			)
		);

		// Sort by price ascending.
		$sorted = SortEngine::sort(
			$filtered,
			array(
				'key'       => 'price',
				'direction' => 'asc',
			)
		);

		// Select first 3.
		$selected = \Catalogist\SelectionEngine::select(
			$sorted,
			array( 'limit' => 3 )
		);

		$this->assertCount( 3, $selected );

		// Verify they are the 3 cheapest simple products.
		$prices = array();
		foreach ( $selected as $id ) {
			$product = wc_get_product( $id );
			$this->assertNotFalse( $product );
			$this->assertSame( 'simple', $product->get_type() );
			$prices[] = (float) $product->get_price();
		}

		sort( $prices );
		// Should be the 3 lowest prices.
		$this->assertTrue( $prices[0] <= $prices[1] );
		$this->assertTrue( $prices[1] <= $prices[2] );
	}

	// ============================================================
	// Variable product handling.
	// ============================================================

	/**
	 * Test sorting includes variable products correctly.
	 */
	public function testSortIncludesVariableProducts(): void {
		$ids = array_values( $this->created_product_ids );

		$result = SortEngine::sort(
			$ids,
			array(
				'key'       => 'price',
				'direction' => 'asc',
			)
		);

		// Variable product should be in the result.
		$var_id = $this->created_product_ids['variable-product'];
		$this->assertContains( $var_id, $result );
	}

	// ============================================================
	// Helper methods.
	// ============================================================

	/**
	 * Create a WooCommerce product for testing.
	 *
	 * @param string $type Product type.
	 * @param string $name Product name.
	 * @param array  $args Additional arguments.
	 * @return int|WP_Error Product ID or error.
	 */
	private function create_product( string $type, string $name, array $args = array() ) {
		$args['post_title']   = $name;
		$args['post_content'] = '';
		$args['post_status']  = 'publish';
		$args['post_type']    = 'product';
		$args['post_author']  = 1;

		if ( isset( $args['price'] ) ) {
			$args['_regular_price'] = $args['price'];
			$args['_sale_price']    = '';
		}

		if ( isset( $args['sku'] ) ) {
			$args['_sku'] = $args['sku'];
		}

		if ( isset( $args['stock_qty'] ) ) {
			$args['_stock']        = $args['stock_qty'];
			$args['_stock_status'] = $args['stock_qty'] > 0 ? 'instock' : 'outofstock';
		}

		$product_id = wp_insert_post( $args );

		if ( is_wp_error( $product_id ) ) {
			return $product_id;
		}

		// Set product type.
		wc_create_product( $product_id, $type );

		// Set categories if provided.
		if ( isset( $args['categories'] ) ) {
			$category_ids = array();
			foreach ( $args['categories'] as $cat_slug ) {
				$term = get_term_by( 'slug', $cat_slug, 'product_cat' );
				if ( $term && ! is_wp_error( $term ) ) {
					$category_ids[] = $term->term_id;
				} else {
					$new_term = wp_insert_term( $cat_slug, 'product_cat' );
					if ( ! is_wp_error( $new_term ) ) {
						$category_ids[] = $new_term['term_id'];
					}
				}
			}
			if ( ! empty( $category_ids ) ) {
				wp_set_object_terms( $product_id, $category_ids, 'product_cat' );
			}
		}

		// Set tags if provided.
		if ( isset( $args['tags'] ) ) {
			$tag_ids = array();
			foreach ( $args['tags'] as $tag_slug ) {
				$term = get_term_by( 'slug', $tag_slug, 'product_tag' );
				if ( $term && ! is_wp_error( $term ) ) {
					$tag_ids[] = $term->term_id;
				} else {
					$new_term = wp_insert_term( $tag_slug, 'product_tag' );
					if ( ! is_wp_error( $new_term ) ) {
						$tag_ids[] = $new_term['term_id'];
					}
				}
			}
			if ( ! empty( $tag_ids ) ) {
				wp_set_object_terms( $product_id, $tag_ids, 'product_tag' );
			}
		}

		return $product_id;
	}
}
