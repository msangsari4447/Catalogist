<?php
/**
 * Product card widget for Elementor.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Elementor\Widgets;

use Catalogist\Template\TemplateEngineInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Product card widget.
 *
 * Renders a product card using the template engine.
 */
class ProductCardWidget {

	/**
	 * Widget ID.
	 *
	 * @var string
	 */
	private string $widget_id = 'catalogist_product_card';

	/**
	 * Widget name.
	 *
	 * @var string
	 */
	private string $widget_name = 'Product Card';

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
	 * Constructor.
	 *
	 * @param TemplateEngineInterface $template_engine Template engine instance.
	 */
	public function __construct( TemplateEngineInterface $template_engine ) {
		$this->template_engine = $template_engine;
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
		return __( 'Product Card', 'catalogist' );
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
		return 'eicon-product';
	}

	/**
	 * Get widget tags.
	 *
	 * @return array<string>
	 */
	public function get_tags(): array {
		return array( 'catalogist', 'woocommerce', 'product' );
	}

	/**
	 * Get widget keywords.
	 *
	 * @return array<string>
	 */
	public function get_keywords(): array {
		return array( 'product', 'card', 'catalog' );
	}

	/**
	 * Register widget controls.
	 *
	 * @return void
	 */
	protected function register_controls(): void {
		// Product selection control.
		$this->start_controls_section(
			'content_section',
			array(
				'label' => __( 'Content', 'catalogist' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'product_id',
			array(
				'label'       => __( 'Product ID', 'catalogist' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => '',
				'dynamic'     => array(
					'active' => true,
				),
				'placeholder' => __( 'Enter product ID or use dynamic tag', 'catalogist' ),
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
			'template',
			array(
				'label'   => __( 'Template', 'catalogist' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'default',
				'options' => array(
					'default' => __( 'Default', 'catalogist' ),
					'simple'  => __( 'Simple', 'catalogist' ),
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
		$product_id = (int) $this->get_settings( 'product_id' );

		if ( ! $product_id ) {
			echo '<p>' . esc_html__( 'No product ID specified.', 'catalogist' ) . '</p>';
			return;
		}

		$template = $this->get_settings( 'template', 'default' );
		$settings = array(
			'template' => $template,
		);

		// Render the product card using the template engine.
		echo $this->template_engine->renderItem( null, catalogist_get_catalog_item( $product_id ), $settings );
	}

	/**
	 * Render widget output for editor preview.
	 *
	 * @return void
	 */
	protected function content_template(): void {
		?>
		<div class="catalogist-product-card">
			<div class="catalogist-product-image">
				<img src="{{{ settings.product_image.src }}}" alt="{{{ settings.product_image.alt }}}" />
			</div>
			<div class="catalogist-product-info">
				<h3>{{{ settings.product_name }}}</h3>
				<span class="catalogist-product-price">{{{ settings.product_price }}}</span>
			</div>
		</div>
		<?php
	}
}
