<?php
/**
 * Tests for Elementor functions.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Tests\Unit\Elementor;

use Catalogist\CatalogItem\CatalogItem;
use PHPUnit\Framework\TestCase;

/**
 * Elementor functions tests.
 */
class ElementorFunctionsTest extends TestCase {

	/**
	 * Test catalogist_get_catalog_item returns null when no factory registered.
	 */
	public function test_get_catalog_item_returns_null_without_factory(): void {
		// When no factory is registered, should return null.
		$result = catalogist_get_catalog_item( 123 );
		$this->assertNull( $result );
	}

	/**
	 * Test catalogist_get_catalog_item with parent ID.
	 */
	public function test_get_catalog_item_with_parent(): void {
		// Should handle parent_product_id parameter.
		$result = catalogist_get_catalog_item( 456, 123 );
		$this->assertNull( $result ); // No factory registered
	}

	/**
	 * Test function signature accepts array context.
	 */
	public function test_get_catalog_item_with_context(): void {
		// Should handle context parameter.
		$result = catalogist_get_catalog_item( 789, 0, array( 'test' => 'value' ) );
		$this->assertNull( $result ); // No factory registered
	}
}
