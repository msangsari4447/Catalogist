<?php
/**
 * Unit tests for Security classes.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Tests\Unit\Security;

use Catalogist\Security\Capability;
use Catalogist\Security\Nonce;
use Catalogist\Security\Sanitizer;
use Catalogist\Security\Validator;
use PHPUnit\Framework\TestCase;

/**
 * Tests for security helper classes.
 */
class SecurityTest extends TestCase {

	/**
	 * Test Capability constants are defined.
	 *
	 * @return void
	 */
	public function test_capability_constants_defined(): void {
		$this->assertEquals( 'catalogist_manage_catalogs', Capability::MANAGE_CATALOGS );
		$this->assertEquals( 'catalogist_edit_catalogs', Capability::EDIT_CATALOGS );
		$this->assertEquals( 'catalogist_delete_catalogs', Capability::DELETE_CATALOGS );
		$this->assertEquals( 'catalogist_manage_templates', Capability::MANAGE_TEMPLATES );
		$this->assertEquals( 'catalogist_manage_settings', Capability::MANAGE_SETTINGS );
	}

	/**
	 * Test Nonce constants are defined.
	 *
	 * @return void
	 */
	public function test_nonce_constants_defined(): void {
		$this->assertEquals( 'catalogist_settings_action', Nonce::SETTINGS_ACTION );
		$this->assertEquals( 'catalogist_settings_nonce', Nonce::SETTINGS_NAME );
	}

	/**
	 * Test Sanitizer::text() sanitizes input.
	 *
	 * @return void
	 */
	public function test_sanitizer_text(): void {
		$sanitizer = new Sanitizer();
		$result    = $sanitizer->text( '<script>alert(1)</script>Hello' );
		$this->assertEquals( 'Hello', $result );
	}

	/**
	 * Test Sanitizer::boolean() converts values.
	 *
	 * @return void
	 */
	public function test_sanitizer_boolean(): void {
		$sanitizer = new Sanitizer();
		$this->assertTrue( $sanitizer->boolean( 'true' ) );
		$this->assertTrue( $sanitizer->boolean( 1 ) );
		$this->assertTrue( $sanitizer->boolean( '1' ) );
		$this->assertFalse( $sanitizer->boolean( 'false' ) );
		$this->assertFalse( $sanitizer->boolean( 0 ) );
		$this->assertFalse( $sanitizer->boolean( '' ) );
	}

	/**
	 * Test Sanitizer::absint() returns positive integers.
	 *
	 * @return void
	 */
	public function test_sanitizer_absint(): void {
		$sanitizer = new Sanitizer();
		$this->assertEquals( 42, $sanitizer->absint( '42' ) );
		$this->assertEquals( 42, $sanitizer->absint( -42 ) );
		$this->assertEquals( 0, $sanitizer->absint( 'abc' ) );
		$this->assertEquals( 0, $sanitizer->absint( -5 ) );
	}

	/**
	 * Test Sanitizer::settings() sanitizes settings array.
	 *
	 * @return void
	 */
	public function test_sanitizer_settings(): void {
		$sanitizer = new Sanitizer();
		$result    = $sanitizer->settings(
			array(
				'post_type_slug' => 'custom-catalogs',
				'per_page'       => '50',
				'enable_print'   => 'true',
			)
		);
		$this->assertEquals( 'custom-catalogs', $result['post_type_slug'] );
		$this->assertEquals( 50, $result['per_page'] );
		$this->assertTrue( $result['enable_print'] );
	}

	/**
	 * Test Sanitizer::settings() clamps per_page to valid range.
	 *
	 * @return void
	 */
	public function test_sanitizer_settings_clamps_per_page(): void {
		$sanitizer = new Sanitizer();
		$result    = $sanitizer->settings(
			array(
				'per_page' => 150,
			)
		);
		$this->assertEquals( 100, $result['per_page'] );

		$result = $sanitizer->settings(
			array(
				'per_page' => 0,
			)
		);
		$this->assertEquals( 1, $result['per_page'] );
	}

	/**
	 * Test Validator::is_non_empty_string().
	 *
	 * @return void
	 */
	public function test_validator_non_empty_string(): void {
		$validator = new Validator();
		$this->assertTrue( $validator->is_non_empty_string( 'hello' ) );
		$this->assertTrue( $validator->is_non_empty_string( '  hello  ' ) );
		$this->assertFalse( $validator->is_non_empty_string( '' ) );
		$this->assertFalse( $validator->is_non_empty_string( '   ' ) );
		$this->assertFalse( $validator->is_non_empty_string( 123 ) );
		$this->assertFalse( $validator->is_non_empty_string( null ) );
		$this->assertFalse( $validator->is_non_empty_string( array() ) );
	}

	/**
	 * Test Validator::is_positive_id().
	 *
	 * @return void
	 */
	public function test_validator_positive_id(): void {
		$validator = new Validator();
		$this->assertTrue( $validator->is_positive_id( 1 ) );
		$this->assertTrue( $validator->is_positive_id( '42' ) );
		$this->assertTrue( $validator->is_positive_id( 100 ) );
		$this->assertFalse( $validator->is_positive_id( 0 ) );
		$this->assertFalse( $validator->is_positive_id( -1 ) );
		$this->assertFalse( $validator->is_positive_id( 'abc' ) );
		$this->assertFalse( $validator->is_positive_id( '' ) );
		$this->assertFalse( $validator->is_positive_id( null ) );
	}

	/**
	 * Test Validator::is_valid_slug().
	 *
	 * @return void
	 */
	public function test_validator_valid_slug(): void {
		$validator = new Validator();
		$this->assertTrue( $validator->is_valid_slug( 'catalog' ) );
		$this->assertTrue( $validator->is_valid_slug( 'custom_catalog' ) );
		$this->assertTrue( $validator->is_valid_slug( 'catalog-123' ) );
		$this->assertFalse( $validator->is_valid_slug( 'Catalog' ) ); // uppercase
		$this->assertFalse( $validator->is_valid_slug( 'catalog!' ) ); // special char
		$this->assertFalse( $validator->is_valid_slug( '' ) ); // empty
		$this->assertFalse( $validator->is_valid_slug( 'catalog name' ) ); // space
	}
}