<?php
/**
 * Product sorting helpers.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Product;

defined( 'ABSPATH' ) || exit;

/**
 * Normalizes and validates product sorting options.
 */
final class ProductSorter {

	/**
	 * Allowed orderby fields.
	 *
	 * @var array<string>
	 */
	private const ALLOWED_ORDERBY = array(
		'date',
		'title',
		'modified',
		'menu_order',
		'price',
		'popularity',
		'reating',
		'rand',
		'id',
	);

	/**
	 * Allowed order directions.
	 *
	 * @var array<string>
	 */
	private const ALLOWED_ORDER = array(
		'ASC',
		'DESC',
	);

	/**
	 * Normalize an orderby field.
	 *
	 * @param string $orderby Orderby field to normalize.
	 *
	 * @return string|null Normalized orderby or null if invalid.
	 */
	public function normalize_orderby( string $orderby ): ?string {
		$orderby = strtolower( sanitize_text_field( $orderby ) );

		return in_array( $orderby, self::ALLOWED_ORDERBY, true ) ? $orderby : null;
	}

	/**
	 * Normalize an order direction.
	 *
	 * @param string $order Order direction to normalize.
	 *
	 * @return string|null Normalized order or null if invalid.
	 */
	public function normalize_order( string $order ): ?string {
		$order = strtoupper( sanitize_text_field( $order ) );

		return in_array( $order, self::ALLOWED_ORDER, true ) ? $order : null;
	}

	/**
	 * Get default orderby field.
	 *
	 * @return string
	 */
	public function get_default_orderby(): string {
		return 'date';
	}

	/**
	 * Get default order direction.
	 *
	 * @return string
	 */
	public function get_default_order(): string {
		return 'DESC';
	}

	/**
	 * Get allowed orderby fields.
	 *
	 * @return array<string>
	 */
	public function get_allowed_orderby(): array {
		return self::ALLOWED_ORDERBY;
	}

	/**
	 * Get allowed order directions.
	 *
	 * @return array<string>
	 */
	public function get_allowed_order(): array {
		return self::ALLOWED_ORDER;
	}

	/**
	 * Check if an orderby field is valid.
	 *
	 * @param string $orderby Orderby field.
	 *
	 * @return bool
	 */
	public function is_valid_orderby( string $orderby ): bool {
		return null !== $this->normalize_orderby( $orderby );
	}

	/**
	 * Check if an order direction is valid.
	 *
	 * @param string $order Order direction.
	 *
	 * @return bool
	 */
	public function is_valid_order( string $order ): bool {
		return null !== $this->normalize_order( $order );
	}
}
