<?php
/**
 * Tests for Elementor Dynamic Tag Integration.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Tests\Integration\Elementor;

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
use Catalogist\Elementor\DynamicTags\VariationNameDynamicTag;
use Catalogist\Elementor\DynamicTags\VariationSkuDynamicTag;
use Catalogist\Elementor\DynamicTags\VariationPriceDynamicTag;
use Catalogist\Elementor\DynamicTags\VariationAttributesDynamicTag;
use Catalogist\Elementor\DynamicTags\CatalogTitleDynamicTag;
use Catalogist\Elementor\DynamicTags\CatalogProductCountDynamicTag;
use PHPUnit\Framework\TestCase;

/**
 * Elementor dynamic tag integration tests.
 */
class ElementorDynamicTagIntegrationTest extends TestCase {

	/**
	 * Test all product dynamic tags can be instantiated.
	 */
	public function test_all_product_dynamic_tags_instantiate(): void {
		$tags = array(
			new ProductNameDynamicTag(),
			new ProductSkuDynamicTag(),
			new ProductPriceDynamicTag(),
			new ProductImageDynamicTag(),
			new ProductUrlDynamicTag(),
			new ProductDescriptionDynamicTag(),
			new ProductCategoriesDynamicTag(),
			new ProductAttributesDynamicTag(),
			new ProductStockStatusDynamicTag(),
			new ProductQrCodeDynamicTag(),
		);

		foreach ( $tags as $tag ) {
			$this->assertInstanceOf( \Catalogist\Elementor\DynamicTags\ProductDynamicTagBase::class, $tag );
			$this->assertNotEmpty( $tag->get_name() );
			$this->assertNotEmpty( $tag->get_title() );
		}
	}

	/**
	 * Test all variation dynamic tags can be instantiated.
	 */
	public function test_all_variation_dynamic_tags_instantiate(): void {
		$tags = array(
			new VariationNameDynamicTag(),
			new VariationSkuDynamicTag(),
			new VariationPriceDynamicTag(),
			new VariationAttributesDynamicTag(),
		);

		foreach ( $tags as $tag ) {
			$this->assertInstanceOf( \Catalogist\Elementor\DynamicTags\VariationDynamicTagBase::class, $tag );
			$this->assertNotEmpty( $tag->get_name() );
			$this->assertNotEmpty( $tag->get_title() );
		}
	}

	/**
	 * Test catalog dynamic tags can be instantiated.
	 */
	public function test_catalog_dynamic_tags_instantiate(): void {
		$tags = array(
			new CatalogTitleDynamicTag(),
			new CatalogProductCountDynamicTag(),
		);

		foreach ( $tags as $tag ) {
			$this->assertInstanceOf( \Catalogist\Elementor\DynamicTags\ProductDynamicTagBase::class, $tag );
			$this->assertNotEmpty( $tag->get_name() );
			$this->assertNotEmpty( $tag->get_title() );
		}
	}

	/**
	 * Test dynamic tag IDs are unique.
	 */
	public function test_dynamic_tag_ids_are_unique(): void {
		$all_tags = array(
			new ProductNameDynamicTag(),
			new ProductSkuDynamicTag(),
			new ProductPriceDynamicTag(),
			new ProductImageDynamicTag(),
			new ProductUrlDynamicTag(),
			new ProductDescriptionDynamicTag(),
			new ProductCategoriesDynamicTag(),
			new ProductAttributesDynamicTag(),
			new ProductStockStatusDynamicTag(),
			new ProductQrCodeDynamicTag(),
			new VariationNameDynamicTag(),
			new VariationSkuDynamicTag(),
			new VariationPriceDynamicTag(),
			new VariationAttributesDynamicTag(),
			new CatalogTitleDynamicTag(),
			new CatalogProductCountDynamicTag(),
		);

		$names = array_map( fn( $tag ) => $tag->get_name(), $all_tags );
		$unique = array_unique( $names );

		$this->assertCount( 16, $names );
		$this->assertCount( 16, $unique );
	}

	/**
	 * Test dynamic tag groups.
	 */
	public function test_dynamic_tag_groups(): void {
		$product_tags = array(
			new ProductNameDynamicTag(),
			new ProductSkuDynamicTag(),
		);

		$variation_tags = array(
			new VariationNameDynamicTag(),
			new VariationSkuDynamicTag(),
		);

		$catalog_tags = array(
			new CatalogTitleDynamicTag(),
			new CatalogProductCountDynamicTag(),
		);

		foreach ( $product_tags as $tag ) {
			$group = $tag->get_group();
			$this->assertContains( 'catalogist-products', $group );
		}

		foreach ( $variation_tags as $tag ) {
			$group = $tag->get_group();
			$this->assertContains( 'catalogist-variations', $group );
		}

		foreach ( $catalog_tags as $tag ) {
			$group = $tag->get_group();
			$this->assertContains( 'catalogist-catalogs', $group );
		}
	}

	/**
	 * Test render_plain_content for accessibility.
	 */
	public function test_render_plain_content(): void {
		$item = $this->createMockCatalogItem( 'Test Product', 'TEST-001', '19.99' );

		$tag = new ProductNameDynamicTag();
		$tag->set_catalog_item( $item );
		$tag->set_settings( array( 'product_id' => 123 ) );

		$result = $tag->render_plain_content( array( 'product_id' => 123 ) );
		$this->assertEquals( 'Test Product', $result );
	}

	/**
	 * Test get_control_settings.
	 */
	public function test_get_control_settings(): void {
		$tag = new ProductNameDynamicTag();
		$settings = $tag->get_control_settings();

		$this->assertArrayHasKey( 'product_id', $settings );
		$this->assertEquals( 'text', $settings['product_id']['type'] );
	}

	/**
	 * Test variation tag settings.
	 */
	public function test_variation_tag_settings(): void {
		$tag = new VariationNameDynamicTag();
		$settings = $tag->get_control_settings();

		$this->assertArrayHasKey( 'variation_id', $settings );
		$this->assertArrayHasKey( 'parent_product_id', $settings );
	}

	/**
	 * Helper to create mock catalog items.
	 *
	 * @param string $title Title.
	 * @param string $sku SKU.
	 * @param string $price Price.
	 *
	 * @return CatalogItem
	 */
	private function createMockCatalogItem( string $title, string $sku, string $price ): CatalogItem {
		return new CatalogItem(
			id: 123,
			type: 'product',
			title: $title,
			sku: $sku,
			price: $price,
			image: array( 'src' => '', 'alt' => '', 'width' => 0, 'height' => 0 ),
			permalink: '',
			stock_status: 'instock',
			attributes: array(),
			description: '',
			categories: array(),
			parent_product: null,
			variation_table: null,
			metadata: array()
		);
	}
}
