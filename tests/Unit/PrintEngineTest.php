<?php

declare(strict_types=1);

namespace Catalogist\Tests\Unit;

use Catalogist\PrintEngine;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for PrintEngine (Stage 10).
 */
final class PrintEngineTest extends TestCase {

	private PrintEngine $print_engine;

	protected function setUp(): void {
		parent::setUp();
		$this->print_engine = new PrintEngine();
	}

	public function testDefaultConfigurationIsA4PortraitLtr(): void {
		$config = PrintEngine::default_configuration();

		$this->assertSame( 'A4', $config['page_size'] );
		$this->assertSame( 'portrait', $config['orientation'] );
		$this->assertSame( 'ltr', $config['direction'] );
		$this->assertSame(
			array(
				'top'    => 10,
				'right'  => 10,
				'bottom' => 10,
				'left'   => 10,
			),
			$config['margins']
		);
	}

	public function testDefaultConfigurationIsValid(): void {
		$this->assertSame( array(), PrintEngine::validate_configuration( PrintEngine::default_configuration() ) );
	}

	public function testSanitizeConfigurationUsesOnlySupportedValues(): void {
		$config = PrintEngine::sanitize_configuration(
			array(
				'page_size'   => 'A3',
				'orientation' => 'landscape',
				'direction'   => 'rtl',
				'margins'     => array(
					'top'    => 5,
					'right'  => 12.5,
					'bottom' => 60,
					'left'   => 'invalid',
				),
			)
		);

		$this->assertSame( 'A4', $config['page_size'] );
		$this->assertSame( 'landscape', $config['orientation'] );
		$this->assertSame( 'rtl', $config['direction'] );
		$this->assertSame( 5, $config['margins']['top'] );
		$this->assertSame( 12.5, $config['margins']['right'] );
		$this->assertSame( 10, $config['margins']['bottom'] );
		$this->assertSame( 10, $config['margins']['left'] );
	}

	public function testValidationRejectsUnsupportedPageAndDirectionValues(): void {
		$errors = PrintEngine::validate_configuration(
			array(
				'page_size'   => 'A3',
				'direction'   => 'invalid',
				'margins'     => PrintEngine::default_configuration()['margins'],
				'orientation' => 'portrait',
			)
		);

		$this->assertCount( 2, $errors );
	}

	public function testValidationRejectsUnsafeMarginValues(): void {
		$errors = PrintEngine::validate_configuration(
			array(
				'page_size'   => 'A4',
				'orientation' => 'portrait',
				'direction'   => 'ltr',
				'margins'     => array(
					'top'    => '10mm; color:red',
					'right'  => -1,
					'bottom' => 51,
					'left'   => INF,
				),
			)
		);

		$this->assertCount( 4, $errors );
	}

	public function testRenderWrapsRendererHtmlAndAddsPrintStyles(): void {
		$html = $this->print_engine->render( '<div class="catalogist-loop">Catalog</div>' );

		$this->assertStringContainsString( 'class="catalogist-print"', $html );
		$this->assertStringContainsString( 'data-page-size="A4"', $html );
		$this->assertStringContainsString( 'data-orientation="portrait"', $html );
		$this->assertStringContainsString( '<style media="print"', $html );
		$this->assertStringContainsString( '<div class="catalogist-loop">Catalog</div>', $html );
	}

	public function testPrintStylesDefineA4PageAndMargins(): void {
		$css = $this->print_engine->get_print_css(
			array(
				'margins' => array(
					'top'    => 8,
					'right'  => 9,
					'bottom' => 10,
					'left'   => 11,
				),
			)
		);

		$this->assertStringContainsString( '@page { size: A4 portrait; margin: 8mm 9mm 10mm 11mm; }', $css );
		$this->assertStringContainsString( '<style media="print"', $css );
	}

	public function testPrintStylesSupportLandscapeAndRtl(): void {
		$css  = $this->print_engine->get_print_css(
			array(
				'orientation' => 'landscape',
				'direction'   => 'rtl',
			)
		);
		$html = $this->print_engine->render(
			'Catalog',
			array(
				'orientation' => 'landscape',
				'direction'   => 'rtl',
			)
		);

		$this->assertStringContainsString( '@page { size: A4 landscape;', $css );
		$this->assertStringContainsString( '.catalogist-print { direction: rtl; }', $css );
		$this->assertStringContainsString( 'dir="rtl"', $html );
		$this->assertStringContainsString( 'data-orientation="landscape"', $html );
	}

	public function testPrintStylesPreventCardAndSectionBreaks(): void {
		$css = $this->print_engine->get_print_css();

		$this->assertStringContainsString( '.catalogist-print .catalogist-card {', $css );
		$this->assertStringContainsString( 'break-inside: avoid;', $css );
		$this->assertStringContainsString( 'page-break-inside: avoid;', $css );
		$this->assertStringContainsString( '.catalogist-print .catalogist-header {', $css );
		$this->assertStringContainsString( 'break-after: avoid;', $css );
		$this->assertStringContainsString( '.catalogist-print .catalogist-footer {', $css );
		$this->assertStringContainsString( 'break-before: avoid;', $css );
	}

	public function testUntrustedConfigurationCannotInjectCss(): void {
		$html = $this->print_engine->render(
			'Catalog',
			array(
				'orientation' => 'portrait; color: red',
				'direction'   => 'rtl; color: red',
				'margins'     => array( 'top' => '1mm; color:red' ),
			)
		);

		$this->assertStringNotContainsString( 'color: red', $html );
		$this->assertStringContainsString( 'size: A4 portrait;', $html );
		$this->assertStringContainsString( 'direction: ltr;', $html );
	}

	public function testPrintEngineIsFinalAndStateless(): void {
		$reflection = new \ReflectionClass( PrintEngine::class );

		$this->assertTrue( $reflection->isFinal() );
		$this->assertCount( 0, $reflection->getProperties() );
	}
}
