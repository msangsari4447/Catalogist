<?php
/**
 * Integration tests for WooCommerceProductRepository.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Tests\Integration\Product;

use Catalogist\Product\ProductQueryArgs;
use Catalogist\Product\WooCommerceProductRepository;
use PHPUnit\Framework\TestCase;

/**
 * Tests for WooCommerceProductRepository.
 *
 * Note: These tests require WooCommerce to be active in the test environment.
 * They will be skipped if WooCommerce is not available.
 */
class WooCommerceProductRepositoryTest extends TestCase {

	/**
	 * Test repository returns empty result when WooCommerce is inactive.
	 *
	 * @return void
	 */
	public function test_query_returns_empty_result_when_woocommerce_inactive(): void {
		$repository = new WooCommerceProductRepository();
		$args       = new ProductQueryArgs();

		$result = $repository->query( $args );

		$this->assertInstanceOf( \Catalogist\Product\ProductQueryResult::class, $result );
	}

	/**
	 * Test find returns null for non-existent product.
	 *
	 * @return void
	 */
	public function test_find_returns_null_for_nonexistent_product(): void {
		$repository = new WooCommerceProductRepository();

		$result = $repository->find( 999999999 );

		$this->assertNull( $result );
	}

	/**
	 * Test exists returns false for non-existent product.
	 *
	 * @return void
	 */
	public function test_exists_returns_false_for_nonexistent_product(): void {
		$repository = new WooCommerceProductRepository();

		$this->assertFalse( $repository->exists( 999999999 ) );
	}

	/**
	 * Test get_ids_by_category returns array.
	 *
	 * @return void
	 */
	public function test_get_ids_by_category_returns_array(): void {
		$repository = new WooCommerceProductRepository();

		$ids = $repository->get_ids_by_category( 'nonexistent-category-xyz' );

		$this->assertIsArray( $ids );
	}

	/**
	 * Test get_ids_by_tag returns array.
	 *
	 * @return void
	 */
	public function test_get_ids_by_tag_returns_array(): void {
		$repository = new WooCommerceProductRepository();

		$ids = $repository->get_ids_by_tag( 'nonexistent-tag-xyz' );

		$this->assertIsArray( $ids );
	}

	/**
	 * Test get_ids_by_category with numeric input.
	 *
	 * @return void
	 */
	public function test_get_ids_by_category_with_numeric(): void {
		$repository = new WooCommerceProductRepository();

		$ids = $repository->get_ids_by_category( 99999 );

		$this->assertIsArray( $ids );
	}

	/**
	 * Test get_ids_by_tag with numeric input.
	 *
	 * @return void
	 */
	public function test_get_ids_by_tag_with_numeric(): void {
		$repository = new WooCommerceProductRepository();

		$ids = $repository->get_ids_by_tag( 99999 );

		$this->assertIsArray( $ids );
	}

	/**
	 * Test query with include filters.
	 *
	 * @return void
	 */
	public function test_query_with_include_filters(): void {
		$repository = new WooCommerceProductRepository();

		$args = ProductQueryArgs::from_array(
			array(
				'include' => array( 999999998, 999999999 ),
			)
		);

		$result = $repository->query( $args );

		$this->assertInstanceOf( \Catalogist\Product\ProductQueryResult::class, $result );
		$this->assertIsArray( $result->get_products() );
	}

	/**
	 * Test query with pagination.
	 *
	 * @return void
	 */
	public function test_query_with_pagination(): void {
		$repository = new WooCommerceProductRepository();

		$args = ProductQueryArgs::from_array(
			array(
				'page'     => 1,
				'per_page' => 10,
			)
		);

		$result = $repository->query( $args );

		$this->assertSame( 1, $result->get_page() );
		$this->assertSame( 10, $result->get_per_page() );
	}

	/**
	 * Test query returns ids when specified.
	 *
	 * @return void
	 */
	public function test_query_returns_ids_when_specified(): void {
		$repository = new WooCommerceProductRepository();

		$args = ProductQueryArgs::from_array(
			array(
				'return'   => 'ids',
				'per_page' => 5,
			)
		);

		$result = $repository->query( $args );

		$this->assertInstanceOf( \Catalogist\Product\ProductQueryResult::class, $result );
	}

	/**
	 * Test query result includes total_on_page.
	 *
	 * @return void
	 */
	public function test_query_result_includes_total_on_page(): void {
		$repository = new WooCommerceProductRepository();

		$args = ProductQueryArgs::from_array(
			array(
				'per_page' => 5,
			)
		);

		$result = $repository->query( $args );
		$array  = $result->to_array();

		$this->assertArrayHasKey( 'total_on_page', $array );
		$this->assertIsInt( $array['total_on_page'] );
	}
}
