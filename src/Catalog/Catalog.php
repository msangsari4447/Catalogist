<?php
/**
 * Catalog model.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Catalog;

/**
 * Represents a catalog entity.
 */
final class Catalog {

	/**
	 * Catalog ID.
	 *
	 * @var int
	 */
	private int $id = 0;

	/**
	 * Catalog title.
	 *
	 * @var string
	 */
	private string $title = '';

	/**
	 * Catalog slug.
	 *
	 * @var string
	 */
	private string $slug = '';

	/**
	 * Catalog status.
	 *
	 * @var string
	 */
	private string $status = 'draft';

	/**
	 * Product query parameters.
	 *
	 * @var array<string, mixed>
	 */
	private array $product_query = array();

	/**
	 * Filter settings.
	 *
	 * @var array<string, mixed>
	 */
	private array $filters = array();

	/**
	 * Selected product IDs.
	 *
	 * @var array<int>
	 */
	private array $selected_products = array();

	/**
	 * Template ID.
	 *
	 * @var int
	 */
	private int $template_id = 0;

	/**
	 * Layout settings.
	 *
	 * @var array<string, mixed>
	 */
	private array $layout_settings = array();

	/**
	 * Print settings.
	 *
	 * @var array<string, mixed>
	 */
	private array $print_settings = array();

	/**
	 * Output settings.
	 *
	 * @var array<string, mixed>
	 */
	private array $output_settings = array();

	/**
	 * Created timestamp.
	 *
	 * @var string
	 */
	private string $created_at = '';

	/**
	 * Updated timestamp.
	 *
	 * @var string
	 */
	private string $updated_at = '';

	/**
	 * Get the catalog ID.
	 *
	 * @return int
	 */
	public function get_id(): int {
		return $this->id;
	}

	/**
	 * Set the catalog ID.
	 *
	 * @param int $id ID.
	 *
	 * @return void
	 */
	public function set_id( int $id ): void {
		$this->id = $id;
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
	 * Set the title.
	 *
	 * @param string $title Title.
	 *
	 * @return void
	 */
	public function set_title( string $title ): void {
		$this->title = $title;
	}

	/**
	 * Get the slug.
	 *
	 * @return string
	 */
	public function get_slug(): string {
		return $this->slug;
	}

	/**
	 * Set the slug.
	 *
	 * @param string $slug Slug.
	 *
	 * @return void
	 */
	public function set_slug( string $slug ): void {
		$this->slug = $slug;
	}

	/**
	 * Get the status.
	 *
	 * @return string
	 */
	public function get_status(): string {
		return $this->status;
	}

	/**
	 * Set the status.
	 *
	 * @param string $status Status.
	 *
	 * @return void
	 */
	public function set_status( string $status ): void {
		$this->status = $status;
	}

	/**
	 * Get the product query.
	 *
	 * @return array<string, mixed>
	 */
	public function get_product_query(): array {
		return $this->product_query;
	}

	/**
	 * Set the product query.
	 *
	 * @param array<string, mixed> $query Query parameters.
	 *
	 * @return void
	 */
	public function set_product_query( array $query ): void {
		$this->product_query = $query;
	}

	/**
	 * Get the filters.
	 *
	 * @return array<string, mixed>
	 */
	public function get_filters(): array {
		return $this->filters;
	}

	/**
	 * Set the filters.
	 *
	 * @param array<string, mixed> $filters Filter settings.
	 *
	 * @return void
	 */
	public function set_filters( array $filters ): void {
		$this->filters = $filters;
	}

	/**
	 * Get selected product IDs.
	 *
	 * @return array<int>
	 */
	public function get_selected_products(): array {
		return $this->selected_products;
	}

	/**
	 * Set selected product IDs.
	 *
	 * @param array<int> $ids Product IDs.
	 *
	 * @return void
	 */
	public function set_selected_products( array $ids ): void {
		$this->selected_products = $ids;
	}

	/**
	 * Get the template ID.
	 *
	 * @return int
	 */
	public function get_template_id(): int {
		return $this->template_id;
	}

	/**
	 * Set the template ID.
	 *
	 * @param int $id Template ID.
	 *
	 * @return void
	 */
	public function set_template_id( int $id ): void {
		$this->template_id = $id;
	}

	/**
	 * Get layout settings.
	 *
	 * @return array<string, mixed>
	 */
	public function get_layout_settings(): array {
		return $this->layout_settings;
	}

	/**
	 * Set layout settings.
	 *
	 * @param array<string, mixed> $settings Settings.
	 *
	 * @return void
	 */
	public function set_layout_settings( array $settings ): void {
		$this->layout_settings = $settings;
	}

	/**
	 * Get print settings.
	 *
	 * @return array<string, mixed>
	 */
	public function get_print_settings(): array {
		return $this->print_settings;
	}

	/**
	 * Set print settings.
	 *
	 * @param array<string, mixed> $settings Settings.
	 *
	 * @return void
	 */
	public function set_print_settings( array $settings ): void {
		$this->print_settings = $settings;
	}

	/**
	 * Get output settings.
	 *
	 * @return array<string, mixed>
	 */
	public function get_output_settings(): array {
		return $this->output_settings;
	}

	/**
	 * Set output settings.
	 *
	 * @param array<string, mixed> $settings Settings.
	 *
	 * @return void
	 */
	public function set_output_settings( array $settings ): void {
		$this->output_settings = $settings;
	}

	/**
	 * Get created timestamp.
	 *
	 * @return string
	 */
	public function get_created_at(): string {
		return $this->created_at;
	}

	/**
	 * Set created timestamp.
	 *
	 * @param string $timestamp Timestamp.
	 *
	 * @return void
	 */
	public function set_created_at( string $timestamp ): void {
		$this->created_at = $timestamp;
	}

	/**
	 * Get updated timestamp.
	 *
	 * @return string
	 */
	public function get_updated_at(): string {
		return $this->updated_at;
	}

	/**
	 * Set updated timestamp.
	 *
	 * @param string $timestamp Timestamp.
	 *
	 * @return void
	 */
	public function set_updated_at( string $timestamp ): void {
		$this->updated_at = $timestamp;
	}

	/**
	 * Convert to array.
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return array(
			'id'               => $this->id,
			'title'            => $this->title,
			'slug'             => $this->slug,
			'status'           => $this->status,
			'product_query'    => $this->product_query,
			'filters'          => $this->filters,
			'selected_products' => $this->selected_products,
			'template_id'      => $this->template_id,
			'layout_settings'  => $this->layout_settings,
			'print_settings'   => $this->print_settings,
			'output_settings'  => $this->output_settings,
			'created_at'       => $this->created_at,
			'updated_at'       => $this->updated_at,
		);
	}
}
