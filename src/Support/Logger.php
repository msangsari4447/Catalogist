<?php
/**
 * Logging utility.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Centralized logging for the plugin.
 *
 * Uses PHP error_log with consistent prefix and supports
 * different log levels. Can be extended for external logging services.
 */
final class Logger {

	/**
	 * Log prefix.
	 *
	 * @var string
	 */
	private const PREFIX = 'Catalogist';

	/**
	 * Whether debug logging is enabled.
	 *
	 * @var bool
	 */
	private static $debug = false;

	/**
	 * Log a debug message.
	 *
	 * @param string $message Log message.
	 * @param array<string, mixed> $context Optional context data.
	 *
	 * @return void
	 */
	public static function debug( string $message, array $context = array() ): void {
		if ( ! self::$debug ) {
			return;
		}
		self::log( 'DEBUG', $message, $context );
	}

	/**
	 * Log an info message.
	 *
	 * @param string $message Log message.
	 * @param array<string, mixed> $context Optional context data.
	 *
	 * @return void
	 */
	public static function info( string $message, array $context = array() ): void {
		self::log( 'INFO', $message, $context );
	}

	/**
	 * Log a notice message.
	 *
	 * @param string $message Log message.
	 * @param array<string, mixed> $context Optional context data.
	 *
	 * @return void
	 */
	public static function notice( string $message, array $context = array() ): void {
		self::log( 'NOTICE', $message, $context );
	}

	/**
	 * Log a warning message.
	 *
	 * @param string $message Log message.
	 * @param array<string, mixed> $context Optional context data.
	 *
	 * @return void
	 */
	public static function warning( string $message, array $context = array() ): void {
		self::log( 'WARNING', $message, $context );
	}

	/**
	 * Log an error message.
	 *
	 * @param string $message Log message.
	 * @param array<string, mixed> $context Optional context data.
	 *
	 * @return void
	 */
	public static function error( string $message, array $context = array() ): void {
		self::log( 'ERROR', $message, $context );
	}

	/**
	 * Enable or disable debug logging.
	 *
	 * @param bool $enabled Whether to enable debug logging.
	 *
	 * @return void
	 */
	public static function set_debug( bool $enabled ): void {
		self::$debug = $enabled;
	}

	/**
	 * Check if debug logging is enabled.
	 *
	 * @return bool
	 */
	public static function is_debug(): bool {
		return self::$debug;
	}

	/**
	 * Internal log method.
	 *
	 * @param string $level Log level.
	 * @param string $message Log message.
	 * @param array<string, mixed> $context Optional context data.
	 *
	 * @return void
	 */
	private static function log( string $level, string $message, array $context ): void {
		$prefix = sprintf( '[%s] [%s] ', self::PREFIX, $level );

		$full_message = $prefix . $message;

		if ( ! empty( $context ) ) {
			$full_message .= ' | Context: ' . wp_json_encode( $context );
		}

		error_log( $full_message );
	}
}