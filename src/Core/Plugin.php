<?php
/**
 * Core plugin bootstrap.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Core;

use Catalogist\Admin\AdminServiceProvider;
use Catalogist\Catalog\CatalogServiceProvider;
use Catalogist\Security\SecurityServiceProvider;

/**
 * Main plugin class.
 */
final class Plugin {

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	public const VERSION = '0.1.0';

	/**
	 * Plugin file path.
	 *
	 * @var string
	 */
	private string $file;

	/**
	 * Service container.
	 *
	 * @var Container|null
	 */
	private ?Container $container = null;

	/**
	 * Registered service providers.
	 *
	 * @var ServiceProviderInterface[]
	 */
	private array $providers = array();

	/**
	 * Plugin instance.
	 *
	 * @var Plugin|null
	 */
	private static ?Plugin $instance = null;

	/**
	 * Private constructor to prevent direct instantiation.
	 *
	 * @param string $file Main plugin file path.
	 */
	private function __construct( string $file ) {
		$this->file = $file;
	}

	/**
	 * Retrieve the singleton plugin instance.
	 *
	 * @return Plugin
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self( CATALOGIST_FILE );
		}

		return self::$instance;
	}

	/**
	 * Boot the plugin.
	 *
	 * @return void
	 */
	public function boot(): void {
		if ( ! $this->check_dependencies() ) {
			return;
		}

		$this->container = new Container();

		$this->container->set( 'plugin.file', $this->file );
		$this->container->set( 'plugin.version', self::VERSION );
		$this->container->set( 'plugin.slug', 'catalogist' );
		$this->container->set( 'plugin.text_domain', 'catalogist' );

		$this->register_providers();
		$this->boot_providers();

		$this->init_hooks();
	}

	/**
	 * Check plugin dependencies.
	 *
	 * @return bool
	 */
	private function check_dependencies(): bool {
		$checker = new DependencyChecker();

		if ( ! $checker->check_php_version() ) {
			add_action(
				'admin_notices',
				static function () use ( $checker ) {
					$checker->render_php_version_notice();
				}
			);
			return false;
		}

		if ( ! $checker->check_woocommerce() ) {
			add_action(
				'admin_notices',
				static function () use ( $checker ) {
					$checker->render_woocommerce_notice();
				}
			);
		}

		return true;
	}

	/**
	 * Register service providers.
	 *
	 * @return void
	 */
	private function register_providers(): void {
		$providers = array(
			SecurityServiceProvider::class,
			AdminServiceProvider::class,
			CatalogServiceProvider::class,
		);

		foreach ( $providers as $provider_class ) {
			$provider = new $provider_class();
			$provider->register( $this->container );
			$this->providers[] = $provider;
		}
	}

	/**
	 * Boot all registered service providers.
	 *
	 * @return void
	 */
	private function boot_providers(): void {
		foreach ( $this->providers as $provider ) {
			if ( method_exists( $provider, 'boot' ) ) {
				$provider->boot( $this->container );
			}
		}
	}

	/**
	 * Initialize WordPress hooks.
	 *
	 * @return void
	 */
	private function init_hooks(): void {
		register_activation_hook( $this->file, array( $this, 'activate' ) );
		register_deactivation_hook( $this->file, array( $this, 'deactivate' ) );
	}

	/**
	 * Plugin activation callback.
	 *
	 * @return void
	 */
	public function activate(): void {
		$this->maybe_init();

		Activator::activate( $this->container );
	}

	/**
	 * Plugin deactivation callback.
	 *
	 * @return void
	 */
	public function deactivate(): void {
		$this->maybe_init();

		Deactivator::deactivate( $this->container );
	}

	/**
	 * Initialize container if not already done.
	 *
	 * @return void
	 */
	private function maybe_init(): void {
		if ( null === $this->container ) {
			$this->boot();
		}
	}

	/**
	 * Retrieve the service container.
	 *
	 * @return Container|null
	 */
	public function get_container(): ?Container {
		return $this->container;
	}

	/**
	 * Prevent cloning.
	 */
	private function __clone() {}

	/**
	 * Prevent unserialization.
	 *
	 * @throws \Exception Always.
	 */
	public function __wakeup() {
		throw new \Exception( 'Cannot unserialize a singleton.' );
	}
}
