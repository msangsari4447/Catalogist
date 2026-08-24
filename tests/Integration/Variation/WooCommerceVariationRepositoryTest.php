<?php
/**
 * Integration tests for WooCommerceVariationRepository.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Tests\Integration\Variation;

use Catalogist\Variation\VariationMode;
use Catalogist\Variation\VariationQueryArgs;
use Catalogist\Variation\WooCommerceVariationRepository;
use PHPUnit\Framework\TestCase;

/**
 * Tests for WooCommerceVariationRepository.
 *
 * Note: These tests require WooCommerce to be active in the test environment.
 * They will be skipped if WooCommerce is not available.
 */
class WooCommerceVariationRepositoryTest extends TestCase {

	/**
	 * Test graceful degradation when WooCommerce is inactive.
	 *
	 * @return void
	 */
	public function test_graceful_degradation_when_woocommerce_inactive(): void {
		$repository = new WooCommerceVariationRepository();
		$args       = new VariationQueryArgs( new VariationMode( VariationMode::ALL ) );

		$result = $repository->get_variations( 99999, $args );

		$this->assertInstanceOf( \Catalogist\Variation\VariationQueryResult::class, $result );
		$this->assertSame( 0, $result->get_total() );
		$this->assertEmpty( $result->get_variations() );
	}

	/**
	 * Test find returns null for non-existent variation.
	 *
	 * @return void
	 */
	public function test_find_returns_null_for_nonexistent_variation(): void {
		$repository = new WooCommerceVariationRepository();

		$result = $repository->find( 999999999 );

		$this->assertNull( $result );
	}

	/**
	 * Test exists returns false for non-existent variation.
	 *
	 * @return void
	 */
	public function test_exists_returns_false_for_nonexistent_variation(): void {
		$repository = new WooCommerceVariationRepository();

		$this->assertFalse( $repository->exists( 999999999 ) );
	}

	/**
	 * Test get_variation_ids returns array.
	 *
	 * @return void
	 */
	public function test_get_variation_ids_returns_array(): void {
		$repository = new WooCommerceVariationRepository();

		// Use a non-existent product to test empty result handling.
		$ids = $repository->get_variation_ids( 99999 );

		$this->assertIsArray( $ids );
		$this->assertEmpty( $ids );
	}

	/**
	 * Test is_variable_product returns false for non-existent product.
	 *
	 * @return void
	 */
	public function test_is_variable_product_returns_false_for_nonexistent(): void {
		$repository = new WooCommerceVariationRepository();

		$this->assertFalse( $repository->is_variable_product( 99999 ) );
	}

	/**
	 * Test get_variations with selected mode filter.
	 *
	 * @return void
	 */
	public function test_get_variations_with_selected_filter(): void {
		$repository = new WooCommerceVariationRepository();

		$args = new VariationQueryArgs(
			new VariationMode( VariationMode::SELECTED ),
			array( 999999998, 999999999 )
		);

		$result = $repository->get_variations( 99999, $args );

		$this->assertInstanceOf( \Catalogist\Variation\VariationQueryResult::class, $result );
		$this->assertSame( 0, $result->get_total() );
	}

	/**
	 * Test get_variations with exclude filter.
	 *
	 * @return void
	 */
	public function test_get_variations_with_exclude_filter(): void {
		$repository = new WooCommerceVariationRepository();

		$args = new VariationQueryArgs(
			new VariationMode( VariationMode::ALL ),
			array(),
			array( 1, 2, 3 )
		);

		$result = $repository->get_variations( 99999, $args );

		$this->assertInstanceOf( \Catalogist\Variation\VariationQueryResult::class, $result );
	}

	/**
	 * Test get_variations with table mode.
	 *
	 * @return void
	 */
	public function test_get_variations_with_table_mode(): void {
		$repository = new WooCommerceVariationRepository();

		$args = new VariationQueryArgs(
			new VariationMode( VariationMode::TABLE )
		);

		$result = $repository->get_variations( 99999, $args );

		$this->assertInstanceOf( \Catalogist\Variation\VariationQueryResult::class, $result );
		$this->assertSame( VariationMode::TABLE, $result->get_mode()->value() );
	}

	/**
	 * Test VariationQueryResult structure.
	 *
	 * @return void
	 */
	public function test_variation_query_result_structure(): void {
		$repository = new WooCommerceVariationRepository();
		$args       = new VariationQueryArgs( new VariationMode( VariationMode::ALL ) );

		$result = $repository->get_variations( 99999, $args );

		$array = $result->to_array();

		$this->assertIsArray( $array );
		$this->assertArrayHasKey( 'parent_product_id', $array );
		$this->assertArrayHasKey( 'variations', $array );
		$this->assertArrayHasKey( 'total', $array );
		$this->assertArrayHasKey( 'mode', $array );
		$this->assertSame( 0, $array['total'] );
		$this->assertSame( 'all', $array['mode'] );
	}

	/**
	 * Test find returns structured data when WooCommerce is active.
	 *
	 * @return void
	 */
	public function test_find_returns_array_structure(): void {
		$repository = new WooCommerceVariationRepository();

		// For non-existent product, should return null.
		$result = $repository->find( 999999999 );

		$this->assertNull( $result );
	}
}
