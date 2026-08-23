<?php
/**
 * Capability definitions.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Security;

/**
 * Catalogist capability constants and helpers.
 */
final class Capability {

	public const MANAGE_CATALOGS  = 'catalogist_manage_catalogs';
	public const EDIT_CATALOGS    = 'catalogist_edit_catalogs';
	public const DELETE_CATALOGS  = 'catalogist_delete_catalogs';
	public const MANAGE_TEMPLATES = 'catalogist_manage_templates';
	public const MANAGE_SETTINGS  = 'catalogist_manage_settings';

	/**
	 * Determine whether the current user can manage plugin settings.
	 *
	 * @return bool
	 */
	public static function can_manage_settings(): bool {
		return current_user_can( self::MANAGE_SETTINGS ) || current_user_can( 'manage_options' );
	}

	/**
	 * Determine whether the current user can manage catalogs.
	 *
	 * @return bool
	 */
	public static function can_manage_catalogs(): bool {
		return current_user_can( self::MANAGE_CATALOGS ) || current_user_can( 'manage_woocommerce' );
	}
}
