<?php
/**
 * Default catalog template — main entry point.
 *
 * Context variables:
 * - $catalog          (Catalog)
 * - $items            (array<CatalogItem>)
 * - $layout           (array)
 * - $print            (array)
 * - $template_id      (int|string)
 * - $template_slug    (string) — passed by TemplateEngine
 * - $columns          (int)
 * - $page_size        (string)
 * - $orientation      (string)
 * - $margins          (array)
 * - $show_header      (bool)
 * - $show_footer      (bool)
 *
 * @package Catalogist
 */

defined( 'ABSPATH' ) || exit;

$cols        = isset( $layout['columns'] ) ? (int) $layout['columns'] : 2;
$templateSlug = isset( $template_slug ) ? $template_slug : '';
$tmplId     = isset( $template_id ) ? $template_id : '';
$isPrintMode = ! empty( $print );
?>
<div class="catalogist-catalog catalogist-template-<?php echo esc_attr( $tmplId ); ?>"
     data-columns="<?php echo esc_attr( $cols ); ?>"
     data-template="<?php echo esc_attr( $tmplId ); ?>"
     data-print-mode="<?php echo esc_attr( $isPrintMode ? 'true' : 'false' ); ?>"
     data-orientation="<?php echo esc_attr( $orientation ); ?>"
     data-page-size="<?php echo esc_attr( $page_size ); ?>"
     style="<?php echo esc_attr( 'page-size:' . esc_attr( $page_size ) . '; orientation:' . esc_attr( $orientation ) ); ?>">

	<?php if ( $isPrintMode ) : ?>
		<?php echo $this->renderSection( 'cover', compact( 'catalog', 'items', 'layout', 'print', 'orientation', 'page_size' ) ); ?>
	<?php endif; ?>

	<?php if ( ! empty( $layout['show_header'] ) ) : ?>
		<?php echo $this->renderSection( 'header', compact( 'catalog', 'layout', 'print' ) ); ?>
	<?php endif; ?>

	<?php if ( ! empty( $items ) ) : ?>
		<?php echo $this->renderSection( 'product-loop', compact( 'items', 'layout' ) ); ?>
	<?php else : ?>
		<p class="catalogist-empty"><?php esc_html_e( 'No products found in this catalog.', 'catalogist' ); ?></p>
	<?php endif; ?>

	<?php if ( ! empty( $layout['show_footer'] ) ) : ?>
		<?php echo $this->renderSection( 'footer', compact( 'catalog', 'layout', 'print' ) ); ?>
	<?php endif; ?>

</div>
