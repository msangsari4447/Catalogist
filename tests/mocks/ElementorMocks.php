<?php
/**
 * Elementor mocks for tests.
 *
 * Provides mock classes for Elementor integration tests.
 *
 * @package Catalogist
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/**
 * Mock Elementor Plugin class.
 */
class Elementor_Mock_Plugin {
	private static ?Elementor_Mock_Plugin $instance = null;
	private array $widgets = array();

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function widgets_manager(): Elementor_Mock_WidgetsManager {
		return new Elementor_Mock_WidgetsManager( $this );
	}

	public function register_widget( $widget ): void {
		$this->widgets[] = $widget;
	}
}

/**
 * Mock Elementor Widgets Manager.
 */
class Elementor_Mock_WidgetsManager {
	private Elementor_Mock_Plugin $plugin;
	private array $registered = array();

	public function __construct( Elementor_Mock_Plugin $plugin ) {
		$this->plugin = $plugin;
	}

	public function register_widget_type( $widget ): void {
		$this->registered[] = $widget;
	}

	public function get_registered(): array {
		return $this->registered;
	}
}

/**
 * Mock Elementor Dynamic Tags Manager.
 */
class Elementor_Mock_DynamicTagsManager {
	private array $registered = array();

	public static function instance(): self {
		return new self();
	}

	public function register_tag( $tag ): void {
		$this->registered[] = $tag;
	}

	public function get_registered(): array {
		return $this->registered;
	}
}

/**
 * Mock Elementor Control.
 */
class Elementor_Mock_Control {
	public string $name;
	public array $settings;

	public function __construct( string $name, array $settings = array() ) {
		$this->name = $name;
		$this->settings = $settings;
	}
}

/**
 * Mock Elementor Dynamic Tag Base.
 */
abstract class Elementor_Mock_DynamicTagBase {
	protected string $name = '';
	protected array $controls = array();

	abstract public function get_name(): string;
	abstract public function get_title(): string;
	abstract public function render(): string;
	abstract public function get_controls(): array;

	public function get_group(): array {
		return array();
	}

	public function render_plain_content( array $controls_data ): string {
		return $this->render();
	}

	public function get_settings( string $key, $default = null ): mixed {
		return $this->controls[ $key ] ?? $default;
	}

	public function set_settings( array $controls ): void {
		$this->controls = $controls;
	}
}

/**
 * Mock Elementor Controls Manager.
 */
class Elementor_Mock_ControlsManager {
	public const TAB_CONTENT = 'content';
	public const TAB_STYLE = 'style';
	public const TEXT = 'text';
	public const SELECT = 'select';
	public const NUMBER = 'number';
	public const DIMENSIONS = 'dimensions';
	public const TEXTAREA = 'textarea';
}

/**
 * Mock Elementor Widget Base.
 */
abstract class Elementor_Mock_WidgetBase {
	protected array $controls = array();
	protected array $settings_cache = array();

	public function get_id(): string {
		return 'mock_widget';
	}

	public function get_name(): string {
		return 'Mock Widget';
	}

	public function get_title(): string {
		return __( 'Mock Widget', 'catalogist' );
	}

	public function get_category(): string {
		return 'general';
	}

	public function get_icon(): string {
		return 'eicon-widget';
	}

	public function get_tags(): array {
		return array();
	}

	public function get_keywords(): array {
		return array();
	}

	public function get_custom_options(): array {
		return array();
	}

	public function start_controls_section( string $name, array $args ): void {
		$this->controls[ $name ]['args'] = $args;
	}

	public function end_controls_section(): void {}

	public function add_control( string $name, array $args ): void {
		$this->controls[ $name ]['args'] = $args;
	}

	public function get_settings( string $key, $default = null ): mixed {
		return $this->settings_cache[ $key ] ?? $default;
	}

	public function set_settings( array $settings ): void {
		$this->settings_cache = $settings;
	}
}

// Register Elementor constants.
if ( ! defined( 'ELEMENTOR_VERSION' ) ) {
	define( 'ELEMENTOR_VERSION', '3.0.0' );
}
