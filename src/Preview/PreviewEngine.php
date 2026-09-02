<?php
/**
 * Preview engine — renders A4-simulated catalog preview.
 *
 * Wraps PrintEngineInterface to add a visualization layer without duplicating rendering.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Preview;

use Catalogist\Catalog\Catalog;
use Catalogist\CatalogItem\CatalogItem;
use Catalogist\Print\PrintEngineInterface;
use Catalogist\Security\Nonce;

/**
 * Preview engine implementation.
 */
final class PreviewEngine implements PreviewEngineInterface {

	/**
	 * Print engine instance (single source of truth for rendering).
	 *
	 * @var PrintEngineInterface
	 */
	private PrintEngineInterface $print_engine;

	/**
	 * Constructor.
	 *
	 * @param PrintEngineInterface $print_engine Print engine instance.
	 */
	public function __construct( PrintEngineInterface $print_engine ) {
		$this->print_engine = $print_engine;
	}

	/**
	 * Generate a preview-ready HTML page for a catalog.
	 *
	 * @param Catalog                  $catalog          Catalog entity.
	 * @param array<CatalogItem>       $catalogItems     Normalized catalog items.
	 * @param array<string, mixed>|null $previewSettings Preview settings override.
	 *
	 * @return string Preview HTML page.
	 */
	public function renderPreview(
		Catalog $catalog,
		array $catalogItems,
		?array $previewSettings = null
	): string {
		// Build merged preview settings.
		$settings = $this->buildPreviewSettings( $previewSettings );

		// Delegate to PrintEngine for actual catalog rendering.
		$catalog_html = $this->print_engine->generatePrintHTML( $catalog, $catalogItems, $previewSettings );

		// Escape settings for data attributes.
		$orientation  = esc_attr( $settings['orientation'] );
		$page_size    = esc_attr( $settings['page_size'] );
		$columns      = esc_attr( $settings['columns'] );
		$margin_top   = esc_attr( (string) $settings['margins']['top'] );
		$margin_right = esc_attr( (string) $settings['margins']['right'] );
		$margin_bottom = esc_attr( (string) $settings['margins']['bottom'] );
		$margin_left  = esc_attr( (string) $settings['margins']['left'] );

		// Build preview chrome.
		$wrapper_open  = '<div class="catalogist-preview-page" data-preview-orientation="' . $orientation . '" data-preview-columns="' . $columns . '">';
		$controls      = '<div class="catalogist-preview-controls">';
		$controls     .= '<button type="button" class="button catalogist-preview-btn catalogist-preview-btn-print" data-orientation="' . $orientation . '">' . esc_html__( 'Print', 'catalogist' ) . '</button>';
		$controls     .= '<button type="button" class="button catalogist-preview-btn catalogist-preview-btn-orientation" data-current="' . $orientation . '">' . esc_html__( 'Switch to ' . ( 'portrait' === $orientation ? 'Landscape' : 'Portrait' ), 'catalogist' ) . '</button>';
		$controls     .= '<button type="button" class="button catalogist-preview-btn catalogist-preview-btn-close" href="' . esc_url( admin_url( 'admin.php?page=catalogist' ) ) . '">' . esc_html__( 'Close', 'catalogist' ) . '</button>';
		$controls     .= '</div>';

		// Info bar.
		$info_bar = '<div class="catalogist-preview-info">';
		$info_bar .= '<span class="catalogist-preview-info-item">' . esc_html__( 'Paper:', 'catalogist' ) . ' <strong>' . esc_html( $page_size ) . '</strong></span>';
		$info_bar .= '<span class="catalogist-preview-info-item">' . esc_html__( 'Orientation:', 'catalogist' ) . ' <strong>' . esc_html( ucfirst( $settings['orientation'] ) ) . '</strong></span>';
		$info_bar .= '<span class="catalogist-preview-info-item">' . esc_html__( 'Columns:', 'catalogist' ) . ' <strong>' . esc_html( (string) $settings['columns'] ) . '</strong></span>';
		$info_bar .= '<span class="catalogist-preview-info-item">' . esc_html__( 'Margins:', 'catalogist' ) . ' <strong>' . esc_html( (string) $settings['margins']['top'] ) . 'mm</strong></span>';
		$info_bar .= '</div>';

		// Paper container with A4 simulation.
		$paper_class = 'catalogist-preview-paper catalogist-preview-paper-' . $orientation;
		$paper_style = sprintf(
			'padding: %dmm %dmm %dmm %dmm;',
			(int) $settings['margins']['top'],
			(int) $settings['margins']['right'],
			(int) $settings['margins']['bottom'],
			(int) $settings['margins']['left']
		);

		$paper = '<div class="' . esc_attr( $paper_class ) . '" style="' . esc_attr( $paper_style ) . '">';

		// Preview notice.
		$preview_notice = '<div class="catalogist-preview-notice">' . esc_html__(
			'Preview approximates print output. Actual pagination may differ when printed. Use the Print button for accurate results.',
			'catalogist'
		) . '</div>';

		// Assemble the page.
		$html  = '<!DOCTYPE html>' . "\n";
		$html .= '<html lang="' . esc_attr( get_bloginfo( 'language' ) ) . '">' . "\n";
		$html .= '<head>' . "\n";
		$html .= '<meta charset="' . esc_attr( get_bloginfo( 'charset' ) ) . '">' . "\n";
		$html .= '<meta name="viewport" content="width=device-width, initial-scale=1">' . "\n";
		$html .= '<title>' . esc_html( $catalog->get_title() ) . ' — ' . esc_html__( 'Preview', 'catalogist' ) . '</title>' . "\n";
		$html .= '<link rel="stylesheet" href="' . esc_url( CATALOGIST_PLUGIN_URL . 'assets/css/preview.css' ) . '">' . "\n";
		$html .= '<link rel="stylesheet" href="' . esc_url( CATALOGIST_PLUGIN_URL . 'assets/css/print.css' ) . '">' . "\n";
		$html .= '</head>' . "\n";
		$html .= '<body class="catalogist-preview-body">' . "\n";
		$html .= $wrapper_open . "\n";
		$html .= $controls . "\n";
		$html .= $info_bar . "\n";
		$html .= $paper . "\n";
		$html .= $preview_notice . "\n";
		$html .= $catalog_html . "\n";
		$html .= '</div>' . "\n";
		$html .= '</div>' . "\n";
		$html .= '<script src="' . esc_url( CATALOGIST_PLUGIN_URL . 'assets/js/preview.js' ) . '"></script>' . "\n";
		$html .= '</body>' . "\n";
		$html .= '</html>' . "\n";

		return $html;
	}

	/**
	 * Generate the admin preview page URL for a catalog.
	 *
	 * @param int                          $catalogId     Catalog post ID.
	 * @param array<string, mixed>|null     $previewSettings Preview settings override.
	 *
	 * @return string Admin preview URL.
	 */
	public function getPreviewURL( int $catalogId, ?array $previewSettings = null ): string {
		$args = array(
			'page'       => 'catalogist-preview',
			'catalog_id' => absint( $catalogId ),
			'_wpnonce'   => wp_create_nonce( 'catalogist_preview_action' ),
		);

		if ( is_array( $previewSettings ) && ! empty( $previewSettings ) ) {
			$args['print_settings'] = base64_encode( wp_json_encode( $previewSettings ) );
		}

		return add_query_arg( $args, admin_url( 'admin.php' ) );
	}

	/**
	 * Generate a print-ready URL with current settings encoded.
	 *
	 * @param int                          $catalogId   Catalog post ID.
	 * @param array<string, mixed>|null     $previewSettings Preview settings override.
	 *
	 * @return string Print URL.
	 */
	public function getPrintURL( int $catalogId, ?array $previewSettings = null ): string {
		return $this->print_engine->generatePrintPreviewURL( $catalogId, $previewSettings );
	}

	/**
	 * Check if the preview should show a loading state.
	 *
	 * @return bool
	 */
	public function shouldShowLoading(): bool {
		return false;
	}

	/**
	 * Get the A4 paper width in millimeters.
	 *
	 * @return int
	 */
	public function getPaperWidthMM(): int {
		return 210;
	}

	/**
	 * Get the A4 paper height in millimeters.
	 *
	 * @return int
	 */
	public function getPaperHeightMM(): int {
		return 297;
	}

	/**
	 * Build merged preview settings from catalog defaults and overrides.
	 *
	 * @param array<string, mixed>|null $previewSettings Override settings.
	 *
	 * @return array<string, mixed> Merged preview settings.
	 */
	private function buildPreviewSettings( ?array $previewSettings ): array {
		// Use PrintEngine to get the same defaults.
		$print_engine = $this->print_engine;

		// Access PrintEngine's buildPrintSettings via reflection if needed,
		// or delegate settings building to PrintEngine and extract.
		// For simplicity, we replicate the same logic here.
		$defaults = array(
			'page_size'   => 'A4',
			'orientation' => 'portrait',
			'columns'     => 2,
			'margins'     => array(
				'top'    => 20.0,
				'right'  => 20.0,
				'bottom' => 20.0,
				'left'   => 20.0,
			),
			'show_cover'  => false,
			'show_header' => true,
			'show_footer' => true,
		);

		// Merge overrides.
		if ( is_array( $previewSettings ) && ! empty( $previewSettings ) ) {
			$defaults = array_merge( $defaults, $previewSettings );
		}

		// Normalize (same as PrintEngine).
		$defaults['page_size']    = strtoupper( sanitize_text_field( $defaults['page_size'] ?? 'a4' ) );
		$defaults['orientation']  = in_array( $defaults['orientation'] ?? 'portrait', array( 'portrait', 'landscape' ), true )
			? $defaults['orientation']
			: 'portrait';
		$defaults['columns']      = max( 1, min( 4, absint( $defaults['columns'] ?? 2 ) ) );
		$defaults['show_header']  = ! empty( $defaults['show_header'] );
		$defaults['show_footer']  = ! empty( $defaults['show_footer'] );
		$defaults['show_cover']   = ! empty( $defaults['show_cover'] );

		$defaults['margins'] = array(
			'top'    => max( 0, (float) ( $defaults['margins']['top'] ?? 20 ) ),
			'right'  => max( 0, (float) ( $defaults['margins']['right'] ?? 20 ) ),
			'bottom' => max( 0, (float) ( $defaults['margins']['bottom'] ?? 20 ) ),
			'left'   => max( 0, (float) ( $defaults['margins']['left'] ?? 20 ) ),
		);

		return $defaults;
	}
}
