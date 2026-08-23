<?php
/**
 * Plugin deactivation handler.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Core;

/**
 * Handles plugin deactivation tasks.
 */
final class Deactivator {

	/**
	 * Run deactivation tasks.
	 *
	 * @param Container $container Service container.
	 *
	 * @return void
	 */
	public static function deactivate( Container $container ): void {
		self::flush_rewrite_rules();
	}

	/**
	 * Flush rewrite rules.
	 *
	 * @return void
	 */
	private static function flush_rewrite_rules(): void {
		flush_rewrite_rules();
	}
}
