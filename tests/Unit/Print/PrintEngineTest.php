<?php
/**
 * Unit tests for PrintEngine.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Tests\Unit\Print;

use Catalogist\Catalog\Catalog;
use Catalogist\Print\PrintEngine;
use Catalogist\Template\TemplateEngineInterface;
use PHPUnit\Framework\TestCase;

/**
 * Tests for PrintEngine.
 */
class PrintEngineTest extends TestCase {

	/**
	 * Test generatePrintCSS() generates valid CSS.
	 *
	 * @return void
	 */
	public function test_generate_print_css(): void {
		$mock_template_engine = $this->createMock( TemplateEngineInterface::class );
		$print_engine = new PrintEngine( $mock_template_engine, '' );

		$reflector = new \ReflectionClass( $print_engine );
		$method    = $reflector->getMethod( 'generatePrintCSS' );
		$method->setAccessible( true );

		$settings = array(
			'page_size'    => 'A4',
			'orientation'  => 'portrait',
			'columns'      => 2,
			'margins'      => array( 'top' => 20, 'right' => 20, 'bottom' => 20, 'left' => 20 ),
			'show_cover'   => false,
			'show_header'  => true,
			'show_footer'  => true,
		);

		$css = $method->invoke( $print_engine, $settings );

		$this->assertIsString( $css );
		$this->assertStringContainsString( '@page', $css );
		$this->assertStringContainsString( 'size: A4 portrait', $css );
		$this->assertStringContainsString( 'margin: 20mm', $css );
		$this->assertStringContainsString( 'break-inside: avoid', $css );
		$this->assertStringContainsString( 'page-break-inside: avoid', $css );
	}

	/**
	 * Test generatePrintCSS() handles landscape orientation.
	 *
	 * @return void
	 */
	public function test_generate_print_css_landscape(): void {
		$mock_template_engine = $this->createMock( TemplateEngineInterface::class );
		$print_engine = new PrintEngine( $mock_template_engine, '' );

		$reflector = new \ReflectionClass( $print_engine );
		$method    = $reflector->getMethod( 'generatePrintCSS' );
		$method->setAccessible( true );

		$settings = array(
			'page_size'   => 'A4',
			'orientation' => 'landscape',
			'columns'     => 2,
		);

		$css = $method->invoke( $print_engine, $settings );

		$this->assertStringContainsString( 'size: A4 landscape', $css );
	}

	/**
	 * Test generatePrintCSS() handles A3 page size.
	 *
	 * @return void
	 */
	public function test_generate_print_css_a3(): void {
		$mock_template_engine = $this->createMock( TemplateEngineInterface::class );
		$print_engine = new PrintEngine( $mock_template_engine, '' );

		$reflector = new \ReflectionClass( $print_engine );
		$method    = $reflector->getMethod( 'generatePrintCSS' );
		$method->setAccessible( true );

		$settings = array(
			'page_size'  => 'A3',
			'orientation' => 'portrait',
			'columns'    => 2,
		);

		$css = $method->invoke( $print_engine, $settings );

		$this->assertStringContainsString( 'size: A3 portrait', $css );
	}

	/**
	 * Test generatePrintCSS() clamps columns to 1-4.
	 *
	 * @return void
	 */
	public function test_generate_print_css_clamps_columns(): void {
		$mock_template_engine = $this->createMock( TemplateEngineInterface::class );
		$print_engine = new PrintEngine( $mock_template_engine, '' );

		$reflector = new \ReflectionClass( $print_engine );
		$method    = $reflector->getMethod( 'generatePrintCSS' );
		$method->setAccessible( true );

		$settings = array(
			'columns' => 10, // Should be clamped to 4
		);

		$css = $method->invoke( $print_engine, $settings );

		$this->assertStringContainsString( 'column-count: 4', $css );
	}

	/**
	 * Test generatePrintCSS() includes RTL support.
	 *
	 * @return void
	 */
	public function test_generate_print_css_rtl(): void {
		$mock_template_engine = $this->createMock( TemplateEngineInterface::class );
		$print_engine = new PrintEngine( $mock_template_engine, '' );

		$reflector = new \ReflectionClass( $print_engine );
		$method    = $reflector->getMethod( 'generatePrintCSS' );
		$method->setAccessible( true );

		$settings = array(
			'columns' => 2,
		);

		$css = $method->invoke( $print_engine, $settings );

		$this->assertStringContainsString( 'dir="rtl"', $css );
	}

	/**
	 * Test generatePrintPreviewURL() generates URL with nonce.
	 *
	 * @return void
	 */
	public function test_generate_print_preview_url(): void {
		$mock_template_engine = $this->createMock( TemplateEngineInterface::class );
		$print_engine = new PrintEngine( $mock_template_engine, '' );

		$url = $print_engine->generatePrintPreviewURL( 123 );

		$this->assertStringContainsString( 'catalogist_print=1', $url );
		$this->assertStringContainsString( 'catalog_id=123', $url );
	}

	/**
	 * Test generatePrintPreviewURL() includes print settings.
	 *
	 * @return void
	 */
	public function test_generate_print_preview_url_with_settings(): void {
		$mock_template_engine = $this->createMock( TemplateEngineInterface::class );
		$print_engine = new PrintEngine( $mock_template_engine, '' );

		$url = $print_engine->generatePrintPreviewURL(
			123,
			array( 'columns' => 3, 'orientation' => 'landscape' )
		);

		$this->assertStringContainsString( 'catalogist_print=1', $url );
		$this->assertStringContainsString( 'catalog_id=123', $url );
		$this->assertStringContainsString( 'print_settings=', $url );
	}
}