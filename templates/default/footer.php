<?php
/**
 * Default catalog footer template.
 *
 * Context variables:
 * - $catalog  (Catalog)
 * - $layout   (array)
 * - $print    (array)
 *
 * @package Catalogist
 */

defined( 'ABSPATH' ) || exit;

$content = isset( $layout['footer_content'] ) ? $layout['footer_content'] : '';
?>
<footer class="catalogist-footer" role="contentinfo">
	<?php if ( ! empty( $content ) ) : ?>
		<div class="catalogist-footer-content">
			<?php echo wp_kses_post( $content ); ?>
		</div>
	<?php endif; ?>

	<p class="catalogist-copyright">
		&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?>
		<?php echo esc_html( $catalog->get_title() ); ?>
	</p>
</footer>
