<?php

declare(strict_types=1);

namespace Catalogist\Tests\Integration;

use Catalogist\VariationEngine;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for VariationEngine.
 *
 * These tests require a full WordPress + WooCommerce environment (run via docker compose).
 */
final class VariationEngineTest extends TestCase {

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
			'simple-product'          => array(
				'title'      => 'Simple Product',
				'type'       => 'simple',
				'sku'        => 'SP-001',
				'categories' => array( 'test-catalog' ),
				'tags'       => array( 'test' ),
				'status'     => 'publish',
				'stock'      => 'instock',
			),
			'variable-product'        => array(
				'title'      => 'Variable Product',
				'type'       => 'variable',
				'sku'        => 'VP-001',
				'categories' => array( 'test-catalog' ),
				'tags'       => array( 'test' ),
				'status'     => 'publish',
				'stock'      => 'instock',
			),
			'empty-variation-product' => array(
				'title'      => 'Empty Variation Product',
				'type'       => 'variable',
				'sku'        => 'EVP-001',
				'categories' => array( 'test-catalog' ),
				'tags'       => array( 'test' ),
				'status'     => 'publish',
				'stock'      => 'instock',
			),
		);

		$ids = array();
		foreach ( $products as $slug => $data ) {
			// Delete any existing product with this slug.
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

				// Set product type AFTER save.
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

			// For variable products, create variations.
			if ( 'variable' === $data['type'] ) {
				$variation_ids = $this->create_variations( $product_id, $slug );
				$ids[ $slug ]  = array(
					'parent'     => (int) $product_id,
					'variations' => $variation_ids,
				);
			} else {
				$ids[ $slug ] = (int) $product_id;
			}
		}
		return $ids;
	}

	/**
	 * Create variations for a variable product.
	 *
	 * @param int    $parent_id Parent product ID.
	 * @param string $slug      Product slug (for naming).
	 * @return list<int> Variation IDs.
	 */
	private function create_variations( int $parent_id, string $slug ): array {
		$variation_ids = array();
		$attributes    = array(
			array(
				'name'   => 'Color',
				'option' => array( 'Red', 'Blue' ),
			),
		);

		foreach ( $attributes as $attr_set ) {
			foreach ( $attr_set['option'] as $option ) {
				$variation = new \WC_Product_Variation();
				$variation->set_parent_id( $parent_id );
				$variation->set_attributes(
					array(
						sanitize_title( $attr_set['name'] ) => $option,
					)
				);
				$variation->set_regular_price( '10.00' );
				$variation->set_stock_status( 'instock' );
				$variation->set_sku( $slug . '-' . strtolower( $option ) );
				$variation->save();
				$variation_ids[] = (int) $variation->get_id();
			}
		}

		// Also create an empty-variation product (variable with no variations).
		return $variation_ids;
	}

	/**
	 * Delete test products.
	 */
	private function delete_test_products(): void {
		foreach ( $this->created_product_ids as $slug => $data ) {
			if ( is_array( $data ) && isset( $data['parent'] ) ) {
				// Delete variations first.
				if ( isset( $data['variations'] ) ) {
					foreach ( $data['variations'] as $vid ) {
						wp_delete_post( $vid, true );
					}
				}
				wp_delete_post( $data['parent'], true );
			} else {
				wp_delete_post( (int) $data, true );
			}
		}
	}

	// ============================================================
	// get_variation_ids Tests.
	// ============================================================

	/**
	 * Test get_variation_ids returns empty array for simple product.
	 */
	public function testGetVariationIdsSimpleProduct(): void {
		$simple_id = $this->created_product_ids['simple-product'];
		$result    = VariationEngine::get_variation_ids( $simple_id );

		$this->assertIsArray( $result );
		$this->assertSame( array(), $result );
	}

	/**
	 * Test get_variation_ids returns variation IDs for variable product.
	 */
	public function testGetVariationIdsVariableProduct(): void {
		$vp       = $this->created_product_ids['variable-product'];
		$result   = VariationEngine::get_variation_ids( $vp['parent'] );
		$expected = $vp['variations'];

		$this->assertIsArray( $result );
		$this->assertCount( 2, $result );
		$this->assertSameSorted( $expected, $result );
	}

	/**
	 * Test get_variation_ids returns empty array for non-existent product.
	 */
	public function testGetVariationIdsNonExistentProduct(): void {
		$result = VariationEngine::get_variation_ids( 99999 );

		$this->assertIsArray( $result );
		$this->assertSame( array(), $result );
	}

	/**
	 * Test get_variation_ids with zero ID.
	 */
	public function testGetVariationIdsZero(): void {
		$result = VariationEngine::get_variation_ids( 0 );

		$this->assertIsArray( $result );
		$this->assertSame( array(), $result );
	}

	/**
	 * Test get_variation_ids with negative ID.
	 */
	public function testGetVariationIdsNegative(): void {
		$result = VariationEngine::get_variation_ids( -1 );

		$this->assertIsArray( $result );
		$this->assertSame( array(), $result );
	}

	// ============================================================
	// is_variable_product Tests.
	// ============================================================

	/**
	 * Test is_variable_product returns false for simple product.
	 */
	public function testIsVariableProductSimple(): void {
		$simple_id = $this->created_product_ids['simple-product'];
		$result    = VariationEngine::is_variable_product( $simple_id );

		$this->assertFalse( $result );
	}

	/**
	 * Test is_variable_product returns true for variable product.
	 */
	public function testIsVariableProductVariable(): void {
		$vp     = $this->created_product_ids['variable-product'];
		$result = VariationEngine::is_variable_product( $vp['parent'] );

		$this->assertTrue( $result );
	}

	/**
	 * Test is_variable_product returns false for non-existent product.
	 */
	public function testIsVariableProductNonExistent(): void {
		$result = VariationEngine::is_variable_product( 99999 );

		$this->assertFalse( $result );
	}

	/**
	 * Test is_variable_product returns false for zero ID.
	 */
	public function testIsVariableProductZero(): void {
		$result = VariationEngine::is_variable_product( 0 );

		$this->assertFalse( $result );
	}

	// ============================================================
	// get_variation_data Tests.
	// ============================================================

	/**
	 * Test get_variation_data returns structured array for valid variation.
	 */
	public function testGetVariationDataValid(): void {
		$vp     = $this->created_product_ids['variable-product'];
		$vid    = $vp['variations'][0];
		$result = VariationEngine::get_variation_data( $vid );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'id', $result );
		$this->assertArrayHasKey( 'parent_id', $result );
		$this->assertArrayHasKey( 'label', $result );
		$this->assertSame( $vid, $result['id'] );
		$this->assertSame( $vp['parent'], $result['parent_id'] );
		$this->assertIsString( $result['label'] );
		$this->assertNotEmpty( $result['label'] );
	}

	/**
	 * Test get_variation_data returns null for simple product.
	 */
	public function testGetVariationDataSimpleProduct(): void {
		$simple_id = $this->created_product_ids['simple-product'];
		$result    = VariationEngine::get_variation_data( $simple_id );

		$this->assertNull( $result );
	}

	/**
	 * Test get_variation_data returns null for non-existent product.
	 */
	public function testGetVariationDataNonExistent(): void {
		$result = VariationEngine::get_variation_data( 99999 );

		$this->assertNull( $result );
	}

	/**
	 * Test get_variation_data returns null for zero ID.
	 */
	public function testGetVariationDataZero(): void {
		$result = VariationEngine::get_variation_data( 0 );

		$this->assertNull( $result );
	}

	// ============================================================
	// expand_product_ids Tests.
	// ============================================================

	/**
	 * Test expand_product_ids without flag preserves simple product IDs.
	 */
	public function testExpandProductIdsWithoutFlag(): void {
		$simple_id = $this->created_product_ids['simple-product'];
		$result    = VariationEngine::expand_product_ids( array( $simple_id ), false );

		$this->assertSame( array( $simple_id ), $result );
	}

	/**
	 * Test expand_product_ids with flag expands variable product to variations.
	 */
	public function testExpandProductIdsWithFlag(): void {
		$vp     = $this->created_product_ids['variable-product'];
		$result = VariationEngine::expand_product_ids(
			array( $vp['parent'] ),
			true
		);

		$this->assertIsArray( $result );
		$this->assertCount( 3, $result ); // parent + 2 variations
		$this->assertContains( $vp['parent'], $result );
		foreach ( $vp['variations'] as $vid ) {
			$this->assertContains( $vid, $result );
		}
	}

	/**
	 * Test expand_product_ids with mixed simple and variable products.
	 */
	public function testExpandProductIdsMixed(): void {
		$simple_id = $this->created_product_ids['simple-product'];
		$vp        = $this->created_product_ids['variable-product'];

		$result = VariationEngine::expand_product_ids(
			array( $simple_id, $vp['parent'] ),
			true
		);

		$this->assertIsArray( $result );
		$this->assertCount( 4, $result ); // simple + parent + 2 variations
		$this->assertContains( $simple_id, $result );
		$this->assertContains( $vp['parent'], $result );
		foreach ( $vp['variations'] as $vid ) {
			$this->assertContains( $vid, $result );
		}
	}

	/**
	 * Test expand_product_ids with non-existent product ID.
	 */
	public function testExpandProductIdsNonExistent(): void {
		$result = VariationEngine::expand_product_ids(
			array( 99999 ),
			true
		);

		$this->assertSame( array( 99999 ), $result );
	}

	/**
	 * Test expand_product_ids filters non-integer IDs.
	 */
	public function testExpandProductIdsFiltersNonIntegers(): void {
		$result = VariationEngine::expand_product_ids(
			array( 'abc', 0, -1, null, '' ),
			false
		);

		$this->assertSame( array(), $result );
	}

	// ============================================================
	// resolve_product_ids Tests.
	// ============================================================

	/**
	 * Test resolve_product_ids with 'parent' mode returns unchanged IDs.
	 */
	public function testResolveProductIdsParentMode(): void {
		$simple_id = $this->created_product_ids['simple-product'];
		$vp        = $this->created_product_ids['variable-product'];

		$result = VariationEngine::resolve_product_ids(
			array( $simple_id, $vp['parent'] ),
			'parent'
		);

		$this->assertSame( array( $simple_id, $vp['parent'] ), $result );
	}

	/**
	 * Test resolve_product_ids with 'variations' mode expands variable products.
	 */
	public function testResolveProductIdsVariationsMode(): void {
		$simple_id = $this->created_product_ids['simple-product'];
		$vp        = $this->created_product_ids['variable-product'];

		$result = VariationEngine::resolve_product_ids(
			array( $simple_id, $vp['parent'] ),
			'variations'
		);

		$this->assertIsArray( $result );
		$this->assertCount( 4, $result ); // simple + parent + 2 variations
		$this->assertContains( $simple_id, $result );
		$this->assertContains( $vp['parent'], $result );
		foreach ( $vp['variations'] as $vid ) {
			$this->assertContains( $vid, $result );
		}
	}

	/**
	 * Test resolve_product_ids with invalid mode falls back to parent.
	 */
	public function testResolveProductIdsInvalidMode(): void {
		$simple_id = $this->created_product_ids['simple-product'];
		$vp        = $this->created_product_ids['variable-product'];

		$result = VariationEngine::resolve_product_ids(
			array( $simple_id, $vp['parent'] ),
			'invalid-mode'
		);

		$this->assertSame( array( $simple_id, $vp['parent'] ), $result );
	}

	/**
	 * Test resolve_product_ids with empty array.
	 */
	public function testResolveProductIdsEmpty(): void {
		$result = VariationEngine::resolve_product_ids( array(), 'variations' );
		$this->assertSame( array(), $result );
	}

	// ============================================================
	// Integration with ProductQueryEngine Tests.
	// ============================================================

	/**
	 * Test expand_product_ids works with ProductQueryEngine results.
	 */
	public function testExpandProductIdsWithProductQueryEngine(): void {
		// Query for simple products only.
		$simple_ids = \Catalogist\ProductQueryEngine::query(
			array( 'type' => 'simple' )
		);

		$this->assertIsArray( $simple_ids );
		$this->assertNotEmpty( $simple_ids );

		// Expand should preserve simple product IDs (no variations).
		$expanded = VariationEngine::expand_product_ids( $simple_ids, true );
		$this->assertSame( $simple_ids, $expanded );
	}

	/**
	 * Test expand_product_ids works with ProductQueryEngine variable results.
	 */
	public function testExpandProductIdsWithVariableQuery(): void {
		// Query for variable products.
		$var_ids = \Catalogist\ProductQueryEngine::query(
			array( 'type' => 'variable' )
		);

		$this->assertIsArray( $var_ids );
		$this->assertNotEmpty( $var_ids );

		// Expand should include variation IDs for our test product.
		$vp       = $this->created_product_ids['variable-product'];
		$expanded = VariationEngine::expand_product_ids( array( $vp['parent'] ), true );

		$this->assertIsArray( $expanded );
		$this->assertCount( 3, $expanded ); // 1 parent + 2 variations
		$this->assertContains( $vp['parent'], $expanded );
	}

	// ============================================================
	// Edge Case Tests.
	// ============================================================

	/**
	 * Test get_variation_ids with deleted variation.
	 */
	public function testGetVariationIdsWithDeletedVariation(): void {
		$vp        = $this->created_product_ids['variable-product'];
		$first_var = $vp['variations'][0];

		// Before deletion.
		$before = VariationEngine::get_variation_ids( $vp['parent'] );
		$this->assertCount( 2, $before );
		$this->assertContains( $first_var, $before );

		// Delete the variation.
		wp_delete_post( $first_var, true );
		wp_cache_flush();

		// After deletion.
		$after = VariationEngine::get_variation_ids( $vp['parent'] );
		$this->assertCount( 1, $after );
		$this->assertNotContains( $first_var, $after );
	}

	/**
	 * Test that all return types are correct.
	 */
	public function testReturnTypes(): void {
		$simple_id = $this->created_product_ids['simple-product'];
		$vp        = $this->created_product_ids['variable-product'];

		// get_variation_ids returns array.
		$this->assertIsArray( VariationEngine::get_variation_ids( $simple_id ) );
		$this->assertIsArray( VariationEngine::get_variation_ids( $vp['parent'] ) );

		// is_variable_product returns bool.
		$this->assertIsBool( VariationEngine::is_variable_product( $simple_id ) );
		$this->assertIsBool( VariationEngine::is_variable_product( $vp['parent'] ) );

		// get_variation_data returns array or null.
		$data = VariationEngine::get_variation_data( $vp['variations'][0] );
		$this->assertIsArray( $data );
		$this->assertNull( VariationEngine::get_variation_data( $simple_id ) );

		// expand_product_ids returns array.
		$this->assertIsArray( VariationEngine::expand_product_ids( array( $simple_id ), false ) );
		$this->assertIsArray( VariationEngine::expand_product_ids( array( $simple_id ), true ) );

		// resolve_product_ids returns array.
		$this->assertIsArray( VariationEngine::resolve_product_ids( array( $simple_id ), 'parent' ) );
		$this->assertIsArray( VariationEngine::resolve_product_ids( array( $simple_id ), 'variations' ) );
	}

	/**
	 * Helper: assert two arrays are the same regardless of order.
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
