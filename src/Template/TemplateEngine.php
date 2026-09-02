<?php
/**
 * Template engine — main orchestrator.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Template;

use Catalogist\Catalog\Catalog;
use Catalogist\CatalogItem\CatalogItem;
use Catalogist\Template\Loader\FileTemplateLoader;
use Catalogist\Template\Renderer\FileTemplateRenderer;

/**
 * Orchestrates template loading, context building, and rendering.
 *
 * Coordinates the three-component architecture:
 * TemplateEngine → TemplateLoader → TemplateContextBuilder → TemplateRenderer
 */
final class TemplateEngine implements TemplateEngineInterface {

	/**
	 * Template loader.
	 *
	 * @var TemplateLoaderInterface
	 */
	private TemplateLoaderInterface $loader;

	/**
	 * Template renderer.
	 *
	 * @var TemplateRendererInterface
	 */
	private TemplateRendererInterface $renderer;

	/**
	 * Context builder.
	 *
	 * @var TemplateContextBuilderInterface
	 */
	private TemplateContextBuilderInterface $contextBuilder;

	/**
	 * Default template slug.
	 *
	 * @var string
	 */
	private string $defaultTemplate;

	/**
	 * Constructor.
	 *
	 * @param TemplateLoaderInterface         $loader         Template loader.
	 * @param TemplateRendererInterface       $renderer       Template renderer.
	 * @param TemplateContextBuilderInterface $contextBuilder Context builder.
	 * @param string                          $defaultTemplate Default template slug.
	 */
	public function __construct(
		TemplateLoaderInterface $loader,
		TemplateRendererInterface $renderer,
		TemplateContextBuilderInterface $contextBuilder,
		string $defaultTemplate = 'default'
	) {
		$this->loader          = $loader;
		$this->renderer        = $renderer;
		$this->contextBuilder  = $contextBuilder;
		$this->defaultTemplate = $defaultTemplate;
	}

	/**
	 * Render a full catalog using the specified template.
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
	): string {
		// Determine template to use.
		$templateSlug = $this->resolveTemplateSlug( $catalog, $settings );

		// Build context.
		$layoutSettings = isset( $settings['layout'] ) ? $settings['layout'] : null;
		$printSettings  = isset( $settings['print'] ) ? $settings['print'] : null;

		$context = $this->contextBuilder->build(
			$catalog,
			$catalogItems,
			$layoutSettings,
			$printSettings
		);

		// Inject template slug into context for section rendering.
		$context['template_slug'] = $templateSlug;

		return $this->renderer->render( $templateSlug, $context );
	}

	/**
	 * Render a single catalog item.
	 *
	 * @param Catalog                  $catalog  Catalog entity.
	 * @param CatalogItem              $item     Catalog item to render.
	 * @param array<string, mixed>|null $settings Override settings.
	 *
	 * @return string Rendered HTML output.
	 */
	public function renderItem(
		Catalog $catalog,
		CatalogItem $item,
		?array $settings = null
	): string {
		$templateSlug = $this->resolveTemplateSlug( $catalog, $settings );

		$layoutSettings = isset( $settings['layout'] ) ? $settings['layout'] : null;
		$printSettings  = isset( $settings['print'] ) ? $settings['print'] : null;

		$context = $this->contextBuilder->build(
			$catalog,
			array( $item ),
			$layoutSettings,
			$printSettings
		);

		$context['template_slug'] = $templateSlug;

		// Render just the product-card section for a single item.
		return $this->renderer->renderSection( 'product-card', $context );
	}

	/**
	 * Get the template loader used by this engine.
	 *
	 * @return TemplateLoaderInterface
	 */
	public function getLoader(): TemplateLoaderInterface {
		return $this->loader;
	}

	/**
	 * Get the template renderer used by this engine.
	 *
	 * @return TemplateRendererInterface
	 */
	public function getRenderer(): TemplateRendererInterface {
		return $this->renderer;
	}

	/**
	 * Get the context builder used by this engine.
	 *
	 * @return TemplateContextBuilderInterface
	 */
	public function getContextBuilder(): TemplateContextBuilderInterface {
		return $this->contextBuilder;
	}

	/**
	 * Resolve the template slug to use for a catalog.
	 *
	 * Priority:
	 * 1. Settings override 'template' key
	 * 2. Catalog template_id (resolved to slug)
	 * 3. Default template slug
	 *
	 * @param Catalog                  $catalog  Catalog entity.
	 * @param array<string, mixed>|null $settings Override settings.
	 *
	 * @return string Template slug.
	 */
	private function resolveTemplateSlug(
		Catalog $catalog,
		?array $settings
	): string {
		// Settings override takes precedence.
		if ( is_array( $settings ) && isset( $settings['template'] ) ) {
			$slug = sanitize_text_field( $settings['template'] );
			// Whitelist allowed template slugs to prevent template injection.
			$allowed_templates = array( 'default', 'fallback' );
			if ( in_array( $slug, $allowed_templates, true ) ) {
				return $slug;
			}
			return $this->defaultTemplate;
		}

		$templateId = $catalog->get_template_id();

		if ( $templateId > 0 ) {
			$template = $this->loader->load( $templateId, $this->defaultTemplate );

			if ( null !== $template ) {
				return $template->get_slug();
			}
		}

		return $this->defaultTemplate;
	}
}
