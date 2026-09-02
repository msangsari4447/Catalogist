<?php
/**
 * Catalog widget for Elementor.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Elementor\Widgets;

use Catalogist\Catalog\Catalog;
use Catalogist\Catalog\CatalogRepository;
use Catalogist\Catalog\CatalogRepositoryInterface;
use Catalogist\CatalogItem\CatalogItem;
use Catalogist\CatalogItem\CatalogProcessor;
use Catalogist\Product\ProductQueryArgs;
use Catalogist\Product\ProductQueryResult;
use Catalogist\Product\ProductRepositoryInterface;
use Catalogist\Template\TemplateEngineInterface;
use Catalogist\Variation\VariationQueryArgs;

defined( 'ABSPATH' ) || exit;

/**
 * Catalog widget.
 *
 * Renders a catalog using the template engine.
 */
class CatalogWidget {

	/**
	 * Widget ID.
	 *
	 * @var string
	 */
	private string $widget_id = 'catalogist_catalog';

	/**
	 * Widget name.
	 *
	 * @var string
	 */
	private string $widget_name = 'Catalog';

	/**
	 * Widget category.
	 *
	 * @var string
	 */
	private string $widget_category = 'catalogist';

	/**
	 * Template engine instance.
	 *
	 * @var TemplateEngineInterface
	 */
	private TemplateEngineInterface $template_engine;

	/**
	 * Catalog repository instance.
	 *
	 * @var CatalogRepository
	 */
	private CatalogRepository $catalog_repo;

	/**
	 * Constructor.
	 *
	 * @param TemplateEngineInterface $template_engine Template engine instance.
	 * @param CatalogRepository       $catalog_repo Catalog repository instance.
	 */
	public function __construct(
		TemplateEngineInterface $template_engine,
		CatalogRepository $catalog_repo
	) {
		$this->template_engine = $template_engine;
		$this->catalog_repo    = $catalog_repo;
	}

	/**
	 * Register widget with Elementor.
	 *
	 * @return void
	 */
	public function register(): void {
		\Elementor\Plugin::instance()->widgets_manager->register_widget_type( $this );
	}

	/**
	 * Get widget ID.
	 *
	 * @return string
	 */
	public function get_id(): string {
		return $this->widget_id;
	}

	/**
	 * Get widget name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return $this->widget_name;
	}

	/**
	 * Get widget title.
	 *
	 * @return string
	 */
	public function get_title(): string {
		return __( 'Catalog', 'catalogist' );
	}

	/**
	 * Get widget category.
	 *
	 * @return string
	 */
	public function get_category(): string {
		return $this->widget_category;
	}

	/**
	 * Get widget icon.
	 *
	 * @return string
	 */
	public function get_icon(): string {
		return 'eicon-archive';
	}

	/**
	 * Get widget tags.
	 *
	 * @return array<string>
	 */
	public function get_tags(): array {
		return array( 'catalogist', 'woocommerce', 'catalog' );
	}

	/**
	 * Get widget keywords.
	 *
	 * @return array<string>
	 */
	public function get_keywords(): array {
		return array( 'catalog', 'woocommerce', 'products' );
	}

	/**
	 * Register widget controls.
	 *
	 * @return void
	 */
	protected function register_controls(): void {
		// Content controls.
		$this->start_controls_section(
			'content_section',
			array(
				'label' => __( 'Content', 'catalogist' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'catalog_id',
			array(
				'label'       => __( 'Catalog ID', 'catalogist' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => '',
				'dynamic'     => array(
					'active' => true,
				),
				'placeholder' => __( 'Enter catalog ID or use dynamic tag', 'catalogist' ),
			)
		);

		$this->add_control(
			'template',
			array(
				'label'   => __( 'Template', 'catalogist' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'default',
				'options' => array(
					'default' => __( 'Default', 'catalogist' ),
				),
			)
		);

		$this->add_control(
			'columns',
			array(
				'label'   => __( 'Columns', 'catalogist' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => '3',
				'options' => array(
					'1' => '1',
					'2' => '2',
					'3' => '3',
					'4' => '4',
				),
			)
		);

		$this->end_controls_section();

		// Style controls.
		$this->start_controls_section(
			'style_section',
			array(
				'label' => __( 'Style', 'catalogist' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'card_padding',
			array(
				'label'     => __( 'Card Padding', 'catalogist' ),
				'type'      => \Elementor\Controls_Manager::DIMENSIONS,
				'default'   => array(
					'top'    => '20',
					'right'  => '20',
					'bottom' => '20',
					'left'   => '20',
				),
				'selectors' => array(
					'.catalogist-product-card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Render widget output.
	 *
	 * @return void
	 */
	protected function render(): void {
		$catalog_id = (int) $this->get_settings( 'catalog_id' );

		if ( ! $catalog_id ) {
			echo '<p>' . esc_html__( 'No catalog ID specified.', 'catalogist' ) . '</p>';
			return;
		}

		// Load catalog.
		$catalog = $this->catalog_repo->find( $catalog_id );

		if ( ! $catalog ) {
			echo '<p>' . esc_html__( 'Catalog not found.', 'catalogist' ) . '</p>';
			return;
		}

		$template = $this->get_settings( 'template', 'default' );
		$columns  = (int) $this->get_settings( 'columns', 3 );

		// Build catalog items via the same pipeline as the shortcode.
		$catalog_items = $this->build_catalog_items( $catalog );

		$settings = array(
			'template' => $template,
			'columns'  => $columns,
		);

		// Render the catalog using the template engine.
		echo $this->template_engine->renderCatalog( $catalog, $catalog_items, $settings );
	}

	/**
	 * Build catalog items from a catalog using the CatalogProcessor.
	 *
	 * Mirrors the logic in template-shortcode.php to ensure consistency.
	 *
	 * @param Catalog $catalog Catalog entity.
	 *
	 * @return array<CatalogItem>
	 */
	private function build_catalog_items( Catalog $catalog ): array {
		$container = catalogist_get_container();

		if ( ! $container ) {
			return array();
		}

		/** @var CatalogProcessor $catalog_processor */
		$catalog_processor = $container->get( CatalogProcessor::class );

		/** @var ProductRepositoryInterface $product_repo */
		$product_repo = $container->get( ProductRepositoryInterface::class );

		// Build product query args from catalog settings.
		$product_query_args = ProductQueryArgs::from_array(
			array_merge(
				array(
					'order'     => 'ASC',
					'orderby'   => 'menu_order title',
					'columns'   => 3,
				),
				$catalog->get_product_query()
			)
		);

		$product_result = $product_repo->query( $product_query_args );

		// Build variation query args from catalog settings.
		$variation_args = VariationQueryArgs::from_array(
			array_merge(
				array( 'variation_mode' => 'parent' ),
				isset( $catalog->get_product_query()['variation_mode'] )
					? array( 'variation_mode' => $catalog->get_product_query()['variation_mode'] )
					: array()
			)
		);

		return $catalog_processor->process( $product_result, $variation_args );
	}

	/**
	 * Render widget output for editor preview.
	 *
	 * @return void
	 */
	protected function content_template(): void {
		?>
		<div class="catalogist-catalog">
			<div class="catalogist-product-loop">
				<# _.each( settings.products, function( product ) { #>
					<div class="catalogist-product-card">
						<div class="catalogist-product-image">
							<img src="{{{ product.image.src }}}" alt="{{{ product.image.alt }}}" />
						</div>
						<div class="catalogist-product-info">
							<h3>{{{ product.name }}}</h3>
							<span class="catalogist-product-price">{{{ product.price }}}</span>
						</div>
					</div>
				<# }); #>
			</div>
		</div>
		<?php
	}
}
