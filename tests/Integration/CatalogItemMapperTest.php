<?php

declare(strict_types=1);

namespace Catalogist;

use PHPUnit\Framework\TestCase;

/**
 * Integration tests for CatalogItemMapper.
 *
 * Runs inside the WordPress/WooCommerce environment.
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

		$this->catalog_id = 100;
		$this->context    = new CatalogContext( $this->catalog_id, 'en_US', 'USD' );

		// Create a simple product using WooCommerce's public API.
		$product = new \WC_Product_Simple();
		$product->set_name( 'Test Product' );
		$product->set_sku( 'TEST-SIMPLE-' . uniqid() );
		$product->set_regular_price( '10.00' );
		$product->set_price( '10.00' );
		$product->set_status( 'publish' );
		$this->product_id = $product->save();

		// Create a variation using WooCommerce's public API.
		$variation = new \WC_Product_Variation();
		$variation->set_parent_id( $this->product_id );
		$variation->set_regular_price( '12.00' );
		$variation->set_price( '12.00' );
		$variation->set_status( 'publish' );
		$this->variation_id = $variation->save();
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
		$this->assertSame( (float) $product->get_price(), $catalog_item->price );
		$this->assertSame( $this->context, $catalog_item->context );
	}

	public function testMapVariation(): void {
		$variation = wc_get_product( $this->variation_id );

		$this->assertInstanceOf( \WC_Product_Variation::class, $variation );

		$catalog_item = CatalogItemMapper::map_product( $variation, $this->context );

		$this->assertInstanceOf( CatalogItem::class, $catalog_item );
		$this->assertSame( $this->variation_id, $catalog_item->id );
		$this->assertSame( 'variation', $catalog_item->type );
		$this->assertSame( $this->product_id, $catalog_item->parent_id );
		$this->assertSame( $variation->get_name(), $catalog_item->title );
	}

	protected function tearDown(): void {
		if ( $this->variation_id > 0 ) {
			wp_delete_post( $this->variation_id, true );
		}

		if ( $this->product_id > 0 ) {
			wp_delete_post( $this->product_id, true );
		}

		parent::tearDown();
	}
}
