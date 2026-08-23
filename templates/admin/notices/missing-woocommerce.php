<?php
/**
 * Missing WooCommerce notice template.
 *
 * @package Catalogist
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;
?>
<div class="notice notice-warning is-dismissible">
	<p>
		<?php
		echo wp_kses(
			sprintf(
				/* translators: %s: WooCommerce plugin link */
				__( 'Catalogist requires %s to be installed and active. Some features may be limited.', 'catalogist' ),
				'<strong><a href="https://woocommerce.com/" target="_blank" rel="noopener noreferrer">WooCommerce</a></strong>'
			),
			array(
				'strong' => array(),
				'a'      => array(
					'href'   => array(),
					'target' => array(),
					'rel'    => array(),
				),
			)
		);
		?>
	</p>
</div>
