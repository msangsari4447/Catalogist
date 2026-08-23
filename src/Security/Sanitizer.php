<?php
/**
 * Sanitization helpers.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Security;

/**
 * Sanitizes plugin input values.
 */
final class Sanitizer {

	/**
	 * Sanitize text input.
	 *
	 * @param mixed $value Input value.
	 *
	 * @return string
	 */
	public function text( $value ): string {
		return sanitize_text_field( wp_unslash( (string) $value ) );
	}

	/**
	 * Sanitize a boolean-like input.
	 *
	 * @param mixed $value Input value.
	 *
	 * @return bool
	 */
	public function boolean( $value ): bool {
		return filter_var( $value, FILTER_VALIDATE_BOOLEAN );
	}

	/**
	 * Sanitize a positive integer input.
	 *
	 * @param mixed $value Input value.
	 *
	 * @return int
	 */
	public function absint( $value ): int {
		return absint( $value );
	}

	/**
	 * Sanitize settings array.
	 *
	 * @param array<string, mixed> $settings Settings input.
	 *
	 * @return array<string, mixed>
	 */
	public function settings( array $settings ): array {
		return array(
			'post_type_slug' => isset( $settings['post_type_slug'] ) ? sanitize_title( wp_unslash( (string) $settings['post_type_slug'] ) ) : 'catalogs',
			'per_page'       => isset( $settings['per_page'] ) ? max( 1, min( 100, absint( $settings['per_page'] ) ) ) : 20,
			'enable_print'   => isset( $settings['enable_print'] ) ? $this->boolean( $settings['enable_print'] ) : false,
		);
	}
}
