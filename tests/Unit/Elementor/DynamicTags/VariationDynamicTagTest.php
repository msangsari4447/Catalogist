<?php
/**
 * Tests for Variation Dynamic Tags.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Tests\Unit\Elementor\DynamicTags;

use Catalogist\CatalogItem\CatalogItem;
use Catalogist\Elementor\DynamicTags\VariationNameDynamicTag;
use Catalogist\Elementor\DynamicTags\VariationSkuDynamicTag;
use Catalogist\Elementor\DynamicTags\VariationPriceDynamicTag;
use Catalogist\Elementor\DynamicTags\VariationAttributesDynamicTag;
use PHPUnit\Framework\TestCase;

/**
 * Variation dynamic tag tests.
 */
class VariationDynamicTagTest extends TestCase {

	/**
	 * Test VariationNameDynamicTag renders variation name.
	 */
	public function test_variation_name_dynamic_tag(): void {
		$item = $this->createMockCatalogItem( 'Red Shirt - Large', 'VAR-001', '24.99' );

		$tag = new VariationNameDynamicTag();
		$tag->set_catalog_item( $item );
		$tag->set_settings( array( 'variation_id' => 456, 'parent_product_id' => 123 ) );

		$result = $tag->render();
		$this->assertEquals( 'Red Shirt - Large', $result );
	}

	/**
	 * Test VariationSkuDynamicTag renders SKU.
	 */
	public function test_variation_sku_dynamic_tag(): void {
		$item = $this->createMockCatalogItem( 'Blue Shirt - Medium', 'VAR-SKU-002', '34.99' );

		$tag = new VariationSkuDynamicTag();
		$tag->set_catalog_item( $item );
		$tag->set_settings( array( 'variation_id' => 457, 'parent_product_id' => 123 ) );

		$result = $tag->render();
		$this->assertEquals( 'VAR-SKU-002', $result );
	}

	/**
	 * Test VariationPriceDynamicTag renders price.
	 */
	public function test_variation_price_dynamic_tag(): void {
		$item = $this->createMockCatalogItem( 'Green Shirt - Small', 'VAR-003', '44.99' );

		$tag = new VariationPriceDynamicTag();
		$tag->set_catalog_item( $item );
		$tag->set_settings( array( 'variation_id' => 458, 'parent_product_id' => 123 ) );

		$result = $tag->render();
		$this->assertEquals( '44.99', $result );
	}

	/**
	 * Test VariationAttributesDynamicTag renders attributes.
	 */
	public function test_variation_attributes_dynamic_tag(): void {
		$item = $this->createMockCatalogItem(
			'Size XL Color Blue',
			'VAR-004',
			'54.99',
			array(),
			'',
			'instock',
			array( 'Color' => 'Blue', 'Size' => 'XL' )
		);

		$tag = new VariationAttributesDynamicTag();
		$tag->set_catalog_item( $item );
		$tag->set_settings( array( 'variation_id' => 459, 'parent_product_id' => 123 ) );

		$result = $tag->render();
		$this->assertStringContainsString( 'Color: Blue', $result );
		$this->assertStringContainsString( 'Size: XL', $result );
	}

	/**
	 * Test tags return empty for missing variation.
	 */
	public function test_variation_tags_return_empty_for_missing(): void {
		$tag = new VariationNameDynamicTag();
		$tag->set_catalog_item( null );
		$tag->set_settings( array( 'variation_id' => 0 ) );

		$result = $tag->render();
		$this->assertEmpty( $result );
	}

	/**
	 * Test tag names.
	 */
	public function test_variation_tag_names(): void {
		$tags = array(
			new VariationNameDynamicTag(),
			new VariationSkuDynamicTag(),
			new VariationPriceDynamicTag(),
			new VariationAttributesDynamicTag(),
		);

		$expected_names = array(
			'catalogist_variation_name',
			'catalogist_variation_sku',
			'catalogist_variation_price',
			'catalogist_variation_attributes',
		);

		foreach ( $tags as $index => $tag ) {
			$this->assertEquals( $expected_names[ $index ], $tag->get_name() );
		}
	}

	/**
	 * Test tag groups.
	 */
	public function test_variation_tag_groups(): void {
		$tag = new VariationNameDynamicTag();
		$group = $tag->get_group();
		$this->assertContains( 'catalogist-variations', $group );
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
	private function createMockCatalogItem(
		string $title,
		string $sku,
		string $price
	): CatalogItem {
		return new CatalogItem(
			id: 456,
			type: 'variation',
			title: $title,
			sku: $sku,
			price: $price,
			image: array( 'src' => '', 'alt' => '', 'width' => 0, 'height' => 0 ),
			permalink: '',
			stock_status: 'instock',
			attributes: array(),
			description: '',
			categories: array(),
			parent_product: 123,
			variation_table: null,
			metadata: array()
		);
	}
}
