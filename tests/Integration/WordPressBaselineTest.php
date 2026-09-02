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

		CatalogPostType::register();

		$this->assertTrue( post_type_exists( CatalogPostType::POST_TYPE ) );
		$this->assertFalse( is_post_type_viewable( CatalogPostType::POST_TYPE ) );
		$this->assertSame( 'post', get_post_type_object( CatalogPostType::POST_TYPE )->capability_type );
	}

	public function testCatalogEntityCanBeCreatedAndRetrieved(): void {

		require_once dirname( __DIR__, 2 ) . '/catalogist.php';

		CatalogPostType::register();

		$post_id = wp_insert_post(
			array(
				'post_type'   => CatalogPostType::POST_TYPE,
				'post_title'  => 'Integration Test Catalog',
				'post_status' => 'draft',
			),
			true
		);

		$this->assertIsInt( $post_id );
		$this->assertGreaterThan( 0, $post_id );

		$catalog = get_post( $post_id );

		$this->assertNotNull( $catalog );
		$this->assertSame( CatalogPostType::POST_TYPE, $catalog->post_type );
		$this->assertSame( 'Integration Test Catalog', $catalog->post_title );
		$this->assertSame( 'draft', $catalog->post_status );

		wp_delete_post( $post_id, true );
	}
	public function testCatalogLifecycleCanBeManagedByWordPress(): void {

		require_once dirname( __DIR__, 2 ) . '/catalogist.php';

		if ( ! post_type_exists( CatalogPostType::POST_TYPE ) ) {
			CatalogPostType::register();
		}

		$post_id = wp_insert_post(
			array(
				'post_type'   => CatalogPostType::POST_TYPE,
				'post_title'  => 'Lifecycle Test Catalog',
				'post_status' => 'draft',
			),
			true
		);

		$this->assertIsInt( $post_id );
		$this->assertGreaterThan( 0, $post_id );

		$catalog = get_post( $post_id );

		$this->assertNotNull( $catalog );
		$this->assertSame( 'draft', $catalog->post_status );

		$updated = wp_update_post(
			array(
				'ID'          => $post_id,
				'post_status' => 'publish',
			),
			true
		);

		$this->assertSame( $post_id, $updated );

		$published_catalog = get_post( $post_id );

		$this->assertNotNull( $published_catalog );
		$this->assertSame( CatalogPostType::POST_TYPE, $published_catalog->post_type );
		$this->assertSame( 'publish', $published_catalog->post_status );

		$deleted = wp_delete_post( $post_id, true );

		$this->assertNotNull( $deleted );
		$this->assertNull( get_post( $post_id ) );
	}
	public function testCatalogUsesStandardPostCapabilities(): void {

		require_once dirname( __DIR__, 2 ) . '/catalogist.php';

		if ( ! post_type_exists( CatalogPostType::POST_TYPE ) ) {
			CatalogPostType::register();
		}

		$post_type = get_post_type_object( CatalogPostType::POST_TYPE );

		$this->assertNotNull( $post_type );
		$this->assertSame( 'post', $post_type->capability_type );
		$this->assertTrue( $post_type->map_meta_cap );

		$this->assertSame( 'edit_post', $post_type->cap->edit_post );
		$this->assertSame( 'delete_post', $post_type->cap->delete_post );
		$this->assertSame( 'publish_posts', $post_type->cap->publish_posts );
	}
}
