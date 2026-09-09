<?php

declare(strict_types=1);

namespace Catalogist;

use PHPUnit\Framework\TestCase;

/**
 * Pure Unit tests for CatalogItem and CatalogContext.
 * These tests do not require WordPress or WooCommerce.
 */
final class CatalogItemTest extends TestCase {

	public function testCatalogContextProperties(): void {
		$context = new CatalogContext( 1, 'en_US', 'USD' );
		$this->assertSame( 1, $context->catalog_id );
		$this->assertSame( 'en_US', $context->language );
		$this->assertSame( 'USD', $context->currency );
	}

	public function testCatalogItemProperties(): void {
		$context = new CatalogContext( 1, 'en_US', 'USD' );
		$item    = new CatalogItem(
			123,
			'simple',
			null,
			'Test Title',
			'test-slug',
			'SKU123',
			10.0,
			5.0,
			5.0,
			'instock',
			'http://image.url',
			'http://permalink.url',
			array( 'cat1', 'cat2' ),
			array( 'tag1' ),
			$context
		);

		$this->assertSame( 123, $item->id );
		$this->assertSame( 'simple', $item->type );
		$this->assertNull( $item->parent_id );
		$this->assertSame( 'Test Title', $item->title );
		$this->assertSame( 'test-slug', $item->slug );
		$this->assertSame( 'SKU123', $item->sku );
		$this->assertSame( 10.0, $item->regular_price );
		$this->assertSame( 5.0, $item->sale_price );
		$this->assertSame( 5.0, $item->price );
		$this->assertSame( 'instock', $item->stock_status );
		$this->assertSame( 'http://image.url', $item->image_url );
		$this->assertSame( 'http://permalink.url', $item->permalink );
		$this->assertSame( array( 'cat1', 'cat2' ), $item->categories );
		$this->assertSame( array( 'tag1' ), $item->tags );
		$this->assertSame( $context, $item->context );
	}

	public function testTypeHelpers(): void {
		$context = new CatalogContext( 1, 'en_US', 'USD' );

		$simple    = new CatalogItem( 1, 'simple', null, 'T', 's', null, null, null, null, null, null, null, array(), array(), $context );
		$variable  = new CatalogItem( 2, 'variable', null, 'T', 's', null, null, null, null, null, null, null, array(), array(), $context );
		$variation = new CatalogItem( 3, 'variation', 2, 'T', 's', null, null, null, null, null, null, null, array(), array(), $context );

		$this->assertTrue( $simple->is_simple() );
		$this->assertFalse( $simple->is_variation() );
		$this->assertFalse( $simple->is_variable() );

		$this->assertFalse( $variable->is_simple() );
		$this->assertFalse( $variable->is_variation() );
		$this->assertTrue( $variable->is_variable() );

		$this->assertFalse( $variation->is_simple() );
		$this->assertTrue( $variation->is_variation() );
		$this->assertFalse( $variation->is_variable() );
	}
}
