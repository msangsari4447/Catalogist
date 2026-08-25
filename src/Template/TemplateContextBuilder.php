<?php
/**
 * Template context builder.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Template;

use Catalogist\Catalog\Catalog;
use Catalogist\CatalogItem\CatalogItem;

/**
 * Builds standardized template context arrays from catalog data.
 *
 * Separates data preparation from rendering. Provides both raw data
 * and pre-escaped helper arrays for template designers.
 */
final class TemplateContextBuilder implements TemplateContextBuilderInterface {

	/**
	 * Default column count when not specified.
	 *
	 * @var int
	 */
	private const DEFAULT_COLUMNS = 2;

	/**
	 * Default show header flag.
	 *
	 * @var bool
	 */
	private const DEFAULT_SHOW_HEADER = true;

	/**
	 * Default show footer flag.
	 *
	 * @var bool
	 */
	private const DEFAULT_SHOW_FOOTER = false;

	/**
	 * Build the main template context for a catalog.
	 *
	 * @param Catalog                    $catalog        Catalog entity.
	 * @param array<CatalogItem>         $catalogItems   Normalized catalog items.
	 * @param array<string, mixed>|null  $layoutSettings Layout settings override.
	 * @param array<string, mixed>|null  $printSettings  Print settings override.
	 *
	 * @return array<string, mixed> Template context array.
	 */
	public function build(
		Catalog $catalog,
		array $catalogItems,
		?array $layoutSettings = null,
		?array $printSettings = null
	): array {
		$layout = $this->normalizeLayoutSettings( $catalog, $layoutSettings );
		$print  = $this->normalizePrintSettings( $catalog, $printSettings );

		$templateId   = $catalog->get_template_id();
		$templateName = '';

		// Resolve template name from catalog if ID is set.
		if ( $templateId > 0 ) {
			$templateName = $this->resolveTemplateName( $templateId );
		}

		return array(
			// Core data.
			'catalog'     => $catalog,
			'items'       => $catalogItems,

			// Template metadata.
			'template_id'   => $templateId,
			'template_name' => $templateName,

			// Layout settings (normalized with defaults).
			'layout'      => $layout,
			'columns'     => $layout['columns'],
			'page_size'   => $layout['page_size'],
			'orientation' => $layout['orientation'],

			// Print settings (normalized with defaults).
			'print'       => $print,
			'margins'     => $print['margins'],
			'show_header' => $layout['show_header'],
			'show_footer' => $layout['show_footer'],
		);
	}

	/**
	 * Build loop context for a single item within a catalog iteration.
	 *
	 * @param Catalog     $catalog Catalog entity.
	 * @param CatalogItem $item    Current catalog item.
	 * @param int         $index   Zero-based loop index.
	 * @param int         $count   Total item count.
	 *
	 * @return array<string, mixed> Loop context array.
	 */
	public function buildLoopContext(
		Catalog $catalog,
		CatalogItem $item,
		int $index,
		int $count
	): array {
		$loopMeta = array(
			'item'        => $item,
			'item_index'  => $index,
			'item_count'  => $count,
			'is_first'    => ( 0 === $index ),
			'is_last'     => ( $index === $count - 1 ),
			'is_even'     => ( 0 === $index % 2 ),
			'is_odd'      => ( 1 === $index % 2 ),
		);

		// Provide pre-escaped helpers for convenience.
		$loopMeta['escaped'] = $this->buildEscapedHelpers( $item );

		return $loopMeta;
	}

	/**
	 * Normalize layout settings from catalog and override array.
	 *
	 * @param Catalog                    $catalog        Catalog entity.
	 * @param array<string, mixed>|null  $override       Override settings.
	 *
	 * @return array<string, mixed> Normalized layout settings.
	 */
	private function normalizeLayoutSettings(
		Catalog $catalog,
		?array $override
	): array {
		$defaults = array(
			'columns'     => self::DEFAULT_COLUMNS,
			'page_size'   => 'A4',
			'orientation' => 'portrait',
			'show_header' => self::DEFAULT_SHOW_HEADER,
			'show_footer' => self::DEFAULT_SHOW_FOOTER,
			'logo_url'    => '',
			'header_content' => '',
			'footer_content' => '',
		);

		$catalogSettings = $catalog->get_layout_settings();

		if ( is_array( $catalogSettings ) ) {
			$defaults = array_merge( $defaults, $catalogSettings );
		}

		if ( is_array( $override ) ) {
			$defaults = array_merge( $defaults, $override );
		}

		// Clamp columns to valid range.
		$defaults['columns'] = max( 1, min( 4, (int) $defaults['columns'] ) );

		return $defaults;
	}

	/**
	 * Normalize print settings from catalog and override array.
	 *
	 * @param Catalog                    $catalog        Catalog entity.
	 * @param array<string, mixed>|null  $override       Override settings.
	 *
	 * @return array<string, mixed> Normalized print settings.
	 */
	private function normalizePrintSettings(
		Catalog $catalog,
		?array $override
	): array {
		$defaults = array(
			'margins' => array(
				'top'    => 20,
				'right'  => 20,
				'bottom' => 20,
				'left'   => 20,
			),
		);

		$catalogSettings = $catalog->get_print_settings();

		if ( is_array( $catalogSettings ) ) {
			$defaults = array_merge( $defaults, $catalogSettings );
		}

		if ( is_array( $override ) ) {
			$defaults = array_merge( $defaults, $override );
		}

		return $defaults;
	}

	/**
	 * Resolve template name from post ID.
	 *
	 * @param int $templateId Template post ID.
	 *
	 * @return string Template name or empty string.
	 */
	private function resolveTemplateName( int $templateId ): string {
		$post = get_post( $templateId );

		if ( ! $post ) {
			return '';
		}

		return (string) $post->post_title;
	}

	/**
	 * Build pre-escaped helper data for a catalog item.
	 *
	 * @param CatalogItem $item Catalog item.
	 *
	 * @return array<string, string> Pre-escaped values.
	 */
	private function buildEscapedHelpers( CatalogItem $item ): array {
		return array(
			'title'       => esc_html( $item->get_title() ),
			'sku'         => esc_html( $item->get_sku() ),
			'price'       => esc_html( $item->get_price() ),
			'permalink'   => esc_url( $item->get_permalink() ),
			'stock_status' => esc_html( $item->get_stock_status() ),
		);
	}
}
