<?php
/**
 * Integration tests for TemplateEngine.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Tests\Integration\Template;

use Catalogist\Catalog\Catalog;
use Catalogist\CatalogItem\CatalogItem;
use Catalogist\Template\Loader\FileTemplateLoader;
use Catalogist\Template\Renderer\FileTemplateRenderer;
use Catalogist\Template\TemplateContextBuilder;
use Catalogist\Template\TemplateEngine;
use PHPUnit\Framework\TestCase;

/**
 * Tests for TemplateEngine integration.
 */
class TemplateEngineTest extends TestCase {

	/**
	 * Plugin directory path.
	 *
	 * @var string
	 */
	private string $pluginDir;

	/**
	 * Set up test fixtures.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		// Determine plugin directory from test file location.
		$this->pluginDir = dirname( dirname( dirname( dirname( __DIR__ ) ) ) ) . '/';
	}

	/**
	 * Create a minimal Catalog for testing.
	 *
	 * @return Catalog
	 */
	private function createCatalog(): Catalog {
		$catalog = new Catalog();
		$catalog->set_id( 100 );
		$catalog->set_title( 'Integration Test Catalog' );
		$catalog->set_slug( 'integration-test' );
		$catalog->set_template_id( 0 );
		$catalog->set_layout_settings( array() );
		$catalog->set_print_settings( array() );

		return $catalog;
	}

	/**
	 * Create a minimal CatalogItem for testing.
	 *
	 * @return CatalogItem
	 */
	private function createItem(): CatalogItem {
		return new CatalogItem(
			200,
			'product',
			0,
			'Integration Product',
			'INT-001',
			'29.99',
			'34.99',
			'',
			'Full description',
			'Short desc',
			null,
			array(),
			array(),
			array(),
			array(),
			'instock',
			10,
			'https://example.com/integration-product',
			null,
			null,
			array()
		);
	}

	/**
	 * Test renderCatalog with default template.
	 *
	 * @return void
	 */
	public function test_renderCatalog_with_default_template(): void {
		$loader    = new FileTemplateLoader( $this->pluginDir );
		$renderer  = new FileTemplateRenderer( $loader );
		$builder   = new TemplateContextBuilder();
		$engine    = new TemplateEngine( $loader, $renderer, $builder );

		$catalog = $this->createCatalog();
		$items   = array( $this->createItem() );

		$output = $engine->renderCatalog( $catalog, $items );

		$this->assertStringContainsString( 'Integration Test Catalog', $output );
		$this->assertStringContainsString( 'Integration Product', $output );
		$this->assertStringContainsString( 'catalogist-catalog', $output );
	}

	/**
	 * Test renderCatalog with override settings.
	 *
	 * @return void
	 */
	public function test_renderCatalog_with_override_settings(): void {
		$loader    = new FileTemplateLoader( $this->pluginDir );
		$renderer  = new FileTemplateRenderer( $loader );
		$builder   = new TemplateContextBuilder();
		$engine    = new TemplateEngine( $loader, $renderer, $builder );

		$catalog = $this->createCatalog();
		$items   = array( $this->createItem() );

		$settings = array(
			'layout' => array(
				'columns' => 3,
			),
		);

		$output = $engine->renderCatalog( $catalog, $items, $settings );

		$this->assertStringContainsString( 'catalogist-columns-3', $output );
	}

	/**
	 * Test renderCatalog with empty items.
	 *
	 * @return void
	 */
	public function test_renderCatalog_with_empty_items(): void {
		$loader    = new FileTemplateLoader( $this->pluginDir );
		$renderer  = new FileTemplateRenderer( $loader );
		$builder   = new TemplateContextBuilder();
		$engine    = new TemplateEngine( $loader, $renderer, $builder );

		$catalog = $this->createCatalog();
		$items   = array();

		$output = $engine->renderCatalog( $catalog, $items );

		$this->assertStringContainsString( 'Integration Test Catalog', $output );
		$this->assertStringContainsString( 'No products found', $output );
	}

	/**
	 * Test renderItem renders single product card.
	 *
	 * @return void
	 */
	public function test_renderItem_renders_single_card(): void {
		$loader    = new FileTemplateLoader( $this->pluginDir );
		$renderer  = new FileTemplateRenderer( $loader );
		$builder   = new TemplateContextBuilder();
		$engine    = new TemplateEngine( $loader, $renderer, $builder );

		$catalog = $this->createCatalog();
		$item    = $this->createItem();

		$output = $engine->renderItem( $catalog, $item );

		$this->assertStringContainsString( 'Integration Product', $output );
		$this->assertStringContainsString( 'INT-001', $output );
		$this->assertStringContainsString( 'catalogist-product-card', $output );
	}

	/**
	 * Test getLoader returns the loader instance.
	 *
	 * @return void
	 */
	public function test_getLoader_returns_loader(): void {
		$loader    = new FileTemplateLoader( $this->pluginDir );
		$renderer  = new FileTemplateRenderer( $loader );
		$builder   = new TemplateContextBuilder();
		$engine    = new TemplateEngine( $loader, $renderer, $builder );

		$this->assertSame( $loader, $engine->getLoader() );
	}

	/**
	 * Test getRenderer returns the renderer instance.
	 *
	 * @return void
	 */
	public function test_getRenderer_returns_renderer(): void {
		$loader    = new FileTemplateLoader( $this->pluginDir );
		$renderer  = new FileTemplateRenderer( $loader );
		$builder   = new TemplateContextBuilder();
		$engine    = new TemplateEngine( $loader, $renderer, $builder );

		$this->assertSame( $renderer, $engine->getRenderer() );
	}

	/**
	 * Test getContextBuilder returns the builder instance.
	 *
	 * @return void
	 */
	public function test_getContextBuilder_returns_builder(): void {
		$loader    = new FileTemplateLoader( $this->pluginDir );
		$renderer  = new FileTemplateRenderer( $loader );
		$builder   = new TemplateContextBuilder();
		$engine    = new TemplateEngine( $loader, $renderer, $builder );

		$this->assertSame( $builder, $engine->getContextBuilder() );
	}
}
