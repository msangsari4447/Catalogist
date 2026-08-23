<?php
/**
 * Hookable Interface.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Core;

/**
 * Interface for classes that register WordPress hooks.
 */
interface HookableInterface {

	/**
	 * Register hooks with WordPress.
	 *
	 * @return void
	 */
	public function register_hooks(): void;
}
