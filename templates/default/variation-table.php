<?php
/**
 * Default variation table template (for table mode).
 *
 * Context variables:
 * - $item      (CatalogItem with variation_table populated)
 *
 * @package Catalogist
 */

defined( 'ABSPATH' ) || exit;

$table = $item->get_variation_table();
if ( empty( $table ) || empty( $table['variations'] ) ) {
	return;
}
?>
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
			<?php foreach ( $table['variations'] as $variation ) : ?>
				<tr class="catalogist-variation-row">
					<td><?php echo esc_html( $variation['title'] ?? '' ); ?></td>
					<td><?php echo esc_html( $variation['sku'] ?? '' ); ?></td>
					<td>
						<?php
						$vPrice = $variation['price'] ?? '';
						if ( $vPrice ) {
							echo wc_price( $vPrice );
						} else {
							echo esc_html( __( 'N/A', 'catalogist' ) );
						}
						?>
					</td>
					<td class="catalogist-stock-<?php echo esc_attr( $variation['stock_status'] ?? 'instock' ); ?>">
						<?php
						$status = $variation['stock_status'] ?? 'instock';
						if ( 'instock' === $status ) {
							esc_html_e( 'In Stock', 'catalogist' );
						} elseif ( 'outofstock' === $status ) {
							esc_html_e( 'Out of Stock', 'catalogist' );
						} else {
							echo esc_html( $status );
						}
						?>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>
