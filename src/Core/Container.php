<?php
/**
 * Simple service container for dependency injection.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Core;

use InvalidArgumentException;
use Closure;

/**
 * Container class for managing services and dependencies.
 */
final class Container {

	/**
	 * Registered services.
	 *
	 * @var array<string, mixed>
	 */
	private array $services = array();

	/**
	 * Service factories.
	 *
	 * @var array<string, Closure>
	 */
	private array $factories = array();

	/**
	 * Set a service in the container.
	 *
	 * @param string        $id      Service identifier.
	 * @param mixed|Closure $service Service value or factory.
	 *
	 * @return void
	 */
	public function set( string $id, $service ): void {
		$this->services[ $id ] = $service;
	}

	/**
	 * Get a service from the container.
	 *
	 * @param string $id Service identifier.
	 *
	 * @return mixed
	 *
	 * @throws InvalidArgumentException If service not found.
	 */
	public function get( string $id ) {
		if ( ! $this->has( $id ) ) {
			throw new InvalidArgumentException(
				sprintf( 'Service "%s" not found in container.', $id )
			);
		}

		$service = $this->services[ $id ];

		if ( $service instanceof Closure ) {
			return $service( $this );
		}

		return $service;
	}

	/**
	 * Check if a service exists in the container.
	 *
	 * @param string $id Service identifier.
	 *
	 * @return bool
	 */
	public function has( string $id ): bool {
		return array_key_exists( $id, $this->services );
	}

	/**
	 * Register a factory for lazy-loaded services.
	 *
	 * @param string  $id      Service identifier.
	 * @param Closure $factory Factory closure.
	 *
	 * @return void
	 */
	public function factory( string $id, Closure $factory ): void {
		$this->services[ $id ] = $factory;
	}

	/**
	 * Remove a service from the container.
	 *
	 * @param string $id Service identifier.
	 *
	 * @return void
	 */
	public function remove( string $id ): void {
		unset( $this->services[ $id ], $this->factories[ $id ] );
	}
}
