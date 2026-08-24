<?php
/**
 * Product query result value object.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Product;

defined( 'ABSPATH' ) || exit;

/**
 * Represents the result of a product query.
 */
final class ProductQueryResult {

	/**
	 * Product items or IDs.
	 *
	 * @var array<\WC_Product|array<string, mixed>|int>
	 */
	private array $products = array();

	/**
	 * Total number of products matching the query.
	 *
	 * @var int
	 */
	private int $total = 0;

	/**
	 * Current page.
	 *
	 * @var int
	 */
	private int $page = 1;

	/**
	 * Items per page.
	 *
	 * @var int
	 */
	private int $per_page = 20;

	/**
	 * Maximum number of pages.
	 *
	 * @var int
	 */
	private int $max_pages = 1;

	/**
	 * Original query arguments.
	 *
	 * @var ProductQueryArgs|null
	 */
	private ?ProductQueryArgs $query_args = null;

	/**
	 * Constructor.
	 *
	 * @param array<\WC_Product|array<string, mixed>|int> $products   Product items, data arrays, or IDs.
	 * @param int                                         $total      Total matching products.
	 * @param int                                         $page       Current page.
	 * @param int                                         $per_page   Items per page.
	 * @param ProductQueryArgs|null                       $query_args Original query args.
	 */
	public function __construct(
		array $products,
		int $total,
		int $page = 1,
		int $per_page = 20,
		?ProductQueryArgs $query_args = null
	) {
		$this->products   = $products;
		$this->total      = max( 0, $total );
		$this->page       = max( 1, $page );
		$this->per_page   = max( 1, $per_page );
		$this->max_pages  = $per_page > 0 ? (int) ceil( $this->total / $per_page ) : 1;
		$this->query_args = $query_args;
	}

	/**
	 * Get product items, data arrays, or IDs.
	 *
	 * @return array<\WC_Product|array<string, mixed>|int>
	 */
	public function get_products(): array {
		return $this->products;
	}

	/**
	 * Get product IDs only.
	 *
	 * @return array<int>
	 */
	public function get_ids(): array {
		$ids = array();

		foreach ( $this->products as $product ) {
			if ( is_numeric( $product ) ) {
				$ids[] = (int) $product;
			} elseif ( is_array( $product ) && isset( $product['id'] ) ) {
				$ids[] = (int) $product['id'];
			}
		}

		return $ids;
	}

	/**
	 * Get total matching products.
	 *
	 * @return int
	 */
	public function get_total(): int {
		return $this->total;
	}

	/**
	 * Get number of products on current page.
	 *
	 * @return int
	 */
	public function get_total_on_page(): int {
		return count( $this->products );
	}

	/**
	 * Get current page.
	 *
	 * @return int
	 */
	public function get_page(): int {
		return $this->page;
	}

	/**
	 * Get items per page.
	 *
	 * @return int
	 */
	public function get_per_page(): int {
		return $this->per_page;
	}

	/**
	 * Get maximum pages.
	 *
	 * @return int
	 */
	public function get_max_pages(): int {
		return $this->max_pages;
	}

	/**
	 * Get original query arguments.
	 *
	 * @return ProductQueryArgs|null
	 */
	public function get_query_args(): ?ProductQueryArgs {
		return $this->query_args;
	}

	/**
	 * Check if there are any products.
	 *
	 * @return bool
	 */
	public function has_products(): bool {
		return ! empty( $this->products );
	}

	/**
	 * Check if there is a next page.
	 *
	 * @return bool
	 */
	public function has_next_page(): bool {
		return $this->page < $this->max_pages;
	}

	/**
	 * Check if there is a previous page.
	 *
	 * @return bool
	 */
	public function has_previous_page(): bool {
		return $this->page > 1;
	}

	/**
	 * Convert to array.
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return array(
			'products'      => $this->get_ids(),
			'total'         => $this->total,
			'total_on_page' => $this->get_total_on_page(),
			'page'          => $this->page,
			'per_page'      => $this->per_page,
			'max_pages'     => $this->max_pages,
		);
	}
}
