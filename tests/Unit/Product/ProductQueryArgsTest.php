<?php
/**
 * Unit tests for ProductQueryArgs.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Tests\Unit\Product;

use Catalogist\Product\ProductQueryArgs;
use PHPUnit\Framework\TestCase;

/**
 * Tests for ProductQueryArgs value object.
 */
class ProductQueryArgsTest extends TestCase {

	/**
	 * Test default values.
	 *
	 * @return void
	 */
	public function test_default_values(): void {
		$args = new ProductQueryArgs();

		$this->assertSame( array(), $args->get_include() );
		$this->assertSame( array(), $args->get_exclude() );
		$this->assertSame( array(), $args->get_categories() );
		$this->assertSame( array(), $args->get_tags() );
		$this->assertSame( '', $args->get_search() );
		$this->assertSame( array( 'publish' ), $args->get_status() );
		$this->assertSame( array(), $args->get_stock_status() );
		$this->assertSame( array(), $args->get_visibility() );
		$this->assertSame( 1, $args->get_page() );
		$this->assertSame( 20, $args->get_per_page() );
		$this->assertSame( 'date', $args->get_orderby() );
		$this->assertSame( 'DESC', $args->get_order() );
		$this->assertSame( 'objects', $args->get_return() );
	}

	/**
	 * Test set_include filters invalid values.
	 *
	 * @return void
	 */
	public function test_set_include_filters_invalid_values(): void {
		$args = new ProductQueryArgs();
		$args->set_include( array( 1, 0, -5, 'invalid', 10 ) );

		$this->assertSame( array( 1, 10 ), $args->get_include() );
	}

	/**
	 * Test set_exclude filters invalid values.
	 *
	 * @return void
	 */
	public function test_set_exclude_filters_invalid_values(): void {
		$args = new ProductQueryArgs();
		$args->set_exclude( array( 5, 0, -1, 'test', 20 ) );

		$this->assertSame( array( 5, 20 ), $args->get_exclude() );
	}

	/**
	 * Test set_search sanitizes input.
	 *
	 * @return void
	 */
	public function test_set_search_sanitizes_input(): void {
		$args = new ProductQueryArgs();
		$args->set_search( '  <script>test</script>  ' );

		$this->assertSame( 'test', $args->get_search() );
	}

	/**
	 * Test set_status only allows valid statuses.
	 *
	 * @return void
	 */
	public function test_set_status_only_allows_valid_statuses(): void {
		$args = new ProductQueryArgs();
		$args->set_status( array( 'publish', 'invalid', 'draft' ) );

		$this->assertSame( array( 'publish', 'draft' ), $args->get_status() );
	}

	/**
	 * Test set_status defaults to publish when empty.
	 *
	 * @return void
	 */
	public function test_set_status_defaults_to_publish_when_empty(): void {
		$args = new ProductQueryArgs();
		$args->set_status( array( 'invalid' ) );

		$this->assertSame( array( 'publish' ), $args->get_status() );
	}

	/**
	 * Test set_stock_status only allows valid values.
	 *
	 * @return void
	 */
	public function test_set_stock_status_only_allows_valid_values(): void {
		$args = new ProductQueryArgs();
		$args->set_stock_status( array( 'instock', 'invalid', 'outofstock' ) );

		$this->assertSame( array( 'instock', 'outofstock' ), $args->get_stock_status() );
	}

	/**
	 * Test set_visibility only allows valid values.
	 *
	 * @return void
	 */
	public function test_set_visibility_only_allows_valid_values(): void {
		$args = new ProductQueryArgs();
		$args->set_visibility( array( 'visible', 'invalid', 'hidden' ) );

		$this->assertSame( array( 'visible', 'hidden' ), $args->get_visibility() );
	}

	/**
	 * Test set_page clamps to minimum.
	 *
	 * @return void
	 */
	public function test_set_page_clamps_to_minimum(): void {
		$args = new ProductQueryArgs();
		$args->set_page( 0 );

		$this->assertSame( 1, $args->get_page() );
	}

	/**
	 * Test set_per_page clamps to range.
	 *
	 * @return void
	 */
	public function test_set_per_page_clamps_to_range(): void {
		$args = new ProductQueryArgs();
		$args->set_per_page( 0 );

		$this->assertSame( 1, $args->get_per_page() );

		$args->set_per_page( 2000 );

		$this->assertSame( 1000, $args->get_per_page() );
	}

	/**
	 * Test set_orderby only allows valid values.
	 *
	 * @return void
	 */
	public function test_set_orderby_only_allows_valid_values(): void {
		$args = new ProductQueryArgs();
		$args->set_orderby( 'invalid' );

		$this->assertSame( 'date', $args->get_orderby() );

		$args->set_orderby( 'price' );

		$this->assertSame( 'price', $args->get_orderby() );
	}

	/**
	 * Test set_order normalizes to uppercase.
	 *
	 * @return void
	 */
	public function test_set_order_normalizes_to_uppercase(): void {
		$args = new ProductQueryArgs();
		$args->set_order( 'asc' );

		$this->assertSame( 'ASC', $args->get_order() );

		$args->set_order( 'desc' );

		$this->assertSame( 'DESC', $args->get_order() );
	}

	/**
	 * Test set_return only allows ids or objects.
	 *
	 * @return void
	 */
	public function test_set_return_only_allows_ids_or_objects(): void {
		$args = new ProductQueryArgs();
		$args->set_return( 'ids' );

		$this->assertSame( 'ids', $args->get_return() );

		$args->set_return( 'invalid' );

		$this->assertSame( 'objects', $args->get_return() );
	}

	/**
	 * Test from_array creates instance correctly.
	 *
	 * @return void
	 */
	public function test_from_array_creates_instance_correctly(): void {
		$args = ProductQueryArgs::from_array(
			array(
				'include'  => array( 1, 2, 3 ),
				'exclude'  => array( 4, 5 ),
				'search'   => 'test product',
				'page'     => 2,
				'per_page' => 50,
				'orderby'  => 'title',
				'order'    => 'ASC',
				'return'   => 'ids',
			)
		);

		$this->assertSame( array( 1, 2, 3 ), $args->get_include() );
		$this->assertSame( array( 4, 5 ), $args->get_exclude() );
		$this->assertSame( 'test product', $args->get_search() );
		$this->assertSame( 2, $args->get_page() );
		$this->assertSame( 50, $args->get_per_page() );
		$this->assertSame( 'title', $args->get_orderby() );
		$this->assertSame( 'ASC', $args->get_order() );
		$this->assertSame( 'ids', $args->get_return() );
	}

	/**
	 * Test to_array returns all values.
	 *
	 * @return void
	 */
	public function test_to_array_returns_all_values(): void {
		$args = ProductQueryArgs::from_array(
			array(
				'include' => array( 1, 2 ),
				'page'    => 3,
			)
		);

		$array = $args->to_array();

		$this->assertIsArray( $array );
		$this->assertArrayHasKey( 'include', $array );
		$this->assertArrayHasKey( 'exclude', $array );
		$this->assertArrayHasKey( 'categories', $array );
		$this->assertArrayHasKey( 'tags', $array );
		$this->assertArrayHasKey( 'search', $array );
		$this->assertArrayHasKey( 'status', $array );
		$this->assertArrayHasKey( 'stock_status', $array );
		$this->assertArrayHasKey( 'visibility', $array );
		$this->assertArrayHasKey( 'page', $array );
		$this->assertArrayHasKey( 'per_page', $array );
		$this->assertArrayHasKey( 'orderby', $array );
		$this->assertArrayHasKey( 'order', $array );
		$this->assertArrayHasKey( 'return', $array );
		$this->assertSame( 3, $array['page'] );
	}
}
