<?php
/**
 * Built-in fallback template — used when no other template is available.
 *
 * Context variables:
 * - $catalog          (Catalog)
 * - $items            (array<CatalogItem>)
 * - $layout           (array)
 * - $columns          (int)
 *
 * @package Catalogist
 */

defined( 'ABSPATH' ) || exit;

$cols = isset( $layout['columns'] ) ? (int) $layout['columns'] : 2;
?>
<div class="catalogist-catalog catalogist-fallback-template"
     data-columns="<?php echo esc_attr( $cols ); ?>">

	<h2 class="catalogist-fallback-title">
		<?php echo esc_html( $catalog->get_title() ); ?>
	</h2>

	<?php if ( ! empty( $items ) ) : ?>
		<div class="catalogist-product-loop catalogist-columns-<?php echo esc_attr( $cols ); ?>">
			<?php foreach ( $items as $index => $item ) : ?>
				<div class="catalogist-product-item catalogist-item-<?php echo esc_attr( $item->get_type() ); ?>"
				     data-item-id="<?php echo esc_attr( $item->get_id() ); ?>">

					<article class="catalogist-product-card">
						<h3 class="catalogist-product-title">
							<a href="<?php echo esc_url( $item->get_permalink() ); ?>">
								<?php echo esc_html( $item->get_title() ); ?>
							</a>
						</h3>

						<?php if ( $item->get_sku() ) : ?>
							<p class="catalogist-product-sku">
								<?php echo esc_html( $item->get_sku() ); ?>
							</p>
						<?php endif; ?>

						<?php if ( $item->get_price() ) : ?>
							<p class="catalogist-product-price">
								<?php echo wc_price( $item->get_price() ); ?>
							</p>
						<?php endif; ?>

						<?php if ( $item->has_variation_table() ) : ?>
							<div class="catalogist-variation-table-wrapper">
								<table class="catalogist-variation-table">
									<thead>
										<tr>
											<th><?php esc_html_e( 'Variation', 'catalogist' ); ?></th>
											<th><?php esc_html_e( 'SKU', 'catalogist' ); ?></th>
											<th><?php esc_html_e( 'Price', 'catalogist' ); ?></th>
											<th><?php esc_html_e( 'Stock', 'catalogist' ); ?></th>
										</tr>
									</thead>
									<tbody>
										<?php foreach ( $item->get_variation_table()['variations'] as $v ) : ?>
											<tr>
												<td><?php echo esc_html( $v['title'] ?? '' ); ?></td>
												<td><?php echo esc_html( $v['sku'] ?? '' ); ?></td>
												<td><?php echo wc_price( $v['price'] ?? '' ); ?></td>
												<td class="stock-<?php echo esc_attr( $v['stock_status'] ?? 'instock' ); ?>">
													<?php echo esc_html( 'instock' === ( $v['stock_status'] ?? 'instock' ) ? __( 'In Stock', 'catalogist' ) : __( 'Out of Stock', 'catalogist' ) ); ?>
												</td>
											</tr>
										<?php endforeach; ?>
									</tbody>
								</table>
							</div>
						<?php endif; ?>

					</article>

				</div>
			<?php endforeach; ?>
		</div>
	<?php else : ?>
		<p class="catalogist-empty"><?php esc_html_e( 'No products found in this catalog.', 'catalogist' ); ?></p>
	<?php endif; ?>

</div>
