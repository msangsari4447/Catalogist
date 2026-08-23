<?php
/**
 * Service Provider Interface.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Core;

/**
 * Interface for service providers.
 */
interface ServiceProviderInterface {

	/**
	 * Register services in the container.
	 *
	 * @param Container $container Service container.
	 *
	 * @return void
	 */
	public function register( Container $container ): void;

	/**
	 * Boot the service provider.
	 *
	 * @param Container $container Service container.
	 *
	 * @return void
	 */
	public function boot( Container $container ): void;
}
