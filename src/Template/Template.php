<?php
/**
 * Template value object.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Template;

/**
 * Immutable value object representing a template definition.
 *
 * A template may be identified by slug, filesystem path, or post ID.
 */
final class Template {

	/**
	 * Template slug or identifier.
	 *
	 * @var string
	 */
	private string $slug;

	/**
	 * Full filesystem path to the template, or empty string if not file-based.
	 *
	 * @var string
	 */
	private string $path;

	/**
	 * Post ID if template is stored as a WordPress post (CPT).
	 *
	 * @var int
	 */
	private int $post_id;

	/**
	 * Template name for human display.
	 *
	 * @var string
	 */
	private string $name;

	/**
	 * Constructor.
	 *
	 * @param string $slug  Template slug.
	 * @param string $path  Filesystem path or empty string.
	 * @param int    $post_id Post ID or 0.
	 * @param string $name  Human-readable name.
	 */
	public function __construct(
		string $slug,
		string $path = '',
		int $post_id = 0,
		string $name = ''
	) {
		$this->slug    = $slug;
		$this->path    = $path;
		$this->post_id = $post_id;
		$this->name    = $name ?: $slug;
	}

	/**
	 * Get the template slug.
	 *
	 * @return string
	 */
	public function get_slug(): string {
		return $this->slug;
	}

	/**
	 * Get the filesystem path.
	 *
	 * @return string
	 */
	public function get_path(): string {
		return $this->path;
	}

	/**
	 * Check if this template has a filesystem path.
	 *
	 * @return bool
	 */
	public function has_path(): bool {
		return '' !== $this->path;
	}

	/**
	 * Get the post ID.
	 *
	 * @return int
	 */
	public function get_post_id(): int {
		return $this->post_id;
	}

	/**
	 * Get the human-readable name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return $this->name;
	}

	/**
	 * Check if this is a built-in fallback template.
	 *
	 * @return bool
	 */
	public function is_fallback(): bool {
		return '' === $this->path && 0 === $this->post_id;
	}
}
