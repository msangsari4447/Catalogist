<?php
/**
 * Preview page handler for admin.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Admin;

use Catalogist\Catalog\CatalogRepositoryInterface;
use Catalogist\CatalogItem\CatalogProcessorInterface;
use Catalogist\Preview\PreviewEngineInterface;
use Catalogist\Product\ProductQueryArgs;
use Catalogist\Product\ProductRepositoryInterface;
use Catalogist\Security\Capability;
use Catalogist\Security\Nonce;
use Catalogist\Variation\VariationQueryArgs;

/**
 * Renders the admin preview page.
 */
final class PreviewPage {

	/**
	 * Catalog repository.
	 *
	 * @var CatalogRepositoryInterface
	 */
	private CatalogRepositoryInterface $catalog_repo;

	/**
	 * Catalog processor.
	 *
	 * @var CatalogProcessorInterface
	 */
	private CatalogProcessorInterface $catalog_processor;

	/**
	 * Product repository.
	 *
	 * @var ProductRepositoryInterface
	 */
	private ProductRepositoryInterface $product_repo;

	/**
	 * Preview engine.
	 *
	 * @var PreviewEngineInterface
	 */
	private PreviewEngineInterface $preview_engine;

	/**
	 * Constructor.
	 *
	 * @param CatalogRepositoryInterface   $catalog_repo     Catalog repository.
	 * @param CatalogProcessorInterface    $catalog_processor Catalog processor.
	 * @param ProductRepositoryInterface   $product_repo      Product repository.
	 * @param PreviewEngineInterface       $preview_engine    Preview engine.
	 */
	public function __construct(
		CatalogRepositoryInterface $catalog_repo,
		CatalogProcessorInterface $catalog_processor,
		ProductRepositoryInterface $product_repo,
		PreviewEngineInterface $preview_engine
	) {
		$this->catalog_repo      = $catalog_repo;
		$this->catalog_processor = $catalog_processor;
		$this->product_repo      = $product_repo;
		$this->preview_engine    = $preview_engine;
	}

	/**
	 * Register hooks with WordPress.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'admin_menu', array( $this, 'add_preview_page' ) );
		add_action( 'post_submitbox_misc_actions', array( $this, 'add_preview_button' ) );
	}

	/**
	 * Add preview page as a hidden submenu.
	 *
	 * @return void
	 */
	public function add_preview_page(): void {
		add_submenu_page(
			'catalogist',
			__( 'Preview', 'catalogist' ),
			__( 'Preview', 'catalogist' ),
			Capability::MANAGE_CATALOGS,
			'catalogist-preview',
			array( $this, 'render_preview' )
		);
	}

	/**
	 * Add a preview button to the catalog edit screen.
	 *
	 * @param \WP_Post $post The current post object.
	 *
	 * @return void
	 */
	public function add_preview_button( \WP_Post $post ): void {
		if ( 'ctlg_catalog' !== $post->post_type ) {
			return;
		}

		if ( ! current_user_can( Capability::MANAGE_CATALOGS ) ) {
			return;
		}

		$preview_url = $this->preview_engine->getPreviewURL( $post->ID );
		?>
		<div class="misc-pub-section">
			<a href="<?php echo esc_url( $preview_url ); ?>" target="_blank" class="button button-primary button-large">
				<?php esc_html_e( 'Preview Catalog', 'catalogist' ); ?>
			</a>
		</div>
		<?php
	}

	/**
	 * Render the preview page.
	 *
	 * @return void
	 */
	public function render_preview(): void {
		// Check capability.
		if ( ! current_user_can( Capability::MANAGE_CATALOGS ) ) {
			wp_die(
				esc_html__( 'You do not have permission to preview catalogs.', 'catalogist' ),
				esc_html__( 'Permission Denied', 'catalogist' ),
				array( 'response' => 403 )
			);
		}

		// Verify CSRF nonce for preview action.
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'catalogist_preview_action' ) ) {
			$this->render_error_page(
				esc_html__( 'Security error.', 'catalogist' ),
				esc_html__( 'Invalid security nonce. Please try again.', 'catalogist' )
			);
			return;
		}

		// Get catalog ID from query.
		$catalog_id = isset( $_GET['catalog_id'] ) ? absint( $_GET['catalog_id'] ) : 0;

		if ( $catalog_id <= 0 ) {
			$this->render_error_page(
				esc_html__( 'Invalid catalog.', 'catalogist' ),
				esc_html__( 'No catalog ID provided.', 'catalogist' )
			);
			return;
		}

		// Fetch catalog.
		$catalog = $this->catalog_repo->find( $catalog_id );

		if ( $catalog instanceof \WP_Error || null === $catalog ) {
			$this->render_error_page(
				esc_html__( 'Catalog not found.', 'catalogist' ),
				esc_html__( 'The requested catalog does not exist.', 'catalogist' )
			);
			return;
		}

		// Check draft visibility.
		$post = get_post( $catalog_id );
		if ( $post && 'draft' === $post->post_status && ! current_user_can( 'edit_post', $catalog_id ) ) {
			$this->render_error_page(
				esc_html__( 'Access denied.', 'catalogist' ),
				esc_html__( 'You do not have permission to preview this draft catalog.', 'catalogist' )
			);
			return;
		}

		// Parse optional print settings from query.
		$print_settings = $this->parse_print_settings();

		// Build product query args from catalog.
		$product_query_args = ProductQueryArgs::from_array(
			array_merge(
				array(
					'order'   => 'ASC',
					'orderby' => 'menu_order title',
				),
				$catalog->get_product_query()
			)
		);

		// Query products.
		$product_result = $this->product_repo->query( $product_query_args );

		// Build variation query args from catalog and print settings.
		$variation_mode = 'parent';
		if ( isset( $catalog->get_product_query()['variation_mode'] ) ) {
			$variation_mode = $catalog->get_product_query()['variation_mode'];
		} elseif ( is_array( $print_settings ) && isset( $print_settings['variation_mode'] ) ) {
			$variation_mode = $print_settings['variation_mode'];
		}

		$variation_args = VariationQueryArgs::from_array(
			array( 'variation_mode' => $variation_mode )
		);

		// Process catalog to get items.
		$items = $this->catalog_processor->process( $product_result, $variation_args );

		// Render preview.
		echo $this->preview_engine->renderPreview( $catalog, $items, $print_settings );
		exit;
	}

	/**
	 * Parse print settings from query string.
	 *
	 * @return array<string, mixed>|null
	 */
	private function parse_print_settings(): ?array {
		$encoded = isset( $_GET['print_settings'] ) ? sanitize_text_field( wp_unslash( $_GET['print_settings'] ) ) : '';

		if ( empty( $encoded ) ) {
			return null;
		}

		$json = base64_decode( $encoded, true );
		if ( false === $json ) {
			return null;
		}

		$settings = json_decode( $json, true );
		if ( ! is_array( $settings ) ) {
			return null;
		}

		// Whitelist allowed keys to prevent arbitrary data injection.
		$allowed_keys = array(
			'page_size',
			'orientation',
			'columns',
			'margins',
			'show_header',
			'show_footer',
			'show_cover',
		);

		$filtered = array();
		foreach ( $allowed_keys as $key ) {
			if ( array_key_exists( $key, $settings ) ) {
				$filtered[ $key ] = $settings[ $key ];
			}
		}

		return $filtered;
	}

	/**
	 * Render an error page.
	 *
	 * @param string $title Error title.
	 * @param string $message Error message.
	 *
	 * @return void
	 */
	private function render_error_page( string $title, string $message ): void {
		wp_enqueue_style(
			'catalogist-preview',
			CATALOGIST_PLUGIN_URL . 'assets/css/preview.css',
			array(),
			CATALOGIST_VERSION
		);

		echo '<!DOCTYPE html>' . "\n";
		echo '<html lang="' . esc_attr( get_bloginfo( 'language' ) ) . '">' . "\n";
		echo '<head>' . "\n";
		echo '<meta charset="' . esc_attr( get_bloginfo( 'charset' ) ) . '">' . "\n";
		echo '<meta name="viewport" content="width=device-width, initial-scale=1">' . "\n";
		echo '<title>' . esc_html( $title ) . ' — Catalogist' . '</title>' . "\n";
		echo '<link rel="stylesheet" href="' . esc_url( CATALOGIST_PLUGIN_URL . 'assets/css/preview.css' ) . '">' . "\n";
		echo '</head>' . "\n";
		echo '<body class="catalogist-preview-body catalogist-preview-error">';
		echo '<div class="catalogist-preview-error-page">';
		echo '<h1>' . esc_html( $title ) . '</h1>';
		echo '<p>' . esc_html( $message ) . '</p>';
		echo '<a href="' . esc_url( admin_url( 'admin.php?page=catalogist' ) ) . '" class="button">' . esc_html__( 'Back to Catalogs', 'catalogist' ) . '</a>';
		echo '</div>';
		echo '</body>' . "\n";
		echo '</html>' . "\n";
		exit;
	}
}
