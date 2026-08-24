<?php
/**
 * Variation query result value object.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Variation;

defined( 'ABSPATH' ) || exit;

/**
 * Represents the result of a variation query.
 *
 * Independent from ProductQueryResult — no inheritance.
 */
final class VariationQueryResult {

	/**
	 * Parent product ID.
	 *
	 * @var int
	 */
	private int $parent_product_id;

	/**
	 * Variation data arrays.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $variations = array();

	/**
	 * Total variation count.
	 *
	 * @var int
	 */
	private int $total = 0;

	/**
	 * Variation mode used.
	 *
	 * @var VariationMode
	 */
	private VariationMode $mode;

	/**
	 * Constructor.
	 *
	 * @param int                            $parent_product_id Parent product ID.
	 * @param array<int, array<string, mixed>> $variations      Variation data arrays.
	 * @param int                            $total             Total variation count.
	 * @param VariationMode                  $mode              Variation mode.
	 */
	public function __construct(
		int $parent_product_id,
		array $variations,
		int $total,
		VariationMode $mode
	) {
		$this->parent_product_id = $parent_product_id;
		$this->variations        = $variations;
		$this->total             = max( 0, $total );
		$this->mode              = $mode;
	}

	/**
	 * Get parent product ID.
	 *
	 * @return int
	 */
	public function get_parent_product_id(): int {
		return $this->parent_product_id;
	}

	/**
	 * Get variation data arrays.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function get_variations(): array {
		return $this->variations;
	}

	/**
	 * Get variation IDs.
	 *
	 * @return array<int>
	 */
	public function get_variation_ids(): array {
		return array_keys( $this->variations );
	}

	/**
	 * Get total variation count.
	 *
	 * @return int
	 */
	public function get_total(): int {
		return $this->total;
	}

	/**
	 * Get the variation mode.
	 *
	 * @return VariationMode
	 */
	public function get_mode(): VariationMode {
		return $this->mode;
	}

	/**
	 * Check if there are any variations.
	 *
	 * @return bool
	 */
	public function has_variations(): bool {
		return ! empty( $this->variations );
	}

	/**
	 * Convert to array.
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return array(
			'parent_product_id' => $this->parent_product_id,
			'variations'        => $this->get_variation_ids(),
			'total'             => $this->total,
			'mode'              => $this->mode->value(),
		);
	}
}
