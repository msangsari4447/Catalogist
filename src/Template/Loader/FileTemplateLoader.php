<?php
/**
 * File-based template loader.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Template\Loader;

use Catalogist\Template\Template;
use Catalogist\Template\TemplateLoaderInterface;

/**
 * Loads templates from the filesystem with caching and fallback chain.
 *
 * Search order:
 * 1. Theme override: wp-content/themes/{theme}/catalogist/{slug}/
 * 2. Plugin default: wp-content/plugins/catalogist/templates/{slug}/
 * 3. Built-in fallback
 */
final class FileTemplateLoader implements TemplateLoaderInterface {

	/**
	 * Plugin base directory path.
	 *
	 * @var string
	 */
	private string $baseDirectory;

	/**
	 * Cache for resolved template paths keyed by slug.
	 *
	 * @var array<string, string|null>
	 */
	private array $pathCache = array();

	/**
	 * Constructor.
	 *
	 * @param string $pluginDirectory Plugin directory path (trailing slash included).
	 */
	public function __construct( string $pluginDirectory ) {
		$this->baseDirectory = untrailingslashit( $pluginDirectory ) . '/templates';
	}

	/**
	 * Load a template by ID or slug.
	 *
	 * @param int|string $templateId      Template ID (post ID or slug).
	 * @param string     $defaultFallback Default template slug if not found.
	 *
	 * @return Template|null Template instance or null if no template could be resolved.
	 */
	public function load( $templateId, string $defaultFallback = '' ): ?Template {
		// If templateId is numeric, treat as post ID (CPT template).
		if ( is_numeric( $templateId ) ) {
			$postId = (int) $templateId;
			$post   = get_post( $postId );

			if ( $post && 'ctlg_template' === $post->post_type ) {
				return new Template(
					$post->post_name,
					'',
					$postId,
					$post->post_title
				);
			}
		}

		// Treat as slug — try filesystem fallback chain.
		$slug = sanitize_file_name( (string) $templateId );
		$path = $this->getPath( $slug );

		if ( null !== $path ) {
			return new Template( $slug, $path );
		}

		// Fall back to default if provided.
		if ( '' !== $defaultFallback ) {
			$defaultPath = $this->getPath( $defaultFallback );

			if ( null !== $defaultPath ) {
				return new Template( $defaultFallback, $defaultPath );
			}
		}

		return null;
	}

	/**
	 * Get the full filesystem path for a template slug.
	 *
	 * Follows the fallback chain:
	 * 1. Theme override: {theme}/catalogist/{slug}/catalog.php
	 * 2. Plugin default: templates/{slug}/catalog.php
	 * 3. Built-in fallback: templates/fallback/catalog.php
	 *
	 * @param string $templateSlug Template slug.
	 *
	 * @return string|null Filesystem path or null if not found.
	 */
	public function getPath( string $templateSlug ): ?string {
		// Return cached result if available.
		if ( array_key_exists( $templateSlug, $this->pathCache ) ) {
			return $this->pathCache[ $templateSlug ];
		}

		$slug = sanitize_file_name( $templateSlug );
		$path = $this->resolvePath( $slug );

		$this->pathCache[ $templateSlug ] = $path;

		return $path;
	}

	/**
	 * Clear the template path cache.
	 *
	 * @return void
	 */
	public function clearCache(): void {
		$this->pathCache = array();
	}

	/**
	 * Resolve the filesystem path for a template slug.
	 *
	 * @param string $slug Sanitized template slug.
	 *
	 * @return string|null Resolved path or null.
	 */
	private function resolvePath( string $slug ): ?string {
		$paths = array(
			// Theme override.
			get_template_directory() . '/catalogist/' . $slug . '/catalog.php',
			// Plugin default.
			$this->baseDirectory . '/' . $slug . '/catalog.php',
			// Built-in fallback.
			$this->baseDirectory . '/fallback/catalog.php',
		);

		foreach ( $paths as $path ) {
			if ( file_exists( $path ) ) {
				return $path;
			}
		}

		return null;
	}
}
