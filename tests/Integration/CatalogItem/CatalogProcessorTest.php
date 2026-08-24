<?php
/**
 * Integration tests for CatalogProcessor.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Tests\Integration\CatalogItem;

use Catalogist\CatalogItem\CatalogItem;
use Catalogist\CatalogItem\CatalogProcessor;
use Catalogist\Product\ProductQueryArgs;
use Catalogist\Product\ProductQueryResult;
use Catalogist\Product\ProductRepositoryInterface;
use Catalogist\Variation\VariationQueryArgs;
use Catalogist\Variation\VariationService;
use Catalogist\Variation\VariationServiceInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests for CatalogProcessor.
 */
class CatalogProcessorTest extends TestCase {

	/**
	 * Mock product repository.
	 *
	 * @var ProductRepositoryInterface&MockObject
	 */
	private $mock_product_repo;

	/**
	 * Mock variation service.
	 *
	 * @var VariationServiceInterface&MockObject
	 */
	private $mock_variation_service;

	/**
	 * Catalog processor under test.
	 *
	 * @var CatalogProcessor
	 */
	private CatalogProcessor $processor;

	/**
	 * Set up test fixtures.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->mock_product_repo   = $this->createMock( ProductRepositoryInterface::class );
		$this->mock_variation_service = $this->createMock( VariationServiceInterface::class );
		$this->processor           = new CatalogProcessor(
			new \Catalogist\CatalogItem\CatalogItemFactory( $this->mock_product_repo ),
			$this->mock_variation_service,
			$this->mock_product_repo
		);
	}

	/**
	 * Test process with empty product result.
	 *
	 * @return void
	 */
	public function test_process_empty_result(): void {
		$product_result = new ProductQueryResult( array(), 0, 1, 20 );

		$items = $this->processor->process( $product_result );

		$this->assertIsArray( $items );
		$this->assertEmpty( $items );
	}

	/**
	 * Test process with simple products only (parent mode).
	 *
	 * @return void
	 */
	public function test_process_simple_products_parent_mode(): void {
		// Mock product data
		$product_data = array(
			'id'              => 100,
			'name'            => 'Simple Product',
			'sku'             => 'SIMPLE-001',
			'price'           => '19.99',
			'regular_price'   => '24.99',
			'sale_price'      => '',
			'description'     => 'A simple product',
			'short_description' => 'Simple desc',
			'stock_status'    => 'instock',
			'stock_quantity'  => 50,
			'permalink'       => 'https://example.com/simple',
			'is_variable'     => false,
		);

		$this->mock_product_repo
			->method( 'find' )
			->willReturn( $product_data );

		$product_result = new ProductQueryResult( array( 100 ), 1, 1, 20 );

		$args = new \Catalogist\Variation\VariationQueryArgs(
			new \Catalogist\Variation\VariationMode( \Catalogist\Variation\VariationMode::PARENT )
		);

		$items = $this->processor->process( $product_result, $args );

		$this->assertCount( 1, $items );
		$this->assertTrue( $items[0]->is_product() );
		$this->assertFalse( $items[0]->is_variation() );
		$this->assertSame( 100, $items[0]->get_id() );
		$this->assertSame( 'Simple Product', $items[0]->get_title() );
		$this->assertSame( 'SIMPLE-001', $items[0]->get_sku() );
		$this->assertFalse( $items[0]->has_variation_table() );
	}

	/**
	 * Test process with no variation args (parent mode default).
	 *
	 * @return void
	 */
	public function test_process_no_variation_args(): void {
		$product_data = array(
			'id'              => 101,
			'name'            => 'Another Product',
			'sku'             => 'ANOTHER-001',
			'price'           => '29.99',
			'stock_status'    => 'instock',
			'permalink'       => 'https://example.com/another',
		);

		$this->mock_product_repo
			->method( 'find' )
			->willReturn( $product_data );

		$product_result = new ProductQueryResult( array( 101 ), 1, 1, 20 );

		// No variation args = parent mode
		$items = $this->processor->process( $product_result );

		$this->assertCount( 1, $items );
		$this->assertTrue( $items[0]->is_product() );
		$this->assertFalse( $items[0]->has_variation_table() );
	}

	/**
	 * Test process with variable product in 'all' mode.
	 *
	 * @return void
	 */
	public function test_process_variable_product_all_mode(): void {
		// Parent product data
		$parent_data = array(
			'id'              => 200,
			'name'            => 'Variable Product',
			'sku'             => 'VAR-001',
			'price'           => '19.99',
			'stock_status'    => 'instock',
			'permalink'       => 'https://example.com/variable',
			'is_variable'     => true,
		);

		// Variation results
		$variation_result = new \Catalogist\Variation\VariationQueryResult(
			200,
			array(
				201 => array(
					'id'           => 201,
					'parent_id'    => 200,
					'name'         => 'Red / Large',
					'sku'          => 'RED-L',
					'price'        => '21.99',
					'regular_price' => '24.99',
					'sale_price'   => '',
					'stock_status' => 'instock',
					'stock_quantity' => 10,
					'attributes'   => array( 'Color' => 'Red', 'Size' => 'Large' ),
					'image'        => null,
					'dimensions'   => array(),
				),
				202 => array(
					'id'           => 202,
					'parent_id'    => 200,
					'name'         => 'Blue / Small',
					'sku'          => 'BLU-S',
					'price'        => '18.99',
					'regular_price' => '20.99',
					'sale_price'   => '18.99',
					'stock_status' => 'outofstock',
					'stock_quantity' => 0,
					'attributes'   => array( 'Color' => 'Blue', 'Size' => 'Small' ),
					'image'        => null,
					'dimensions'   => array(),
				),
			),
			2,
			new \Catalogist\Variation\VariationMode( \Catalogist\Variation\VariationMode::ALL )
		);

		$this->mock_product_repo
			->method( 'find' )
			->willReturn( $parent_data );

		$this->mock_variation_service
			->method( 'expand' )
			->willReturn( $variation_result );

		$product_result = new ProductQueryResult( array( 200 ), 1, 1, 20 );

		$args = new VariationQueryArgs(
			new \Catalogist\Variation\VariationMode( \Catalogist\Variation\VariationMode::ALL )
		);

		$items = $this->processor->process( $product_result, $args );

		// Parent should be replaced by variations in 'all' mode
		$this->assertCount( 2, $items );
		$this->assertTrue( $items[0]->is_variation() );
		$this->assertTrue( $items[1]->is_variation() );
		$this->assertSame( 201, $items[0]->get_id() );
		$this->assertSame( 202, $items[1]->get_id() );
		$this->assertSame( 200, $items[0]->get_parent_product_id() );
		$this->assertSame( 200, $items[1]->get_parent_product_id() );
	}

	/**
	 * Test process with variable product in 'table' mode.
	 *
	 * @return void
	 */
	public function test_process_variable_product_table_mode(): void {
		$parent_data = array(
			'id'              => 300,
			'name'            => 'Table Product',
			'sku'             => 'TABLE-001',
			'price'           => '19.99',
			'stock_status'    => 'instock',
			'permalink'       => 'https://example.com/table',
			'is_variable'     => true,
		);

		$variation_result = new \Catalogist\Variation\VariationQueryResult(
			300,
			array(
				301 => array(
					'id'           => 301,
					'parent_id'    => 300,
					'name'         => 'Red',
					'sku'          => 'RED',
					'price'        => '21.99',
					'stock_status' => 'instock',
					'attributes'   => array( 'Color' => 'Red' ),
					'image'        => null,
					'dimensions'   => array(),
				),
			),
			1,
			new \Catalogist\Variation\VariationMode( \Catalogist\Variation\VariationMode::TABLE )
		);

		$this->mock_product_repo
			->method( 'find' )
			->willReturn( $parent_data );

		$this->mock_variation_service
			->method( 'expand' )
			->willReturn( $variation_result );

		$product_result = new ProductQueryResult( array( 300 ), 1, 1, 20 );

		$args = new VariationQueryArgs(
			new \Catalogist\Variation\VariationMode( \Catalogist\Variation\VariationMode::TABLE )
		);

		$items = $this->processor->process( $product_result, $args );

		// In table mode, parent stays and has variation_table
		$this->assertCount( 1, $items );
		$this->assertTrue( $items[0]->is_product() );
		$this->assertSame( 300, $items[0]->get_id() );
		$this->assertTrue( $items[0]->has_variation_table() );

		$table = $items[0]->get_variation_table();
		$this->assertArrayHasKey( 'variations', $table );
		$this->assertArrayHasKey( 'parent_id', $table );
		$this->assertSame( 300, $table['parent_id'] );
		$this->assertArrayHasKey( 301, $table['variations'] );
		$this->assertSame( 'Red', $table['variations'][301]['title'] );
	}

	/**
	 * Test process with selected mode.
	 *
	 * @return void
	 */
	public function test_process_selected_mode(): void {
		$parent_data = array(
			'id'              => 400,
			'name'            => 'Selected Product',
			'sku'             => 'SEL-001',
			'price'           => '19.99',
			'stock_status'    => 'instock',
			'permalink'       => 'https://example.com/selected',
		);

		$variation_result = new \Catalogist\Variation\VariationQueryResult(
			400,
			array(
				401 => array(
					'id'           => 401,
					'parent_id'    => 400,
					'name'         => 'Selected Variation',
					'sku'          => 'SEL-V',
					'price'        => '25.99',
					'stock_status' => 'instock',
					'attributes'   => array( 'Size' => 'L' ),
					'image'        => null,
					'dimensions'   => array(),
				),
			),
			1,
			new \Catalogist\Variation\VariationMode( \Catalogist\Variation\VariationMode::SELECTED )
		);

		$this->mock_product_repo
			->method( 'find' )
			->willReturn( $parent_data );

		$this->mock_variation_service
			->method( 'expand' )
			->willReturn( $variation_result );

		$product_result = new ProductQueryResult( array( 400 ), 1, 1, 20 );

		$args = new VariationQueryArgs(
			new \Catalogist\Variation\VariationMode( \Catalogist\Variation\VariationMode::SELECTED ),
			array( 401 )
		);

		$items = $this->processor->process( $product_result, $args );

		$this->assertCount( 1, $items );
		$this->assertTrue( $items[0]->is_variation() );
		$this->assertSame( 401, $items[0]->get_id() );
	}

	/**
	 * Test process with multiple selected mode.
	 *
	 * @return void
	 */
	public function test_process_multiple_selected_mode(): void {
		$parent_data = array(
			'id'              => 500,
			'name'            => 'Multi Product',
			'sku'             => 'MULTI-001',
			'price'           => '19.99',
			'stock_status'    => 'instock',
			'permalink'       => 'https://example.com/multi',
		);

		$variation_result = new \Catalogist\Variation\VariationQueryResult(
			500,
			array(
				501 => array(
					'id'           => 501,
					'parent_id'    => 500,
					'name'         => 'Multi Var 1',
					'sku'          => 'M1',
					'price'        => '21.99',
					'stock_status' => 'instock',
					'attributes'   => array(),
					'image'        => null,
					'dimensions'   => array(),
				),
				502 => array(
					'id'           => 502,
					'parent_id'    => 500,
					'name'         => 'Multi Var 2',
					'sku'          => 'M2',
					'price'        => '22.99',
					'stock_status' => 'instock',
					'attributes'   => array(),
					'image'        => null,
					'dimensions'   => array(),
				),
			),
			2,
			new \Catalogist\Variation\VariationMode( \Catalogist\Variation\VariationMode::MULTIPLE )
		);

		$this->mock_product_repo
			->method( 'find' )
			->willReturn( $parent_data );

		$this->mock_variation_service
			->method( 'expand' )
			->willReturn( $variation_result );

		$product_result = new ProductQueryResult( array( 500 ), 1, 1, 20 );

		$args = new VariationQueryArgs(
			new \Catalogist\Variation\VariationMode( \Catalogist\Variation\VariationMode::MULTIPLE ),
			array( 501, 502 )
		);

		$items = $this->processor->process( $product_result, $args );

		$this->assertCount( 2, $items );
		$this->assertTrue( $items[0]->is_variation() );
		$this->assertTrue( $items[1]->is_variation() );
		$this->assertSame( 501, $items[0]->get_id() );
		$this->assertSame( 502, $items[1]->get_id() );
	}

	/**
	 * Test process with mixed catalog (simple + variable products).
	 *
	 * @return void
	 */
	public function test_process_mixed_catalog(): void {
		$this->mock_product_repo
			->method( 'find' )
			->willReturnMap(
				array(
					array( 600, array(
						'id' => 600, 'name' => 'Simple', 'sku' => 'SIMP',
						'price' => '10.00', 'stock_status' => 'instock',
						'permalink' => 'https://example.com/simple',
					)),
					array( 601, array(
						'id' => 601, 'name' => 'Variable', 'sku' => 'VAR',
						'price' => '20.00', 'stock_status' => 'instock',
						'permalink' => 'https://example.com/variable',
					)),
				)
			);

		$variation_result = new \Catalogist\Variation\VariationQueryResult(
			601,
			array(
				602 => array(
					'id'           => 602,
					'parent_id'    => 601,
					'name'         => 'Var A',
					'sku'          => 'VA',
					'price'        => '22.00',
					'stock_status' => 'instock',
					'attributes'   => array(),
					'image'        => null,
					'dimensions'   => array(),
				),
			),
			1,
			new \Catalogist\Variation\VariationMode( \Catalogist\Variation\VariationMode::ALL )
		);

		$this->mock_variation_service
			->method( 'expand' )
			->willReturn( $variation_result );

		$product_result = new ProductQueryResult( array( 600, 601 ), 2, 1, 20 );

		$args = new VariationQueryArgs(
			new \Catalogist\Variation\VariationMode( \Catalogist\Variation\VariationMode::ALL )
		);

		$items = $this->processor->process( $product_result, $args );

		// Simple product + 1 variation (parent replaced)
		$this->assertCount( 2, $items );
		$this->assertTrue( $items[0]->is_product() );
		$this->assertSame( 600, $items[0]->get_id() );
		$this->assertTrue( $items[1]->is_variation() );
		$this->assertSame( 602, $items[1]->get_id() );
	}

	/**
	 * Test find method.
	 *
	 * @return void
	 */
	public function test_find(): void {
		$product_data = array(
			'id' => 700, 'name' => 'Find Me', 'sku' => 'FIND',
			'price' => '15.00', 'stock_status' => 'instock',
			'permalink' => 'https://example.com/find',
		);

		$this->mock_product_repo
			->method( 'find' )
			->with( 700 )
			->willReturn( $product_data );

		$item = $this->processor->find( 700 );

		$this->assertNotNull( $item );
		$this->assertTrue( $item->is_product() );
		$this->assertSame( 700, $item->get_id() );
		$this->assertSame( 'Find Me', $item->get_title() );
	}

	/**
	 * Test find returns null for non-existent product.
	 *
	 * @return void
	 */
	public function test_find_returns_null(): void {
		$this->mock_product_repo
			->method( 'find' )
			->willReturn( null );

		$item = $this->processor->find( 99999 );

		$this->assertNull( $item );
	}

	/**
	 * Test process with non-variable product in expansion mode.
	 *
	 * @return void
	 */
	public function test_non_variable_product_in_expansion_mode(): void {
		$parent_data = array(
			'id' => 800, 'name' => 'Simple Only', 'sku' => 'SO',
			'price' => '10.00', 'stock_status' => 'instock',
			'permalink' => 'https://example.com/simple-only',
		);

		$variation_result = new \Catalogist\Variation\VariationQueryResult(
			800, array(), 0,
			new \Catalogist\Variation\VariationMode( \Catalogist\Variation\VariationMode::ALL )
		);

		$this->mock_product_repo
			->method( 'find' )
			->willReturn( $parent_data );

		$this->mock_variation_service
			->method( 'expand' )
			->willReturn( $variation_result );

		$product_result = new ProductQueryResult( array( 800 ), 1, 1, 20 );

		$args = new VariationQueryArgs(
			new \Catalogist\Variation\VariationMode( \Catalogist\Variation\VariationMode::ALL )
		);

		$items = $this->processor->process( $product_result, $args );

		// Non-variable product stays as product even in expansion mode
		$this->assertCount( 1, $items );
		$this->assertTrue( $items[0]->is_product() );
		$this->assertSame( 800, $items[0]->get_id() );
		$this->assertFalse( $items[0]->has_variation_table() );
	}

	/**
	 * Test process with array product data (not IDs).
	 *
	 * @return void
	 */
	public function test_process_with_array_products(): void {
		$product_data = array(
			'id' => 900, 'name' => 'Array Product', 'sku' => 'AP',
			'price' => '30.00', 'stock_status' => 'instock',
			'permalink' => 'https://example.com/array-product',
		);

		$product_result = new ProductQueryResult( array( $product_data ), 1, 1, 20 );

		$items = $this->processor->process( $product_result );

		$this->assertCount( 1, $items );
		$this->assertTrue( $items[0]->is_product() );
		$this->assertSame( 'Array Product', $items[0]->get_title() );
	}

	/**
	 * Test architecture boundary: processor does not reference WC_Product.
	 *
	 * @return void
	 */
	public function test_no_wc_product_dependency(): void {
		$reflection = new \ReflectionClass( CatalogProcessor::class );
		$contents = file_get_contents( $reflection->getFileName() );

		$this->assertStringNotContainsString( 'WC_Product', $contents,
			'CatalogProcessor should not reference WC_Product class' );
	}
}
