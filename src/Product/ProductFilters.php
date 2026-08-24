<?php
/**
 * Product filter normalization helpers.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Product;

defined( 'ABSPATH' ) || exit;

/**
 * Normalizes and validates product filter values.
 */
final class ProductFilters {

	/**
	 * Allowed product statuses.
	 *
	 * @var array<string>
	 */
	private const ALLOWED_STATUSES = array(
		'publish',
		'pending',
		'draft',
		'private',
		'trash',
	);

	/**
	 * Allowed stock statuses.
	 *
	 * @var array<string>
	 */
	private const ALLOWED_STOCK_STATUSES = array(
		'instock',
		'outofstock',
		'onbackorder',
	);

	/**
	 * Allowed visibility values.
	 *
	 * @var array<string>
	 */
	private const ALLOWED_VISIBILITY = array(
		'visible',
		'catalog',
		'search',
		'hidden',
	);

	/**
	 * Normalize a product status value.
	 *
	 * @param string $status Status to normalize.
	 *
	 * @return string|null Normalized status or null if invalid.
	 */
	public function normalize_status( string $status ): ?string {
		$status = strtolower( sanitize_text_field( $status ) );

		return in_array( $status, self::ALLOWED_STATUSES, true ) ? $status : null;
	}

	/**
	 * Normalize multiple status values.
	 *
	 * @param array<string> $statuses Statuses to normalize.
	 *
	 * @return array<string>
	 */
	public function normalize_statuses( array $statuses ): array {
		$normalized = array();

		foreach ( $statuses as $status ) {
			$result = $this->normalize_status( $status );
			if ( null !== $result ) {
				$normalized[] = $result;
			}
		}

		return $normalized;
	}

	/**
	 * Normalize a stock status value.
	 *
	 * @param string $status Stock status to normalize.
	 *
	 * @return string|null Normalized stock status or null if invalid.
	 */
	public function normalize_stock_status( string $status ): ?string {
		$status = strtolower( sanitize_text_field( $status ) );

		return in_array( $status, self::ALLOWED_STOCK_STATUSES, true ) ? $status : null;
	}

	/**
	 * Normalize multiple stock status values.
	 *
	 * @param array<string> $statuses Stock statuses to normalize.
	 *
	 * @return array<string>
	 */
	public function normalize_stock_statuses( array $statuses ): array {
		$normalized = array();

		foreach ( $statuses as $status ) {
			$result = $this->normalize_stock_status( $status );
			if ( null !== $result ) {
				$normalized[] = $result;
			}
		}

		return $normalized;
	}

	/**
	 * Normalize a visibility value.
	 *
	 * @param string $visibility Visibility to normalize.
	 *
	 * @return string|null Normalized visibility or null if invalid.
	 */
	public function normalize_visibility( string $visibility ): ?string {
		$visibility = strtolower( sanitize_text_field( $visibility ) );

		return in_array( $visibility, self::ALLOWED_VISIBILITY, true ) ? $visibility : null;
	}

	/**
	 * Normalize multiple visibility values.
	 *
	 * @param array<string> $visibilities Visibilities to normalize.
	 *
	 * @return array<string>
	 */
	public function normalize_visibilities( array $visibilities ): array {
		$normalized = array();

		foreach ( $visibilities as $visibility ) {
			$result = $this->normalize_visibility( $visibility );
			if ( null !== $result ) {
				$normalized[] = $result;
			}
		}

		return $normalized;
	}

	/**
	 * Normalize a category identifier.
	 *
	 * @param int|string $category Category ID or slug.
	 *
	 * @return int|string|null Normalized category or null if invalid.
	 */
	public function normalize_category( $category ) {
		if ( is_numeric( $category ) ) {
			$id = absint( $category );
			return $id > 0 ? $id : null;
		}

		if ( is_string( $category ) ) {
			$slug = sanitize_title( $category );
			return '' !== $slug ? $slug : null;
		}

		return null;
	}

	/**
	 * Normalize multiple category identifiers.
	 *
	 * @param array<int|string> $categories Categories to normalize.
	 *
	 * @return array<int|string>
	 */
	public function normalize_categories( array $categories ): array {
		$normalized = array();

		foreach ( $categories as $category ) {
			$result = $this->normalize_category( $category );
			if ( null !== $result ) {
				$normalized[] = $result;
			}
		}

		return $normalized;
	}

	/**
	 * Normalize a tag identifier.
	 *
	 * @param int|string $tag Tag ID or slug.
	 *
	 * @return int|string|null Normalized tag or null if invalid.
	 */
	public function normalize_tag( $tag ) {
		if ( is_numeric( $tag ) ) {
			$id = absint( $tag );
			return $id > 0 ? $id : null;
		}

		if ( is_string( $tag ) ) {
			$slug = sanitize_title( $tag );
			return '' !== $slug ? $slug : null;
		}

		return null;
	}

	/**
	 * Normalize multiple tag identifiers.
	 *
	 * @param array<int|string> $tags Tags to normalize.
	 *
	 * @return array<int|string>
	 */
	public function normalize_tags( array $tags ): array {
		$normalized = array();

		foreach ( $tags as $tag ) {
			$result = $this->normalize_tag( $tag );
			if ( null !== $result ) {
				$normalized[] = $result;
			}
		}

		return $normalized;
	}

	/**
	 * Get allowed status values.
	 *
	 * @return array<string>
	 */
	public function get_allowed_statuses(): array {
		return self::ALLOWED_STATUSES;
	}

	/**
	 * Get allowed stock status values.
	 *
	 * @return array<string>
	 */
	public function get_allowed_stock_statuses(): array {
		return self::ALLOWED_STOCK_STATUSES;
	}

	/**
	 * Get allowed visibility values.
	 *
	 * @return array<string>
	 */
	public function get_allowed_visibilities(): array {
		return self::ALLOWED_VISIBILITY;
	}
}
