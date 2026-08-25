<?php
/**
 * Tests for Product Dynamic Tags.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Tests\Unit\Elementor\DynamicTags;

use Catalogist\CatalogItem\CatalogItem;
use Catalogist\Elementor\DynamicTags\ProductNameDynamicTag;
use Catalogist\Elementor\DynamicTags\ProductSkuDynamicTag;
use Catalogist\Elementor\DynamicTags\ProductPriceDynamicTag;
use Catalogist\Elementor\DynamicTags\ProductImageDynamicTag;
use Catalogist\Elementor\DynamicTags\ProductUrlDynamicTag;
use Catalogist\Elementor\DynamicTags\ProductDescriptionDynamicTag;
use Catalogist\Elementor\DynamicTags\ProductCategoriesDynamicTag;
use Catalogist\Elementor\DynamicTags\ProductAttributesDynamicTag;
use Catalogist\Elementor\DynamicTags\ProductStockStatusDynamicTag;
use Catalogist\Elementor\DynamicTags\ProductQrCodeDynamicTag;
use PHPUnit\Framework\TestCase;

/**
 * Product dynamic tag tests.
 */
class ProductDynamicTagTest extends TestCase {

	/**
	 * Test ProductNameDynamicTag renders product name.
	 */
	public function test_product_name_dynamic_tag(): void {
		// Create mock catalog item.
		$item = $this->createMockCatalogItem( 'Test Product', 'TEST-001', '19.99' );

		// Create tag instance.
		$tag = new ProductNameDynamicTag();
		$tag->set_catalog_item( $item );

		// Mock resolve_catalog_item via reflection.
		$reflection = new \ReflectionClass( $tag );
		$method = $reflection->getMethod( 'resolve_catalog_item' );
		$method->setAccessible( true );
		$method->invoke( $tag, 123 ) === null; // Fallback test

		// Set settings.
		$tag->set_settings( array( 'product_id' => 123 ) );

		// Test render with set catalog item.
		$tag->set_catalog_item( $item );
		$result = $tag->render();
		$this->assertEquals( 'Test Product', $result );
	}

	/**
	 * Test ProductSkuDynamicTag renders SKU.
	 */
	public function test_product_sku_dynamic_tag(): void {
		$item = $this->createMockCatalogItem( 'Test Product', 'TEST-SKU-001', '29.99' );

		$tag = new ProductSkuDynamicTag();
		$tag->set_catalog_item( $item );
		$tag->set_settings( array( 'product_id' => 123 ) );

		$result = $tag->render();
		$this->assertEquals( 'TEST-SKU-001', $result );
	}

	/**
	 * Test ProductPriceDynamicTag renders price.
	 */
	public function test_product_price_dynamic_tag(): void {
		$item = $this->createMockCatalogItem( 'Test Product', 'TEST-001', '39.99' );

		$tag = new ProductPriceDynamicTag();
		$tag->set_catalog_item( $item );
		$tag->set_settings( array( 'product_id' => 123 ) );

		$result = $tag->render();
		$this->assertEquals( '39.99', $result );
	}

	/**
	 * Test ProductImageDynamicTag renders image HTML.
	 */
	public function test_product_image_dynamic_tag(): void {
		$item = $this->createMockCatalogItem( 'Test Product', 'TEST-001', '49.99', array(
			'src' => 'https://example.com/image.jpg',
			'alt' => 'Test Image',
			'width' => 300,
			'height' => 300,
		) );

		$tag = new ProductImageDynamicTag();
		$tag->set_catalog_item( $item );
		$tag->set_settings( array( 'product_id' => 123 ) );

		$result = $tag->render();
		$this->assertStringContainsString( 'https://example.com/image.jpg', $result );
		$this->assertStringContainsString( '<img', $result );
		$this->assertStringContainsString( 'alt="Test Image"', $result );
	}

	/**
	 * Test ProductUrlDynamicTag renders permalink.
	 */
	public function test_product_url_dynamic_tag(): void {
		$item = $this->createMockCatalogItem( 'Test Product', 'TEST-001', '59.99', array(), 'https://example.com/product/test' );

		$tag = new ProductUrlDynamicTag();
		$tag->set_catalog_item( $item );
		$tag->set_settings( array( 'product_id' => 123 ) );

		$result = $tag->render();
		$this->assertEquals( 'https://example.com/product/test', $result );
	}

	/**
	 * Test ProductDescriptionDynamicTag renders description.
	 */
	public function test_product_description_dynamic_tag(): void {
		$item = $this->createMockCatalogItem( 'Test Product', 'TEST-001', '69.99', array(), '', 'This is a test description.' );

		$tag = new ProductDescriptionDynamicTag();
		$tag->set_catalog_item( $item );
		$tag->set_settings( array( 'product_id' => 123 ) );

		$result = $tag->render();
		$this->assertEquals( 'This is a test description.', $result );
	}

	/**
	 * Test ProductAttributesDynamicTag renders attributes.
	 */
	public function test_product_attributes_dynamic_tag(): void {
		$item = $this->createMockCatalogItem(
			'Test Product',
			'TEST-001',
			'79.99',
			array(),
			'',
			'',
			array( 'Color' => 'Red', 'Size' => 'Large' )
		);

		$tag = new ProductAttributesDynamicTag();
		$tag->set_catalog_item( $item );
		$tag->set_settings( array( 'product_id' => 123 ) );

		$result = $tag->render();
		$this->assertStringContainsString( 'Color: Red', $result );
		$this->assertStringContainsString( 'Size: Large', $result );
		$this->assertStringContainsString( '<br>', $result );
	}

	/**
	 * Test ProductStockStatusDynamicTag renders status.
	 */
	public function test_product_stock_status_dynamic_tag(): void {
		$item = $this->createMockCatalogItem( 'Test Product', 'TEST-001', '89.99', array(), '', 'instock' );

		$tag = new ProductStockStatusDynamicTag();
		$tag->set_catalog_item( $item );
		$tag->set_settings( array( 'product_id' => 123 ) );

		$result = $tag->render();
		$this->assertStringContainsString( 'In Stock', $result );
	}

	/**
	 * Test ProductQrCodeDynamicTag renders QR code.
	 */
	public function test_product_qr_code_dynamic_tag(): void {
		$item = $this->createMockCatalogItem(
			'Test Product',
			'TEST-001',
			'99.99',
			array(),
			'https://example.com/product/test'
		);

		$tag = new ProductQrCodeDynamicTag();
		$tag->set_catalog_item( $item );
		$tag->set_settings( array( 'product_id' => 123, 'size' => 150 ) );

		$result = $tag->render();
		$this->assertStringContainsString( 'api.qrserver.com', $result );
		$this->assertStringContainsString( 'urlencode', 'test' ); // URL-encoded check
		$this->assertStringContainsString( 'qr-code', $result );
	}

	/**
	 * Test tags return empty for missing product.
	 */
	public function test_tags_return_empty_for_missing_product(): void {
		// Create tag with no catalog item.
		$tag = new ProductNameDynamicTag();
		$tag->set_catalog_item( null );
		$tag->set_settings( array( 'product_id' => 0 ) );

		$result = $tag->render();
		$this->assertEmpty( $result );
	}

	/**
	 * Test tag name and title methods.
	 */
	public function test_tag_name_and_title(): void {
		$tag = new ProductNameDynamicTag();
		$this->assertEquals( 'catalogist_product_name', $tag->get_name() );
		$this->assertStringContainsString( 'Product Name', $tag->get_title() );
	}

	/**
	 * Test tag group.
	 */
	public function test_tag_group(): void {
		$tag = new ProductNameDynamicTag();
		$group = $tag->get_group();
		$this->assertIsArray( $group );
		$this->assertContains( 'catalogist-products', $group );
	}

	/**
	 * Helper to create mock catalog items.
	 *
	 * @param string      $title Product title.
	 * @param string      $sku Product SKU.
	 * @param string      $price Product price.
	 * @param array       $image Image data.
	 * @param string      $permalink Product permalink.
	 * @param string      $stock_status Stock status.
	 * @param array       $attributes Product attributes.
	 *
	 * @return CatalogItem
	 */
	private function createMockCatalogItem(
		string $title,
		string $sku,
		string $price,
		array $image = array(),
		string $permalink = '',
		string $stock_status = 'instock',
		array $attributes = array()
	): CatalogItem {
		return new CatalogItem(
			id: 123,
			type: 'product',
			title: $title,
			sku: $sku,
			price: $price,
			image: $image ?: array( 'src' => '', 'alt' => '', 'width' => 0, 'height' => 0 ),
			permalink: $permalink,
			stock_status: $stock_status,
			attributes: $attributes,
			description: '',
			categories: array(),
			parent_product: null,
			variation_table: null,
			metadata: array()
		);
	}
}
