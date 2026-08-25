<?php
/**
 * Default product card template.
 *
 * Context variables:
 * - $item      (CatalogItem)
 * - $index     (int)
 * - $count     (int)
 * - $escaped   (array) — pre-escaped helpers from TemplateContextBuilder
 *
 * @package Catalogist
 */

defined( 'ABSPATH' ) || exit;

$title   = $item->get_title();
$sku     = $item->get_sku();
$price   = $item->get_price();
$permalink = $item->get_permalink();
$image   = $item->get_image();
$shortDesc = $item->get_short_description();
?>
<article class="catalogist-product-card catalogist-item-<?php echo esc_attr( $item->get_type() ); ?>"
         data-item-id="<?php echo esc_attr( $item->get_id() ); ?>"
         data-item-index="<?php echo esc_attr( $index ); ?>">

	<?php if ( $image ) : ?>
		<div class="catalogist-product-image">
			<a href="<?php echo esc_url( $permalink ); ?>">
				<img src="<?php echo esc_url( $image['src'] ); ?>"
				     alt="<?php echo esc_attr( $title ); ?>"
				     width="<?php echo esc_attr( isset( $image['width'] ) ? $image['width'] : '' ); ?>"
				     height="<?php echo esc_attr( isset( $image['height'] ) ? $image['height'] : '' ); ?>"
				     class="catalogist-product-image-img" />
			</a>
		</div>
	<?php endif; ?>

	<div class="catalogist-product-info">
		<h2 class="catalogist-product-title">
			<a href="<?php echo esc_url( $permalink ); ?>">
				<?php echo esc_html( $title ); ?>
			</a>
		</h2>

		<?php if ( $sku ) : ?>
			<p class="catalogist-product-sku"><?php echo esc_html( $sku ); ?></p>
		<?php endif; ?>

		<?php if ( $price ) : ?>
			<p class="catalogist-product-price">
				<?php echo wc_price( $price ); ?>
			</p>
		<?php endif; ?>

		<?php if ( $shortDesc ) : ?>
			<div class="catalogist-product-short-description">
				<?php echo wp_kses_post( $shortDesc ); ?>
			</div>
		<?php endif; ?>
	</div>

	<?php if ( $item->has_variation_table() ) : ?>
		<?php echo $this->renderSection( 'variation-table', compact( 'item' ) ); ?>
	<?php endif; ?>

</article>
