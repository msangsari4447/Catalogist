<?php
/**
 * Template renderer interface.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Template;

/**
 * Interface for rendering template files with a given context array.
 *
 * Implementations may use file-based includes, Elementor API, or custom rendering.
 */
interface TemplateRendererInterface {

	/**
	 * Render a template with the given context.
	 *
	 * Captures output via output buffering — never echoes directly.
	 *
	 * @param string            $templateSlug Template slug.
	 * @param array<string, mixed> $context    Template context array.
	 *
	 * @return string Rendered HTML output.
	 */
	public function render( string $templateSlug, array $context ): string;

	/**
	 * Render a specific section of a template.
	 *
	 * Sections are sub-templates included within the main template (header, footer, loop, card).
	 *
	 * @param string            $section     Section name (e.g., 'header', 'footer', 'loop', 'card', 'variation-table').
	 * @param array<string, mixed> $context    Template context array for the section.
	 *
	 * @return string Rendered HTML output.
	 */
	public function renderSection( string $section, array $context ): string;
}
