<?php
/**
 * Array helper utilities.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Support;

/**
 * Utility methods for working with arrays.
 */
final class ArrayHelper {

	/**
	 * Get an array value using dot notation.
	 *
	 * @param array<string, mixed> $array   Source array.
	 * @param string              $key     Dot-notated key.
	 * @param mixed               $default Default value.
	 *
	 * @return mixed
	 */
	public static function get( array $array, string $key, $default = null ) {
		if ( array_key_exists( $key, $array ) ) {
			return $array[ $key ];
		}

		foreach ( explode( '.', $key ) as $segment ) {
			if ( ! is_array( $array ) || ! array_key_exists( $segment, $array ) ) {
				return $default;
			}

			$array = $array[ $segment ];
		}

		return $array;
	}

	/**
	 * Determine if an array key exists using dot notation.
	 *
	 * @param array<string, mixed> $array Source array.
	 * @param string              $key   Dot-notated key.
	 *
	 * @return bool
	 */
	public static function has( array $array, string $key ): bool {
		return null !== self::get( $array, $key, null );
	}
}
