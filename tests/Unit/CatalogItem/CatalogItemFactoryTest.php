<?php
/**
 * Unit tests for CatalogItemFactory.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Tests\Unit\CatalogItem;

use Catalogist\CatalogItem\CatalogItemFactory;
use Catalogist\Product\ProductRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests for CatalogItemFactory.
 */
class CatalogItemFactoryTest extends TestCase {

	/**
	 * Mock product repository.
	 *
	 * @var ProductRepositoryInterface&MockObject
	 */
	private $mock_repository;

	/**
	 * Set up test fixtures.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->mock_repository = $this->createMock( ProductRepositoryInterface::class );
	}

	/**
	 * Test from_product with product data array.
	 *
	 * @return void
	 */
	public function test_from_product_with_array(): void {
		$factory = new CatalogItemFactory( $this->mock_repository );

		$data = array(
			'id'              => 125,
			'name'            => 'Test Product',
			'sku'             => 'TEST-001',
			'price'           => '19.99',
			'regular_price'   => '24.99',
			'sale_price'      => '19.99',
			'description'     => 'Full description',
			'short_description' => 'Short desc',
			'image_id'        => 0,
			'gallery'         => array(),
			'categories'      => array( 10, 20 ),
			'tags'            => array( 30, 40 ),
			'stock_status'    => 'instock',
			'stock_quantity'  => 50,
			'permalink'       => 'https://example.com/test',
			'is_variable'     => false,
		);

		$item = $factory->from_product( $data );

		$this->assertSame( 125, $item->get_id() );
		$this->assertTrue( $item->is_product() );
		$this->assertFalse( $item->is_variation() );
		$this->assertSame( 'Test Product', $item->get_title() );
		$this->assertSame( 'TEST-001', $item->get_sku() );
		$this->assertSame( '19.99', $item->get_price() );
		$this->assertSame( '24.99', $item->get_regular_price() );
		$this->assertSame( '19.99', $item->get_sale_price() );
		$this->assertSame( 'Full description', $item->get_description() );
		$this->assertSame( 'Short desc', $item->get_short_description() );
		$this->assertSame( array( 10, 20 ), $item->get_categories() );
		$this->assertSame( array( 30, 40 ), $item->get_tags() );
		$this->assertSame( 'instock', $item->get_stock_status() );
		$this->assertSame( 50, $item->get_stock_quantity() );
		$this->assertFalse( $item->is_variable_product() );
	}

	/**
	 * Test from_product with missing optional fields.
	 *
	 * @return void
	 */
	public function test_from_product_with_missing_optional_fields(): void {
		$factory = new CatalogItemFactory( $this->mock_repository );

		$data = array(
			'id'            => 126,
			'name'          => 'Minimal Product',
			'sku'           => '',
			'price'         => '0.00',
			'stock_status'  => 'outofstock',
			'permalink'     => 'https://example.com/minimal',
		);

		$item = $factory->from_product( $data );

		$this->assertSame( 126, $item->get_id() );
		$this->assertSame( '', $item->get_sku() );
		$this->assertSame( '0.00', $item->get_price() );
		$this->assertSame( 'outofstock', $item->get_stock_status() );
		$this->assertNull( $item->get_image() );
		$this->assertSame( array(), $item->get_gallery() );
	}

	/**
	 * Test from_product_array with complete data.
	 *
	 * @return void
	 */
	public function test_from_product_array(): void {
		$factory = new CatalogItemFactory( $this->mock_repository );

		$data = array(
			'id'              => 127,
			'name'            => 'Array Product',
			'sku'             => 'ARRAY-001',
			'price'           => '15.00',
			'regular_price'   => '20.00',
			'sale_price'      => '15.00',
			'description'     => 'Desc from array',
			'short_description' => 'Short from array',
			'categories'      => array( 5 ),
			'tags'            => array( 15 ),
			'stock_status'    => 'instock',
			'stock_quantity'  => 100,
			'permalink'       => 'https://example.com/array',
		);

		$item = $factory->from_product_array( $data );

		$this->assertTrue( $item->is_product() );
		$this->assertSame( 'Array Product', $item->get_title() );
		$this->assertSame( 'ARRAY-001', $item->get_sku() );
	}

	/**
	 * Test from_variation with parent context.
	 *
	 * @return void
	 */
	public function test_from_variation_with_parent(): void {
		$factory = new CatalogItemFactory( $this->mock_repository );

		$variation = array(
			'id'             => 241,
			'parent_id'      => 125,
			'name'           => 'Red / Large',
			'sku'            => 'RED-L',
			'price'          => '21.99',
			'regular_price'  => '24.99',
			'sale_price'     => '',
			'stock_status'   => 'instock',
			'stock_quantity' => 10,
			'attributes'     => array( 'Color' => 'Red', 'Size' => 'Large' ),
			'image'          => null,
			'dimensions'     => array(),
		);

		$parent = array(
			'id'       => 125,
			'name'     => 'Test Product',
			'sku'      => 'TEST-001',
			'permalink' => 'https://example.com/test',
		);

		$item = $factory->from_variation( $variation, $parent );

		$this->assertSame( 241, $item->get_id() );
		$this->assertTrue( $item->is_variation() );
		$this->assertFalse( $item->is_product() );
		$this->assertSame( 125, $item->get_parent_product_id() );
		$this->assertSame( 'Red / Large', $item->get_title() );
		$this->assertSame( 'RED-L', $item->get_sku() );
		$this->assertSame( '21.99', $item->get_price() );
		$this->assertSame( array( 'Color' => 'Red', 'Size' => 'Large' ), $item->get_attributes() );
		$this->assertSame( 10, $item->get_stock_quantity() );

		$parent_ctx = $item->get_parent_product();
		$this->assertNotNull( $parent_ctx );
		$this->assertSame( 125, $parent_ctx['id'] );
		$this->assertSame( 'Test Product', $parent_ctx['name'] );
		$this->assertSame( 'TEST-001', $parent_ctx['sku'] );
	}

	/**
	 * Test from_variation without parent context.
	 *
	 * @return void
	 */
	public function test_from_variation_without_parent(): void {
		$factory = new CatalogItemFactory( $this->mock_repository );

		$variation = array(
			'id'           => 242,
			'parent_id'    => 125,
			'name'         => 'Blue / Small',
			'sku'          => 'BLU-S',
			'price'        => '18.99',
			'stock_status' => 'instock',
			'attributes'   => array( 'Color' => 'Blue', 'Size' => 'Small' ),
		);

		$item = $factory->from_variation( $variation, null );

		$this->assertTrue( $item->is_variation() );
		$this->assertNull( $item->get_parent_product() );
		$this->assertSame( 125, $item->get_parent_product_id() );
	}

	/**
	 * Test from_variation with image data.
	 *
	 * @return void
	 */
	public function test_from_variation_with_image(): void {
		$factory = new CatalogItemFactory( $this->mock_repository );

		$variation = array(
			'id'           => 243,
			'parent_id'    => 125,
			'name'         => 'Green / Medium',
			'sku'          => 'GRN-M',
			'price'        => '22.99',
			'stock_status' => 'instock',
			'attributes'   => array( 'Color' => 'Green', 'Size' => 'Medium' ),
			'image'        => array(
				'id'    => 600,
				'src'   => 'https://example.com/green.jpg',
				'width' => 80,
				'height' => 80,
			),
		);

		$item = $factory->from_variation( $variation, null );

		$this->assertSame( 600, $item->get_image()['id'] );
		$this->assertSame( 'https://example.com/green.jpg', $item->get_image()['src'] );
	}

	/**
	 * Test get_parent_context uses repository.
	 *
	 * @return void
	 */
	public function test_get_parent_context_uses_repository(): void {
		$factory = new CatalogItemFactory( $this->mock_repository );

		$this->mock_repository
			->method( 'find' )
			->with( 125 )
			->willReturn( array(
				'id'       => 125,
				'name'     => 'Parent Product',
				'sku'      => 'PARENT-001',
				'permalink' => 'https://example.com/parent',
			) );

		$context = $factory->get_parent_context( 125 );

		$this->assertNotNull( $context );
		$this->assertSame( 125, $context['id'] );
		$this->assertSame( 'Parent Product', $context['name'] );
		$this->assertSame( 'PARENT-001', $context['sku'] );
	}

	/**
	 * Test get_parent_context caches results.
	 *
	 * @return void
	 */
	public function test_get_parent_context_caches(): void {
		$factory = new CatalogItemFactory( $this->mock_repository );

		$call_count = 0;
		$this->mock_repository
			->method( 'find' )
			->willReturnCallback(
				function () use ( &$call_count ) {
					$call_count++;
					return array( 'id' => 125, 'name' => 'Test', 'sku' => '', 'permalink' => '' );
				}
			);

		$factory->get_parent_context( 125 );
		$factory->get_parent_context( 125 );

		$this->assertSame( 1, $call_count, 'Parent context should be cached' );
	}

	/**
	 * Test get_parent_context returns null for non-existent product.
	 *
	 * @return void
	 */
	public function test_get_parent_context_returns_null(): void {
		$factory = new CatalogItemFactory( $this->mock_repository );

		$this->mock_repository
			->method( 'find' )
			->with( 999 )
			->willReturn( null );

		$context = $factory->get_parent_context( 999 );

		$this->assertNull( $context );
	}

	/**
	 * Test clear_cache resets parent cache.
	 *
	 * @return void
	 */
	public function test_clear_cache(): void {
		$factory = new CatalogItemFactory( $this->mock_repository );

		$this->mock_repository
			->method( 'find' )
			->willReturn( array( 'id' => 125, 'name' => 'Test', 'sku' => '', 'permalink' => '' ) );

		$factory->get_parent_context( 125 );
		$factory->clear_cache();

		$call_count = 0;
		$this->mock_repository
			->method( 'find' )
			->willReturnCallback(
				function () use ( &$call_count ) {
					$call_count++;
					return array( 'id' => 125, 'name' => 'Test', 'sku' => '', 'permalink' => '' );
				}
			);

		$factory->get_parent_context( 125 );
		$this->assertSame( 1, $call_count, 'Cache should be cleared' );
	}

	/**
	 * Test from_product with image resolution via wp_get_attachment_image_src.
	 *
	 * @return void
	 */
	public function test_from_product_with_image(): void {
		$factory = new CatalogItemFactory( $this->mock_repository );

		$data = array(
			'id'              => 128,
			'name'            => 'Product With Image',
			'sku'             => 'IMG-001',
			'price'           => '25.00',
			'stock_status'    => 'instock',
			'permalink'       => 'https://example.com/with-image',
			'image_id'        => 500,
		);

		$item = $factory->from_product( $data );

		$this->assertNotNull( $item->get_image() );
		$this->assertSame( 500, $item->get_image()['id'] );
	}
}
