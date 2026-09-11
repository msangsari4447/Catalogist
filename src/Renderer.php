<?php

declare(strict_types=1);

namespace Catalogist;

/**
 * Renderer — stateless HTML generation service.
 *
 * Transforms CatalogContext + Template configuration + CatalogItem[] into rendered HTML.
 * No WooCommerce coupling, no Elementor, no print/preview/output logic.
 * All dynamic output is safely escaped.
 */
final class Renderer {

	/**
	 * Render catalog HTML.
	 *
	 * @param CatalogContext $context  Rendering context (catalog_id, language, currency).
	 * @param array<string, mixed> $template Template configuration array.
	 * @param array<CatalogItem> $items      Catalog items to render.
	 * @return string Rendered HTML.
	 */
	public function render(
		CatalogContext $context,
		array $template,
		array $items
	): string {
		$html = '';

		if ( isset( $template['header'] ) && is_array( $template['header'] ) ) {
			$html .= $this->render_header( $template['header'], $context );
		}

		if ( ! empty( $items ) ) {
			if ( isset( $template['loop'] ) && is_array( $template['loop'] ) ) {
				$html .= $this->render_loop( $template['loop'], $template['card'] ?? array(), $context, $items );
			}
		}

		if ( isset( $template['footer'] ) && is_array( $template['footer'] ) ) {
			$html .= $this->render_footer( $template['footer'] );
		}

		return $html;
	}

	/**
	 * Render header section.
	 *
	 * @param array<string, mixed> $header_config Header configuration.
	 * @param CatalogContext $context Rendering context.
	 * @return string Rendered header HTML or empty string if disabled.
	 */
	private function render_header( array $header_config, CatalogContext $context ): string {
		if ( ! ( $header_config['enabled'] ?? true ) ) {
			return '';
		}

		$html = '<div class="catalogist-header">';

		if ( $header_config['show_title'] ?? false ) {
			$title = get_the_title( $context->catalog_id );
			$html .= '<h1 class="catalogist-title">' . esc_html( $title ) . '</h1>';
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Render product loop section.
	 *
	 * @param array<string, mixed> $loop_config Loop configuration.
	 * @param array<string, mixed> $card_config Card configuration.
	 * @param CatalogContext $context Rendering context.
	 * @param array<CatalogItem> $items Catalog items.
	 * @return string Rendered loop HTML.
	 */
	private function render_loop(
		array $loop_config,
		array $card_config,
		CatalogContext $context,
		array $items
	): string {
		$columns = $this->validate_columns( $loop_config['columns'] ?? 3 );

		$html  = '<div class="catalogist-loop" data-columns="' . esc_attr( (string) $columns ) . '">';
		$html .= '<div class="catalogist-grid" style="' . esc_attr( $this->grid_style( $columns ) ) . '">';

		foreach ( $items as $item ) {
			if ( $item instanceof CatalogItem ) {
				$html .= $this->render_card( $card_config, $context, $item );
			}
		}

		$html .= '</div>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Render single product card.
	 *
	 * @param array<string, mixed> $card_config Card visibility configuration.
	 * @param CatalogContext $context Rendering context.
	 * @param CatalogItem $item Catalog item.
	 * @return string Rendered card HTML.
	 */
	private function render_card(
		array $card_config,
		CatalogContext $context,
		CatalogItem $item
	): string {
		$html = '<div class="catalogist-card">';

		if ( $card_config['show_image'] ?? true ) {
			$html .= $this->render_card_image( $item );
		}

		if ( $card_config['show_title'] ?? true ) {
			$html .= $this->render_card_title( $item );
		}

		if ( $card_config['show_price'] ?? true ) {
			$html .= $this->render_card_price( $item, $context );
		}

		if ( $card_config['show_sku'] ?? false ) {
			$html .= $this->render_card_sku( $item );
		}

		if ( $card_config['show_stock'] ?? false ) {
			$html .= $this->render_card_stock( $item );
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Render product image.
	 *
	 * @param CatalogItem $item Catalog item.
	 * @return string Rendered image HTML or empty string.
	 */
	private function render_card_image( CatalogItem $item ): string {
		if ( empty( $item->image_url ) ) {
			return '';
		}

		$alt_text = sprintf(
			/* translators: %s: product title */
			esc_html__( 'Image of %s', 'catalogist' ),
			esc_html( $item->title )
		);

		return '<div class="catalogist-image">' .
			'<img src="' . esc_url( $item->image_url ) . '" alt="' . esc_attr( $alt_text ) . '" />' .
			'</div>';
	}

	/**
	 * Render product title.
	 *
	 * @param CatalogItem $item Catalog item.
	 * @return string Rendered title HTML.
	 */
	private function render_card_title( CatalogItem $item ): string {
		return '<h3 class="catalogist-title">' . esc_html( $item->title ) . '</h3>';
	}

	/**
	 * Render product price.
	 *
	 * @param CatalogItem $item Catalog item.
	 * @param CatalogContext $context Rendering context.
	 * @return string Rendered price HTML.
	 */
	private function render_card_price( CatalogItem $item, CatalogContext $context ): string {
		$html = '<div class="catalogist-price">';

		$display_price = $item->sale_price > 0 ? $item->sale_price : $item->price;

		$price_html = sprintf(
			'<span class="amount">%s %s</span>',
			esc_html( number_format( $display_price, 2 ) ),
			esc_html( $context->currency )
		);

		if ( $item->sale_price > 0 && $item->sale_price < $item->regular_price ) {
			$price_html = sprintf(
				'<span class="regular-price"><del>%s %s</del></span> <span class="sale-price">%s %s</span>',
				esc_html( number_format( $item->regular_price, 2 ) ),
				esc_html( $context->currency ),
				esc_html( number_format( $item->sale_price, 2 ) ),
				esc_html( $context->currency )
			);
		}

		$html .= $price_html;
		$html .= '</div>';

		return $html;
	}

	/**
	 * Render product SKU.
	 *
	 * @param CatalogItem $item Catalog item.
	 * @return string Rendered SKU HTML or empty string.
	 */
	private function render_card_sku( CatalogItem $item ): string {
		if ( empty( $item->sku ) ) {
			return '';
		}

		return '<div class="catalogist-sku">' .
			'<span class="label">' . esc_html__( 'SKU:', 'catalogist' ) . '</span> ' .
			'<span class="value">' . esc_html( $item->sku ) . '</span>' .
			'</div>';
	}

	/**
	 * Render product stock status.
	 *
	 * @param CatalogItem $item Catalog item.
	 * @return string Rendered stock HTML.
	 */
	private function render_card_stock( CatalogItem $item ): string {
		$status_class = 'instock' === $item->stock_status ? 'in-stock' : 'out-of-stock';
		$status_text  = 'instock' === $item->stock_status ?
			esc_html__( 'In Stock', 'catalogist' ) :
			esc_html__( 'Out of Stock', 'catalogist' );

		return '<div class="catalogist-stock ' . esc_attr( $status_class ) . '">' .
			esc_html( $status_text ) .
			'</div>';
	}

	/**
	 * Render footer section.
	 *
	 * @param array<string, mixed> $footer_config Footer configuration.
	 * @param CatalogContext $context Rendering context.
	 * @return string Rendered footer HTML or empty string if disabled.
	 */
	private function render_footer( array $footer_config ): string {
		if ( ! ( $footer_config['enabled'] ?? true ) ) {
			return '';
		}

		$html  = '<div class="catalogist-footer">';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Validate and normalize column count.
	 *
	 * @param mixed $columns Column count input.
	 * @return int Validated column count (1-12).
	 */
	private function validate_columns( $columns ): int {
		$col = (int) $columns;
		if ( $col < 1 ) {
			$col = 1;
		}
		if ( $col > 12 ) {
			$col = 12;
		}
		return $col;
	}

	/**
	 * Generate CSS grid style string.
	 *
	 * @param int $columns Column count.
	 * @return string CSS style string.
	 */
	private function grid_style( int $columns ): string {
		return 'display: grid; grid-template-columns: repeat(' . absint( $columns ) . ', 1fr); gap: 1rem;';
	}
}
