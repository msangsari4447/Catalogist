<?php
/**
 * PHPUnit bootstrap file.
 *
 * @package Catalogist
 */

declare(strict_types=1);

// Define constants for testing.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/tmp/wordpress/' );
}

if ( ! defined( 'WP_CONTENT_DIR' ) ) {
	define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
}

if ( ! defined( 'CATALOGIST_FILE' ) ) {
	define( 'CATALOGIST_FILE', __DIR__ . '/../catalogist.php' );
}

if ( ! defined( 'CATALOGIST_PLUGIN_DIR' ) ) {
	define( 'CATALOGIST_PLUGIN_DIR', __DIR__ . '/../' );
}

if ( ! defined( 'CATALOGIST_PLUGIN_URL' ) ) {
	define( 'CATALOGIST_PLUGIN_URL', 'http://example.com/wp-content/plugins/catalogist/' );
}

if ( ! defined( 'CATALOGIST_VERSION' ) ) {
	define( 'CATALOGIST_VERSION', '0.1.0' );
}

// Mock WordPress functions for unit tests.
if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $value ) {
		return strip_tags( stripslashes( $value ) );
	}
}

if ( ! function_exists( 'wp_unslash' ) ) {
	function wp_unslash( $value ) {
		return stripslashes( $value );
	}
}

if ( ! function_exists( 'sanitize_title' ) ) {
	function sanitize_title( $value ) {
		return strtolower( preg_replace( '/[^a-z0-9]+/', '-', $value ) );
	}
}

if ( ! function_exists( 'absint' ) ) {
	function absint( $value ) {
		return abs( (int) $value );
	}
}

if ( ! function_exists( 'esc_html' ) ) {
	function esc_html( $text ) {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_attr' ) ) {
	function esc_attr( $text ) {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_url' ) ) {
	function esc_url( $url ) {
		return filter_var( $url, FILTER_SANITIZE_URL ) ?: '';
	}
}

if ( ! function_exists( 'wp_kses' ) ) {
	function wp_kses( $content, $allowed ) {
		return $content;
	}
}

if ( ! function_exists( 'wp_create_nonce' ) ) {
	function wp_create_nonce( $action = -1 ) {
		return substr( md5( $action ), 0, 10 );
	}
}

if ( ! function_exists( 'wp_verify_nonce' ) ) {
	function wp_verify_nonce( $nonce, $action = -1 ) {
		return $nonce === substr( md5( $action ), 0, 10 );
	}
}

if ( ! function_exists( 'wp_nonce_field' ) ) {
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
	function current_user_can( $capability ) {
		return true;
	}
}

if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = 'default' ) {
		return $text;
	}
}

if ( ! function_exists( 'esc_html__' ) ) {
	function esc_html__( $text, $domain = 'default' ) {
		return esc_html( __( $text, $domain ) );
	}
}

if ( ! function_exists( 'sprintf' ) ) {
	// Native PHP function, no need to mock.
}

// Load Composer autoloader.
require_once __DIR__ . '/../vendor/autoload.php';
