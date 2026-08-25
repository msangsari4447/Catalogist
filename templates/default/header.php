<?php
/**
 * Default catalog header template.
 *
 * Context variables:
 * - $catalog  (Catalog)
 * - $layout   (array)
 * - $print    (array)
 *
 * @package Catalogist
 */

defined( 'ABSPATH' ) || exit;

$logoUrl  = isset( $layout['logo_url'] ) ? $layout['logo_url'] : '';
$content  = isset( $layout['header_content'] ) ? $layout['header_content'] : '';
?>
<header class="catalogist-header" role="banner">
	<?php if ( ! empty( $logoUrl ) ) : ?>
		<div class="catalogist-logo">
			<img src="<?php echo esc_url( $logoUrl ); ?>"
			     alt="<?php esc_attr_e( 'Company Logo', 'catalogist' ); ?>"
			     class="catalogist-logo-img" />
		</div>
	<?php endif; ?>

	<h1 class="catalogist-title"><?php echo esc_html( $catalog->get_title() ); ?></h1>

	<?php if ( ! empty( $content ) ) : ?>
		<div class="catalogist-header-content">
			<?php echo wp_kses_post( $content ); ?>
		</div>
	<?php endif; ?>
</header>
