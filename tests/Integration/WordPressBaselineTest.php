<?php

declare(strict_types=1);

use Catalogist\CatalogPostType;
use Catalogist\Plugin;
use PHPUnit\Framework\TestCase;

final class WordPressBaselineTest extends TestCase {

	public function testWordPressIsLoaded(): void {
		$this->assertTrue( defined( 'ABSPATH' ) );
		$this->assertTrue( defined( 'WPINC' ) );
	}

	public function testCatalogistBootstrapRegistersInitializationHook(): void {
		require_once dirname( __DIR__, 2 ) . '/catalogist.php';

		$this->assertSame( 10, has_action( 'init', array( CatalogPostType::class, 'register' ) ) );
	}

	public function testCatalogPostTypeIsRegistered(): void {
		require_once dirname( __DIR__, 2 ) . '/catalogist.php';

		do_action( 'init' );

		$this->assertTrue( post_type_exists( CatalogPostType::POST_TYPE ) );
		$this->assertFalse( is_post_type_viewable( CatalogPostType::POST_TYPE ) );
		$this->assertSame( 'post', get_post_type_object( CatalogPostType::POST_TYPE )->capability_type );

		$labels = get_post_type_object( CatalogPostType::POST_TYPE )->labels;
		$this->assertSame( 'Catalogs', $labels->name );
		$this->assertSame( 'Catalog', $labels->singular_name );
		$this->assertSame( 'Catalogs', $labels->menu_name );
		$this->assertSame( 'Add New Catalog', $labels->add_new_item );
		$this->assertSame( 'Edit Catalog', $labels->edit_item );
		$this->assertSame( 'New Catalog', $labels->new_item );
		$this->assertSame( 'View Catalog', $labels->view_item );
		$this->assertSame( 'Search Catalogs', $labels->search_items );
		$this->assertSame( 'No catalogs found.', $labels->not_found );
		$this->assertSame( 'No catalogs found in Trash.', $labels->not_found_in_trash );
		$this->assertSame( 'All Catalogs', $labels->all_items );
		$this->assertSame( 'Catalog', $labels->name_admin_bar );
	}
}
