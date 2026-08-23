<?php
/**
 * Module Interface.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Core;

/**
 * Interface for plugin modules.
 */
interface ModuleInterface {

	/**
	 * Initialize the module.
	 *
	 * @return void
	 */
	public function init(): void;
}
