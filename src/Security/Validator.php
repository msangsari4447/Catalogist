<?php
/**
 * Validation helpers.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Security;

/**
 * Validates plugin input values.
 */
final class Validator {

	/**
	 * Check if a value is a non-empty string.
	 *
	 * @param mixed $value Value to validate.
	 *
	 * @return bool
	 */
	public function is_non_empty_string( $value ): bool {
		return is_string( $value ) && '' !== trim( $value );
	}

	/**
	 * Check if an ID is positive.
	 *
	 * @param mixed $value Value to validate.
	 *
	 * @return bool
	 */
	public function is_positive_id( $value ): bool {
		return is_numeric( $value ) && 0 < absint( $value );
	}

	/**
	 * Check if a post type slug is valid.
	 *
	 * @param string $slug Post type slug.
	 *
	 * @return bool
	 */
	public function is_valid_slug( string $slug ): bool {
		return (bool) preg_match( '/^[a-z0-9_\-]+$/', $slug );
	}
}
