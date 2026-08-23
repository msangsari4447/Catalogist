<?php
/**
 * Security service provider.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Security;

use Catalogist\Core\Container;
use Catalogist\Core\ServiceProviderInterface;

/**
 * Registers security services.
 */
final class SecurityServiceProvider implements ServiceProviderInterface {

	/**
	 * Register services in the container.
	 *
	 * @param Container $container Service container.
	 *
	 * @return void
	 */
	public function register( Container $container ): void {
		$container->set( Nonce::class, new Nonce() );
		$container->set( Sanitizer::class, new Sanitizer() );
		$container->set( Validator::class, new Validator() );
	}

	/**
	 * Boot the service provider.
	 *
	 * @param Container $container Service container.
	 *
	 * @return void
	 */
	public function boot( Container $container ): void {}
}
