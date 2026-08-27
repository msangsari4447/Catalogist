<?php
/**
 * Output engine implementation.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Output;

use Catalogist\Catalog\Catalog;
use Catalogist\CatalogItem\CatalogItem;
use Catalogist\Template\TemplateEngineInterface;
use Catalogist\Print\PrintEngineInterface;

/**
 * Output engine that delegates to TemplateEngine or PrintEngine.
 */
final class OutputEngine implements OutputEngineInterface {

	/**
	 * Template engine.
	 *
	 * @var TemplateEngineInterface
	 */
	private TemplateEngineInterface $template_engine;

	/**
	 * Print engine.
	 *
	 * @var PrintEngineInterface
	 */
	private PrintEngineInterface $print_engine;

	/**
	 * Constructor.
	 *
	 * @param TemplateEngineInterface $template_engine Template engine.
	 * @param PrintEngineInterface   $print_engine    Print engine.
	 */
	public function __construct(
		TemplateEngineInterface $template_engine,
		PrintEngineInterface $print_engine
	) {
		$this->template_engine = $template_engine;
		$this->print_engine    = $print_engine;
	}

	/**
	 * Generate output.
	 *
	 * @param Catalog                $catalog     Catalog entity.
	 * @param array<CatalogItem>     $items       Normalized catalog items.
	 * @param string                 $format      Output format.
	 * @param array<string, mixed>   $settings    Additional settings.
	 *
	 * @return string Generated output HTML.
	 */
	public function generate(
		Catalog $catalog,
		array $items,
		string $format,
		array $settings = array()
	): string {
		if ( OutputFormat::PRINT === $format ) {
			$print_settings = isset( $settings['print_settings'] )
				? $settings['print_settings']
				: $catalog->get_print_settings();
			return $this->print_engine->generatePrintHTML( $catalog, $items, $print_settings );
		}

		// Default to HTML format (including HTML).
		$template_settings = array(
			'template'  => $settings['template'] ?? 'default',
			'layout'    => $settings['layout'] ?? $catalog->get_layout_settings(),
		);

		return $this->template_engine->renderCatalog( $catalog, $items, $template_settings );
	}
}