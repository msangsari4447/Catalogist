<?php
/**
 * Nonce helpers.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Security;

/**
 * Generates and verifies WordPress nonces.
 */
final class Nonce {

	public const SETTINGS_ACTION = 'catalogist_settings_action';
	public const SETTINGS_NAME   = 'catalogist_settings_nonce';

	/**
	 * Create a nonce for an action.
	 *
	 * @param string $action Nonce action.
	 *
	 * @return string
	 */
	public function create( string $action ): string {
		return wp_create_nonce( $action );
	}

	/**
	 * Verify a nonce value.
	 *
	 * @param string $nonce  Nonce value.
	 * @param string $action Nonce action.
	 *
	 * @return bool
	 */
	public function verify( string $nonce, string $action ): bool {
		return (bool) wp_verify_nonce( $nonce, $action );
	}

	/**
	 * Output a nonce field.
	 *
	 * @param string $action Nonce action.
	 * @param string $name   Nonce field name.
	 *
	 * @return void
	 */
	public function field( string $action, string $name ): void {
		wp_nonce_field( $action, $name );
	}
}
