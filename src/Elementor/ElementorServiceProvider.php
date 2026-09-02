<?php
/**
 * Elementor service provider.
 *
 * Conditionally loads Elementor integration only when Elementor is active.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Elementor;

use Catalogist\Core\Container;
use Catalogist\Core\ServiceProviderInterface;
use Catalogist\Elementor\Widgets\ProductCardWidget;
use Catalogist\Elementor\Widgets\CatalogWidget;
use Catalogist\Support\Logger;

defined( 'ABSPATH' ) || exit;

/**
 * Elementor service provider.
 *
 * Registers Elementor-specific services and hooks into Elementor's
 * widget and dynamic tag systems when Elementor is active.
 */
class ElementorServiceProvider implements ServiceProviderInterface {

	/**
	 * Plugin directory path.
	 *
	 * @var string
	 */
	private string $plugin_dir;

	/**
	 * Constructor.
	 *
	 * @param string $plugin_dir Plugin directory path.
	 */
	public function __construct( string $plugin_dir ) {
		$this->plugin_dir = rtrim( $plugin_dir, '/\\' );
	}

	/**
	 * Register services with the container.
	 *
	 * @param Container $container Service container.
	 *
	 * @return void
	 */
	public function register( Container $container ): void {
		// Register Elementor-specific configurations.
		$container->set( 'elementor.dynamic_tags', array(
			'catalogist_product_name'           => '\Catalogist\Elementor\DynamicTags\ProductNameDynamicTag',
			'catalogist_product_sku'            => '\Catalogist\Elementor\DynamicTags\ProductSkuDynamicTag',
			'catalogist_product_price'          => '\Catalogist\Elementor\DynamicTags\ProductPriceDynamicTag',
			'catalogist_product_image'          => '\Catalogist\Elementor\DynamicTags\ProductImageDynamicTag',
			'catalogist_product_url'            => '\Catalogist\Elementor\DynamicTags\ProductUrlDynamicTag',
			'catalogist_product_description'    => '\Catalogist\Elementor\DynamicTags\ProductDescriptionDynamicTag',
			'catalogist_product_categories'     => '\Catalogist\Elementor\DynamicTags\ProductCategoriesDynamicTag',
			'catalogist_product_attributes'     => '\Catalogist\Elementor\DynamicTags\ProductAttributesDynamicTag',
			'catalogist_product_stock_status'   => '\Catalogist\Elementor\DynamicTags\ProductStockStatusDynamicTag',
			'catalogist_product_qr_code'        => '\Catalogist\Elementor\DynamicTags\ProductQrCodeDynamicTag',
			'catalogist_variation_name'         => '\Catalogist\Elementor\DynamicTags\VariationNameDynamicTag',
			'catalogist_variation_sku'          => '\Catalogist\Elementor\DynamicTags\VariationSkuDynamicTag',
			'catalogist_variation_price'        => '\Catalogist\Elementor\DynamicTags\VariationPriceDynamicTag',
			'catalogist_variation_attributes'   => '\Catalogist\Elementor\DynamicTags\VariationAttributesDynamicTag',
			'catalogist_catalog_title'          => '\Catalogist\Elementor\DynamicTags\CatalogTitleDynamicTag',
			'catalogist_catalog_product_count'  => '\Catalogist\Elementor\DynamicTags\CatalogProductCountDynamicTag',
		) );

		$container->set( 'elementor.widgets', array(
			'catalogist_product_card' => ProductCardWidget::class,
			'catalogist_catalog'      => CatalogWidget::class,
		) );
	}

	/**
	 * Boot the service provider.
	 *
	 * Loads Elementor integration only when Elementor is active.
	 *
	 * @param Container $container Service container.
	 *
	 * @return void
	 */
	public function boot( Container $container ): void {
		if ( ! $this->is_elementor_active() ) {
			return;
		}

		// Register dynamic tags.
		add_action(
			'elementor/dynamic_tags/register',
			function () use ( $container ) {
				$this->register_dynamic_tags( $container );
			}
		);

		// Register widgets.
		add_action(
			'elementor/widgets/register',
			function () use ( $container ) {
				$this->register_widgets( $container );
			}
		);
	}

	/**
	 * Check if Elementor is active.
	 *
	 * @return bool
	 */
	private function is_elementor_active(): bool {
		return class_exists( '\Elementor\Plugin' );
	}

	/**
	 * Register dynamic tags with Elementor.
	 *
	 * @param Container $container Service container.
	 *
	 * @return void
	 */
	private function register_dynamic_tags( Container $container ): void {
		$tags = $container->get( 'elementor.dynamic_tags' );

		foreach ( $tags as $tag_id => $tag_class ) {
			if ( class_exists( $tag_class ) ) {
				try {
					$tag_instance = new $tag_class();
					\ElementorPro\Modules\DynamicTags\Manager::instance()->register_tag( $tag_instance );
				} catch ( \Throwable $e ) {
					Logger::error( 'Failed to register dynamic tag', array(
						'tag_id' => $tag_id,
						'error'  => $e->getMessage(),
					) );
				}
			}
		}
	}

	/**
	 * Register widgets with Elementor.
	 *
	 * @param Container $container Service container.
	 *
	 * @return void
	 */
	private function register_widgets( Container $container ): void {
		$widgets = $container->get( 'elementor.widgets' );

		foreach ( $widgets as $widget_id => $widget_class ) {
			if ( class_exists( $widget_class ) ) {
				try {
					$widget_instance = new $widget_class();
					\Elementor\Plugin::instance()->widgets_manager->register_widget_type( $widget_instance );
				} catch ( \Throwable $e ) {
					Logger::error( 'Failed to register widget', array(
						'widget_id' => $widget_id,
						'error'     => $e->getMessage(),
					) );
				}
			}
		}
	}
}
