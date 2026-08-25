<?php
/**
 * Catalogist mocks for tests.
 *
 * @package Catalogist
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/**
 * Mock WooCommerce Product.
 */
class Mock_WC_Product {
	private int $id;
	private string $type;
	private string $name;
	private string $sku;
	private float $price;
	private ?string $description;
	private array $categories = array();
	private array $attributes = array();
	private string $stock_status = 'instock';
	private ?string $image_url = null;
	private int $width = 300;
	private int $height = 300;
	private string $permalink;
	private ?int $parent_id = null;
	private ?Mock_WC_Product $parent = null;
	private array $variation_data = array();

	public function __construct(
		int $id,
		string $type = 'simple',
		string $name = 'Test Product',
		string $sku = 'TEST-SKU',
		float $price = 99.99,
		?string $description = null,
		array $categories = array(),
		array $attributes = array(),
		string $stock_status = 'instock',
		?string $image_url = null,
		string $permalink = '',
		?int $parent_id = null,
		?Mock_WC_Product $parent = null,
		array $variation_data = array()
	) {
		$this->id = $id;
		$this->type = $type;
		$this->name = $name;
		$this->sku = $sku;
		$this->price = $price;
		$this->description = $description;
		$this->categories = $categories;
		$this->attributes = $attributes;
		$this->stock_status = $stock_status;
		$this->image_url = $image_url;
		$this->permalink = $permalink;
		$this->parent_id = $parent_id;
		$this->parent = $parent;
		$this->variation_data = $variation_data;
	}

	public function get_id(): int {
		return $this->id;
	}

	public function get_type(): string {
		return $this->type;
	}

	public function get_name(): string {
		return $this->name;
	}

	public function get_sku(): string {
		return $this->sku;
	}

	public function get_price(): string {
		return $this->price;
	}

	public function get_regular_price(): string {
		return $this->price;
	}

	public function get_sale_price(): string {
		return '';
	}

	public function get_description(): string {
		return $this->description ?? '';
	}

	public function get_categories(): array {
		return $this->categories;
	}

	public function get_attributes(): array {
		return $this->attributes;
	}

	public function get_stock_status(): string {
		return $this->stock_status;
	}

	public function get_image( string $size = 'thumbnail' ): array {
		return array(
			'src' => $this->image_url ?? '',
			'alt' => $this->name,
			'width' => $this->width,
			'height' => $this->height,
		);
	}

	public function get_permalink(): string {
		return $this->permalink;
	}

	public function get_parent_id(): ?int {
		return $this->parent_id;
	}

	public function get_parent(): ?Mock_WC_Product {
		return $this->parent;
	}

	public function is_type( string $type ): bool {
		return $this->type === $type;
	}

	public function is_virtual(): bool {
		return false;
	}

	public function is_downloadable(): bool {
		return false;
	}

	public function get_variation_data(): array {
		return $this->variation_data;
	}
}

/**
 * Mock Catalog Repository.
 */
class Mock_CatalogRepository {
	private array $catalogs = array();

	public function __construct( array $catalogs = array() ) {
		$this->catalogs = $catalogs;
	}

	public function find( int $id ): ?\Catalogist\Catalog\Catalog {
		return $this->catalogs[ $id ] ?? null;
	}

	public function get_all(): array {
		return $this->catalogs;
	}
}

/**
 * Mock Catalog.
 */
class Mock_Catalog extends \Catalogist\Catalog\Catalog {
	public function __construct(
		int $id = 0,
		string $title = 'Test Catalog',
		array $catalog_items = array(),
		array $layout_settings = array(),
		array $print_settings = array()
	) {
		$this->id = $id;
		$this->title = $title;
		$this->catalog_items = $catalog_items;
		$this->layout_settings = $layout_settings;
		$this->print_settings = $print_settings;
	}

	public function get_id(): int {
		return $this->id;
	}

	public function get_title(): string {
		return $this->title;
	}

	public function get_catalog_items(): array {
		return $this->catalog_items;
	}

	public function get_layout_settings(): array {
		return $this->layout_settings;
	}

	public function get_print_settings(): array {
		return $this->print_settings;
	}
}
