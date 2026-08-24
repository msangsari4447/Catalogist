<?php
/**
 * Unit tests for ProductFilters.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Tests\Unit\Product;

use Catalogist\Product\ProductFilters;
use PHPUnit\Framework\TestCase;

/**
 * Tests for ProductFilters helper class.
 */
class ProductFiltersTest extends TestCase {

	/**
	 * Test normalize_status returns valid status.
	 *
	 * @return void
	 */
	public function test_normalize_status_returns_valid_status(): void {
		$filters = new ProductFilters();

		$this->assertSame( 'publish', $filters->normalize_status( 'publish' ) );
		$this->assertSame( 'draft', $filters->normalize_status( 'DRAFT' ) );
		$this->assertSame( 'private', $filters->normalize_status( '  private  ' ) );
	}

	/**
	 * Test normalize_status returns null for invalid.
	 *
	 * @return void
	 */
	public function test_normalize_status_returns_null_for_invalid(): void {
		$filters = new ProductFilters();

		$this->assertNull( $filters->normalize_status( 'invalid' ) );
		$this->assertNull( $filters->normalize_status( '' ) );
	}

	/**
	 * Test normalize_statuses filters array.
	 *
	 * @return void
	 */
	public function test_normalize_statuses_filters_array(): void {
		$filters = new ProductFilters();
		$result  = $filters->normalize_statuses( array( 'publish', 'invalid', 'draft' ) );

		$this->assertSame( array( 'publish', 'draft' ), $result );
	}

	/**
	 * Test normalize_stock_status returns valid value.
	 *
	 * @return void
	 */
	public function test_normalize_stock_status_returns_valid_value(): void {
		$filters = new ProductFilters();

		$this->assertSame( 'instock', $filters->normalize_stock_status( 'instock' ) );
		$this->assertSame( 'outofstock', $filters->normalize_stock_status( 'OUTOFSTOCK' ) );
		$this->assertSame( 'onbackorder', $filters->normalize_stock_status( 'onbackorder' ) );
	}

	/**
	 * Test normalize_stock_status returns null for invalid.
	 *
	 * @return void
	 */
	public function test_normalize_stock_status_returns_null_for_invalid(): void {
		$filters = new ProductFilters();

		$this->assertNull( $filters->normalize_stock_status( 'invalid' ) );
		$this->assertNull( $filters->normalize_stock_status( '' ) );
	}

	/**
	 * Test normalize_visibility returns valid value.
	 *
	 * @return void
	 */
	public function test_normalize_visibility_returns_valid_value(): void {
		$filters = new ProductFilters();

		$this->assertSame( 'visible', $filters->normalize_visibility( 'visible' ) );
		$this->assertSame( 'catalog', $filters->normalize_visibility( 'CATALOG' ) );
		$this->assertSame( 'search', $filters->normalize_visibility( 'search' ) );
		$this->assertSame( 'hidden', $filters->normalize_visibility( 'hidden' ) );
	}

	/**
	 * Test normalize_visibility returns null for invalid.
	 *
	 * @return void
	 */
	public function test_normalize_visibility_returns_null_for_invalid(): void {
		$filters = new ProductFilters();

		$this->assertNull( $filters->normalize_visibility( 'invalid' ) );
	}

	/**
	 * Test normalize_category with numeric ID.
	 *
	 * @return void
	 */
	public function test_normalize_category_with_numeric_id(): void {
		$filters = new ProductFilters();

		$this->assertSame( 5, $filters->normalize_category( 5 ) );
		$this->assertSame( 10, $filters->normalize_category( '10' ) );
	}

	/**
	 * Test normalize_category with slug.
	 *
	 * @return void
	 */
	public function test_normalize_category_with_slug(): void {
		$filters = new ProductFilters();

		$this->assertSame( 'my-category', $filters->normalize_category( 'My Category' ) );
		$this->assertSame( 'test', $filters->normalize_category( 'test' ) );
	}

	/**
	 * Test normalize_category returns null for invalid.
	 *
	 * @return void
	 */
	public function test_normalize_category_returns_null_for_invalid(): void {
		$filters = new ProductFilters();

		$this->assertNull( $filters->normalize_category( 0 ) );
		$this->assertNull( $filters->normalize_category( -5 ) );
		$this->assertNull( $filters->normalize_category( '' ) );
	}

	/**
	 * Test normalize_tag with numeric ID.
	 *
	 * @return void
	 */
	public function test_normalize_tag_with_numeric_id(): void {
		$filters = new ProductFilters();

		$this->assertSame( 3, $filters->normalize_tag( 3 ) );
		$this->assertSame( 7, $filters->normalize_tag( '7' ) );
	}

	/**
	 * Test normalize_tag with slug.
	 *
	 * @return void
	 */
	public function test_normalize_tag_with_slug(): void {
		$filters = new ProductFilters();

		$this->assertSame( 'my-tag', $filters->normalize_tag( 'My Tag' ) );
		$this->assertSame( 'special', $filters->normalize_tag( 'special' ) );
	}

	/**
	 * Test get_allowed_statuses returns expected values.
	 *
	 * @return void
	 */
	public function test_get_allowed_statuses_returns_expected_values(): void {
		$filters  = new ProductFilters();
		$statuses = $filters->get_allowed_statuses();

		$this->assertContains( 'publish', $statuses );
		$this->assertContains( 'draft', $statuses );
		$this->assertContains( 'private', $statuses );
	}

	/**
	 * Test get_allowed_stock_statuses returns expected values.
	 *
	 * @return void
	 */
	public function test_get_allowed_stock_statuses_returns_expected_values(): void {
		$filters  = new ProductFilters();
		$statuses = $filters->get_allowed_stock_statuses();

		$this->assertContains( 'instock', $statuses );
		$this->assertContains( 'outofstock', $statuses );
		$this->assertContains( 'onbackorder', $statuses );
	}

	/**
	 * Test get_allowed_visibilities returns expected values.
	 *
	 * @return void
	 */
	public function test_get_allowed_visibilities_returns_expected_values(): void {
		$filters     = new ProductFilters();
		$visibilities = $filters->get_allowed_visibilities();

		$this->assertContains( 'visible', $visibilities );
		$this->assertContains( 'catalog', $visibilities );
		$this->assertContains( 'search', $visibilities );
		$this->assertContains( 'hidden', $visibilities );
	}
}
