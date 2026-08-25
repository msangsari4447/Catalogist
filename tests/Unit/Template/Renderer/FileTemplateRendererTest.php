<?php
/**
 * Unit tests for FileTemplateRenderer.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Tests\Unit\Template\Renderer;

use Catalogist\Template\Loader\FileTemplateLoader;
use Catalogist\Template\Renderer\FileTemplateRenderer;
use Catalogist\Template\Template;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests for FileTemplateRenderer.
 */
class FileTemplateRendererTest extends TestCase {

	/**
	 * Mock loader.
	 *
	 * @var FileTemplateLoader&MockObject
	 */
	private $mock_loader;

	/**
	 * Renderer under test.
	 *
	 * @var FileTemplateRenderer
	 */
	private FileTemplateRenderer $renderer;

	/**
	 * Set up test fixtures.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->mock_loader = $this->createMock( FileTemplateLoader::class );
		$this->renderer    = new FileTemplateRenderer( $this->mock_loader );
	}

	/**
	 * Test render loads template path and returns output.
	 *
	 * @return void
	 */
	public function test_render_loads_template_and_returns_output(): void {
		$templatePath = '/path/to/template/catalog.php';
		$template     = new Template( 'default', $templatePath );

		$this->mock_loader
			->method( 'getPath' )
			->with( 'default' )
			->willReturn( $templatePath );

		// Create a temporary template file.
		file_put_contents(
			$templatePath,
			'<?php /** test */ echo "<div class=\"catalogist-catalog\">Test Output</div>";'
		);

		$output = $this->renderer->render( 'default', array( 'catalog' => new stdClass() ) );

		$this->assertStringContainsString( 'Test Output', $output );
		$this->assertStringContainsString( 'catalogist-catalog', $output );

		// Cleanup.
		unlink( $templatePath );
	}

	/**
	 * Test render returns fallback HTML when template not found.
	 *
	 * @return void
	 */
	public function test_render_returns_fallback_when_template_not_found(): void {
		$this->mock_loader
			->method( 'getPath' )
			->willReturn( null );

		$output = $this->renderer->render( 'missing-template', array() );

		$this->assertStringContainsString( 'catalogist-error', $output );
		$this->assertStringContainsString( 'Template not found', $output );
	}

	/**
	 * Test renderSection returns empty string for missing section.
	 *
	 * @return void
	 */
	public function test_renderSection_returns_empty_for_missing_section(): void {
		$templatePath = '/path/to/template/catalog.php';
		$template     = new Template( 'default', $templatePath );

		$this->mock_loader
			->method( 'getPath' )
			->with( 'default' )
			->willReturn( $templatePath );

		// Create a temporary template file without sections.
		file_put_contents(
			$templatePath,
			'<?php /** test */ echo "Main template";'
		);

		$output = $this->renderer->renderSection( 'product-card', array() );

		$this->assertSame( '', $output );

		// Cleanup.
		unlink( $templatePath );
	}

	/**
	 * Test renderSection finds and renders section file.
	 *
	 * @return void
	 */
	public function test_renderSection_finds_and_renders_section(): void {
		$templatePath = '/path/to/template/catalog.php';
		$sectionPath  = '/path/to/template/product-card.php';
		$template     = new Template( 'default', $templatePath );

		$this->mock_loader
			->method( 'getPath' )
			->willReturnMap(
				array(
					array( 'default', $templatePath ),
					array( 'default/product-card', $sectionPath ),
				)
			);

		// Create temporary template files.
		file_put_contents( $templatePath, '<?php /** test */ echo "Main";' );
		file_put_contents(
			$sectionPath,
			'<?php /** test */ echo "<article class=\"product-card\">Product</article>";'
		);

		$output = $this->renderer->renderSection( 'product-card', array() );

		$this->assertStringContainsString( 'product-card', $output );
		$this->assertStringContainsString( 'Product', $output );

		// Cleanup.
		unlink( $templatePath );
		unlink( $sectionPath );
	}

	/**
	 * Test render passes context to template file.
	 *
	 * @return void
	 */
	public function test_render_passes_context_to_template(): void {
		$templatePath = '/path/to/template/catalog.php';
		$template     = new Template( 'default', $templatePath );

		$this->mock_loader
			->method( 'getPath' )
			->with( 'default' )
			->willReturn( $templatePath );

		// Template uses $catalog and $columns from context.
		file_put_contents(
			$templatePath,
			'<?php /** test */
			echo "<div class=\"catalogist-catalog\" data-columns=\"". esc_attr( $columns ) ."\">";
			echo esc_html( $catalog->title );
			echo "</div>";'
		);

		$catalog = new stdClass();
		$catalog->title = 'Test Catalog';

		$output = $this->renderer->render(
			'default',
			array(
				'catalog' => $catalog,
				'columns' => 2,
			)
		);

		$this->assertStringContainsString( 'Test Catalog', $output );
		$this->assertStringContainsString( 'data-columns="2"', $output );

		// Cleanup.
		unlink( $templatePath );
	}

	/**
	 * Test render handles template errors gracefully.
	 *
	 * @return void
	 */
	public function test_render_handles_template_errors_gracefully(): void {
		$templatePath = '/path/to/template/catalog.php';
		$template     = new Template( 'default', $templatePath );

		$this->mock_loader
			->method( 'getPath' )
			->with( 'default' )
			->willReturn( $templatePath );

		// Template with syntax error.
		file_put_contents(
			$templatePath,
			'<?php /** test */ echo "<div";'
		);

		// This should not throw an exception, but return fallback or empty.
		$output = $this->renderer->render( 'default', array() );

		// We expect either fallback or empty output, not a fatal error.
		$this->assertIsString( $output );

		// Cleanup.
		unlink( $templatePath );
	}
}
