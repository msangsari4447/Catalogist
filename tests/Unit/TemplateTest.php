<?php

declare(strict_types=1);

namespace Catalogist\Tests\Unit;

use Catalogist\Template;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Template (Stage 8) — pure PHP, no WordPress.
 */
final class TemplateTest extends TestCase {

	public function testConfigVersionConstant(): void {
		$this->assertIsString( Template::CONFIG_VERSION );
		$this->assertMatchesRegularExpression( '/^\d+\.\d+\.\d+$/', Template::CONFIG_VERSION );
	}

	public function testDefaultConfigurationStructure(): void {
		$config = Template::default_configuration();

		$this->assertIsArray( $config );
		$this->assertArrayHasKey( 'version', $config );
		$this->assertArrayHasKey( 'status', $config );
		$this->assertArrayHasKey( 'header', $config );
		$this->assertArrayHasKey( 'footer', $config );
		$this->assertArrayHasKey( 'loop', $config );
		$this->assertArrayHasKey( 'card', $config );

		$this->assertSame( Template::CONFIG_VERSION, $config['version'] );
		$this->assertSame( 'draft', $config['status'] );

		// Header.
		$this->assertTrue( $config['header']['enabled'] );
		$this->assertTrue( $config['header']['show_title'] );

		// Footer.
		$this->assertTrue( $config['footer']['enabled'] );

		// Loop.
		$this->assertSame( 3, $config['loop']['columns'] );

		// Card.
		$this->assertTrue( $config['card']['show_image'] );
		$this->assertTrue( $config['card']['show_title'] );
		$this->assertTrue( $config['card']['show_price'] );
		$this->assertFalse( $config['card']['show_sku'] );
		$this->assertFalse( $config['card']['show_stock'] );
	}

	public function testDefaultConfigurationVersionMatchesConstant(): void {
		$config = Template::default_configuration();
		$this->assertSame( Template::CONFIG_VERSION, $config['version'] );
	}

	public function testMetaKeys(): void {
		$keys = Template::meta_keys();
		$this->assertCount( 3, $keys );
		$this->assertContains( 'ctlg_template_configuration', $keys );
		$this->assertContains( 'ctlg_template_version', $keys );
		$this->assertContains( 'ctlg_template_status', $keys );
	}

	public function testApplyDefaultsPreservesProvidedValues(): void {
		$defaults = Template::default_configuration();
		$provided = array(
			'status' => 'active',
			'header' => array( 'enabled' => false ),
			'loop'   => array( 'columns' => 5 ),
		);

		$result = Template::apply_defaults( $defaults, $provided );

		$this->assertSame( 'active', $result['status'] );
		$this->assertFalse( $result['header']['enabled'] );
		$this->assertTrue( $result['header']['show_title'] );
		$this->assertSame( 5, $result['loop']['columns'] );
		$this->assertTrue( $result['card']['show_price'] );
	}

	public function testApplyDefaultsDoesNotMutateDefaults(): void {
		$before = Template::default_configuration();
		$result = Template::apply_defaults( $before, array( 'status' => 'active' ) );

		$this->assertSame( 'draft', $before['status'] );
		$this->assertSame( 'active', $result['status'] );
	}

	public function testApplyDefaultsRecursiveMerge(): void {
		$defaults = Template::default_configuration();
		$provided = array( 'card' => array( 'show_sku' => true ) );

		$result = Template::apply_defaults( $defaults, $provided );

		$this->assertTrue( $result['card']['show_sku'] );
		$this->assertTrue( $result['card']['show_price'] );
		$this->assertSame( 3, $result['loop']['columns'] );
	}

	public function testApplyDefaultsRetainsMissingKeys(): void {
		$result = Template::apply_defaults( Template::default_configuration(), array( 'status' => 'archived' ) );
		$this->assertSame( 'archived', $result['status'] );
		$this->assertSame( 3, $result['loop']['columns'] );
	}

	public function testTemplateClassIsFinal(): void {
		$ref = new \ReflectionClass( Template::class );
		$this->assertTrue( $ref->isFinal() );
	}

	public function testAllowedStatusesConstant(): void {
		$ref      = new \ReflectionClass( Template::class );
		$constant = $ref->getConstant( 'ALLOWED_STATUSES' );
		$this->assertSame( array( 'draft', 'active', 'archived' ), $constant );
	}

	public function testNoHtmlRenderingMethodExists(): void {
		$ref     = new \ReflectionClass( Template::class );
		$methods = array_map( static fn( $m ) => $m->getName(), $ref->getMethods() );
		$this->assertNotContains( 'render', $methods );
		$this->assertNotContains( 'to_html', $methods );
		$this->assertNotContains( 'toHtml', $methods );
	}

	public function testNoElementorDependency() {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$source = file_get_contents( ( new \ReflectionClass( Template::class ) )->getFileName() );
		// Core must not depend on Elementor classes/providers.
		$this->assertStringNotContainsStringIgnoringCase( 'elementor', $source );
	}

	public function testNoWooCommerceDependency(): void {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$source = file_get_contents( ( new \ReflectionClass( Template::class ) )->getFileName() );
		// Direct WC coupling would be wc_get_product / WC_Product
		$this->assertStringNotContainsString( 'wc_get_product', $source );
		$this->assertStringNotContainsString( 'WC_Product', $source );
	}
}
