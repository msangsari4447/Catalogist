<?php
/**
 * Unit tests for VariationQueryArgs.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Tests\Unit\Variation;

use Catalogist\Variation\VariationMode;
use Catalogist\Variation\VariationQueryArgs;
use PHPUnit\Framework\TestCase;

/**
 * Tests for VariationQueryArgs value object.
 */
class VariationQueryArgsTest extends TestCase {

	/**
	 * Test default mode is parent.
	 *
	 * @return void
	 */
	public function test_default_mode_is_parent(): void {
		$args = VariationQueryArgs::from_array( array() );

		$this->assertSame( VariationMode::PARENT, $args->get_mode()->value() );
	}

	/**
	 * Test set mode from array.
	 *
	 * @return void
	 */
	public function test_set_mode_from_array(): void {
		$args = VariationQueryArgs::from_array(
			array( 'variation_mode' => 'all' )
		);

		$this->assertSame( VariationMode::ALL, $args->get_mode()->value() );
	}

	/**
	 * Test invalid mode defaults to parent.
	 *
	 * @return void
	 */
	public function test_invalid_mode_defaults_to_parent(): void {
		$args = VariationQueryArgs::from_array(
			array( 'variation_mode' => 'invalid' )
		);

		$this->assertSame( VariationMode::PARENT, $args->get_mode()->value() );
	}

	/**
	 * Test selected_variation_ids are sanitized.
	 *
	 * @return void
	 */
	public function test_selected_variation_ids_are_sanitized(): void {
		$args = VariationQueryArgs::from_array(
			array(
				'variation_mode' => 'selected',
				'selected_variation_ids' => array( 1, 0, -5, 'invalid', 10 ),
			)
		);

		$this->assertSame( array( 1, 10 ), $args->get_selected_variation_ids() );
	}

	/**
	 * Test exclude_variation_ids are sanitized.
	 *
	 * @return void
	 */
	public function test_exclude_variation_ids_are_sanitized(): void {
		$args = VariationQueryArgs::from_array(
			array(
				'variation_mode' => 'all',
				'exclude_variation_ids' => array( 5, 0, -1, 'test', 20 ),
			)
		);

		$this->assertSame( array( 5, 20 ), $args->get_exclude_variation_ids() );
	}

	/**
	 * Test to_array round-trip.
	 *
	 * @return void
	 */
	public function test_to_array_round_trip(): void {
		$args = VariationQueryArgs::from_array(
			array(
				'variation_mode' => 'multiple',
				'selected_variation_ids' => array( 1, 2, 3 ),
				'exclude_variation_ids' => array( 4, 5 ),
			)
		);

		$array = $args->to_array();

		$this->assertSame( 'multiple', $array['variation_mode'] );
		$this->assertSame( array( 1, 2, 3 ), $array['selected_variation_ids'] );
		$this->assertSame( array( 4, 5 ), $array['exclude_variation_ids'] );
	}

	/**
	 * Test all five modes are accepted.
	 *
	 * @return void
	 */
	public function test_all_five_modes_accepted(): void {
		$modes = array( 'parent', 'all', 'selected', 'multiple', 'table' );

		foreach ( $modes as $mode ) {
			$args = VariationQueryArgs::from_array( array( 'variation_mode' => $mode ) );
			$this->assertSame( $mode, $args->get_mode()->value(), "Mode '$mode' should be accepted" );
		}
	}
}
