<?php
/**
 * Print engine — generates print-ready HTML from catalog data.
 *
 * Reuses the existing TemplateEngine pipeline and injects print-specific CSS.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Print;

use Catalogist\Catalog\Catalog;
use Catalogist\CatalogItem\CatalogItem;
use Catalogist\Template\TemplateEngineInterface;

/**
 * Print engine implementation.
 */
final class PrintEngine implements PrintEngineInterface {

	/**
	 * Template engine instance.
	 *
	 * @var TemplateEngineInterface
	 */
	private TemplateEngineInterface $template_engine;

	/**
	 * Print CSS base path (relative to plugin directory).
	 *
	 * @var string
	 */
	private string $css_path;

	/**
	 * Print CSS content (cached).
	 *
	 * @var string|null
	 */
	private ?string $css_cache = null;

	/**
	 * Constructor.
	 *
	 * @param TemplateEngineInterface $template_engine Template engine instance.
	 * @param string                  $css_path        Relative path to print.css.
	 */
	public function __construct( TemplateEngineInterface $template_engine, string $css_path ) {
		$this->template_engine = $template_engine;
		$this->css_path        = $css_path;
	}

	/**
	 * Generate print-ready HTML for a catalog.
	 *
	 * @param Catalog                  $catalog       Catalog entity.
	 * @param array<CatalogItem>       $catalogItems  Normalized catalog items.
	 * @param array<string, mixed>|null $printSettings Print settings override.
	 *
	 * @return string Print-ready HTML.
	 */
	public function generatePrintHTML(
		Catalog $catalog,
		array $catalogItems,
		?array $printSettings = null
	): string {
		// Build print settings with defaults from CatalogSettings.
		$settings = $this->buildPrintSettings( $catalog, $printSettings );

		// Render via existing TemplateEngine pipeline.
		$templateSettings = array(
			'template' => 'default',
			'layout'   => array(
				'columns'     => $settings['columns'],
				'show_header' => $settings['show_header'] ?? true,
				'show_footer' => $settings['show_footer'] ?? true,
			),
			'print'    => $settings,
		);

		$html = $this->template_engine->renderCatalog( $catalog, $catalogItems, $templateSettings );

		// Inject print data attributes and CSS.
		$html = $this->injectPrintAttributes( $html, $settings );
		$html = $this->injectPrintCSS( $html, $settings );

		return $html;
	}

	/**
	 * Generate print CSS based on settings.
	 *
	 * @param array<string, mixed> $settings Print settings.
	 *
	 * @return string Generated CSS string.
	 */
	public function generatePrintCSS( array $settings ): string {
		$page_size    = strtoupper( $settings['page_size'] ?? 'A4' );
		$orientation  = $settings['orientation'] ?? 'portrait';
		$margins      = $settings['margins'] ?? array( 'top' => 20, 'right' => 20, 'bottom' => 20, 'left' => 20 );
		$columns      = max( 1, min( 4, (int) ( $settings['columns'] ?? 2 ) ) );
		$show_cover   = $settings['show_cover'] ?? false;
		$show_header  = $settings['show_header'] ?? true;
		$show_footer  = $settings['show_footer'] ?? true;

		// Build @page rule.
		$size = 'A4 ' . $orientation;
		$margin_top    = isset( $margins['top'] )    ? (float) $margins['top']    : 20;
		$margin_right  = isset( $margins['right'] )  ? (float) $margins['right']  : 20;
		$margin_bottom = isset( $margins['bottom'] ) ? (float) $margins['bottom'] : 20;
		$margin_left   = isset( $margins['left'] )   ? (float) $margins['left']   : 20;

		$css  = sprintf(
			'@page {%ssize: %s; margin: %dmm %dmm %dmm %dmm;}\n',
			( 'A3' === $page_size || 'LEGAL' === $page_size ) ? '' : '',
			$size,
			(int) $margin_top,
			(int) $margin_right,
			(int) $margin_bottom,
			(int) $margin_left
		);

		// Column layouts.
		$css .= $this->generateColumnCSS( $columns, $orientation, $margins );

		// Page break protection.
		$css .= $this->generateBreakCSS( $settings );

		// Cover page forced break.
		if ( $show_cover ) {
			$css .= ".catalogist-cover ~ .catalogist-catalog {\n";
			$css .= "    break-before: page;\n";
			$css .= "    page-break-before: always;\n";
			$css .= "}\n";
		}

		// Header/footer page break behavior.
		if ( $settings['page_break_after_header'] ?? false ) {
			$css .= ".catalogist-header + .catalogist-product-loop {\n";
			$css .= "    break-after: page;\n";
			$css .= "    page-break-after: always;\n";
			$css .= "}\n";
		}

		if ( $settings['page_break_before_footer'] ?? false ) {
			$css .= ".catalogist-footer {\n";
			$css .= "    break-before: page;\n";
			$css .= "    page-break-before: always;\n";
			$css .= "}\n";
		}

		// Image handling.
		$css .= "
.catalogist-product-image img {
    max-width: 100% !important;
    height: auto !important;
    object-fit: contain;
    page-break-inside: avoid;
    break-inside: avoid;
}

.catalogist-product-card {
    page-break-inside: avoid;
    break-inside: avoid;
}

.catalogist-variation-table {
    page-break-inside: avoid;
    break-inside: avoid;
    width: 100%;
    page-break-inside: avoid;
}

.catalogist-product-loop {
    page-break-inside: avoid;
    break-inside: avoid;
}

.catalogist-header,
.catalogist-footer {
    page-break-inside: avoid;
    break-inside: avoid;
}
";

		// RTL support.
		$css .= "
@media print {
    .catalogist-catalog[dir=\"rtl\"] .catalogist-product-loop {
        direction: rtl;
        unicode-bidi: embed;
    }
}
";

		return $css;
	}

	/**
	 * Generate a print preview URL for a catalog.
	 *
	 * @param int                          $catalogId   Catalog post ID.
	 * @param array<string, mixed>|null     $printSettings Print settings override.
	 *
	 * @return string Print preview URL.
	 */
	public function generatePrintPreviewURL( int $catalogId, ?array $printSettings = null ): string {
		$args = array(
			'catalogist_print' => '1',
			'catalog_id'       => absint( $catalogId ),
		);

		if ( is_array( $printSettings ) && ! empty( $printSettings ) ) {
			$args['print_settings'] = base64_encode( wp_json_encode( $printSettings ) );
		}

		return add_query_arg( $args, home_url( '/' ) );
	}

	/**
	 * Build merged print settings from catalog defaults and overrides.
	 *
	 * @param Catalog                  $catalog       Catalog entity.
	 * @param array<string, mixed>|null $printSettings Override settings.
	 *
	 * @return array<string, mixed> Merged print settings.
	 */
	private function buildPrintSettings( Catalog $catalog, ?array $printSettings ): array {
		$defaults = $this->getPrintDefaults();

		// Merge catalog print settings.
		$catalogPrint = $catalog->get_print_settings();
		if ( is_array( $catalogPrint ) && ! empty( $catalogPrint ) ) {
			$defaults = array_merge( $defaults, $catalogPrint );
		}

		// Merge override settings.
		if ( is_array( $printSettings ) && ! empty( $printSettings ) ) {
			$defaults = array_merge( $defaults, $printSettings );
		}

		// Normalize.
		$defaults['page_size']    = strtoupper( sanitize_text_field( $defaults['page_size'] ?? 'a4' ) );
		$defaults['orientation']  = in_array( $defaults['orientation'] ?? 'portrait', array( 'portrait', 'landscape' ), true )
			? $defaults['orientation']
			: 'portrait';
		$defaults['columns']      = max( 1, min( 4, absint( $defaults['columns'] ?? 2 ) ) );
		$defaults['show_header']  = ! empty( $defaults['show_header'] );
		$defaults['show_footer']  = ! empty( $defaults['show_footer'] );
		$defaults['show_cover']   = ! empty( $defaults['show_cover'] );

		// Ensure margins array is normalized.
		if ( ! is_array( $defaults['margins'] ) ) {
			$defaults['margins'] = $defaults['margins'] ?? array(
				'top'    => 20,
				'right'  => 20,
				'bottom' => 20,
				'left'   => 20,
			);
		}

		$defaults['margins'] = array(
			'top'    => max( 0, (float) ( $defaults['margins']['top'] ?? 20 ) ),
			'right'  => max( 0, (float) ( $defaults['margins']['right'] ?? 20 ) ),
			'bottom' => max( 0, (float) ( $defaults['margins']['bottom'] ?? 20 ) ),
			'left'   => max( 0, (float) ( $defaults['margins']['left'] ?? 20 ) ),
		);

		return $defaults;
	}

	/**
	 * Get default print settings from CatalogSettings.
	 *
	 * @return array<string, mixed>
	 */
	private function getPrintDefaults(): array {
		// Use CatalogSettings as the single authoritative source.
		$settings_class = new \Catalogist\Catalog\CatalogSettings();
		return $settings_class->get_default_print_settings();
	}

	/**
	 * Generate column layout CSS.
	 *
	 * @param int    $columns   Column count.
	 * @param string $orientation Page orientation.
	 * @param array  $margins   Margin values.
	 *
	 * @return string CSS fragment.
	 */
	private function generateColumnCSS( int $columns, string $orientation, array $margins ): string {
		$css = '';

		// Calculate available width based on page size and orientation.
		// A4: 210mm wide (portrait), 297mm wide (landscape).
		$page_width = 'landscape' === $orientation ? 297 : 210;
		$margin_total = ( isset( $margins['left'] ) ? $margins['left'] : 20 ) +
		                ( isset( $margins['right'] ) ? $margins['right'] : 20 );
		$available_width = $page_width - $margin_total;

		// Column gap in mm.
		$gap = 5;

		for ( $col = 1; $col <= 4; $col++ ) {
			$css .= sprintf(
				'.catalogist-catalog[data-columns="%d"] .catalogist-product-loop {
    column-count: %d;
    column-gap: %dmm;
}
',
				$col,
				$col,
				$gap
			);
		}

		// Grid fallback for modern browsers.
		$css .= "
@media print {
    .catalogist-catalog[data-columns=\"1\"] .catalogist-product-loop {
        column-count: 1;
    }
    .catalogist-catalog[data-columns=\"2\"] .catalogist-product-loop {
        column-count: 2;
    }
    .catalogist-catalog[data-columns=\"3\"] .catalogist-product-loop {
        column-count: 3;
    }
    .catalogist-catalog[data-columns=\"4\"] .catalogist-product-loop {
        column-count: 4;
    }
}
";

		return $css;
	}

	/**
	 * Generate page-break protection CSS.
	 *
	 * @param array<string, mixed> $settings Print settings.
	 *
	 * @return string CSS fragment.
	 */
	private function generateBreakCSS( array $settings ): string {
		$css = "";

		// Default break protection for common elements.
		$elements = array(
			'.catalogist-product-card',
			'.catalogist-variation-table',
			'.catalogist-product-loop',
			'.catalogist-header',
			'.catalogist-footer',
		);

		foreach ( $elements as $selector ) {
			$css .= sprintf(
				"%s {\n    break-inside: avoid;\n    page-break-inside: avoid;\n}\n",
				$selector
			);
		}

		return $css;
	}

	/**
	 * Inject print-specific data attributes into the rendered HTML.
	 *
	 * @param string                 $html Rendered HTML.
	 * @param array<string, mixed>  $settings Print settings.
	 *
	 * @return string HTML with print attributes.
	 */
	private function injectPrintAttributes( string $html, array $settings ): string {
		$attrs = sprintf(
			'data-print-mode="true" data-orientation="%s" data-page-size="%s" data-columns="%s"',
			esc_attr( $settings['orientation'] ),
			esc_attr( $settings['page_size'] ),
			esc_attr( $settings['columns'] )
		);

		// Add print attributes to the root catalog div.
		$html = preg_replace(
			'/(<div[^>]*class="[^"]*catalogist-catalog[^"]*"[^>]*)>/',
			'${1} ' . $attrs . '>',
			$html,
			1
		);

		return $html;
	}

	/**
	 * Inject print CSS into the HTML output.
	 *
	 * @param string                 $html Rendered HTML.
	 * @param array<string, mixed>  $settings Print settings.
	 *
	 * @return string HTML with print CSS.
	 */
	private function injectPrintCSS( string $html, array $settings ): string {
		$css = $this->generatePrintCSS( $settings );

		$style_tag = sprintf(
			'<style type="text/css" media="print">%s</style>',
			$css
		);

		// Inject before </head> or at the beginning if no head.
		if ( false !== strpos( $html, '</head>' ) ) {
			$html = str_replace( '</head>', $style_tag . "\n</head>", $html );
		} else {
			$html = $style_tag . "\n" . $html;
		}

		return $html;
	}
}
