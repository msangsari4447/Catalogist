<?php
/**
 * Unit tests for TemplateContextBuilder.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Tests\Unit\Template;

use Catalogist\Catalog\Catalog;
use Catalogist\CatalogItem\CatalogItem;
use Catalogist\Template\TemplateContextBuilder;
use PHPUnit\Framework\TestCase;

/**
 * Tests for TemplateContextBuilder.
 */
class TemplateContextBuilderTest extends TestCase {

	/**
	 * Create a mock Catalog for testing.
	 *
	 * @param array<string, mixed> $overrides Array of property overrides.
	 *
	 * @return Catalog
	 */
	private function createMockCatalog( array $overrides = array() ): Catalog {
		$catalog = new Catalog();
		$catalog->set_id( 100 );
		$catalog->set_title( 'Test Catalog' );
		$catalog->set_slug( 'test-catalog' );
		$catalog->set_template_id( isset( $overrides['template_id'] ) ? $overrides['template_id'] : 0 );
		$catalog->set_layout_settings( isset( $overrides['layout_settings'] ) ? $overrides['layout_settings'] : array() );
		$catalog->set_print_settings( isset( $overrides['print_settings'] ) ? $overrides['print_settings'] : array() );

		return $catalog;
	}

	/**
	 * Create a mock CatalogItem for testing.
	 *
	 * @param array<string, mixed> $overrides Array of property overrides.
	 *
	 * @return CatalogItem
	 */
	private function createMockItem( array $overrides = array() ): CatalogItem {
		return new CatalogItem(
			isset( $overrides['id'] ) ? $overrides['id'] : 200,
			isset( $overrides['type'] ) ? $overrides['type'] : 'product',
			isset( $overrides['parent_product_id'] ) ? $overrides['parent_product_id'] : 0,
			isset( $overrides['title'] ) ? $overrides['title'] : 'Test Product',
			isset( $overrides['sku'] ) ? $overrides['sku'] : 'TEST-001',
			isset( $overrides['price'] ) ? $overrides['price'] : '19.99',
			isset( $overrides['regular_price'] ) ? $overrides['regular_price'] : '24.99',
			isset( $overrides['sale_price'] ) ? $overrides['sale_price'] : '',
			isset( $overrides['description'] ) ? $overrides['description'] : 'Full description',
			isset( $overrides['short_description'] ) ? $overrides['short_description'] : 'Short desc',
			isset( $overrides['image'] ) ? $overrides['image'] : null,
			isset( $overrides['gallery'] ) ? $overrides['gallery'] : array(),
			isset( $overrides['categories'] ) ? $overrides['categories'] : array(),
			isset( $overrides['tags'] ) ? $overrides['tags'] : array(),
			isset( $overrides['attributes'] ) ? $overrides['attributes'] : array(),
			isset( $overrides['stock_status'] ) ? $overrides['stock_status'] : 'instock',
			isset( $overrides['stock_quantity'] ) ? $overrides['stock_quantity'] : 50,
			isset( $overrides['permalink'] ) ? $overrides['permalink'] : 'https://example.com/test',
			isset( $overrides['parent_product'] ) ? $overrides['parent_product'] : null,
			isset( $overrides['variation_table'] ) ? $overrides['variation_table'] : null,
			isset( $overrides['metadata'] ) ? $overrides['metadata'] : array()
		);
	}

	/**
	 * Test build returns required keys.
	 *
	 * @return void
	 */
	public function test_build_returns_required_keys(): void {
		$builder = new TemplateContextBuilder();
		$catalog = $this->createMockCatalog();
		$items   = array( $this->createMockItem() );

		$context = $builder->build( $catalog, $items );

		$this->assertArrayHasKey( 'catalog', $context );
		$this->assertArrayHasKey( 'items', $context );
		$this->assertArrayHasKey( 'template_id', $context );
		$this->assertArrayHasKey( 'template_name', $context );
		$this->assertArrayHasKey( 'layout', $context );
		$this->assertArrayHasKey( 'columns', $context );
		$this->assertArrayHasKey( 'page_size', $context );
		$this->assertArrayHasKey( 'orientation', $context );
		$this->assertArrayHasKey( 'print', $context );
		$this->assertArrayHasKey( 'margins', $context );
		$this->assertArrayHasKey( 'show_header', $context );
		$this->assertArrayHasKey( 'show_footer', $context );
	}

	/**
	 * Test build uses catalog template_id when settings not provided.
	 *
	 * @return void
	 */
	public function test_build_uses_catalog_template_id(): void {
		$builder = new TemplateContextBuilder();
		$catalog = $this->createMockCatalog( array( 'template_id' => 123 ) );
		$items   = array( $this->createMockItem() );

		// Mock get_post to return a fake template post.
		$templatePost = new stdClass();
		$templatePost->post_name  = 'my-template';
		$templatePost->post_title = 'My Template';

		$reflection = new \ReflectionClass( $builder );
		$method     = $reflection->getMethod( 'resolveTemplateName' );
		$method->setAccessible( true );

		// Since resolveTemplateName calls get_post, we need to mock it.
		// In our bootstrap, get_post returns null by default.
		// We'll test the fallback by asserting template_name is empty when get_post returns null.
		$context = $builder->build( $catalog, $items );

		$this->assertSame( 123, $context['template_id'] );
		$this->assertSame( '', $context['template_name'] );
	}

	/**
	 * Test build normalizes layout settings with defaults.
	 *
	 * @return void
	 */
	public function test_build_normalizes_layout_settings(): void {
		$builder = new TemplateContextBuilder();
		$catalog = $this->createMockCatalog();
		$items   = array( $this->createMockItem() );

		$context = $builder->build( $catalog, $items );

		$this->assertSame( 2, $context['columns'] ); // DEFAULT_COLUMNS = 2
		$this->assertSame( 'A4', $context['page_size'] );
		$this->assertSame( 'portrait', $context['orientation'] );
		$this->assertTrue( $context['show_header'] );
		$this->assertFalse( $context['show_footer'] );
	}

	/**
	 * Test build applies override layout settings.
	 *
	 * @return void
	 */
	public function test_build_applies_override_layout_settings(): void {
		$builder = new TemplateContextBuilder();
		$catalog = $this->createMockCatalog();
		$items   = array( $this->createMockItem() );

		$override = array(
			'columns'     => 3,
			'show_header' => false,
			'logo_url'    => 'https://example.com/logo.png',
		);

		$context = $builder->build( $catalog, $items, $override );

		$this->assertSame( 3, $context['columns'] );
		$this->assertFalse( $context['show_header'] );
		$this->assertSame( 'https://example.com/logo.png', $context['layout']['logo_url'] );
	}

	/**
	 * Test build clamps columns to valid range.
	 *
	 * @return void
	 */
	public function test_build_clamps_columns(): void {
		$builder = new TemplateContextBuilder();
		$catalog = $this->createMockCatalog();
		$items   = array( $this->createMockItem() );

		$context = $builder->build( $catalog, $items, array( 'columns' => 0 ) );
		$this->assertSame( 1, $context['columns'] );

		$context = $builder->build( $catalog, $items, array( 'columns' => 10 ) );
		$this->assertSame( 4, $context['columns'] );
	}

	/**
	 * Test build applies catalog layout settings as base.
	 *
	 * @return void
	 */
	public function test_build_applies_catalog_layout_settings(): void {
		$builder = new TemplateContextBuilder();
		$catalog = $this->createMockCatalog(
			array(
				'layout_settings' => array(
					'columns'     => 1,
					'show_footer' => true,
					'footer_content' => 'Custom footer',
				),
			)
		);
		$items = array( $this->createMockItem() );

		$context = $builder->build( $catalog, $items );

		$this->assertSame( 1, $context['columns'] );
		$this->assertTrue( $context['show_footer'] );
		$this->assertSame( 'Custom footer', $context['layout']['footer_content'] );
	}

	/**
	 * Test build merges catalog and override settings.
	 *
	 * @return void
	 */
	public function test_build_merges_catalog_and_override_settings(): void {
		$builder = new TemplateContextBuilder();
		$catalog = $this->createMockCatalog(
			array(
				'layout_settings' => array(
					'columns'     => 1,
					'show_footer' => true,
					'footer_content' => 'Footer from catalog',
				),
			)
		);
		$items   = array( $this->createMockItem() );
		$override = array(
			'columns' => 3,
		);

		$context = $builder->build( $catalog, $items, $override );

		$this->assertSame( 3, $context['columns'] );
		$this->assertTrue( $context['show_footer'] );
		$this->assertSame( 'Footer from catalog', $context['layout']['footer_content'] );
	}

	/**
	 * Test build normalizes print settings with defaults.
	 *
	 * @return void
	 */
	public function test_build_normalizes_print_settings(): void {
		$builder = new TemplateContextBuilder();
		$catalog = $this->createMockCatalog();
		$items   = array( $this->createMockItem() );

		$context = $builder->build( $catalog, $items );

		$this->assertArrayHasKey( 'margins', $context );
		$this->assertSame( array( 'top' => 20, 'right' => 20, 'bottom' => 20, 'left' => 20 ), $context['margins'] );
	}

	/**
	 * Test build applies override print settings.
	 *
	 * @return void
	 */
	public function test_build_applies_override_print_settings(): void {
		$builder = new TemplateContextBuilder();
		$catalog = $this->createMockCatalog();
		$items   = array( $this->createMockItem() );

		$override = array(
			'print' => array(
				'margins' => array(
					'top'    => 10,
					'right'  => 15,
					'bottom' => 20,
					'left'   => 25,
				),
			),
		);

		$context = $builder->build( $catalog, $items, null, $override['print'] );

		$this->assertSame( 10, $context['print']['margins']['top'] );
		$this->assertSame( 15, $context['print']['margins']['right'] );
		$this->assertSame( 20, $context['print']['margins']['bottom'] );
		$this->assertSame( 25, $context['print']['margins']['left'] );
	}

	/**
	 * Test buildLoopContext provides correct loop meta.
	 *
	 * @return void
	 */
	public function test_buildLoopContext_provides_loop_meta(): void {
		$builder = new TemplateContextBuilder();
		$catalog = $this->createMockCatalog();
		$item    = $this->createMockItem( array( 'id' => 201 ) );
		$index   = 1;
		$count   = 3;

		$context = $builder->buildLoopContext( $catalog, $item, $index, $count );

		$this->assertSame( $item, $context['item'] );
		$this->assertSame( 1, $context['item_index'] );
		$this->assertSame( 3, $context['item_count'] );
		$this->assertFalse( $context['is_first'] );
		$this->assertFalse( $context['is_last'] );
		$this->assertFalse( $context['is_even'] );
		$this->assertTrue( $context['is_odd'] );
		$this->assertArrayHasKey( 'escaped', $context );
	}

	/**
	 * Test buildLoopContext first and last flags.
	 *
	 * @return void
	 */
	public function test_buildLoopContext_first_and_last(): void {
		$builder = new TemplateContextBuilder();
		$catalog = $this->createMockCatalog();
		$item    = $this->createMockItem();

		$first = $builder->buildLoopContext( $catalog, $item, 0, 1 );
		$this->assertTrue( $first['is_first'] );
		$this->assertTrue( $first['is_last'] );

		$last = $builder->buildLoopContext( $catalog, $item, 2, 3 );
		$this->assertFalse( $last['is_first'] );
		$this->assertTrue( $last['is_last'] );
	}

	/**
	 * Test buildLoopContext escaped helpers.
	 *
	 * @return void
	 */
	public function test_buildLoopContext_escaped_helpers(): void {
		$builder = new TemplateContextBuilder();
		$catalog = $this->createMockCatalog();
		$item    = $this->createMockItem(
			array(
				'title'       => '<script>alert("xss")</script>Product',
				'sku'         => '<b>SKU</b>',
				'price'       => '10.00',
				'permalink'   => 'https://example.com/\"',
				'stock_status' => 'instock',
			)
		);

		$context = $builder->buildLoopContext( $catalog, $item, 0, 1 );
		$escaped = $context['escaped'];

		$this->assertStringContainsString( '&lt;script&gt;', $escaped['title'] );
		$this->assertStringContainsString( '&lt;b&gt;', $escaped['sku'] );
		$this->assertSame( '10.00', $escaped['price'] );
		$this->assertStringContainsString( 'https://example.com/', $escaped['permalink'] );
		$this->assertSame( 'instock', $escaped['stock_status'] );
	}
}
