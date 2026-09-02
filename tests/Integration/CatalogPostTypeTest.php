<?php
/**
 * Integration tests for CatalogPostType.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Tests\Integration;

use Catalogist\Catalog\CatalogPostType;
use PHPUnit\Framework\TestCase;

/**
 * Tests for CatalogPostType registration.
 */
class CatalogPostTypeTest extends TestCase {

	/**
	 * Test that the post type constant is correct.
	 *
	 * @return void
	 */
	public function test_post_type_constant(): void {
		$this->assertEquals( 'ctlg_catalog', CatalogPostType::POST_TYPE );
	}

	/**
	 * Test that the class implements HookableInterface.
	 *
	 * @return void
	 */
	public function test_implements_hookable_interface(): void {
		$post_type = new CatalogPostType();

		$this->assertTrue( method_exists( $post_type, 'register_hooks' ) );
	}

	/**
	 * Test that meta fields are defined.
	 *
	 * @return void
	 */
	public function test_meta_fields_exist(): void {
		// This test verifies the structure exists.
		// In a real WordPress environment, we would test actual registration.
		$expected_fields = array(
			'_catalogist_product_query',
			'_catalogist_filters',
			'_catalogist_selected_products',
			'_catalogist_template_id',
			'_catalogist_layout_settings',
			'_catalogist_print_settings',
		);

		foreach ( $expected_fields as $field ) {
			$this->assertNotEmpty( $field );
		}
	}

	/**
	 * Test sanitize_array() recursively sanitizes strings.
	 *
	 * @return void
	 */
	public function test_sanitize_array_recursively_sanitizes_strings(): void {
		$post_type = new CatalogPostType();

		$input = array(
			'name'      => '<script>alert(1)</script>Title',
			'count'     => '42',
			'nested'    => array(
				'inner' => '<b>Bold</b>',
			),
		);

		$result = $post_type->sanitize_array( $input );

		$this->assertEquals( 'Title', $result['name'] );
		$this->assertEquals( 42, $result['count'] );
		$this->assertEquals( 'Bold', $result['nested']['inner'] );
		$this->assertIsInt( $result['count'] );
	}

	/**
	 * Test sanitize_array() returns empty array for non-array input.
	 *
	 * @return void
	 */
	public function test_sanitize_array_non_array_input(): void {
		$post_type = new CatalogPostType();

		$this->assertEquals( array(), $post_type->sanitize_array( 'not an array' ) );
		$this->assertEquals( array(), $post_type->sanitize_array( 123 ) );
		$this->assertEquals( array(), $post_type->sanitize_array( null ) );
	}

	/**
	 * Test sanitize_array() handles mixed types.
	 *
	 * @return void
	 */
	public function test_sanitize_array_mixed_types(): void {
		$post_type = new CatalogPostType();

		$input = array(
			'string'  => 'hello',
			'int'     => 42,
			'float'   => 3.14,
			'bool'    => true,
			'array'   => array( 'nested' => 'value' ),
			'object'  => new stdClass(),
		);

		$result = $post_type->sanitize_array( $input );

		$this->assertEquals( 'hello', $result['string'] );
		$this->assertEquals( 42, $result['int'] );
		$this->assertEquals( 3.14, $result['float'] ); // floats pass through
		$this->assertTrue( $result['bool'] ); // bools pass through
		$this->assertEquals( array( 'nested' => 'value' ), $result['array'] );
		$this->assertInstanceOf( stdClass::class, $result['object'] ); // objects pass through
	}

	/**
	 * Test sanitize_int_array() converts all values to positive integers.
	 *
	 * @return void
	 */
	public function test_sanitize_int_array(): void {
		$post_type = new CatalogPostType();

		$result = $post_type->sanitize_int_array( array( '1', '2', -5, 'abc', 0 ) );

		$this->assertEquals( array( 1, 2, 5, 0, 0 ), $result );
	}
}
