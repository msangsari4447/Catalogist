<?php

declare(strict_types=1);

namespace Catalogist;

/**
 * Catalog Context — minimal context value object.
 *
 * Holds contextual metadata required by catalog layers (locale, currency, etc.).
 * No future abstractions; only what is immediately needed.
 */
final class CatalogContext {

	/**
	 * Catalog post ID.
	 *
	 * @var int
	 */
	public int $catalog_id;

	/**
	 * Locale code (e.g., 'en_US').
	 *
	 * @var string
	 */
	public string $language;

	/**
	 * Currency code (e.g., 'USD', 'EUR').
	 *
	 * @var string
	 */
	public string $currency;

	/**
	 * Constructor.
	 *
	 * @param int    $catalog_id Catalog post ID.
	 * @param string $language   Locale code.
	 * @param string $currency   Currency code.
	 */
	public function __construct(
		int $catalog_id,
		string $language,
		string $currency
	) {
		$this->catalog_id = $catalog_id;
		$this->language   = $language;
		$this->currency   = $currency;
	}
}
