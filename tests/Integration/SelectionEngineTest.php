<?php

declare(strict_types=1);

namespace Catalogist\Tests\Integration;

use Catalogist\FilterEngine;
use Catalogist\SelectionEngine;
use Catalogist\SortEngine;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for SelectionEngine.
 *
 * These tests require a full WordPress + WooCommerce environment (run via docker compose).
 */
final class SelectionEngineTest extends TestCase {

	/**
	 * Test IDs created during setUp.
	 *
	 * @var array<string, int>
	 */
	private array $created_product_ids = array();

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->created_product_ids = array(
			'product-a' => $this->create_product(
				'simple',
				'Product A',
				array(
					'price' => '10.00',
					'sku'   => 'A-001',
				)
			),
			'product-b' => $this->create_product(
				'simple',
				'Product B',
				array(
					'price' => '20.00',
					'sku'   => 'B-001',
				)
			),
			'product-c' => $this->create_product(
				'simple',
				'Product C',
				array(
					'price' => '30.00',
					'sku'   => 'C-001',
				)
			),
			'product-d' => $this->create_product(
				'simple',
				'Product D',
				array(
					'price' => '40.00',
					'sku'   => 'D-001',
				)
			),
			'product-e' => $this->create_product(
				'simple',
				'Product E',
				array(
					'price' => '50.00',
					'sku'   => 'E-001',
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
	 * Test that the SelectionEngine class exists.
	 */
	public function testSelectionEngineClassExists(): void {
		$this->assertTrue( class_exists( SelectionEngine::class ) );
	}

	/**
	 * Test that select() is public and static.
	 */
	public function testSelectMethodIsPublicStatic(): void {
		$reflection = new \ReflectionClass( SelectionEngine::class );
		$method     = $reflection->getMethod( 'select' );
		$this->assertTrue( $method->isPublic() );
		$this->assertTrue( $method->isStatic() );
	}

	// ============================================================
	// Limit tests.
	// ============================================================

	/**
	 * Test limit selection with real products.
	 */
	public function testSelectWithLimit(): void {
		$ids = array_values( $this->created_product_ids );

		$result = SelectionEngine::select( $ids, array( 'limit' => 3 ) );
		$this->assertCount( 3, $result );
		$this->assertSame( array_slice( $ids, 0, 3 ), $result );
	}

	/**
	 * Test limit exceeding available items.
	 */
	public function testSelectExcessiveLimit(): void {
		$ids = array_values( $this->created_product_ids );

		$result = SelectionEngine::select( $ids, array( 'limit' => 100 ) );
		$this->assertCount( 5, $result );
		$this->assertSame( $ids, $result );
	}

	/**
	 * Test zero limit returns empty.
	 */
	public function testSelectZeroLimit(): void {
		$ids = array_values( $this->created_product_ids );

		$result = SelectionEngine::select( $ids, array( 'limit' => 0 ) );
		$this->assertSame( array(), $result );
	}

	/**
	 * Test negative limit returns all items.
	 */
	public function testSelectNegativeLimit(): void {
		$ids = array_values( $this->created_product_ids );

		$result = SelectionEngine::select( $ids, array( 'limit' => -5 ) );
		$this->assertCount( 5, $result );
		$this->assertSame( $ids, $result );
	}

	// ============================================================
	// Offset tests.
	// ============================================================

	/**
	 * Test offset selection with real products.
	 */
	public function testSelectWithOffset(): void {
		$ids = array_values( $this->created_product_ids );

		$result = SelectionEngine::select( $ids, array( 'offset' => 2 ) );
		$this->assertCount( 3, $result );
		$this->assertSame( array_slice( $ids, 2 ), $result );
	}

	/**
	 * Test offset exceeding available items.
	 */
	public function testSelectExcessiveOffset(): void {
		$ids = array_values( $this->created_product_ids );

		$result = SelectionEngine::select( $ids, array( 'offset' => 100 ) );
		$this->assertSame( array(), $result );
	}

	/**
	 * Test negative offset treated as zero.
	 */
	public function testSelectNegativeOffset(): void {
		$ids = array_values( $this->created_product_ids );

		$result = SelectionEngine::select( $ids, array( 'offset' => -5 ) );
		$this->assertCount( 5, $result );
		$this->assertSame( $ids, $result );
	}

	// ============================================================
	// Offset + Limit tests.
	// ============================================================

	/**
	 * Test offset + limit selection with real products.
	 */
	public function testSelectWithOffsetAndLimit(): void {
		$ids = array_values( $this->created_product_ids );

		$result = SelectionEngine::select(
			$ids,
			array(
				'offset' => 1,
				'limit'  => 3,
			)
		);
		$this->assertCount( 3, $result );
		$this->assertSame( array_slice( $ids, 1, 3 ), $result );
	}

	// ============================================================
	// Empty input tests.
	// ============================================================

	/**
	 * Test empty input.
	 */
	public function testSelectEmptyInput(): void {
		$result = SelectionEngine::select( array() );
		$this->assertSame( array(), $result );
	}

	/**
	 * Test empty input with config.
	 */
	public function testSelectEmptyInputWithConfig(): void {
		$result = SelectionEngine::select( array(), array( 'limit' => 5 ) );
		$this->assertSame( array(), $result );
	}

	// ============================================================
	// No config tests.
	// ============================================================

	/**
	 * Test no config returns all IDs.
	 */
	public function testSelectNoConfigReturnsAll(): void {
		$ids = array_values( $this->created_product_ids );

		$result = SelectionEngine::select( $ids );
		$this->assertSame( $ids, $result );
	}

	/**
	 * Test null config returns all IDs.
	 */
	public function testSelectNullConfigReturnsAll(): void {
		$ids = array_values( $this->created_product_ids );

		$result = SelectionEngine::select( $ids, null );
		$this->assertSame( $ids, $result );
	}

	// ============================================================
	// Invalid config tests.
	// ============================================================

	/**
	 * Test invalid config returns all IDs.
	 */
	public function testSelectInvalidConfigReturnsAll(): void {
		$ids = array_values( $this->created_product_ids );

		$result = SelectionEngine::select( $ids, 'invalid' );
		$this->assertSame( $ids, $result );

		$result = SelectionEngine::select( $ids, 123 );
		$this->assertSame( $ids, $result );
	}

	// ============================================================
	// Determinism tests.
	// ============================================================

	/**
	 * Test deterministic selection: same input produces same output.
	 */
	public function testSelectDeterministic(): void {
		$ids    = array_values( $this->created_product_ids );
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
	// Pipeline integration tests (Filter → Sort → Select).
	// ============================================================

	/**
	 * Test full pipeline: Filter → Sort → Select.
	 */
	public function testFullPipeline(): void {
		$ids = array_values( $this->created_product_ids );

		// Filter all simple products.
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

		// Select first 2.
		$selected = SelectionEngine::select(
			$sorted,
			array( 'limit' => 2 )
		);

		$this->assertCount( 2, $selected );

		// Verify they are the 2 cheapest products.
		$prices = array();
		foreach ( $selected as $id ) {
			$product = wc_get_product( $id );
			$this->assertNotFalse( $product );
			$prices[] = (float) $product->get_price();
		}

		sort( $prices );
		$this->assertTrue( $prices[0] <= $prices[1] );
		$this->assertSame( 10.0, $prices[0] );
		$this->assertSame( 20.0, $prices[1] );
	}

	/**
	 * Test full pipeline with offset.
	 */
	public function testFullPipelineWithOffset(): void {
		$ids = array_values( $this->created_product_ids );

		// Filter all simple products.
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

		// Select with offset 1, limit 2.
		$selected = SelectionEngine::select(
			$sorted,
			array(
				'offset' => 1,
				'limit'  => 2,
			)
		);

		$this->assertCount( 2, $selected );

		// Should be the 2nd and 3rd cheapest.
		$prices = array();
		foreach ( $selected as $id ) {
			$product = wc_get_product( $id );
			$this->assertNotFalse( $product );
			$prices[] = (float) $product->get_price();
		}

		sort( $prices );
		$this->assertSame( 20.0, $prices[0] );
		$this->assertSame( 30.0, $prices[1] );
	}

	/**
	 * Test deterministic results through full pipeline.
	 */
	public function testFullPipelineDeterministic(): void {
		$ids    = array_values( $this->created_product_ids );
		$config = array(
			'limit' => 3,
		);

		$results = array();
		for ( $i = 0; $i < 10; $i++ ) {
			$filtered  = FilterEngine::filter( $ids, array() );
			$sorted    = SortEngine::sort(
				$filtered,
				array(
					'key'       => 'price',
					'direction' => 'asc',
				)
			);
			$selected  = SelectionEngine::select( $sorted, $config );
			$results[] = $selected;
		}

		$first = $results[0];
		foreach ( $results as $result ) {
			$this->assertSame( $first, $result );
		}
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

		$product_id = wp_insert_post( $args );

		if ( is_wp_error( $product_id ) ) {
			return $product_id;
		}

		wc_create_product( $product_id, $type );

		return $product_id;
	}
}
