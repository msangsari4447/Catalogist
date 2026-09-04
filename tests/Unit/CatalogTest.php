<?php

declare(strict_types=1);

namespace Catalogist\Tests\Unit;

use Catalogist\Catalog;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Catalog class.
 *
 * These tests only test pure PHP logic - no WordPress functions.
 * Tests requiring WordPress functions are in Integration tests.
 */
final class CatalogTest extends TestCase {

	/**
	 * Test default settings structure.
	 */
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

	/**
	 * Test meta keys are defined.
	 */
	public function testMetaKeys(): void {
		$keys = Catalog::meta_keys();

		$this->assertIsArray( $keys );
		$this->assertCount( 3, $keys );
		$this->assertContains( 'ctlg_catalog_description', $keys );
		$this->assertContains( 'ctlg_catalog_settings', $keys );
		$this->assertContains( 'ctlg_catalog_products', $keys );
	}
}
