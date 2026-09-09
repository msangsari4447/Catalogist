<?php

declare(strict_types=1);

namespace Catalogist;

/**
 * Catalog data structure and helper methods.
 *
 * Represents a single catalog entity with its meta fields.
 * Transforms the Catalog CPT from a basic post type into a real,
 * persistent, validated configuration container for the Catalog pipeline.
 */
final class Catalog {

	/**
	 * Meta key for catalog description.
	 */
	public const META_DESCRIPTION = 'ctlg_catalog_description';

	/**
	 * Meta key for catalog settings (JSON encoded array).
	 *
	 * @deprecated Use CTLG_META_CONFIGURATION instead.
	 * Kept for backward compatibility with Stage 1–5 data.
	 */
	public const META_SETTINGS = 'ctlg_catalog_settings';

	/**
	 * Meta key for selected product IDs (JSON encoded array).
	 *
	 * @deprecated Use CTLG_META_CONFIGURATION instead.
	 * Kept for backward compatibility with Stage 1–5 data.
	 */
	public const META_PRODUCTS = 'ctlg_catalog_products';

	/**
	 * Meta key for catalog configuration (JSON encoded array).
	 *
	 * Stores the full structured configuration including version,
	 * status, filters, sort, selection, and layout settings.
	 */
	public const CTLG_META_CONFIGURATION = 'ctlg_catalog_configuration';

	/**
	 * Meta key for catalog version (string).
	 *
	 * @deprecated Stored inside CTLG_META_CONFIGURATION instead.
	 * Kept for backward compatibility.
	 */
	public const CTLG_META_VERSION = 'ctlg_catalog_version';

	/**
	 * Meta key for catalog status (string).
	 *
	 * @deprecated Stored inside CTLG_META_CONFIGURATION instead.
	 * Kept for backward compatibility.
	 */
	public const CTLG_META_STATUS = 'ctlg_catalog_status';

	/**
	 * Current configuration schema version.
	 */
	public const CONFIG_VERSION = '1.0.0';

	/**
	 * Allowed catalog statuses.
	 *
	 * @var list<string>
	 */
	private const ALLOWED_STATUSES = array(
		'draft',
		'active',
		'archived',
	);

	/**
	 * Allowed layout values.
	 *
	 * @var list<string>
	 */
	private const ALLOWED_LAYOUTS = array(
		'grid',
		'list',
		'table',
	);

	/**
	 * Allowed sort keys.
	 *
	 * @var list<string>
	 */
	private const ALLOWED_SORT_KEYS = array(
		'title',
		'price',
		'sku',
		'menu_order',
		'id',
	);

	/**
	 * Allowed sort directions.
	 *
	 * @var list<string>
	 */
	private const ALLOWED_SORT_DIRECTIONS = array(
		'asc',
		'desc',
	);
	private const ALLOWED_FILTER_TYPES    = array(
		'category',
	);
	// --------------------------------------------------------------------
	// Defaults
	// --------------------------------------------------------------------

	/**
	 * Default catalog configuration.
	 *
	 * @return array<string, mixed>
	 */
	public static function default_configuration(): array {
		return array(
			'version'   => self::CONFIG_VERSION,
			'status'    => 'draft',
			'filters'   => array(),
			'sort'      => array(
				'key'       => 'title',
				'direction' => 'asc',
			),
			'selection' => array(
				'offset' => 0,
				'limit'  => null,
			),
			'layout'    => array(
				'layout'     => 'grid',
				'columns'    => 3,
				'show_price' => true,
				'show_sku'   => false,
				'show_stock' => false,
			),
			'template'  => array(
				'id' => null,
			),
		);
	}

	/**
	 * Legacy default settings (backward compatibility).
	 *
	 * @return array<string, mixed>
	 */
	public static function default_settings(): array {
		return array(
			'layout'     => 'grid',
			'columns'    => 3,
			'show_price' => true,
			'show_sku'   => false,
			'show_stock' => false,
		);
	}

	// --------------------------------------------------------------------
	// Meta keys
	// --------------------------------------------------------------------

	/**
	 * Get all meta keys used by Catalog.
	 *
	 * @return array<int, string>
	 */
	public static function meta_keys(): array {
		return array(
			self::META_DESCRIPTION,
			self::META_SETTINGS,
			self::META_PRODUCTS,
			self::CTLG_META_CONFIGURATION,
			self::CTLG_META_VERSION,
			self::CTLG_META_STATUS,
		);
	}

	// --------------------------------------------------------------------
	// Load
	// --------------------------------------------------------------------

	/**
	 * Build catalog data array from post ID.
	 *
	 * Loads configuration from the new structured meta key while
	 * preserving backward compatibility with legacy meta keys.
	 *
	 * @param int $post_id Catalog post ID.
	 * @return array<string, mixed> Catalog data with defaults applied.
	 */
	public static function get_data( int $post_id ): array {
		$description = get_post_meta( $post_id, self::META_DESCRIPTION, true );
		$settings    = get_post_meta( $post_id, self::META_SETTINGS, true );
		$products    = get_post_meta( $post_id, self::META_PRODUCTS, true );
		$config      = get_post_meta( $post_id, self::CTLG_META_CONFIGURATION, true );
		$version     = get_post_meta( $post_id, self::CTLG_META_VERSION, true );
		$status      = get_post_meta( $post_id, self::CTLG_META_STATUS, true );
		if ( is_string( $settings ) && '' !== $settings ) {
			$decoded  = json_decode( $settings, true );
			$settings = is_array( $decoded ) ? $decoded : array();
		}
		// Build configuration from the structured meta key, falling back to legacy.
		$configuration = self::default_configuration();

		if ( is_string( $config ) && '' !== $config ) {
			$decoded = json_decode( $config, true );
			$config  = is_array( $decoded ) ? $decoded : null;
		}

		if ( is_array( $config ) ) {
			$configuration = self::apply_defaults( $configuration, $config );
		} elseif ( is_array( $settings ) || '' !== $settings ) {
			// Legacy: merge old settings array into configuration.
			$legacy_settings         = is_array( $settings ) ? $settings : array();
			$configuration['layout'] = array_merge(
				self::default_configuration()['layout'],
				$legacy_settings
			);
		}

		// Backward-compatible overrides from legacy meta keys.
		if ( '' !== $description && ! isset( $configuration['description'] ) ) {
			$configuration['description'] = $description;
		}

		// Legacy products.
		$products_array = array();
		if ( is_array( $products ) ) {
			$products_array = array_map( 'intval', array_filter( $products, 'is_numeric' ) );
		} elseif ( '' !== $products ) {
			$decoded = json_decode( $products, true );
			if ( is_array( $decoded ) ) {
				$products_array = array_map( 'intval', array_filter( $decoded, 'is_numeric' ) );
			}
		}

		return array(
			'id'            => $post_id,
			'title'         => get_the_title( $post_id ),
			'description'   => $configuration['description'] ?? '',
			'settings'      => $configuration['layout'] ?? self::default_configuration()['layout'],
			'products'      => $products_array,
			'configuration' => $configuration,
			'version'       => $version ? $version : ( $configuration['version'] ?? self::CONFIG_VERSION ),
			'status'        => $status ? $status : ( $configuration['status'] ?? 'draft' ),
			'created_at'    => get_the_date( 'c', $post_id ),
			'updated_at'    => get_the_modified_date( 'c', $post_id ),
		);
	}

	/**
	 * Apply defaults to partially-provided configuration.
	 *
	 * Missing keys retain their default values; provided keys override defaults.
	 *
	 * @param array<string, mixed> $defaults The full default configuration.
	 * @param array<string, mixed> $provided The partially-provided configuration.
	 * @return array<string, mixed> Merged configuration.
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
	 * Validate a catalog configuration array.
	 *
	 * Returns a list of validation errors. An empty list means valid.
	 *
	 * @param array<string, mixed> $config Configuration array.
	 * @return list<string> Validation errors.
	 */
	public static function validate_configuration( array $config ): array {
		$errors = array();

		// Version: must be a non-empty string.
		if ( ! isset( $config['version'] ) || ! is_string( $config['version'] ) || '' === trim( $config['version'] ) ) {
			$errors[] = __( 'Configuration version is required and must be a string.', 'catalogist' );
		}

		// Status: must be an allowed value.
		if ( ! isset( $config['status'] ) || ! in_array( $config['status'], self::ALLOWED_STATUSES, true ) ) {
			$errors[] = __( 'Catalog status must be one of: draft, active, archived.', 'catalogist' );
		}

		// Filters: must be an array of valid filter configs.
		if ( ! isset( $config['filters'] ) || ! is_array( $config['filters'] ) ) {
			$errors[] = __( 'Filters configuration must be an array.', 'catalogist' );
		} else {
			foreach ( $config['filters'] as $index => $filter ) {
				$filter_errors = self::validate_filter( $filter, $index );
				$errors        = array_merge( $errors, $filter_errors );
			}
		}

		// Sort configuration.
		if ( isset( $config['sort'] ) ) {
			$sort_errors = self::validate_sort_config( $config['sort'] );
			$errors      = array_merge( $errors, $sort_errors );
		}

		// Selection configuration.
		if ( isset( $config['selection'] ) ) {
			$selection_errors = self::validate_selection_config( $config['selection'] );
			$errors           = array_merge( $errors, $selection_errors );
		}

		// Layout configuration.
		if ( isset( $config['layout'] ) ) {
			$layout_errors = self::validate_layout_config( $config['layout'] );
			$errors        = array_merge( $errors, $layout_errors );
		}

		return $errors;
	}

	/**
	 * Validate a single filter configuration.
	 *
	 * @param mixed  $filter Raw filter configuration.
	 * @param int    $index  Filter index (for error messages).
	 * @return list<string> Validation errors.
	 */
	private static function validate_filter( $filter, int $index ): array {
		$errors = array();

		if ( ! is_array( $filter ) ) {
			// Translators: %d is the filter index number.
			return array( sprintf( __( 'Filter at index %d must be an array.', 'catalogist' ), $index ) );
		}

		if ( ! isset( $filter['type'] ) || ! is_string( $filter['type'] ) || '' === trim( $filter['type'] ) ) {
			// Translators: %d is the filter index number.
			$errors[] = sprintf( __( 'Filter at index %d is missing a valid type.', 'catalogist' ), $index );
		}

		if ( ! isset( $filter['value'] ) ) {
			// Translators: %d is the filter index number.
			$errors[] = sprintf( __( 'Filter at index %d is missing a value.', 'catalogist' ), $index );
		}

		// Validate type-specific constraints.
		if ( isset( $filter['type'] ) && '' !== trim( $filter['type'] ) ) {
			if ( ! in_array( $filter['type'], self::ALLOWED_FILTER_TYPES, true ) ) {
				$errors[] = sprintf(
					// translators: %1$d is the filter index, %2$s is the invalid filter type.
					__( 'Filter at index %1$d has an invalid type: "%2$s".', 'catalogist' ),
					$index,
					$filter['type']
				);
			}
		}

		return $errors;
	}

	/**
	 * Validate sort configuration.
	 *
	 * @param mixed $sort_config Sort configuration array.
	 * @return list<string> Validation errors.
	 */
	private static function validate_sort_config( $sort_config ): array {
		$errors = array();

		if ( ! is_array( $sort_config ) ) {
			return array( __( 'Sort configuration must be an array.', 'catalogist' ) );
		}

		if ( isset( $sort_config['key'] ) ) {
			if ( ! is_string( $sort_config['key'] ) || '' === trim( $sort_config['key'] ) ) {
				$errors[] = __( 'Sort key must be a non-empty string.', 'catalogist' );
			} elseif ( ! in_array( strtolower( trim( $sort_config['key'] ) ), self::ALLOWED_SORT_KEYS, true ) ) {
				$errors[] = sprintf(
					// Translators: %s is the invalid sort key. The second %s is the comma-separated list of allowed sort keys.
					__( 'Sort key "%1$s" is not supported. Allowed keys: %2$s.', 'catalogist' ),
					$sort_config['key'],
					implode( ', ', self::ALLOWED_SORT_KEYS )
				);
			}
		}

		if ( isset( $sort_config['direction'] ) ) {
			if ( ! is_string( $sort_config['direction'] ) || '' === trim( $sort_config['direction'] ) ) {
				$errors[] = __( 'Sort direction must be a non-empty string.', 'catalogist' );
			} elseif ( ! in_array( strtolower( trim( $sort_config['direction'] ) ), self::ALLOWED_SORT_DIRECTIONS, true ) ) {
				$errors[] = sprintf(
					// Translators: %s is the invalid sort direction. The second %s is the comma-separated list of allowed sort directions.
					__( 'Sort direction "%1$s" is not supported. Allowed directions: %2$s.', 'catalogist' ),
					$sort_config['direction'],
					implode( ', ', self::ALLOWED_SORT_DIRECTIONS )
				);
			}
		}

		return $errors;
	}

	/**
	 * Validate selection configuration.
	 *
	 * @param mixed $selection_config Selection configuration array.
	 * @return list<string> Validation errors.
	 */
	private static function validate_selection_config( $selection_config ): array {
		$errors = array();

		if ( ! is_array( $selection_config ) ) {
			return array( __( 'Selection configuration must be an array.', 'catalogist' ) );
		}

		if ( isset( $selection_config['offset'] ) ) {
			$offset = (int) $selection_config['offset'];
			if ( $offset < 0 ) {
				$errors[] = __( 'Selection offset must be a non-negative integer.', 'catalogist' );
			}
		}

		if ( isset( $selection_config['limit'] ) && null !== $selection_config['limit'] ) {
			$limit = (int) $selection_config['limit'];
			if ( $limit < 0 ) {
				$errors[] = __( 'Selection limit must be a non-negative integer or null.', 'catalogist' );
			}
		}

		return $errors;
	}

	/**
	 * Validate layout configuration.
	 *
	 * @param mixed $layout_config Layout configuration array.
	 * @return list<string> Validation errors.
	 */
	private static function validate_layout_config( $layout_config ): array {
		$errors = array();

		if ( ! is_array( $layout_config ) ) {
			return array( __( 'Layout configuration must be an array.', 'catalogist' ) );
		}

		if ( isset( $layout_config['layout'] ) ) {
			if ( ! in_array( $layout_config['layout'], self::ALLOWED_LAYOUTS, true ) ) {
				$errors[] = sprintf(
					// Translators: %s is the invalid layout. The second %s is the comma-separated list of allowed layouts.
					__( 'Layout "%1$s" is not supported. Allowed layouts: %2$s.', 'catalogist' ),
					$layout_config['layout'],
					implode( ', ', self::ALLOWED_LAYOUTS )
				);
			}
		}

		if ( isset( $layout_config['columns'] ) ) {
			$columns = (int) $layout_config['columns'];
			if ( $columns < 1 || $columns > 12 ) {
				$errors[] = __( 'Layout columns must be between 1 and 12.', 'catalogist' );
			}
		}

		return $errors;
	}

	/**
	 * Sanitize and normalize a configuration array.
	 *
	 * Takes a partially-provided configuration, validates it, and returns
	 * a normalized configuration with defaults applied for missing fields.
	 * Invalid values are replaced with safe defaults.
	 *
	 * @param array<string, mixed> $input Raw configuration input.
	 * @return array<string, mixed> Sanitized and validated configuration.
	 */
	public static function sanitize_configuration( array $input ): array {
		$defaults = self::default_configuration();
		$config   = self::apply_defaults( $defaults, $input );

		// Ensure version is set.
		if ( ! isset( $config['version'] ) || ! is_string( $config['version'] ) ) {
			$config['version'] = self::CONFIG_VERSION;
		}

		// Ensure status is valid.
		if ( ! in_array( $config['status'], self::ALLOWED_STATUSES, true ) ) {
			$config['status'] = 'draft';
		}

		// Ensure filters is an array.
		if ( ! is_array( $config['filters'] ) ) {
			$config['filters'] = array();
		}

		// Ensure sort is valid.
		if ( ! is_array( $config['sort'] ) ) {
			$config['sort'] = array(
				'key'       => 'title',
				'direction' => 'asc',
			);
		} else {
			if ( ! in_array( $config['sort']['key'] ?? '', self::ALLOWED_SORT_KEYS, true ) ) {
				$config['sort']['key'] = 'title';
			}
			if ( ! in_array( $config['sort']['direction'] ?? '', self::ALLOWED_SORT_DIRECTIONS, true ) ) {
				$config['sort']['direction'] = 'asc';
			}
		}

		// Ensure selection is valid.
		if ( ! is_array( $config['selection'] ) ) {
			$config['selection'] = array(
				'offset' => 0,
				'limit'  => null,
			);
		} else {
			$offset                        = $config['selection']['offset'] ?? 0;
			$config['selection']['offset'] = max( 0, (int) $offset );

			$limit = $config['selection']['limit'] ?? null;
			if ( null !== $limit ) {
				$config['selection']['limit'] = max( 0, (int) $limit );
			} else {
				$config['selection']['limit'] = null;
			}
		}

		// Ensure layout is valid.
		if ( ! is_array( $config['layout'] ) ) {
			$config['layout'] = self::default_settings();
		} else {
			if ( ! in_array( $config['layout']['layout'] ?? '', self::ALLOWED_LAYOUTS, true ) ) {
				$config['layout']['layout'] = 'grid';
			}
			$columns                        = $config['layout']['columns'] ?? 3;
			$config['layout']['columns']    = max( 1, min( 12, (int) $columns ) );
			$config['layout']['show_price'] = (bool) ( $config['layout']['show_price'] ?? true );
			$config['layout']['show_sku']   = (bool) ( $config['layout']['show_sku'] ?? false );
			$config['layout']['show_stock'] = (bool) ( $config['layout']['show_stock'] ?? false );
		}

		return $config;
	}

	// --------------------------------------------------------------------
	// Sanitize input (backward-compatible with existing Admin form)
	// --------------------------------------------------------------------

	/**
	 * Validate and sanitize catalog input data from $_POST.
	 *
	 * @param array<string, mixed> $input Raw input data.
	 * @return array{description: string, settings: array<string, mixed>, products: array<int>, configuration: array<string, mixed>} Sanitized data.
	 */
	public static function sanitize_input( array $input ): array {
		$description = isset( $input['catalog_description'] )
			? sanitize_textarea_field( wp_unslash( $input['catalog_description'] ) )
			: '';
		// Build configuration from input.
		$config_input = array();

		// Legacy settings.
		if ( isset( $input['catalog_settings'] ) && is_array( $input['catalog_settings'] ) ) {
			$config_input['layout'] = array();
			$defaults               = self::default_settings();
			foreach ( $defaults as $key => $default ) {
				if ( isset( $input['catalog_settings'][ $key ] ) ) {
					$value = $input['catalog_settings'][ $key ];
					if ( is_bool( $default ) ) {
						$config_input['layout'][ $key ] = (bool) $value;
					} elseif ( is_int( $default ) ) {
						$config_input['layout'][ $key ] = max( 1, intval( $value ) );
					} else {
						$sanitized = sanitize_text_field( wp_unslash( $value ) );
						// Validate layout value against allowed list.
						if ( 'layout' === $key && ! in_array( $sanitized, self::ALLOWED_LAYOUTS, true ) ) {
							$sanitized = 'grid';
						}
						$config_input['layout'][ $key ] = $sanitized;
					}
				} else {
					$config_input['layout'][ $key ] = $default;
				}
			}
		}

		// Sort configuration.
		if ( isset( $input['catalog_sort_key'] ) ) {
			$key = sanitize_text_field( wp_unslash( $input['catalog_sort_key'] ) );
			if ( in_array( $key, self::ALLOWED_SORT_KEYS, true ) ) {
				$config_input['sort']['key'] = $key;
			}
		}
		if ( isset( $input['catalog_sort_direction'] ) ) {
			$direction = sanitize_text_field( wp_unslash( $input['catalog_sort_direction'] ) );
			if ( in_array( $direction, self::ALLOWED_SORT_DIRECTIONS, true ) ) {
				$config_input['sort']['direction'] = $direction;
			}
		}

		// Selection configuration.
		if ( isset( $input['catalog_selection_offset'] ) ) {
			$offset = intval( $input['catalog_selection_offset'] );
			if ( $offset >= 0 ) {
				$config_input['selection']['offset'] = $offset;
			}
		}
		if ( isset( $input['catalog_selection_limit'] ) ) {
			$limit = intval( $input['catalog_selection_limit'] );
			if ( $limit >= 0 ) {
				$config_input['selection']['limit'] = $limit;
			}
		}

		// Status.
		if ( isset( $input['catalog_status'] ) ) {
			$status = sanitize_text_field( wp_unslash( $input['catalog_status'] ) );
			if ( in_array( $status, self::ALLOWED_STATUSES, true ) ) {
				$config_input['status'] = $status;
			}
		}

		// Template reference (post ID).
		if ( isset( $input['catalog_template_id'] ) ) {
			$template_id = intval( $input['catalog_template_id'] );
			if ( $template_id > 0 ) {
				$config_input['template']['id'] = $template_id;
			}
		}

		// Sanitize and normalize the full configuration.
		$configuration = self::sanitize_configuration( $config_input );

		// Legacy products.
		$products = array();
		if ( isset( $input['catalog_products'] ) && is_array( $input['catalog_products'] ) ) {
			foreach ( $input['catalog_products'] as $product_id ) {
				$id = intval( $product_id );
				if ( $id > 0 ) {
					$products[] = $id;
				}
			}
		}

		return array(
			'description'   => $description,
			'settings'      => $configuration['layout'] ?? self::default_settings(),
			'products'      => $products,
			'configuration' => $configuration,
		);
	}

	// --------------------------------------------------------------------
	// Save
	// --------------------------------------------------------------------

	/**
	 * Save catalog meta data for a post.
	 *
	 * Persists both legacy meta keys (for backward compatibility) and
	 * the new structured configuration meta key.
	 *
	 * @param int   $post_id     Catalog post ID.
	 * @param array $data        Sanitized data from sanitize_input().
	 * @return bool True on success.
	 */
	public static function save( int $post_id, array $data ): bool {
		$results = array();

		// Legacy meta keys.
		$results[] = update_post_meta( $post_id, self::META_DESCRIPTION, $data['description'] );
		$results[] = update_post_meta( $post_id, self::META_SETTINGS, $data['settings'] );
		$results[] = update_post_meta( $post_id, self::META_PRODUCTS, $data['products'] );

		// New structured configuration.
		$configuration = $data['configuration'] ?? self::default_configuration();

		if ( ! isset( $data['configuration'] ) && isset( $data['settings'] ) && is_array( $data['settings'] ) ) {
			$configuration['layout'] = array_merge(
				$configuration['layout'],
				$data['settings']
			);
		}
		$results[] = update_post_meta( $post_id, self::CTLG_META_CONFIGURATION, wp_json_encode( $configuration ) );
		$results[] = update_post_meta( $post_id, self::CTLG_META_VERSION, $configuration['version'] ?? self::CONFIG_VERSION );
		$results[] = update_post_meta( $post_id, self::CTLG_META_STATUS, $configuration['status'] ?? 'draft' );

		return ! in_array( false, $results, true );
	}

	// --------------------------------------------------------------------
	// Delete
	// --------------------------------------------------------------------

	/**
	 * Delete all catalog meta for a post.
	 *
	 * @param int $post_id Catalog post ID.
	 * @return bool True on success.
	 */
	public static function delete_meta( int $post_id ): bool {
		$results = array();
		foreach ( self::meta_keys() as $key ) {
			$results[] = delete_post_meta( $post_id, $key );
		}
		return ! in_array( false, $results, true );
	}
}
