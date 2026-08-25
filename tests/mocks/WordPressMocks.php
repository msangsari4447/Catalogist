<?php
/**
 * WordPress function mocks for unit tests.
 *
 * @package Catalogist
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'sanitize_text_field' ) ) {
	/**
	 * @param string $value
	 * @return string
	 */
	function sanitize_text_field( $value ) {
		return strip_tags( stripslashes( $value ) );
	}
}

if ( ! function_exists( 'wp_unslash' ) ) {
	/**
	 * @param mixed $value
	 * @return mixed
	 */
	function wp_unslash( $value ) {
		return stripslashes( $value );
	}
}

if ( ! function_exists( 'sanitize_title' ) ) {
	/**
	 * @param string $value
	 * @return string
	 */
	function sanitize_title( $value ) {
		return strtolower( preg_replace( '/[^a-z0-9]+/', '-', $value ) );
	}
}

if ( ! function_exists( 'absint' ) ) {
	/**
	 * @param mixed $value
	 * @return int
	 */
	function absint( $value ) {
		return abs( (int) $value );
	}
}

if ( ! function_exists( 'esc_html' ) ) {
	/**
	 * @param string $text
	 * @return string
	 */
	function esc_html( $text ) {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_attr' ) ) {
	/**
	 * @param string $text
	 * @return string
	 */
	function esc_attr( $text ) {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_url' ) ) {
	/**
	 * @param string $url
	 * @return string
	 */
	function esc_url( $url ) {
		return filter_var( $url, FILTER_SANITIZE_URL ) ?: '';
	}
}

if ( ! function_exists( 'wp_kses' ) ) {
	/**
	 * @param string $content
	 * @param array $allowed
	 * @return string
	 */
	function wp_kses( $content, $allowed ) {
		return $content;
	}
}

if ( ! function_exists( 'wp_create_nonce' ) ) {
	/**
	 * @param string|int $action
	 * @return string
	 */
	function wp_create_nonce( $action = -1 ) {
		return substr( md5( $action ), 0, 10 );
	}
}

if ( ! function_exists( 'wp_verify_nonce' ) ) {
	/**
	 * @param string $nonce
	 * @param string|int $action
	 * @return bool|int
	 */
	function wp_verify_nonce( $nonce, $action = -1 ) {
		return $nonce === substr( md5( $action ), 0, 10 );
	}
}

if ( ! function_exists( 'wp_nonce_field' ) ) {
	/**
	 * @param string|int $action
	 * @param string $name
	 * @param bool $referer
	 * @param bool $echo
	 * @return string
	 */
	function wp_nonce_field( $action = -1, $name = '_wpnonce', $referer = true, $echo = true ) {
		$field = sprintf(
			'<input type="hidden" id="%s" name="%s" value="%s" />',
			esc_attr( $name ),
			esc_attr( $name ),
			esc_attr( wp_create_nonce( $action ) )
		);
		if ( $echo ) {
			echo $field;
		}
		return $field;
	}
}

if ( ! function_exists( 'current_user_can' ) ) {
	/**
	 * @param string $capability
	 * @return bool
	 */
	function current_user_can( $capability ) {
		return true;
	}
}

if ( ! function_exists( '__' ) ) {
	/**
	 * @param string $text
	 * @param string $domain
	 * @return string
	 */
	function __( $text, $domain = 'default' ) {
		return $text;
	}
}

if ( ! function_exists( 'esc_html__' ) ) {
	/**
	 * @param string $text
	 * @param string $domain
	 * @return string
	 */
	function esc_html__( $text, $domain = 'default' ) {
		return esc_html( __( $text, $domain ) );
	}
}

// Mock WordPress functions for template tests.
if ( ! function_exists( 'get_post' ) ) {
	/**
	 * @param int|object|string|null $post
	 * @return object|null
	 */
	function get_post( $post = null ) {
		return null;
	}
}

if ( ! function_exists( 'get_template_directory' ) ) {
	/**
	 * @return string
	 */
	function get_template_directory(): string {
		return __DIR__ . '/../../';
	}
}

if ( ! function_exists( 'sanitize_file_name' ) ) {
	/**
	 * @param string $filename
	 * @return string
	 */
	function sanitize_file_name( string $filename ): string {
		return preg_replace( '/[^\w\-\._]+/', '-', $filename ) ?? $filename;
	}
}

if ( ! function_exists( 'ob_start' ) ) {
	/**
	 * @param callable|string|null $callback
	 * @param int $chunk_size
	 * @param bool $erase
	 * @return bool
	 */
	function ob_start( $callback = null, int $chunk_size = 0, bool $erase = true ): bool {
		return \ob_start( $callback, $chunk_size, $erase );
	}
}

if ( ! function_exists( 'ob_get_clean' ) ) {
	/**
	 * @return string|null
	 */
	function ob_get_clean(): ?string {
		return \ob_get_clean();
	}
}

if ( ! function_exists( 'apply_filters' ) ) {
	/**
	 * @param string $tag
	 * @param mixed $value
	 * @return mixed
	 */
	function apply_filters( string $tag, $value ) {
		return $value;
	}
}

if ( ! function_exists( 'do_action' ) ) {
	/**
	 * @param string $tag
	 * @return void
	 */
	function do_action( string $tag ): void {
		// No-op.
	}
}

if ( ! function_exists( 'add_shortcode' ) ) {
	/**
	 * @param string $tag
	 * @param callable $callback
	 * @return void
	 */
	function add_shortcode( string $tag, callable $callback ): void {
		// No-op for tests.
	}
}

if ( ! function_exists( 'shortcode_atts' ) ) {
	/**
	 * @param array<string, mixed> $pairs
	 * @param array<string, mixed> $atts
	 * @param string $shortcode
	 * @return array<string, mixed>
	 */
	function shortcode_atts( array $pairs, array $atts, string $shortcode = '' ): array {
		return array_merge( $pairs, $atts );
	}
}

if ( ! function_exists( 'wc_price' ) ) {
	/**
	 * @param string $price
	 * @return string
	 */
	function wc_price( string $price ): string {
		return '$' . $price;
	}
}

if ( ! function_exists( 'wp_kses_post' ) ) {
	/**
	 * @param string $content
	 * @return string
	 */
	function wp_kses_post( string $content ): string {
		return $content;
	}
}

if ( ! function_exists( 'get_permalink' ) ) {
	/**
	 * @param int|null $id
	 * @return string
	 */
	function get_permalink( $id = null ): string {
		return '';
	}
}
