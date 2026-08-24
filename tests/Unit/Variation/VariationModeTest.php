<?php
/**
 * Unit tests for VariationMode.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Tests\Unit\Variation;

use Catalogist\Variation\VariationMode;
use PHPUnit\Framework\TestCase;

/**
 * Tests for VariationMode value object.
 */
class VariationModeTest extends TestCase {

	/**
	 * Test from_string returns correct modes.
	 *
	 * @return void
	 */
	public function test_from_string_returns_correct_modes(): void {
		$this->assertSame( VariationMode::PARENT, VariationMode::from_string( 'parent' )->value() );
		$this->assertSame( VariationMode::ALL, VariationMode::from_string( 'all' )->value() );
		$this->assertSame( VariationMode::SELECTED, VariationMode::from_string( 'selected' )->value() );
		$this->assertSame( VariationMode::MULTIPLE, VariationMode::from_string( 'multiple' )->value() );
		$this->assertSame( VariationMode::TABLE, VariationMode::from_string( 'table' )->value() );
	}

	/**
	 * Test from_string is case-insensitive.
	 *
	 * @return void
	 */
	public function test_from_string_is_case_insensitive(): void {
		$this->assertSame( VariationMode::PARENT, VariationMode::from_string( 'Parent' )->value() );
		$this->assertSame( VariationMode::ALL, VariationMode::from_string( 'ALL' )->value() );
		$this->assertSame( VariationMode::TABLE, VariationMode::from_string( 'TaBlE' )->value() );
	}

	/**
	 * Test from_string returns null for invalid modes.
	 *
	 * @return void
	 */
	public function test_from_string_returns_null_for_invalid(): void {
		$this->assertNull( VariationMode::from_string( 'invalid' ) );
		$this->assertNull( VariationMode::from_string( '' ) );
		$this->assertNull( VariationMode::from_string( 'parent-only' ) );
	}

	/**
	 * Test is_expansion_mode.
	 *
	 * @return void
	 */
	public function test_is_expansion_mode(): void {
		$this->assertFalse( VariationMode::from_string( 'parent' )->is_expansion_mode() );
		$this->assertTrue( VariationMode::from_string( 'all' )->is_expansion_mode() );
		$this->assertTrue( VariationMode::from_string( 'selected' )->is_expansion_mode() );
		$this->assertTrue( VariationMode::from_string( 'multiple' )->is_expansion_mode() );
		$this->assertTrue( VariationMode::from_string( 'table' )->is_expansion_mode() );
	}

	/**
	 * Test is_parent_mode.
	 *
	 * @return void
	 */
	public function test_is_parent_mode(): void {
		$this->assertTrue( VariationMode::from_string( 'parent' )->is_parent_mode() );
		$this->assertFalse( VariationMode::from_string( 'all' )->is_parent_mode() );
		$this->assertFalse( VariationMode::from_string( 'table' )->is_parent_mode() );
	}

	/**
	 * Test is_table_mode.
	 *
	 * @return void
	 */
	public function test_is_table_mode(): void {
		$this->assertTrue( VariationMode::from_string( 'table' )->is_table_mode() );
		$this->assertFalse( VariationMode::from_string( 'parent' )->is_table_mode() );
		$this->assertFalse( VariationMode::from_string( 'all' )->is_table_mode() );
	}

	/**
	 * Test get_allowed_modes.
	 *
	 * @return void
	 */
	public function test_get_allowed_modes(): void {
		$allowed = VariationMode::get_allowed_modes();

		$this->assertContains( 'parent', $allowed );
		$this->assertContains( 'all', $allowed );
		$this->assertContains( 'selected', $allowed );
		$this->assertContains( 'multiple', $allowed );
		$this->assertContains( 'table', $allowed );
		$this->assertCount( 5, $allowed );
	}

	/**
	 * Test get_expansion_modes.
	 *
	 * @return void
	 */
	public function test_get_expansion_modes(): void {
		$expansion = VariationMode::get_expansion_modes();

		$this->assertContains( 'all', $expansion );
		$this->assertContains( 'selected', $expansion );
		$this->assertContains( 'multiple', $expansion );
		$this->assertContains( 'table', $expansion );
		$this->assertNotContains( 'parent', $expansion );
		$this->assertCount( 4, $expansion );
	}

	/**
	 * Test __toString.
	 *
	 * @return void
	 */
	public function test_to_string(): void {
		$this->assertSame( 'parent', (string) VariationMode::from_string( 'parent' ) );
		$this->assertSame( 'all', (string) VariationMode::from_string( 'all' ) );
	}
}
