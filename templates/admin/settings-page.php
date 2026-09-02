<?php
/**
 * Settings page template.
 *
 * @package Catalogist
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap catalogist-admin-wrap">
	<h1><?php echo esc_html__( 'Catalogist Settings', 'catalogist' ); ?></h1>

	<form action="options.php" method="post" class="catalogist-settings-form">
		<?php
		settings_fields( \Catalogist\Security\Nonce::SETTINGS_ACTION );
		do_settings_sections( 'catalogist' );
		submit_button( __( 'Save Settings', 'catalogist' ) );
		?>
	</form>
</div>
