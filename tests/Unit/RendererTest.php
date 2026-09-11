<?php

declare(strict_types=1);

namespace Catalogist\Tests\Unit;

use Catalogist\CatalogContext;
use Catalogist\CatalogItem;
use Catalogist\Renderer;
use Catalogist\Template;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Renderer (Stage 9).
 *
 * Pure unit tests; uses mock escaping when WordPress is not available.
 */
final class RendererTest extends TestCase {

	private Renderer $renderer;

	protected function setUp(): void {
		parent::setUp();
		$this->renderer = new Renderer();
	}

	/**
	 * Test rendering with all sections enabled.
	 */
	public function testRenderAllSectionsEnabled(): void {
		$context  = new CatalogContext( 1, 'en_US', 'USD' );
		$template = Template::default_configuration();
		$items    = array(
			new CatalogItem(
				1,
				'simple',
				null,
				'Product 1',
				'product-1',
				'SKU001',
				100.0,
				0.0,
				100.0,
				'instock',
				'http://image1.jpg',
				'http://permalink1.html',
				array(),
				array(),
				$context
			),
		);

		$html = $this->renderer->render( $context, $template, $items );

		$this->assertStringContainsString( 'catalogist-header', $html );
		$this->assertStringContainsString( 'catalogist-loop', $html );
		$this->assertStringContainsString( 'catalogist-card', $html );
		$this->assertStringContainsString( 'catalogist-footer', $html );
	}

	/**
	 * Test rendering with header disabled.
	 */
	public function testRenderHeaderDisabled(): void {
		$context            = new CatalogContext( 1, 'en_US', 'USD' );
		$template           = Template::default_configuration();
		$template['header'] = array(
			'enabled'    => false,
			'show_title' => true,
		);
		$items              = array();

		$html = $this->renderer->render( $context, $template, $items );

		$this->assertStringNotContainsString( 'catalogist-header', $html );
	}

	/**
	 * Test rendering with footer disabled.
	 */
	public function testRenderFooterDisabled(): void {
		$context            = new CatalogContext( 1, 'en_US', 'USD' );
		$template           = Template::default_configuration();
		$template['footer'] = array( 'enabled' => false );
		$items              = array();

		$html = $this->renderer->render( $context, $template, $items );

		$this->assertStringNotContainsString( 'catalogist-footer', $html );
	}

	/**
	 * Test rendering with empty items.
	 */
	public function testRenderEmptyItems(): void {
		$context  = new CatalogContext( 1, 'en_US', 'USD' );
		$template = Template::default_configuration();
		$items    = array();

		$html = $this->renderer->render( $context, $template, $items );

		$this->assertStringContainsString( 'catalogist-header', $html );
		$this->assertStringNotContainsString( 'catalogist-card', $html );
		$this->assertStringContainsString( 'catalogist-footer', $html );
	}

	/**
	 * Test rendering with multiple items.
	 */
	public function testRenderMultipleItems(): void {
		$context  = new CatalogContext( 1, 'en_US', 'USD' );
		$template = Template::default_configuration();
		$items    = array(
			new CatalogItem(
				1,
				'simple',
				null,
				'Product 1',
				'product-1',
				'SKU001',
				100.0,
				0.0,
				100.0,
				'instock',
				'http://image1.jpg',
				'http://permalink1.html',
				array(),
				array(),
				$context
			),
			new CatalogItem(
				2,
				'simple',
				null,
				'Product 2',
				'product-2',
				'SKU002',
				200.0,
				150.0,
				150.0,
				'instock',
				'http://image2.jpg',
				'http://permalink2.html',
				array(),
				array(),
				$context
			),
		);

		$html = $this->renderer->render( $context, $template, $items );

		$this->assertStringContainsString( 'Product 1', $html );
		$this->assertStringContainsString( 'Product 2', $html );
		$this->assertStringContainsString( 'catalogist-card', $html );
	}

	/**
	 * Test loop columns configuration.
	 */
	public function testRenderLoopColumns(): void {
		$context          = new CatalogContext( 1, 'en_US', 'USD' );
		$template         = Template::default_configuration();
		$template['loop'] = array( 'columns' => 4 );
		$items            = array(
			new CatalogItem(
				1,
				'simple',
				null,
				'Product',
				'product',
				'SKU',
				100.0,
				0.0,
				100.0,
				'instock',
				'',
				'http://permalink.html',
				array(),
				array(),
				$context
			),
		);

		$html = $this->renderer->render( $context, $template, $items );

		$this->assertStringContainsString( 'data-columns="4"', $html );
		$this->assertStringContainsString( 'grid-template-columns: repeat(4, 1fr)', $html );
	}

	/**
	 * Test loop columns with invalid value.
	 */
	public function testRenderLoopColumnsValidation(): void {
		$context          = new CatalogContext( 1, 'en_US', 'USD' );
		$template         = Template::default_configuration();
		$template['loop'] = array( 'columns' => 999 );
		$items            = array(
			new CatalogItem(
				1,
				'simple',
				null,
				'Product',
				'product',
				'SKU',
				100.0,
				0.0,
				100.0,
				'instock',
				'',
				'http://permalink.html',
				array(),
				array(),
				$context
			),
		);

		$html = $this->renderer->render( $context, $template, $items );

		$this->assertStringContainsString( 'data-columns="12"', $html );
		$this->assertStringContainsString( 'grid-template-columns: repeat(12, 1fr)', $html );
	}

	/**
	 * Test card visibility: show_image.
	 */
	public function testRenderCardShowImage(): void {
		$context          = new CatalogContext( 1, 'en_US', 'USD' );
		$template         = Template::default_configuration();
		$template['card'] = array(
			'show_image' => true,
			'show_title' => false,
			'show_price' => false,
			'show_sku'   => false,
			'show_stock' => false,
		);
		$items            = array(
			new CatalogItem(
				1,
				'simple',
				null,
				'Product',
				'product',
				'SKU',
				100.0,
				0.0,
				100.0,
				'instock',
				'http://image.jpg',
				'http://permalink.html',
				array(),
				array(),
				$context
			),
		);

		$html = $this->renderer->render( $context, $template, $items );

		$this->assertStringContainsString( 'catalogist-image', $html );
		$this->assertStringContainsString( 'http://image.jpg', $html );
	}

	/**
	 * Test card visibility: hide_image.
	 */
	public function testRenderCardHideImage(): void {
		$context          = new CatalogContext( 1, 'en_US', 'USD' );
		$template         = Template::default_configuration();
		$template['card'] = array(
			'show_image' => false,
			'show_title' => false,
			'show_price' => false,
			'show_sku'   => false,
			'show_stock' => false,
		);
		$items            = array(
			new CatalogItem(
				1,
				'simple',
				null,
				'Product',
				'product',
				'SKU',
				100.0,
				0.0,
				100.0,
				'instock',
				'http://image.jpg',
				'http://permalink.html',
				array(),
				array(),
				$context
			),
		);

		$html = $this->renderer->render( $context, $template, $items );

		$this->assertStringNotContainsString( 'catalogist-image', $html );
	}

	/**
	 * Test card visibility: show_title.
	 */
	public function testRenderCardShowTitle(): void {
		$context          = new CatalogContext( 1, 'en_US', 'USD' );
		$template         = Template::default_configuration();
		$template['card'] = array(
			'show_image' => false,
			'show_title' => true,
			'show_price' => false,
			'show_sku'   => false,
			'show_stock' => false,
		);
		$items            = array(
			new CatalogItem(
				1,
				'simple',
				null,
				'Test Product Title',
				'product',
				'SKU',
				100.0,
				0.0,
				100.0,
				'instock',
				'',
				'http://permalink.html',
				array(),
				array(),
				$context
			),
		);

		$html = $this->renderer->render( $context, $template, $items );

		$this->assertStringContainsString( 'catalogist-title', $html );
		$this->assertStringContainsString( 'Test Product Title', $html );
	}

	/**
	 * Test card visibility: show_price.
	 */
	public function testRenderCardShowPrice(): void {
		$context          = new CatalogContext( 1, 'en_US', 'USD' );
		$template         = Template::default_configuration();
		$template['card'] = array(
			'show_image' => false,
			'show_title' => false,
			'show_price' => true,
			'show_sku'   => false,
			'show_stock' => false,
		);
		$items            = array(
			new CatalogItem(
				1,
				'simple',
				null,
				'Product',
				'product',
				'SKU',
				100.0,
				0.0,
				100.0,
				'instock',
				'',
				'http://permalink.html',
				array(),
				array(),
				$context
			),
		);

		$html = $this->renderer->render( $context, $template, $items );

		$this->assertStringContainsString( 'catalogist-price', $html );
		$this->assertStringContainsString( '100.00', $html );
		$this->assertStringContainsString( 'USD', $html );
	}

	/**
	 * Test card price with sale price.
	 */
	public function testRenderCardSalePrice(): void {
		$context          = new CatalogContext( 1, 'en_US', 'EUR' );
		$template         = Template::default_configuration();
		$template['card'] = array(
			'show_image' => false,
			'show_title' => false,
			'show_price' => true,
			'show_sku'   => false,
			'show_stock' => false,
		);
		$items            = array(
			new CatalogItem(
				1,
				'simple',
				null,
				'Product',
				'product',
				'SKU',
				100.0,
				75.0,
				75.0,
				'instock',
				'',
				'http://permalink.html',
				array(),
				array(),
				$context
			),
		);

		$html = $this->renderer->render( $context, $template, $items );

		$this->assertStringContainsString( 'regular-price', $html );
		$this->assertStringContainsString( 'sale-price', $html );
		$this->assertStringContainsString( '100.00', $html );
		$this->assertStringContainsString( '75.00', $html );
		$this->assertStringContainsString( 'EUR', $html );
	}

	/**
	 * Test card visibility: show_sku.
	 */
	public function testRenderCardShowSku(): void {
		$context          = new CatalogContext( 1, 'en_US', 'USD' );
		$template         = Template::default_configuration();
		$template['card'] = array(
			'show_image' => false,
			'show_title' => false,
			'show_price' => false,
			'show_sku'   => true,
			'show_stock' => false,
		);
		$items            = array(
			new CatalogItem(
				1,
				'simple',
				null,
				'Product',
				'product',
				'TEST-SKU-123',
				100.0,
				0.0,
				100.0,
				'instock',
				'',
				'http://permalink.html',
				array(),
				array(),
				$context
			),
		);

		$html = $this->renderer->render( $context, $template, $items );

		$this->assertStringContainsString( 'catalogist-sku', $html );
		$this->assertStringContainsString( 'TEST-SKU-123', $html );
	}

	/**
	 * Test card visibility: hide_sku when empty.
	 */
	public function testRenderCardHideSkuWhenEmpty(): void {
		$context          = new CatalogContext( 1, 'en_US', 'USD' );
		$template         = Template::default_configuration();
		$template['card'] = array(
			'show_image' => false,
			'show_title' => false,
			'show_price' => false,
			'show_sku'   => true,
			'show_stock' => false,
		);
		$items            = array(
			new CatalogItem(
				1,
				'simple',
				null,
				'Product',
				'product',
				'',
				100.0,
				0.0,
				100.0,
				'instock',
				'',
				'http://permalink.html',
				array(),
				array(),
				$context
			),
		);

		$html = $this->renderer->render( $context, $template, $items );

		$this->assertStringNotContainsString( 'catalogist-sku', $html );
	}

	/**
	 * Test card visibility: show_stock.
	 */
	public function testRenderCardShowStock(): void {
		$context          = new CatalogContext( 1, 'en_US', 'USD' );
		$template         = Template::default_configuration();
		$template['card'] = array(
			'show_image' => false,
			'show_title' => false,
			'show_price' => false,
			'show_sku'   => false,
			'show_stock' => true,
		);
		$items            = array(
			new CatalogItem(
				1,
				'simple',
				null,
				'Product',
				'product',
				'SKU',
				100.0,
				0.0,
				100.0,
				'instock',
				'',
				'http://permalink.html',
				array(),
				array(),
				$context
			),
		);

		$html = $this->renderer->render( $context, $template, $items );

		$this->assertStringContainsString( 'catalogist-stock', $html );
		$this->assertStringContainsString( 'in-stock', $html );
	}

	/**
	 * Test card stock out of stock.
	 */
	public function testRenderCardStockOutOfStock(): void {
		$context          = new CatalogContext( 1, 'en_US', 'USD' );
		$template         = Template::default_configuration();
		$template['card'] = array(
			'show_image' => false,
			'show_title' => false,
			'show_price' => false,
			'show_sku'   => false,
			'show_stock' => true,
		);
		$items            = array(
			new CatalogItem(
				1,
				'simple',
				null,
				'Product',
				'product',
				'SKU',
				100.0,
				0.0,
				100.0,
				'outofstock',
				'',
				'http://permalink.html',
				array(),
				array(),
				$context
			),
		);

		$html = $this->renderer->render( $context, $template, $items );

		$this->assertStringContainsString( 'catalogist-stock', $html );
		$this->assertStringContainsString( 'out-of-stock', $html );
	}

	/**
	 * Test XSS escaping in title.
	 */
	public function testXssEscapingTitle(): void {
		$context          = new CatalogContext( 1, 'en_US', 'USD' );
		$template         = Template::default_configuration();
		$template['card'] = array(
			'show_image' => false,
			'show_title' => true,
			'show_price' => false,
			'show_sku'   => false,
			'show_stock' => false,
		);
		$items            = array(
			new CatalogItem(
				1,
				'simple',
				null,
				'<script>alert("xss")</script>',
				'product',
				'SKU',
				100.0,
				0.0,
				100.0,
				'instock',
				'',
				'http://permalink.html',
				array(),
				array(),
				$context
			),
		);

		$html = $this->renderer->render( $context, $template, $items );

		$this->assertStringNotContainsString( '<script>', $html );
		$this->assertStringContainsString( '&lt;script&gt;', $html );
	}

	/**
	 * Test XSS escaping in SKU.
	 */
	public function testXssEscapingSku(): void {
		$context          = new CatalogContext( 1, 'en_US', 'USD' );
		$template         = Template::default_configuration();
		$template['card'] = array(
			'show_image' => false,
			'show_title' => false,
			'show_price' => false,
			'show_sku'   => true,
			'show_stock' => false,
		);
		$items            = array(
			new CatalogItem(
				1,
				'simple',
				null,
				'Product',
				'product',
				'<img src=x onerror=alert("xss")>',
				100.0,
				0.0,
				100.0,
				'instock',
				'',
				'http://permalink.html',
				array(),
				array(),
				$context
			),
		);

		$html = $this->renderer->render( $context, $template, $items );

		// Ensure the dangerous attribute is escaped and not executable
		$this->assertStringNotContainsString( '<img src=x onerror', $html );
		$this->assertStringContainsString( '&lt;img', $html );
		$this->assertStringContainsString( '&quot;', $html );
	}

	/**
	 * Test URL escaping in image src.
	 */
	public function testUrlEscapingImage(): void {
		$context          = new CatalogContext( 1, 'en_US', 'USD' );
		$template         = Template::default_configuration();
		$template['card'] = array(
			'show_image' => true,
			'show_title' => false,
			'show_price' => false,
			'show_sku'   => false,
			'show_stock' => false,
		);
		$items            = array(
			new CatalogItem(
				1,
				'simple',
				null,
				'Product',
				'product',
				'SKU',
				100.0,
				0.0,
				100.0,
				'instock',
				'javascript:alert("xss")',
				'http://permalink.html',
				array(),
				array(),
				$context
			),
		);

		$html = $this->renderer->render( $context, $template, $items );

		$this->assertStringNotContainsString( 'javascript:', $html );
	}

	/**
	 * Test simple product rendering.
	 */
	public function testRenderSimpleProduct(): void {
		$context  = new CatalogContext( 1, 'en_US', 'USD' );
		$template = Template::default_configuration();
		$items    = array(
			new CatalogItem(
				1,
				'simple',
				null,
				'Simple Product',
				'simple-product',
				'SIMPLE-001',
				50.0,
				0.0,
				50.0,
				'instock',
				'http://image.jpg',
				'http://permalink.html',
				array(),
				array(),
				$context
			),
		);

		$html = $this->renderer->render( $context, $template, $items );

		$this->assertStringContainsString( 'Simple Product', $html );
		$this->assertStringContainsString( '50.00', $html );
	}

	/**
	 * Test variable product rendering.
	 */
	public function testRenderVariableProduct(): void {
		$context  = new CatalogContext( 1, 'en_US', 'USD' );
		$template = Template::default_configuration();
		$items    = array(
			new CatalogItem(
				1,
				'variable',
				null,
				'Variable Product',
				'variable-product',
				'VAR-001',
				100.0,
				80.0,
				80.0,
				'instock',
				'http://image.jpg',
				'http://permalink.html',
				array(),
				array(),
				$context
			),
		);

		$html = $this->renderer->render( $context, $template, $items );

		$this->assertStringContainsString( 'Variable Product', $html );
		$this->assertStringContainsString( '100.00', $html );
		$this->assertStringContainsString( '80.00', $html );
	}

	/**
	 * Test variation product rendering.
	 */
	public function testRenderVariationProduct(): void {
		$context  = new CatalogContext( 1, 'en_US', 'USD' );
		$template = Template::default_configuration();
		$items    = array(
			new CatalogItem(
				2,
				'variation',
				1,
				'Variation Product - Red',
				'variation-product-red',
				'VAR-001-RED',
				75.0,
				0.0,
				75.0,
				'instock',
				'http://image-red.jpg',
				'http://permalink-red.html',
				array(),
				array(),
				$context
			),
		);

		$html = $this->renderer->render( $context, $template, $items );

		$this->assertStringContainsString( 'Variation Product - Red', $html );
		$this->assertStringContainsString( '75.00', $html );
	}

	/**
	 * Test context language and currency.
	 */
	public function testContextLanguageCurrency(): void {
		$context  = new CatalogContext( 1, 'de_DE', 'EUR' );
		$template = Template::default_configuration();
		$items    = array(
			new CatalogItem(
				1,
				'simple',
				null,
				'Product',
				'product',
				'SKU',
				99.99,
				0.0,
				99.99,
				'instock',
				'',
				'http://permalink.html',
				array(),
				array(),
				$context
			),
		);

		$html = $this->renderer->render( $context, $template, $items );

		$this->assertStringContainsString( 'EUR', $html );
		$this->assertSame( 'de_DE', $context->language );
		$this->assertSame( 'EUR', $context->currency );
	}

	/**
	 * Test rendering with missing card config defaults to showing all.
	 */
	public function testRenderMissingCardConfigDefaults(): void {
		$context  = new CatalogContext( 1, 'en_US', 'USD' );
		$template = array(
			'header' => array(
				'enabled'    => false,
				'show_title' => false,
			),
			'footer' => array( 'enabled' => false ),
			'loop'   => array( 'columns' => 3 ),
			'card'   => array(),
		);
		$items    = array(
			new CatalogItem(
				1,
				'simple',
				null,
				'Product',
				'product',
				'SKU',
				100.0,
				0.0,
				100.0,
				'instock',
				'http://image.jpg',
				'http://permalink.html',
				array(),
				array(),
				$context
			),
		);

		$html = $this->renderer->render( $context, $template, $items );

		$this->assertStringContainsString( 'catalogist-image', $html );
		$this->assertStringContainsString( 'catalogist-title', $html );
		$this->assertStringContainsString( 'catalogist-price', $html );
	}

	/**
	 * Test rendering with image URL empty hides image.
	 */
	public function testRenderImageUrlEmpty(): void {
		$context          = new CatalogContext( 1, 'en_US', 'USD' );
		$template         = Template::default_configuration();
		$template['card'] = array(
			'show_image' => true,
			'show_title' => false,
			'show_price' => false,
			'show_sku'   => false,
			'show_stock' => false,
		);
		$items            = array(
			new CatalogItem(
				1,
				'simple',
				null,
				'Product',
				'product',
				'SKU',
				100.0,
				0.0,
				100.0,
				'instock',
				'',
				'http://permalink.html',
				array(),
				array(),
				$context
			),
		);

		$html = $this->renderer->render( $context, $template, $items );

		$this->assertStringNotContainsString( 'catalogist-image', $html );
	}
}
