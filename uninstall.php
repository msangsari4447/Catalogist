<?php
/**
 * Uninstall handler for Catalogist.
 *
 * @package Catalogist
 */

declare(strict_types=1);

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Delete plugin options.
delete_option( 'catalogist_settings' );
delete_option( 'catalogist_version' );

// Remove capabilities from administrator role.
$admin = get_role( 'administrator' );

if ( $admin ) {
	$capabilities = array(
		'catalogist_manage_catalogs',
		'catalogist_edit_catalogs',
		'catalogist_delete_catalogs',
		'catalogist_manage_templates',
		'catalogist_manage_settings',
	);

	foreach ( $capabilities as $cap ) {
		$admin->remove_cap( $cap );
	}
}

// Clear any cached data.
wp_cache_flush();
