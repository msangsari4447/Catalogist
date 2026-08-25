<?php
/**
 * Template context builder interface.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Template;

use Catalogist\Catalog\Catalog;
use Catalogist\CatalogItem\CatalogItem;

/**
 * Interface for building template context arrays from catalog data.
 *
 * Decouples template rendering from catalog data structures.
 * Provides both raw data and pre-escaped helpers for template designers.
 */
interface TemplateContextBuilderInterface {

	/**
	 * Build the main template context for a catalog.
	 *
	 * @param Catalog                    $catalog       Catalog entity.
	 * @param array<CatalogItem>         $catalogItems  Normalized catalog items.
	 * @param array<string, mixed>|null  $layoutSettings Layout settings override.
	 * @param array<string, mixed>|null  $printSettings  Print settings override.
	 *
	 * @return array<string, mixed> Template context array.
	 */
	public function build(
		Catalog $catalog,
		array $catalogItems,
		?array $layoutSettings = null,
		?array $printSettings = null
	): array;

	/**
	 * Build loop context for a single item within a catalog iteration.
	 *
	 * Provides index metadata and helper booleans for loop positioning.
	 *
	 * @param Catalog      $catalog Catalog entity.
	 * @param CatalogItem  $item    Current catalog item.
	 * @param int          $index   Zero-based loop index.
	 * @param int          $count   Total item count.
	 *
	 * @return array<string, mixed> Loop context array.
	 */
	public function buildLoopContext(
		Catalog $catalog,
		CatalogItem $item,
		int $index,
		int $count
	): array;
}
