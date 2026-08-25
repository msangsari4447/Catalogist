<?php
/**
 * Template loader interface.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Template;

/**
 * Interface for loading template definitions by ID, slug, or fallback chain.
 *
 * Implementations may source templates from filesystem, CPT, Elementor, etc.
 */
interface TemplateLoaderInterface {

	/**
	 * Load a template by ID or slug.
	 *
	 * Follows the fallback chain: catalog-specific → theme override → plugin default → built-in fallback.
	 *
	 * @param int|string $templateId      Template ID (post ID or slug).
	 * @param string     $defaultFallback Default template slug if not found.
	 *
	 * @return Template|null Template instance or null if no template could be resolved.
	 */
	public function load( $templateId, string $defaultFallback = '' ): ?Template;

	/**
	 * Get the full filesystem path for a template slug.
	 *
	 * @param string $templateSlug Template slug.
	 *
	 * @return string|null Filesystem path or null if not found.
	 */
	public function getPath( string $templateSlug ): ?string;

	/**
	 * Clear the template cache.
	 *
	 * Use after template files are added or removed at runtime.
	 *
	 * @return void
	 */
	public function clearCache(): void;
}
