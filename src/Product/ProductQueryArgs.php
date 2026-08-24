<?php
/**
 * Product query argument value object.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Product;

defined( 'ABSPATH' ) || exit;

/**
 * Represents normalized query arguments for product retrieval.
 */
final class ProductQueryArgs {

	/**
	 * Product IDs to include.
	 *
	 * @var array<int>
	 */
	private array $include = array();

	/**
	 * Product IDs to exclude.
	 *
	 * @var array<int>
	 */
	private array $exclude = array();

	/**
	 * Category IDs or slugs.
	 *
	 * @var array<int|string>
	 */
	private array $categories = array();

	/**
	 * Tag IDs or slugs.
	 *
	 * @var array<int|string>
	 */
	private array $tags = array();

	/**
	 * Search term.
	 *
	 * @var string
	 */
	private string $search = '';

	/**
	 * Product status.
	 *
	 * @var array<string>
	 */
	private array $status = array( 'publish' );

	/**
	 * Stock status.
	 *
	 * @var array<string>
	 */
	private array $stock_status = array();

	/**
	 * Product visibility.
	 *
	 * @var array<string>
	 */
	private array $visibility = array();

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
	 * Orderby field.
	 *
	 * @var string
	 */
	private string $orderby = 'date';

	/**
	 * Order direction.
	 *
	 * @var string
	 */
	private string $order = 'DESC';

	/**
	 * Return format: 'objects' or 'ids'.
	 *
	 * @var string
	 */
	private string $return = 'objects';

	/**
	 * Minimum search term length.
	 *
	 * @var int
	 */
	private const MIN_SEARCH_LENGTH = 2;

	/**
	 * Maximum search term length.
	 *
	 * @var int
	 */
	private const MAX_SEARCH_LENGTH = 200;

	/**
	 * Get include IDs.
	 *
	 * @return array<int>
	 */
	public function get_include(): array {
		return $this->include;
	}

	/**
	 * Set include IDs.
	 *
	 * @param array<int> $ids Product IDs.
	 *
	 * @return void
	 */
	public function set_include( array $ids ): void {
		$this->include = array_filter( array_map( 'absint', $ids ) );
	}

	/**
	 * Get exclude IDs.
	 *
	 * @return array<int>
	 */
	public function get_exclude(): array {
		return $this->exclude;
	}

	/**
	 * Set exclude IDs.
	 *
	 * @param array<int> $ids Product IDs.
	 *
	 * @return void
	 */
	public function set_exclude( array $ids ): void {
		$this->exclude = array_filter( array_map( 'absint', $ids ) );
	}

	/**
	 * Get categories.
	 *
	 * @return array<int|string>
	 */
	public function get_categories(): array {
		return $this->categories;
	}

	/**
	 * Set categories with validation.
	 *
	 * @param array<int|string> $categories Category IDs or slugs.
	 *
	 * @return void
	 */
	public function set_categories( array $categories ): void {
		$normalized = array();
		foreach ( $categories as $category ) {
			$filters  = new ProductFilters();
			$result   = $filters->normalize_category( $category );
			if ( null !== $result ) {
				$normalized[] = $result;
			}
		}
		$this->categories = $normalized;
	}

	/**
	 * Get tags.
	 *
	 * @return array<int|string>
	 */
	public function get_tags(): array {
		return $this->tags;
	}

	/**
	 * Set tags with validation.
	 *
	 * @param array<int|string> $tags Tag IDs or slugs.
	 *
	 * @return void
	 */
	public function set_tags( array $tags ): void {
		$normalized = array();
		foreach ( $tags as $tag ) {
			$filters  = new ProductFilters();
			$result   = $filters->normalize_tag( $tag );
			if ( null !== $result ) {
				$normalized[] = $result;
			}
		}
		$this->tags = $normalized;
	}

	/**
	 * Get search term.
	 *
	 * @return string
	 */
	public function get_search(): string {
		return $this->search;
	}

	/**
	 * Set search term with length validation.
	 *
	 * @param string $search Search term.
	 *
	 * @return void
	 */
	public function set_search( string $search ): void {
		$search      = sanitize_text_field( $search );
		$search      = trim( $search );
		$length      = strlen( $search );

		if ( $length >= self::MIN_SEARCH_LENGTH && $length <= self::MAX_SEARCH_LENGTH ) {
			$this->search = $search;
		} else {
			$this->search = '';
		}
	}

	/**
	 * Get product status.
	 *
	 * @return array<string>
	 */
	public function get_status(): array {
		return $this->status;
	}

	/**
	 * Set product status.
	 *
	 * @param array<string> $status Status values.
	 *
	 * @return void
	 */
	public function set_status( array $status ): void {
		$allowed       = array( 'publish', 'pending', 'draft', 'private', 'trash' );
		$this->status  = array_intersect( $status, $allowed );

		if ( empty( $this->status ) ) {
			$this->status = array( 'publish' );
		}
	}

	/**
	 * Get stock status.
	 *
	 * @return array<string>
	 */
	public function get_stock_status(): array {
		return $this->stock_status;
	}

	/**
	 * Set stock status.
	 *
	 * @param array<string> $status Stock status values.
	 *
	 * @return void
	 */
	public function set_stock_status( array $status ): void {
		$allowed = array( 'instock', 'outofstock', 'onbackorder' );

		$this->stock_status = array_intersect( $status, $allowed );
	}

	/**
	 * Get visibility.
	 *
	 * @return array<string>
	 */
	public function get_visibility(): array {
		return $this->visibility;
	}

	/**
	 * Set visibility.
	 *
	 * @param array<string> $visibility Visibility values.
	 *
	 * @return void
	 */
	public function set_visibility( array $visibility ): void {
		$allowed = array( 'visible', 'catalog', 'search', 'hidden' );

		$this->visibility = array_intersect( $visibility, $allowed );
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
	 * Set current page.
	 *
	 * @param int $page Page number.
	 *
	 * @return void
	 */
	public function set_page( int $page ): void {
		$this->page = max( 1, $page );
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
	 * Set items per page.
	 *
	 * @param int $per_page Items per page.
	 *
	 * @return void
	 */
	public function set_per_page( int $per_page ): void {
		$this->per_page = max( 1, min( 1000, $per_page ) );
	}

	/**
	 * Get orderby field.
	 *
	 * @return string
	 */
	public function get_orderby(): string {
		return $this->orderby;
	}

	/**
	 * Set orderby field.
	 *
	 * @param string $orderby Orderby field.
	 *
	 * @return void
	 */
	public function set_orderby( string $orderby ): void {
		$allowed = array(
			'date',
			'title',
			'modified',
			'menu_order',
			'price',
			'popularity',
			'rating',
			'rand',
			'id',
		);

		if ( in_array( $orderby, $allowed, true ) ) {
			$this->orderby = $orderby;
		}
	}

	/**
	 * Get order direction.
	 *
	 * @return string
	 */
	public function get_order(): string {
		return $this->order;
	}

	/**
	 * Set order direction.
	 *
	 * @param string $order Order direction.
	 *
	 * @return void
	 */
	public function set_order( string $order ): void {
		$this->order = strtoupper( $order ) === 'ASC' ? 'ASC' : 'DESC';
	}

	/**
	 * Get return format.
	 *
	 * @return string
	 */
	public function get_return(): string {
		return $this->return;
	}

	/**
	 * Set return format.
	 *
	 * @param string $return Return format: 'objects' or 'ids'.
	 *
	 * @return void
	 */
	public function set_return( string $return ): void {
		$this->return = 'ids' === $return ? 'ids' : 'objects';
	}

	/**
	 * Create from an array of arguments.
	 *
	 * @param array<string, mixed> $args Query arguments.
	 *
	 * @return self
	 */
	public static function from_array( array $args ): self {
		$instance = new self();

		if ( isset( $args['include'] ) && is_array( $args['include'] ) ) {
			$instance->set_include( $args['include'] );
		}

		if ( isset( $args['exclude'] ) && is_array( $args['exclude'] ) ) {
			$instance->set_exclude( $args['exclude'] );
		}

		if ( isset( $args['categories'] ) && is_array( $args['categories'] ) ) {
			$instance->set_categories( $args['categories'] );
		}

		if ( isset( $args['tags'] ) && is_array( $args['tags'] ) ) {
			$instance->set_tags( $args['tags'] );
		}

		if ( isset( $args['search'] ) ) {
			$instance->set_search( (string) $args['search'] );
		}

		if ( isset( $args['status'] ) && is_array( $args['status'] ) ) {
			$instance->set_status( $args['status'] );
		}

		if ( isset( $args['stock_status'] ) && is_array( $args['stock_status'] ) ) {
			$instance->set_stock_status( $args['stock_status'] );
		}

		if ( isset( $args['visibility'] ) && is_array( $args['visibility'] ) ) {
			$instance->set_visibility( $args['visibility'] );
		}

		if ( isset( $args['page'] ) ) {
			$instance->set_page( (int) $args['page'] );
		}

		if ( isset( $args['per_page'] ) ) {
			$instance->set_per_page( (int) $args['per_page'] );
		}

		if ( isset( $args['orderby'] ) ) {
			$instance->set_orderby( (string) $args['orderby'] );
		}

		if ( isset( $args['order'] ) ) {
			$instance->set_order( (string) $args['order'] );
		}

		if ( isset( $args['return'] ) ) {
			$instance->set_return( (string) $args['return'] );
		}

		return $instance;
	}

	/**
	 * Convert to array.
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return array(
			'include'      => $this->include,
			'exclude'      => $this->exclude,
			'categories'   => $this->categories,
			'tags'         => $this->tags,
			'search'       => $this->search,
			'status'       => $this->status,
			'stock_status' => $this->stock_status,
			'visibility'   => $this->visibility,
			'page'         => $this->page,
			'per_page'     => $this->per_page,
			'orderby'      => $this->orderby,
			'order'        => $this->order,
			'return'       => $this->return,
		);
	}
}
