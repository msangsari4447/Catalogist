<?php

declare(strict_types=1);

namespace Catalogist;

/**
 * Template data structure and persistence.
 *
 * Structural configuration container for Header -> Product Loop -> Product Card -> Footer.
 * WordPress-native persistence via ctlg_template CPT + post meta (JSON).
 * No HTML rendering — Stage 9 responsibility.
 * No WooCommerce coupling — input is CatalogContext / CatalogItem only.
 */
final class Template {

	public const CTLG_META_CONFIGURATION = 'ctlg_template_configuration';
	public const CTLG_META_VERSION       = 'ctlg_template_version';
	public const CTLG_META_STATUS        = 'ctlg_template_status';

	public const CONFIG_VERSION = '1.0.0';

	/**
	 * @var list<string>
	 */
	private const ALLOWED_STATUSES = array( 'draft', 'active', 'archived' );

	// --------------------------------------------------------------------
	// Defaults
	// --------------------------------------------------------------------

	/**
	 * Default template configuration.
	 *
	 * @return array<string, mixed>
	 */
	public static function default_configuration(): array {
		return array(
			'version' => self::CONFIG_VERSION,
			'status'  => 'draft',
			'header'  => array(
				'enabled'    => true,
				'show_title' => true,
			),
			'footer'  => array(
				'enabled' => true,
			),
			'loop'    => array(
				'columns' => 3,
			),
			'card'    => array(
				'show_image' => true,
				'show_title' => true,
				'show_price' => true,
				'show_sku'   => false,
				'show_stock' => false,
			),
		);
	}

	/**
	 * All meta keys used by Template.
	 *
	 * @return list<string>
	 */
	public static function meta_keys(): array {
		return array(
			self::CTLG_META_CONFIGURATION,
			self::CTLG_META_VERSION,
			self::CTLG_META_STATUS,
		);
	}

	// --------------------------------------------------------------------
	// Load
	// --------------------------------------------------------------------

	/**
	 * Build template data array from post ID.
	 *
	 * Fail-safe: missing / deleted / wrong post type returns defaults with requested ID.
	 *
	 * @param int $post_id Template post ID.
	 * @return array<string, mixed>
	 */
	public static function get_data( int $post_id ): array {
		$post = get_post( $post_id );

		$is_valid = $post instanceof \WP_Post && TemplatePostType::POST_TYPE === $post->post_type;

		$configuration = self::default_configuration();

		if ( $is_valid ) {
			$raw  = get_post_meta( $post_id, self::CTLG_META_CONFIGURATION, true );
			$json = null;

			if ( is_string( $raw ) && '' !== $raw ) {
				$decoded = json_decode( $raw, true );
				$json    = is_array( $decoded ) ? $decoded : null;
			} elseif ( is_array( $raw ) && ! empty( $raw ) ) {
				$json = $raw;
			}

			if ( is_array( $json ) ) {
				$configuration = self::apply_defaults( $configuration, $json );
			}

			// Legacy standalone version/status meta overrides (forward-compat).
			$version = get_post_meta( $post_id, self::CTLG_META_VERSION, true );
			$status  = get_post_meta( $post_id, self::CTLG_META_STATUS, true );

			if ( is_string( $version ) && '' !== $version ) {
				$configuration['version'] = $version;
			}
			if ( is_string( $status ) && '' !== $status ) {
				$configuration['status'] = $status;
			}

			return array(
				'id'            => $post_id,
				'title'         => get_the_title( $post_id ),
				'configuration' => $configuration,
				'version'       => $configuration['version'] ?? self::CONFIG_VERSION,
				'status'        => $configuration['status'] ?? 'draft',
				'created_at'    => get_the_date( 'c', $post_id ),
				'updated_at'    => get_the_modified_date( 'c', $post_id ),
				'exists'        => true,
			);
		}

		// Fail-safe fallback for missing / invalid template.
		return array(
			'id'            => $post_id,
			'title'         => '',
			'configuration' => $configuration,
			'version'       => $configuration['version'],
			'status'        => $configuration['status'],
			'created_at'    => '',
			'updated_at'    => '',
			'exists'        => false,
		);
	}

	/**
	 * Apply defaults recursively (assoc arrays only; lists replaced).
	 *
	 * @param array<string, mixed> $defaults
	 * @param array<string, mixed> $provided
	 * @return array<string, mixed>
	 */
	public static function apply_defaults( array $defaults, array $provided ): array {
		$result = $defaults;

		foreach ( $provided as $key => $value ) {
			if ( array_key_exists( $key, $result ) ) {
				if (
					is_array( $value )
					&& is_array( $result[ $key ] )
					&& ! array_is_list( $value )
					&& ! array_is_list( $result[ $key ] )
				) {
					$result[ $key ] = self::apply_defaults( $result[ $key ], $value );
				} else {
					$result[ $key ] = $value;
				}
			}
		}

		return $result;
	}

	// --------------------------------------------------------------------
	// Validation
	// --------------------------------------------------------------------

	/**
	 * Validate template configuration.
	 *
	 * @param array<string, mixed> $config
	 * @return list<string>
	 */
	public static function validate_configuration( array $config ): array {
		$errors = array();

		if ( ! isset( $config['version'] ) || ! is_string( $config['version'] ) || '' === trim( $config['version'] ) ) {
			$errors[] = __( 'Template version is required and must be a string.', 'catalogist' );
		}

		if ( ! isset( $config['status'] ) || ! in_array( $config['status'], self::ALLOWED_STATUSES, true ) ) {
			$errors[] = __( 'Template status must be one of: draft, active, archived.', 'catalogist' );
		}

		if ( isset( $config['header'] ) ) {
			$errors = array_merge( $errors, self::validate_header_config( $config['header'] ) );
		}

		if ( isset( $config['footer'] ) ) {
			$errors = array_merge( $errors, self::validate_footer_config( $config['footer'] ) );
		}

		if ( isset( $config['loop'] ) ) {
			$errors = array_merge( $errors, self::validate_loop_config( $config['loop'] ) );
		}

		if ( isset( $config['card'] ) ) {
			$errors = array_merge( $errors, self::validate_card_config( $config['card'] ) );
		}

		return $errors;
	}

	/**
	 * @param mixed $header
	 * @return list<string>
	 */
	private static function validate_header_config( $header ): array {
		if ( ! is_array( $header ) ) {
			return array( __( 'Header configuration must be an array.', 'catalogist' ) );
		}

		$errors = array();

		if ( isset( $header['enabled'] ) && ! is_bool( $header['enabled'] ) ) {
			$errors[] = __( 'Header enabled must be a boolean.', 'catalogist' );
		}

		if ( isset( $header['show_title'] ) && ! is_bool( $header['show_title'] ) ) {
			$errors[] = __( 'Header show_title must be a boolean.', 'catalogist' );
		}

		return $errors;
	}

	/**
	 * @param mixed $footer
	 * @return list<string>
	 */
	private static function validate_footer_config( $footer ): array {
		if ( ! is_array( $footer ) ) {
			return array( __( 'Footer configuration must be an array.', 'catalogist' ) );
		}

		$errors = array();

		if ( isset( $footer['enabled'] ) && ! is_bool( $footer['enabled'] ) ) {
			$errors[] = __( 'Footer enabled must be a boolean.', 'catalogist' );
		}

		return $errors;
	}

	/**
	 * @param mixed $loop
	 * @return list<string>
	 */
	private static function validate_loop_config( $loop ): array {
		if ( ! is_array( $loop ) ) {
			return array( __( 'Loop configuration must be an array.', 'catalogist' ) );
		}

		$errors = array();

		if ( isset( $loop['columns'] ) ) {
			$columns = (int) $loop['columns'];
			if ( $columns < 1 || $columns > 12 ) {
				$errors[] = __( 'Loop columns must be between 1 and 12.', 'catalogist' );
			}
		}

		return $errors;
	}

	/**
	 * @param mixed $card
	 * @return list<string>
	 */
	private static function validate_card_config( $card ): array {
		if ( ! is_array( $card ) ) {
			return array( __( 'Card configuration must be an array.', 'catalogist' ) );
		}

		$errors    = array();
		$bool_keys = array( 'show_image', 'show_title', 'show_price', 'show_sku', 'show_stock' );

		foreach ( $bool_keys as $key ) {
			if ( isset( $card[ $key ] ) && ! is_bool( $card[ $key ] ) ) {
				$errors[] = sprintf(
					/* translators: %s is the card field name */
					__( 'Card field "%s" must be a boolean.', 'catalogist' ),
					$key
				);
			}
		}

		return $errors;
	}

	/**
	 * Sanitize and normalize configuration (fail-safe defaults for invalid values).
	 *
	 * @param array<string, mixed> $input
	 * @return array<string, mixed>
	 */
	public static function sanitize_configuration( array $input ): array {
		$defaults = self::default_configuration();
		$config   = self::apply_defaults( $defaults, $input );

		if ( ! isset( $config['version'] ) || ! is_string( $config['version'] ) || '' === trim( $config['version'] ) ) {
			$config['version'] = self::CONFIG_VERSION;
		}

		if ( ! in_array( $config['status'], self::ALLOWED_STATUSES, true ) ) {
			$config['status'] = 'draft';
		}

		// Header.
		if ( ! is_array( $config['header'] ) ) {
			$config['header'] = $defaults['header'];
		} else {
			$config['header']['enabled']    = isset( $config['header']['enabled'] ) ? (bool) $config['header']['enabled'] : $defaults['header']['enabled'];
			$config['header']['show_title'] = isset( $config['header']['show_title'] ) ? (bool) $config['header']['show_title'] : $defaults['header']['show_title'];
		}

		// Footer.
		if ( ! is_array( $config['footer'] ) ) {
			$config['footer'] = $defaults['footer'];
		} else {
			$config['footer']['enabled'] = isset( $config['footer']['enabled'] ) ? (bool) $config['footer']['enabled'] : $defaults['footer']['enabled'];
		}

		// Loop.
		if ( ! is_array( $config['loop'] ) ) {
			$config['loop'] = $defaults['loop'];
		} else {
			$columns                   = isset( $config['loop']['columns'] ) ? (int) $config['loop']['columns'] : $defaults['loop']['columns'];
			$config['loop']['columns'] = max( 1, min( 12, $columns ) );
		}

		// Card.
		if ( ! is_array( $config['card'] ) ) {
			$config['card'] = $defaults['card'];
		} else {
			foreach ( array_keys( $defaults['card'] ) as $key ) {
				$config['card'][ $key ] = isset( $config['card'][ $key ] ) ? (bool) $config['card'][ $key ] : $defaults['card'][ $key ];
			}
		}

		return $config;
	}

	/**
	 * Sanitize raw input (e.g. $_POST) into normalized template data.
	 *
	 * Keeps WP sanitization at trust boundary; no HTML involved.
	 *
	 * @param array<string, mixed> $input
	 * @return array{configuration: array<string, mixed>}
	 */
	public static function sanitize_input( array $input ): array {
		$config_input = array();

		if ( isset( $input['template_status'] ) ) {
			$status = sanitize_text_field( wp_unslash( $input['template_status'] ) );
			if ( in_array( $status, self::ALLOWED_STATUSES, true ) ) {
				$config_input['status'] = $status;
			}
		}

		if ( isset( $input['template_header'] ) && is_array( $input['template_header'] ) ) {
			$config_input['header'] = array(
				'enabled'    => ! empty( $input['template_header']['enabled'] ),
				'show_title' => ! empty( $input['template_header']['show_title'] ),
			);
		}

		if ( isset( $input['template_footer'] ) && is_array( $input['template_footer'] ) ) {
			$config_input['footer'] = array(
				'enabled' => ! empty( $input['template_footer']['enabled'] ),
			);
		}

		if ( isset( $input['template_loop'] ) && is_array( $input['template_loop'] ) ) {
			$columns              = isset( $input['template_loop']['columns'] ) ? intval( $input['template_loop']['columns'] ) : 3;
			$config_input['loop'] = array(
				'columns' => max( 1, min( 12, $columns ) ),
			);
		}

		if ( isset( $input['template_card'] ) && is_array( $input['template_card'] ) ) {
			$config_input['card'] = array(
				'show_image' => ! empty( $input['template_card']['show_image'] ),
				'show_title' => ! empty( $input['template_card']['show_title'] ),
				'show_price' => ! empty( $input['template_card']['show_price'] ),
				'show_sku'   => ! empty( $input['template_card']['show_sku'] ),
				'show_stock' => ! empty( $input['template_card']['show_stock'] ),
			);
		}

		// Allow direct configuration array (tests / programmatic).
		if ( isset( $input['configuration'] ) && is_array( $input['configuration'] ) ) {
			$config_input = array_merge( $config_input, $input['configuration'] );
		}

		$configuration = self::sanitize_configuration( $config_input );

		return array(
			'configuration' => $configuration,
		);
	}

	// --------------------------------------------------------------------
	// Save / Delete
	// --------------------------------------------------------------------

	/**
	 * Persist template configuration for a post.
	 *
	 * @param int   $post_id Template post ID.
	 * @param array $data    Sanitized data from sanitize_input().
	 * @return bool
	 */
	public static function save( int $post_id, array $data ): bool {
		$configuration = $data['configuration'] ?? self::default_configuration();

		$results   = array();
		$results[] = update_post_meta( $post_id, self::CTLG_META_CONFIGURATION, wp_json_encode( $configuration ) );
		$results[] = update_post_meta( $post_id, self::CTLG_META_VERSION, $configuration['version'] ?? self::CONFIG_VERSION );
		$results[] = update_post_meta( $post_id, self::CTLG_META_STATUS, $configuration['status'] ?? 'draft' );

		return ! in_array( false, $results, true );
	}

	/**
	 * Delete all template meta for a post.
	 *
	 * @param int $post_id
	 * @return bool
	 */
	public static function delete_meta( int $post_id ): bool {
		$results = array();
		foreach ( self::meta_keys() as $key ) {
			$results[] = delete_post_meta( $post_id, $key );
		}
		return ! in_array( false, $results, true );
	}

	// --------------------------------------------------------------------
	// Context binding (no HTML — Stage 9 owns rendering)
	// --------------------------------------------------------------------

	/**
	 * Bind template configuration with catalog context and optional catalog item.
	 *
	 * Pure data operation — no HTML, no WooCommerce, no external editor.
	 * Fail-safe: invalid template data falls back to defaults.
	 *
	 * @param array<string, mixed> $template_data Result of get_data().
	 * @param CatalogContext        $context
	 * @param CatalogItem|null      $item
	 * @return array<string, mixed> Bound data: {template, context, item, header, loop, card, footer}
	 */
	public static function bind( array $template_data, CatalogContext $context, ?CatalogItem $item = null ): array {
		$configuration = $template_data['configuration'] ?? self::default_configuration();

		// Fail-safe sanitize before binding.
		if ( ! is_array( $configuration ) ) {
			$configuration = self::default_configuration();
		} else {
			$errors = self::validate_configuration( $configuration );
			if ( ! empty( $errors ) ) {
				$configuration = self::sanitize_configuration( $configuration );
			}
		}

		return array(
			'template_id' => $template_data['id'] ?? 0,
			'template'    => $configuration,
			'header'      => $configuration['header'],
			'loop'        => $configuration['loop'],
			'card'        => $configuration['card'],
			'footer'      => $configuration['footer'],
			'context'     => $context,
			'item'        => $item,
			'exists'      => $template_data['exists'] ?? false,
		);
	}
}
