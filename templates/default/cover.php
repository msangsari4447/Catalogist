<?php
/**
 * Default cover page template for print mode.
 *
 * Context variables:
 * - $catalog          (Catalog)
 * - $items            (array<CatalogItem>)
 * - $layout           (array)
 * - $print            (array)
 * - $orientation      (string)
 * - $page_size        (string)
 * - $margins          (array)
 *
 * @package Catalogist
 */

defined( 'ABSPATH' ) || exit;

$item_count  = is_countable( $items ) ? count( $items ) : 0;
$orientation = $orientation ?? 'portrait';
$page_size   = $page_size ?? 'A4';
?>
<div class="catalogist-cover" data-print-section="cover">
	<div class="catalogist-cover-inner">
		<?php if ( ! empty( $layout['logo_url'] ?? '' ) ) : ?>
			<div class="catalogist-cover-logo">
				<img src="<?php echo esc_url( $layout['logo_url'] ); ?>"
				     alt="<?php esc_attr_e( 'Company Logo', 'catalogist' ); ?>"
				     class="catalogist-cover-logo-img" />
			</div>
		<?php endif; ?>

		<h1 class="catalogist-cover-title"><?php echo esc_html( $catalog->get_title() ); ?></h1>

		<?php if ( ! empty( $layout['header_content'] ?? '' ) ) : ?>
			<div class="catalogist-cover-subtitle">
				<?php echo wp_kses_post( $layout['header_content'] ); ?>
			</div>
		<?php endif; ?>

		<div class="catalogist-cover-meta">
			<span class="catalogist-cover-count"><?php echo esc_html( sprintf(
				/* translators: %d: number of products */
				__( '%d Products', 'catalogist' ),
				$item_count
			) ); ?></span>
			<span class="catalogist-cover-separator">&middot;</span>
			<span class="catalogist-cover-specs"><?php echo esc_html( sprintf(
				'%s &middot; %s',
				strtoupper( $page_size ),
				ucfirst( $orientation )
			) ); ?></span>
			<span class="catalogist-cover-date"><?php echo esc_html( gmdate( 'F Y' ) ); ?></span>
		</div>
	</div>
</div>
