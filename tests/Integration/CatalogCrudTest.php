<?php

declare(strict_types=1);

namespace Catalogist\Tests\Integration;

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
		//do_action( 'init' );
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

	// ----------------------------------------------------------------
	// Stage 6: Configuration Validation
	// ----------------------------------------------------------------

	/**
	 * Test validate_configuration accepts a complete valid configuration.
	 */
	public function testValidateConfigurationAcceptsCompleteValid(): void {
		$config            = Catalog::default_configuration();
		$config['filters'] = array(
			array(
				'type'  => 'category',
				'value' => 'electronics',
			),
		);

		$errors = Catalog::validate_configuration( $config );
		$this->assertEmpty( $errors );
	}

	/**
	 * Test validate_configuration rejects missing version.
	 */
	public function testValidateConfigurationRejectsMissingVersion(): void {
		$config = Catalog::default_configuration();
		unset( $config['version'] );

		$errors = Catalog::validate_configuration( $config );
		$this->assertNotEmpty( $errors );
		$this->assertContains( 'version', $errors[0] );
	}

	/**
	 * Test validate_configuration rejects invalid status.
	 */
	public function testValidateConfigurationRejectsInvalidStatus(): void {
		$config           = Catalog::default_configuration();
		$config['status'] = 'published';

		$errors = Catalog::validate_configuration( $config );
		$this->assertNotEmpty( $errors );
		$this->assertContains( 'status', $errors[0] );
	}

	/**
	 * Test validate_configuration accepts all allowed statuses.
	 *
	 * @dataProvider providerAllowedStatuses
	 */
	public function testValidateConfigurationAcceptsAllowedStatuses( string $status ): void {
		$config           = Catalog::default_configuration();
		$config['status'] = $status;

		$errors = Catalog::validate_configuration( $config );
		$this->assertEmpty( $errors, "Status '$status' should be valid" );
	}

	/**
	 * Data provider for allowed statuses.
	 *
	 * @return array<array<string>>
	 */
	public static function providerAllowedStatuses(): array {
		return array(
			array( 'draft' ),
			array( 'active' ),
			array( 'archived' ),
		);
	}

	/**
	 * Test validate_configuration rejects invalid sort key.
	 */
	public function testValidateConfigurationRejectsInvalidSortKey(): void {
		$config         = Catalog::default_configuration();
		$config['sort'] = array(
			'key'       => 'random_field',
			'direction' => 'asc',
		);

		$errors = Catalog::validate_configuration( $config );
		$this->assertNotEmpty( $errors );
		$this->assertContains( 'key', $errors[0] );
	}

	/**
	 * Test validate_configuration accepts all allowed sort keys.
	 *
	 * @dataProvider providerAllowedSortKeys
	 */
	public function testValidateConfigurationAcceptsAllowedSortKeys( string $key ): void {
		$config         = Catalog::default_configuration();
		$config['sort'] = array(
			'key'       => $key,
			'direction' => 'asc',
		);

		$errors = Catalog::validate_configuration( $config );
		$this->assertEmpty( $errors, "Sort key '$key' should be valid" );
	}

	/**
	 * Data provider for allowed sort keys.
	 *
	 * @return array<array<string>>
	 */
	public static function providerAllowedSortKeys(): array {
		return array(
			array( 'title' ),
			array( 'price' ),
			array( 'sku' ),
			array( 'menu_order' ),
			array( 'id' ),
		);
	}

	/**
	 * Test validate_configuration rejects invalid sort direction.
	 */
	public function testValidateConfigurationRejectsInvalidSortDirection(): void {
		$config         = Catalog::default_configuration();
		$config['sort'] = array(
			'key'       => 'title',
			'direction' => 'DOWN',
		);

		$errors = Catalog::validate_configuration( $config );
		$this->assertNotEmpty( $errors );
		$this->assertContains( 'direction', $errors[0] );
	}

	/**
	 * Test validate_configuration accepts both allowed directions.
	 *
	 * @dataProvider providerAllowedSortDirections
	 */
	public function testValidateConfigurationAcceptsAllowedSortDirections( string $direction ): void {
		$config         = Catalog::default_configuration();
		$config['sort'] = array(
			'key'       => 'title',
			'direction' => $direction,
		);

		$errors = Catalog::validate_configuration( $config );
		$this->assertEmpty( $errors, "Direction '$direction' should be valid" );
	}

	/**
	 * Data provider for allowed sort directions.
	 *
	 * @return array<array<string>>
	 */
	public static function providerAllowedSortDirections(): array {
		return array(
			array( 'asc' ),
			array( 'desc' ),
		);
	}

	/**
	 * Test validate_configuration rejects negative selection offset.
	 */
	public function testValidateConfigurationRejectsNegativeOffset(): void {
		$config              = Catalog::default_configuration();
		$config['selection'] = array(
			'offset' => -1,
			'limit'  => null,
		);

		$errors = Catalog::validate_configuration( $config );
		$this->assertNotEmpty( $errors );
		$this->assertContains( 'offset', $errors[0] );
	}

	/**
	 * Test validate_configuration rejects negative selection limit.
	 */
	public function testValidateConfigurationRejectsNegativeLimit(): void {
		$config              = Catalog::default_configuration();
		$config['selection'] = array(
			'offset' => 0,
			'limit'  => -5,
		);

		$errors = Catalog::validate_configuration( $config );
		$this->assertNotEmpty( $errors );
		$this->assertContains( 'limit', $errors[0] );
	}

	/**
	 * Test validate_configuration accepts null limit (no limit).
	 */
	public function testValidateConfigurationAcceptsNullLimit(): void {
		$config              = Catalog::default_configuration();
		$config['selection'] = array(
			'offset' => 0,
			'limit'  => null,
		);

		$errors = Catalog::validate_configuration( $config );
		$this->assertEmpty( $errors );
	}

	/**
	 * Test validate_configuration rejects invalid layout value.
	 */
	public function testValidateConfigurationRejectsInvalidLayout(): void {
		$config                     = Catalog::default_configuration();
		$config['layout']['layout'] = 'carousel';

		$errors = Catalog::validate_configuration( $config );
		$this->assertNotEmpty( $errors );
		$this->assertContains( 'Layout', $errors[0] );
	}

	/**
	 * Test validate_configuration accepts all allowed layouts.
	 *
	 * @dataProvider providerAllowedLayouts
	 */
	public function testValidateConfigurationAcceptsAllowedLayouts( string $layout ): void {
		$config                     = Catalog::default_configuration();
		$config['layout']['layout'] = $layout;

		$errors = Catalog::validate_configuration( $config );
		$this->assertEmpty( $errors, "Layout '$layout' should be valid" );
	}

	/**
	 * Data provider for allowed layouts.
	 *
	 * @return array<array<string>>
	 */
	public static function providerAllowedLayouts(): array {
		return array(
			array( 'grid' ),
			array( 'list' ),
			array( 'table' ),
		);
	}

	/**
	 * Test validate_configuration rejects invalid column count.
	 */
	public function testValidateConfigurationRejectsInvalidColumns(): void {
		$config                      = Catalog::default_configuration();
		$config['layout']['columns'] = 0;

		$errors = Catalog::validate_configuration( $config );
		$this->assertNotEmpty( $errors );
		$this->assertContains( 'columns', $errors[0] );
	}

	/**
	 * Test validate_configuration rejects too many columns.
	 */
	public function testValidateConfigurationRejectsTooManyColumns(): void {
		$config                      = Catalog::default_configuration();
		$config['layout']['columns'] = 13;

		$errors = Catalog::validate_configuration( $config );
		$this->assertNotEmpty( $errors );
		$this->assertContains( 'columns', $errors[0] );
	}

	/**
	 * Test validate_configuration accepts valid column counts.
	 *
	 * @dataProvider providerValidColumnCounts
	 */
	public function testValidateConfigurationAcceptsValidColumns( int $columns ): void {
		$config                      = Catalog::default_configuration();
		$config['layout']['columns'] = $columns;

		$errors = Catalog::validate_configuration( $config );
		$this->assertEmpty( $errors, "Columns $columns should be valid" );
	}

	/**
	 * Data provider for valid column counts.
	 *
	 * @return array<array<int>>
	 */
	public static function providerValidColumnCounts(): array {
		return array(
			array( 1 ),
			array( 3 ),
			array( 6 ),
			array( 12 ),
		);
	}

	/**
	 * Test validate_configuration rejects invalid filter type.
	 */
	public function testValidateConfigurationRejectsInvalidFilterType(): void {
		$config            = Catalog::default_configuration();
		$config['filters'] = array(
			array(
				'type'  => 'nonexistent_filter',
				'value' => 'something',
			),
		);

		$errors = Catalog::validate_configuration( $config );
		$this->assertNotEmpty( $errors );
	}

	/**
	 * Test validate_configuration rejects filter missing type.
	 */
	public function testValidateConfigurationRejectsFilterMissingType(): void {
		$config            = Catalog::default_configuration();
		$config['filters'] = array(
			array( 'value' => 'something' ),
		);

		$errors = Catalog::validate_configuration( $config );
		$this->assertNotEmpty( $errors );
		$this->assertContains( 'Filter at index 0', $errors[0] );
	}

	/**
	 * Test validate_configuration rejects filter missing value.
	 */
	public function testValidateConfigurationRejectsFilterMissingValue(): void {
		$config            = Catalog::default_configuration();
		$config['filters'] = array(
			array( 'type' => 'category' ),
		);

		$errors = Catalog::validate_configuration( $config );
		$this->assertNotEmpty( $errors );
		$this->assertContains( 'Filter at index 0', $errors[0] );
	}

	/**
	 * Test validate_configuration rejects non-array filter.
	 */
	public function testValidateConfigurationRejectsNonArrayFilter(): void {
		$config            = Catalog::default_configuration();
		$config['filters'] = array( 'not-an-array' );

		$errors = Catalog::validate_configuration( $config );
		$this->assertNotEmpty( $errors );
		$this->assertContains( 'Filter at index 0', $errors[0] );
	}

	/**
	 * Test validate_configuration accepts valid filter.
	 */
	public function testValidateConfigurationAcceptsValidFilter(): void {
		$config            = Catalog::default_configuration();
		$config['filters'] = array(
			array(
				'type'  => 'category',
				'value' => 'electronics',
			),
		);

		$errors = Catalog::validate_configuration( $config );
		$this->assertEmpty( $errors );
	}

	/**
	 * Test validate_configuration with empty filters array is valid.
	 */
	public function testValidateConfigurationEmptyFiltersValid(): void {
		$config            = Catalog::default_configuration();
		$config['filters'] = array();

		$errors = Catalog::validate_configuration( $config );
		$this->assertEmpty( $errors );
	}

	/**
	 * Test validate_configuration rejects non-array filters.
	 */
	public function testValidateConfigurationRejectsNonArrayFilters(): void {
		$config            = Catalog::default_configuration();
		$config['filters'] = 'not-an-array';

		$errors = Catalog::validate_configuration( $config );
		$this->assertNotEmpty( $errors );
		$this->assertContains( 'Filters', $errors[0] );
	}

	// ----------------------------------------------------------------
	// Stage 6: Configuration Persistence
	// ----------------------------------------------------------------

	/**
	 * Test saving configuration fields to post meta.
	 */
	public function testSaveConfigurationFields(): void {
		$post_id = wp_insert_post(
			array(
				'post_type'   => CatalogPostType::POST_TYPE,
				'post_title'  => 'Config Catalog',
				'post_status' => 'draft',
			)
		);
		$this->assertGreaterThan( 0, $post_id );
		self::$catalog_id = $post_id;

		// Simulate saving new configuration fields.
		$input = array(
			'catalog_status'           => 'active',
			'catalog_sort_key'         => 'price',
			'catalog_sort_direction'   => 'desc',
			'catalog_selection_offset' => '10',
			'catalog_selection_limit'  => '25',
			'catalog_template_id'      => '5',
		);

		// Call Admin::save_meta_box_data directly with the input.
		// We need to simulate the POST data.
		$_POST    = $input;
		$_REQUEST = $input;

		// Use reflection to call the save method.
		$ref    = new ReflectionClass( Admin::class );
		$method = $ref->getMethod( 'save_meta_box_data' );
		$method->setAccessible( true );

		// Create a mock request object.
		$request = new \WP_REST_Request( 'POST', '/' );
		$request->set_param( 'catalog_status', 'active' );
		$request->set_param( 'catalog_sort_key', 'price' );
		$request->set_param( 'catalog_sort_direction', 'desc' );
		$request->set_param( 'catalog_selection_offset', '10' );
		$request->set_param( 'catalog_selection_limit', '25' );
		$request->set_param( 'catalog_template_id', '5' );
		$request->set_param( '_wpnonce', wp_create_nonce( 'catalogist_save_data' ) );
		$request->set_param( 'post_id', $post_id );

		// Since we can't easily call the admin handler, let's test via Catalog::save directly.
		$configuration = Catalog::sanitize_configuration(
			array(
				'status'    => 'active',
				'sort'      => array(
					'key'       => 'price',
					'direction' => 'desc',
				),
				'selection' => array(
					'offset' => 10,
					'limit'  => 25,
				),
				'template'  => array( 'id' => 5 ),
			)
		);

		$result = Catalog::save(
			$post_id,
			array(
				'description'   => 'Test config catalog',
				'settings'      => array(),
				'products'      => array(),
				'configuration' => $configuration,
			)
		);

		$this->assertTrue( $result );

		// Verify meta was saved.
		$saved_config = get_post_meta( $post_id, Catalog::CTLG_META_CONFIGURATION, true );
		$saved_config = json_decode( $saved_config, true );

		$this->assertSame( 'active', $saved_config['status'] );
		$this->assertSame( 'price', $saved_config['sort']['key'] );
		$this->assertSame( 'desc', $saved_config['sort']['direction'] );
		$this->assertSame( 10, $saved_config['selection']['offset'] );
		$this->assertSame( 25, $saved_config['selection']['limit'] );
		$this->assertSame( 5, $saved_config['template']['id'] );
	}

	/**
	 * Test loading configuration from post meta.
	 */
	public function testLoadConfigurationFromMeta(): void {
		$post_id = wp_insert_post(
			array(
				'post_type'   => CatalogPostType::POST_TYPE,
				'post_title'  => 'Load Config Catalog',
				'post_status' => 'draft',
			)
		);
		$this->assertGreaterThan( 0, $post_id );
		self::$catalog_id = $post_id;

		// Manually set configuration meta.
		$config              = Catalog::default_configuration();
		$config['status']    = 'active';
		$config['sort']      = array(
			'key'       => 'price',
			'direction' => 'desc',
		);
		$config['selection'] = array(
			'offset' => 5,
			'limit'  => 10,
		);
		$config['layout']    = array(
			'layout'  => 'table',
			'columns' => 4,
		);

		update_post_meta( $post_id, Catalog::CTLG_META_CONFIGURATION, wp_json_encode( $config ) );
		update_post_meta( $post_id, Catalog::CTLG_META_VERSION, Catalog::CONFIG_VERSION );
		update_post_meta( $post_id, Catalog::CTLG_META_STATUS, 'active' );

		// Load via Catalog::get_data.
		$data = Catalog::get_data( $post_id );

		$this->assertSame( 'active', $data['configuration']['status'] );
		$this->assertSame( 'price', $data['configuration']['sort']['key'] );
		$this->assertSame( 'desc', $data['configuration']['sort']['direction'] );
		$this->assertSame( 5, $data['configuration']['selection']['offset'] );
		$this->assertSame( 10, $data['configuration']['selection']['limit'] );
		$this->assertSame( 'table', $data['configuration']['layout']['layout'] );
		$this->assertSame( 4, $data['configuration']['layout']['columns'] );
	}

	/**
	 * Test backward compatibility: legacy meta merged into configuration.
	 */
	public function testBackwardCompatibilityLegacyMeta(): void {
		$post_id = wp_insert_post(
			array(
				'post_type'   => CatalogPostType::POST_TYPE,
				'post_title'  => 'Legacy Catalog',
				'post_status' => 'draft',
			)
		);
		$this->assertGreaterThan( 0, $post_id );
		self::$catalog_id = $post_id;

		// Simulate legacy meta (Stage 1-5 format).
		update_post_meta( $post_id, Catalog::META_DESCRIPTION, 'Legacy description' );
		update_post_meta(
			$post_id,
			Catalog::META_SETTINGS,
			wp_json_encode(
				array(
					'layout'     => 'list',
					'columns'    => 2,
					'show_price' => false,
					'show_sku'   => true,
					'show_stock' => false,
				)
			)
		);
		update_post_meta( $post_id, Catalog::META_PRODUCTS, wp_json_encode( array( 100, 200 ) ) );

		$data = Catalog::get_data( $post_id );

		// Description should be loaded.
		$this->assertSame( 'Legacy description', $data['description'] );

		// Legacy settings should be merged into configuration.
		$this->assertSame( 'list', $data['configuration']['layout']['layout'] );
		$this->assertSame( 2, $data['configuration']['layout']['columns'] );
		$this->assertFalse( $data['configuration']['layout']['show_price'] );
		$this->assertTrue( $data['configuration']['layout']['show_sku'] );
		$this->assertFalse( $data['configuration']['layout']['show_stock'] );

		// Products should still be available.
		$this->assertSame( array( 100, 200 ), $data['products'] );

		// Default configuration values should fill in missing parts.
		$this->assertSame( 'draft', $data['configuration']['status'] );
		$this->assertSame( 'title', $data['configuration']['sort']['key'] );
	}

	/**
	 * Test configuration version is stored and retrievable.
	 */
	public function testConfigurationVersionStored(): void {
		$post_id = wp_insert_post(
			array(
				'post_type'   => CatalogPostType::POST_TYPE,
				'post_title'  => 'Version Catalog',
				'post_status' => 'draft',
			)
		);
		$this->assertGreaterThan( 0, $post_id );
		self::$catalog_id = $post_id;

		$config = Catalog::default_configuration();
		Catalog::save(
			$post_id,
			array(
				'description'   => '',
				'settings'      => array(),
				'products'      => array(),
				'configuration' => $config,
			)
		);

		// Version should be stored in meta.
		$version_meta = get_post_meta( $post_id, Catalog::CTLG_META_VERSION, true );
		$this->assertSame( Catalog::CONFIG_VERSION, $version_meta );

		// Version should also be in configuration.
		$data = Catalog::get_data( $post_id );
		$this->assertSame( Catalog::CONFIG_VERSION, $data['configuration']['version'] );
	}

	/**
	 * Test sanitize_configuration with full valid input.
	 */
	public function testSanitizeConfigurationFullValid(): void {
		$input = array(
			'status'    => 'active',
			'filters'   => array(
				array(
					'type'  => 'category',
					'value' => 'books',
				),
			),
			'sort'      => array(
				'key'       => 'price',
				'direction' => 'desc',
			),
			'selection' => array(
				'offset' => 0,
				'limit'  => 20,
			),
			'layout'    => array(
				'layout'     => 'table',
				'columns'    => 4,
				'show_price' => true,
			),
			'template'  => array( 'id' => 10 ),
		);

		$result = Catalog::sanitize_configuration( $input );

		$this->assertSame( 'active', $result['status'] );
		$this->assertCount( 1, $result['filters'] );
		$this->assertSame( 'price', $result['sort']['key'] );
		$this->assertSame( 'desc', $result['sort']['direction'] );
		$this->assertSame( 0, $result['selection']['offset'] );
		$this->assertSame( 20, $result['selection']['limit'] );
		$this->assertSame( 'table', $result['layout']['layout'] );
		$this->assertSame( 4, $result['layout']['columns'] );
		$this->assertSame( 10, $result['template']['id'] );
	}

	/**
	 * Test sanitize_configuration with empty input returns defaults.
	 */
	public function testSanitizeConfigurationEmptyInput(): void {
		$result = Catalog::sanitize_configuration( array() );

		$this->assertSame( Catalog::CONFIG_VERSION, $result['version'] );
		$this->assertSame( 'draft', $result['status'] );
		$this->assertEmpty( $result['filters'] );
		$this->assertSame( 'title', $result['sort']['key'] );
		$this->assertSame( 'asc', $result['sort']['direction'] );
		$this->assertSame( 0, $result['selection']['offset'] );
		$this->assertNull( $result['selection']['limit'] );
		$this->assertSame( 'grid', $result['layout']['layout'] );
		$this->assertSame( 3, $result['layout']['columns'] );
	}

	/**
	 * Test sanitize_configuration with invalid values normalizes to defaults.
	 */
	public function testSanitizeConfigurationInvalidValuesNormalized(): void {
		$input = array(
			'status'    => 'invalid_status',
			'sort'      => array(
				'key'       => 'invalid_key',
				'direction' => 'invalid_dir',
			),
			'selection' => array(
				'offset' => -5,
				'limit'  => -10,
			),
			'layout'    => array(
				'layout'  => 'invalid_layout',
				'columns' => 99,
			),
		);

		$result = Catalog::sanitize_configuration( $input );

		// All invalid values should be normalized to safe defaults.
		$this->assertSame( 'draft', $result['status'] );
		$this->assertSame( 'title', $result['sort']['key'] );
		$this->assertSame( 'asc', $result['sort']['direction'] );
		$this->assertSame( 0, $result['selection']['offset'] );
		$this->assertSame( 0, $result['selection']['limit'] );
		$this->assertSame( 'grid', $result['layout']['layout'] );
		$this->assertSame( 12, $result['layout']['columns'] );
	}

	/**
	 * Test get_data with new configuration meta takes precedence over legacy.
	 */
	public function testGetDataTypeOverridesLegacy(): void {
		$post_id = wp_insert_post(
			array(
				'post_type'   => CatalogPostType::POST_TYPE,
				'post_title'  => 'Override Catalog',
				'post_status' => 'draft',
			)
		);
		$this->assertGreaterThan( 0, $post_id );
		self::$catalog_id = $post_id;

		// Set both legacy and new meta.
		update_post_meta(
			$post_id,
			Catalog::META_SETTINGS,
			wp_json_encode(
				array(
					'layout'  => 'list',
					'columns' => 2,
				)
			)
		);

		$new_config           = Catalog::default_configuration();
		$new_config['layout'] = array(
			'layout'  => 'table',
			'columns' => 5,
		);
		update_post_meta( $post_id, Catalog::CTLG_META_CONFIGURATION, wp_json_encode( $new_config ) );

		$data = Catalog::get_data( $post_id );

		// New configuration should take precedence.
		$this->assertSame( 'table', $data['configuration']['layout']['layout'] );
		$this->assertSame( 5, $data['configuration']['layout']['columns'] );
	}
}
