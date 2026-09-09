<?php

declare(strict_types=1);

namespace Catalogist;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CatalogItemMapper.
 *
 * Note: These are integration-style unit tests because mapping
 * depends heavily on WooCommerce and WordPress objects.
 * They should be run within the WordPress/WooCommerce environment.
 */
final class CatalogItemMapperTest extends TestCase {

	/**
	 * @var int
	 */
	private int $product_id;

	/**
	 * @var int
	 */
	private int $variation_id;

	/**
	 * @var int
	 */
	private int $catalog_id;

	/**
	 * @var CatalogContext
	 */
	private CatalogContext $context;

	protected function setUp(): void {
		parent::setUp();

		// Mock context.
		$this->catalog_id = 100;
		$this->context    = new CatalogContext( $this->catalog_id, 'en_US', 'USD' );

		// Ensure WC is loaded and create mock data.
		// In the integration environment, we actually create products.
		$product          = WC_Helper_Product::create_simple_product();
		$this->product_id = $product->get_id();

		$variation          = WC_Helper_Product::create_variation( $this->product_id );
		$this->variation_id = $variation->get_id();
	}

	public function testMapSimpleProduct(): void {
		$product = wc_get_product( $this->product_id );
		$this->assertInstanceOf( \WC_Product::class, $product );

		$catalog_item = CatalogItemMapper::map_product( $product, $this->context );

		$this->assertInstanceOf( CatalogItem::class, $catalog_item );
		$this->assertSame( $this->product_id, $catalog_item->id );
		$this->assertSame( 'simple', $catalog_item->type );
		$this->assertSame( $product->get_name(), $catalog_item->title );
		$this->assertSame( $product->get_sku(), $catalog_item->sku );
		$this->assertSame( $product->get_price(), $catalog_item->price );
		$this->assertSame( $this->context, $catalog_item->context );
	}

	public function testMapVariation(): void {
		$variation = wc_get_product( $this->variation_id );
		$this->assertInstanceOf( \WC_Product_Variation::class, $variation );

		$catalog_item = CatalogItemMapper::map_product( $variation, $this->context );

		$this->assertInstanceOf( CatalogItem::class, $catalog_item );
		$this->assertSame( $this->variation_id, $catalog_item->id );
		$this->assertSame( 'variation', $catalog_item->type );
		$this->assertNotNull( $catalog_item->parent_id );
		// Ensure it uses the variation's own name.
		$this->assertSame( $variation->get_name(), $catalog_item->title );
	}
}
