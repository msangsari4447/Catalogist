<?php

declare(strict_types=1);

namespace Catalogist;

/**
 * Catalog Item — pure data object.
 *
 * Represents a single product or variation item ready for catalog rendering.
 * This class holds only the final normalized data; no queries or rendering logic.
 */
final class CatalogItem {

	/**
	 * Item ID.
	 *
	 * @var int
	 */
	public int $id;

	/**
	 * Item type: 'simple', 'variable', 'variation'.
	 *
	 * @var string
	 */
	public string $type;

	/**
	 * Parent product ID (null for simple products).
	 *
	 * @var int|null
	 */
	public ?int $parent_id;

	/**
	 * Product title.
	 *
	 * @var string
	 */
	public string $title;

	/**
	 * Product slug.
	 *
	 * @var string
	 */
	public string $slug;

	/**
	 * SKU (nullable).
	 *
	 * @var string|null
	 */
	public ?string $sku;

	/**
	 * Regular price (null if not set).
	 *
	 * @var float|null
	 */
	public ?float $regular_price;

	/**
	 * Sale price (null if no sale).
	 *
	 * @var float|null
	 */
	public ?float $sale_price;

	/**
	 * Current price (regular or sale).
	 *
	 * @var float|null
	 */
	public ?float $price;

	/**
	 * Stock status: 'instock', 'outofstock', 'onbackorder'.
	 *
	 * @var string|null
	 */
	public ?string $stock_status;

	/**
	 * Main image URL (nullable).
	 *
	 * @var string|null
	 */
	public ?string $image_url;

	/**
	 * Product permalink (nullable).
	 *
	 * @var string|null
	 */
	public ?string $permalink;

	/**
	 * Category slugs.
	 *
	 * @var list<string>
	 */
	public array $categories;

	/**
	 * Tag slugs.
	 *
	 * @var list<string>
	 */
	public array $tags;

	/**
	 * Catalog context (for locale/currency).
	 *
	 * @var CatalogContext
	 */
	public CatalogContext $context;

	/**
	 * Constructor.
	 *
	 * @param int              $id         Item ID.
	 * @param string           $type       Item type.
	 * @param int|null         $parent_id  Parent product ID.
	 * @param string           $title      Product title.
	 * @param string           $slug       Product slug.
	 * @param string|null      $sku        SKU.
	 * @param float|null       $regular_price Regular price.
	 * @param float|null       $sale_price  Sale price.
	 * @param float|null       $price       Current price.
	 * @param string|null      $stock_status Stock status.
	 * @param string|null      $image_url   Main image URL.
	 * @param string|null      $permalink   Permalink.
	 * @param list<string>     $categories  Category slugs.
	 * @param list<string>     $tags        Tag slugs.
	 * @param CatalogContext   $context     Catalog context.
	 */
	public function __construct(
		int $id,
		string $type,
		?int $parent_id,
		string $title,
		string $slug,
		?string $sku,
		?float $regular_price,
		?float $sale_price,
		?float $price,
		?string $stock_status,
		?string $image_url,
		?string $permalink,
		array $categories,
		array $tags,
		CatalogContext $context
	) {
		$this->id            = $id;
		$this->type          = $type;
		$this->parent_id     = $parent_id;
		$this->title         = $title;
		$this->slug          = $slug;
		$this->sku           = $sku;
		$this->regular_price = $regular_price;
		$this->sale_price    = $sale_price;
		$this->price         = $price;
		$this->stock_status  = $stock_status;
		$this->image_url     = $image_url;
		$this->permalink     = $permalink;
		$this->categories    = $categories;
		$this->tags          = $tags;
		$this->context       = $context;
	}

	/**
	 * Check if item is a simple product.
	 *
	 * @return bool
	 */
	public function is_simple(): bool {
		return 'simple' === $this->type;
	}

	/**
	 * Check if item is a variation.
	 *
	 * @return bool
	 */
	public function is_variation(): bool {
		return 'variation' === $this->type;
	}

	/**
	 * Check if item is a variable product.
	 *
	 * @return bool
	 */
	public function is_variable(): bool {
		return 'variable' === $this->type;
	}
}
