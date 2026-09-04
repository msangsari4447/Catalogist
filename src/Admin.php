<?php

declare(strict_types=1);

namespace Catalogist;

/**
 * Admin UI for Catalog CPT.
 *
 * Handles meta box registration, rendering, and save logic.
 * All admin operations include nonce verification and capability checks.
 */
final class Admin {

	/**
	 * Nonce action name.
	 */
	private const NONCE_ACTION = 'catalogist_catalog_save';

	/**
	 * Nonce field name.
	 */
	private const NONCE_FIELD = 'catalogist_catalog_nonce';

	/**
	 * Capability required to edit catalogs.
	 */
	private const EDIT_CAPABILITY = 'edit_posts';

	/**
	 * Get nonce configuration for testing.
	 *
	 * @return array{action: string, field: string}
	 */
	protected static function get_nonce_config(): array {
		return array(
			'action' => self::NONCE_ACTION,
			'field'  => self::NONCE_FIELD,
		);
	}

	/**
	 * Boot admin hooks.
	 */
	public static function boot(): void {
		add_action( 'add_meta_boxes_' . CatalogPostType::POST_TYPE, array( self::class, 'register_meta_boxes' ) );
		add_action( 'save_post_' . CatalogPostType::POST_TYPE, array( self::class, 'save_meta_box_data' ), 10, 3 );
	}

	/**
	 * Register meta boxes for catalog edit screen.
	 */
	public static function register_meta_boxes(): void {
		add_meta_box(
			'catalogist_catalog_settings',
			__( 'Catalog Settings', 'catalogist' ),
			array( self::class, 'render_settings_meta_box' ),
			CatalogPostType::POST_TYPE,
			'normal',
			'high'
		);

		add_meta_box(
			'catalogist_catalog_products',
			__( 'Catalog Products', 'catalogist' ),
			array( self::class, 'render_products_meta_box' ),
			CatalogPostType::POST_TYPE,
			'normal',
			'default'
		);
	}

	/**
	 * Render the Catalog Settings meta box.
	 *
	 * @param \WP_Post $post Current post object.
	 */
	public static function render_settings_meta_box( \WP_Post $post ): void {
		// Nonce field for verification.
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD );

		$data        = Catalog::get_data( $post->ID );
		$settings    = $data['settings'];
		$description = $data['description'];
		?>
		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row">
						<label for="catalog_description"><?php esc_html_e( 'Description', 'catalogist' ); ?></label>
					</th>
					<td>
						<textarea
							id="catalog_description"
							name="catalog_description"
							rows="5"
							class="large-text code"
							aria-describedby="catalog_description_desc"
						><?php echo esc_textarea( $description ); ?></textarea>
						<p class="description" id="catalog_description_desc">
							<?php esc_html_e( 'Optional description for this catalog. Shown in catalog listings.', 'catalogist' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<fieldset>
							<legend><?php esc_html_e( 'Layout Settings', 'catalogist' ); ?></legend>
						</fieldset>
					</th>
					<td>
						<fieldset>
							<legend class="screen-reader-text"><?php esc_html_e( 'Layout Settings', 'catalogist' ); ?></legend>
							<label for="catalog_settings_layout">
								<?php esc_html_e( 'Layout', 'catalogist' ); ?>
							</label>
							<select id="catalog_settings_layout" name="catalog_settings[layout]">
								<option value="grid" <?php selected( $settings['layout'], 'grid' ); ?>>
									<?php esc_html_e( 'Grid', 'catalogist' ); ?>
								</option>
								<option value="list" <?php selected( $settings['layout'], 'list' ); ?>>
									<?php esc_html_e( 'List', 'catalogist' ); ?>
								</option>
								<option value="table" <?php selected( $settings['layout'], 'table' ); ?>>
									<?php esc_html_e( 'Table', 'catalogist' ); ?>
								</option>
							</select>
							<br><br>
							<label for="catalog_settings_columns">
								<?php esc_html_e( 'Columns (Grid only)', 'catalogist' ); ?>
							</label>
							<input
								type="number"
								id="catalog_settings_columns"
								name="catalog_settings[columns]"
								value="<?php echo esc_attr( $settings['columns'] ); ?>"
								min="1"
								max="6"
								step="1"
								class="small-text"
							>
							<p class="description">
								<?php esc_html_e( 'Number of columns for grid layout (1-6).', 'catalogist' ); ?>
							</p>
						</fieldset>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<fieldset>
							<legend><?php esc_html_e( 'Display Options', 'catalogist' ); ?></legend>
						</fieldset>
					</th>
					<td>
						<fieldset>
							<legend class="screen-reader-text"><?php esc_html_e( 'Display Options', 'catalogist' ); ?></legend>
							<label for="catalog_settings_show_price">
								<input
									type="checkbox"
									id="catalog_settings_show_price"
									name="catalog_settings[show_price]"
									value="1"
									<?php checked( $settings['show_price'], true ); ?>
								>
								<?php esc_html_e( 'Show Price', 'catalogist' ); ?>
							</label>
							<br>
							<label for="catalog_settings_show_sku">
								<input
									type="checkbox"
									id="catalog_settings_show_sku"
									name="catalog_settings[show_sku]"
									value="1"
									<?php checked( $settings['show_sku'], true ); ?>
								>
								<?php esc_html_e( 'Show SKU', 'catalogist' ); ?>
							</label>
							<br>
							<label for="catalog_settings_show_stock">
								<input
									type="checkbox"
									id="catalog_settings_show_stock"
									name="catalog_settings[show_stock]"
									value="1"
									<?php checked( $settings['show_stock'], true ); ?>
								>
								<?php esc_html_e( 'Show Stock Status', 'catalogist' ); ?>
							</label>
						</fieldset>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Render the Catalog Products meta box.
	 *
	 * @param \WP_Post $post Current post object.
	 */
	public static function render_products_meta_box( \WP_Post $post ): void {
		// Nonce field for verification (shared with settings meta box).
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD );

		$data     = Catalog::get_data( $post->ID );
		$products = $data['products'];

		// Get product titles for display.
		$product_titles = array();
		if ( ! empty( $products ) ) {
			foreach ( $products as $product_id ) {
				$title = get_the_title( $product_id );
				if ( $title ) {
					$product_titles[ $product_id ] = $title;
				}
			}
		}
		?>
		<div class="catalogist-products-wrapper">
			<p class="description">
				<?php esc_html_e( 'Select products to include in this catalog. Search by title or SKU.', 'catalogist' ); ?>
			</p>

			<div id="catalogist-products-search">
				<label for="catalogist_product_search" class="screen-reader-text">
					<?php esc_html_e( 'Search products', 'catalogist' ); ?>
				</label>
				<input
					type="search"
					id="catalogist_product_search"
					class="regular-text"
					placeholder="<?php esc_attr_e( 'Search products...', 'catalogist' ); ?>"
					aria-describedby="catalogist_product_search_desc"
				>
				<p class="description" id="catalogist_product_search_desc">
					<?php esc_html_e( 'Type to search WooCommerce products by title or SKU.', 'catalogist' ); ?>
				</p>
			</div>

			<input type="hidden" name="catalog_products[]" value="">

			<ul id="catalogist-selected-products" class="catalogist-product-list">
				<?php if ( ! empty( $product_titles ) ) : ?>
					<?php foreach ( $product_titles as $id => $title ) : ?>
						<li data-product-id="<?php echo esc_attr( $id ); ?>">
							<input type="hidden" name="catalog_products[]" value="<?php echo esc_attr( $id ); ?>">
							<span class="product-title"><?php echo esc_html( $title ); ?></span>
							<button type="button" class="button-link catalogist-remove-product" aria-label="<?php esc_attr_e( 'Remove product', 'catalogist' ); ?>">
								<?php esc_html_e( 'Remove', 'catalogist' ); ?>
							</button>
						</li>
					<?php endforeach; ?>
				<?php endif; ?>
			</ul>

			<div id="catalogist-search-results" class="catalogist-search-results" style="display: none;"></div>

			<script type="text/javascript">
				// Simple product search and selection (vanilla JS, no dependencies)
				(function() {
					var searchInput = document.getElementById('catalogist_product_search');
					var resultsDiv = document.getElementById('catalogist-search-results');
					var selectedList = document.getElementById('catalogist-selected-products');
					var debounceTimer = null;

					function getSelectedIds() {
						var ids = [];
						var inputs = selectedList.querySelectorAll('input[name="catalog_products[]"]');
						for (var i = 0; i < inputs.length; i++) {
							var val = parseInt(inputs[i].value, 10);
							if (val > 0) ids.push(val);
						}
						return ids;
					}

					function renderResult(product) {
						var selectedIds = getSelectedIds();
						var isSelected = selectedIds.indexOf(product.ID) !== -1;
						var div = document.createElement('div');
						div.className = 'catalogist-search-result' + (isSelected ? ' selected' : '');
						div.setAttribute('data-product-id', product.ID);
						div.innerHTML = '<span class="product-title">' + escapeHtml(product.post_title) + '</span>'
							+ '<span class="product-sku">' + escapeHtml(product._sku || '') + '</span>'
							+ '<button type="button" class="button button-small catalogist-toggle-product" data-id="' + product.ID + '">'
							+ (isSelected ? '<?php esc_js_e( 'Remove', 'catalogist' ); ?>' : '<?php esc_js_e( 'Add', 'catalogist' ); ?>') + '</button>';
						return div;
					}

					function escapeHtml(text) {
						var div = document.createElement('div');
						div.textContent = text;
						return div.innerHTML;
					}

					function updateSelectedList() {
						// Update hidden inputs in selected list
						var items = selectedList.querySelectorAll('li');
						for (var i = 0; i < items.length; i++) {
							var li = items[i];
							var id = li.getAttribute('data-product-id');
							var hiddenInput = li.querySelector('input[name="catalog_products[]"]');
							if (hiddenInput) {
								hiddenInput.value = id;
							}
						}
					}

					searchInput.addEventListener('input', function() {
						var query = this.value.trim();
						clearTimeout(debounceTimer);
						if (query.length < 2) {
							resultsDiv.style.display = 'none';
							resultsDiv.innerHTML = '';
							return;
						}
						debounceTimer = setTimeout(function() {
							fetch(ajaxurl + '?action=catalogist_search_products&nonce=' + encodeURIComponent('<?php echo esc_js( wp_create_nonce( 'catalogist_search_products' ) ); ?>') + '&query=' + encodeURIComponent(query))
								.then(function(response) { return response.json(); })
								.then(function(data) {
									resultsDiv.innerHTML = '';
									if (data.success && data.data.length > 0) {
										for (var i = 0; i < data.data.length; i++) {
											resultsDiv.appendChild(renderResult(data.data[i]));
										}
										resultsDiv.style.display = 'block';
									} else {
										resultsDiv.style.display = 'none';
									}
								})
								.catch(function() {
									resultsDiv.style.display = 'none';
								});
						}, 300);
					});

					// Handle clicks on search results
					resultsDiv.addEventListener('click', function(e) {
						var button = e.target.closest('.catalogist-toggle-product');
						if (!button) return;
						var productId = parseInt(button.getAttribute('data-id'), 10);
						var selectedIds = getSelectedIds();
						var isSelected = selectedIds.indexOf(productId) !== -1;

						if (isSelected) {
							// Remove from selected list
							var li = selectedList.querySelector('li[data-product-id="' + productId + '"]');
							if (li) li.parentNode.removeChild(li);
						} else {
							// Add to selected list
							var resultDiv = resultsDiv.querySelector('.catalogist-search-result[data-product-id="' + productId + '"]');
							var title = resultDiv ? resultDiv.querySelector('.product-title').textContent : 'Product #' + productId;
							var li = document.createElement('li');
							li.setAttribute('data-product-id', productId);
							li.innerHTML = '<input type="hidden" name="catalog_products[]" value="' + productId + '">'
								+ '<span class="product-title">' + escapeHtml(title) + '</span>'
								+ '<button type="button" class="button-link catalogist-remove-product" aria-label="<?php esc_js_e( 'Remove product', 'catalogist' ); ?>">'
								+ '<?php esc_js_e( 'Remove', 'catalogist' ); ?>' + '</button>';
							selectedList.appendChild(li);
						}
						updateSelectedList();
						// Update search result button state
						var resultBtn = resultsDiv.querySelector('.catalogist-toggle-product[data-id="' + productId + '"]');
						if (resultBtn) {
							resultBtn.textContent = isSelected ? '<?php esc_js_e( 'Add', 'catalogist' ); ?>' : '<?php esc_js_e( 'Remove', 'catalogist' ); ?>';
							resultBtn.closest('.catalogist-search-result').classList.toggle('selected');
						}
					});

					// Handle remove buttons in selected list
					selectedList.addEventListener('click', function(e) {
						var button = e.target.closest('.catalogist-remove-product');
						if (!button) return;
						var li = button.closest('li');
						if (li) {
							var productId = parseInt(li.getAttribute('data-product-id'), 10);
							li.parentNode.removeChild(li);
							updateSelectedList();
							// Update search result button if visible
							var resultBtn = resultsDiv.querySelector('.catalogist-toggle-product[data-id="' + productId + '"]');
							if (resultBtn) {
								resultBtn.textContent = '<?php esc_js_e( 'Add', 'catalogist' ); ?>';
								resultBtn.closest('.catalogist-search-result').classList.remove('selected');
							}
						}
					});

					// Hide results when clicking outside
					document.addEventListener('click', function(e) {
						if (!searchInput.contains(e.target) && !resultsDiv.contains(e.target)) {
							resultsDiv.style.display = 'none';
						}
					});
				})();
			</script>
			<style>
				.catalogist-product-list { list-style: none; padding: 0; margin: 0; }
				.catalogist-product-list li { padding: 8px 12px; border: 1px solid #ddd; margin-bottom: 4px; background: #fafafa; display: flex; align-items: center; gap: 12px; }
				.catalogist-product-list .product-title { flex: 1; }
				.catalogist-search-results { border: 1px solid #ddd; background: #fff; max-height: 300px; overflow-y: auto; margin-top: 8px; }
				.catalogist-search-result { padding: 8px 12px; border-bottom: 1px solid #eee; display: flex; align-items: center; gap: 12px; }
				.catalogist-search-result:last-child { border-bottom: none; }
				.catalogist-search-result:hover { background: #f0f0f1; }
				.catalogist-search-result.selected { background: #d4edda; }
				.catalogist-search-result .product-title { flex: 1; }
				.catalogist-search-result .product-sku { color: #666; font-size: 12px; min-width: 80px; }
			</style>
		</div>
		<?php
	}

	/**
	 * Save catalog meta box data.
	 *
	 * @param int     $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 * @param bool    $update   Whether this is an update.
	 */
	public static function save_meta_box_data( int $post_id, \WP_Post $post, bool $update ): void {
		// Verify nonce.
		if ( ! isset( $_POST[ self::NONCE_FIELD ] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST[ self::NONCE_FIELD ] ) ), self::NONCE_ACTION ) ) {
			return;
		}

		// Check autosave.
		if ( defined( 'DOING_AUTOSAVE' ) && true === DOING_AUTOSAVE ) {
			return;
		}

		// Check user capabilities.
		if ( ! current_user_can( self::EDIT_CAPABILITY, $post_id ) ) {
			return;
		}

		// Check post type.
		if ( CatalogPostType::POST_TYPE !== $post->post_type ) {
			return;
		}

		// Suppress unused parameter warning.
		( void ) $update;

		// Sanitize and save.
		$data = Catalog::sanitize_input( $_POST );
		Catalog::save( $post_id, $data );
	}

	/**
	 * AJAX handler for product search.
	 */
	public static function ajax_search_products(): void {
		// Verify nonce.
		check_ajax_referer( 'catalogist_search_products', 'nonce' );

		// Check capability.
		if ( ! current_user_can( self::EDIT_CAPABILITY ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'catalogist' ) ) );
		}

		$query = isset( $_GET['query'] ) ? sanitize_text_field( wp_unslash( $_GET['query'] ) ) : '';

		if ( strlen( $query ) < 2 ) {
			wp_send_json_success( array() );
		}

		// Search WooCommerce products.
		$args = array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => 20,
			's'              => $query,
			'fields'         => 'ids',
		);

		$product_ids = get_posts( $args );

		$results = array();
		foreach ( $product_ids as $id ) {
			$product = wc_get_product( $id );
			if ( $product ) {
				$results[] = array(
					'ID'         => $product->get_id(),
					'post_title' => $product->get_name(),
					'_sku'       => $product->get_sku(),
				);
			}
		}

		wp_send_json_success( $results );
	}
}

