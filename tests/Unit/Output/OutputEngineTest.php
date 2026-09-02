<?php
/**
 * Unit tests for OutputEngine.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Tests\Unit\Output;

use Catalogist\Catalog\Catalog;
use Catalogist\CatalogItem\CatalogItem;
use Catalogist\Output\OutputEngine;
use Catalogist\Output\OutputFormat;
use Catalogist\Print\PrintEngineInterface;
use Catalogist\Template\TemplateEngineInterface;
use PHPUnit\Framework\TestCase;

/**
 * Tests for OutputEngine.
 */
class OutputEngineTest extends TestCase {

	/**
	 * Test generate() delegates to print engine for PRINT format.
	 *
	 * @return void
	 */
	public function test_generate_delegates_to_print_engine(): void {
		$mock_print_engine    = $this->createMock( PrintEngineInterface::class );
		$mock_template_engine = $this->createMock( TemplateEngineInterface::class );

		$mock_print_engine
			->expects( $this->once() )
			->method( 'generatePrintHTML' )
			->willReturn( '<html>Print Output</html>' );

		$output_engine = new OutputEngine( $mock_template_engine, $mock_print_engine );

		$catalog = new Catalog(
			1,
			'Test Catalog',
			array(),
			0,
			'publish',
			array()
		);
		$catalog->set_print_settings( array( 'columns' => 2 ) );

		$items = array();

		$result = $output_engine->generate(
			$catalog,
			$items,
			OutputFormat::PRINT,
			array()
		);

		$this->assertEquals( '<html>Print Output</html>', $result );
	}

	/**
	 * Test generate() uses print settings from settings array when provided.
	 *
	 * @return void
	 */
	public function test_generate_uses_settings_print_settings(): void {
		$mock_print_engine    = $this->createMock( PrintEngineInterface::class );
		$mock_template_engine = $this->createMock( TemplateEngineInterface::class );

		$mock_print_engine
			->expects( $this->once() )
			->method( 'generatePrintHTML' )
			->with(
				$this->isInstanceOf( Catalog::class ),
				$this->equalTo( array() ),
				$this->equalTo( array( 'columns' => 4, 'custom' => 'value' ) )
			)
			->willReturn( '<html>Print Output</html>' );

		$output_engine = new OutputEngine( $mock_template_engine, $mock_print_engine );

		$catalog = new Catalog(
			1,
			'Test Catalog',
			array(),
			0,
			'publish',
			array()
		);
		$catalog->set_print_settings( array( 'columns' => 2 ) );

		$result = $output_engine->generate(
			$catalog,
			array(),
			OutputFormat::PRINT,
			array(
				'print_settings' => array( 'columns' => 4, 'custom' => 'value' ),
			)
		);

		$this->assertEquals( '<html>Print Output</html>', $result );
	}

	/**
	 * Test generate() delegates to template engine for HTML format.
	 *
	 * @return void
	 */
	public function test_generate_delegates_to_template_engine(): void {
		$mock_print_engine    = $this->createMock( PrintEngineInterface::class );
		$mock_template_engine = $this->createMock( TemplateEngineInterface::class );

		$mock_template_engine
			->expects( $this->once() )
			->method( 'renderCatalog' )
			->willReturn( '<html>Template Output</html>' );

		$output_engine = new OutputEngine( $mock_template_engine, $mock_print_engine );

		$catalog = new Catalog(
			1,
			'Test Catalog',
			array(),
			0,
			'publish',
			array()
		);
		$catalog->set_layout_settings( array( 'columns' => 2 ) );

		$result = $output_engine->generate(
			$catalog,
			array(),
			OutputFormat::HTML,
			array()
		);

		$this->assertEquals( '<html>Template Output</html>', $result );
	}

	/**
	 * Test generate() uses template settings from settings array when provided.
	 *
	 * @return void
	 */
	public function test_generate_uses_settings_template(): void {
		$mock_print_engine    = $this->createMock( PrintEngineInterface::class );
		$mock_template_engine = $this->createMock( TemplateEngineInterface::class );

		$mock_template_engine
			->expects( $this->once() )
			->method( 'renderCatalog' )
			->with(
				$this->isInstanceOf( Catalog::class ),
				$this->equalTo( array() ),
				$this->equalTo(
					array(
						'template' => 'custom-template',
						'layout'   => array( 'columns' => 3 ),
					)
				)
			)
			->willReturn( '<html>Template Output</html>' );

		$output_engine = new OutputEngine( $mock_template_engine, $mock_print_engine );

		$catalog = new Catalog(
			1,
			'Test Catalog',
			array(),
			0,
			'publish',
			array()
		);
		$catalog->set_layout_settings( array( 'columns' => 2 ) );

		$result = $output_engine->generate(
			$catalog,
			array(),
			OutputFormat::HTML,
			array(
				'template' => 'custom-template',
				'layout'   => array( 'columns' => 3 ),
			)
		);

		$this->assertEquals( '<html>Template Output</html>', $result );
	}

	/**
	 * Test generate() defaults to HTML format for unknown formats.
	 *
	 * @return void
	 */
	public function test_generate_defaults_to_html(): void {
		$mock_print_engine    = $this->createMock( PrintEngineInterface::class );
		$mock_template_engine = $this->createMock( TemplateEngineInterface::class );

		$mock_template_engine
			->expects( $this->once() )
			->method( 'renderCatalog' )
			->willReturn( '<html>Template Output</html>' );

		$mock_print_engine
			->expects( $this->never() )
			->method( 'generatePrintHTML' );

		$output_engine = new OutputEngine( $mock_template_engine, $mock_print_engine );

		$catalog = new Catalog(
			1,
			'Test Catalog',
			array(),
			0,
			'publish',
			array()
		);

		$result = $output_engine->generate(
			$catalog,
			array(),
			'unknown_format',
			array()
		);

		$this->assertEquals( '<html>Template Output</html>', $result );
	}
}