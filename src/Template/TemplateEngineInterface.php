<?php
/**
 * Template engine interface.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Template;

use Catalogist\Catalog\Catalog;
use Catalogist\CatalogItem\CatalogItem;

/**
 * Interface for the template engine orchestrator.
 *
 * Coordinates loading, context building, and rendering.
 */
interface TemplateEngineInterface {

	/**
	 * Render a full catalog using the specified template.
	 *
	 * Builds context, loads template, and renders HTML output.
	 *
	 * @param Catalog                  $catalog      Catalog entity.
	 * @param array<CatalogItem>       $catalogItems Normalized catalog items.
	 * @param array<string, mixed>|null $settings     Override settings (template, columns, etc.).
	 *
	 * @return string Rendered HTML output.
	 */
	public function renderCatalog(
		Catalog $catalog,
		array $catalogItems,
		?array $settings = null
	): string;

	/**
	 * Render a single catalog item (e.g., for Elementor widget preview).
	 *
	 * @param Catalog                  $catalog      Catalog entity.
	 * @param CatalogItem              $item         Catalog item to render.
	 * @param array<string, mixed>|null $settings     Override settings.
	 *
	 * @return string Rendered HTML output.
	 */
	public function renderItem(
		Catalog $catalog,
		CatalogItem $item,
		?array $settings = null
	): string;

	/**
	 * Get the template loader used by this engine.
	 *
	 * @return TemplateLoaderInterface
	 */
	public function getLoader(): TemplateLoaderInterface;

	/**
	 * Get the template renderer used by this engine.
	 *
	 * @return TemplateRendererInterface
	 */
	public function getRenderer(): TemplateRendererInterface;

	/**
	 * Get the context builder used by this engine.
	 *
	 * @return TemplateContextBuilderInterface
	 */
	public function getContextBuilder(): TemplateContextBuilderInterface;
}
