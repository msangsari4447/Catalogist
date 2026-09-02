<?php
/**
 * Unit tests for PreviewPage.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Tests\Unit\Admin;

use Catalogist\Admin\PreviewPage;
use PHPUnit\Framework\TestCase;

/**
 * Tests for PreviewPage.
 */
class PreviewPageTest extends TestCase {

	/**
	 * Test parse_print_settings() whitelists allowed keys.
	 *
	 * @return void
	 */
	public function test_parse_print_settings_whitelists_keys(): void {
		$preview_page = new PreviewPage(
			$this->createMock( \Catalogist\Catalog\CatalogRepositoryInterface::class ),
			$this->createMock( \Catalogist\CatalogItem\CatalogProcessorInterface::class ),
			$this->createMock( \Catalogist\Product\ProductRepositoryInterface::class ),
			$this->createMock( \Catalogist\Preview\PreviewEngineInterface::class )
		);

		// Use reflection to test private method.
		$reflector = new \ReflectionClass( $preview_page );
		$method    = $reflector->getMethod( 'parse_print_settings' );
		$method->setAccessible( true );

		// Test with valid keys.
		$_GET['print_settings'] = base64_encode( json_encode(
			array(
				'page_size'   => 'A4',
				'orientation' => 'landscape',
				'columns'     => 3,
				'margins'     => array( 'top' => 20 ),
				'show_header' => true,
				'show_footer' => false,
				'show_cover'  => true,
				'evil_key'    => '<script>alert(1)</script>', // Should be filtered out
			)
		) );

		$result = $method->invoke( $preview_page );

		$this->assertIsArray( $result );
		$this->assertEquals( 'A4', $result['page_size'] );
		$this->assertEquals( 'landscape', $result['orientation'] );
		$this->assertEquals( 3, $result['columns'] );
		$this->assertArrayHasKey( 'margins', $result );
		$this->assertTrue( $result['show_header'] );
		$this->assertFalse( $result['show_footer'] );
		$this->assertTrue( $result['show_cover'] );
		$this->assertArrayNotHasKey( 'evil_key', $result );

		unset( $_GET['print_settings'] );
	}

	/**
	 * Test parse_print_settings() returns null for invalid base64.
	 *
	 * @return void
	 */
	public function test_parse_print_settings_invalid_base64(): void {
		$preview_page = new PreviewPage(
			$this->createMock( \Catalogist\Catalog\CatalogRepositoryInterface::class ),
			$this->createMock( \Catalogist\CatalogItem\CatalogProcessorInterface::class ),
			$this->createMock( \Catalogist\Product\ProductRepositoryInterface::class ),
			$this->createMock( \Catalogist\Preview\PreviewEngineInterface::class )
		);

		$reflector = new \ReflectionClass( $preview_page );
		$method    = $reflector->getMethod( 'parse_print_settings' );
		$method->setAccessible( true );

		$_GET['print_settings'] = 'not-valid-base64!';
		$result = $method->invoke( $preview_page );
		$this->assertNull( $result );

		unset( $_GET['print_settings'] );
	}

	/**
	 * Test parse_print_settings() returns null for empty input.
	 *
	 * @return void
	 */
	public function test_parse_print_settings_empty(): void {
		$preview_page = new PreviewPage(
			$this->createMock( \Catalogist\Catalog\CatalogRepositoryInterface::class ),
			$this->createMock( \Catalogist\CatalogItem\CatalogProcessorInterface::class ),
			$this->createMock( \Catalogist\Product\ProductRepositoryInterface::class ),
			$this->createMock( \Catalogist\Preview\PreviewEngineInterface::class )
		);

		$reflector = new \ReflectionClass( $preview_page );
		$method    = $reflector->getMethod( 'parse_print_settings' );
		$method->setAccessible( true );

		unset( $_GET['print_settings'] );
		$result = $method->invoke( $preview_page );
		$this->assertNull( $result );
	}

	/**
	 * Test parse_print_settings() returns null for non-array JSON.
	 *
	 * @return void
	 */
	public function test_parse_print_settings_non_array(): void {
		$preview_page = new PreviewPage(
			$this->createMock( \Catalogist\Catalog\CatalogRepositoryInterface::class ),
			$this->createMock( \Catalogist\CatalogItem\CatalogProcessorInterface::class ),
			$this->createMock( \Catalogist\Product\ProductRepositoryInterface::class ),
			$this->createMock( \Catalogist\Preview\PreviewEngineInterface::class )
		);

		$reflector = new \ReflectionClass( $preview_page );
		$method    = $reflector->getMethod( 'parse_print_settings' );
		$method->setAccessible( true );

		$_GET['print_settings'] = base64_encode( json_encode( 'not-an-array' ) );
		$result = $method->invoke( $preview_page );
		$this->assertNull( $result );

		unset( $_GET['print_settings'] );
	}
}