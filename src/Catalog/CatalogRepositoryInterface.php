<?php
/**
 * Catalog repository interface.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Catalog;

/**
 * Interface for catalog persistence.
 */
interface CatalogRepositoryInterface {

	/**
	 * Find a catalog by ID.
	 *
	 * @param int $id Catalog ID.
	 *
	 * @return Catalog|null
	 */
	public function find( int $id ): ?Catalog;

	/**
	 * Find all catalogs matching criteria.
	 *
	 * @param array<string, mixed> $args Query arguments.
	 *
	 * @return array<Catalog>
	 */
	public function find_by( array $args = array() ): array;

	/**
	 * Save a catalog.
	 *
	 * @param Catalog $catalog Catalog to save.
	 *
	 * @return int|\WP_Error Catalog ID or error.
	 */
	public function save( Catalog $catalog );

	/**
	 * Delete a catalog.
	 *
	 * @param int $id Catalog ID.
	 *
	 * @return bool
	 */
	public function delete( int $id ): bool;
}
