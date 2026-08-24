<?php
/**
 * Variation mode value object.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Variation;

defined( 'ABSPATH' ) || exit;

/**
 * Represents a variation expansion mode.
 *
 * Five modes supported per CLAUDE.md:
 * - parent: variable product stays as a single catalog entry
 * - all: every variation becomes its own catalog entry
 * - selected: user picks one specific variation per parent
 * - multiple: user picks multiple specific variations per parent
 * - table: parent shown with embedded variation data
 */
final class VariationMode {

	public const PARENT  = 'parent';
	public const ALL     = 'all';
	public const SELECTED = 'selected';
	public const MULTIPLE = 'multiple';
	public const TABLE   = 'table';

	/**
	 * All supported modes.
	 *
	 * @var array<string>
	 */
	private const ALLOWED_MODES = array(
		self::PARENT,
		self::ALL,
		self::SELECTED,
		self::MULTIPLE,
		self::TABLE,
	);

	/**
	 * Modes that expand beyond the parent product.
	 *
	 * @var array<string>
	 */
	private const EXPANSION_MODES = array(
		self::ALL,
		self::SELECTED,
		self::MULTIPLE,
		self::TABLE,
	);

	/**
	 * Mode value.
	 *
	 * @var string
	 */
	private string $mode;

	/**
	 * Constructor.
	 *
	 * @param string $mode Mode value.
	 */
	public function __construct( string $mode ) {
		$this->mode = $mode;
	}

	/**
	 * Get the mode string.
	 *
	 * @return string
	 */
	public function value(): string {
		return $this->mode;
	}

	/**
	 * Create a VariationMode from a string.
	 *
	 * @param string $mode Mode string.
	 *
	 * @return self|null
	 */
	public static function from_string( string $mode ): ?self {
		$mode = strtolower( trim( $mode ) );

		if ( ! in_array( $mode, self::ALLOWED_MODES, true ) ) {
			return null;
		}

		return new self( $mode );
	}

	/**
	 * Check if this mode expands variations.
	 *
	 * @return bool
	 */
	public function is_expansion_mode(): bool {
		return in_array( $this->mode, self::EXPANSION_MODES, true );
	}

	/**
	 * Check if this mode returns only the parent.
	 *
	 * @return bool
	 */
	public function is_parent_mode(): bool {
		return self::PARENT === $this->mode;
	}

	/**
	 * Check if this mode includes table layout.
	 *
	 * @return bool
	 */
	public function is_table_mode(): bool {
		return self::TABLE === $this->mode;
	}

	/**
	 * Get all allowed modes.
	 *
	 * @return array<string>
	 */
	public static function get_allowed_modes(): array {
		return self::ALLOWED_MODES;
	}

	/**
	 * Get all expansion modes.
	 *
	 * @return array<string>
	 */
	public static function get_expansion_modes(): array {
		return self::EXPANSION_MODES;
	}

	/**
	 * Convert to string.
	 *
	 * @return string
	 */
	public function __toString(): string {
		return $this->mode;
	}
}
