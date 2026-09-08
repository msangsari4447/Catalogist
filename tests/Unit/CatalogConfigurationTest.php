<?php

declare(strict_types=1);

namespace Catalogist\Tests\Unit;

use Catalogist\Catalog;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Catalog configuration (Stage 6).
 *
 * Tests pure PHP logic only — no WordPress function dependencies.
 * Validation and sanitization tests that use WordPress functions
 * are in Integration tests.
 */
final class CatalogConfigurationTest extends TestCase {

	// ----------------------------------------------------------------
	// Versioning
	// ----------------------------------------------------------------

	public function testConfigVersionConstant(): void {
		$this->assertIsString( Catalog::CONFIG_VERSION );
		$this->assertMatchesRegularExpression( '/^\d+\.\d+\.\d+$/', Catalog::CONFIG_VERSION );
	}

	public function testDefaultConfigurationIncludesVersion(): void {
		$config = Catalog::default_configuration();
		$this->assertArrayHasKey( 'version', $config );
		$this->assertSame( Catalog::CONFIG_VERSION, $config['version'] );
	}

	// ----------------------------------------------------------------
	// Defaults
	// ----------------------------------------------------------------

	public function testDefaultConfigurationStructure(): void {
		$config = Catalog::default_configuration();

		$this->assertIsArray( $config );
		$this->assertArrayHasKey( 'version', $config );
		$this->assertArrayHasKey( 'status', $config );
		$this->assertArrayHasKey( 'filters', $config );
		$this->assertArrayHasKey( 'sort', $config );
		$this->assertArrayHasKey( 'selection', $config );
		$this->assertArrayHasKey( 'layout', $config );

		$this->assertSame( 'draft', $config['status'] );
		$this->assertIsArray( $config['filters'] );
		$this->assertEmpty( $config['filters'] );
		$this->assertIsArray( $config['sort'] );
		$this->assertSame( 'title', $config['sort']['key'] );
		$this->assertSame( 'asc', $config['sort']['direction'] );
		$this->assertIsArray( $config['selection'] );
		$this->assertSame( 0, $config['selection']['offset'] );
		$this->assertNull( $config['selection']['limit'] );
		$this->assertIsArray( $config['layout'] );
		$this->assertSame( 'grid', $config['layout']['layout'] );
		$this->assertSame( 3, $config['layout']['columns'] );
		$this->assertTrue( $config['layout']['show_price'] );
		$this->assertFalse( $config['layout']['show_sku'] );
		$this->assertFalse( $config['layout']['show_stock'] );
	}

	public function testDefaultSettingsStructure(): void {
		$settings = Catalog::default_settings();

		$this->assertIsArray( $settings );
		$this->assertArrayHasKey( 'layout', $settings );
		$this->assertArrayHasKey( 'columns', $settings );
		$this->assertArrayHasKey( 'show_price', $settings );
		$this->assertArrayHasKey( 'show_sku', $settings );
		$this->assertArrayHasKey( 'show_stock', $settings );

		$this->assertSame( 'grid', $settings['layout'] );
		$this->assertSame( 3, $settings['columns'] );
		$this->assertTrue( $settings['show_price'] );
		$this->assertFalse( $settings['show_sku'] );
		$this->assertFalse( $settings['show_stock'] );
	}

	// ----------------------------------------------------------------
	// apply_defaults
	// ----------------------------------------------------------------

	public function testApplyDefaultsPreservesProvidedValues(): void {
		$defaults = Catalog::default_configuration();
		$provided = array(
			'status' => 'active',
			'layout' => array(
				'layout'  => 'table',
				'columns' => 5,
			),
		);

		$result = Catalog::apply_defaults( $defaults, $provided );

		$this->assertSame( 'active', $result['status'] );
		$this->assertSame( 'table', $result['layout']['layout'] );
		$this->assertSame( 5, $result['layout']['columns'] );
		// Other values should retain defaults.
		$this->assertSame( 'title', $result['sort']['key'] );
		$this->assertSame( 'asc', $result['sort']['direction'] );
	}

	/**
	 * Test that apply_defaults does not mutate the defaults array.
	 */
	public function testApplyDefaultsDoesNotMutateDefaults(): void {
		$before = Catalog::default_configuration();
		$result = Catalog::apply_defaults( $before, array( 'status' => 'active' ) );

		// Original should be unchanged.
		$this->assertSame( 'draft', $before['status'] );
		// Result should have the new value.
		$this->assertSame( 'active', $result['status'] );
	}

	/**
	 * Test recursive merging of nested arrays.
	 */
	public function testApplyDefaultsRecursiveMerge(): void {
		$defaults = Catalog::default_configuration();
		$provided = array(
			'layout' => array(
				'columns' => 6,
			),
		);

		$result = Catalog::apply_defaults( $defaults, $provided );

		// Nested value should be updated.
		$this->assertSame( 6, $result['layout']['columns'] );
		// Sibling nested values should retain defaults.
		$this->assertSame( 'grid', $result['layout']['layout'] );
		$this->assertTrue( $result['layout']['show_price'] );
	}

	/**
	 * Test that missing keys in provided array retain defaults.
	 */
	public function testApplyDefaultsRetainsMissingKeys(): void {
		$result = Catalog::apply_defaults(
			Catalog::default_configuration(),
			array( 'status' => 'active' )
		);

		$this->assertSame( 'active', $result['status'] );
		$this->assertSame( 'title', $result['sort']['key'] );
		$this->assertSame( 0, $result['selection']['offset'] );
	}

	// ----------------------------------------------------------------
	// Constants
	// ----------------------------------------------------------------

	public function testAllowedStatusesConstant(): void {
		$reflection = new \ReflectionClass( Catalog::class );
		$constant   = $reflection->getConstant( 'ALLOWED_STATUSES' );
		$this->assertIsArray( $constant );
		$this->assertSame( array( 'draft', 'active', 'archived' ), $constant );
	}

	public function testAllowedLayoutsConstant(): void {
		$reflection = new \ReflectionClass( Catalog::class );
		$constant   = $reflection->getConstant( 'ALLOWED_LAYOUTS' );
		$this->assertIsArray( $constant );
		$this->assertSame( array( 'grid', 'list', 'table' ), $constant );
	}

	public function testAllowedSortKeysConstant(): void {
		$reflection = new \ReflectionClass( Catalog::class );
		$constant   = $reflection->getConstant( 'ALLOWED_SORT_KEYS' );
		$this->assertIsArray( $constant );
		$this->assertSame( array( 'title', 'price', 'sku', 'menu_order', 'id' ), $constant );
	}

	public function testAllowedSortDirectionsConstant(): void {
		$reflection = new \ReflectionClass( Catalog::class );
		$constant   = $reflection->getConstant( 'ALLOWED_SORT_DIRECTIONS' );
		$this->assertIsArray( $constant );
		$this->assertSame( array( 'asc', 'desc' ), $constant );
	}

	// ----------------------------------------------------------------
	// Structure Tests
	// ----------------------------------------------------------------

	public function testCatalogClassIsFinal(): void {
		$reflection = new \ReflectionClass( Catalog::class );
		$this->assertTrue( $reflection->isFinal() );
	}

	public function testDefaultConfigurationVersionMatchesConstant(): void {
		$config = Catalog::default_configuration();
		$this->assertSame( Catalog::CONFIG_VERSION, $config['version'] );
	}

	/**
	 * Test that meta_keys returns all expected keys.
	 */
	public function testMetaKeysCount(): void {
		$keys = Catalog::meta_keys();
		$this->assertCount( 6, $keys );
	}

	public function testMetaKeysIncludesLegacyKeys(): void {
		$keys = Catalog::meta_keys();
		$this->assertContains( 'ctlg_catalog_description', $keys );
		$this->assertContains( 'ctlg_catalog_settings', $keys );
		$this->assertContains( 'ctlg_catalog_products', $keys );
	}

	public function testMetaKeysIncludesNewKeys(): void {
		$keys = Catalog::meta_keys();
		$this->assertContains( 'ctlg_catalog_configuration', $keys );
		$this->assertContains( 'ctlg_catalog_version', $keys );
		$this->assertContains( 'ctlg_catalog_status', $keys );
	}
}
