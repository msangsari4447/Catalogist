<?php
/**
 * Catalog Item value object.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\CatalogItem;

defined( 'ABSPATH' ) || exit;

/**
 * Represents a single sellable entry in a catalog.
 *
 * Can be either a standalone product or a variation of a variable product.
 * Immutable value object — all fields set in constructor.
 */
final class CatalogItem {

	/**
	 * WooCommerce product or variation ID.
	 *
	 * @var int
	 */
	private int $id;

	/**
	 * Item type: 'product' or 'variation'.
	 *
	 * @var string
	 */
	private string $type;

	/**
	 * Parent product ID. 0 for standalone products.
	 *
	 * @var int
	 */
	private int $parent_product_id;

	/**
	 * Product or variation name.
	 *
	 * @var string
	 */
	private string $title;

	/**
	 * Stock Keeping Unit.
	 *
	 * @var string
	 */
	private string $sku;

	/**
	 * Active price (sale price if on sale, otherwise regular price).
	 *
	 * @var string
	 */
	private string $price;

	/**
	 * Base price.
	 *
	 * @var string
	 */
	private string $regular_price;

	/**
	 * Sale price. Empty string if no sale.
	 *
	 * @var string
	 */
	private string $sale_price;

	/**
	 * Full product description. May contain HTML.
	 *
	 * @var string
	 */
	private string $description;

	/**
	 * Short description / excerpt. May contain HTML.
	 *
	 * @var string
	 */
	private string $short_description;

	/**
	 * Main image data. Null if no image.
	 *
	 * @var array{id: int, src: string, width: int, height: int}|null
	 */
	private ?array $image;

	/**
	 * Gallery attachment IDs.
	 *
	 * @var array<int>
	 */
	private array $gallery;

	/**
	 * Category IDs.
	 *
	 * @var array<int>
	 */
	private array $categories;

	/**
	 * Tag IDs.
	 *
	 * @var array<int>
	 */
	private array $tags;

	/**
	 * Variation attributes. Empty array for simple products.
	 *
	 * @var array<string, string>
	 */
	private array $attributes;

	/**
	 * Stock status: 'instock', 'outofstock', 'onbackorder'.
	 *
	 * @var string
	 */
	private string $stock_status;

	/**
	 * Stock quantity. Null if not tracked.
	 *
	 * @var int|null
	 */
	private ?int $stock_quantity;

	/**
	 * WordPress permalink.
	 *
	 * @var string
	 */
	private string $permalink;

	/**
	 * Parent product context for variations. Null for products.
	 *
	 * @var array{id: int, name: string, sku: string, permalink: string}|null
	 */
	private ?array $parent_product;

	/**
	 * Variation table data for table mode. Null otherwise.
	 *
	 * @var array{variations: array<int, array<string, mixed>>, parent_id: int}|null
	 */
	private ?array $variation_table;

	/**
	 * Extra metadata (dimensions, weight, shipping class, custom meta).
	 *
	 * @var array<string, mixed>
	 */
	private array $metadata;

	/**
	 * Constructor.
	 *
	 * @param int                              $id                Product or variation ID.
	 * @param string                           $type              'product' or 'variation'.
	 * @param int                              $parent_product_id Parent product ID (0 for products).
	 * @param string                           $title             Product or variation name.
	 * @param string                           $sku               Stock Keeping Unit.
	 * @param string                           $price             Active price.
	 * @param string                           $regular_price     Base price.
	 * @param string                           $sale_price        Sale price.
	 * @param string                           $description       Full description.
	 * @param string                           $short_description Short description.
	 * @param array{id:int,src:string,width:int,height:int}|null $image             Main image.
	 * @param array<int>                       $gallery           Gallery attachment IDs.
	 * @param array<int>                       $categories        Category IDs.
	 * @param array<int>                       $tags              Tag IDs.
	 * @param array<string, string>            $attributes        Variation attributes.
	 * @param string                           $stock_status      Stock status.
	 * @param int|null                         $stock_quantity    Stock quantity.
	 * @param string                           $permalink         WordPress permalink.
	 * @param array{id:int,name:string,sku:string,permalink:string}|null $parent_product Parent context.
	 * @param array{variations:array<int,array<string,mixed>>,parent_id:int}|null $variation_table   Variation table.
	 * @param array<string, mixed>             $metadata          Extra metadata.
	 */
	public function __construct(
		int $id,
		string $type,
		int $parent_product_id = 0,
		string $title = '',
		string $sku = '',
		string $price = '',
		string $regular_price = '',
		string $sale_price = '',
		string $description = '',
		string $short_description = '',
		?array $image = null,
		array $gallery = array(),
		array $categories = array(),
		array $tags = array(),
		array $attributes = array(),
		string $stock_status = 'instock',
		?int $stock_quantity = null,
		string $permalink = '',
		?array $parent_product = null,
		?array $variation_table = null,
		array $metadata = array()
	) {
		$this->id                  = $id;
		$this->type                = $type;
		$this->parent_product_id   = $parent_product_id;
		$this->title               = $title;
		$this->sku                 = $sku;
		$this->price               = $price;
		$this->regular_price       = $regular_price;
		$this->sale_price          = $sale_price;
		$this->description         = $description;
		$this->short_description   = $short_description;
		$this->image               = $image;
		$this->gallery             = $gallery;
		$this->categories          = $categories;
		$this->tags                = $tags;
		$this->attributes          = $attributes;
		$this->stock_status        = $stock_status;
		$this->stock_quantity      = $stock_quantity;
		$this->permalink           = $permalink;
		$this->parent_product      = $parent_product;
		$this->variation_table     = $variation_table;
		$this->metadata            = $metadata;
	}

	/**
	 * Get the item ID.
	 *
	 * @return int
	 */
	public function get_id(): int {
		return $this->id;
	}

	/**
	 * Get the item type.
	 *
	 * @return string
	 */
	public function get_type(): string {
		return $this->type;
	}

	/**
	 * Get the parent product ID.
	 *
	 * @return int
	 */
	public function get_parent_product_id(): int {
		return $this->parent_product_id;
	}

	/**
	 * Get the title.
	 *
	 * @return string
	 */
	public function get_title(): string {
		return $this->title;
	}

	/**
	 * Get the SKU.
	 *
	 * @return string
	 */
	public function get_sku(): string {
		return $this->sku;
	}

	/**
	 * Get the active price.
	 *
	 * @return string
	 */
	public function get_price(): string {
		return $this->price;
	}

	/**
	 * Get the regular price.
	 *
	 * @return string
	 */
	public function get_regular_price(): string {
		return $this->regular_price;
	}

	/**
	 * Get the sale price.
	 *
	 * @return string
	 */
	public function get_sale_price(): string {
		return $this->sale_price;
	}

	/**
	 * Get the description.
	 *
	 * @return string
	 */
	public function get_description(): string {
		return $this->description;
	}

	/**
	 * Get the short description.
	 *
	 * @return string
	 */
	public function get_short_description(): string {
		return $this->short_description;
	}

	/**
	 * Get the image data.
	 *
	 * @return array{id: int, src: string, width: int, height: int}|null
	 */
	public function get_image(): ?array {
		return $this->image;
	}

	/**
	 * Get gallery attachment IDs.
	 *
	 * @return array<int>
	 */
	public function get_gallery(): array {
		return $this->gallery;
	}

	/**
	 * Get category IDs.
	 *
	 * @return array<int>
	 */
	public function get_categories(): array {
		return $this->categories;
	}

	/**
	 * Get tag IDs.
	 *
	 * @return array<int>
	 */
	public function get_tags(): array {
		return $this->tags;
	}

	/**
	 * Get variation attributes.
	 *
	 * @return array<string, string>
	 */
	public function get_attributes(): array {
		return $this->attributes;
	}

	/**
	 * Get stock status.
	 *
	 * @return string
	 */
	public function get_stock_status(): string {
		return $this->stock_status;
	}

	/**
	 * Get stock quantity.
	 *
	 * @return int|null
	 */
	public function get_stock_quantity(): ?int {
		return $this->stock_quantity;
	}

	/**
	 * Get the permalink.
	 *
	 * @return string
	 */
	public function get_permalink(): string {
		return $this->permalink;
	}

	/**
	 * Get parent product context.
	 *
	 * @return array{id: int, name: string, sku: string, permalink: string}|null
	 */
	public function get_parent_product(): ?array {
		return $this->parent_product;
	}

	/**
	 * Get variation table data.
	 *
	 * @return array{variations: array<int, array<string, mixed>>, parent_id: int}|null
	 */
	public function get_variation_table(): ?array {
		return $this->variation_table;
	}

	/**
	 * Get extra metadata.
	 *
	 * @return array<string, mixed>
	 */
	public function get_metadata(): array {
		return $this->metadata;
	}

	/**
	 * Check if this is a product.
	 *
	 * @return bool
	 */
	public function is_product(): bool {
		return 'product' === $this->type;
	}

	/**
	 * Check if this is a variation.
	 *
	 * @return bool
	 */
	public function is_variation(): bool {
		return 'variation' === $this->type;
	}

	/**
	 * Check if this item has a variation table (table mode).
	 *
	 * @return bool
	 */
	public function has_variation_table(): bool {
		return null !== $this->variation_table;
	}

	/**
	 * Check if this is a variable product (has variations).
	 *
	 * @return bool
	 */
	public function is_variable_product(): bool {
		return 'product' === $this->type && $this->parent_product_id === 0 && ! empty( $this->metadata['has_variations'] );
	}

	/**
	 * Convert to array.
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return array(
			'id'                => $this->id,
			'type'              => $this->type,
			'parent_product_id' => $this->parent_product_id,
			'title'             => $this->title,
			'sku'               => $this->sku,
			'price'             => $this->price,
			'regular_price'     => $this->regular_price,
			'sale_price'        => $this->sale_price,
			'description'       => $this->description,
			'short_description' => $this->short_description,
			'image'             => $this->image,
			'gallery'           => $this->gallery,
			'categories'        => $this->categories,
			'tags'              => $this->tags,
			'attributes'        => $this->attributes,
			'stock_status'      => $this->stock_status,
			'stock_quantity'    => $this->stock_quantity,
			'permalink'         => $this->permalink,
			'parent_product'    => $this->parent_product,
			'variation_table'   => $this->variation_table,
			'metadata'          => $this->metadata,
		);
	}
}
