<?php
/**
 * Unit tests for CatalogItem value object.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Tests\Unit\CatalogItem;

use Catalogist\CatalogItem\CatalogItem;
use PHPUnit\Framework\TestCase;

/**
 * Tests for CatalogItem value object.
 */
class CatalogItemTest extends TestCase {

	/**
	 * Test product CatalogItem construction.
	 *
	 * @return void
	 */
	public function test_product_construction(): void {
		$item = new CatalogItem(
			125,
			'product',
			0,
			'Test Product',
			'TEST-001',
			'19.99',
			'24.99',
			'19.99',
			'Full description',
			'Short desc',
			null,
			array(),
			array( 10, 20 ),
			array( 30, 40 ),
			array(),
			'instock',
			50,
			'https://example.com/product/test',
			null,
			null,
			array()
		);

		$this->assertSame( 125, $item->get_id() );
		$this->assertTrue( $item->is_product() );
		$this->assertFalse( $item->is_variation() );
		$this->assertSame( 0, $item->get_parent_product_id() );
		$this->assertSame( 'Test Product', $item->get_title() );
		$this->assertSame( 'TEST-001', $item->get_sku() );
		$this->assertSame( '19.99', $item->get_price() );
		$this->assertSame( '24.99', $item->get_regular_price() );
		$this->assertSame( '19.99', $item->get_sale_price() );
		$this->assertSame( 'Full description', $item->get_description() );
		$this->assertSame( 'Short desc', $item->get_short_description() );
		$this->assertNull( $item->get_image() );
		$this->assertSame( array( 10, 20 ), $item->get_gallery() );
		$this->assertSame( array( 10, 20 ), $item->get_categories() );
		$this->assertSame( array( 30, 40 ), $item->get_tags() );
		$this->assertSame( array(), $item->get_attributes() );
		$this->assertSame( 'instock', $item->get_stock_status() );
		$this->assertSame( 50, $item->get_stock_quantity() );
		$this->assertSame( 'https://example.com/product/test', $item->get_permalink() );
		$this->assertNull( $item->get_parent_product() );
		$this->assertNull( $item->get_variation_table() );
		$this->assertFalse( $item->has_variation_table() );
		$this->assertFalse( $item->is_variable_product() );
	}

	/**
	 * Test variation CatalogItem construction.
	 *
	 * @return void
	 */
	public function test_variation_construction(): void {
		$item = new CatalogItem(
			241,
			'variation',
			125,
			'Red / Large',
			'RED-L',
			'21.99',
			'24.99',
			'21.99',
			'',
			'',
			null,
			array(),
			array(),
			array(),
			array( 'Color' => 'Red', 'Size' => 'Large' ),
			'instock',
			10,
			'https://example.com/product/test/variation/241',
			array(
				'id'        => 125,
				'name'      => 'Test Product',
				'sku'       => 'TEST-001',
				'permalink' => 'https://example.com/product/test',
			),
			null,
			array()
		);

		$this->assertSame( 241, $item->get_id() );
		$this->assertFalse( $item->is_product() );
		$this->assertTrue( $item->is_variation() );
		$this->assertSame( 125, $item->get_parent_product_id() );
		$this->assertSame( array( 'Color' => 'Red', 'Size' => 'Large' ), $item->get_attributes() );
		$this->assertSame( array(
			'id'        => 125,
			'name'      => 'Test Product',
			'sku'       => 'TEST-001',
			'permalink' => 'https://example.com/product/test',
		), $item->get_parent_product() );
	}

	/**
	 * Test table mode CatalogItem.
	 *
	 * @return void
	 */
	public function test_table_mode(): void {
		$table = array(
			'variations' => array(
				241 => array(
					'id'           => 241,
					'title'        => 'Red / Large',
					'attributes'   => array( 'Color' => 'Red', 'Size' => 'Large' ),
					'price'        => '21.99',
					'sale_price'   => '',
					'sku'          => 'RED-L',
					'stock_status' => 'instock',
					'permalink'    => 'https://example.com/v/241',
					'image'        => null,
				),
				242 => array(
					'id'           => 242,
					'title'        => 'Blue / Large',
					'attributes'   => array( 'Color' => 'Blue', 'Size' => 'Large' ),
					'price'        => '22.99',
					'sale_price'   => '19.99',
					'sku'          => 'BLU-L',
					'stock_status' => 'outofstock',
					'permalink'    => 'https://example.com/v/242',
					'image'        => null,
				),
			),
			'parent_id' => 125,
		);

		$item = new CatalogItem(
			125,
			'product',
			0,
			'Test Product',
			'TEST-001',
			'19.99',
			'24.99',
			'19.99',
			'Desc',
			'Short',
			null,
			array(),
			array(),
			array(),
			array(),
			'instock',
			100,
			'https://example.com/product/test',
			null,
			$table,
			array( 'has_variations' => true )
		);

		$this->assertFalse( $item->is_variation() );
		$this->assertTrue( $item->has_variation_table() );
		$this->assertSame( $table, $item->get_variation_table() );
		$this->assertTrue( $item->is_variable_product() );
	}

	/**
	 * Test to_array serialization.
	 *
	 * @return void
	 */
	public function test_to_array(): void {
		$item = new CatalogItem(
			125,
			'product',
			0,
			'Test',
			'TEST',
			'10.00',
			'12.00',
			'',
			'Desc',
			'Short',
			null,
			array(),
			array( 1 ),
			array( 2 ),
			array(),
			'instock',
			5,
			'https://example.com/test',
			null,
			null,
			array()
		);

		$array = $item->to_array();

		$this->assertIsArray( $array );
		$this->assertSame( 125, $array['id'] );
		$this->assertSame( 'product', $array['type'] );
		$this->assertSame( 0, $array['parent_product_id'] );
		$this->assertSame( 'Test', $array['title'] );
		$this->assertSame( 'TEST', $array['sku'] );
		$this->assertSame( '10.00', $array['price'] );
		$this->assertSame( '12.00', $array['regular_price'] );
		$this->assertSame( '', $array['sale_price'] );
		$this->assertSame( 'Desc', $array['description'] );
		$this->assertSame( 'Short', $array['short_description'] );
		$this->assertNull( $array['image'] );
		$this->assertSame( array( 1 ), $array['categories'] );
		$this->assertSame( array( 2 ), $array['tags'] );
		$this->assertSame( array(), $array['attributes'] );
		$this->assertSame( 'instock', $array['stock_status'] );
		$this->assertSame( 5, $array['stock_quantity'] );
		$this->assertSame( 'https://example.com/test', $array['permalink'] );
		$this->assertNull( $array['parent_product'] );
		$this->assertNull( $array['variation_table'] );
		$this->assertSame( array(), $array['metadata'] );
	}

	/**
	 * Test with image data.
	 *
	 * @return void
	 */
	public function test_with_image(): void {
		$image = array(
			'id'     => 500,
			'src'    => 'https://example.com/image.jpg',
			'width'  => 80,
			'height' => 80,
		);

		$item = new CatalogItem(
			125, 'product', 0, 'Test', '', '', '', '', '', '',
			$image, array(), array(), array(), array(),
			'instock', null, '', null, null, array()
		);

		$this->assertSame( $image, $item->get_image() );
	}

	/**
	 * Test null stock quantity.
	 *
	 * @return void
	 */
	public function test_null_stock_quantity(): void {
		$item = new CatalogItem(
			125, 'product', 0, 'Test', '', '10.00', '12.00', '', '', '',
			null, array(), array(), array(), array(),
			'instock', null, '', null, null, array()
		);

		$this->assertNull( $item->get_stock_quantity() );
	}

	/**
	 * Test variation without parent context.
	 *
	 * @return void
	 */
	public function test_variation_without_parent(): void {
		$item = new CatalogItem(
			241, 'variation', 125, 'Red / Large', 'RED-L',
			'21.99', '24.99', '21.99', '', '',
			null, array(), array(), array(),
			array( 'Color' => 'Red', 'Size' => 'Large' ),
			'instock', 10, '', null, null, array()
		);

		$this->assertNull( $item->get_parent_product() );
		$this->assertSame( 125, $item->get_parent_product_id() );
	}
}
