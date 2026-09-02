<?php
/**
 * Unit tests for SettingsPage.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Tests\Unit\Admin;

use Catalogist\Admin\SettingsPage;
use Catalogist\Security\Nonce;
use PHPUnit\Framework\TestCase;

/**
 * Tests for SettingsPage.
 */
class SettingsPageTest extends TestCase {

	/**
	 * Test SettingsPage constants use Nonce constants.
	 *
	 * @return void
	 */
	public function test_settings_page_uses_nonce_constants(): void {
		$this->assertEquals( Nonce::SETTINGS_ACTION, SettingsPage::OPTION_GROUP );
		// Note: OPTION_NAME is private, we test it indirectly
	}

	/**
	 * Test sanitize_settings() sanitizes input correctly.
	 *
	 * @return void
	 */
	public function test_sanitize_settings(): void {
		$settings_page = new SettingsPage();

		$input = array(
			'post_type_slug' => 'custom-catalogs',
			'per_page'       => '50',
			'enable_print'   => 'true',
		);

		$result = $settings_page->sanitize_settings( $input );

		$this->assertEquals( 'custom-catalogs', $result['post_type_slug'] );
		$this->assertEquals( 50, $result['per_page'] );
		$this->assertTrue( $result['enable_print'] );
	}

	/**
	 * Test sanitize_settings() clamps per_page to valid range.
	 *
	 * @return void
	 */
	public function test_sanitize_settings_clamps_per_page(): void {
		$settings_page = new SettingsPage();

		$result = $settings_page->sanitize_settings( array( 'per_page' => 150 ) );
		$this->assertEquals( 100, $result['per_page'] );

		$result = $settings_page->sanitize_settings( array( 'per_page' => 0 ) );
		$this->assertEquals( 1, $result['per_page'] );
	}

	/**
	 * Test sanitize_settings() handles missing values with defaults.
	 *
	 * @return void
	 */
	public function test_sanitize_settings_defaults(): void {
		$settings_page = new SettingsPage();

		$result = $settings_page->sanitize_settings( array() );

		$this->assertEquals( 'catalogs', $result['post_type_slug'] );
		$this->assertEquals( 20, $result['per_page'] );
		$this->assertFalse( $result['enable_print'] );
	}
}