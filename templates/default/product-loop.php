<?php
/**
 * Default product loop template.
 *
 * Context variables:
 * - $items   (array<CatalogItem>)
 * - $layout  (array)
 *
 * @package Catalogist
 */

defined( 'ABSPATH' ) || exit;

$cols = isset( $layout['columns'] ) ? (int) $layout['columns'] : 2;
?>
<div class="catalogist-product-loop catalogist-columns-<?php echo esc_attr( $cols ); ?>"
     data-columns="<?php echo esc_attr( $cols ); ?>">
	<?php foreach ( $items as $index => $item ) : ?>
		<div class="catalogist-product-item catalogist-item-<?php echo esc_attr( $item->get_type() ); ?>"
		     data-item-id="<?php echo esc_attr( $item->get_id() ); ?>"
		     data-item-type="<?php echo esc_attr( $item->get_type() ); ?>">

			<?php echo $this->renderSection( 'product-card', compact( 'item', 'index', 'count' => count( $items ) ) ); ?>

		</div>
	<?php endforeach; ?>
</div>
