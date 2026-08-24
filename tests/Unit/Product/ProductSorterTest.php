<?php
/**
 * Unit tests for ProductSorter.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Tests\Unit\Product;

use Catalogist\Product\ProductSorter;
use PHPUnit\Framework\TestCase;

/**
 * Tests for ProductSorter helper class.
 */
class ProductSorterTest extends TestCase {

	/**
	 * Test normalize_orderby returns valid field.
	 *
	 * @return void
	 */
	public function test_normalize_orderby_returns_valid_field(): void {
		$sorter = new ProductSorter();

		$this->assertSame( 'date', $sorter->normalize_orderby( 'date' ) );
		$this->assertSame( 'title', $sorter->normalize_orderby( 'TITLE' ) );
		$this->assertSame( 'price', $sorter->normalize_orderby( 'price' ) );
		$this->assertSame( 'popularity', $sorter->normalize_orderby( 'popularity' ) );
		$this->assertSame( 'rating', $sorter->normalize_orderby( 'rating' ) );
		$this->assertSame( 'rand', $sorter->normalize_orderby( 'rand' ) );
	}

	/**
	 * Test normalize_orderby returns null for invalid.
	 *
	 * @return void
	 */
	public function test_normalize_orderby_returns_null_for_invalid(): void {
		$sorter = new ProductSorter();

		$this->assertNull( $sorter->normalize_orderby( 'invalid' ) );
		$this->assertNull( $sorter->normalize_orderby( '' ) );
	}

	/**
	 * Test normalize_order returns valid direction.
	 *
	 * @return void
	 */
	public function test_normalize_order_returns_valid_direction(): void {
		$sorter = new ProductSorter();

		$this->assertSame( 'ASC', $sorter->normalize_order( 'asc' ) );
		$this->assertSame( 'DESC', $sorter->normalize_order( 'desc' ) );
		$this->assertSame( 'ASC', $sorter->normalize_order( 'ASC' ) );
		$this->assertSame( 'DESC', $sorter->normalize_order( 'DESC' ) );
	}

	/**
	 * Test normalize_order returns null for invalid.
	 *
	 * @return void
	 */
	public function test_normalize_order_returns_null_for_invalid(): void {
		$sorter = new ProductSorter();

		$this->assertNull( $sorter->normalize_order( 'invalid' ) );
		$this->assertNull( $sorter->normalize_order( '' ) );
	}

	/**
	 * Test get_default_orderby.
	 *
	 * @return void
	 */
	public function test_get_default_orderby(): void {
		$sorter = new ProductSorter();

		$this->assertSame( 'date', $sorter->get_default_orderby() );
	}

	/**
	 * Test get_default_order.
	 *
	 * @return void
	 */
	public function test_get_default_order(): void {
		$sorter = new ProductSorter();

		$this->assertSame( 'DESC', $sorter->get_default_order() );
	}

	/**
	 * Test is_valid_orderby.
	 *
	 * @return void
	 */
	public function test_is_valid_orderby(): void {
		$sorter = new ProductSorter();

		$this->assertTrue( $sorter->is_valid_orderby( 'date' ) );
		$this->assertTrue( $sorter->is_valid_orderby( 'price' ) );
		$this->assertFalse( $sorter->is_valid_orderby( 'invalid' ) );
	}

	/**
	 * Test is_valid_order.
	 *
	 * @return void
	 */
	public function test_is_valid_order(): void {
		$sorter = new ProductSorter();

		$this->assertTrue( $sorter->is_valid_order( 'asc' ) );
		$this->assertTrue( $sorter->is_valid_order( 'DESC' ) );
		$this->assertFalse( $sorter->is_valid_order( 'invalid' ) );
	}

	/**
	 * Test get_allowed_orderby returns expected values.
	 *
	 * @return void
	 */
	public function test_get_allowed_orderby_returns_expected_values(): void {
		$sorter   = new ProductSorter();
		$orderby  = $sorter->get_allowed_orderby();

		$this->assertContains( 'date', $orderby );
		$this->assertContains( 'title', $orderby );
		$this->assertContains( 'price', $orderby );
		$this->assertContains( 'popularity', $orderby );
		$this->assertContains( 'rating', $orderby );
		$this->assertContains( 'menu_order', $orderby );
	}

	/**
	 * Test get_allowed_order returns expected values.
	 *
	 * @return void
	 */
	public function test_get_allowed_order_returns_expected_values(): void {
		$sorter = new ProductSorter();
		$order  = $sorter->get_allowed_order();

		$this->assertContains( 'ASC', $order );
		$this->assertContains( 'DESC', $order );
		$this->assertCount( 2, $order );
	}
}
