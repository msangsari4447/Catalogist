<?php
/**
 * Tests for PrintEngine.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Tests\Unit\Print;

use Catalogist\Catalog\Catalog;
use Catalogist\CatalogItem\CatalogItem;
use Catalogist\Print\PrintEngine;
use Catalogist\Print\PrintEngineInterface;
use Catalogist\Template\TemplateEngineInterface;
use Catalogist\Template\TemplateLoaderInterface;
use Catalogist\Template\TemplateRendererInterface;
use Catalogist\Template\TemplateContextBuilderInterface;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Tests for PrintEngine.
 */
class PrintEngineTest extends TestCase {

	/**
	 * Template engine mock.
	 *
	 * @var TemplateEngineInterface
	 */
	private TemplateEngineInterface $template_engine;

	/**
	 * Catalog mock.
	 *
	 * @var Catalog
	 */
	private Catalog $catalog;

	/**
	 * Catalog items mock.
	 *
	 * @var array<CatalogItem>
	 */
	private array $items;

	protected function setUp(): void {
		parent::setUp();

		// Create a minimal template engine mock.
		$this->template_engine = $this->createMock( TemplateEngineInterface::class );

		// Create a minimal catalog.
		$this->catalog = new Catalog();
		$this->catalog->set_id( 1 );
		$this->catalog->set_title( 'Test Catalog' );
		$this->catalog->set_print_settings( array(
			'page_size'   => 'a4',
			'orientation' => 'portrait',
			'margins'     => array(
				'top'    => 20,
				'right'  => 20,
				'bottom' => 20,
				'left'   => 20,
			),
			'columns'     => 2,
		) );

		// Create a minimal catalog item.
		$this->items = array(
			new CatalogItem(
				id: 10,
				type: 'product',
				parent_product_id: 0,
				title: 'Test Product',
				sku: 'TEST-001',
				price: '29.99',
				regular_price: '29.99',
				sale_price: '',
				description: 'Test description',
				short_description: 'Short test',
				image: null,
				gallery: array(),
				categories: array(),
				tags: array(),
				attributes: array(),
				stock_status: 'instock',
				stock_quantity: 10,
				permalink: 'https://example.com/product/test',
				parent_product: null,
				variation_table: null,
				metadata: array()
			),
		);
	}

	/**
	 * Test PrintEngine instantiation.
	 */
	public function test_print_engine_instantiation(): void {
		$engine = new PrintEngine( $this->template_engine, 'assets/css/print.css' );

		$this->assertInstanceOf( PrintEngineInterface::class, $engine );
		$this->assertInstanceOf( PrintEngine::class, $engine );
	}

	/**
	 * Test generatePrintHTML calls template engine.
	 */
	public function test_generate_print_html_calls_template_engine(): void {
		$engine = new PrintEngine( $this->template_engine, 'assets/css/print.css' );

		// Expect renderCatalog to be called once.
		$this->template_engine
			->method( 'renderCatalog' )
			->willReturn( '<div class="catalogist-catalog">Test</div>' );

		$html = $engine->generatePrintHTML( $this->catalog, $this->items );

		$this->assertStringContainsString( 'catalogist-catalog', $html );
		$this->assertStringContainsString( 'data-print-mode="true"', $html );
		$this->assertStringContainsString( '<style', $html );
		$this->assertStringContainsString( '@media print', $html );
	}

	/**
	 * Test generatePrintHTML injects print attributes.
	 */
	public function test_generate_print_html_injects_print_attributes(): void {
		$engine = new PrintEngine( $this->template_engine, 'assets/css/print.css' );

		$this->template_engine
			->method( 'renderCatalog' )
			->willReturn( '<div class="catalogist-catalog">Content</div>' );

		$html = $engine->generatePrintHTML( $this->catalog, $this->items );

		$this->assertStringContainsString( 'data-print-mode="true"', $html );
		$this->assertStringContainsString( 'data-orientation="portrait"', $html );
		$this->assertStringContainsString( 'data-page-size="A4"', $html );
		$this->assertStringContainsString( 'data-columns="2"', $html );
	}

	/**
	 * Test generatePrintHTML respects override settings.
	 */
	public function test_generate_print_html_respects_override_settings(): void {
		$engine = new PrintEngine( $this->template_engine, 'assets/css/print.css' );

		$this->template_engine
			->method( 'renderCatalog' )
			->willReturn( '<div class="catalogist-catalog">Content</div>' );

		$override = array(
			'orientation' => 'landscape',
			'columns'     => 4,
			'page_size'   => 'a4',
			'margins'     => array(
				'top'    => 10,
				'right'  => 15,
				'bottom' => 10,
				'left'   => 15,
			),
		);

		$html = $engine->generatePrintHTML( $this->catalog, $this->items, $override );

		$this->assertStringContainsString( 'data-orientation="landscape"', $html );
		$this->assertStringContainsString( 'data-columns="4"', $html );
	}

	/**
	 * Test generatePrintCSS contains @page rule.
	 */
	public function test_generate_print_css_contains_page_rule(): void {
		$engine = new PrintEngine( $this->template_engine, 'assets/css/print.css' );

		$css = $engine->generatePrintCSS( array(
			'page_size'   => 'a4',
			'orientation' => 'portrait',
			'margins'     => array( 'top' => 20, 'right' => 15, 'bottom' => 20, 'left' => 15 ),
			'columns'     => 2,
		) );

		$this->assertStringContainsString( '@page', $css );
		$this->assertStringContainsString( 'size: A4 portrait', $css );
		$this->assertStringContainsString( 'margin: 20mm 15mm 20mm 15mm', $css );
	}

	/**
	 * Test generatePrintCSS handles landscape orientation.
	 */
	public function test_generate_print_css_landscape(): void {
		$engine = new PrintEngine( $this->template_engine, 'assets/css/print.css' );

		$css = $engine->generatePrintCSS( array(
			'page_size'   => 'a4',
			'orientation' => 'landscape',
			'margins'     => array( 'top' => 15, 'right' => 15, 'bottom' => 15, 'left' => 15 ),
			'columns'     => 3,
		) );

		$this->assertStringContainsString( 'size: A4 landscape', $css );
		$this->assertStringContainsString( 'margin: 15mm 15mm 15mm 15mm', $css );
	}

	/**
	 * Test generatePrintCSS contains page-break rules.
	 */
	public function test_generate_print_css_contains_page_break_rules(): void {
		$engine = new PrintEngine( $this->template_engine, 'assets/css/print.css' );

		$css = $engine->generatePrintCSS( array(
			'page_size'   => 'a4',
			'orientation' => 'portrait',
			'margins'     => array( 'top' => 20, 'right' => 20, 'bottom' => 20, 'left' => 20 ),
			'columns'     => 2,
		) );

		$this->assertStringContainsString( 'break-inside: avoid', $css );
		$this->assertStringContainsString( 'page-break-inside: avoid', $css );
		$this->assertStringContainsString( '.catalogist-product-card', $css );
		$this->assertStringContainsString( '.catalogist-variation-table', $css );
	}

	/**
	 * Test generatePrintCSS generates column rules.
	 */
	public function test_generate_print_css_column_rules(): void {
		$engine = new PrintEngine( $this->template_engine, 'assets/css/print.css' );

		$css = $engine->generatePrintCSS( array(
			'page_size'   => 'a4',
			'orientation' => 'portrait',
			'margins'     => array( 'top' => 20, 'right' => 20, 'bottom' => 20, 'left' => 20 ),
			'columns'     => 3,
		) );

		$this->assertStringContainsString( 'column-count: 1', $css );
		$this->assertStringContainsString( 'column-count: 2', $css );
		$this->assertStringContainsString( 'column-count: 3', $css );
		$this->assertStringContainsString( 'column-count: 4', $css );
	}

	/**
	 * Test generatePrintCSS includes cover page break.
	 */
	public function test_generate_print_css_cover_break(): void {
		$engine = new PrintEngine( $this->template_engine, 'assets/css/print.css' );

		$css = $engine->generatePrintCSS( array(
			'page_size'   => 'a4',
			'orientation' => 'portrait',
			'margins'     => array( 'top' => 20, 'right' => 20, 'bottom' => 20, 'left' => 20 ),
			'columns'     => 2,
			'show_cover'  => true,
		) );

		$this->assertStringContainsString( '.catalogist-cover ~ .catalogist-catalog', $css );
		$this->assertStringContainsString( 'break-before: page', $css );
	}

	/**
	 * Test generatePrintPreviewURL returns valid URL.
	 */
	public function test_generate_print_preview_url(): void {
		$engine = new PrintEngine( $this->template_engine, 'assets/css/print.css' );

		// Need WordPress functions for home_url().
		$url = $engine->generatePrintPreviewURL( 42 );

		$this->assertStringContainsString( 'catalogist_print=1', $url );
		$this->assertStringContainsString( 'catalog_id=42', $url );
	}

	/**
	 * Test generatePrintPreviewURL with settings.
	 */
	public function test_generate_print_preview_url_with_settings(): void {
		$engine = new PrintEngine( $this->template_engine, 'assets/css/print.css' );

		$settings = array( 'orientation' => 'landscape', 'columns' => 3 );
		$url = $engine->generatePrintPreviewURL( 42, $settings );

		$this->assertStringContainsString( 'catalogist_print=1', $url );
		$this->assertStringContainsString( 'catalog_id=42', $url );
		$this->assertStringContainsString( 'print_settings', $url );
	}

	/**
	 * Test generatePrintHTML injects CSS into head.
	 */
	public function test_generate_print_html_injects_css_into_head(): void {
		$engine = new PrintEngine( $this->template_engine, 'assets/css/print.css' );

		$html_with_head = '<html><head><title>Test</title></head><body><div class="catalogist-catalog">Content</div></body></html>';

		$this->template_engine
			->method( 'renderCatalog' )
			->willReturn( $html_with_head );

		$output = $engine->generatePrintHTML( $this->catalog, $this->items );

		$this->assertStringContainsString( '<style', $output );
		$this->assertStringContainsString( '@page', $output );
		$this->assertStringContainsString( '</head>', $output );
	}

	/**
	 * Test generatePrintHTML handles missing head tag.
	 */
	public function test_generate_print_html_handles_missing_head(): void {
		$engine = new PrintEngine( $this->template_engine, 'assets/css/print.css' );

		$this->template_engine
			->method( 'renderCatalog' )
			->willReturn( '<div class="catalogist-catalog">Content</div>' );

		$output = $engine->generatePrintHTML( $this->catalog, $this->items );

		$this->assertStringContainsString( '<style', $output );
		$this->assertStringContainsString( '@page', $output );
	}

	/**
	 * Test print settings normalization — clamps columns.
	 */
	public function test_print_settings_clamps_columns(): void {
		$engine = new PrintEngine( $this->template_engine, 'assets/css/print.css' );

		$override = array( 'columns' => 10 );
		$reflection = new ReflectionClass( $engine );
		$method = $reflection->getMethod( 'buildPrintSettings' );
		$method->setAccessible( true );

		$settings = $method->invoke( $engine, $this->catalog, $override );

		$this->assertSame( 4, $settings['columns'] );

		$override = array( 'columns' => 0 );
		$settings = $method->invoke( $engine, $this->catalog, $override );
		$this->assertSame( 1, $settings['columns'] );
	}

	/**
	 * Test print settings normalization — valid orientation.
	 */
	public function test_print_settings_valid_orientation(): void {
		$engine = new PrintEngine( $this->template_engine, 'assets/css/print.css' );

		$reflection = new ReflectionClass( $engine );
		$method = $reflection->getMethod( 'buildPrintSettings' );
		$method->setAccessible( true );

		// Valid orientation should pass through.
		$settings = $method->invoke( $engine, $this->catalog, array( 'orientation' => 'landscape' ) );
		$this->assertSame( 'landscape', $settings['orientation'] );

		// Invalid orientation should fall back to portrait.
		$settings = $method->invoke( $engine, $this->catalog, array( 'orientation' => 'invalid' ) );
		$this->assertSame( 'portrait', $settings['orientation'] );
	}

	/**
	 * Test print settings normalization — valid page_size.
	 */
	public function test_print_settings_valid_page_size(): void {
		$engine = new PrintEngine( $this->template_engine, 'assets/css/print.css' );

		$reflection = new ReflectionClass( $engine );
		$method = $reflection->getMethod( 'buildPrintSettings' );
		$method->setAccessible( true );

		$settings = $method->invoke( $engine, $this->catalog, array( 'page_size' => 'letter' ) );
		$this->assertSame( 'LETTER', $settings['page_size'] );
	}

	/**
	 * Test margins normalization.
	 */
	public function test_print_settings_margins_normalized(): void {
		$engine = new PrintEngine( $this->template_engine, 'assets/css/print.css' );

		$reflection = new ReflectionClass( $engine );
		$method = $reflection->getMethod( 'buildPrintSettings' );
		$method->setAccessible( true );

		$settings = $method->invoke( $engine, $this->catalog, array(
			'margins' => array(
				'top'    => 10,
				'right'  => 15,
				'bottom' => 20,
				'left'   => 25,
			),
		) );

		$this->assertSame( 10.0, $settings['margins']['top'] );
		$this->assertSame( 15.0, $settings['margins']['right'] );
		$this->assertSame( 20.0, $settings['margins']['bottom'] );
		$this->assertSame( 25.0, $settings['margins']['left'] );
	}

	/**
	 * Test margins fall back to defaults when not provided.
	 */
	public function test_print_settings_margins_fallback(): void {
		$engine = new PrintEngine( $this->template_engine, 'assets/css/print.css' );

		$reflection = new ReflectionClass( $engine );
		$method = $reflection->getMethod( 'buildPrintSettings' );
		$method->setAccessible( true );

		$settings = $method->invoke( $engine, $this->catalog, array() );

		$this->assertSame( 20.0, $settings['margins']['top'] );
		$this->assertSame( 20.0, $settings['margins']['right'] );
		$this->assertSame( 20.0, $settings['margins']['bottom'] );
		$this->assertSame( 20.0, $settings['margins']['left'] );
	}

	/**
	 * Test architecture: PrintEngine depends on interface, not concrete.
	 */
	public function test_print_engine_depends_on_interface(): void {
		$reflection = new ReflectionClass( PrintEngine::class );
		$constructor = $reflection->getConstructor();
		$params = $constructor->getParameters();

		$templateEngineParam = $params[0] ?? null;
		$this->assertNotNull( $templateEngineParam );
		$this->assertSame( TemplateEngineInterface::class, $templateEngineParam->getType()->getName() );
	}

	/**
	 * Test CSS does not contain screen-only styles.
	 */
	public function test_print_css_is_print_only(): void {
		$engine = new PrintEngine( $this->template_engine, 'assets/css/print.css' );

		$css = $engine->generatePrintCSS( array(
			'page_size'   => 'a4',
			'orientation' => 'portrait',
			'margins'     => array( 'top' => 20, 'right' => 20, 'bottom' => 20, 'left' => 20 ),
			'columns'     => 2,
		) );

		// All CSS should be inside @page or element selectors — no body/screen rules.
		$this->assertStringContainsString( '@page', $css );
		$this->assertStringContainsString( '@media print', $css );
	}

	/**
	 * Test RTL CSS is included.
	 */
	public function test_print_css_includes_rtl(): void {
		$engine = new PrintEngine( $this->template_engine, 'assets/css/print.css' );

		$css = $engine->generatePrintCSS( array(
			'page_size'   => 'a4',
			'orientation' => 'portrait',
			'margins'     => array( 'top' => 20, 'right' => 20, 'bottom' => 20, 'left' => 20 ),
			'columns'     => 2,
		) );

		$this->assertStringContainsString( 'direction: rtl', $css );
		$this->assertStringContainsString( 'unicode-bidi: embed', $css );
	}
}
