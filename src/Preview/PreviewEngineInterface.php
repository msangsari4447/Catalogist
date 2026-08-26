<?php
/**
 * Preview engine interface.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Preview;

use Catalogist\Catalog\Catalog;
use Catalogist\CatalogItem\CatalogItem;

/**
 * Interface for preview rendering engines.
 *
 * Wraps PrintEngineInterface to add a visualization layer.
 */
interface PreviewEngineInterface {

	/**
	 * Generate a preview-ready HTML page for a catalog.
	 *
	 * @param Catalog                  $catalog          Catalog entity.
	 * @param array<CatalogItem>       $catalogItems     Normalized catalog items.
	 * @param array<string, mixed>|null $previewSettings Preview settings override.
	 *
	 * @return string Preview HTML page.
	 */
	public function renderPreview(
		Catalog $catalog,
		array $catalogItems,
		?array $previewSettings = null
	): string;

	/**
	 * Generate the admin preview page URL for a catalog.
	 *
	 * @param int                          $catalogId     Catalog post ID.
	 * @param array<string, mixed>|null     $previewSettings Preview settings override.
	 *
	 * @return string Admin preview URL.
	 */
	public function getPreviewURL( int $catalogId, ?array $previewSettings = null ): string;

	/**
	 * Generate a print-ready URL with current settings encoded.
	 *
	 * @param int                          $catalogId   Catalog post ID.
	 * @param array<string, mixed>|null     $previewSettings Preview settings override.
	 *
	 * @return string Print URL.
	 */
	public function getPrintURL( int $catalogId, ?array $previewSettings = null ): string;

	/**
	 * Check if the preview should show a loading state.
	 *
	 * @return bool
	 */
	public function shouldShowLoading(): bool;

	/**
	 * Get the A4 paper width in millimeters.
	 *
	 * @return int
	 */
	public function getPaperWidthMM(): int;

	/**
	 * Get the A4 paper height in millimeters.
	 *
	 * @return int
	 */
	public function getPaperHeightMM(): int;
}
