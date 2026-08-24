<?php
/**
 * Variation query argument value object.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Variation;

defined( 'ABSPATH' ) || exit;

/**
 * Represents query arguments for variation retrieval and expansion.
 */
final class VariationQueryArgs {

	/**
	 * Variation expansion mode.
	 *
	 * @var VariationMode
	 */
	private VariationMode $mode;

	/**
	 * Selected variation IDs.
	 *
	 * @var array<int>
	 */
	private array $selected_variation_ids = array();

	/**
	 * Excluded variation IDs.
	 *
	 * @var array<int>
	 */
	private array $exclude_variation_ids = array();

	/**
	 * Constructor.
	 *
	 * @param VariationMode     $mode                    Variation mode.
	 * @param array<int>        $selected_variation_ids  Selected variation IDs.
	 * @param array<int>        $exclude_variation_ids   Excluded variation IDs.
	 */
	public function __construct(
		VariationMode $mode,
		array $selected_variation_ids = array(),
		array $exclude_variation_ids = array()
	) {
		$this->mode                   = $mode;
		$this->selected_variation_ids = $selected_variation_ids;
		$this->exclude_variation_ids  = $exclude_variation_ids;
	}

	/**
	 * Get the variation mode.
	 *
	 * @return VariationMode
	 */
	public function get_mode(): VariationMode {
		return $this->mode;
	}

	/**
	 * Get selected variation IDs.
	 *
	 * @return array<int>
	 */
	public function get_selected_variation_ids(): array {
		return $this->selected_variation_ids;
	}

	/**
	 * Get excluded variation IDs.
	 *
	 * @return array<int>
	 */
	public function get_exclude_variation_ids(): array {
		return $this->exclude_variation_ids;
	}

	/**
	 * Create from an array of arguments.
	 *
	 * @param array<string, mixed> $args Query arguments.
	 *
	 * @return self
	 */
	public static function from_array( array $args ): self {
		$mode = VariationMode::from_string( $args['variation_mode'] ?? 'parent' );
		if ( null === $mode ) {
			$mode = new VariationMode( VariationMode::PARENT );
		}

		$selected = array();
		if ( isset( $args['selected_variation_ids'] ) && is_array( $args['selected_variation_ids'] ) ) {
			$selected = array_values( array_filter(
				array_map( 'absint', $args['selected_variation_ids'] ),
				function ( $id ) {
					return $id > 0;
				}
			) );
		}

		$excluded = array();
		if ( isset( $args['exclude_variation_ids'] ) && is_array( $args['exclude_variation_ids'] ) ) {
			$excluded = array_values( array_filter(
				array_map( 'absint', $args['exclude_variation_ids'] ),
				function ( $id ) {
					return $id > 0;
				}
			) );
		}

		return new self( $mode, $selected, $excluded );
	}

	/**
	 * Convert to array.
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return array(
			'variation_mode'            => $this->mode->value(),
			'selected_variation_ids'    => $this->selected_variation_ids,
			'exclude_variation_ids'     => $this->exclude_variation_ids,
		);
	}
}
