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
	}
}
