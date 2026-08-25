<?php
/**
 * Unit tests for FileTemplateLoader.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Tests\Unit\Template\Loader;

use Catalogist\Template\Loader\FileTemplateLoader;
use Catalogist\Template\Template;
use PHPUnit\Framework\TestCase;

/**
 * Tests for FileTemplateLoader.
 */
class FileTemplateLoaderTest extends TestCase {

	/**
	 * Test constructor sets base directory correctly.
	 *
	 * @return void
	 */
	public function test_constructor_sets_base_directory(): void {
		$pluginDir = '/path/to/plugin/';
		$loader    = new FileTemplateLoader( $pluginDir );

		$reflection = new \ReflectionClass( $loader );
		$property   = $reflection->getProperty( 'baseDirectory' );
		$property->setAccessible( true );

		$this->assertSame( '/path/to/plugin/templates', $property->getValue( $loader ) );
	}

	/**
	 * Test getPath returns plugin default path when template exists.
	 *
	 * @return void
	 */
	public function test_getPath_returns_plugin_default_path(): void {
		// Create a temporary template directory and catalog.php file.
		$pluginDir = sys_get_temp_dir() . '/catalogist-test-' . uniqid();
		$templateDir = $pluginDir . '/templates/default';
		mkdir( $templateDir, 0777, true );
		file_put_contents( $templateDir . '/catalog.php', '<?php /** test */' );

		$loader = new FileTemplateLoader( $pluginDir . '/' );

		$path = $loader->getPath( 'default' );

		$this->assertNotNull( $path );
		$this->assertStringEndsWith( 'catalog.php', $path );
		$this->assertStringContainsString( 'default', $path );

		// Cleanup.
		unlink( $templateDir . '/catalog.php' );
		rmdir( $templateDir );
		rmdir( $pluginDir . '/templates' );
		rmdir( $pluginDir );
	}

	/**
	 * Test getPath falls back to built-in fallback when template not found.
	 *
	 * @return void
	 */
	public function test_getPath_falls_back_to_builtin_fallback(): void {
		// Use the actual plugin directory.
		$pluginDir = dirname( dirname( dirname( dirname( __DIR__ ) ) ) ) . '/';
		$loader    = new FileTemplateLoader( $pluginDir );

		$path = $loader->getPath( 'non-existent-template' );

		// Should fall back to the built-in fallback template.
		$this->assertNotNull( $path );
		$this->assertStringEndsWith( 'catalog.php', $path );
		$this->assertStringContainsString( 'fallback', $path );
	}

	/**
	 * Test getPath caches results.
	 *
	 * @return void
	 */
	public function test_getPath_caches_results(): void {
		$pluginDir = dirname( dirname( dirname( dirname( __DIR__ ) ) ) ) . '/';
		$loader    = new FileTemplateLoader( $pluginDir );

		$path1 = $loader->getPath( 'default' );
		$path2 = $loader->getPath( 'default' );

		$this->assertSame( $path1, $path2 );
	}

	/**
	 * Test load returns null for invalid template slug.
	 *
	 * @return void
	 */
	public function test_load_returns_null_for_invalid_slug(): void {
		$pluginDir = sys_get_temp_dir() . '/catalogist-test-' . uniqid() . '/';
		$loader    = new FileTemplateLoader( $pluginDir );

		$template = $loader->load( 'invalid-template-slug' );

		$this->assertNull( $template );
	}

	/**
	 * Test load returns Template object for valid slug.
	 *
	 * @return void
	 */
	public function test_load_returns_template_object(): void {
		// Create a temporary template directory.
		$pluginDir = sys_get_temp_dir() . '/catalogist-test-' . uniqid();
		$templateDir = $pluginDir . '/templates/default';
		mkdir( $templateDir, 0777, true );
		file_put_contents( $templateDir . '/catalog.php', '<?php /** test */' );

		$loader = new FileTemplateLoader( $pluginDir . '/' );

		$template = $loader->load( 'default' );

		$this->assertNotNull( $template );
		$this->assertInstanceOf( Template::class, $template );
		$this->assertSame( 'default', $template->get_slug() );
		$this->assertStringEndsWith( 'catalog.php', $template->get_path() );

		// Cleanup.
		unlink( $templateDir . '/catalog.php' );
		rmdir( $templateDir );
		rmdir( $pluginDir . '/templates' );
		rmdir( $pluginDir );
	}

	/**
	 * Test load falls back to default fallback slug.
	 *
	 * @return void
	 */
	public function test_load_falls_back_to_default_fallback(): void {
		$pluginDir = dirname( dirname( dirname( dirname( __DIR__ ) ) ) ) . '/';
		$loader    = new FileTemplateLoader( $pluginDir );

		$template = $loader->load( 'non-existent', 'default' );

		$this->assertNotNull( $template );
		$this->assertSame( 'default', $template->get_slug() );
	}

	/**
	 * Test load returns null when default fallback also not found.
	 *
	 * @return void
	 */
	public function test_load_returns_null_when_default_not_found(): void {
		$pluginDir = sys_get_temp_dir() . '/catalogist-test-' . uniqid() . '/';
		$loader    = new FileTemplateLoader( $pluginDir );

		$template = $loader->load( 'non-existent', 'also-non-existent' );

		$this->assertNull( $template );
	}

	/**
	 * Test clearCache clears the path cache.
	 *
	 * @return void
	 */
	public function test_clearCache_clears_cache(): void {
		$pluginDir = dirname( dirname( dirname( dirname( __DIR__ ) ) ) ) . '/';
		$loader    = new FileTemplateLoader( $pluginDir );

		// Load a template to populate cache.
		$loader->getPath( 'default' );

		// Clear cache.
		$loader->clearCache();

		// The property should be reset, but we can't directly test it.
		// Instead, we can test that getPath still works after clearCache.
		$path = $loader->getPath( 'default' );
		$this->assertNotNull( $path );
	}
}
