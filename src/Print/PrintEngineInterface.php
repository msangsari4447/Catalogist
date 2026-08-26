<?php
/**
 * Print engine interface.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Print;

use Catalogist\Catalog\Catalog;
use Catalogist\CatalogItem\CatalogItem;

/**
 * Interface for print rendering engines.
 */
interface PrintEngineInterface {

	/**
	 * Generate print-ready HTML for a catalog.
	 *
	 * @param Catalog                  $catalog       Catalog entity.
	 * @param array<CatalogItem>       $catalogItems  Normalized catalog items.
	 * @param array<string, mixed>|null $printSettings Print settings override.
	 *
	 * @return string Print-ready HTML.
	 */
	public function generatePrintHTML(
		Catalog $catalog,
		array $catalogItems,
		?array $printSettings = null
	): string;

	/**
	 * Generate print CSS based on settings.
	 *
	 * @param array<string, mixed> $settings Print settings.
	 *
	 * @return string Generated CSS string.
	 */
	public function generatePrintCSS( array $settings ): string;

	/**
	 * Generate a print preview URL for a catalog.
	 *
	 * @param int                          $catalogId   Catalog post ID.
	 * @param array<string, mixed>|null     $printSettings Print settings override.
	 *
	 * @return string Print preview URL.
	 */
	public function generatePrintPreviewURL( int $catalogId, ?array $printSettings = null ): string;
}
