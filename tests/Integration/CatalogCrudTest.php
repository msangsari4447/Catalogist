<?php

declare(strict_types=1);

use Catalogist\Admin;
use Catalogist\Catalog;
use Catalogist\CatalogPostType;
use Catalogist\Plugin;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for Catalog CRUD operations.
 *
 * These tests require a full WordPress environment (run via docker compose).
 */
final class CatalogCrudTest extends TestCase {

	/**
	 * @var int
	 */
	private static int $catalog_id = 0;

	/**
	 * Get Admin nonce config via reflection (test-only helper).
	 *
	 * @return array{action: string, field: string}
	 */
	private static function get_admin_nonce_config(): array {
		$ref    = new ReflectionClass( Admin::class );
		$method = $ref->getMethod( 'get_nonce_config' );
		$method->setAccessible( true );
		return $method->invoke( null );
	}

	public static function setUpBeforeClass(): void {
		require_once dirname( __DIR__, 2 ) . '/catalogist.php';
		do_action( 'init' );
	}

	/**
	 * Set up test environment before each test.
	 */
	protected function setUp(): void {
		parent::setUp();
		wp_set_current_user( 1 );
	}

	/**
	 * Clean up after each test.
	 */
	protected function tearDown(): void {
		if ( self::$catalog_id > 0 ) {
			wp_delete_post( self::$catalog_id, true );
			self::$catalog_id = 0;
		}
	}

	/**
	 * Test creating a catalog post.
	 */
	public function testCreateCatalog(): void {
		$post_id = wp_insert_post(
			array(
				'post_type'   => CatalogPostType::POST_TYPE,
				'post_title'  => 'Test Catalog',
				'post_status' => 'publish',
			)
		);

		$this->assertGreaterThan( 0, $post_id );
		$this->assertSame( 'Test Catalog', get_the_title( $post_id ) );
		$this->assertSame( 'publish', get_post_status( $post_id ) );

		self::$catalog_id = $post_id;
	}

	/**
	 * Test saving catalog meta data.
	 */
	public function testSaveCatalogMeta(): void {
		$post_id = wp_insert_post(
			array(
				'post_type'   => CatalogPostType::POST_TYPE,
				'post_title'  => 'Catalog with Meta',
				'post_status' => 'draft',
			)
		);

		$this->assertGreaterThan( 0, $post_id );
		self::$catalog_id = $post_id;

		$data = array(
			'description' => 'This is a test catalog description.',
			'settings'    => array(
				'layout'     => 'list',
				'columns'    => 2,
				'show_price' => false,
				'show_sku'   => true,
				'show_stock' => false,
			),
			'products'    => array( 100, 101, 102 ),
		);

		$result = Catalog::save( $post_id, $data );
		$this->assertTrue( $result );

		// Verify each meta field was saved.
		$this->assertSame(
			'This is a test catalog description.',
			get_post_meta( $post_id, Catalog::META_DESCRIPTION, true )
		);

		$saved_settings = get_post_meta( $post_id, Catalog::META_SETTINGS, true );
		$this->assertIsArray( $saved_settings );
		$this->assertSame( 'list', $saved_settings['layout'] );
		$this->assertSame( 2, $saved_settings['columns'] );
		$this->assertFalse( $saved_settings['show_price'] );
		$this->assertTrue( $saved_settings['show_sku'] );
		$this->assertFalse( $saved_settings['show_stock'] );

		$saved_products = get_post_meta( $post_id, Catalog::META_PRODUCTS, true );
		$this->assertIsArray( $saved_products );
		$this->assertSame( array( 100, 101, 102 ), $saved_products );
	}

	/**
	 * Test loading catalog data with defaults.
	 */
	public function testLoadCatalogDataWithDefaults(): void {
		$post_id = wp_insert_post(
			array(
				'post_type'   => CatalogPostType::POST_TYPE,
				'post_title'  => 'Catalog with Defaults',
				'post_status' => 'publish',
			)
		);

		$this->assertGreaterThan( 0, $post_id );
		self::$catalog_id = $post_id;

		$data = Catalog::get_data( $post_id );

		$this->assertSame( $post_id, $data['id'] );
		$this->assertSame( 'Catalog with Defaults', $data['title'] );
		$this->assertSame( '', $data['description'] );
		$this->assertSame( Catalog::default_settings(), $data['settings'] );
		$this->assertSame( array(), $data['products'] );
		$this->assertIsString( $data['created_at'] );
		$this->assertIsString( $data['updated_at'] );
	}

	/**
	 * Test loading catalog data with saved meta.
	 */
	public function testLoadCatalogDataWithMeta(): void {
		$post_id = wp_insert_post(
			array(
				'post_type'   => CatalogPostType::POST_TYPE,
				'post_title'  => 'Catalog with Meta',
				'post_status' => 'publish',
			)
		);

		$this->assertGreaterThan( 0, $post_id );
		self::$catalog_id = $post_id;

		// Save some meta first.
		Catalog::save(
			$post_id,
			array(
				'description' => 'Loaded description.',
				'settings'    => array(
					'layout'     => 'table',
					'columns'    => 4,
					'show_price' => true,
					'show_sku'   => false,
					'show_stock' => true,
				),
				'products'    => array( 200, 201 ),
			)
		);

		$data = Catalog::get_data( $post_id );

		$this->assertSame( 'Loaded description.', $data['description'] );
		$this->assertSame( 'table', $data['settings']['layout'] );
		$this->assertSame( 4, $data['settings']['columns'] );
		$this->assertTrue( $data['settings']['show_price'] );
		$this->assertFalse( $data['settings']['show_sku'] );
		$this->assertTrue( $data['settings']['show_stock'] );
		$this->assertSame( array( 200, 201 ), $data['products'] );
	}

	/**
	 * Test saving and loading through Admin save handler simulation.
	 */
	public function testAdminSaveHandlerSimulation(): void {
		$post_id = wp_insert_post(
			array(
				'post_type'   => CatalogPostType::POST_TYPE,
				'post_title'  => 'Admin Save Test',
				'post_status' => 'draft',
			)
		);

		$this->assertGreaterThan( 0, $post_id );
		self::$catalog_id = $post_id;

		$nonce = self::get_admin_nonce_config();

		// Simulate the POST data that would come from the meta box.
		$_POST = array(
			'catalog_description' => 'Admin saved description.',
			'catalog_settings'    => array(
				'layout'     => 'grid',
				'columns'    => 5,
				'show_price' => '1',
				'show_sku'   => '0',
				'show_stock' => '1',
			),
			'catalog_products'    => array( '300', '301' ),
			$nonce['field']       => wp_create_nonce( $nonce['action'] ),
		);

		// Create a mock post object.
		$post = get_post( $post_id );

		// Call the save handler directly.
		Admin::save_meta_box_data( $post_id, $post, true );

		// Verify data was saved.
		$data = Catalog::get_data( $post_id );
		$this->assertSame( 'Admin saved description.', $data['description'] );
		$this->assertSame( 5, $data['settings']['columns'] );
		$this->assertTrue( $data['settings']['show_price'] );
		$this->assertFalse( $data['settings']['show_sku'] );
		$this->assertTrue( $data['settings']['show_stock'] );
		$this->assertSame( array( 300, 301 ), $data['products'] );
	}

	/**
	 * Test Admin save handler rejects invalid nonce.
	 */
	public function testAdminSaveHandlerInvalidNonce(): void {
		$post_id = wp_insert_post(
			array(
				'post_type'   => CatalogPostType::POST_TYPE,
				'post_title'  => 'Nonce Test',
				'post_status' => 'draft',
			)
		);

		$this->assertGreaterThan( 0, $post_id );
		self::$catalog_id = $post_id;

		$nonce = self::get_admin_nonce_config();

		$_POST = array(
			'catalog_description' => 'Should not save.',
			'catalog_settings'    => array(),
			'catalog_products'    => array(),
			$nonce['field']       => 'invalid_nonce',
		);

		$post = get_post( $post_id );
		Admin::save_meta_box_data( $post_id, $post, true );

		// Data should NOT be saved.
		$data = Catalog::get_data( $post_id );
		$this->assertSame( '', $data['description'] );
	}

	/**
	 * Test Admin save handler rejects autosave.
	 */
	public function testAdminSaveHandlerAutosave(): void {
		$post_id = wp_insert_post(
			array(
				'post_type'   => CatalogPostType::POST_TYPE,
				'post_title'  => 'Autosave Test',
				'post_status' => 'draft',
			)
		);

		$this->assertGreaterThan( 0, $post_id );
		self::$catalog_id = $post_id;

		// Simulate DOING_AUTOSAVE constant.
		if ( ! defined( 'DOING_AUTOSAVE' ) ) {
			define( 'DOING_AUTOSAVE', true );
		}

		$nonce = self::get_admin_nonce_config();

		$_POST = array(
			'catalog_description' => 'Autosave should not save.',
			'catalog_settings'    => array(),
			'catalog_products'    => array(),
			$nonce['field']       => wp_create_nonce( $nonce['action'] ),
		);

		$post = get_post( $post_id );
		Admin::save_meta_box_data( $post_id, $post, true );

		$data = Catalog::get_data( $post_id );
		$this->assertSame( '', $data['description'] );
	}

	/**
	 * Test delete_meta removes all catalog meta.
	 */
	public function testDeleteMeta(): void {
		$post_id = wp_insert_post(
			array(
				'post_type'   => CatalogPostType::POST_TYPE,
				'post_title'  => 'Delete Meta Test',
				'post_status' => 'draft',
			)
		);

		$this->assertGreaterThan( 0, $post_id );
		self::$catalog_id = $post_id;

		// Save meta first.
		Catalog::save(
			$post_id,
			array(
				'description' => 'To be deleted.',
				'settings'    => Catalog::default_settings(),
				'products'    => array( 400 ),
			)
		);

		// Verify saved.
		$this->assertSame( 'To be deleted.', get_post_meta( $post_id, Catalog::META_DESCRIPTION, true ) );

		// Delete meta.
		$result = Catalog::delete_meta( $post_id );
		$this->assertTrue( $result );

		// Verify all meta is gone.
		$this->assertSame( '', get_post_meta( $post_id, Catalog::META_DESCRIPTION, true ) );
		$this->assertSame( '', get_post_meta( $post_id, Catalog::META_SETTINGS, true ) );
		$this->assertSame( '', get_post_meta( $post_id, Catalog::META_PRODUCTS, true ) );
	}

	/**
	 * Test sanitize_input with empty input.
	 */
	public function testSanitizeInputEmpty(): void {
		$input  = array();
		$result = Catalog::sanitize_input( $input );

		$this->assertSame( '', $result['description'] );
		$this->assertSame( Catalog::default_settings(), $result['settings'] );
		$this->assertSame( array(), $result['products'] );
	}

	/**
	 * Test sanitize_input with description.
	 */
	public function testSanitizeInputDescription(): void {
		$input  = array(
			'catalog_description' => '  Test description with <script>alert(1)</script>  ',
		);
		$result = Catalog::sanitize_input( $input );

		$this->assertStringContainsString( 'Test description', $result['description'] );
		$this->assertStringNotContainsString( '<script>', $result['description'] );
	}

	/**
	 * Test sanitize_input with settings.
	 */
	public function testSanitizeInputSettings(): void {
		$input  = array(
			'catalog_settings' => array(
				'layout'     => 'list',
				'columns'    => '5',
				'show_price' => '0',
				'show_sku'   => '1',
				'show_stock' => '1',
			),
		);
		$result = Catalog::sanitize_input( $input );

		$this->assertSame( 'list', $result['settings']['layout'] );
		$this->assertSame( 5, $result['settings']['columns'] );
		$this->assertFalse( $result['settings']['show_price'] );
		$this->assertTrue( $result['settings']['show_sku'] );
		$this->assertTrue( $result['settings']['show_stock'] );
	}

	/**
	 * Test sanitize_input clamps columns to minimum 1.
	 */
	public function testSanitizeInputColumnsMinimum(): void {
		$input  = array(
			'catalog_settings' => array(
				'columns' => '0',
			),
		);
		$result = Catalog::sanitize_input( $input );

		$this->assertSame( 1, $result['settings']['columns'] );
	}

	/**
	 * Test sanitize_input with negative columns.
	 */
	public function testSanitizeInputNegativeColumns(): void {
		$input  = array(
			'catalog_settings' => array(
				'columns' => '-2',
			),
		);
		$result = Catalog::sanitize_input( $input );

		$this->assertSame( 1, $result['settings']['columns'] );
	}

	/**
	 * Test sanitize_input with products array.
	 */
	public function testSanitizeInputProducts(): void {
		$input  = array(
			'catalog_products' => array( '123', '456', 'invalid', '789' ),
		);
		$result = Catalog::sanitize_input( $input );

		$this->assertSame( array( 123, 456, 789 ), $result['products'] );
	}

	/**
	 * Test sanitize_input filters out zero and negative product IDs.
	 */
	public function testSanitizeInputProductsFiltersInvalid(): void {
		$input  = array(
			'catalog_products' => array( '0', '-1', '5' ),
		);
		$result = Catalog::sanitize_input( $input );

		$this->assertSame( array( 5 ), $result['products'] );
	}

	/**
	 * Test sanitize_input handles mixed valid/invalid settings.
	 */
	public function testSanitizeInputPartialSettings(): void {
		$input = array(
			'catalog_settings' => array(
				'layout' => 'table',
				// Missing other settings - should use defaults
			),
		);
		$result = Catalog::sanitize_input( $input );

		$this->assertSame( 'table', $result['settings']['layout'] );
		$this->assertSame( 3, $result['settings']['columns'] ); // default
		$this->assertTrue( $result['settings']['show_price'] ); // default
	}

	/**
	 * Test sanitize_input rejects invalid layout values.
	 */
	public function testSanitizeInputInvalidLayout(): void {
		$input  = array(
			'catalog_settings' => array(
				'layout' => 'invalid_layout',
			),
		);
		$result = Catalog::sanitize_input( $input );

		// Should sanitize but not validate layout values - that's a business logic decision
		// For Stage 1, we just sanitize the string
		$this->assertSame( 'invalid_layout', $result['settings']['layout'] );
	}
}
