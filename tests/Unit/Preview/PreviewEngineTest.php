<?php
/**
 * Unit tests for PreviewEngine.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Tests\Unit\Preview;

use Catalogist\Catalog\Catalog;
use Catalogist\CatalogItem\CatalogItem;
use Catalogist\Preview\PreviewEngine;
use Catalogist\Preview\PreviewEngineInterface;
use Catalogist\Print\PrintEngineInterface;
use PHPUnit\Framework\TestCase;

/**
 * Test PreviewEngine.
 */
final class PreviewEngineTest extends TestCase {

	/**
	 * Mock print engine.
	 *
	 * @var PrintEngineInterface|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $mock_print_engine;

	/**
	 * Set up the test.
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->mock_print_engine = $this->createMock( PrintEngineInterface::class );
	}

	/**
	 * Test interface compliance.
	 */
	public function test_interface_compliance(): void {
		$engine = new PreviewEngine( $this->mock_print_engine );
		$this->assertInstanceOf( PreviewEngineInterface::class, $engine );
	}

	/**
	 * Test renderPreview delegates to PrintEngine.
	 */
	public function test_render_preview_delegates_to_print_engine(): void {
		$catalog = new Catalog();
		$catalog->set_id( 1 );
		$catalog->set_title( 'Test Catalog' );

		$items = array(
			new CatalogItem(
				id: 10,
				type: 'product',
				parent_product_id: 0,
				title: 'Test Product',
				sku: 'TEST-001',
				price: '29.99',
				regular_price: '29.99',
				sale_price: '',
				description: 'Test',
				short_description: 'Short',
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

		$expected_html = '<div class="catalogist-catalog">Test</div>';

		$this->mock_print_engine
			->expects( $this->once() )
			->method( 'generatePrintHTML' )
			->with( $catalog, $items, array( 'orientation' => 'portrait' ) )
			->willReturn( $expected_html );

		$engine = new PreviewEngine( $this->mock_print_engine );
		$result = $engine->renderPreview( $catalog, $items, array( 'orientation' => 'portrait' ) );

		$this->assertStringContainsString( 'catalogist-preview-paper', $result );
		$this->assertStringContainsString( $expected_html, $result );
		$this->assertStringContainsString( 'catalogist-preview-controls', $result );
		$this->assertStringContainsString( 'catalogist-preview-info', $result );
	}

	/**
	 * Test renderPreview includes paper classes.
	 */
	public function test_render_preview_includes_paper_classes(): void {
		$catalog = new Catalog();
		$catalog->set_id( 1 );
		$catalog->set_title( 'Test' );

		$this->mock_print_engine
			->method( 'generatePrintHTML' )
			->willReturn( '<div>content</div>' );

		$engine = new PreviewEngine( $this->mock_print_engine );
		$result = $engine->renderPreview( $catalog, array() );

		$this->assertStringContainsString( 'catalogist-preview-paper-portrait', $result );
		$this->assertStringNotContainsString( 'catalogist-preview-paper-landscape', $result );
	}

	/**
	 * Test renderPreview handles landscape orientation.
	 */
	public function test_render_preview_handles_landscape(): void {
		$catalog = new Catalog();
		$catalog->set_id( 1 );
		$catalog->set_title( 'Test' );

		$this->mock_print_engine
			->method( 'generatePrintHTML' )
			->willReturn( '<div>content</div>' );

		$engine = new PreviewEngine( $this->mock_print_engine );
		$result = $engine->renderPreview( $catalog, array(), array( 'orientation' => 'landscape' ) );

		$this->assertStringContainsString( 'catalogist-preview-paper-landscape', $result );
		$this->assertStringNotContainsString( 'catalogist-preview-paper-portrait', $result );
	}

	/**
	 * Test renderPreview includes info bar with settings.
	 */
	public function test_render_preview_includes_info_bar(): void {
		$catalog = new Catalog();
		$catalog->set_id( 1 );
		$catalog->set_title( 'Test' );

		$this->mock_print_engine
			->method( 'generatePrintHTML' )
			->willReturn( '<div>content</div>' );

		$engine = new PreviewEngine( $this->mock_print_engine );
		$result = $engine->renderPreview( $catalog, array(), array(
			'page_size'   => 'A4',
			'orientation' => 'portrait',
			'columns'     => 3,
			'margins'     => array( 'top' => 15, 'right' => 15, 'bottom' => 15, 'left' => 15 ),
		) );

		$this->assertStringContainsString( 'catalogist-preview-info', $result );
		$this->assertStringContainsString( 'Paper:', $result );
		$this->assertStringContainsString( 'Orientation:', $result );
		$this->assertStringContainsString( 'Columns:', $result );
		$this->assertStringContainsString( 'Margins:', $result );
	}

	/**
	 * Test renderPreview includes preview notice.
	 */
	public function test_render_preview_includes_notice(): void {
		$catalog = new Catalog();
		$catalog->set_id( 1 );
		$catalog->set_title( 'Test' );

		$this->mock_print_engine
			->method( 'generatePrintHTML' )
			->willReturn( '<div>content</div>' );

		$engine = new PreviewEngine( $this->mock_print_engine );
		$result = $engine->renderPreview( $catalog, array() );

		$this->assertStringContainsString( 'catalogist-preview-notice', $result );
		$this->assertStringContainsString( 'Preview approximates print output', $result );
	}

	/**
	 * Test getPreviewURL returns correct admin URL.
	 */
	public function test_get_preview_url(): void {
		$engine = new PreviewEngine( $this->mock_print_engine );
		$url = $engine->getPreviewURL( 123 );

		$this->assertStringContainsString( 'admin.php', $url );
		$this->assertStringContainsString( 'page=catalogist-preview', $url );
		$this->assertStringContainsString( 'catalog_id=123', $url );
	}

	/**
	 * Test getPreviewURL encodes settings.
	 */
	public function test_get_preview_url_encodes_settings(): void {
		$engine = new PreviewEngine( $this->mock_print_engine );
		$settings = array( 'orientation' => 'landscape', 'columns' => 4 );
		$url = $engine->getPreviewURL( 123, $settings );

		$this->assertStringContainsString( 'print_settings=', $url );

		// Decode to verify.
		parse_str( wp_parse_url( $url, PHP_URL_QUERY ), $query );
		$encoded = $query['print_settings'] ?? '';
		$json = base64_decode( $encoded, true );
		$decoded = json_decode( $json, true );

		$this->assertEquals( $settings, $decoded );
	}

	/**
	 * Test getPrintURL delegates to PrintEngine.
	 */
	public function test_get_print_url_delegates(): void {
		$expected_url = 'https://example.com/?catalogist_print=1&catalog_id=123';

		$this->mock_print_engine
			->expects( $this->once() )
			->method( 'generatePrintPreviewURL' )
			->with( 123, array( 'orientation' => 'landscape' ) )
			->willReturn( $expected_url );

		$engine = new PreviewEngine( $this->mock_print_engine );
		$url = $engine->getPrintURL( 123, array( 'orientation' => 'landscape' ) );

		$this->assertEquals( $expected_url, $url );
	}

	/**
	 * Test paper dimensions.
	 */
	public function test_paper_dimensions(): void {
		$engine = new PreviewEngine( $this->mock_print_engine );
		$this->assertEquals( 210, $engine->getPaperWidthMM() );
		$this->assertEquals( 297, $engine->getPaperHeightMM() );
	}

	/**
	 * Test shouldShowLoading returns false.
	 */
	public function test_should_show_loading(): void {
		$engine = new PreviewEngine( $this->mock_print_engine );
		$this->assertFalse( $engine->shouldShowLoading() );
	}

	/**
	 * Test renderPreview uses fallback defaults when no settings provided.
	 */
	public function test_render_preview_uses_fallback_defaults(): void {
		$catalog = new Catalog();
		$catalog->set_id( 1 );
		$catalog->set_title( 'Test' );

		$this->mock_print_engine
			->expects( $this->once() )
			->method( 'generatePrintHTML' )
			->with( $catalog, array(), null )
			->willReturn( '<div>content</div>' );

		$engine = new PreviewEngine( $this->mock_print_engine );
		$result = $engine->renderPreview( $catalog, array() );

		$this->assertStringContainsString( 'catalogist-preview-paper-portrait', $result );
		$this->assertStringContainsString( 'columns:2', $result );
	}
}
