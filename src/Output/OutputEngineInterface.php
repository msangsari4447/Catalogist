<?php
/**
 * Output engine interface.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Output;

use Catalogist\Catalog\Catalog;
use Catalogist\CatalogItem\CatalogItem;

/**
 * Interface for output engines.
 */
interface OutputEngineInterface {

	/**
	 * Generate output for a catalog.
	 *
	 * @param Catalog                $catalog     Catalog entity.
	 * @param array<CatalogItem>     $items       Normalized catalog items.
	 * @param string                 $format      Output format (OutputFormat constants).
	 * @param array<string, mixed>   $settings    Additional settings.
	 *
	 * @return string Generated output HTML.
	 */
	public function generate(
		Catalog $catalog,
		array $items,
		string $format,
		array $settings = array()
	): string;
}