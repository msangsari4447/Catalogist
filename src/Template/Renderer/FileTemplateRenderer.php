<?php
/**
 * File-based template renderer.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Template\Renderer;

use Catalogist\Template\TemplateLoaderInterface;
use Catalogist\Template\TemplateRendererInterface;

/**
 * Renders templates from filesystem files using output buffering.
 *
 * All template files receive extracted context variables and must
 * use WordPress escaping functions for output.
 */
final class FileTemplateRenderer implements TemplateRendererInterface {

	/**
	 * Template loader instance.
	 *
	 * @var TemplateLoaderInterface
	 */
	private TemplateLoaderInterface $loader;

	/**
	 * Constructor.
	 *
	 * @param TemplateLoaderInterface $loader Template loader.
	 */
	public function __construct( TemplateLoaderInterface $loader ) {
		$this->loader = $loader;
	}

	/**
	 * Render a template with the given context.
	 *
	 * @param string            $templateSlug Template slug.
	 * @param array<string, mixed> $context    Template context array.
	 *
	 * @return string Rendered HTML output.
	 */
	public function render( string $templateSlug, array $context ): string {
		$path = $this->loader->getPath( $templateSlug );

		if ( null === $path ) {
			error_log( 'Catalogist: Template not found: ' . $templateSlug );

			return $this->renderFallback( $context );
		}

		// Apply pre-render filter.
		$path = apply_filters( 'catalogist_template_path', $path, $templateSlug, $context );

		// Action hook before render.
		do_action( 'catalogist_before_template_render', $templateSlug, $context );

		// Extract context variables into local scope.
		extract( $context, EXTR_SKIP );

		// Start output buffering.
		ob_start();

		try {
			require $path;
		} catch ( \Throwable $e ) {
			// Clean buffer on error.
			if ( ob_get_level() ) {
				ob_end_clean();
			}

			error_log( 'Catalogist template render error (' . $templateSlug . '): ' . $e->getMessage() );

			return $this->renderFallback( $context );
		}

		$html = ob_get_clean();

		// Apply post-render filter.
		$html = apply_filters( 'catalogist_template_output', $html, $templateSlug, $context );

		// Action hook after render.
		do_action( 'catalogist_after_template_render', $html, $templateSlug, $context );

		return $html;
	}

	/**
	 * Render a specific section of a template.
	 *
	 * @param string            $section     Section name (header, footer, loop, card, variation-table).
	 * @param array<string, mixed> $context    Template context array for the section.
	 *
	 * @return string Rendered HTML output.
	 */
	public function renderSection( string $section, array $context ): string {
		$templateSlug = $context['template_id'] ?? '';

		if ( empty( $templateSlug ) ) {
			return $this->renderFallback( $context );
		}

		$sectionSlug  = $templateSlug . '/' . $section;
		$sectionPath  = $this->loader->getPath( $sectionSlug );

		if ( null === $sectionPath ) {
			// If section not found, return empty string (parent template will handle).
			return '';
		}

		// Extract context variables into local scope.
		extract( $context, EXTR_SKIP );

		ob_start();

		try {
			require $sectionPath;
		} catch ( \Throwable $e ) {
			if ( ob_get_level() ) {
				ob_end_clean();
			}

			error_log( 'Catalogist section render error (' . $sectionSlug . '): ' . $e->getMessage() );

			return '';
		}

		return ob_get_clean();
	}

	/**
	 * Render a minimal fallback when template is missing.
	 *
	 * @param array<string, mixed> $context Template context.
	 *
	 * @return string Fallback HTML.
	 */
	private function renderFallback( array $context ): string {
		$title = '';

		if ( isset( $context['catalog'] ) && method_exists( $context['catalog'], 'get_title' ) ) {
			$title = $context['catalog']->get_title();
		}

		$html  = '<div class="catalogist-fallback">';
		$html .= '<h2>' . esc_html__( 'Catalogist', 'catalogist' ) . '</h2>';
		$html .= '<p>' . esc_html__( 'No template configured for this catalog.', 'catalogist' ) . '</p>';

		if ( ! empty( $title ) ) {
			$html .= '<p class="catalogist-catalog-title">' . esc_html( $title ) . '</p>';
		}

		$html .= '</div>';

		return $html;
	}
}
