<?php
/**
 * Tests for Elementor Widgets.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Tests\Unit\Elementor\Widgets;

use Catalogist\Elementor\Widgets\ProductCardWidget;
use Catalogist\Elementor\Widgets\CatalogWidget;
use Catalogist\Template\TemplateEngineInterface;
use Catalogist\Catalog\CatalogRepository;
use Catalogist\CatalogItem\CatalogItem;
use PHPUnit\Framework\TestCase;

/**
 * Elementor widget tests.
 */
class ElementorWidgetTest extends TestCase {

	/**
	 * Test ProductCardWidget instantiation.
	 */
	public function test_product_card_widget_instantiation(): void {
		$template_engine = $this->createMock( TemplateEngineInterface::class );

		$widget = new ProductCardWidget( $template_engine );

		$this->assertInstanceOf( ProductCardWidget::class, $widget );
	}

	/**
	 * Test ProductCardWidget gets ID.
	 */
	public function test_product_card_widget_get_id(): void {
		$template_engine = $this->createMock( TemplateEngineInterface::class );
		$widget = new ProductCardWidget( $template_engine );

		$reflection = new \ReflectionClass( $widget );
		$property = $reflection->getProperty( 'widget_id' );
		$property->setAccessible( true );
		$this->assertEquals( 'catalogist_product_card', $property->getValue( $widget ) );
	}

	/**
	 * Test CatalogWidget instantiation.
	 */
	public function test_catalog_widget_instantiation(): void {
		$template_engine = $this->createMock( TemplateEngineInterface::class );
		$catalog_repo = new class implements CatalogRepository {
			public function find( int $id ): ?\Catalogist\Catalog\Catalog { return null; }
			public function get_all(): array { return array(); }
		};

		$widget = new CatalogWidget( $template_engine, $catalog_repo );

		$this->assertInstanceOf( CatalogWidget::class, $widget );
	}

	/**
	 * Test CatalogWidget gets ID.
	 */
	public function test_catalog_widget_get_id(): void {
		$template_engine = $this->createMock( TemplateEngineInterface::class );
		$catalog_repo = new class implements CatalogRepository {
			public function find( int $id ): ?\Catalogist\Catalog\Catalog { return null; }
			public function get_all(): array { return array(); }
		};

		$widget = new CatalogWidget( $template_engine, $catalog_repo );

		$reflection = new \ReflectionClass( $widget );
		$property = $reflection->getProperty( 'widget_id' );
		$property->setAccessible( true );
		$this->assertEquals( 'catalogist_catalog', $property->getValue( $widget ) );
	}

	/**
	 * Test ProductCardWidget render with no product ID.
	 */
	public function test_product_card_widget_render_no_id(): void {
		$template_engine = $this->createMock( TemplateEngineInterface::class );
		$widget = new ProductCardWidget( $template_engine );

		// Set up mock widget with no settings.
		$reflection = new \ReflectionClass( $widget );

		// Mock the render method through setting up settings.
		$settings_property = $reflection->getProperty( 'settings_cache' );
		$settings_property->setAccessible( true );
		$settings_property->setValue( $widget, array( 'product_id' => 0 ) );

		// Render should not crash.
		ob_start();
		$widget->render();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'No product ID specified', $output );
	}

	/**
	 * Test CatalogWidget render with no catalog ID.
	 */
	public function test_catalog_widget_render_no_id(): void {
		$template_engine = $this->createMock( TemplateEngineInterface::class );
		$catalog_repo = new class implements CatalogRepository {
			public function find( int $id ): ?\Catalogist\Catalog\Catalog { return null; }
			public function get_all(): array { return array(); }
		};

		$widget = new CatalogWidget( $template_engine, $catalog_repo );

		$reflection = new \ReflectionClass( $widget );
		$settings_property = $reflection->getProperty( 'settings_cache' );
		$settings_property->setAccessible( true );
		$settings_property->setValue( $widget, array( 'catalog_id' => 0 ) );

		ob_start();
		$widget->render();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'No catalog ID specified', $output );
	}

	/**
	 * Test CatalogWidget render with non-existent catalog.
	 */
	public function test_catalog_widget_render_missing_catalog(): void {
		$template_engine = $this->createMock( TemplateEngineInterface::class );
		$catalog_repo = new class implements CatalogRepository {
			public function find( int $id ): ?\Catalogist\Catalog\Catalog { return null; }
			public function get_all(): array { return array(); }
		};

		$widget = new CatalogWidget( $template_engine, $catalog_repo );

		$reflection = new \ReflectionClass( $widget );
		$settings_property = $reflection->getProperty( 'settings_cache' );
		$settings_property->setAccessible( true );
		$settings_property->setValue( $widget, array( 'catalog_id' => 999 ) );

		ob_start();
		$widget->render();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Catalog not found', $output );
	}

	/**
	 * Test widget class names.
	 */
	public function test_widget_class_names(): void {
		$template_engine = $this->createMock( TemplateEngineInterface::class );
		$catalog_repo = new class implements CatalogRepository {
			public function find( int $id ): ?\Catalogist\Catalog\Catalog { return null; }
			public function get_all(): array { return array(); }
		};

		$product_widget = new ProductCardWidget( $template_engine );
		$catalog_widget = new CatalogWidget( $template_engine, $catalog_repo );

		$this->assertEquals( 'Product Card', $product_widget->get_title() );
		$this->assertEquals( 'Catalog', $catalog_widget->get_title() );
	}

	/**
	 * Test widget categories.
	 */
	public function test_widget_categories(): void {
		$template_engine = $this->createMock( TemplateEngineInterface::class );
		$catalog_repo = new class implements CatalogRepository {
			public function find( int $id ): ?\Catalogist\Catalog\Catalog { return null; }
			public function get_all(): array { return array(); }
		};

		$product_widget = new ProductCardWidget( $template_engine );
		$catalog_widget = new CatalogWidget( $template_engine, $catalog_repo );

		$this->assertEquals( 'catalogist', $product_widget->get_category() );
		$this->assertEquals( 'catalogist', $catalog_widget->get_category() );
	}
}
