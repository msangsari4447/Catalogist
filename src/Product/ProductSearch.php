<?php
/**
 * Product search helpers.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Product;

defined( 'ABSPATH' ) || exit;

/**
 * Normalizes and validates product search parameters.
 */
final class ProductSearch {

	/**
	 * Minimum search term length.
	 *
	 * @var int
	 */
	private const MIN_LENGTH = 2;

	/**
	 * Maximum search term length.
	 *
	 * @var int
	 */
	private const MAX_LENGTH = 200;

	/**
	 * Normalize a search term.
	 *
	 * @param string $search Search term to normalize.
	 *
	 * @return string Normalized search term.
	 */
	public function normalize( string $search ): string {
		$search = sanitize_text_field( $search );
		$search = trim( $search );

		return $search;
	}

	/**
	 * Validate a search term.
	 *
	 * @param string $search Search term to validate.
	 *
	 * @return bool True if valid, false otherwise.
	 */
	public function is_valid( string $search ): bool {
		$search = $this->normalize( $search );
		$length = strlen( $search );

		return $length >= self::MIN_LENGTH && $length <= self::MAX_LENGTH;
	}

	/**
	 * Get minimum search term length.
	 *
	 * @return int
	 */
	public function get_min_length(): int {
		return self::MIN_LENGTH;
	}

	/**
	 * Get maximum search term length.
	 *
	 * @return int
	 */
	public function get_max_length(): int {
		return self::MAX_LENGTH;
	}

	/**
	 * Prepare search term for query.
	 *
	 * @param string $search Search term.
	 *
	 * @return string Prepared search term or empty string if invalid.
	 */
	public function prepare( string $search ): string {
		$search = $this->normalize( $search );

		if ( ! $this->is_valid( $search ) ) {
			return '';
		}

		return $search;
	}
}
