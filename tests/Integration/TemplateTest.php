<?php

declare(strict_types=1);

namespace Catalogist\Tests\Integration;

use Catalogist\CatalogContext;
use Catalogist\CatalogItem;
use Catalogist\Template;
use Catalogist\TemplatePostType;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for Template (Stage 8).
 *
 * Requires WordPress runtime (docker compose).
 */
final class TemplateTest extends TestCase {

	private static int $template_id = 0;

	protected function setUp(): void {
		parent::setUp();
		wp_set_current_user( 1 );
	}

	protected function tearDown(): void {
		if ( self::$template_id > 0 ) {
			wp_delete_post( self::$template_id, true );
			self::$template_id = 0;
		}
	}

	public function testCreateTemplate(): void {
		$post_id = wp_insert_post(
			array(
				'post_type'   => TemplatePostType::POST_TYPE,
				'post_title'  => 'Test Template',
				'post_status' => 'publish',
			)
		);

		$this->assertGreaterThan( 0, $post_id );
		$this->assertSame( 'Test Template', get_the_title( $post_id ) );

		self::$template_id = $post_id;
	}

	public function testSaveAndLoadTemplate(): void {
		$post_id           = wp_insert_post(
			array(
				'post_type'   => TemplatePostType::POST_TYPE,
				'post_title'  => 'Save Template',
				'post_status' => 'draft',
			)
		);
		self::$template_id = $post_id;

		$configuration                      = Template::default_configuration();
		$configuration['header']['enabled'] = false;
		$configuration['loop']['columns']   = 2;
		$configuration['card']['show_sku']  = true;

		$result = Template::save( $post_id, array( 'configuration' => $configuration ) );
		$this->assertTrue( $result );

		$data = Template::get_data( $post_id );
		$this->assertTrue( $data['exists'] );
		$this->assertSame( $post_id, $data['id'] );
		$this->assertFalse( $data['configuration']['header']['enabled'] );
		$this->assertSame( 2, $data['configuration']['loop']['columns'] );
		$this->assertTrue( $data['configuration']['card']['show_sku'] );
	}

	public function testLoadTemplateWithDefaults(): void {
		$post_id           = wp_insert_post(
			array(
				'post_type'   => TemplatePostType::POST_TYPE,
				'post_title'  => 'Defaults Template',
				'post_status' => 'publish',
			)
		);
		self::$template_id = $post_id;

		$data = Template::get_data( $post_id );

		$this->assertTrue( $data['exists'] );
		$this->assertSame( Template::default_configuration(), $data['configuration'] );
	}

	public function testMissingTemplateFallback(): void {
		$data = Template::get_data( 999999 );

		$this->assertFalse( $data['exists'] );
		$this->assertSame( 999999, $data['id'] );
		$this->assertSame( '', $data['title'] );
		$this->assertSame( Template::default_configuration(), $data['configuration'] );
		// No fatal, no exception — fail-safe.
	}

	public function testInvalidPostTypeFallback(): void {
		$post_id   = wp_insert_post(
			array(
				'post_type'   => 'post',
				'post_title'  => 'Not a template',
				'post_status' => 'publish',
			)
		);
		$to_delete = $post_id;

		$data = Template::get_data( $post_id );
		$this->assertFalse( $data['exists'] );
		$this->assertSame( Template::default_configuration(), $data['configuration'] );

		wp_delete_post( $to_delete, true );
	}

	public function testDeletedTemplateFallback(): void {
		$post_id = wp_insert_post(
			array(
				'post_type'   => TemplatePostType::POST_TYPE,
				'post_title'  => 'To Delete',
				'post_status' => 'publish',
			)
		);
		wp_delete_post( $post_id, true );

		$data = Template::get_data( $post_id );
		$this->assertFalse( $data['exists'] );
		$this->assertSame( Template::default_configuration(), $data['configuration'] );
	}

	public function testCorruptJsonFallback(): void {
		$post_id           = wp_insert_post(
			array(
				'post_type'   => TemplatePostType::POST_TYPE,
				'post_title'  => 'Corrupt',
				'post_status' => 'publish',
			)
		);
		self::$template_id = $post_id;

		update_post_meta( $post_id, Template::CTLG_META_CONFIGURATION, 'not-json{{{ ' );

		$data = Template::get_data( $post_id );
		$this->assertTrue( $data['exists'] );
		// Corrupt JSON -> defaults retained, no fatal.
		$this->assertSame( Template::default_configuration(), $data['configuration'] );
	}

	public function testValidateConfigurationAcceptsValid(): void {
		$config = Template::default_configuration();
		$errors = Template::validate_configuration( $config );
		$this->assertEmpty( $errors );
	}

	public function testValidateConfigurationRejectsMissingVersion(): void {
		$config = Template::default_configuration();
		unset( $config['version'] );
		$errors = Template::validate_configuration( $config );
		$this->assertNotEmpty( $errors );
	}

	public function testValidateConfigurationRejectsInvalidStatus(): void {
		$config           = Template::default_configuration();
		$config['status'] = 'nope';
		$errors           = Template::validate_configuration( $config );
		$this->assertNotEmpty( $errors );
	}

	public function testValidateConfigurationRejectsInvalidColumns(): void {
		$config                    = Template::default_configuration();
		$config['loop']['columns'] = 99;
		$errors                    = Template::validate_configuration( $config );
		$this->assertNotEmpty( $errors );
	}

	public function testSanitizeConfigurationEmptyReturnsDefaults(): void {
		$result = Template::sanitize_configuration( array() );
		$this->assertSame( Template::default_configuration(), $result );
	}

	public function testSanitizeConfigurationClampsColumns(): void {
		$result = Template::sanitize_configuration( array( 'loop' => array( 'columns' => 99 ) ) );
		$this->assertSame( 12, $result['loop']['columns'] );

		$result = Template::sanitize_configuration( array( 'loop' => array( 'columns' => 0 ) ) );
		$this->assertSame( 1, $result['loop']['columns'] );
	}

	public function testSanitizeConfigurationNormalizesBooleans(): void {
		$result = Template::sanitize_configuration(
			array(
				'header' => array(
					'enabled'    => 0,
					'show_title' => 1,
				),
				'card'   => array( 'show_sku' => 1 ),
			)
		);
		$this->assertFalse( $result['header']['enabled'] );
		$this->assertTrue( $result['header']['show_title'] );
		$this->assertTrue( $result['card']['show_sku'] );
	}

	public function testBindWithContextAndItem(): void {
		$post_id           = wp_insert_post(
			array(
				'post_type'   => TemplatePostType::POST_TYPE,
				'post_title'  => 'Bind Template',
				'post_status' => 'publish',
			)
		);
		self::$template_id = $post_id;

		$data    = Template::get_data( $post_id );
		$context = new CatalogContext( 1, 'en_US', 'USD' );
		$item    = new CatalogItem( 10, 'simple', null, 'T', 's', 'SKU', 10.0, null, 10.0, 'instock', null, null, array(), array(), $context );

		$bound = Template::bind( $data, $context, $item );

		$this->assertSame( $post_id, $bound['template_id'] );
		$this->assertSame( $context, $bound['context'] );
		$this->assertSame( $item, $bound['item'] );
		$this->assertArrayHasKey( 'header', $bound );
		$this->assertArrayHasKey( 'loop', $bound );
		$this->assertArrayHasKey( 'card', $bound );
		$this->assertArrayHasKey( 'footer', $bound );
		$this->assertTrue( $bound['exists'] );
		$this->assertIsArray( $bound['template'] );
	}

	public function testBindWithMissingTemplateFallback(): void {
		$data    = Template::get_data( 999998 );
		$context = new CatalogContext( 1, 'en_US', 'USD' );

		$bound = Template::bind( $data, $context, null );

		$this->assertFalse( $bound['exists'] );
		$this->assertNull( $bound['item'] );
		$this->assertSame( $context, $bound['context'] );
		$this->assertSame( 3, $bound['loop']['columns'] );
	}

	public function testBindWithInvalidConfigurationSanitized(): void {
		$context = new CatalogContext( 1, 'en_US', 'USD' );
		$data    = array(
			'id'            => 0,
			'configuration' => array(
				'version' => '',
				'status'  => 'bad',
				'header'  => 'not-array',
				'loop'    => array( 'columns' => 99 ),
				'card'    => array( 'show_price' => 'yes' ),
				'footer'  => array( 'enabled' => 'nope' ),
			),
			'exists'        => false,
		);

		$bound = Template::bind( $data, $context, null );

		// Fail-safe sanitized.
		$this->assertSame( 'draft', $bound['template']['status'] );
		$this->assertSame( 12, $bound['loop']['columns'] );
		$this->assertIsBool( $bound['card']['show_price'] );
		$this->assertIsBool( $bound['footer']['enabled'] );
	}

	public function testBindWithoutItem(): void {
		$post_id           = wp_insert_post(
			array(
				'post_type'   => TemplatePostType::POST_TYPE,
				'post_title'  => 'No Item Bind',
				'post_status' => 'publish',
			)
		);
		self::$template_id = $post_id;

		$data    = Template::get_data( $post_id );
		$context = new CatalogContext( 5, 'fa_IR', 'IRR' );

		$bound = Template::bind( $data, $context );

		$this->assertNull( $bound['item'] );
		$this->assertSame( 'fa_IR', $bound['context']->language );
		$this->assertSame( 'IRR', $bound['context']->currency );
	}

	public function testDeleteMeta(): void {
		$post_id           = wp_insert_post(
			array(
				'post_type'   => TemplatePostType::POST_TYPE,
				'post_title'  => 'Delete Meta',
				'post_status' => 'draft',
			)
		);
		self::$template_id = $post_id;

		Template::save( $post_id, array( 'configuration' => Template::default_configuration() ) );
		$this->assertNotSame( '', get_post_meta( $post_id, Template::CTLG_META_CONFIGURATION, true ) );

		Template::delete_meta( $post_id );
		$this->assertSame( '', get_post_meta( $post_id, Template::CTLG_META_CONFIGURATION, true ) );
		$this->assertSame( '', get_post_meta( $post_id, Template::CTLG_META_VERSION, true ) );
		$this->assertSame( '', get_post_meta( $post_id, Template::CTLG_META_STATUS, true ) );
	}

	public function testCptRegistered(): void {
		$post_type_object = get_post_type_object( TemplatePostType::POST_TYPE );
		$this->assertNotNull( $post_type_object );
		$this->assertSame( TemplatePostType::POST_TYPE, $post_type_object->name );
	}

	public function testNoElementorDependencyInTemplate(): void {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$source = file_get_contents( ( new \ReflectionClass( Template::class ) )->getFileName() );
		$this->assertStringNotContainsStringIgnoringCase( 'elementor', $source );
	}

	public function testNoWooCommerceDependencyInTemplate(): void {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$source = file_get_contents( ( new \ReflectionClass( Template::class ) )->getFileName() );
		$this->assertStringNotContainsString( 'wc_get_product', $source );
		$this->assertStringNotContainsString( 'WC_Product', $source );
	}

	public function testNoHtmlInTemplateSource(): void {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$source = file_get_contents( ( new \ReflectionClass( Template::class ) )->getFileName() );
		// Stage 9 owns HTML — Template must not echo/print HTML.
		$this->assertStringNotContainsString( '<div', $source );
		$this->assertStringNotContainsString( 'echo', $source );
	}
}
