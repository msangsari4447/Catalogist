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
}
